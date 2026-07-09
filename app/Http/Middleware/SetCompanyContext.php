<?php

namespace App\Http\Middleware;

use App\Support\Tenant\CompanyContext;
use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetCompanyContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $isPlatformRoute = $request->routeIs('super-admin.*');

        if ($user && $user->company_id && ! $isPlatformRoute) {
            if ($user->company?->accessIsBlocked()) {
                if ($user->isSuperAdmin()) {
                    return redirect()->route('super-admin.dashboard')
                        ->with('error', 'Your organization account is suspended.');
                }

                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                return redirect()->route('login')
                    ->withErrors(['email' => 'This organization account is suspended. Please contact support at '.config('support.email').' or '.config('support.phone').'.']);
            }

            CompanyContext::setId($user->company_id);
        }

        try {
            return $next($request);
        } finally {
            CompanyContext::clear();
        }
    }
}
