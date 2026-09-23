<?php

namespace Database\Seeders;

use App\Models\Customer;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;

/**
 * Adds sample customers for testing (not called from DatabaseSeeder,
 * so it never runs on a live server by accident).
 *
 * Uses plain PHP randomness instead of Faker, because Faker is a dev
 * dependency and is not installed on Laravel Cloud.
 *
 * Codes continue from the last customer (SN2611 → SN2612 …) so the
 * normal "last code + 1" numbering keeps working afterwards.
 *
 * Run: php artisan db:seed --class=SampleCustomerSeeder
 */
class SampleCustomerSeeder extends Seeder
{
    private const COUNT = 100;

    /**
     * Every 10th sample customer is inactive.
     */
    private const INACTIVE_EVERY = 10;

    /**
     * @var list<string>
     */
    private const FIRST_NAMES = [
        'Arun', 'Bala', 'Chitra', 'Deepa', 'Ganesh', 'Gowri', 'Hari', 'Indira',
        'Jaya', 'Karthik', 'Kavitha', 'Lakshmi', 'Mani', 'Meena', 'Murugan',
        'Nandhini', 'Prabhu', 'Priya', 'Rajesh', 'Revathi', 'Saravanan',
        'Selvi', 'Senthil', 'Sudha', 'Suresh', 'Tamil', 'Uma', 'Vasanth',
        'Vijaya', 'Yamuna',
    ];

    /**
     * @var list<string>
     */
    private const LAST_NAMES = [
        'Kumar', 'Raj', 'Selvam', 'Murugesan', 'Palanisamy', 'Subramani',
        'Krishnan', 'Ramasamy', 'Velu', 'Shanmugam', 'Rajendran', 'Natarajan',
    ];

    /**
     * @var list<string>
     */
    private const TOWNS = [
        'Pudhupalayam', 'Erode', 'Gobichettipalayam', 'Perundurai', 'Tiruppur',
        'Bhavani', 'Sathyamangalam', 'Chennimalai', 'Kangeyam', 'Anthiyur',
    ];

    /**
     * @var list<string>
     */
    private const REMARKS = [
        'Near temple', 'Textile shop owner', 'Referred by Kumar', 'Farmer',
        'Govt. employee', 'Teacher', 'Auto driver', 'Tailor', 'Grocery shop',
        'Bank employee',
    ];

    public function run(): void
    {
        $lastCode = Customer::orderByDesc('id')->value('customer_code');

        $nextNumber = $lastCode ? ((int) substr($lastCode, 2)) + 1 : 2601;

        foreach (range(1, self::COUNT) as $index) {
            $name = Arr::random(self::FIRST_NAMES).' '.Arr::random(self::LAST_NAMES);

            Customer::create([
                'customer_code' => 'SN'.$nextNumber++,
                'name' => $name,
                'phone' => Arr::random(['6', '7', '8', '9']).str_pad((string) random_int(0, 999999999), 9, '0', STR_PAD_LEFT),
                'email' => random_int(1, 100) <= 40
                    ? strtolower(str_replace(' ', '.', $name)).random_int(1, 99).'@gmail.com'
                    : null,
                'address' => random_int(1, 250).', '.Arr::random(['Main Road', 'Temple Street', 'Gandhi Nagar', 'Bazaar Street', 'Anna Nagar']).', '.Arr::random(self::TOWNS),
                'remarks' => random_int(1, 100) <= 70 ? Arr::random(self::REMARKS) : null,
                'is_active' => $index % self::INACTIVE_EVERY !== 0,
            ]);
        }

        $this->command?->info('Added '.self::COUNT.' sample customers (SN'.($nextNumber - self::COUNT).' – SN'.($nextNumber - 1).').');
    }
}
