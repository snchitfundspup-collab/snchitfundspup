<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * SN Traders: rice orders customers place from their own pages. The
     * office turns an order into a sale (invoice) or cancels it.
     */
    public function up(): void
    {
        Schema::create('trader_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number', 20)->nullable()->unique();
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->string('status', 12)->default('new');
            $table->decimal('estimated_total', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->foreignId('sale_id')->nullable()->constrained('trader_sales')->nullOnDelete();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index(['customer_id', 'created_at']);
        });

        Schema::create('trader_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('trader_orders')->cascadeOnDelete();
            $table->foreignId('variety_id')->constrained('trader_varieties')->restrictOnDelete();
            $table->unsignedInteger('bags');
            $table->decimal('rate', 10, 2)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trader_order_items');
        Schema::dropIfExists('trader_orders');
    }
};
