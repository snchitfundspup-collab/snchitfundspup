<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Who may edit a Micro Finance loan after it is saved (Sathiya only).
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        /* safe to run again: an earlier attempt may have added the column already */
        if (! Schema::hasColumn('users', 'can_edit_loans')) {
            Schema::table('users', function (Blueprint $table) {
                $table->boolean('can_edit_loans')->default(false)->after('can_view_usage');
            });
        }

        DB::table('users')->where('username', 'sathiya')->update(['can_edit_loans' => true]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('can_edit_loans');
        });
    }
};
