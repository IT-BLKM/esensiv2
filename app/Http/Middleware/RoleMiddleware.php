<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        $user = $request->user('web');

        if ($user === null) {
            return redirect()->to(Route::has('login') ? route('login') : '/login');
        }

        if ($user->role !== $role) {
            return redirect()->to($this->dashboardPathFor($user->role))
                ->with('warning', "Halaman ini hanya untuk akun {$role}.");
        }

        return $next($request);
    }

    private function dashboardPathFor(string $role): string
    {
        return $role === 'admin' ? '/admin/dashboard' : '/dashboard';
    }
}
