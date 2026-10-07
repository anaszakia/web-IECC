@extends('layouts.auth')

@section('title', 'Reset Password')

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
                        <h1 class="mb-1">Set new password</h1>
                        <p class="mb-0">No worries, we will send you reset instruction.</p>
                    </div>
                </div>
            </div>
            <div class="row justify-content-center">
                <div class="col-xl-5 col-lg-6 col-md-8 col-12">
                    <div class="card card-lg mb-6">
                        <div class="card-body p-6">
                            <form method="POST" action="{{ route('password.update') }}" class="needs-validation" novalidate>
                                @csrf
                                <input type="hidden" name="token" value="{{ $token }}">
                                <input type="hidden" name="email" value="{{ $email ?? old('email') }}">

                                <div class="mb-3">
                                    <label for="password" class="form-label">Password</label>
                                    <div class="password-field position-relative">
                                        <input type="password"
                                            class="form-control fakePassword @error('password') is-invalid @enderror"
                                            id="password" name="password" required />
                                        <span><i class="ti ti-eye-off passwordToggler"></i></span>
                                        @error('password')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @else
                                            <div class="invalid-feedback">Please enter password.</div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="password_confirmation" class="form-label">Confirm Password</label>
                                    <div class="password-field position-relative">
                                        <input type="password"
                                            class="form-control fakePassword"
                                            id="password_confirmation" name="password_confirmation" required />
                                        <span><i class="ti ti-eye-off passwordToggler"></i></span>
                                        <div class="invalid-feedback">Please confirm your password.</div>
                                    </div>
                                </div>

                                <div class="d-grid mb-4">
                                    <button class="btn btn-primary" type="submit">Reset Password</button>
                                </div>

                                <div class="text-center">
                                    <a href="{{ route('login') }}">Back to Login</a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>
@endsection