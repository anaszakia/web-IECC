@extends('layouts.app')

@section('title', 'Edit Unit Armada - ' . $unit->code)

@section('content')

    <div class="d-flex justify-content-between align-items-center mb-6">
        <div>
            <h4 class="mb-0">Edit Unit: {{ $unit->code }}</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('units.index') }}">Master Units</a></li>
                    <li class="breadcrumb-item active">Edit</li>
                </ol>
            </nav>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('units.show', $unit) }}" class="btn btn-outline-secondary">
                <i class="ti ti-users me-1"></i> Atur Anggota
            </a>
            <a href="{{ route('units.index') }}" class="btn btn-white">
                <i class="ti ti-arrow-left me-1"></i> Kembali
            </a>
        </div>
    </div>

    <form action="{{ route('units.update', $unit) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="row">
            {{-- Form Utama --}}
            <div class="col-xl-8">
                <div class="card card-lg mb-4">
                    <div class="card-body">
                        <h6 class="mb-4 text-muted text-uppercase" style="font-size:11px; letter-spacing:.05em;">
                            Informasi Unit Armada
                        </h6>

                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label class="form-label">Kode / Plat Unit <span class="text-danger">*</span></label>
                                <input type="text" name="code"
                                    class="form-control text-uppercase @error('code') is-invalid @enderror"
                                    value="{{ old('code', $unit->code) }}"
                                    placeholder="cth: AMB-01, DAM-02, POL-05" required />
                                @error('code')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Instansi (Agency) <span class="text-danger">*</span></label>
                                <select name="agency_id" class="form-select @error('agency_id') is-invalid @enderror" required>
                                    <option value="">-- Pilih Instansi --</option>
                                    @foreach ($agencies as $agency)
                                        <option value="{{ $agency->id }}" {{ old('agency_id', $unit->agency_id) == $agency->id ? 'selected' : '' }}>
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
                                <label class="form-label">Tipe Unit Armada <span class="text-danger">*</span></label>
                                <select name="type" class="form-select @error('type') is-invalid @enderror" required>
                                    <option value="">-- Pilih Tipe --</option>
                                    <option value="AMBULANCE" {{ old('type', $unit->type) == 'AMBULANCE' ? 'selected' : '' }}>🚑 Ambulans Medis</option>
                                    <option value="FIRE_TRUCK" {{ old('type', $unit->type) == 'FIRE_TRUCK' ? 'selected' : '' }}>🚒 Mobil Pemadam Kebakaran</option>
                                    <option value="POLICE_PATROL" {{ old('type', $unit->type) == 'POLICE_PATROL' ? 'selected' : '' }}>🚓 Mobil Patroli Polisi</option>
                                    <option value="RESCUE_TEAM" {{ old('type', $unit->type) == 'RESCUE_TEAM' ? 'selected' : '' }}>🛟 Tim Penyelamat (Rescue)</option>
                                    <option value="TRAFFIC_UNIT" {{ old('type', $unit->type) == 'TRAFFIC_UNIT' ? 'selected' : '' }}>🚨 Satuan Lalu Lintas</option>
                                    <option value="OTHER" {{ old('type', $unit->type) == 'OTHER' ? 'selected' : '' }}>🚐 Armada Operasional Lainnya</option>
                                </select>
                                @error('type')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Pangkalan / Fasilitas Homebase</label>
                                <select name="base_facility_id" class="form-select @error('base_facility_id') is-invalid @enderror">
                                    <option value="">-- Tidak Terikat Pangkalan Khusus --</option>
                                    @foreach ($facilities as $facility)
                                        <option value="{{ $facility->id }}" {{ old('base_facility_id', $unit->base_facility_id) == $facility->id ? 'selected' : '' }}>
                                            {{ $facility->name }} ({{ $facility->type }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('base_facility_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mt-5 mb-3">
                            <h6 class="mb-0 text-muted text-uppercase" style="font-size:11px; letter-spacing:.05em;">
                                <i class="ti ti-map-pin me-1 text-primary"></i> Koordinat Terakhir / Posisi Live GPS (Pilih di Peta)
                            </h6>
                            <button type="button" class="btn btn-xs btn-outline-primary" id="btn-current-location">
                                <i class="ti ti-current-location me-1"></i> Lokasi Saya Saat Ini
                            </button>
                        </div>

                        {{-- Leaflet Map Picker Container --}}
                        <div class="mb-4">
                            <div id="unit-picker-map" style="height: 320px; width: 100%; border-radius: 8px; border: 1px solid #dee2e6; z-index: 1;"></div>
                            <small class="text-muted d-block mt-1">
                                <i class="ti ti-info-circle me-1"></i> Klik pada area peta di atas atau geser icon untuk memperbarui posisi koordinat unit armada.
                            </small>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Latitude</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-white"><i class="ti ti-map-pin text-primary"></i></span>
                                    <input type="number" step="any" name="lat" id="unit_lat"
                                        class="form-control @error('lat') is-invalid @enderror"
                                        value="{{ old('lat', $unit->lat ?? -6.175392) }}"
                                        placeholder="cth: -6.175392" />
                                </div>
                                @error('lat')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Longitude</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-white"><i class="ti ti-map-pin text-primary"></i></span>
                                    <input type="number" step="any" name="lng" id="unit_lng"
                                        class="form-control @error('lng') is-invalid @enderror"
                                        value="{{ old('lng', $unit->lng ?? 106.827153) }}"
                                        placeholder="cth: 106.827153" />
                                </div>
                                @error('lng')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Sidebar Status & Submit --}}
            <div class="col-xl-4">
                <div class="card card-lg mb-4">
                    <div class="card-body">
                        <h6 class="mb-4 text-muted text-uppercase" style="font-size:11px; letter-spacing:.05em;">
                            Status Kesiapan Operasional
                        </h6>

                        <div class="mb-4">
                            <label class="form-label">Status Unit <span class="text-danger">*</span></label>
                            <select name="status" class="form-select @error('status') is-invalid @enderror" required>
                                <option value="AVAILABLE" {{ old('status', $unit->status) == 'AVAILABLE' ? 'selected' : '' }}>🟢 AVAILABLE (Siap Tugas)</option>
                                <option value="BUSY" {{ old('status', $unit->status) == 'BUSY' ? 'selected' : '' }}>🔴 BUSY (Sedang Bertugas)</option>
                                <option value="OFFLINE" {{ old('status', $unit->status) == 'OFFLINE' ? 'selected' : '' }}>⚪ OFFLINE (Tidak Aktif)</option>
                                <option value="MAINTENANCE" {{ old('status', $unit->status) == 'MAINTENANCE' ? 'selected' : '' }}>🟡 MAINTENANCE (Perawatan)</option>
                            </select>
                            @error('status')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-check form-switch mb-4">
                            <input class="form-check-input" type="checkbox" name="crew_ready" id="crew_ready" value="1" {{ old('crew_ready', $unit->crew_ready) ? 'checked' : '' }}>
                            <label class="form-check-label fw-semibold" for="crew_ready">
                                Regu / Awak Siap (Crew Ready)
                            </label>
                            <small class="text-muted d-block mt-1">
                                Jika diaktifkan, unit dapat langsung di-dispatch saat ada insiden darurat.
                            </small>
                        </div>
                    </div>
                </div>

                {{-- Submit Buttons --}}
                <div class="card card-lg">
                    <div class="card-body">
                        <button type="submit" class="btn btn-primary w-100 mb-2">
                            <i class="ti ti-check me-1"></i> Perbarui Unit Armada
                        </button>
                        <a href="{{ route('units.index') }}" class="btn btn-white w-100">
                            Batal
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </form>

@endsection

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.css" />
<style>
    .unit-pin-icon {
        background: #0d6efd;
        border: 2.5px solid #fff;
        border-radius: 50%;
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 2px 8px rgba(0,0,0,0.35);
        font-size: 14px;
        transition: transform 0.2s ease;
    }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const latInput = document.getElementById('unit_lat');
    const lngInput = document.getElementById('unit_lng');
    const typeSelect = document.querySelector('[name="type"]');
    const currentLocBtn = document.getElementById('btn-current-location');

    let initLat = parseFloat(latInput.value) || -6.175392;
    let initLng = parseFloat(lngInput.value) || 106.827153;

    // Inisialisasi Peta Leaflet
    const map = L.map('unit-picker-map').setView([initLat, initLng], 14);

    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    function getMarkerIcon(type) {
        let iconClass = 'ti ti-truck';
        let bgClass = '#0d6efd';

        if (type === 'AMBULANCE') {
            iconClass = 'ti ti-ambulance';
            bgClass = '#dc3545';
        } else if (type === 'FIRE_TRUCK') {
            iconClass = 'ti ti-flame';
            bgClass = '#fd7e14';
        } else if (type === 'POLICE_PATROL') {
            iconClass = 'ti ti-shield';
            bgClass = '#0dcaf0';
        } else if (type === 'RESCUE_TEAM') {
            iconClass = 'ti ti-lifebuoy';
            bgClass = '#198754';
        }

        return L.divIcon({
            className: '',
            html: `<div class="unit-pin-icon" style="background:${bgClass}; width:34px; height:34px;"><i class="${iconClass}"></i></div>`,
            iconSize: [34, 34],
            iconAnchor: [17, 17],
            popupAnchor: [0, -18]
        });
    }

    // Buat Marker draggable
    let marker = L.marker([initLat, initLng], {
        draggable: true,
        icon: getMarkerIcon(typeSelect ? typeSelect.value : 'AMBULANCE')
    }).addTo(map);

    marker.bindPopup('<b>Posisi Unit: {{ $unit->code }}</b><br>Geser marker atau klik di peta').openPopup();

    function updateInputs(lat, lng) {
        latInput.value = parseFloat(lat).toFixed(6);
        lngInput.value = parseFloat(lng).toFixed(6);
    }

    // Event saat peta diklik
    map.on('click', function(e) {
        const { lat, lng } = e.latlng;
        marker.setLatLng([lat, lng]);
        updateInputs(lat, lng);
    });

    // Event saat marker digeser (dragend)
    marker.on('dragend', function() {
        const pos = marker.getLatLng();
        updateInputs(pos.lat, pos.lng);
    });

    // Event saat input lat/lng diketik manual
    function onManualCoordsChange() {
        const lat = parseFloat(latInput.value);
        const lng = parseFloat(lngInput.value);
        if (!isNaN(lat) && !isNaN(lng)) {
            marker.setLatLng([lat, lng]);
            map.panTo([lat, lng]);
        }
    }
    latInput.addEventListener('input', onManualCoordsChange);
    lngInput.addEventListener('input', onManualCoordsChange);

    // Update icon marker jika tipe unit diganti
    if (typeSelect) {
        typeSelect.addEventListener('change', function() {
            marker.setIcon(getMarkerIcon(this.value));
        });
    }

    // Tombol Lokasi Saya Saat Ini (Geolocation API)
    if (currentLocBtn) {
        currentLocBtn.addEventListener('click', function() {
            if (!navigator.geolocation) {
                alert('Browser Anda tidak mendukung Geolocation.');
                return;
            }
            currentLocBtn.disabled = true;
            currentLocBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Mendapatkan lokasi...';

            navigator.geolocation.getCurrentPosition(
                function(position) {
                    const lat = position.coords.latitude;
                    const lng = position.coords.longitude;
                    marker.setLatLng([lat, lng]);
                    map.setView([lat, lng], 16);
                    updateInputs(lat, lng);
                    currentLocBtn.disabled = false;
                    currentLocBtn.innerHTML = '<i class="ti ti-current-location me-1"></i> Lokasi Saya Saat Ini';
                },
                function(error) {
                    alert('Gagal mengambil lokasi: ' + error.message);
                    currentLocBtn.disabled = false;
                    currentLocBtn.innerHTML = '<i class="ti ti-current-location me-1"></i> Lokasi Saya Saat Ini';
                },
                { enableHighAccuracy: true }
            );
        });
    }

    // Invalidate size agar render map tidak abu-abu/gepeng
    setTimeout(() => {
        map.invalidateSize();
    }, 300);
});
</script>
@endpush
