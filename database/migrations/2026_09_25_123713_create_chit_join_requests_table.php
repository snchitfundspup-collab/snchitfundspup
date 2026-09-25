<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A customer's wish to join a chit group that is forming. The office
     * adds them to the group (approved) or dismisses the request.
     */
    public function up(): void
    {
        Schema::create('chit_join_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chit_group_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('seats')->default(1);
            $table->string('note', 500)->nullable();
            $table->string('status', 12)->default('pending');
            $table->string('reply', 500)->nullable();
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index(['customer_id', 'chit_group_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chit_join_requests');
    }
};
