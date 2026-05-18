<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminWeb
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Jika belum login, arahkan ke halaman login admin
        if (!Auth::check()) {
            return redirect()->route('admin.login');
        }

        // Jika sudah login tapi bukan admin, logout dan kembalikan dengan pesan error
        if (!Auth::user()->isAdmin()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('admin.login')->withErrors([
                'email' => 'Anda tidak memiliki hak akses sebagai administrator.',
            ]);
        }

        return $next($request);
    }
}
