<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Customers sign in to their own pages with their phone number. No
     * password saved means the default password (Customer::DEFAULT_PASSWORD).
     * A password the office typed in must be changed at the next sign-in,
     * like the default one.
     */
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('password')->nullable()->after('is_active');
            $table->boolean('must_change_password')->default(false)->after('password');
            $table->rememberToken()->after('must_change_password');
            $table->timestamp('last_login_at')->nullable()->after('remember_token');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['password', 'must_change_password', 'remember_token', 'last_login_at']);
        });
    }
};
