<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sri Lakshmi Micro Finance: loans (a processing fee and GST on it are cut
 * when the money is given; interest per annum for the loan period is added,
 * and the total is repaid in daily or weekly instalments), the collections
 * against them, and the business's own expenses and partner settlements.
 * Amounts are whole rupees.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('finance_loans', function (Blueprint $table) {
            $table->id();
            $table->string('loan_number', 20)->nullable()->unique();
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->date('loaned_on');
            $table->unsignedInteger('principal');
            $table->decimal('processing_fee_rate', 5, 2);
            $table->unsignedInteger('processing_fee');
            $table->decimal('gst_rate', 5, 2);
            $table->unsignedInteger('gst');
            $table->decimal('interest_rate', 5, 2);
            $table->unsignedInteger('interest');
            $table->unsignedSmallInteger('term_days');
            $table->unsignedInteger('loan_amount');
            $table->string('frequency', 10);
            $table->unsignedInteger('installment_amount');
            $table->unsignedSmallInteger('installments');
            $table->date('first_due_on');
            $table->string('status', 10)->default('active');
            $table->date('closed_on')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'loaned_on']);
            $table->index('loaned_on');
        });

        Schema::create('finance_collections', function (Blueprint $table) {
            $table->id();
            $table->string('receipt_number', 20)->nullable()->unique();
            $table->foreignId('finance_loan_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->dateTime('collected_at');
            $table->unsignedInteger('amount');
            $table->string('method', 20);
            $table->string('reference', 100)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('collected_at');
            $table->index(['finance_loan_id', 'collected_at']);
        });

        Schema::create('finance_expenses', function (Blueprint $table) {
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

        Schema::create('finance_partner_settlements', function (Blueprint $table) {
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
        Schema::dropIfExists('finance_partner_settlements');
        Schema::dropIfExists('finance_expenses');
        Schema::dropIfExists('finance_collections');
        Schema::dropIfExists('finance_loans');
    }
};
