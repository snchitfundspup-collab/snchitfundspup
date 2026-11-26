<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Money in each agent's hand (Sri Lakshmi Micro Finance): opening balance,
 * money taken from the treasury, money returned to it and money passed
 * from one agent to another. Loans and collections are added from their
 * own tables.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('finance_agent_cash', function (Blueprint $table) {
            $table->id();
            $table->dateTime('entry_at');
            $table->string('type', 20);
            $table->foreignId('agent_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('other_agent_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->unsignedBigInteger('amount');
            $table->string('notes', 1000)->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['agent_id', 'entry_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('finance_agent_cash');
    }
};
