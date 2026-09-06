<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Guarantees a signed-in user still belongs to a workspace, and shares the
 * tenant + connected number with every view.
 */
class EnsureTenantContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (! $user?->tenant) {
            Auth::logout();

            return redirect()->route('login')->withErrors(['email' => 'This account is not linked to a workspace.']);
        }

        view()->share('tenant', $user->tenant);
        view()->share('whatsappAccount', $user->tenant->whatsappAccount);

        return $next($request);
    }
}
