<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Agent statements: the staff member who brought the customer in (loan
 * agent_id, chit seat agent_id) and who collected the money (collection /
 * payment collected_by). Existing loans and receipts take whoever recorded
 * them; existing chit seats are left unset.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('finance_loans', function (Blueprint $table) {
            $table->foreignId('agent_id')->nullable()->after('recorded_by')->constrained('users')->nullOnDelete();
        });

        Schema::table('finance_collections', function (Blueprint $table) {
            $table->foreignId('collected_by')->nullable()->after('recorded_by')->constrained('users')->nullOnDelete();
        });

        Schema::table('chit_group_members', function (Blueprint $table) {
            $table->foreignId('agent_id')->nullable()->after('position')->constrained('users')->nullOnDelete();
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->foreignId('collected_by')->nullable()->after('recorded_by')->constrained('users')->nullOnDelete();
        });

        DB::table('finance_loans')->update(['agent_id' => DB::raw('recorded_by')]);
        DB::table('finance_collections')->update(['collected_by' => DB::raw('recorded_by')]);
        DB::table('payments')->update(['collected_by' => DB::raw('recorded_by')]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('collected_by');
        });

        Schema::table('chit_group_members', function (Blueprint $table) {
            $table->dropConstrainedForeignId('agent_id');
        });

        Schema::table('finance_collections', function (Blueprint $table) {
            $table->dropConstrainedForeignId('collected_by');
        });

        Schema::table('finance_loans', function (Blueprint $table) {
            $table->dropConstrainedForeignId('agent_id');
        });
    }
};
