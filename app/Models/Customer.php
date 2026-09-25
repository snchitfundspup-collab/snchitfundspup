<?php

namespace App\Models;

use Database\Factories\CustomerFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;

/**
 * A customer of both businesses. Customers also sign in to their own pages
 * (the "customer" guard) with their phone number; until a password is set
 * the default password works. Signed in with the default password, or one
 * the office typed in, they must choose their own before anything else.
 */
class Customer extends Authenticatable
{
    /** @use HasFactory<CustomerFactory> */
    use HasFactory;

    /**
     * The password every customer starts with (and gets back on a reset).
     */
    public const DEFAULT_PASSWORD = 'snchitfunds';

    protected $hidden = ['password', 'remember_token'];

    protected $fillable = [
        'customer_code',
        'name',
        'phone',
        'email',
        'address',
        'remarks',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'password' => 'hashed',
            'must_change_password' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    /**
     * The last 10 digits of a phone number ("+91 98765 43210" → "9876543210").
     */
    public static function phoneDigits(?string $phone): string
    {
        return substr((string) preg_replace('/\D/', '', (string) $phone), -10);
    }

    /**
     * Active customers with this phone number whose password matches. More
     * than one when family members share a phone.
     *
     * @return Collection<int, Customer>
     */
    public static function forLogin(string $phone, string $password): Collection
    {
        $digits = self::phoneDigits($phone);

        if (strlen($digits) < 10) {
            return collect();
        }

        return self::query()
            ->where('is_active', true)
            ->where('phone', 'like', '%'.substr($digits, -4).'%')
            ->orderBy('name')
            ->get()
            ->filter(fn (Customer $customer) => self::phoneDigits($customer->phone) === $digits && $customer->passwordMatches($password))
            ->values();
    }

    public function passwordMatches(string $password): bool
    {
        return $this->usesDefaultPassword()
            ? hash_equals(self::DEFAULT_PASSWORD, $password)
            : Hash::check($password, $this->password);
    }

    public function usesDefaultPassword(): bool
    {
        return $this->password === null;
    }

    /**
     * The customer must choose their own password before using their pages:
     * they have the default one, or one the office set for them.
     */
    public function mustChangePassword(): bool
    {
        return $this->usesDefaultPassword() || $this->must_change_password;
    }

    /**
     * Back to the default password (the customer chooses a new one when
     * they next sign in).
     */
    public function resetPassword(): void
    {
        $this->forceFill(['password' => null, 'must_change_password' => false, 'remember_token' => null])->save();
    }

    /**
     * A password the office typed in (e.g. on a phone call): the customer
     * must replace it with their own at the next sign-in.
     */
    public function setPasswordByOffice(string $password): void
    {
        $this->forceFill(['password' => $password, 'must_change_password' => true, 'remember_token' => null])->save();
    }

    /**
     * "default", "office" (set by the office, to be changed) or "own".
     */
    public function passwordState(): string
    {
        return match (true) {
            $this->usesDefaultPassword() => 'default',
            $this->must_change_password => 'office',
            default => 'own',
        };
    }

    /**
     * The customer's own new password.
     */
    public function choosePassword(string $password): void
    {
        $this->forceFill(['password' => $password, 'must_change_password' => false])->save();
    }

    /**
     * Match customer ID, name, phone, identification (remarks) or address.
     *
     * @param  Builder<Customer>  $query
     */
    public function scopeSearch(Builder $query, string $term): void
    {
        $term = trim($term);

        if ($term === '') {
            return;
        }

        $query->where(function (Builder $query) use ($term) {
            foreach (['customer_code', 'name', 'phone', 'remarks', 'address'] as $column) {
                $query->orWhere($column, 'like', "%{$term}%");
            }
        });
    }

    /**
     * Seats this customer holds across chit groups.
     *
     * @return HasMany<ChitGroupMember, $this>
     */
    public function memberships(): HasMany
    {
        return $this->hasMany(ChitGroupMember::class);
    }

    /**
     * SN Traders: rice sold to this customer.
     *
     * @return HasMany<Sale, $this>
     */
    public function traderSales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    /**
     * SN Traders: money received from this customer.
     *
     * @return HasMany<TraderReceipt, $this>
     */
    public function traderReceipts(): HasMany
    {
        return $this->hasMany(TraderReceipt::class);
    }

    /**
     * SN Traders: what the customer still owes (sales minus money received;
     * below zero is an advance).
     */
    public function traderBalance(): float
    {
        return round((float) $this->traderSales()->sum('total_amount') - (float) $this->traderReceipts()->sum('amount'), 2);
    }

    /**
     * SN Traders: rice orders placed from the customer's own pages.
     *
     * @return HasMany<TraderOrder, $this>
     */
    public function traderOrders(): HasMany
    {
        return $this->hasMany(TraderOrder::class);
    }
}
