<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The interested members the admin put into a draw (the wheel).
     */
    public function up(): void
    {
        Schema::create('draw_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('draw_id')->constrained()->cascadeOnDelete();
            $table->foreignId('chit_group_member_id')->constrained()->restrictOnDelete();
            $table->timestamps();

            $table->unique(['draw_id', 'chit_group_member_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('draw_participants');
    }
};
