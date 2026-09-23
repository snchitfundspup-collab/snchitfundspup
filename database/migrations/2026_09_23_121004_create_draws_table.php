<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One draw per group month. The winner (a member seat) wins once per
     * group. The payout columns are filled when the prize money is handed
     * over, which also gives the payout voucher its number (PV000001 …).
     * Amounts are whole rupees; dates are office local time.
     */
    public function up(): void
    {
        Schema::create('draws', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chit_group_id')->constrained()->restrictOnDelete();
            $table->unsignedSmallInteger('month_number');
            $table->foreignId('winner_member_id')->constrained('chit_group_members')->restrictOnDelete();
            $table->unsignedBigInteger('withdrawal_amount');
            $table->dateTime('drawn_at');
            $table->foreignId('drawn_by')->nullable()->constrained('users')->nullOnDelete();

            $table->string('voucher_number', 20)->nullable()->unique();
            $table->unsignedBigInteger('payout_amount')->nullable();
            $table->string('payout_method', 20)->nullable();
            $table->string('payout_reference', 100)->nullable();
            $table->text('payout_notes')->nullable();
            $table->dateTime('paid_at')->nullable();
            $table->foreignId('paid_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->unique(['chit_group_id', 'month_number']);
            $table->unique(['chit_group_id', 'winner_member_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('draws');
    }
};
