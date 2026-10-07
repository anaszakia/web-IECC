@extends('layouts.auth')

@section('title', '403 - Akses Ditolak')

@section('content')
<main class="min-vh-100 d-flex align-items-center justify-content-center py-6"
    style="background: #f8f9fa;">
    <section class="container">
        <div class="row justify-content-center">
            <div class="col-xl-5 col-lg-6 col-md-8 col-12 text-center">

                {{-- Icon --}}
                <div class="position-relative d-inline-flex align-items-center justify-content-center mb-6">
                    <div class="rounded-circle d-flex align-items-center justify-content-center"
                        style="width:120px; height:120px; background: rgba(220,53,69,0.1);">
                        <svg width="56" height="56" viewBox="0 0 24 24" fill="none"
                            stroke="#dc3545" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                            <path d="M12 8v4M12 16h.01"/>
                        </svg>
                    </div>
                    <div class="position-absolute rounded-circle d-flex align-items-center justify-content-center"
                        style="width:32px; height:32px; background: rgba(255,193,7,0.2); top:-4px; right:-4px;">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none"
                            stroke="#ffc107" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M18 6L6 18M6 6l12 12"/>
                        </svg>
                    </div>
                </div>

                {{-- Badge --}}
                <div class="d-inline-block mb-3">
                    <span class="badge rounded-pill" style="background: rgba(220,53,69,0.1); color:#dc3545; font-size:12px; padding: 6px 16px; border: 1px solid rgba(220,53,69,0.2);">
                        Error 403
                    </span>
                </div>

                {{-- Heading --}}
                <h1 class="mb-2" style="font-size: 28px; font-weight: 600;">Akses Ditolak</h1>
                <p class="text-muted mb-5" style="font-size: 15px; line-height: 1.6;">
                    {{ isset($exception) && $exception->getMessage() ? $exception->getMessage() : 'Anda tidak memiliki izin untuk mengakses halaman ini. Hubungi administrator jika Anda merasa ini adalah kesalahan.' }}
                </p>

                {{-- Detail Card --}}
                <div class="card card-lg mb-5 text-start">
                    <div class="card-body">
                        <p class="text-muted mb-3" style="font-size:11px; text-transform:uppercase; letter-spacing:.05em;">Detail Error</p>
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <i class="ti ti-info-circle text-muted"></i>
                            <span class="text-muted small">Status: <strong class="text-dark">403 Forbidden</strong></span>
                        </div>
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <i class="ti ti-shield-off text-muted"></i>
                            <span class="text-muted small">Alasan: <strong class="text-dark">Hak akses tidak mencukupi</strong></span>
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