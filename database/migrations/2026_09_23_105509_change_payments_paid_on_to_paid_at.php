<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Receipts carry a date AND time (office local time, editable by the
     * admin). Existing payments keep their date at 00:00.
     */
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dateTime('paid_at')->nullable()->after('amount');
        });

        DB::table('payments')->update(['paid_at' => DB::raw('paid_on')]);

        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex(['paid_on']);
            $table->dropColumn('paid_on');
            $table->index('paid_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->date('paid_on')->nullable()->after('amount');
        });

        DB::table('payments')->update(['paid_on' => DB::raw('DATE(paid_at)')]);

        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex(['paid_at']);
            $table->dropColumn('paid_at');
            $table->index('paid_on');
        });
    }
};
