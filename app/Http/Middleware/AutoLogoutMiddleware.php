<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AutoLogoutMiddleware
{
    // 10 menit dalam detik
    const TIMEOUT = 600;

    public function handle(Request $request, Closure $next)
    {
        if (Auth::check()) {
            $lastActivity = session('last_activity');

            if ($lastActivity && (now()->timestamp - $lastActivity) > self::TIMEOUT) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('login')->with('warning',
                    'Sesi Anda telah berakhir karena tidak aktif selama 10 menit.'
                );
            }

            // Update last activity setiap request
            session(['last_activity' => now()->timestamp]);
        }

        return $next($request);
    }
}