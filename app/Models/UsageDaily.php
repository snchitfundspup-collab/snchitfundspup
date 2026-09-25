<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

/**
 * Pages opened by one person on one day. Kept for 400 days for the daily
 * and monthly usage charts, while the detailed UsageLog is kept for a month.
 */
class UsageDaily extends Model
{
    use MassPrunable;

    public $timestamps = false;

    protected $table = 'usage_daily';

    private const KEEP_DAYS = 400;

    protected $fillable = ['visited_on', 'person', 'user_id', 'customer_id', 'views'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'visited_on' => 'date',
            'views' => 'integer',
        ];
    }

    /**
     * "u12" for a staff member, "c34" for a customer.
     */
    public static function personKey(?int $userId, ?int $customerId): string
    {
        return $userId ? 'u'.$userId : 'c'.$customerId;
    }

    /**
     * Count one more page for the person on the log's day.
     */
    public static function countLog(UsageLog $log): void
    {
        self::query()->upsert([[
            'visited_on' => $log->visited_on->toDateString(),
            'person' => self::personKey($log->user_id, $log->customer_id),
            'user_id' => $log->user_id,
            'customer_id' => $log->customer_id,
            'views' => 1,
        ]], ['visited_on', 'person'], ['views' => DB::raw('views + 1')]);
    }

    /**
     * @return Builder<UsageDaily>
     */
    public function prunable(): Builder
    {
        return static::where('visited_on', '<', today(config('app.business_timezone'))->subDays(self::KEEP_DAYS)->toDateString());
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
