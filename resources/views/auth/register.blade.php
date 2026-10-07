@extends('layouts.auth')

@section('title', 'Sign Up')

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
                        <h1 class="mb-1">Buat Akun</h1>
                        <p class="mb-0">Daftar sekarang dan dapatkan akun gratis secara instan.</p>
                    </div>
                </div>
            </div>
            <div class="row justify-content-center">
                <div class="col-xl-5 col-lg-6 col-md-8 col-12">
                    <div class="card shadow-sm mb-4">
                        <div class="card-body p-6">
                            <form method="POST" action="{{ route('register') }}" class="needs-validation mb-6" novalidate>
                                @csrf
                                <div class="mb-3">
                                    <label for="name" class="form-label">Nama Lengkap</label>
                                    <input type="text" class="form-control @error('name') is-invalid @enderror"
                                        id="name" name="name" value="{{ old('name') }}" required />
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @else
                                        <div class="invalid-feedback">Masukkan nama lengkap.</div>
                                    @enderror
                                </div>
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                                    <input type="email" class="form-control @error('email') is-invalid @enderror"
                                        id="email" name="email" value="{{ old('email') }}" required />
                                    @error('email')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @else
                                        <div class="invalid-feedback">Masukkan email.</div>
                                    @enderror
                                </div>
                                <div class="mb-3">
                                    <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
                                    <div class="password-field position-relative">
                                        <input type="password" class="form-control fakePassword @error('password') is-invalid @enderror"
                                            id="password" name="password" required autocomplete="new-password" />
                                        <span><i class="ti ti-eye-off passwordToggler"></i></span>
                                        @error('password')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    
                                    {{-- Password Strength Meter UI --}}
                                    <div class="mt-2" id="passwordStrengthWrapper">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <small class="text-muted">Kekuatan Password:</small>
                                            <small class="fw-bold" id="strengthText">-</small>
                                        </div>
                                        <div class="progress" style="height: 6px;">
                                            <div class="progress-bar" id="strengthBar" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                                        </div>
                                        
                                        <div class="mt-2 small" id="passwordRequirements">
                                            <div class="text-muted mb-1"><small>Ketentuan password:</small></div>
                                            <ul class="list-unstyled mb-0" style="font-size: 0.8rem;">
                                                <li id="rule-length" class="text-danger"><i class="ti ti-circle-x me-1"></i> Minimal 8 karakter</li>
                                                <li id="rule-case" class="text-danger"><i class="ti ti-circle-x me-1"></i> Kombinasi huruf besar & kecil (A-Z, a-z)</li>
                                                <li id="rule-number" class="text-danger"><i class="ti ti-circle-x me-1"></i> Mengandung angka (0-9)</li>
                                                <li id="rule-symbol" class="text-danger"><i class="ti ti-circle-x me-1"></i> Mengandung simbol unik (!@#$%^&*...)</li>
                                                <li id="rule-user" class="text-danger"><i class="ti ti-circle-x me-1"></i> Tidak boleh sama/mengandung nama atau email</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="password_confirmation" class="form-label">Konfirmasi Password <span class="text-danger">*</span></label>
                                    <div class="password-field position-relative">
                                        <input type="password" class="form-control fakePassword"
                                            id="password_confirmation" name="password_confirmation" required autocomplete="new-password" />
                                        <span><i class="ti ti-eye-off passwordToggler"></i></span>
                                        <div id="confirmFeedback" class="small mt-1 d-none"></div>
                                    </div>
                                </div>
                                <div class="mb-4 d-flex align-items-center justify-content-between">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="agreeTerms" required />
                                        <label class="form-check-label ms-2" for="agreeTerms">
                                            <a href="#">Syarat dan Ketentuan</a> & <a href="#">Kebijakan Privasi</a>
                                        </label>
                                    </div>
                                </div>
                                <div class="d-grid">
                                    <button class="btn btn-primary" id="btnSubmit" type="submit">Daftar</button>
                                </div>
                            </form>

                            <span>Daftar dengan jaringan sosial Anda.</span>
                            <div class="mt-3 d-flex gap-2 justify-content-between">
                                <a href="{{ route('auth.google.redirect') }}?intent=register" class="btn btn-google w-100">
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
                    <span>
                        Sudah punya akun?
                        <a href="{{ route('login') }}" class="text-primary">Masuk di sini.</a>
                    </span>
                </div>
            </div>
        </div>
    </section>
</main>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const passwordInput = document.getElementById('password');
    const confirmInput = document.getElementById('password_confirmation');
    const nameInput = document.getElementById('name');
    const emailInput = document.getElementById('email');
    const strengthBar = document.getElementById('strengthBar');
    const strengthText = document.getElementById('strengthText');
    const confirmFeedback = document.getElementById('confirmFeedback');
    const btnSubmit = document.getElementById('btnSubmit');

    const ruleLength = document.getElementById('rule-length');
    const ruleCase = document.getElementById('rule-case');
    const ruleNumber = document.getElementById('rule-number');
    const ruleSymbol = document.getElementById('rule-symbol');
    const ruleUser = document.getElementById('rule-user');

    function updateRule(element, isValid) {
        if (isValid) {
            element.classList.remove('text-danger');
            element.classList.add('text-success');
            const icon = element.querySelector('i');
            if (icon) icon.className = 'ti ti-circle-check me-1';
        } else {
            element.classList.remove('text-success');
            element.classList.add('text-danger');
            const icon = element.querySelector('i');
            if (icon) icon.className = 'ti ti-circle-x me-1';
        }
    }

    function calculateStrength(pwd) {
        const name = (nameInput.value || '').trim().toLowerCase();
        const email = (emailInput.value || '').trim().toLowerCase();
        const emailUser = email ? email.split('@')[0] : '';
        const pwdLower = pwd.toLowerCase();

        // Rules check
        const isLenValid = pwd.length >= 8;
        const isCaseValid = /[A-Z]/.test(pwd) && /[a-z]/.test(pwd);
        const isNumValid = /[0-9]/.test(pwd);
        const isSymValid = /[^A-Za-z0-9]/.test(pwd);
        
        let isUserValid = true;
        if (pwd.length > 0) {
            if (name.length >= 3 && pwdLower.includes(name)) isUserValid = false;
            if (email.length >= 3 && pwdLower.includes(email)) isUserValid = false;
            if (emailUser.length >= 3 && pwdLower.includes(emailUser)) isUserValid = false;
        }

        updateRule(ruleLength, isLenValid);
        updateRule(ruleCase, isCaseValid);
        updateRule(ruleNumber, isNumValid);
        updateRule(ruleSymbol, isSymValid);
        updateRule(ruleUser, isUserValid);

        if (!pwd) {
            strengthBar.style.width = '0%';
            strengthBar.className = 'progress-bar';
            strengthText.textContent = '-';
            strengthText.className = 'fw-bold';
            return 0;
        }

        let score = 0;
        if (isLenValid) score++;
        if (isCaseValid) score++;
        if (isNumValid) score++;
        if (isSymValid) score++;
        if (isUserValid) score++;

        // Bonus length
        if (pwd.length >= 12 && isLenValid && isCaseValid && isNumValid && isSymValid && isUserValid) {
            score++;
        }

        // Level Akurasi: Lemah, Sedang, Kuat, Sangat Kuat
        // Syarat Kuat: Memenuhi 8 char + upper/lower + number + symbol + not contain user info (score >= 5)
        if (!isUserValid || score < 3) {
            strengthBar.style.width = '25%';
            strengthBar.className = 'progress-bar bg-danger';
            strengthText.textContent = 'Lemah';
            strengthText.className = 'fw-bold text-danger';
            return 1;
        } else if (score === 3 || score === 4) {
            strengthBar.style.width = '50%';
            strengthBar.className = 'progress-bar bg-warning';
            strengthText.textContent = 'Sedang';
            strengthText.className = 'fw-bold text-warning';
            return 2;
        } else if (score === 5) {
            strengthBar.style.width = '75%';
            strengthBar.className = 'progress-bar bg-primary';
            strengthText.textContent = 'Kuat (Memenuhi Syarat)';
            strengthText.className = 'fw-bold text-primary';
            return 3;
        } else {
            strengthBar.style.width = '100%';
            strengthBar.className = 'progress-bar bg-success';
            strengthText.textContent = 'Sangat Kuat';
            strengthText.className = 'fw-bold text-success';
            return 4;
        }
    }

    function checkConfirmation() {
        const pwd = passwordInput.value;
        const conf = confirmInput.value;

        if (!conf) {
            confirmFeedback.className = 'small mt-1 d-none';
            return;
        }

        if (pwd === conf) {
            confirmFeedback.className = 'small mt-1 text-success d-block';
            confirmFeedback.innerHTML = '<i class="ti ti-check me-1"></i> Konfirmasi password cocok.';
        } else {
            confirmFeedback.className = 'small mt-1 text-danger d-block';
            confirmFeedback.innerHTML = '<i class="ti ti-x me-1"></i> Password tidak cocok.';
        }
    }

    passwordInput.addEventListener('input', function () {
        calculateStrength(this.value);
        checkConfirmation();
    });

    confirmInput.addEventListener('input', checkConfirmation);
    nameInput.addEventListener('input', () => calculateStrength(passwordInput.value));
    emailInput.addEventListener('input', () => calculateStrength(passwordInput.value));

    // Submit handler
    const form = document.querySelector('form.needs-validation');
    form.addEventListener('submit', function (e) {
        const strength = calculateStrength(passwordInput.value);
        
        // Minimal harus "Kuat" (level >= 3)
        if (strength < 3) {
            e.preventDefault();
            e.stopPropagation();
            alert('Password belum memenuhi standar keamanan minimal (Kuat). Harap lengkapi seluruh ketentuan password.');
            passwordInput.focus();
            return false;
        }

        if (passwordInput.value !== confirmInput.value) {
            e.preventDefault();
            e.stopPropagation();
            alert('Konfirmasi password tidak cocok.');
            confirmInput.focus();
            return false;
        }
    });
});
</script>
@endpush