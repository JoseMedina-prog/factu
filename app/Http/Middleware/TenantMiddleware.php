<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class TenantMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();

        if (!$user->tenant_id) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'No tienes una empresa asociada'], 403);
            }
            abort(403, 'No tienes una empresa asociada.');
        }

        if (!$user->tenant->is_active) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Tu empresa está inactiva'], 403);
            }
            abort(403, 'Tu empresa está inactiva.');
        }

        app()->instance('current_tenant_id', $user->tenant_id);

        return $next($request);
    }
}

