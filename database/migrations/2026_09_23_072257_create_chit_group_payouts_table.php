<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Withdrawal schedule: the amount paid out to the winner in each month
     * of a group, typed in by the admin (whole rupees).
     */
    public function up(): void
    {
        Schema::create('chit_group_payouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chit_group_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('month_number');
            $table->unsignedBigInteger('withdrawal_amount');
            $table->timestamps();

            $table->unique(['chit_group_id', 'month_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chit_group_payouts');
    }
};
