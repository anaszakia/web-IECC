@extends('layouts.auth')

@section('title', 'Sign In')

@section('content')
<main class="d-flex flex-column justify-content-center min-vh-100 py-6">
    <section>
        <div class="container">
            <div class="row mb-8">
                <div class="col-xl-4 offset-xl-4 col-md-12 col-12">
                    <div class="text-center">
                        <a href="{{ url('/dashboard') }}" class="fs-2 fw-bold d-flex align-items-center gap-2 justify-content-center mb-6">
                            <img src="{{ asset('images/brand/logo/logo-icon.svg') }}" alt="" />
                            <span>Dasher</span>
                        </a>
                        <h1 class="mb-1">Selamat Datang</h1>
                        <p class="mb-0">
                            Belum punya akun?
                            <a href="{{ route('register') }}" class="text-primary">Daftar di sini</a>
                        </p>
                    </div>
                </div>
            </div>
            <div class="row justify-content-center">
                <div class="col-xl-5 col-lg-6 col-md-8 col-12">
                    <div class="card card-lg mb-6">
                        <div class="card-body p-6">
                            <form method="POST" action="{{ route('login') }}" class="needs-validation mb-6" novalidate>
                                @csrf
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                                    <input type="email" class="form-control @error('email') is-invalid @enderror"
                                        id="email" name="email" value="{{ old('email') }}" autocomplete="email webauthn" required />
                                    @error('email')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="mb-3">
                                    <label for="password" class="form-label">Password</label>
                                    <div class="password-field position-relative">
                                        <input type="password" class="form-control fakePassword @error('password') is-invalid @enderror"
                                            id="password" name="password" required />
                                        <span><i class="ti ti-eye-off passwordToggler"></i></span>
                                        @error('password')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="mb-4 d-flex align-items-center justify-content-between">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="remember" id="rememberMe" data-passkey-remember />
                                        <label class="form-check-label" for="rememberMe">Ingat Saya</label>
                                    </div>
                                    @if (Route::has('password.request'))
                                        <a href="{{ route('password.request') }}" class="text-primary">Lupa Password</a>
                                    @endif
                                </div>
                                <div class="d-grid">
                                    <button class="btn btn-primary" type="submit">Masuk</button>
                                </div>
                            </form>

                            <div class="d-flex align-items-center gap-3 mb-3">
                                <hr class="flex-grow-1 my-0">
                                <span class="text-muted small">Atau masuk dengan</span>
                                <hr class="flex-grow-1 my-0">
                            </div>
                            <div class="d-grid mb-3">
                                <button class="btn btn-outline-primary" type="button" data-passkey-login>
                                    <span class="me-2"><i class="ti ti-fingerprint"></i></span>
                                    Passkey
                                </button>
                            </div>
                            <div class="alert alert-danger py-2 small d-none" data-passkey-login-message></div>
                            <div class="d-flex gap-2 justify-content-between">
                                <a href="{{ route('auth.google.redirect') }}?intent=login" class="btn btn-google w-100">
                                    <span class="me-2">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                            <path d="M15.545 6.558a9.42 9.42 0 0 1 .139 1.626c0 2.434-.87 4.492-2.384 5.885h.002C11.978 15.292 10.158 16 8 16A8 8 0 1 1 8 0a7.689 7.689 0 0 1 5.352 2.082l-2.284 2.284A4.347 4.347 0 0 0 8 3.166c-2.087 0-3.86 1.408-4.492 3.304a4.792 4.792 0 0 0 0 3.063h.003c.635 1.893 2.405 3.301 4.492 3.301 1.078 0 2.004-.276 2.722-.764h-.003a3.702 3.702 0 0 0 1.599-2.431H8v-3.08h7.545z"/>
                                        </svg>
                                    </span>
                                    Google
                                </a>
                                <a href="#" class="btn btn-dark w-100">
                                    <span class="me-2">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                            <path d="M11.182.008C11.148-.03 9.923.023 8.857 1.18c-1.066 1.156-.902 2.482-.878 2.516.024.034 1.52.087 2.475-1.258.955-1.345.762-2.391.728-2.43zm3.314 11.733c-.048-.096-2.325-1.234-2.113-3.422.212-2.189 1.675-2.789 1.698-2.854.023-.065-.597-.79-1.254-1.157a3.692 3.692 0 0 0-1.563-.434c-.108-.003-.483-.095-1.254.116-.508.139-1.653.589-1.968.607-.316.018-1.256-.522-2.267-.665-.647-.125-1.333.131-1.824.328-.49.196-1.422.754-2.074 2.237-.652 1.482-.311 3.83-.067 4.56.244.729.625 1.924 1.273 2.796.576.984 1.34 1.667 1.659 1.899.319.232 1.219.386 1.843.067.502-.308 1.408-.485 1.766-.472.357.013 1.061.154 1.782.539.545.19 1.06.116 1.583-.105.524-.221 1.301-1.058 2.183-2.45a9.606 9.606 0 0 0 .865-1.79c-.03-.013-1.601-.616-1.649-2.45z"/>
                                        </svg>
                                    </span>
                                    Apple
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>
@endsection
