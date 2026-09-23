<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The monthly installment is typed in by the admin (whole rupees)
     * instead of always being chit amount ÷ months. Existing groups keep
     * their old value.
     */
    public function up(): void
    {
        Schema::table('chit_groups', function (Blueprint $table) {
            $table->unsignedBigInteger('installment_amount')->default(0)->after('months');
        });

        DB::table('chit_groups')
            ->where('months', '>', 0)
            ->update(['installment_amount' => DB::raw('ROUND(amount / months)')]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('chit_groups', function (Blueprint $table) {
            $table->dropColumn('installment_amount');
        });
    }
};
