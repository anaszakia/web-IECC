<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class AuthMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $userId = session('user_id');

        if (!$userId) {
            return redirect()->route('login');
        }

        // Pastikan user masih ada di database (bukan deleted user / ghost session)
        if (!Auth::check() || Auth::id() != $userId) {
            $user = User::select('id', 'name')->find($userId);
            if (!$user) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                return redirect()->route('login')->withErrors(['email' => 'Sesi tidak valid atau akun telah dihapus.']);
            }
        }

        return $next($request);
    }
}