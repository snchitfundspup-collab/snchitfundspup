<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

/**
 * The language the person picked (English / தமிழ்) is kept in the
 * "sn_language" cookie by app.js, so printed reports and PDFs made on the
 * server come out in the same language as the screen.
 */
class SetLanguage
{
    public const COOKIE = 'sn_language';

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        App::setLocale($request->cookie(self::COOKIE) === 'ta' ? 'ta' : 'en');

        return $next($request);
    }
}
