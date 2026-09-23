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
}
