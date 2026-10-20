<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Staff who may sign in. Narayanan is removed (Oct 2026): he can no longer
 * sign in and is signed out, but his receipts and entries keep his name
 * and signature.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        /* safe to run again: an earlier attempt may have added the column already */
        if (! Schema::hasColumn('users', 'is_active')) {
            Schema::table('users', function (Blueprint $table) {
                $table->boolean('is_active')->default(true);
            });
        }

        $narayanan = DB::table('users')->where('username', 'narayanan')->value('id');

        if ($narayanan) {
            DB::table('users')->where('id', $narayanan)->update(['is_active' => false, 'remember_token' => null]);

            if (Schema::hasTable('sessions')) {
                DB::table('sessions')->where('user_id', $narayanan)->delete();
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('users', 'is_active')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('is_active');
            });
        }
    }
};
