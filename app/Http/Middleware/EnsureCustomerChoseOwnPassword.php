<?php

namespace App\Http\Middleware;

use App\Models\Customer;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * A customer signed in with the default password, or one the office set,
 * sees nothing but the Change Password page until they choose their own.
 */
class EnsureCustomerChoseOwnPassword
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $customer = $request->user('customer');

        if ($customer instanceof Customer && $customer->mustChangePassword()) {
            return redirect()->route('portal.password.edit');
        }

        return $next($request);
    }
}
