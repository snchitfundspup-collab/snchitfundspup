<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Seats in a chit group. One customer can hold several seats in the
     * same group; each seat gets its own member code (SN2612, SN2612-2 …).
     */
    public function up(): void
    {
        Schema::create('chit_group_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chit_group_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->string('member_code', 20);
            $table->timestamps();

            $table->unique(['chit_group_id', 'member_code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chit_group_members');
    }
};
