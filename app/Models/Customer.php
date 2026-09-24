<?php

namespace App\Models;

use Database\Factories\CustomerFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    /** @use HasFactory<CustomerFactory> */
    use HasFactory;

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
}
