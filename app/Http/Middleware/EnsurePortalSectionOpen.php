<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The customer pages show only the businesses that are open to customers
 * (PORTAL_SECTIONS: chit, traders, finance). SN Chit Funds and SN Traders
 * are paused for now, so their customer pages go back to My Home.
 */
class EnsurePortalSectionOpen
{
    public const CHIT = 'chit';

    public const TRADERS = 'traders';

    public const FINANCE = 'finance';

    public static function isOpen(string $section): bool
    {
        return in_array($section, (array) config('app.portal_sections'), true);
    }

    /**
     * Only Micro Finance open: the customer pages carry its name.
     */
    public static function financeOnly(): bool
    {
        return self::isOpen(self::FINANCE) && ! self::isOpen(self::CHIT) && ! self::isOpen(self::TRADERS);
    }

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string $section): Response
    {
        if (! self::isOpen($section)) {
            return redirect()->route('portal.dashboard');
        }

        return $next($request);
    }
}
