<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The owner (Sathiya) sees all of Micro Finance; every other staff member
 * is an agent and sees only their own loans, collections and cash.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'is_owner')) {
            Schema::table('users', function (Blueprint $table) {
                $table->boolean('is_owner')->default(false);
            });
        }

        DB::table('users')->where('username', 'sathiya')->update(['is_owner' => true]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('users', 'is_owner')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('is_owner');
            });
        }
    }
};
