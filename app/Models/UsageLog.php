<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * One page opened (or a sign-in) by a staff member, or by a customer once
 * customers can sign in. The detail (page, device, IP) is kept for a month;
 * each log also adds to that person's UsageDaily count, kept for a year.
 */
class UsageLog extends Model
{
    use MassPrunable;

    public const UPDATED_AT = null;

    private const KEEP_DAYS = 31;

    /**
     * Friendly names for the pages people open most.
     *
     * @var array<string, string>
     */
    private const PAGE_NAMES = [
        'dashboard' => 'Chit Funds dashboard',
        'customers.index' => 'Customers',
        'customers.create' => 'Add customer',
        'customers.edit' => 'Edit customer',
        'groups.index' => 'Groups',
        'groups.show' => 'Group details',
        'groups.create' => 'Create group',
        'payments.create' => 'Collect payment',
        'payments.index' => 'All payments',
        'payments.show' => 'Payment receipt',
        'payments.ledger' => 'Payment ledger',
        'draws.index' => 'Draws',
        'reports.customer' => 'Customer statement',
        'reports.dues' => 'Pending & due report',
        'expenses.index' => 'Expenses',
        'expenses.create' => 'Add expense',
        'expenses.balance' => 'Balance sheet',
        'password.edit' => 'Change password',
        'usage.index' => 'Usage',
        'traders.dashboard' => 'Traders dashboard',
        'traders.sales.create' => 'New sale',
        'traders.sales.index' => 'All sales',
        'traders.sales.show' => 'Sale invoice',
        'traders.purchases.create' => 'New purchase',
        'traders.purchases.index' => 'All purchases',
        'traders.receipts.create' => 'Receive payment',
        'traders.balances.index' => 'Customer balances',
        'traders.accounts.show' => 'Customer account',
        'traders.stock' => 'Stock',
        'traders.varieties.index' => 'Rice varieties',
        'traders.reports.show' => 'Report',
        'traders.reports.customer' => 'Customer statement',
        'traders.expenses.index' => 'Traders expenses',
        'traders.expenses.balance' => 'Traders balance sheet',
    ];

    protected $fillable = ['user_id', 'customer_id', 'event', 'business', 'route_name', 'path', 'device', 'ip', 'visited_on'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'visited_on' => 'date',
            'created_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::created(fn (UsageLog $log) => UsageDaily::countLog($log));
    }

    /**
     * Log a page view (or 'login') for a staff member or a customer.
     */
    public static function record(Request $request, ?User $user = null, ?Customer $customer = null, string $event = 'view'): self
    {
        return self::create([
            'user_id' => $user?->id,
            'customer_id' => $customer?->id,
            'event' => $event,
            'business' => $request->hasSession() ? $request->session()->get('business') : null,
            'route_name' => $request->route()?->getName(),
            'path' => Str::limit('/'.ltrim($request->path(), '/'), 250, ''),
            'device' => self::deviceFrom((string) $request->userAgent()),
            'ip' => $request->ip(),
            'visited_on' => today(config('app.business_timezone'))->toDateString(),
        ]);
    }

    /**
     * Phone / tablet / computer from the browser's user agent.
     */
    public static function deviceFrom(string $userAgent): string
    {
        return match (true) {
            (bool) preg_match('/iPad|Tablet|Android(?!.*Mobile)/i', $userAgent) => 'tablet',
            (bool) preg_match('/Mobile|iPhone|Android/i', $userAgent) => 'phone',
            default => 'computer',
        };
    }

    /**
     * "Collect payment", "Report: profit", or a tidy version of the route.
     */
    public function pageLabel(): string
    {
        if ($this->event === 'login') {
            return 'Signed in';
        }

        $label = self::PAGE_NAMES[$this->route_name] ?? Str::headline(str_replace('.', ' ', (string) ($this->route_name ?? $this->path)));

        if ($this->route_name === 'traders.reports.show') {
            $label .= ': '.Str::headline((string) Str::afterLast($this->path, '/'));
        }

        return $label;
    }

    /**
     * Who: the staff member's or customer's name.
     */
    public function personName(): string
    {
        return $this->user?->name ?? $this->customer?->name ?? 'Unknown';
    }

    /**
     * Logs older than the keep period are removed by model:prune.
     *
     * @return Builder<UsageLog>
     */
    public function prunable(): Builder
    {
        return static::where('created_at', '<', now()->subDays(self::KEEP_DAYS));
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
