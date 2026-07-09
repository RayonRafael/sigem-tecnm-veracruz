<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    /**
     * Show the login form or redirect to the correct panel if already authenticated.
     */
    public function showLoginForm()
    {
        if (Auth::check()) {
            return $this->redirectBasedOnRole(Auth::user());
        }

        return view('auth.login');
    }

    /**
     * Handle the login request.
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
            'tipo_acceso' => ['required', 'in:alumno,personal'],
        ]);

        $credentials = $request->only('email', 'password');

        if (Auth::guard('web')->attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();
            
            $user = Auth::guard('web')->user();
            $tipo_acceso = $request->input('tipo_acceso');
            
            if ($tipo_acceso === 'alumno') {
                if ($user->hasRole('Administrador') || $user->tipo_usuario === 'Administrador') {
                    Auth::guard('web')->logout();
                    $request->session()->invalidate();
                    $request->session()->regenerateToken();
                    return back()->withErrors([
                        'email' => 'Esta cuenta es de administración. Usa la pestaña "Personal".',
                    ])->onlyInput('email');
                }
                return redirect()->intended('/servicio-social');
            }

            if ($tipo_acceso === 'personal') {
                if ($user->hasRole('Servicio Social') || $user->tipo_usuario === 'Servicio') {
                    Auth::guard('web')->logout();
                    $request->session()->invalidate();
                    $request->session()->regenerateToken();
                    return back()->withErrors([
                        'email' => 'Esta cuenta es de alumno. Usa la pestaña "Alumnos".',
                    ])->onlyInput('email');
                }
                return redirect()->intended('/admin');
            }
            
            return $this->redirectBasedOnRole($user);
        }

        return back()->withErrors([
            'email' => 'Las credenciales no coinciden con nuestros registros.',
        ])->onlyInput('email');
    }

    /**
     * Log the user out of the application.
     */
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    /**
     * Redirect the user to the correct panel based on their role.
     */
    protected function redirectBasedOnRole($user)
    {
        if ($user->hasRole('Administrador')) {
            return redirect('/admin');
        }

        if ($user->hasRole('Servicio Social')) {
            return redirect('/servicio-social');
        }

        // If no valid role, log them out and redirect with error
        Auth::logout();
        return redirect('/login')->withErrors([
            'email' => 'No tienes acceso al sistema. Contacta al administrador.',
        ]);
    }
}
