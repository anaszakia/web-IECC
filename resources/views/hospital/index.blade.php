@extends('layouts.app')

@section('title', 'Hospital Emergency Portal - IECC')

@section('content')
<div class="row g-4 mb-4">
    <div class="col-12 d-flex justify-content-between align-items-center">
        <div>
            <h3 class="mb-1 fw-bold"><i class="ti ti-building-hospital me-2 text-primary"></i>Hospital Pre-Arrival Portal</h3>
            <p class="text-muted mb-0">{{ $hospital ? $hospital->name : 'Portal IGD Rumah Sakit' }}</p>
        </div>
        <div>
            <span class="badge bg-{{ $hospital?->er_status === 'NORMAL' ? 'success' : ($hospital?->er_status === 'BUSY' ? 'warning' : 'danger') }} fs-6 px-3 py-2">
                Status IGD: {{ $hospital?->er_status ?? 'NORMAL' }}
            </span>
        </div>
    </div>

    {{-- Kartu Kapasitas IGD --}}
    <div class="col-xl-4 col-md-6">
        <div class="card card-lg shadow-sm border-0">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="text-muted small fw-semibold">BED IGD TERSEDIA</span>
                    <span class="badge bg-primary-subtle text-primary">Kapasitas</span>
                </div>
                <div class="d-flex align-items-baseline gap-2">
                    <h2 class="fw-bold mb-0 text-primary">{{ $hospital?->er_beds_available ?? 0 }}</h2>
                    <span class="text-muted">/ {{ $hospital?->er_beds_total ?? 0 }} Total Bed</span>
                </div>
                <div class="progress mt-3" style="height: 8px;">
                    @php
                        $percentage = $hospital && $hospital->er_beds_total > 0 ? round(($hospital->er_beds_available / $hospital->er_beds_total) * 100) : 0;
                    @endphp
                    <div class="progress-bar bg-success" style="width: {{ $percentage }}%"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-4 col-md-6">
        <div class="card card-lg shadow-sm border-0">
            <div class="card-body">
                <span class="text-muted small fw-semibold">PASIEN MASUK (INCOMING)</span>
                <div class="d-flex align-items-baseline gap-2 mt-2">
                    <h2 class="fw-bold mb-0 text-danger">{{ $incomingPatients->count() }}</h2>
                    <span class="text-muted">Dalam Perjalanan Ambulans</span>
                </div>
                <p class="text-secondary small mt-2 mb-0">Pre-Arrival notification terdeteksi dari armada lapangan.</p>
            </div>
        </div>
    </div>

    {{-- Form Cepat Update Kapasitas --}}
    <div class="col-xl-4 col-md-12">
        <div class="card card-lg shadow-sm border-0">
            <div class="card-header bg-transparent border-bottom-0 pb-0 pt-3">
                <h6 class="fw-bold mb-0">Update Cepat Kapasitas IGD</h6>
            </div>
            <div class="card-body pt-2">
                @if($hospital)
                    <form action="{{ route('hospital.capacity.update', $hospital->ulid) }}" method="POST" class="row g-2 align-items-center">
                        @csrf
                        <div class="col-4">
                            <label class="small text-muted mb-1">Total</label>
                            <input type="number" name="er_beds_total" class="form-control form-control-sm" value="{{ $hospital->er_beds_total }}" required>
                        </div>
                        <div class="col-4">
                            <label class="small text-muted mb-1">Sedia</label>
                            <input type="number" name="er_beds_available" class="form-control form-control-sm" value="{{ $hospital->er_beds_available }}" required>
                        </div>
                        <div class="col-4">
                            <label class="small text-muted mb-1">Status</label>
                            <select name="er_status" class="form-select form-select-sm">
                                <option value="NORMAL" {{ $hospital->er_status === 'NORMAL' ? 'selected' : '' }}>NORMAL</option>
                                <option value="BUSY" {{ $hospital->er_status === 'BUSY' ? 'selected' : '' }}>BUSY</option>
                                <option value="FULL" {{ $hospital->er_status === 'FULL' ? 'selected' : '' }}>FULL</option>
                            </select>
                        </div>
                        <div class="col-12 mt-2">
                            <button type="submit" class="btn btn-sm btn-outline-primary w-100 py-1">Simpan Perubahan</button>
                        </div>
                    </form>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- Tabel Pasien Dalam Perjalanan (Incoming Patients) --}}
<div class="row g-4 mb-4">
    <div class="col-12">
        <div class="card card-lg shadow-sm border-0">
            <div class="card-header bg-transparent border-bottom-0 d-flex justify-content-between align-items-center py-3">
                <h5 class="fw-bold mb-0 text-danger"><i class="ti ti-ambulance me-2"></i>Pasien Darurat Masuk (Pre-Arrival List)</h5>
                <span class="badge bg-danger pulse-animation">{{ $incomingPatients->count() }} Pasien Incoming</span>
            </div>
            <div class="table-responsive">
                <table class="table table-hover table-centered mb-0 text-nowrap">
                    <thead class="table-light">
                        <tr>
                            <th>No. Insiden</th>
                            <th>Unit Ambulans</th>
                            <th>Kondisi Pasien</th>
                            <th>Kesadaran (AVPU)</th>
                            <th>Estimasi Umur / JK</th>
                            <th>Waktu Notifikasi / ETA</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($incomingPatients as $p)
                            <tr>
                                <td>
                                    <span class="fw-bold text-dark">{{ $p->incident->incident_no }}</span><br>
                                    <span class="badge bg-danger-subtle text-danger small">Level {{ $p->incident->severity ?? 'Urgent' }}</span>
                                </td>
                                <td>
                                    <span class="fw-semibold">{{ $p->assignment->unit?->code }}</span><br>
                                    <small class="text-muted">{{ $p->assignment->agency?->name }}</small>
                                </td>
                                <td style="white-space: normal; max-width: 250px;">
                                    <span class="text-dark small">{{ $p->condition_text ?: 'Tidak ada catatan kondisi spesifik.' }}</span>
                                </td>
                                <td>
                                    <span class="badge bg-{{ $p->consciousness === 'ALERT' ? 'success' : ($p->consciousness === 'UNRESPONSIVE' ? 'danger' : 'warning') }}">
                                        {{ $p->consciousness }}
                                    </span>
                                </td>
                                <td>{{ $p->age_estimate ? $p->age_estimate . ' Thn' : 'N/A' }} ({{ $p->gender }})</td>
                                <td>
                                    <span class="fw-semibold text-primary">~{{ $p->eta_seconds ? ceil($p->eta_seconds / 60) . ' mnt' : 'Segera Tiba' }}</span><br>
                                    <small class="text-muted">{{ $p->notified_at?->diffForHumans() }}</small>
                                </td>
                                <td>
                                    <form action="{{ route('hospital.received', $p->ulid) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-success">
                                            <i class="ti ti-check me-1"></i> Konfirmasi RECEIVED
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">
                                    <i class="ti ti-circle-check fs-1 text-success mb-2"></i>
                                    <p>Tidak ada pasien darurat yang sedang dalam perjalanan menuju IGD saat ini.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- Riwayat Pasien Diterima --}}
<div class="row g-4">
    <div class="col-12">
        <div class="card card-lg shadow-sm border-0">
            <div class="card-header bg-transparent border-bottom-0 py-3">
                <h5 class="fw-bold mb-0 text-muted"><i class="ti ti-history me-2"></i>Riwayat Pasien Diterima Hari Ini</h5>
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-hover table-centered mb-0 text-nowrap">
                    <thead class="table-light">
                        <tr>
                            <th>No. Insiden</th>
                            <th>Unit Ambulans</th>
                            <th>Kondisi Pasien</th>
                            <th>Diterima Oleh</th>
                            <th>Waktu Masuk IGD</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($receivedHistory as $h)
                            <tr>
                                <td class="fw-semibold">{{ $h->incident->incident_no }}</td>
                                <td>{{ $h->assignment->unit?->code }}</td>
                                <td style="white-space: normal; max-width: 250px;">{{ $h->condition_text }}</td>
                                <td>{{ $h->receiver?->name ?? 'Petugas IGD' }}</td>
                                <td>{{ $h->received_at?->format('H:i:s, d M Y') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-3 text-muted">Belum ada riwayat pasien yang diterima hari ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
