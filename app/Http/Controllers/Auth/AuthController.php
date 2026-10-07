<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Auth\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Laravel\Socialite\Facades\Socialite;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) return redirect()->route('dashboard');
        return view('auth.login');
    }

    public function showRegister()
    {
        if (Auth::check()) return redirect()->route('dashboard');
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:100',
            'email'    => 'required|email|max:150|unique:users,email',
            'password' => [
                'required',
                'string',
                'confirmed',
                Password::min(8)
                    ->letters()
                    ->mixedCase()
                    ->numbers()
                    ->symbols()
                    ->uncompromised(),
                function ($attribute, $value, $fail) use ($request) {
                    $email = strtolower((string) $request->input('email'));
                    $name = strtolower((string) $request->input('name'));
                    $passwordLower = strtolower((string) $value);

                    // Username dari bagian sebelum @ pada email
                    $emailUser = explode('@', $email)[0] ?? '';

                    if (!empty($name) && str_contains($passwordLower, $name)) {
                        $fail('Password tidak boleh mengandung nama Anda.');
                    }

                    if (!empty($email) && str_contains($passwordLower, $email)) {
                        $fail('Password tidak boleh sama atau mengandung alamat email.');
                    }

                    if (!empty($emailUser) && strlen($emailUser) >= 3 && str_contains($passwordLower, $emailUser)) {
                        $fail('Password tidak boleh mengandung username email Anda.');
                    }
                },
            ],
        ], [
            'name.required'      => 'Nama lengkap wajib diisi.',
            'email.required'     => 'Alamat email wajib diisi.',
            'email.email'        => 'Format email tidak valid.',
            'email.unique'       => 'Email ini sudah terdaftar.',
            'password.required'  => 'Password wajib diisi.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
        ]);

        $defaultRole = Role::where('slug', 'citizen')->first() ?? Role::where('slug', 'user')->first();

        $user = User::create([
            'name'      => $request->name,
            'email'     => $request->email,
            'password'  => Hash::make($request->password),
            'role_id'   => $defaultRole?->id,
            'user_type' => 'CITIZEN',
        ]);

        if ($defaultRole) {
            $user->roles()->syncWithoutDetaching([$defaultRole->id]);
        }

        Auth::login($user);
        $request->session()->regenerate();

        session([
            'user_id'       => $user->id,
            'user_name'     => $user->name,
            'user_role'     => $user->role?->slug,
            'last_activity' => now()->timestamp,
        ]);

        return redirect()->route('dashboard')->with('success', 'Pendaftaran berhasil! Selamat datang di IECC.');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        // Key unik berdasarkan lowercase email dan IP address
        $throttleKey = Str::transliterate(Str::lower($request->input('email')) . '|' . $request->ip());

        // Cek jika percobaan gagal sudah mencapai batas 5 kali
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            $minutes = ceil($seconds / 60);

            return back()->withErrors([
                'email' => "Terlalu banyak percobaan login yang gagal. Akun/IP ini dikunci sementara, silakan coba lagi dalam {$minutes} menit.",
            ])->withInput();
        }

        if (Auth::attempt(['email' => $request->email, 'password' => $request->password], $request->boolean('remember'))) {
            // Bersihkan hitungan throttle jika login berhasil
            RateLimiter::clear($throttleKey);

            $request->session()->regenerate();

            // Reload user dengan relasi passkeys untuk memastikan data terbaru
            $user = Auth::user()->loadMissing('passkeys');

            session([
                'user_id'        => Auth::id(),
                'user_name'      => $user->name,
                'user_role'      => $user->role?->slug,
                'last_activity'  => now()->timestamp,
            ]);

            // Selalu tunjukkan prompt passkey jika user belum punya passkey
            if ($user->passkeys()->doesntExist()) {
                return redirect()->route('dashboard')->with('show_passkey_prompt', true);
            }

            return redirect()->route('dashboard');
        }

        // Catat kegagalan login dengan durasi lockout 15 menit (900 detik)
        RateLimiter::hit($throttleKey, 15 * 60);

        $remainingAttempts = RateLimiter::remaining($throttleKey, 5);

        $errorMessage = $remainingAttempts > 0
            ? "Email atau password salah. Sisa percobaan: {$remainingAttempts} kali sebelum akun dikunci 15 menit."
            : "Email atau password salah. Akun/IP ini telah dikunci selama 15 menit.";

        return back()->withErrors(['email' => $errorMessage])->withInput();
    }

    public function redirectToGoogle(Request $request)
    {
        // Save intent so callback knows whether to auto-register or only login
        $intent = $request->query('intent', 'login');
        session(['oauth_intent' => $intent]);

        return Socialite::driver('google')->redirect();
    }

    public function handleGoogleCallback()
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (\Exception $e) {
            Log::error('Google login gagal: ' . $e->getMessage());
            return redirect()->route('login')->withErrors(['email' => 'Login dengan Google gagal, coba lagi.']);
        }

        // Retrieve intent (login or register) and then remove it from session
        $intent = session()->pull('oauth_intent', 'login');

        // Cari berdasarkan google_id dulu, fallback ke email (untuk user lama yang daftar manual)
        $user = User::findByGoogleOrEmail($googleUser->getId(), $googleUser->getEmail());

        // Pastikan role default ada
        $defaultRole = Role::where('slug', 'citizen')->first() ?? Role::where('slug', 'user')->first();

        if (! $user) {
            // If the flow was initiated from the login page, do not auto-register
            if ($intent !== 'register') {
                return redirect()->route('login')->withErrors(['email' => 'Akun belum terdaftar. Silakan daftar terlebih dahulu.']);
            }
            $user = User::create([
                'name'              => $googleUser->getName(),
                'email'             => $googleUser->getEmail(),
                'google_id'         => $googleUser->getId(),
                'password'          => bcrypt(Str::random(24)), // tidak pernah dipakai untuk login
                'role_id'           => $defaultRole?->id,
                'user_type'         => 'CITIZEN',
                'email_verified_at' => now(),
            ]);

            if ($defaultRole) {
                $user->roles()->syncWithoutDetaching([$defaultRole->id]);
            }
        } else {
            $updates = [];
            if (! $user->google_id) {
                $updates['google_id'] = $googleUser->getId();
            }

            if (! $user->role_id && $defaultRole) {
                $updates['role_id'] = $defaultRole->id;
            }

            if (! empty($updates)) {
                $user->update($updates);
            }

            if ($defaultRole && ! $user->roles()->where('roles.id', $defaultRole->id)->exists()) {
                $user->roles()->syncWithoutDetaching([$defaultRole->id]);
            }
        }

        Auth::login($user, remember: true);
        request()->session()->regenerate();

        $user = Auth::user()->loadMissing('passkeys');

        session([
            'user_id'        => Auth::id(),
            'user_name'      => $user->name,
            'user_role'      => $user->role?->slug,
            'last_activity'  => now()->timestamp,
        ]);

        if ($user->passkeys()->doesntExist()) {
            return redirect()->route('dashboard')->with('show_passkey_prompt', true);
        }

        return redirect()->route('dashboard');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login')->with('info', 'Anda telah logout.');
    }
}
