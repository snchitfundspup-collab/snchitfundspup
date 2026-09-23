<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Chit groups. Named "chit_groups" because GROUPS is a reserved word
     * in MySQL 8. Money columns hold whole rupees.
     */
    public function up(): void
    {
        Schema::create('chit_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('type', 20)->default('draw');
            $table->unsignedBigInteger('amount');
            $table->unsignedSmallInteger('months');
            $table->unsignedSmallInteger('member_count')->default(20);
            $table->unsignedBigInteger('commission_amount')->default(0);
            $table->date('start_date');
            $table->string('status', 20)->default('forming');
            $table->timestamp('started_at')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chit_groups');
    }
};
