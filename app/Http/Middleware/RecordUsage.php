<?php

namespace App\Http\Middleware;

use App\Models\UsageLog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Logs each page a signed-in person opens, for the Usage dashboard. Only
 * full page loads count: not form posts, live-filter refreshes, failed
 * pages or file downloads.
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

        if ($this->counts($request, $response)) {
            try {
                UsageLog::record($request, $request->user());
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
            && $request->user() !== null
            && ! $request->ajax()
            && $response->getStatusCode() === 200
            && str_contains((string) $response->headers->get('Content-Type'), 'text/html');
    }
}
