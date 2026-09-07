<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureAccountIsActive
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            /** @var \App\Models\User $user */
            $user = Auth::user();
            $routeName = $request->route() ? $request->route()->getName() : '';
            $allowedRoutes = ['activation.notice', 'activation.request', 'logout', 'lang.switch'];

            if (!$user->is_active) {
                // Inactive user must only access activation notice/request or logout
                if (!in_array($routeName, $allowedRoutes)) {
                    return redirect()->route('activation.notice');
                }
            } else {
                // Active user should not see activation page
                if (in_array($routeName, ['activation.notice', 'activation.request'])) {
                    return $user->isStaff()
                        ? redirect()->route('staff.attendance')
                        : redirect()->route('dashboard');
                }
            }
        }

        return $next($request);
    }
}
