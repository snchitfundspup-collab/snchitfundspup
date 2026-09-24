<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * The app runs two businesses: SN Chit Funds and SN Traders (rice). Traders
 * pages (traders.*) and Chit Funds pages set which one is open; shared pages
 * (customers, change password) keep the last one. Every view gets
 * $business for the header, menu and footer.
 */
class SetBusinessContext
{
    /**
     * @var array<string, array{key: string, name: string, full_name: string, home: string, tagline_key: string}>
     */
    public const BUSINESSES = [
        'chit' => [
            'key' => 'chit',
            'name' => 'Chit Funds',
            'full_name' => 'SN Chit Funds',
            'home' => 'dashboard',
            'tagline_key' => 'brand_tagline',
        ],
        'traders' => [
            'key' => 'traders',
            'name' => 'Traders',
            'full_name' => 'SN Traders',
            'home' => 'traders.dashboard',
            'tagline_key' => 'traders_tagline',
        ],
    ];

    /**
     * Pages both businesses use; they keep the business last opened.
     *
     * @var list<string>
     */
    private const SHARED_ROUTES = ['customers.*', 'password.*', 'usage.*', 'logout'];

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $key = match (true) {
            $request->routeIs('traders.*') => 'traders',
            $request->routeIs(...self::SHARED_ROUTES) => $request->hasSession() ? $request->session()->get('business', 'chit') : 'chit',
            default => 'chit',
        };

        if ($request->hasSession() && $request->user()) {
            $request->session()->put('business', $key);
        }

        View::share('business', self::BUSINESSES[$key] ?? self::BUSINESSES['chit']);

        return $next($request);
    }
}
