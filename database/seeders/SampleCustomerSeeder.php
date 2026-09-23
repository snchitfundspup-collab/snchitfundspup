<?php

namespace Database\Seeders;

use App\Models\Customer;
use Illuminate\Database\Seeder;

/**
 * Adds sample customers for local testing (not called from
 * DatabaseSeeder, so it never runs on a live server by accident).
 *
 * Codes continue from the last customer (SN2611 → SN2612 …) so the
 * normal "last code + 1" numbering keeps working afterwards.
 *
 * Run: php artisan db:seed --class=SampleCustomerSeeder
 */
class SampleCustomerSeeder extends Seeder
{
    private const COUNT = 50;

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
            $name = fake()->randomElement(self::FIRST_NAMES).' '.fake()->randomElement(self::LAST_NAMES);

            Customer::create([
                'customer_code' => 'SN'.$nextNumber++,
                'name' => $name,
                'phone' => fake()->randomElement(['6', '7', '8', '9']).fake()->numerify('#########'),
                'email' => fake()->boolean(40)
                    ? strtolower(str_replace(' ', '.', $name)).fake()->numberBetween(1, 99).'@gmail.com'
                    : null,
                'address' => fake()->numberBetween(1, 250).', '.fake()->randomElement(['Main Road', 'Temple Street', 'Gandhi Nagar', 'Bazaar Street', 'Anna Nagar']).', '.fake()->randomElement(self::TOWNS),
                'remarks' => fake()->boolean(70) ? fake()->randomElement(self::REMARKS) : null,
                'is_active' => $index % self::INACTIVE_EVERY !== 0,
            ]);
        }

        $this->command?->info('Added '.self::COUNT.' sample customers (SN'.($nextNumber - self::COUNT).' – SN'.($nextNumber - 1).').');
    }
}
