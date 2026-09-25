<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AdminUserSeeder extends Seeder
{
    /**
     * Admin accounts: username => display name.
     *
     * @var array<string, string>
     */
    private const ADMINS = [
        'narayanan' => 'Narayanan',
        'sathiya' => 'Sathiya',
    ];

    /**
     * Create any missing admin accounts with a random password and print it
     * once. Existing accounts are left untouched so re-running is safe.
     */
    public function run(): void
    {
        foreach (self::ADMINS as $username => $name) {
            if (User::where('username', $username)->exists()) {
                $this->command?->line("Admin '{$username}' already exists — password unchanged.");

                continue;
            }

            $password = Str::password(12, symbols: false);

            User::create([
                'name' => $name,
                'username' => $username,
                'password' => $password,
                'can_view_usage' => $username === 'sathiya',
            ]);

            $this->command?->info("Created admin '{$username}' with password: {$password}");
        }
    }
}
