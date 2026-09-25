<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pages opened per person per day. The detailed usage log is kept for a
     * month only; these small daily counts keep the daily and monthly
     * charts for a year. Filled from the existing log.
     */
    public function up(): void
    {
        Schema::create('usage_daily', function (Blueprint $table) {
            $table->id();
            $table->date('visited_on');
            $table->string('person', 20);
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('views')->default(0);

            $table->unique(['visited_on', 'person']);
            $table->index('person');
        });

        DB::table('usage_logs')
            ->selectRaw('visited_on, user_id, customer_id, COUNT(*) as views')
            ->groupBy('visited_on', 'user_id', 'customer_id')
            ->orderBy('visited_on')
            ->get()
            ->each(fn ($row) => DB::table('usage_daily')->insert([
                'visited_on' => substr((string) $row->visited_on, 0, 10),
                'person' => $row->user_id ? 'u'.$row->user_id : 'c'.$row->customer_id,
                'user_id' => $row->user_id,
                'customer_id' => $row->customer_id,
                'views' => $row->views,
            ]));
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('usage_daily');
    }
};
