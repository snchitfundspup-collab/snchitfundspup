<?php

namespace App\Models;

use Database\Factories\FinanceHolidayFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A holiday for one agent's customers (Sri Lakshmi Micro Finance): from
 * starts_on to ends_on nothing is collected; instalments falling on those
 * dates move one step later, and every later instalment with them
 * (FinanceLoan::dueDates()).
 */
class FinanceHoliday extends Model
{
    /** @use HasFactory<FinanceHolidayFactory> */
    use HasFactory;

    protected $fillable = ['starts_on', 'ends_on', 'agent_id', 'notes', 'recorded_by'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'ends_on' => 'date',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function agent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
