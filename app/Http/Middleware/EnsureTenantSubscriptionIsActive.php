<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantSubscriptionIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        if ($user->isAdmin()) {
            return $next($request);
        }

        $tenant = $user->tenant;

        if (!$tenant) {
            abort(403, 'No tienes una empresa asociada.');
        }

        if (!$tenant->isSubscriptionActive()) {
            return response()->view('errors.subscription-expired', [
                'tenant' => $tenant,
            ], 403);
        }

        return $next($request);
    }
}