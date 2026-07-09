<?php

namespace App\Http\Middleware;

use App\Support\Tenant\CompanyContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetCompanyContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->company_id) {
            CompanyContext::setId($user->company_id);
        }

        try {
            return $next($request);
        } finally {
            CompanyContext::clear();
        }
    }
}
