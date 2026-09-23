<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One row per receipt: money collected from a member seat on a date.
     * How it is spread across months lives in payment_allocations.
     * Amounts are whole rupees.
     */
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->string('receipt_number', 20)->nullable()->unique();
            $table->foreignId('chit_group_member_id')->constrained()->restrictOnDelete();
            $table->foreignId('chit_group_id')->constrained()->restrictOnDelete();
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('amount');
            $table->date('paid_on');
            $table->string('method', 20);
            $table->string('reference', 100)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('paid_on');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
