<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Serial order of members within a group (1, 2, 3 …), set by dragging
     * cards on the Edit Members page. Existing members keep the order they
     * were added in.
     */
    public function up(): void
    {
        Schema::table('chit_group_members', function (Blueprint $table) {
            $table->unsignedInteger('position')->default(0)->after('member_code');
        });

        DB::table('chit_group_members')
            ->orderBy('chit_group_id')
            ->orderBy('id')
            ->get(['id', 'chit_group_id'])
            ->groupBy('chit_group_id')
            ->each(function ($members) {
                foreach ($members->values() as $index => $member) {
                    DB::table('chit_group_members')
                        ->where('id', $member->id)
                        ->update(['position' => $index + 1]);
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('chit_group_members', function (Blueprint $table) {
            $table->dropColumn('position');
        });
    }
};
