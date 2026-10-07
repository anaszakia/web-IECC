<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta content="Codescandy" name="author">
    <title>@yield('title', 'Dasher Dashboard')</title>

    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/favicon/favicon-32x32.png') }}" />

    <script src="{{ asset('js/vendors/color-modes.js') }}"></script>
    <script>
        if (localStorage.getItem('sidebarExpanded') === 'false') {
            document.documentElement.classList.add('collapsed');
            document.documentElement.classList.remove('expanded');
        } else {
            document.documentElement.classList.remove('collapsed');
            document.documentElement.classList.add('expanded');
        }
    </script>

    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@300;400;500;600;700;800&display=swap" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/simplebar@latest/dist/simplebar.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
    <link rel="stylesheet" href="{{ asset('css/theme.css') }}" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" />
    @vite(['resources/js/app.js'])

    @stack('styles')
</head>

<body>

    @include('layouts.sidebar')

    <div id="content" class="position-relative h-100">
        @include('layouts.topbar')

        <div class="custom-container">
            @yield('content')
        </div>
    </div>

    {{-- Bootstrap JS (wajib pertama) --}}
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script src="{{ asset('js/vendors/sidebarnav.js') }}"></script>
    <script src="{{ asset('js/vendors/color-modes.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script src="{{ asset('js/vendors/chart.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
    <script src="{{ asset('js/vendors/swiper.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>
    <script src="{{ asset('js/vendors/choice.js') }}"></script>
    <script src="{{ asset('js/main.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>

    <script>
        // Handle semua form delete dengan SweetAlert
        document.addEventListener('DOMContentLoaded', function () {

            document.querySelectorAll('[data-confirm]').forEach(function (el) {
                el.addEventListener('click', function (e) {
                    e.preventDefault();

                    const message = el.getAttribute('data-confirm') || 'Yakin ingin melanjutkan?';
                    const form    = el.closest('form');

                    Swal.fire({
                        title: 'Konfirmasi',
                        text: message,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#dc3545',
                        cancelButtonColor: '#6c757d',
                        confirmButtonText: 'Ya, Hapus!',
                        cancelButtonText: 'Batal',
                    }).then((result) => {
                        if (result.isConfirmed) {
                            form.submit();
                        }
                    });
                });
            });

        });
    </script>

    <script>
        const TIMEOUT    = 10 * 60 * 1000; // 10 menit
        const WARNING_AT = 9 * 60 * 1000;  // peringatan di menit ke-9
        const LOGIN_URL  = '{{ route('login') }}';
        let warningShown = false;
        let lastActivity = Date.now();

        // Reset timer saat ada aktivitas
        ['mousemove', 'keydown', 'click', 'scroll', 'touchstart'].forEach(event => {
            document.addEventListener(event, () => {
                lastActivity = Date.now();
                warningShown = false;
            });
        });

        setInterval(() => {
            const idle = Date.now() - lastActivity;

            // Peringatan 1 menit sebelum logout
            if (idle >= WARNING_AT && !warningShown) {
                warningShown = true;
                Swal.fire({
                    icon: 'warning',
                    title: 'Sesi Hampir Berakhir!',
                    text: 'Anda akan otomatis logout dalam 1 menit karena tidak aktif.',
                    showCancelButton: true,
                    confirmButtonText: 'Tetap Login',
                    cancelButtonText: 'Logout Sekarang',
                    confirmButtonColor: '#0d6efd',
                    cancelButtonColor: '#dc3545',
                    timer: 60000,
                    timerProgressBar: true,
                }).then((result) => {
                    if (result.isDismissed && result.dismiss !== Swal.DismissReason.timer) {
                        // Logout sekarang
                        document.getElementById('logout-form').submit();
                    } else if (result.isConfirmed) {
                        // Kirim request untuk update last_activity di server
                        fetch('/keep-alive', { method: 'POST', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' } });
                        lastActivity = Date.now();
                    }
                });
            }

            // Auto logout di client side
            if (idle >= TIMEOUT) {
                window.location.replace(LOGIN_URL);
            }

        }, 5000); // cek setiap 5 detik
    </script>

    {{-- Form logout hidden --}}
    <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
        @csrf
    </form>

    @if (session('show_passkey_prompt') && authUser()?->passkeys()->doesntExist())
        <div class="modal fade" id="passkeyPromptModal" tabindex="-1" aria-labelledby="passkeyPromptModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="passkeyPromptModalLabel">Daftarkan Passkey</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <div class="modal-body">
                        <p class="text-muted mb-4">Login berikutnya bisa memakai fingerprint, Face ID, Windows Hello, PIN perangkat, atau security key.</p>
                        <label class="form-label" for="passkeyName">Nama perangkat</label>
                        <input type="text" class="form-control" id="passkeyName" value="{{ gethostname() ?: 'Perangkat saya' }}" data-passkey-name>
                        <div class="alert alert-danger py-2 small d-none mt-3 mb-0" data-passkey-register-message></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-white" data-bs-dismiss="modal">Nanti</button>
                        <button type="button" class="btn btn-primary" data-passkey-register>
                            <span class="me-2"><i class="ti ti-fingerprint"></i></span>
                            Daftarkan
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- SweetAlert untuk session flash --}}
    @include('layouts.sweetalert')

    @stack('scripts')
</body>

</html>
