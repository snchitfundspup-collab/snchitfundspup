<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * SN Traders (rice purchase and sales): its own tables, separate from
     * the chit fund. Customers are shared (the customers table).
     */
    public function up(): void
    {
        Schema::create('trader_varieties', function (Blueprint $table) {
            $table->id();
            $table->string('name', 80)->unique();
            $table->decimal('bag_kg', 8, 2)->default(26);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('trader_suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('phone', 20)->nullable();
            $table->string('place', 120)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('trader_purchases', function (Blueprint $table) {
            $table->id();
            $table->string('purchase_number', 20)->nullable()->unique();
            $table->foreignId('supplier_id')->constrained('trader_suppliers')->restrictOnDelete();
            $table->date('purchased_on');
            $table->string('supplier_bill_no', 60)->nullable();
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->string('method', 20);
            $table->text('notes')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('purchased_on');
        });

        Schema::create('trader_purchase_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_id')->constrained('trader_purchases')->cascadeOnDelete();
            $table->foreignId('variety_id')->constrained('trader_varieties')->restrictOnDelete();
            $table->unsignedInteger('bags')->default(0);
            $table->decimal('bag_kg', 8, 2);
            $table->decimal('loose_kg', 10, 2)->default(0);
            $table->decimal('kg', 12, 2);
            $table->decimal('rate', 10, 2);
            $table->string('rate_per', 3);
            $table->decimal('amount', 12, 2);
            $table->timestamps();
        });

        Schema::create('trader_sales', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number', 20)->nullable()->unique();
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->date('sold_on');
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('sold_on');
            $table->index(['customer_id', 'sold_on']);
        });

        Schema::create('trader_sale_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained('trader_sales')->cascadeOnDelete();
            $table->foreignId('variety_id')->constrained('trader_varieties')->restrictOnDelete();
            $table->unsignedInteger('bags')->default(0);
            $table->decimal('bag_kg', 8, 2);
            $table->decimal('loose_kg', 10, 2)->default(0);
            $table->decimal('kg', 12, 2);
            $table->decimal('rate', 10, 2);
            $table->string('rate_per', 3);
            $table->decimal('amount', 12, 2);
            $table->timestamps();
        });

        /* money received from customers (at the sale, or later against credit) */
        Schema::create('trader_receipts', function (Blueprint $table) {
            $table->id();
            $table->string('receipt_number', 20)->nullable()->unique();
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->foreignId('sale_id')->nullable()->constrained('trader_sales')->cascadeOnDelete();
            $table->dateTime('received_at');
            $table->decimal('amount', 12, 2);
            $table->string('method', 20);
            $table->string('reference', 100)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('received_at');
            $table->index(['customer_id', 'received_at']);
        });

        Schema::create('trader_expenses', function (Blueprint $table) {
            $table->id();
            $table->date('spent_on');
            $table->string('description', 200);
            $table->unsignedInteger('amount');
            $table->foreignId('paid_by')->constrained('users')->restrictOnDelete();
            $table->string('paid_to', 120)->nullable();
            $table->string('method', 20);
            $table->string('reference', 100)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('spent_on');
            $table->index(['paid_by', 'spent_on']);
        });

        Schema::create('trader_partner_settlements', function (Blueprint $table) {
            $table->id();
            $table->date('settled_on');
            $table->foreignId('from_user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('to_user_id')->constrained('users')->restrictOnDelete();
            $table->unsignedInteger('amount');
            $table->string('method', 20);
            $table->string('reference', 100)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('settled_on');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trader_partner_settlements');
        Schema::dropIfExists('trader_expenses');
        Schema::dropIfExists('trader_receipts');
        Schema::dropIfExists('trader_sale_items');
        Schema::dropIfExists('trader_sales');
        Schema::dropIfExists('trader_purchase_items');
        Schema::dropIfExists('trader_purchases');
        Schema::dropIfExists('trader_suppliers');
        Schema::dropIfExists('trader_varieties');
    }
};
