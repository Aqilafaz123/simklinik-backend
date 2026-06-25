<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        if ($user->roleKode() === 'admin') {
            return $next($request);
        }

        if (! in_array($user->roleKode(), $roles, true)) {
            abort(403, 'Anda tidak memiliki akses ke halaman tersebut.');
        }

        return $next($request);
    }
}
