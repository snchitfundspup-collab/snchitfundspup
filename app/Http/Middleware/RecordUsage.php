<?php

namespace App\Http\Middleware;

use App\Models\UsageLog;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Logs each page a signed-in person opens, for the Usage dashboard: staff
 * on the office pages, customers on their own pages (/my). Only full page
 * loads count: not form posts, live-filter refreshes, failed pages or file
 * downloads.
 */
class RecordUsage
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $this->counts($request, $response)) {
            return $response;
        }

        $customer = $request->is('my', 'my/*') ? Auth::guard('customer')->user() : null;
        $user = $customer ? null : $request->user();

        if ($customer || $user) {
            try {
                UsageLog::record($request, $user, $customer);
            } catch (Throwable $exception) {
                /* usage logging must never break the page */
                report($exception);
            }
        }

        return $response;
    }

    private function counts(Request $request, Response $response): bool
    {
        return $request->isMethod('GET')
            && ! $request->ajax()
            && $response->getStatusCode() === 200
            && str_contains((string) $response->headers->get('Content-Type'), 'text/html');
    }
}
