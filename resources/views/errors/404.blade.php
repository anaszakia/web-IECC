@extends('layouts.auth')

@section('title', '404 - Halaman Tidak Ditemukan')

@section('content')
<main class="min-vh-100 d-flex align-items-center justify-content-center py-6" style="background: #f8f9fa;">
    <section class="container">
        <div class="row justify-content-center">
            <div class="col-xl-5 col-lg-6 col-md-8 col-12 text-center">

                {{-- Icon --}}
                <div class="position-relative d-inline-flex align-items-center justify-content-center mb-6">
                    <div class="rounded-circle d-flex align-items-center justify-content-center"
                        style="width:120px; height:120px; background: rgba(13,110,253,0.1);">
                        <svg width="56" height="56" viewBox="0 0 24 24" fill="none"
                            stroke="#0d6efd" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="11" cy="11" r="8"/>
                            <path d="M21 21l-4.35-4.35"/>
                            <path d="M11 8v3M11 14h.01"/>
                        </svg>
                    </div>
                    <div class="position-absolute rounded-circle d-flex align-items-center justify-content-center"
                        style="width:32px; height:32px; background: rgba(13,110,253,0.15); top:-4px; right:-4px;">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none"
                            stroke="#0d6efd" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M18 6L6 18M6 6l12 12"/>
                        </svg>
                    </div>
                </div>

                {{-- Badge --}}
                <div class="d-inline-block mb-3">
                    <span class="badge rounded-pill" style="background: rgba(13,110,253,0.1); color:#0d6efd; font-size:12px; padding: 6px 16px; border: 1px solid rgba(13,110,253,0.2);">
                        Error 404
                    </span>
                </div>

                {{-- Heading --}}
                <h1 class="mb-2" style="font-size: 28px; font-weight: 600;">Halaman Tidak Ditemukan</h1>
                <p class="text-muted mb-5" style="font-size: 15px; line-height: 1.6;">
                    Halaman yang Anda cari tidak ada atau telah dipindahkan. Periksa kembali URL yang Anda masukkan atau kembali ke halaman utama.
                </p>

                {{-- Detail Card --}}
                <div class="card card-lg mb-5 text-start">
                    <div class="card-body">
                        <p class="text-muted mb-3" style="font-size:11px; text-transform:uppercase; letter-spacing:.05em;">Detail Error</p>
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <i class="ti ti-info-circle text-muted"></i>
                            <span class="text-muted small">Status: <strong class="text-dark">404 Not Found</strong></span>
                        </div>
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <i class="ti ti-link text-muted"></i>
                            <span class="text-muted small">URL: <strong class="text-dark">{{ request()->fullUrl() }}</strong></span>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <i class="ti ti-calendar text-muted"></i>
                            <span class="text-muted small">Waktu: <strong class="text-dark">{{ now()->format('d M Y, H:i:s') }}</strong></span>
                        </div>
                    </div>
                </div>

                {{-- Buttons --}}
                <div class="d-flex gap-3 justify-content-center">
                    <a href="{{ url('/dashboard') }}" class="btn btn-primary btn-lg d-flex align-items-center gap-2">
                        <i class="ti ti-home"></i> Go Home
                    </a>
                    <a href="javascript:history.back()" class="btn btn-white btn-lg d-flex align-items-center gap-2">
                        <i class="ti ti-arrow-left"></i> Kembali
                    </a>
                </div>

            </div>
        </div>
    </section>
</main>
@endsection