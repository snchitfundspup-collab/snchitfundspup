<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Every page a signed-in person opens (staff now, customers once they
     * can sign in), for the Usage dashboard; and who may see that dashboard
     * (Sathiya).
     */
    public function up(): void
    {
        Schema::create('usage_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('event', 10)->default('view');
            $table->string('business', 10)->nullable();
            $table->string('route_name', 80)->nullable();
            $table->string('path', 255);
            $table->string('device', 10)->nullable();
            $table->string('ip', 45)->nullable();
            $table->date('visited_on');
            $table->timestamp('created_at')->nullable();

            $table->index('visited_on');
            $table->index(['user_id', 'visited_on']);
            $table->index(['customer_id', 'visited_on']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->boolean('can_view_usage')->default(false)->after('is_partner');
        });

        DB::table('users')->where('username', 'sathiya')->update(['can_view_usage' => true]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('can_view_usage');
        });

        Schema::dropIfExists('usage_logs');
    }
};
