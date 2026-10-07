<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Laravel\Passkeys\Contracts\PasskeyLoginResponse as PasskeyLoginResponseContract;

class PasskeyLoginResponse implements PasskeyLoginResponseContract
{
    public function toResponse($request)
    {
        if (Auth::check()) {
            // Reload user dengan relasi passkeys untuk memastikan data terbaru
            $user = Auth::user()->loadMissing('passkeys');

            session([
                'user_id' => Auth::id(),
                'user_name' => $user->name,
                'user_role' => $user->role?->slug,
                'last_activity' => now()->timestamp,
            ]);

            // Tunjukkan prompt passkey jika user belum punya passkey
            if ($user->passkeys()->doesntExist()) {
                $request->session()->put('show_passkey_prompt', true);
            }
        }

        $redirect = redirect()->intended(config('passkeys.redirect', '/dashboard'))->getTargetUrl();

        if ($request->wantsJson()) {
            return new JsonResponse(['redirect' => $redirect], 200);
        }

        return redirect()->to($redirect);
    }
}
