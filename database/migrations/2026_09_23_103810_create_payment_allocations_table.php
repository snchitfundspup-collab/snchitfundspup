<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Which installment month(s) a payment covers. A ₹300 daily payment is
     * one allocation to the oldest unpaid month; three full months at once
     * are three allocations.
     */
    public function up(): void
    {
        Schema::create('payment_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('chit_group_member_id')->constrained()->restrictOnDelete();
            $table->unsignedSmallInteger('month_number');
            $table->unsignedBigInteger('amount');
            $table->timestamps();

            $table->index(['chit_group_member_id', 'month_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_allocations');
    }
};
