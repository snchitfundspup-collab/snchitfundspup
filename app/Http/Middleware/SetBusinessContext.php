<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * The app runs three businesses: SN Chit Funds, SN Traders (rice) and Sri
 * Lakshmi Micro Finance (loans). Traders pages (traders.*), Micro Finance
 * pages (finance.*) and Chit Funds pages set which one is open; shared
 * pages (customers, change password) keep the last one. Every view gets
 * $business for the header, menu and footer; prints and PDFs find their
 * letterhead with forCompany().
 */
class SetBusinessContext
{
    /**
     * company: the name used on prints and PDFs ($companyName); prefix: the
     * brand before it ("SN" / "Sri Lakshmi").
     *
     * @var array<string, array{key: string, name: string, full_name: string, prefix: string, company: string, home: string, tagline_key: string, tagline: string, logo: string, pdf_logo: string}>
     */
    public const BUSINESSES = [
        'chit' => [
            'key' => 'chit',
            'name' => 'Chit Funds',
            'full_name' => 'SN Chit Funds',
            'prefix' => 'SN',
            'company' => 'Chit Funds',
            'home' => 'dashboard',
            'tagline_key' => 'brand_tagline',
            'tagline' => 'Trust · Growth · Together',
            'logo' => 'images/sn-chit-funds-logo.png',
            'pdf_logo' => 'images/sn-chit-funds-logo-pdf.png',
        ],
        'traders' => [
            'key' => 'traders',
            'name' => 'Traders',
            'full_name' => 'SN Traders',
            'prefix' => 'SN',
            'company' => 'Traders',
            'home' => 'traders.dashboard',
            'tagline_key' => 'traders_tagline',
            'tagline' => 'Quality Rice · Fair Price',
            'logo' => 'images/sn-chit-funds-logo.png',
            'pdf_logo' => 'images/sn-chit-funds-logo-pdf.png',
        ],
        'finance' => [
            'key' => 'finance',
            'name' => 'Micro Finance',
            'full_name' => 'Sri Lakshmi Micro Finance',
            'prefix' => 'Sri Lakshmi',
            'company' => 'Micro Finance',
            'home' => 'finance.dashboard',
            'tagline_key' => 'finance_tagline',
            'tagline' => 'Small Loans · Easy Repayment',
            'logo' => 'images/sri-lakshmi-logo.png',
            'pdf_logo' => 'images/sri-lakshmi-logo.png',
        ],
    ];

    /**
     * Pages all businesses use; they keep the business last opened.
     *
     * @var list<string>
     */
    private const SHARED_ROUTES = ['customers.*', 'password.*', 'usage.*', 'logout'];

    /**
     * The business whose letterhead a print or PDF uses, from its
     * $companyName ('Chit Funds', 'Traders', 'Micro Finance').
     *
     * @return array{key: string, name: string, full_name: string, prefix: string, company: string, home: string, tagline_key: string, tagline: string, logo: string, pdf_logo: string}
     */
    public static function forCompany(?string $companyName): array
    {
        foreach (self::BUSINESSES as $business) {
            if ($business['company'] === $companyName) {
                return $business;
            }
        }

        return self::BUSINESSES['chit'];
    }

    /**
     * Opened on Micro Finance's own domain (FINANCE_DOMAIN), if one is set.
     */
    public static function onFinanceDomain(Request $request): bool
    {
        $domain = (string) config('app.finance_domain');

        return $domain !== '' && in_array(strtolower($request->getHost()), [strtolower($domain), 'www.'.strtolower($domain)], true);
    }

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $fallback = self::onFinanceDomain($request) ? 'finance' : 'chit';

        $key = match (true) {
            $request->routeIs('traders.*') => 'traders',
            $request->routeIs('finance.*') => 'finance',
            $request->routeIs(...self::SHARED_ROUTES) => $request->hasSession() ? $request->session()->get('business', $fallback) : $fallback,
            default => 'chit',
        };

        if ($request->hasSession() && $request->user()) {
            $request->session()->put('business', $key);
        }

        View::share('business', self::BUSINESSES[$key] ?? self::BUSINESSES['chit']);

        return $next($request);
    }
}
