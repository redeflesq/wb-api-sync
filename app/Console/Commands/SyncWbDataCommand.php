<?php

namespace App\Console\Commands;

use App\Services\WbApiService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SyncWbDataCommand extends Command
{
    protected $signature = 'wb:sync 
                            {entity? : sales, orders, stocks, incomes or all}
                            {--date-from= : Start date (Y-m-d)}
                            {--date-to= : End date (Y-m-d)}
                            {--force : Force resync all data}';

    protected $description = 'Синхронизация данных с WB API 2';

    private WbApiService $apiService;

    public function __construct(WbApiService $apiService)
    {
        parent::__construct();
        $this->apiService = $apiService;
    }

    public function handle()
    {
        $entity = $this->argument('entity') ?? 'all';
        $dateFrom = $this->option('date-from') ?? Carbon::now()->subDays(30)->format('Y-m-d');
        $dateTo = $this->option('date-to') ?? Carbon::now()->format('Y-m-d');
        $force = $this->option('force');

        $this->info("Синхронизация: {$entity}");
        $this->info("Период: {$dateFrom} - {$dateTo}");
        if ($force) {
            $this->info("Режим: FORCE (пересинхронизация)");
        }

        $entities = $entity === 'all' 
            ? ['sales', 'orders', 'stocks', 'incomes']
            : [$entity];

        foreach ($entities as $entityType) {
            $this->syncEntity($entityType, $dateFrom, $dateTo, $force);
        }

        $this->info("\nСинхронизация завершена!");
    }

    private function syncEntity(string $entity, string $dateFrom, string $dateTo, bool $force)
    {
        $this->info("Синхронизация: " . strtoupper($entity));
        
        $logId = DB::table('sync_logs')->insertGetId([
            'entity_type' => $entity,
            'status' => 'processing',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $totalProcessed = 0;
        $totalSaved = 0;
        $totalSkipped = 0;
        $totalFailed = 0;
        $page = 1;
        $hasMorePages = true;

        try {
            while ($hasMorePages) {
                $this->info("\nЗагрузка страницы {$page}...");
                
                $response = $this->fetchData($entity, $dateFrom, $dateTo, $page);

                if (isset($response['error'])) {
                    throw new \Exception($response['error']);
                }

                $data = $response['data'] ?? [];
                
                if (empty($data)) {
                    $this->warn("   Нет данных на странице {$page}");
                    $hasMorePages = false;
                    break;
                }

                $this->info("   Получено записей: " . count($data));

                foreach ($data as $item) {
                    $totalProcessed++;
                    
                    try {
                        $result = $this->saveItem($entity, $item, $force);
                        
                        if ($result === 'saved') {
                            $totalSaved++;
                        } elseif ($result === 'skipped') {
                            $totalSkipped++;
                        }
                        
                        // Прогресс бар
                        if ($totalProcessed % 50 === 0) {
                            $this->info("   Обработано: {$totalProcessed}, Сохранено: {$totalSaved}, Пропущено: {$totalSkipped}");
                        }
                        
                    } catch (\Exception $e) {
                        $totalFailed++;
                        $this->error("   Ошибка записи #{$totalProcessed}: " . $e->getMessage());
                    }
                }

                $this->info("Страница {$page} обработана: записей {$totalProcessed}, сохранено {$totalSaved}, пропущено {$totalSkipped}");

                // Проверка на последнюю страницу
                if (count($data) < 500) {
                    $hasMorePages = false;
                    $this->info("   Последняя страница (получено меньше 500 записей)");
                }

                $page++;
                
                // Пауза между запросами
                if ($hasMorePages) {
                    usleep(100000);
                }
            }

            DB::table('sync_logs')->where('id', $logId)->update([
                'records_processed' => $totalProcessed,
                'records_saved' => $totalSaved,
                'records_failed' => $totalFailed,
                'status' => 'completed',
                'updated_at' => now(),
            ]);

            $this->info("\n" . str_repeat("=", 60));
            $this->info("{$entity}: ЗАВЕРШЕНО");
            $this->info("Обработано: {$totalProcessed}");
            $this->info("Сохранено: {$totalSaved}");
            $this->info("Пропущено: {$totalSkipped} (уже существуют)");
            $this->info("Ошибок: {$totalFailed}");
            $this->info(str_repeat("=", 60));

        } catch (\Exception $e) {
            DB::table('sync_logs')->where('id', $logId)->update([
                'records_processed' => $totalProcessed,
                'records_saved' => $totalSaved,
                'records_failed' => $totalFailed,
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'updated_at' => now(),
            ]);

            $this->error("\n {$entity}: ОШИБКА - " . $e->getMessage());
        }
    }

    private function fetchData(string $entity, string $dateFrom, string $dateTo, int $page): array
    {
        return match ($entity) {
            'sales' => $this->apiService->getSales($dateFrom, $dateTo, $page),
            'orders' => $this->apiService->getOrders($dateFrom, $dateTo, $page),
            'stocks' => $this->apiService->getStocks($dateFrom, $page),
            'incomes' => $this->apiService->getIncomes($dateFrom, $dateTo, $page),
            default => ['data' => [], 'error' => 'Unknown entity type'],
        };
    }

    private function saveItem(string $entity, array $item, bool $force): string
    {
        $table = $entity;
        
        // Генерируем уникальный хэш из ВСЕХ данных записи
        $uniqueHash = md5(json_encode($item));
        
        $dateField = match ($entity) {
            'sales' => 'sale_date',
            'orders' => 'order_date',
            'stocks' => 'stock_date',
            'incomes' => 'income_date',
        };

        // Извлекаем дату из данных если есть
        $date = $item['date'] ?? $item['created_at'] ?? $item['lastChangeDate'] ?? now();

        // Проверяем существует ли уже такая запись
        if (!$force) {
            $exists = DB::table($table)
                ->where('unique_hash', $uniqueHash)
                ->exists();
            
            if ($exists) {
                return 'skipped'; // Запись уже существует, пропускаем
            }
        }

        // Вставляем или обновляем запись
        DB::table($table)->updateOrInsert(
            ['unique_hash' => $uniqueHash],
            [
                'data' => json_encode($item, JSON_UNESCAPED_UNICODE),
                $dateField => $date,
                'updated_at' => now(),
                'created_at' => DB::raw('COALESCE(created_at, NOW())'),
            ]
        );
        
        return 'saved';
    }
}