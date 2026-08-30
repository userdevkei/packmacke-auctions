<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DomainRoleRedirect
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();
        $host = $request->getHost(); // e.g. stocks.packmack.net
        if ($user) {
            if (env('APP_ENV') === 'local') {
                if ($user->role_id == 10 && $host === 'localhost') {
                    return redirect()->away('http://127.0.0.1/packmacke'); // adjust port if needed
                }

                if ($user->role_id != 10 && $host === '127.0.0.1') {
                    return redirect()->away('http://localhost/packmacke');
                }
            } else {
                if ($user->role_id == 10 && $host === 'stocks.packmac.net') {
                    return redirect()->away('https://clients.packmac.net');
                }
                if ($user->role_id != 10 && $host === 'clients.packmac.net') {
                    return redirect()->away('https://stocks.packmac.net');
                }
            }

        }
        return $next($request);
    }
}
