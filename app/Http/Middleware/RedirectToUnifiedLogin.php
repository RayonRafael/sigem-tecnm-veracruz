<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class RedirectToUnifiedLogin
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $path = $request->path();

        if ($path === 'admin/login' || $path === 'servicio-social/login') {
            if (Auth::check()) {
                $user = Auth::user();
                if ($user->hasRole('Administrador')) {
                    return redirect('/admin');
                }
                if ($user->hasRole('Servicio Social')) {
                    return redirect('/servicio-social');
                }
            }
            return redirect('/login');
        }

        return $next($request);
    }
}
