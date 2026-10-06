<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sri Lakshmi Micro Finance capital: money put into the business to lend
 * (invest) or taken back out (withdraw). With loans given and collections
 * it gives the money available to lend.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('finance_capital', function (Blueprint $table) {
            $table->id();
            $table->date('entry_on');
            $table->string('type', 10);
            $table->unsignedInteger('amount');
            $table->string('method', 20)->default('cash');
            $table->text('notes')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('entry_on');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('finance_capital');
    }
};
