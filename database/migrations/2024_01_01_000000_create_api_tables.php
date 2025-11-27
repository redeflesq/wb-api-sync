<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Конфигурация API
        Schema::create('api_configs', function (Blueprint $table) {
            $table->id();
            $table->string('api_key');
            $table->string('api_host');
            $table->timestamps();
        });

        // Продажи
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->string('unique_hash')->unique(); 
            $table->json('data');
            $table->timestamp('sale_date')->nullable();
            $table->timestamps();
            $table->index('sale_date');
            $table->index('unique_hash');
        });

        // Заказы
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('unique_hash')->unique(); 
            $table->json('data');
            $table->timestamp('order_date')->nullable();
            $table->timestamps();
            $table->index('order_date');
            $table->index('unique_hash');
        });

        // Склады
        Schema::create('stocks', function (Blueprint $table) {
            $table->id();
            $table->string('unique_hash')->unique(); 
            $table->json('data');
            $table->date('stock_date')->nullable();
            $table->timestamps();
            $table->index('stock_date');
            $table->index('unique_hash');
        });

        // Доходы
        Schema::create('incomes', function (Blueprint $table) {
            $table->id();
            $table->string('unique_hash')->unique(); 
            $table->json('data');
            $table->timestamp('income_date')->nullable();
            $table->timestamps();
            $table->index('income_date');
            $table->index('unique_hash');
        });

        // Логи синхронизации
        Schema::create('sync_logs', function (Blueprint $table) {
            $table->id();
            $table->string('entity_type');
            $table->integer('records_processed')->default(0);
            $table->integer('records_saved')->default(0);
            $table->integer('records_failed')->default(0);
            $table->string('status');
            $table->text('error_message')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('sync_logs');
        Schema::dropIfExists('incomes');
        Schema::dropIfExists('stocks');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('sales');
        Schema::dropIfExists('api_configs');
    }
};