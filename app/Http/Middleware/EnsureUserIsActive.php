<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next, ?string $ability = null): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        if (in_array($user->role, ['super_admin', 'admin'])) {
            return redirect('/admin');
        }

        if ($user->status === 'banned') {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'email' => 'Your account has been banned.',
            ]);
        }

        if ($user->status === 'pending' && ! $request->routeIs('verification.pending')) {
            return redirect()->route('verification.pending');
        }

        if ($ability === 'create' && $user->status === 'restricted') {
            return redirect()->back()->with(
                'error',
                'Your account is restricted from creating surveys or redeeming points.',
            );
        }

        return $next($request);
    }
}
