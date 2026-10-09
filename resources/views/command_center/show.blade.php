@extends('layouts.app')

@section('title', 'Detail Insiden - ' . $incident->incident_no)

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<style>
    #detail-map {
        height: 320px;
        border-radius: 8px;
    }
</style>
@endpush

@section('content')
@php
    $isOverriddenByOperator = $incident->severity_source === 'OPERATOR' || $incident->status === 'VERIFIED';
    $aiSummary = strtolower($incident->aiAnalysis?->summary ?? '');
    $aiMediaAnalysis = strtolower($incident->aiAnalysis?->media_analysis ?? '');
    $isInvalidReport = !$isOverriddenByOperator && $incident->aiAnalysis && (
        $incident->aiAnalysis->category === 'UNKNOWN' ||
        $incident->aiAnalysis->confidence < 0.4 ||
        str_contains($aiSummary, 'tidak valid') ||
        str_contains($aiMediaAnalysis, 'tidak valid')
    );
@endphp
<div class="row g-4">
    {{-- Header & Status Action Bar --}}
    <div class="col-12">
        <div class="card card-lg shadow-sm border-0">
            <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div>
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <h3 class="fw-bold mb-0">{{ $incident->incident_no }}</h3>
                        @php
                            $badgeBg = match($incident->status) {
                                'NEW' => 'danger',
                                'VERIFIED' => 'warning',
                                'DISPATCHED', 'ACCEPTED', 'ARRIVED' => 'primary',
                                'RESOLVED', 'CLOSED' => 'success',
                                'FALSE_REPORT', 'REJECTED' => 'dark',
                                'CANCELLED', 'DUPLICATE' => 'secondary',
                                default => 'secondary'
                            };
                        @endphp
                        <span class="badge bg-{{ $badgeBg }} fs-6">
                            {{ $incident->status_label }}
                        </span>
                        @if($incident->severity)
                            <span class="badge bg-danger-subtle text-danger border border-danger">
                                Tingkat Keparahan {{ $incident->severity }}
                            </span>
                        @endif
                    </div>
                    <p class="text-muted mb-0 small">
                        Dilaporkan pada {{ $incident->reported_at?->format('d M Y, H:i:s') }} ({{ $incident->reported_at?->diffForHumans() }})
                    </p>
                </div>

                <div class="d-flex gap-2">
                    @if(in_array($incident->status, ['NEW', 'VERIFIED']))
                        <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#verifyModal">
                            <i class="ti ti-check me-1"></i> {{ $incident->status === 'VERIFIED' ? 'Ubah / Koreksi Verifikasi' : 'Verifikasi Insiden' }}
                        </button>
                        <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#rejectModal">
                            <i class="ti ti-ban me-1"></i> Tolak Laporan
                        </button>
                    @endif
                    <a href="{{ route('command-center.index') }}" class="btn btn-outline-secondary">
                        <i class="ti ti-arrow-left me-1"></i> Kembali ke Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- Kolom Kiri: Detail, Media, & Lokasi --}}
    <div class="col-xl-7 col-lg-6">
        <div class="card card-lg shadow-sm border-0 mb-4">
            <div class="card-header bg-transparent border-bottom-0 pt-4">
                <h5 class="fw-bold mb-0"><i class="ti ti-info-circle me-2 text-primary"></i>Informasi Kejadian</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <span class="text-muted small">KATEGORI INSIDEN</span>
                        <div class="fw-semibold fs-6">{{ $incident->category_label }}</div>
                    </div>
                    <div class="col-md-6">
                        <span class="text-muted small">SUMBER LAPORAN</span>
                        <div class="fw-semibold fs-6">
                            {{ $incident->source === 'APP' ? 'Aplikasi Mobile Warga' : ($incident->source === 'CALL_CENTER' ? 'Call Center 112' : $incident->source) }}
                        </div>
                    </div>
                    <div class="col-12">
                        <span class="text-muted small">DESKRIPSI WARGA / KETERANGAN</span>
                        <div class="p-3 bg-light rounded-3 mt-1 text-dark">
                            {{ $incident->description ?: 'Tidak ada deskripsi tambahan.' }}
                        </div>
                    </div>
                    <div class="col-12">
                        <span class="text-muted small">ALAMAT / CATATAN LOKASI</span>
                        <div class="fw-semibold">{{ $incident->address_text ?: 'Koordinat Titik GPS' }}</div>
                    </div>
                </div>

                {{-- Media Lampiran (Foto / Video / Suara) --}}
                <div class="mt-4">
                    <span class="text-muted small fw-semibold">LAMPIRAN BUKTI MEDIA ({{ $incident->media->count() }})</span>
                    <div class="d-flex gap-3 mt-2 flex-wrap">
                        @forelse($incident->media as $media)
                            @if($media->type === 'VIDEO')
                                @php
                                    $videoUrl = str_starts_with($media->disk_path, 'http') || str_starts_with($media->disk_path, 'data:')
                                        ? $media->disk_path 
                                        : Storage::disk('public')->url($media->disk_path);
                                @endphp
                                <div class="rounded-3 border overflow-hidden position-relative shadow-sm" style="width: 260px; background: #0f172a;">
                                    <video src="{{ $videoUrl }}" controls playsinline preload="metadata" style="width: 100%; height: 160px; object-fit: contain; background: #000;">
                                        <source src="{{ $videoUrl }}" type="{{ $media->mime ?: 'video/mp4' }}">
                                        Browser tidak mendukung pemutaran video.
                                    </video>
                                    <div class="p-1 bg-dark text-center">
                                        <a href="{{ $videoUrl }}" target="_blank" class="text-white small text-decoration-none">
                                            <i class="ti ti-external-link"></i> Buka / Unduh Video
                                        </a>
                                    </div>
                                </div>
                            @elseif($media->type === 'AUDIO')
                                @php
                                    $audioUrl = str_starts_with($media->disk_path, 'http') || str_starts_with($media->disk_path, 'data:')
                                        ? $media->disk_path 
                                        : asset('storage/' . $media->disk_path);
                                @endphp
                                <div class="p-2 border rounded-3 bg-light d-flex align-items-center gap-2" style="min-width: 220px;">
                                    <i class="ti ti-microphone text-primary fs-3"></i>
                                    <audio controls class="w-100" style="height: 32px;">
                                        <source src="{{ $audioUrl }}" type="{{ $media->mime ?: 'audio/mp4' }}">
                                    </audio>
                                </div>
                            @else
                                @php
                                    $imgUrl = str_starts_with($media->disk_path, 'http') || str_starts_with($media->disk_path, 'data:')
                                        ? $media->disk_path 
                                        : asset('storage/' . $media->disk_path);
                                @endphp
                                <a href="{{ $imgUrl }}" target="_blank">
                                    <img src="{{ $imgUrl }}" class="rounded-3 border shadow-sm" style="width: 120px; height: 120px; object-fit: cover;">
                                </a>
                            @endif
                        @empty
                            <p class="text-muted small mb-0">Tidak ada lampiran foto/video/suara.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        {{-- Peta Lokasi Insiden --}}
        <div class="card card-lg shadow-sm border-0 mb-4">
            <div class="card-header bg-transparent border-bottom-0 pt-4">
                <h5 class="fw-bold mb-0"><i class="ti ti-map-pin me-2 text-primary"></i>Titik Koordinat Insiden</h5>
            </div>
            <div class="card-body">
                <div id="detail-map"></div>
            </div>
        </div>
    </div>

    {{-- Kolom Kanan: Rekomendasi Dispatch & Status Timeline --}}
    <div class="col-xl-5 col-lg-6">
        {{-- Hasil AI Analysis --}}
        <div class="card card-lg shadow-sm border-0 mb-4">
            <div class="card-header bg-transparent border-bottom-0 pt-4 d-flex justify-content-between align-items-center">
                <h5 class="fw-bold mb-0"><i class="ti ti-sparkles me-2 text-warning"></i>Analisis Decision Intelligence AI</h5>
                <span class="badge bg-{{ $incident->ai_status === 'DONE' ? 'success' : 'secondary' }}">
                    Status AI: {{ $incident->ai_status === 'DONE' ? 'Selesai Dianalisis' : 'Sedang Memproses' }}
                </span>
            </div>
            <div class="card-body">
                @if($incident->aiAnalysis)
                    @php
                        $rawResp = is_array($incident->aiAnalysis->raw_response) ? $incident->aiAnalysis->raw_response : [];
                        $candidateText = $rawResp['candidates'][0]['content']['parts'][0]['text'] ?? null;
                        $parsedAi = $candidateText ? json_decode($candidateText, true) : [];
                        $mediaAnalysis = $incident->aiAnalysis->media_analysis ?: ($parsedAi['media_analysis'] ?? null);
                        $incidentDetail = $incident->aiAnalysis->incident_detail ?: ($parsedAi['incident_detail'] ?? null);
                        $riskAnalysis = $incident->aiAnalysis->risk_analysis ?: ($parsedAi['risk_analysis'] ?? null);
                        $detectedLoc = $parsedAi['detected_location'] ?? null;
                        
                        // Jika operator sudah memverifikasi / meng-override kejadian, maka status tidak lagi dianggap invalid
                        $isOverriddenByOperator = $incident->severity_source === 'OPERATOR' || $incident->status === 'VERIFIED';
                        $isInvalidReport = !$isOverriddenByOperator && ($incident->aiAnalysis->category === 'UNKNOWN' || $incident->aiAnalysis->confidence < 0.4 || str_contains(strtolower($incident->aiAnalysis->summary ?? ''), 'tidak valid') || str_contains(strtolower($mediaAnalysis ?? ''), 'tidak valid'));
                    @endphp

                    {{-- Indikasi Laporan Tidak Valid / Potensi Hoax dari AI --}}
                    @if($isInvalidReport)
                        <div class="alert alert-danger d-flex align-items-center mb-3 p-3 border-danger shadow-sm rounded-3">
                            <i class="ti ti-alert-triangle fs-2 me-3 text-danger"></i>
                            <div>
                                <h6 class="fw-bold text-danger mb-1">Rekomendasi AI: Laporan Berpotensi Tidak Valid / Palsu</h6>
                                <p class="small mb-0 text-dark">
                                    AI mendeteksi bukti media atau informasi laporan tidak menunjukkan insiden darurat nyata. 
                                    <b>Disarankan untuk Menolak Laporan</b> guna mencegah pengerahan armada yang tidak perlu.
                                </p>
                            </div>
                        </div>
                    @endif

                    {{-- 1. Ringkasan Eksekutif --}}
                    <div class="mb-3">
                        <span class="text-muted small fw-semibold"><i class="ti ti-file-text me-1 text-primary"></i>RINGKASAN & ANALISIS SITUASI:</span>
                        <div class="p-3 bg-light rounded-3 mt-1 text-dark border">
                            <p class="mb-0 fw-medium" style="line-height: 1.6;">{{ $incident->aiAnalysis->summary }}</p>
                        </div>
                    </div>

                    {{-- 2. Detail Analisis Bukti Media (Foto / Video / Suara) --}}
                    @if($mediaAnalysis)
                        <div class="mb-3">
                            <span class="text-muted small fw-semibold text-info"><i class="ti ti-video me-1"></i>HASIL ANALISIS MEDIA (FOTO/VIDEO/SUARA):</span>
                            <div class="p-3 bg-info-subtle border border-info-subtle rounded-3 mt-1 text-dark">
                                <p class="mb-0 small fw-medium" style="line-height: 1.5;">{{ $mediaAnalysis }}</p>
                            </div>
                        </div>
                    @endif

                    {{-- 3. Detail Kejadian & Potensi Bahaya --}}
                    @if($incidentDetail || $riskAnalysis)
                        <div class="row g-2 mb-3">
                            @if($incidentDetail)
                                <div class="col-12">
                                    <span class="text-muted small fw-semibold"><i class="ti ti-notes me-1"></i>DETAIL KRONOLOGI / KONDISI:</span>
                                    <div class="p-2 bg-light rounded-2 mt-1 small text-dark border">
                                        {{ $incidentDetail }}
                                    </div>
                                </div>
                            @endif
                            @if($riskAnalysis)
                                <div class="col-12">
                                    <span class="text-muted small fw-semibold text-danger"><i class="ti ti-alert-triangle me-1"></i>ANALISIS POTENSI RISIKO LANJUTAN:</span>
                                    <div class="p-2 bg-danger-subtle border border-danger-subtle rounded-2 mt-1 small text-dark">
                                        {{ $riskAnalysis }}
                                    </div>
                                </div>
                            @endif
                        </div>
                    @endif

                    {{-- 4. Deteksi Alamat Spesifik Verbal/Visual --}}
                    @if($detectedLoc)
                        <div class="mb-3">
                            <span class="text-muted small fw-semibold text-success"><i class="ti ti-map-pin me-1"></i>ALAMAT TERDETEKSI DARI REKAMAN/MEDIA:</span>
                            <div class="p-2 bg-success-subtle border border-success-subtle rounded-2 mt-1 small text-success-emphasis fw-semibold">
                                {{ $detectedLoc }}
                            </div>
                        </div>
                    @endif

                    {{-- 5. Rekomendasi Armada Yang Wajib Dikerahkan --}}
                    @if(!$isInvalidReport && is_array($incident->aiAnalysis->required_units) && count($incident->aiAnalysis->required_units) > 0)
                        <div class="mb-3">
                            <span class="text-muted small fw-semibold"><i class="ti ti-truck me-1"></i>REKOMENDASI ARMADA YANG WAJIB DIKERAHKAN:</span>
                            <div class="d-flex gap-2 flex-wrap mt-1">
                                @foreach($incident->aiAnalysis->required_units as $unitType)
                                    @php
                                        $unitName = match($unitType) {
                                            'ambulance' => 'Ambulans Medis',
                                            'fire_truck' => 'Mobil Pemadam Kebakaran',
                                            'police_patrol' => 'Patroli Kepolisian',
                                            'rescue_team' => 'Tim SAR / Penyelamat',
                                            'traffic_unit' => 'Unit Lalu Lintas',
                                            default => str_replace('_', ' ', $unitType),
                                        };
                                    @endphp
                                    <span class="badge bg-primary text-white px-2 py-1">
                                        <i class="ti ti-truck me-1"></i>{{ $unitName }}
                                    </span>
                                @endforeach
                                @if($incident->aiAnalysis->critical_victim)
                                    <span class="badge bg-danger text-white px-2 py-1">
                                        <i class="ti ti-alert-circle me-1"></i>Korban Kritis Terdeteksi
                                    </span>
                                @endif
                            </div>
                        </div>
                    @elseif($isInvalidReport)
                        <div class="mb-3">
                            <span class="text-muted small fw-semibold"><i class="ti ti-truck-off me-1 text-danger"></i>REKOMENDASI ARMADA:</span>
                            <div class="p-2 bg-danger-subtle border border-danger-subtle rounded-2 mt-1 small text-danger fw-semibold">
                                <i class="ti ti-ban me-1"></i> Tidak ada pengerahan armada yang disarankan (Laporan Tidak Valid).
                            </div>
                        </div>
                    @endif

                    {{-- 6. Detail Waktu, Jarak Tempuh, & Metadata --}}
                    @php
                        $nearestRec = $incident->dispatchRecommendations->sortBy('rank_no')->first();
                    @endphp
                    @if(!$isInvalidReport && $nearestRec)
                        <div class="p-2 bg-primary-subtle border border-primary-subtle rounded-3 mb-3 d-flex justify-content-between align-items-center">
                            <div>
                                <small class="text-primary fw-bold d-block"><i class="ti ti-navigation me-1"></i>ESTIMASI ARMADA TERDEKAT ({{ $nearestRec->unit?->code }}):</small>
                                <span class="small text-dark">Jarak: <b>{{ number_format($nearestRec->distance_m / 1000, 1) }} km</b> | Waktu Tempuh: <b>~{{ ceil($nearestRec->eta_seconds / 60) }} menit</b></span>
                            </div>
                            <span class="badge bg-primary">Skor: {{ $nearestRec->score }}</span>
                        </div>
                    @endif

                    <div class="d-flex gap-2 pt-2 border-top flex-wrap align-items-center">
                        <span class="badge bg-info-subtle text-info">Tingkat Keyakinan: {{ number_format($incident->aiAnalysis->confidence * 100, 1) }}%</span>
                        @if($incident->aiAnalysis->first_aid_key)
                            <span class="badge bg-warning-subtle text-warning">Pertolongan Pertama: {{ strtoupper($incident->aiAnalysis->first_aid_key) }}</span>
                        @endif
                        @if($incident->aiAnalysis->latency_ms)
                            <small class="text-muted ms-auto align-self-center">{{ $incident->aiAnalysis->latency_ms }} ms</small>
                        @endif
                    </div>
                @else
                    <p class="text-muted small mb-0">Menunggu hasil klasifikasi AI...</p>
                @endif
            </div>
        </div>

        {{-- Panel Rekomendasi Dispatch & Penugasan Unit --}}
        <div class="card card-lg shadow-sm border-0 mb-4">
            <div class="card-header bg-transparent border-bottom-0 pt-4 d-flex justify-content-between align-items-center">
                <h5 class="fw-bold mb-0"><i class="ti ti-truck-delivery me-2 text-primary"></i>Rekomendasi Armada Terdekat</h5>
                <span class="badge bg-{{ $isInvalidReport ? 'danger-subtle text-danger' : 'primary-subtle text-primary' }}">
                    {{ $isInvalidReport ? 'Pengerahan Ditangguhkan' : 'Sistem Penugasan Otomatis' }}
                </span>
            </div>
            <div class="card-body">
                @if($isInvalidReport)
                    <div class="alert alert-danger mb-0 text-center py-4 rounded-3">
                        <i class="ti ti-ban fs-1 text-danger d-block mb-2"></i>
                        <h6 class="fw-bold text-danger mb-1">Rekomendasi Armada Otomatis Dinonaktifkan</h6>
                        <p class="small text-muted mb-0">
                            AI mengidentifikasi laporan ini sebagai laporan tidak valid / palsu. 
                            Silakan lakukan verifikasi manual atau gunakan tombol <b>Tolak Laporan</b>.
                        </p>
                    </div>
                @elseif($incident->dispatchRecommendations->isNotEmpty())
                    <div class="list-group list-group-flush mb-3">
                        @foreach($incident->dispatchRecommendations->sortBy('rank_no') as $rec)
                            <div class="list-group-item d-flex justify-content-between align-items-center px-0 py-3 {{ $rec->chosen ? 'bg-success-subtle px-3 rounded-3' : '' }}">
                                <div>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="badge bg-dark">Peringkat #{{ $rec->rank_no }}</span>
                                        <span class="fw-bold fs-6">{{ $rec->unit->code }}</span>
                                        <span class="badge bg-secondary-subtle text-secondary small">{{ $rec->unit->type }}</span>
                                        @if($rec->chosen)
                                            <span class="badge bg-success">DITUGASKAN</span>
                                        @endif
                                    </div>
                                    <div class="text-muted small mt-1">
                                        <span><i class="ti ti-map-pin me-1"></i>{{ number_format($rec->distance_m / 1000, 1) }} km</span>
                                        <span class="ms-2"><i class="ti ti-clock me-1"></i>Estimasi Waktu ~{{ ceil($rec->eta_seconds / 60) }} menit</span>
                                        <span class="ms-2 text-primary fw-semibold">Skor Efisiensi: {{ $rec->score }}</span>
                                    </div>
                                </div>
                                @if(!$rec->chosen && !in_array($incident->status, ['CLOSED', 'RESOLVED', 'FALSE_REPORT', 'DUPLICATE', 'CANCELLED', 'REJECTED']))
                                    <form action="{{ route('command-center.dispatch', $incident->ulid) }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="unit_id" value="{{ $rec->unit_id }}">
                                        <button type="submit" class="btn btn-sm btn-primary">
                                            <i class="ti ti-send me-1"></i> Kerahkan Armada
                                        </button>
                                    </form>
                                @endif
                            </div>
                        @endforeach
                    </div>

                    {{-- Opsi Kerahkan Armada Tambahan Secara Manual --}}
                    @if($availableUnits->isNotEmpty() && !in_array($incident->status, ['CLOSED', 'RESOLVED', 'FALSE_REPORT', 'DUPLICATE', 'CANCELLED', 'REJECTED']))
                        <div class="mt-3 pt-3 border-top">
                            <label class="form-label small fw-semibold text-muted mb-1">
                                <i class="ti ti-plus me-1 text-primary"></i>Kerahkan Armada Tambahan (Multi-Unit):
                            </label>
                            <form action="{{ route('command-center.dispatch', $incident->ulid) }}" method="POST" class="d-flex gap-2">
                                @csrf
                                <select name="unit_id" class="form-select form-select-sm" required>
                                    <option value="">-- Pilih Unit Tambahan --</option>
                                    @foreach($availableUnits as $unit)
                                        <option value="{{ $unit->id }}">{{ $unit->code }} ({{ $unit->type }}) - {{ $unit->agency->name }}</option>
                                    @endforeach
                                </select>
                                <button type="submit" class="btn btn-sm btn-outline-primary text-nowrap">
                                    <i class="ti ti-send me-1"></i> Kerahkan
                                </button>
                            </form>
                        </div>
                    @endif
                @else
                    <div class="text-center py-3 text-muted">
                        <p class="small mb-2">Belum ada skor rekomendasi unit terdekat.</p>
                        @if($availableUnits->isNotEmpty() && !in_array($incident->status, ['CLOSED', 'RESOLVED', 'FALSE_REPORT', 'DUPLICATE', 'CANCELLED', 'REJECTED']))
                            <form action="{{ route('command-center.dispatch', $incident->ulid) }}" method="POST" class="d-flex gap-2">
                                @csrf
                                <select name="unit_id" class="form-select form-select-sm" required>
                                    <option value="">-- Pilih Unit Manual --</option>
                                    @foreach($availableUnits as $unit)
                                        <option value="{{ $unit->id }}">{{ $unit->code }} ({{ $unit->type }}) - {{ $unit->agency->name }}</option>
                                    @endforeach
                                </select>
                                <button type="submit" class="btn btn-sm btn-primary text-nowrap">Kerahkan Manual</button>
                            </form>
                        @endif
                    </div>
                @endif
            </div>
        </div>

        {{-- Timeline Status Log --}}
        <div class="card card-lg shadow-sm border-0">
            <div class="card-header bg-transparent border-bottom-0 pt-4 d-flex justify-content-between align-items-center">
                <h5 class="fw-bold mb-0"><i class="ti ti-history me-2 text-primary"></i>Riwayat & Jejak Audit Status</h5>
                <span class="badge bg-success-subtle text-success small" id="live-indicator">
                    <i class="ti ti-circle-filled text-success me-1 fs-xs"></i> Live
                </span>
            </div>
            <div class="card-body">
                <ul class="list-unstyled mb-0" id="timeline-log-list">
                    @forelse($incident->statusLogs->sortByDesc('occurred_at') as $log)
                        @php
                            $statusTitle = match($log->to_status) {
                                'NEW'         => 'Laporan Diterima',
                                'VERIFIED'    => 'Insiden Terverifikasi',
                                'DISPATCHED'  => 'Armada Ditugaskan Menuju Lokasi',
                                'ACCEPTED'    => 'Petugas Menerima Tugas',
                                'EN_ROUTE'    => 'Petugas Menuju Lokasi (Dalam Perjalanan)',
                                'ARRIVED'     => 'Petugas Tiba di Lokasi (TKP)',
                                'HANDLING'    => 'Tindakan Penanganan di Lokasi',
                                'TRANSFERRED' => 'Rujukan / Transfer ke Rumah Sakit',
                                'RESOLVED'    => 'Penanganan Lapangan Selesai',
                                'CLOSED'      => 'Insiden Ditutup Resmi',
                                'FALSE_REPORT'=> 'Ditolak: Laporan Palsu / Hoax',
                                'DUPLICATE'   => 'Dibatalkan: Laporan Duplikat',
                                'CANCELLED'   => 'Laporan Dibatalkan',
                                'REJECTED'    => 'Laporan Ditolak',
                                default       => str_replace('_', ' ', $log->to_status),
                            };
                        @endphp
                        <li class="d-flex gap-3 mb-3 timeline-item" data-log-id="{{ $log->id }}">
                            <div class="icon-shape icon-sm rounded-circle bg-primary-subtle text-primary mt-1">
                                <i class="ti ti-circle-dot"></i>
                            </div>
                            <div>
                                <span class="fw-bold text-dark">{{ $statusTitle }}</span>
                                <p class="text-muted small mb-0">{{ $log->note }}</p>
                                <small class="text-secondary">{{ $log->occurred_at?->format('H:i:s, d M Y') }}</small>
                            </div>
                        </li>
                    @empty
                        <li class="text-muted small">Belum ada riwayat status.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
</div>

{{-- Modal Verifikasi & Koreksi Kategori Operator (Human Override) --}}
<div class="modal fade" id="verifyModal" tabindex="-1" aria-labelledby="verifyModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form action="{{ route('command-center.verify', $incident->ulid) }}" method="POST">
                @csrf
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title fw-bold" id="verifyModalLabel">
                        <i class="ti ti-shield-check me-2"></i>Verifikasi & Koreksi Data Insiden (Human Override)
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <p class="text-muted small mb-3">
                        Sebagai operator Command Center, Anda memegang keputusan final. Tentukan kategori kejadian dan tingkat keparahan yang sebenarnya. Sistem akan otomatis menghitung rekomendasi armada terdekat sesuai penetapan Anda.
                    </p>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Kategori Kejadian Nyata <span class="text-danger">*</span></label>
                        <select name="category" class="form-select" required>
                            <option value="FIRE" {{ $incident->category === 'FIRE' ? 'selected' : '' }}>🔥 Kebakaran (FIRE) - Damkar & Ambulans</option>
                            <option value="TRAFFIC" {{ $incident->category === 'TRAFFIC' ? 'selected' : '' }}>🚗 Kecelakaan Lalu Lintas (TRAFFIC) - Ambulans & Polisi</option>
                            <option value="MEDICAL" {{ $incident->category === 'MEDICAL' ? 'selected' : '' }}>🚑 Darurat Medis (MEDICAL) - Ambulans</option>
                            <option value="DISASTER" {{ $incident->category === 'DISASTER' ? 'selected' : '' }}>🌊 Bencana Alam (DISASTER) - Tim SAR & Ambulans</option>
                            <option value="SECURITY" {{ $incident->category === 'SECURITY' ? 'selected' : '' }}>🛡️ Kamtibmas / Kriminal (SECURITY) - Polisi</option>
                            <option value="UNKNOWN" {{ $incident->category === 'UNKNOWN' ? 'selected' : '' }}>❓ Belum Diketahui / Lainnya</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Tingkat Keparahan (Severity) <span class="text-danger">*</span></label>
                        <select name="severity" class="form-select" required>
                            <option value="1" {{ $incident->severity == 1 ? 'selected' : '' }}>Level 1 - Ringan / Non-Kritis</option>
                            <option value="2" {{ $incident->severity == 2 ? 'selected' : '' }}>Level 2 - Sedang (Butuh Bantuan)</option>
                            <option value="3" {{ ($incident->severity == 3 || !$incident->severity) ? 'selected' : '' }}>Level 3 - Berat / Kedaruratan Tinggi</option>
                            <option value="4" {{ $incident->severity == 4 ? 'selected' : '' }}>Level 4 - Kritis / Mengancam Jiwa</option>
                            <option value="5" {{ $incident->severity == 5 ? 'selected' : '' }}>Level 5 - Bencana / Korban Massal</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Catatan Verifikasi Operator (Opsional)</label>
                        <textarea name="note" rows="2" class="form-control" placeholder="Contoh: Laporan dikonfirmasi valid via telepon warga / pemantauan CCTV."></textarea>
                    </div>

                    <div class="alert alert-info small mb-0 d-flex align-items-center">
                        <i class="ti ti-info-circle fs-4 me-2 text-info"></i>
                        <span>Setelah diverifikasi, sistem akan langsung mengaktifkan rekomendasi armada terdekat sesuai kategori yang dipilih.</span>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning fw-bold">
                        <i class="ti ti-check me-1"></i> Simpan & Verifikasi Insiden
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Modal Konfirmasi Tolak / Batalkan Laporan --}}
<div class="modal fade" id="rejectModal" tabindex="-1" aria-labelledby="rejectModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form action="{{ route('command-center.reject', $incident->ulid) }}" method="POST">
                @csrf
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title fw-bold text-white" id="rejectModalLabel">
                        <i class="ti ti-ban me-2"></i>Tolak / Batalkan Laporan Insiden
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <p class="text-muted small mb-3">
                        Pilih kategori penolakan dan berikan catatan alasan untuk keperluan riwayat jejak audit (audit log).
                    </p>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Alasan Penolakan <span class="text-danger">*</span></label>
                        <select name="reason_type" class="form-select" required>
                            <option value="FALSE_REPORT" selected>Laporan Palsu / Hoax / Foto Tidak Relevan</option>
                            <option value="DUPLICATE">Laporan Duplikat (Sudah Dilaporkan Sebelumnya)</option>
                            <option value="CANCELLED">Dibatalkan (Permintaan Pelapor / Tidak Ditemukan Kejadian)</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Catatan Tambahan Operator (Opsional)</label>
                        <textarea name="reason_note" rows="3" class="form-control" placeholder="Contoh: Bukti foto merupakan tangkapan layar berita lama / tidak ada insiden aktual di lokasi."></textarea>
                    </div>

                    <div class="alert alert-warning small mb-0 d-flex align-items-center">
                        <i class="ti ti-alert-triangle fs-4 me-2"></i>
                        <span>Insiden ini akan ditutup & tidak akan ada armada yang ditugaskan ke lokasi.</span>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kembali</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="ti ti-ban me-1"></i> Konfirmasi Tolak Laporan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const lat = {{ $incident->lat }};
        const lng = {{ $incident->lng }};

        const map = L.map('detail-map').setView([lat, lng], 15);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);

        L.circleMarker([lat, lng], {
            radius: 10,
            fillColor: '#dc3545',
            color: '#fff',
            weight: 3,
            fillOpacity: 0.95
        }).addTo(map).bindPopup('<b>Lokasi Insiden:</b><br>{{ $incident->incident_no }}').openPopup();

        // Auto reload jika AI masih memproses agar otomatis terupdate saat analisis selesai
        @if($incident->ai_status !== 'DONE')
            setTimeout(function() {
                window.location.reload();
            }, 4000);
        @endif

        // Realtime Polling Timeline & Status Log Setiap 3 Detik
        const dataUrl = "{{ route('command-center.detail-data', $incident->ulid) }}";
        let lastLogsCount = {{ $incident->statusLogs->count() }};

        function fetchLiveStatus() {
            fetch(dataUrl, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            })
            .then(res => res.json())
            .then(data => {
                if (!data.success) return;

                // 1. Update Timeline Log jika ada data baru
                if (data.logs && data.logs.length !== lastLogsCount) {
                    lastLogsCount = data.logs.length;
                    const container = document.getElementById('timeline-log-list');
                    if (container) {
                        let html = '';
                        data.logs.forEach(log => {
                            html += `
                                <li class="d-flex gap-3 mb-3 timeline-item animate__animated animate__fadeIn" data-log-id="${log.id}">
                                    <div class="icon-shape icon-sm rounded-circle bg-primary-subtle text-primary mt-1">
                                        <i class="ti ti-circle-dot"></i>
                                    </div>
                                    <div>
                                        <span class="fw-bold text-dark">${log.status_title}</span>
                                        <p class="text-muted small mb-0">${log.note || '-'}</p>
                                        <small class="text-secondary">${log.time}</small>
                                    </div>
                                </li>
                            `;
                        });
                        container.innerHTML = html;
                    }
                }
            })
            .catch(err => console.debug('Polling detail error:', err));
        }

        // Jalankan polling setiap 3 detik
        setInterval(fetchLiveStatus, 3000);
    });
</script>
@endpush
