@extends('layouts.auth')

@section('title', 'OTP Verification')

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
                        <h1 class="mb-1">OTP Verification</h1>
                        <p class="mb-0">
                            We sent a code to
                            <a href="#" class="text-inherit">dasher@example.com</a>
                        </p>
                    </div>
                </div>
            </div>
            <div class="row justify-content-center">
                <div class="col-xl-4 col-lg-6 col-md-8 col-12">
                    <div class="card card-lg mb-6">
                        <div class="card-body p-6">
                            <form method="POST" action="#">
                                @csrf
                                <div class="d-flex flex-row gap-2 mb-5">
                                    <input type="text" class="form-control inputpass-code text-center" maxlength="1" />
                                    <input type="text" class="form-control inputpass-code text-center" maxlength="1" />
                                    <input type="text" class="form-control inputpass-code text-center" maxlength="1" />
                                    <input type="text" class="form-control inputpass-code text-center" maxlength="1" />
                                </div>
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="d-grid mb-4">
                                            <button class="btn btn-primary" type="submit">Continue</button>
                                        </div>
                                        <div class="text-center mb-3">
                                            <span>Didn't receive the email? Click <a href="#">send it again.</a></span>
                                        </div>
                                        <div class="text-center">
                                            <a href="{{ route('login') }}">Back to Login</a>
                                        </div>
                                    </div>
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

@push('scripts')
<script>
    // Auto move to next input
    document.querySelectorAll('.inputpass-code').forEach((input, index, inputs) => {
        input.addEventListener('input', () => {
            if (input.value.length === 1 && index < inputs.length - 1) {
                inputs[index + 1].focus();
            }
        });
        input.addEventListener('keydown', (e) => {
            if (e.key === 'Backspace' && input.value === '' && index > 0) {
                inputs[index - 1].focus();
            }
        });
    });
</script>
@endpush