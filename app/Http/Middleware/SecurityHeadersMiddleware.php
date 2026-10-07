<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeadersMiddleware
{
    /**
     * Handle an incoming request and apply security HTTP headers.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Mencegah Clickjacking (tidak bisa di-embed di iframe situs lain)
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');

        // Mencegah MIME-type sniffing (browser tidak akan mengeksekusi file selain tipe aslinya)
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // Mengaktifkan filter XSS bawaan browser
        $response->headers->set('X-XSS-Protection', '1; mode=block');

        // Mengontrol informasi referrer saat navigasi keluar
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Izin fitur hardware/browser yang diperlukan sistem (geolocation diizinkan untuk origin aplikasi)
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(self)');

        return $response;
    }
}
