<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Purchase and selling price per bag on each rice variety, and the
     * purchase cost of a bag saved on each sale line (for profit).
     */
    public function up(): void
    {
        Schema::table('trader_varieties', function (Blueprint $table) {
            $table->decimal('purchase_price', 10, 2)->nullable()->after('bag_kg');
            $table->decimal('selling_price', 10, 2)->nullable()->after('purchase_price');
        });

        Schema::table('trader_sale_items', function (Blueprint $table) {
            $table->decimal('cost_rate', 10, 2)->nullable()->after('rate_per');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trader_sale_items', function (Blueprint $table) {
            $table->dropColumn('cost_rate');
        });

        Schema::table('trader_varieties', function (Blueprint $table) {
            $table->dropColumn(['purchase_price', 'selling_price']);
        });
    }
};
