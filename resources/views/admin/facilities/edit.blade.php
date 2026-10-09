@extends('layouts.app')

@section('title', 'Edit Fasilitas & Pos Layanan')

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<style>
    #map-picker {
        height: 280px;
        width: 100%;
        border-radius: 8px;
        z-index: 1;
    }
</style>
@endpush

@section('content')

    <div class="d-flex justify-content-between align-items-center mb-6">
        <div>
            <h4 class="mb-0">Edit Fasilitas: {{ $facility->name }}</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('facilities.index') }}">Master Fasilitas</a></li>
                    <li class="breadcrumb-item active">Edit</li>
                </ol>
            </nav>
        </div>
        <a href="{{ route('facilities.index') }}" class="btn btn-white">
            <i class="ti ti-arrow-left me-1"></i> Kembali
        </a>
    </div>

    <form action="{{ route('facilities.update', $facility) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="row">
            {{-- Form Utama --}}
            <div class="col-xl-8">
                <div class="card card-lg mb-4">
                    <div class="card-body">
                        <h6 class="mb-4 text-muted text-uppercase" style="font-size:11px; letter-spacing:.05em;">
                            Informasi Fasilitas & Pos
                        </h6>

                        <div class="row mb-4">
                            <div class="col-md-8">
                                <label class="form-label">Nama Fasilitas / Faskes <span class="text-danger">*</span></label>
                                <input type="text" name="name"
                                    class="form-control @error('name') is-invalid @enderror"
                                    value="{{ old('name', $facility->name) }}"
                                    placeholder="cth: RSUD Wilayah Barat, Puskesmas Gambir, Pos Damkar 01" required />
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Instansi Terkait <span class="text-danger">*</span></label>
                                <select name="agency_id" class="form-select @error('agency_id') is-invalid @enderror" required>
                                    <option value="">-- Pilih Instansi --</option>
                                    @foreach ($agencies as $agency)
                                        <option value="{{ $agency->id }}" {{ old('agency_id', $facility->agency_id) == $agency->id ? 'selected' : '' }}>
                                            {{ $agency->name }} ({{ $agency->code }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('agency_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label class="form-label">Tipe Fasilitas <span class="text-danger">*</span></label>
                                <select name="type" id="facility_type" class="form-select @error('type') is-invalid @enderror" required>
                                    <option value="">-- Pilih Tipe --</option>
                                    <option value="HOSPITAL" {{ old('type', $facility->type) == 'HOSPITAL' ? 'selected' : '' }}>🏥 Rumah Sakit (RSUD / RS Swasta)</option>
                                    <option value="PUSKESMAS" {{ old('type', $facility->type) == 'PUSKESMAS' ? 'selected' : '' }}>🩺 Puskesmas / Klinik</option>
                                    <option value="FIRE_STATION" {{ old('type', $facility->type) == 'FIRE_STATION' ? 'selected' : '' }}>🚒 Pos Pemadam Kebakaran</option>
                                    <option value="POLICE_STATION" {{ old('type', $facility->type) == 'POLICE_STATION' ? 'selected' : '' }}>🚓 Kantor Polisi / Polsek</option>
                                    <option value="OTHER" {{ old('type', $facility->type) == 'OTHER' ? 'selected' : '' }}>🏢 Fasilitas / Posko Lainnya</option>
                                </select>
                                @error('type')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Nomor Telepon / Hotline Emergency</label>
                                <input type="text" name="phone"
                                    class="form-control @error('phone') is-invalid @enderror"
                                    value="{{ old('phone', $facility->phone) }}"
                                    placeholder="cth: (021) 555-1234 / 08123456789" />
                                @error('phone')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label">Alamat Lengkap</label>
                            <textarea name="address" rows="2"
                                class="form-control @error('address') is-invalid @enderror"
                                placeholder="cth: Jl. Kesehatan Raya No. 45, Jakarta Pusat">{{ old('address', $facility->address) }}</textarea>
                            @error('address')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Section Bed IGD khusus Faskes Medis --}}
                        <div id="section-er" class="p-3 bg-light rounded-3 mb-4 border">
                            <h6 class="fw-bold text-dark mb-3"><i class="ti ti-bed me-1 text-danger"></i> Informasi IGD & Kapasitas Ranjang</h6>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label fs-xs text-muted text-uppercase">Total Kapasitas Bed IGD</label>
                                    <input type="number" name="er_beds_total" min="0" class="form-control" value="{{ old('er_beds_total', $facility->er_beds_total ?? 0) }}">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fs-xs text-muted text-uppercase">Bed IGD Tersedia (Kosong)</label>
                                    <input type="number" name="er_beds_available" min="0" class="form-control" value="{{ old('er_beds_available', $facility->er_beds_available ?? 0) }}">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fs-xs text-muted text-uppercase">Status Kesiapan IGD</label>
                                    <select name="er_status" class="form-select">
                                        <option value="NORMAL" {{ old('er_status', $facility->er_status) == 'NORMAL' ? 'selected' : '' }}>NORMAL (Tersedia)</option>
                                        <option value="BUSY" {{ old('er_status', $facility->er_status) == 'BUSY' ? 'selected' : '' }}>BUSY (Padat)</option>
                                        <option value="FULL" {{ old('er_status', $facility->er_status) == 'FULL' ? 'selected' : '' }}>FULL (Penuh)</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="mb-4">
                            @php
                                $servicesString = is_array($facility->services) ? implode(', ', $facility->services) : ($facility->services ?? '');
                            @endphp
                            <label class="form-label">Daftar Layanan Spesialisasi (Pisahkan dengan koma)</label>
                            <input type="text" name="services" class="form-control" value="{{ old('services', $servicesString) }}" placeholder="IGD 24 Jam, Trauma Center, ICU, Kamar Operasi, Isolasi">
                        </div>

                        <div class="d-flex justify-content-between align-items-center mt-5 mb-3">
                            <h6 class="mb-0 text-muted text-uppercase" style="font-size:11px; letter-spacing:.05em;">
                                <i class="ti ti-map-pin me-1 text-primary"></i> Titik Koordinat Lokasi (Pilih di Peta)
                            </h6>
                            <button type="button" class="btn btn-xs btn-outline-primary" id="btn-current-location">
                                <i class="ti ti-current-location me-1"></i> Lokasi Saya Saat Ini
                            </button>
                        </div>

                        <div id="map-picker" class="mb-3 border"></div>

                        <div class="row">
                            <div class="col-md-6">
                                <label class="form-label">Latitude</label>
                                <input type="number" step="any" name="lat" id="lat"
                                    class="form-control @error('lat') is-invalid @enderror"
                                    value="{{ old('lat', $facility->lat ?? -6.200000) }}" />
                                @error('lat')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Longitude</label>
                                <input type="number" step="any" name="lng" id="lng"
                                    class="form-control @error('lng') is-invalid @enderror"
                                    value="{{ old('lng', $facility->lng ?? 106.816666) }}" />
                                @error('lng')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Kolom Kanan --}}
            <div class="col-xl-4">
                <div class="card card-lg mb-4">
                    <div class="card-body">
                        <h6 class="mb-4 text-muted text-uppercase" style="font-size:11px; letter-spacing:.05em;">
                            Status Operasional
                        </h6>

                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" role="switch" id="is_active" name="is_active" value="1" {{ old('is_active', $facility->is_active) ? 'checked' : '' }}>
                            <label class="form-check-label fw-medium" for="is_active">Aktif & Beroperasi</label>
                            <div class="form-text">Jika dimatikan, fasilitas tidak akan muncul pada rekomendasi rujukan atau pangkalan armada.</div>
                        </div>

                        <hr class="my-4">

                        <button type="submit" class="btn btn-primary w-100 mb-2">
                            <i class="ti ti-device-floppy me-1"></i> Perbarui Fasilitas
                        </button>
                        <a href="{{ route('facilities.index') }}" class="btn btn-outline-secondary w-100">
                            Batal
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </form>

@endsection

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const defaultLat = parseFloat(document.getElementById('lat').value) || -6.200000;
        const defaultLng = parseFloat(document.getElementById('lng').value) || 106.816666;

        const map = L.map('map-picker').setView([defaultLat, defaultLng], 14);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap'
        }).addTo(map);

        let marker = L.marker([defaultLat, defaultLng], { draggable: true }).addTo(map);

        function updateInputs(lat, lng) {
            document.getElementById('lat').value = lat.toFixed(6);
            document.getElementById('lng').value = lng.toFixed(6);
        }

        marker.on('dragend', function(e) {
            const pos = e.target.getLatLng();
            updateInputs(pos.lat, pos.lng);
        });

        map.on('click', function(e) {
            marker.setLatLng(e.latlng);
            updateInputs(e.latlng.lat, e.latlng.lng);
        });

        document.getElementById('btn-current-location').addEventListener('click', function() {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(function(pos) {
                    const lat = pos.coords.latitude;
                    const lng = pos.coords.longitude;
                    map.setView([lat, lng], 15);
                    marker.setLatLng([lat, lng]);
                    updateInputs(lat, lng);
                }, function(err) {
                    alert('Gagal mendeteksi lokasi GPS: ' + err.message);
                });
            } else {
                alert('Browser Anda tidak mendukung geolokasi.');
            }
        });

        // Hide/Show ER section depending on facility type
        const typeSelect = document.getElementById('facility_type');
        const erSection = document.getElementById('section-er');

        function toggleEr() {
            const val = typeSelect.value;
            if (val === 'HOSPITAL' || val === 'PUSKESMAS') {
                erSection.style.display = 'block';
            } else {
                erSection.style.display = 'none';
            }
        }

        typeSelect.addEventListener('change', toggleEr);
        toggleEr();
    });
</script>
@endpush
