@extends('layouts.auth')

@section('title', 'Forgot Password')

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
                        <h1 class="mb-1">Forgot Password</h1>
                        <p class="mb-0 text-secondary">No worries, we will send you reset instruction.</p>
                    </div>
                </div>
            </div>
            <div class="row justify-content-center">
                <div class="col-xl-5 col-lg-6 col-md-8 col-12">
                    <div class="card card-lg mb-6">
                        <div class="card-body p-6">

                            {{-- Success message --}}
                            @if (session('status'))
                                <div class="alert alert-success mb-4">
                                    {{ session('status') }}
                                </div>
                            @endif

                            <form method="POST" action="{{ route('password.email') }}" class="needs-validation mb-5" novalidate>
                                @csrf
                                <div class="mb-3">
                                    <label for="email" class="form-label">
                                        Email <span class="text-danger">*</span>
                                    </label>
                                    <input type="email"
                                        class="form-control @error('email') is-invalid @enderror"
                                        id="email" name="email"
                                        value="{{ old('email') }}"
                                        placeholder="Enter your email" required />
                                    @error('email')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @else
                                        <div class="invalid-feedback">Please enter email.</div>
                                    @enderror
                                </div>
                                <div class="d-grid">
                                    <button class="btn btn-primary" type="submit">Reset Password</button>
                                </div>
                            </form>

                            <div class="text-center">
                                <a href="{{ route('login') }}">Back to Login</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>
@endsection