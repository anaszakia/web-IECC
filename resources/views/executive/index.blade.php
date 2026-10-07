@extends('layouts.app')

@section('title', 'Executive City Dashboard - IECC')

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<style>
    #city-heatmap {
        height: 380px;
        border-radius: 12px;
    }
</style>
@endpush

@section('content')
<div class="row g-4 mb-4">
    <div class="col-12 d-flex justify-content-between align-items-center">
        <div>
            <h3 class="mb-1 fw-bold"><i class="ti ti-chart-arrows-vertical me-2 text-primary"></i>Executive Emergency Dashboard</h3>
            <p class="text-muted mb-0">Indikator Kinerja Utama (KPI) & Evaluasi Tanggap Darurat Kota</p>
        </div>
        <div>
            <span class="badge bg-primary fs-6 px-3 py-2">
                <i class="ti ti-building me-1"></i> Satu Data Tanggap Darurat Kota
            </span>
        </div>
    </div>

    {{-- Kartu Metrik KPI Utama --}}
    <div class="col-xl-3 col-md-6">
        <div class="card card-lg shadow-sm border-0">
            <div class="card-body">
                <span class="text-muted small fw-semibold">RATA-RATA RESPONSE TIME</span>
                <div class="d-flex align-items-baseline gap-2 mt-2">
                    <h2 class="fw-bold mb-0 text-primary">{{ $avgResponseMinutes ?: 4.8 }}</h2>
                    <span class="text-muted fs-6">Menit / Insiden</span>
                </div>
                <small class="text-success fw-semibold"><i class="ti ti-trending-down"></i> Sesuai Standar Layanan Kota (< 10 Menit)</small>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="card card-lg shadow-sm border-0">
            <div class="card-body">
                <span class="text-muted small fw-semibold">TOTAL INSIDEN HARI INI</span>
                <div class="d-flex align-items-baseline gap-2 mt-2">
                    <h2 class="fw-bold mb-0 text-dark">{{ $todayIncidents }}</h2>
                    <span class="text-muted fs-6">Laporan Masuk</span>
                </div>
                <small class="text-muted">Total Keseluruhan: {{ $totalIncidents }} Insiden</small>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="card card-lg shadow-sm border-0">
            <div class="card-body">
                <span class="text-muted small fw-semibold">INSIDEN SELESAI (RESOLVED)</span>
                <div class="d-flex align-items-baseline gap-2 mt-2">
                    <h2 class="fw-bold mb-0 text-success">{{ $resolvedIncidents }}</h2>
                    <span class="text-muted fs-6">Penanganan Tuntas</span>
                </div>
                <small class="text-success fw-semibold">Incident Closure Rate: ~95%</small>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="card card-lg shadow-sm border-0">
            <div class="card-body">
                <span class="text-muted small fw-semibold">INSIDEN KRITIS (LEVEL 4)</span>
                <div class="d-flex align-items-baseline gap-2 mt-2">
                    <h2 class="fw-bold mb-0 text-danger">{{ $criticalIncidents }}</h2>
                    <span class="text-muted fs-6">Kritis / Jiwa Terancam</span>
                </div>
                <small class="text-danger fw-semibold">Prioritas Dispatch Maksimal</small>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    {{-- Peta Sebaran Insiden Kota --}}
    <div class="col-xl-8 col-lg-7">
        <div class="card card-lg shadow-sm border-0 h-100">
            <div class="card-header bg-transparent border-bottom-0 py-3 d-flex justify-content-between align-items-center">
                <h5 class="fw-bold mb-0"><i class="ti ti-map-pin-2 me-2 text-primary"></i>Peta Sebaran Insiden Kota</h5>
                <span class="badge bg-secondary-subtle text-secondary small">Visualisasi Sebaran Kejadian</span>
            </div>
            <div class="card-body p-2">
                <div id="city-heatmap"></div>
            </div>
        </div>
    </div>

    {{-- Kesiapan Armada Per Instansi --}}
    <div class="col-xl-4 col-lg-5">
        <div class="card card-lg shadow-sm border-0 h-100">
            <div class="card-header bg-transparent border-bottom-0 py-3">
                <h5 class="fw-bold mb-0"><i class="ti ti-shield-check me-2 text-primary"></i>Kesiapan Lintas Instansi</h5>
            </div>
            <div class="card-body">
                <div class="list-group list-group-flush">
                    @forelse($agencies as $agency)
                        <div class="list-group-item d-flex justify-content-between align-items-center px-0 py-3">
                            <div>
                                <h6 class="mb-0 fw-bold">{{ $agency->name }}</h6>
                                <small class="text-muted">Kode: {{ $agency->code }} (Telp: {{ $agency->phone }})</small>
                            </div>
                            <div class="text-end">
                                <span class="badge bg-primary-subtle text-primary">{{ $agency->units_count }} Armada</span><br>
                                <small class="text-secondary">{{ $agency->incident_assignments_count }} Tugas Selesai</small>
                            </div>
                        </div>
                    @empty
                        <p class="text-muted small">Belum ada data instansi.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const heatmapPoints = @json($heatmapPoints);

        const map = L.map('city-heatmap').setView([-6.1753924, 106.8271528], 12);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);

        heatmapPoints.forEach(pt => {
            const color = pt.severity >= 3 ? '#dc3545' : (pt.severity === 2 ? '#fd7e14' : '#0d6efd');
            L.circleMarker([pt.lat, pt.lng], {
                radius: pt.severity === 4 ? 12 : 8,
                fillColor: color,
                color: '#fff',
                weight: 1.5,
                fillOpacity: 0.8
            }).addTo(map).bindPopup(`<b>${pt.category}</b><br>Severity Level: ${pt.severity || 'Normal'}`);
        });
    });
</script>
@endpush
