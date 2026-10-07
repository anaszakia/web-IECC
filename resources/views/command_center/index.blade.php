@extends('layouts.app')

@section('title', 'Command Center - IECC')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.css" />
<style>
    #map-container {
        height: 560px;
        min-height: 560px;
        width: 100%;
        border-radius: 8px;
        position: relative;
        z-index: 1;
        background-color: #1e222d;
    }
    #map-container.leaflet-container {
        height: 560px !important;
        width: 100% !important;
        background: #1e222d !important;
        border-radius: 8px;
    }
    /* Ikon marker custom */
    .cc-icon { background: transparent; border: 0; }
    .cc-inc-new { animation: ccRing 1.2s infinite; }
    @keyframes ccRing {
        0%   { box-shadow: 0 0 0 0 rgba(220, 53, 69, .85), 0 2px 8px rgba(0,0,0,.55); }
        100% { box-shadow: 0 0 0 16px rgba(220, 53, 69, 0), 0 2px 8px rgba(0,0,0,.55); }
    }
    /* Notifikasi alarm persisten */
    #cc-alarm-banner {
        position: fixed;
        top: 76px;
        right: 16px;
        width: 360px;
        max-width: calc(100vw - 32px);
        max-height: 70vh;
        overflow-y: auto;
        z-index: 2050;
    }
    .cc-alarm-card {
        background: #dc3545;
        color: #fff;
        border-radius: 10px;
        padding: 10px 12px;
        margin-bottom: 8px;
        animation: ccPulse 1s infinite;
    }
    @keyframes ccPulse {
        0%, 100% { box-shadow: 0 0 0 0 rgba(220, 53, 69, .7); }
        50%      { box-shadow: 0 0 0 10px rgba(220, 53, 69, 0); }
    }
    /* Tampilan gelap untuk tile OSM (marker tidak terpengaruh) */
    #map-container .leaflet-tile-pane {
        filter: invert(100%) hue-rotate(180deg) brightness(95%) contrast(90%);
    }
    .incident-card {
        cursor: pointer;
        transition: all 0.2s ease-in-out;
        border-left: 4px solid #6c757d;
    }
    .incident-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.12);
    }
    .incident-card.status-NEW { border-left-color: #dc3545; background-color: rgba(220, 53, 69, 0.04); }
    .incident-card.status-VERIFIED { border-left-color: #fd7e14; }
    .incident-card.status-DISPATCHED { border-left-color: #0d6efd; }
    .incident-card.status-ACCEPTED { border-left-color: #20c997; }
    .incident-card.status-ARRIVED { border-left-color: #198754; }
</style>
@endpush

@section('content')
<script>
// ==========================================
// 1. ENGINE SUARA SIRENE (WEB AUDIO API)
// ==========================================
window.playEmergencySirenSound = function() {
    try {
        var AudioCtx = window.AudioContext || window.webkitAudioContext;
        if (AudioCtx) {
            var ctx = window._sirenAudioCtx || (window._sirenAudioCtx = new AudioCtx());
            if (ctx.state === 'suspended') {
                ctx.resume();
            }

            var master = ctx.createGain();
            master.gain.setValueAtTime(0.5, ctx.currentTime);
            master.connect(ctx.destination);

            var start = ctx.currentTime + 0.02;
            var tones = [960, 680, 960, 680, 960, 680, 960, 680];
            var step = 0.28;

            tones.forEach(function(freq, i) {
                var t = start + (i * step);
                var osc = ctx.createOscillator();
                var g = ctx.createGain();

                osc.type = 'sawtooth';
                osc.frequency.setValueAtTime(freq, t);

                g.gain.setValueAtTime(0.001, t);
                g.gain.linearRampToValueAtTime(0.9, t + 0.02);
                g.gain.setValueAtTime(0.9, t + step - 0.04);
                g.gain.linearRampToValueAtTime(0.001, t + step);

                osc.connect(g);
                g.connect(master);

                osc.start(t);
                osc.stop(t + step);
            });
        }

        var audioTag = new Audio('/sounds/emergency-alarm');
        audioTag.play().catch(function(){});
    } catch (e) {
        console.warn('Audio play error:', e);
    }
};

window.testEmergencySound = function() {
    window.playEmergencySirenSound();
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'warning',
            title: '🚨 Tes Sirene Darurat',
            text: 'Sirene darurat aktif & siap membunyikan alarm insiden baru.',
            timer: 3500,
            showConfirmButton: false,
            background: '#dc3545',
            color: '#fff',
            iconColor: '#fff',
        });
    }
};

// Status/diagnosa peta (tampil di dalam kotak peta)
window.ccMapReady = false;
window.ccMapNotice = function(msg) {
    var el = document.getElementById('map-status');
    if (!el) return;
    if (!msg) { el.style.display = 'none'; return; }
    el.style.display = 'block';
    el.innerText = msg;
};
window.addEventListener('error', function(e) {
    if (window.ccMapReady) return;
    window.ccMapNotice('JS error: ' + e.message + ' (' + String(e.filename || '').split('/').pop() + ':' + e.lineno + ')');
});

// Unlock Audio saat ada interaksi pertama
['click', 'keydown', 'touchstart'].forEach(function(ev) {
    document.addEventListener(ev, function() {
        var AC = window.AudioContext || window.webkitAudioContext;
        if (AC && !window._sirenAudioCtx) {
            try { window._sirenAudioCtx = new AC(); } catch (e) {}
        }
        if (window._sirenAudioCtx && window._sirenAudioCtx.state === 'suspended') {
            window._sirenAudioCtx.resume().catch(function(){});
        }
        if (!window._sirenUnlocked) {
            window._sirenUnlocked = true;
            if (window.ccRefreshStatus) window.ccRefreshStatus();
        }
    }, { passive: true });
});
</script>

<div class="row g-4 mb-4">
    {{-- Header & Live Control --}}
    <div class="col-12 d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h3 class="mb-1 fw-bold">Integrated Emergency Command Center</h3>
            <p class="text-muted mb-0">Pemantauan dan Orkestrasi Insiden Tanggap Darurat Kota Real-Time</p>
            <div class="d-flex align-items-center gap-2 mt-1">
                <span id="cc-live-badge" class="badge bg-warning-subtle text-warning border border-warning px-2 py-1">
                    ● Menghubungkan Reverb...
                </span>
                <span id="cc-status-text" class="text-secondary small font-monospace">Menghubungkan WebSocket...</span>
            </div>
        </div>
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('incidents.history') }}" class="btn btn-outline-primary btn-sm d-flex align-items-center gap-1 shadow-sm px-3 py-2">
                <i class="ti ti-history fs-5"></i>
                <span class="fw-bold">Riwayat Kejadian</span>
            </a>
            <button id="btn-sound-toggle" type="button" class="btn btn-danger btn-sm d-flex align-items-center gap-2 shadow-sm px-3 py-2" onclick="window.testEmergencySound()">
                <i class="ti ti-volume" id="sound-icon"></i>
                <span id="sound-text" class="fw-bold">Tes Sirene Darurat</span>
            </button>
            <button class="btn btn-outline-secondary btn-sm px-3 py-2" onclick="window.manualSyncData()">
                <i class="ti ti-refresh me-1"></i> Sinkronisasi
            </button>
        </div>
    </div>

    {{-- Kartu Ringkasan Cepat --}}
    <div class="col-xl-3 col-md-6">
        <div class="card card-lg shadow-sm border-0">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-muted small fw-semibold">TOTAL INSIDEN HARI INI</span>
                    <h2 class="fw-bold mb-0 mt-1" id="stat-today">{{ $todayStats['total'] }}</h2>
                </div>
                <div class="icon-shape icon-lg rounded-circle bg-primary-subtle text-primary">
                    <i class="ti ti-phone-incoming fs-3"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card card-lg shadow-sm border-0">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-muted small fw-semibold">INSIDEN AKTIF</span>
                    <h2 class="fw-bold mb-0 mt-1 text-danger" id="stat-active">{{ $todayStats['active'] }}</h2>
                </div>
                <div class="icon-shape icon-lg rounded-circle bg-danger-subtle text-danger">
                    <i class="ti ti-alert-triangle fs-3"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card card-lg shadow-sm border-0">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-muted small fw-semibold">DARURAT KRITIS (SEV 4)</span>
                    <h2 class="fw-bold mb-0 mt-1 text-warning" id="stat-critical">{{ $todayStats['critical'] }}</h2>
                </div>
                <div class="icon-shape icon-lg rounded-circle bg-warning-subtle text-warning">
                    <i class="ti ti-flame fs-3"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card card-lg shadow-sm border-0 cursor-pointer" onclick="window.location.href='{{ route('incidents.history') }}?status=RESOLVED'" style="cursor: pointer;">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-muted small fw-semibold">SELESAI (RESOLVED)</span>
                    <h2 class="fw-bold mb-0 mt-1 text-success" id="stat-resolved">{{ $todayStats['resolved'] }}</h2>
                </div>
                <div class="icon-shape icon-lg rounded-circle bg-success-subtle text-success">
                    <i class="ti ti-circle-check fs-3"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    {{-- Peta Situasi Kota --}}
    <div class="col-xl-8 col-lg-7">
        <div class="card card-lg shadow-sm border-0 h-100">
            <div class="card-header bg-transparent border-bottom-0 d-flex justify-content-between align-items-center py-3">
                <h5 class="mb-0 fw-bold"><i class="ti ti-map-2 me-2 text-primary"></i>Peta Situasi Wilayah</h5>
                <div class="d-flex gap-2 flex-wrap">
                    <span class="badge bg-danger">● Insiden Baru</span>
                    <span class="badge" style="background:#0d6efd">🚑 Ambulans</span>
                    <span class="badge" style="background:#f08c00">🚒 Damkar</span>
                    <span class="badge" style="background:#0891b2">🚓 Polisi</span>
                    <span class="badge bg-success">✚ Rumah Sakit</span>
                </div>
            </div>
            <div class="card-body p-2">
                <div id="map-container"><div id="map-status" style="position:absolute;top:8px;left:8px;right:8px;z-index:1000;pointer-events:none;background:rgba(0,0,0,.75);color:#fff;padding:6px 10px;border-radius:6px;font:12px monospace;">Memuat peta...</div></div>
            </div>
        </div>
    </div>

    {{-- Antrean Insiden Aktif --}}
    <div class="col-xl-4 col-lg-5">
        <div class="card card-lg shadow-sm border-0 h-100">
            <div class="card-header bg-transparent border-bottom-0 d-flex justify-content-between align-items-center py-3">
                <h5 class="mb-0 fw-bold"><i class="ti ti-layout-list me-2 text-primary"></i>Antrean Insiden</h5>
                <span class="badge bg-primary-subtle text-primary" id="incident-count">{{ $activeIncidents->count() }} Laporan</span>
            </div>
            <div class="card-body p-3 overflow-auto" style="max-height: 560px;" id="incident-list-container">
                @forelse ($activeIncidents as $inc)
                    <div class="card incident-card status-{{ $inc->status }} mb-3 p-3" onclick="window.location.href='{{ route('command-center.show', $inc->ulid) }}'">
                        <div class="d-flex justify-content-between align-items-start mb-1">
                            <span class="fw-bold text-dark fs-6">{{ $inc->incident_no }}</span>
                            <span class="badge bg-{{ $inc->status == 'NEW' ? 'danger' : ($inc->status == 'VERIFIED' ? 'warning' : ($inc->status == 'RESOLVED' ? 'success' : 'primary')) }}">
                                {{ $inc->status_label }}
                            </span>
                        </div>
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <span class="badge bg-secondary-subtle text-secondary small">{{ $inc->category_label }}</span>
                            @if($inc->severity)
                                <span class="badge bg-danger-subtle text-danger small">Tingkat {{ $inc->severity }}</span>
                            @endif
                            <small class="text-muted ms-auto">{{ $inc->reported_at?->diffForHumans() }}</small>
                        </div>
                        <p class="text-secondary small mb-2 text-truncate">
                            {{ $inc->description ?: ($inc->address_text ?: 'Tidak ada keterangan tambahan.') }}
                        </p>
                        <div class="d-flex justify-content-between align-items-center small text-muted border-top pt-2">
                            <span><i class="ti ti-map-pin me-1"></i>{{ number_format($inc->lat, 4) }}, {{ number_format($inc->lng, 4) }}</span>
                            <span class="text-primary fw-semibold">Lihat Detail <i class="ti ti-chevron-right"></i></span>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-5 text-muted">
                        <i class="ti ti-shield-check fs-1 text-success mb-2"></i>
                        <p>Tidak ada insiden darurat aktif saat ini.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.js"></script>
<script async src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
<script>
(function() {
    'use strict';

    // 2. Leaflet Map
    var ccMap = null;
    var ccIncidentMarkers = {};
    var ccUnitMarkers = {};
    var ccIncidentLayer = null;
    var ccIncidentData = {};
    var ccIncidentSig = '';
    var ccOpenKey = null;
    var ccRebuilding = false;
    var ccRenderTimer = null;
    var ccKnownIncidentIds = new Set();
    var ccBoundsLayers = [];
    var ccFallbackTried = false;
    var ccTileErrShown = false;
    
    // Inisialisasi ID yang sudah ada di blade awal
    @foreach ($activeIncidents as $inc)
        ccKnownIncidentIds.add(@json($inc->id));
    @endforeach

    function initMap() {
        var container = document.getElementById('map-container');
        if (!container) return;
        if (typeof L === 'undefined') {
            if (!ccFallbackTried) {
                ccFallbackTried = true;
                window.ccMapNotice('Leaflet dari jsdelivr gagal dimuat, mencoba cdnjs...');
                var css = document.createElement('link');
                css.rel = 'stylesheet';
                css.href = 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.css';
                document.head.appendChild(css);
                var js = document.createElement('script');
                js.src = 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.js';
                js.onload = initMap;
                js.onerror = function() {
                    window.ccMapNotice('Leaflet gagal dimuat (jsdelivr & cdnjs). Cek koneksi / CSP / adblock.');
                };
                document.head.appendChild(js);
            }
            return;
        }

        try {
            if (ccMap) return;
            ccMap = L.map('map-container', {
                center: [-6.1753924, 106.8271528],
                zoom: 13,
                attributionControl: true
            });

            L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; OpenStreetMap contributors'
            }).on('tileerror', function() {
                if (!ccTileErrShown) {
                    ccTileErrShown = true;
                    window.ccMapNotice('Tile peta gagal dimuat (cek akses ke tile.openstreetmap.org)');
                }
            }).addTo(ccMap);

            // Layer insiden (digroup per titik koordinat)
            ccIncidentLayer = L.layerGroup().addTo(ccMap);

            // Render Facilities
            var facilities = @json($mapFacilities);
            if (Array.isArray(facilities)) {
                facilities.forEach(function(fac) {
                    if (!fac.lat || !fac.lng) return;
                    var facMarker = L.marker([parseFloat(fac.lat), parseFloat(fac.lng)], {
                        icon: L.divIcon({
                            className: 'cc-icon',
                            html: ccFacilityIconHtml(fac.type),
                            iconSize: [30, 30],
                            iconAnchor: [15, 15],
                            popupAnchor: [0, -16]
                        }),
                        zIndexOffset: 0
                    }).addTo(ccMap).bindPopup('<b>' + ccEsc(fac.name) + '</b><br><small>' + ccEsc(fac.type) + ' - ' + ccEsc(fac.address || '') + '</small>');
                    ccBoundsLayers.push(facMarker);
                });
            }

            // Render Awal Insiden
            var incidents = @json($mapIncidents);
            if (Array.isArray(incidents)) {
                incidents.forEach(drawIncidentMarker);
                renderIncidentMarkers();
            }

            // Render Awal Unit
            var units = @json($mapUnits);
            if (Array.isArray(units)) {
                units.forEach(drawUnitMarker);
            }

            function fitToMarkers() {
                if (!ccMap) return;
                ccMap.invalidateSize();
                var layers = ccBoundsLayers
                    .concat(Object.keys(ccIncidentMarkers).map(function(k) { return ccIncidentMarkers[k]; }))
                    .concat(Object.keys(ccUnitMarkers).map(function(k) { return ccUnitMarkers[k]; }));
                if (layers.length) {
                    ccMap.fitBounds(L.featureGroup(layers).getBounds().pad(0.2), { maxZoom: 15 });
                }
            }
            fitToMarkers();
            setTimeout(function() { if (ccMap) ccMap.invalidateSize(); }, 300);
            setTimeout(function() { if (ccMap) ccMap.invalidateSize(); }, 1000);
            window.ccMapReady = true;
            window.ccMapNotice('');
        } catch (e) {
            console.error('Leaflet init exception:', e);
            window.ccMapNotice('Init error: ' + e.message);
        }
    }

    var ccStatusLabels = { NEW: 'Laporan Baru', VERIFIED: 'Terverifikasi', DISPATCHED: 'Armada Bergerak' };
    var ccCategoryLabels = { FIRE: 'Kebakaran', MEDICAL: 'Medis Darurat', TRAFFIC: 'Laka Lantas', DISASTER: 'Bencana Alam' };

    function ccEsc(v) {
        return String(v == null ? '' : v).replace(/[&<>"']/g, function(c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }

    var INACTIVE_STATUSES = ['RESOLVED', 'CLOSED', 'CANCELLED', 'DUPLICATE', 'FALSE_REPORT'];

    // Simpan data insiden, marker digambar per titik koordinat
    function drawIncidentMarker(inc) {
        if (!inc || inc.id === undefined || inc.id === null) return;
        
        // Jika status insiden sudah selesai/RESOLVED/CLOSED, hapus dari list data aktif
        if (inc.status && INACTIVE_STATUSES.indexOf(inc.status) !== -1) {
            delete ccIncidentData[inc.id];
        } else {
            ccIncidentData[inc.id] = inc;
        }

        if (ccRenderTimer) return;
        ccRenderTimer = setTimeout(function() {
            ccRenderTimer = null;
            renderIncidentMarkers();
        }, 0);
    }

    function buildIncidentPopup(items) {
        var html = '<div style="min-width: 240px; max-width: 300px;">';
        if (items.length > 1) {
            html += '<div class="fw-bold mb-2">' + items.length + ' insiden di lokasi ini</div>';
        }
        html += '<div style="max-height: 260px; overflow-y: auto;">';
        items.forEach(function(inc, idx) {
            var badge = inc.status === 'NEW' ? 'danger' : (inc.status === 'VERIFIED' ? 'warning' : 'primary');
            html += '<div class="' + (idx > 0 ? 'border-top pt-2 mt-2' : '') + '">' +
                '<div class="d-flex justify-content-between align-items-center">' +
                '<b class="text-danger">' + ccEsc(inc.incident_no) + '</b>' +
                '<span class="badge bg-' + badge + '">' + ccEsc(ccStatusLabels[inc.status] || inc.status) + '</span>' +
                '</div>' +
                '<div class="small"><b>Kategori:</b> ' + ccEsc(ccCategoryLabels[inc.category] || inc.category || 'DARURAT') +
                (inc.severity ? ' &middot; <b>Tingkat</b> ' + ccEsc(inc.severity) : '') + '</div>' +
                '<div class="small text-secondary">' + ccEsc(inc.description || inc.address_text || 'Laporan Masuk') + '</div>' +
                (inc.reported_at ? '<div class="small text-muted">' + ccEsc(new Date(inc.reported_at).toLocaleString()) + '</div>' : '') +
                '<a href="/command-center/' + encodeURIComponent(inc.ulid) + '" class="btn btn-sm btn-primary text-white w-100 py-1 mt-1">Buka Insiden</a>' +
                '</div>';
        });
        html += '</div></div>';
        return html;
    }

    function renderIncidentMarkers() {
        if (!ccMap || !ccIncidentLayer) return;

        var list = Object.keys(ccIncidentData)
            .map(function(k) { return ccIncidentData[k]; })
            .filter(function(i) {
                return i &&
                    INACTIVE_STATUSES.indexOf(i.status) === -1 &&
                    !isNaN(parseFloat(i.lat)) &&
                    !isNaN(parseFloat(i.lng));
            });

        var sig = list.map(function(i) {
            return i.id + '|' + i.status + '|' + i.severity + '|' + i.lat + '|' + i.lng;
        }).sort().join(';');
        if (sig === ccIncidentSig) return;
        ccIncidentSig = sig;

        var groups = {};
        list.forEach(function(i) {
            var key = parseFloat(i.lat).toFixed(5) + ',' + parseFloat(i.lng).toFixed(5);
            (groups[key] = groups[key] || []).push(i);
        });

        ccRebuilding = true;
        ccIncidentLayer.clearLayers();
        ccIncidentMarkers = {};

        Object.keys(groups).forEach(function(key) {
            var items = groups[key];
            items.sort(function(a, b) {
                var an = a.status === 'NEW' ? 0 : 1;
                var bn = b.status === 'NEW' ? 0 : 1;
                if (an !== bn) return an - bn;
                return String(b.reported_at || '').localeCompare(String(a.reported_at || ''));
            });

            var hasNew = items.some(function(i) { return i.status === 'NEW'; });
            var hasVerified = items.some(function(i) { return i.status === 'VERIFIED'; });
            var color = hasNew ? '#dc3545' : (hasVerified ? '#fd7e14' : '#0d6efd');
            var count = items.length;
            var kind = ccCategoryKind(items[0].category);

            var icon = L.divIcon({
                className: 'cc-icon',
                html: ccIncidentIconHtml(kind, color, count, hasNew),
                iconSize: [40, 40],
                iconAnchor: [20, 20],
                popupAnchor: [0, -22]
            });

            var parts = key.split(',');
            var marker = L.marker([parseFloat(parts[0]), parseFloat(parts[1])], {
                icon: icon,
                zIndexOffset: hasNew ? 1000 : 500
            }).addTo(ccIncidentLayer).bindPopup(buildIncidentPopup(items), { maxWidth: 320 });

            marker.on('popupopen', function() { ccOpenKey = key; });
            marker.on('popupclose', function() {
                if (!ccRebuilding && ccOpenKey === key) ccOpenKey = null;
            });

            ccIncidentMarkers[key] = marker;
        });

        ccRebuilding = false;
        if (ccOpenKey && ccIncidentMarkers[ccOpenKey]) {
            ccIncidentMarkers[ccOpenKey].openPopup();
        }
    }

    // ===== Ikon marker (SVG inline, tanpa dependensi) =====
    var ccUnitStyles = {
        AMBULANCE:     { color: '#0d6efd', label: 'Ambulans' },
        FIRE_TRUCK:    { color: '#f08c00', label: 'Mobil Damkar' },
        POLICE_PATROL: { color: '#0891b2', label: 'Mobil Polisi' },
        DEFAULT:       { color: '#6c757d', label: 'Unit' }
    };
    var ccFacilityStyles = {
        HOSPITAL:       { color: '#198754', glyph: 'cross' },
        PUSKESMAS:      { color: '#20a37a', glyph: 'cross' },
        FIRE_STATION:   { color: '#f08c00', glyph: 'house' },
        POLICE_STATION: { color: '#0891b2', glyph: 'shield' },
        DEFAULT:        { color: '#6c757d', glyph: 'house' }
    };

    function ccSvg(inner, size) {
        return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="' + size + '" height="' + size + '" fill="#fff">' + inner + '</svg>';
    }

    function ccWheel(cx, cy, bg) {
        return '<circle cx="' + cx + '" cy="' + cy + '" r="2.3" fill="#fff" stroke="' + bg + '" stroke-width="1.3"/>';
    }

    function ccVehicleGlyph(type, bg) {
        var cut = ' fill="' + bg + '"';
        if (type === 'AMBULANCE') {
            return '<rect x="7.5" y="4.4" width="3.8" height="1.7" rx=".6"/>' +
                '<rect x="1.5" y="6.5" width="14" height="10" rx="1.5"/>' +
                '<path d="M15.5 9H19l3.5 3.5v4h-7z"/>' +
                '<path d="M17 10.4h1.7l2.1 2.1H17z"' + cut + '/>' +
                '<path d="M8.5 8.4h1.8v1.9h1.9v1.8h-1.9V14H8.5v-1.9H6.6v-1.8h1.9z"' + cut + '/>' +
                ccWheel(6.5, 17.2, bg) + ccWheel(18.5, 17.2, bg);
        }
        if (type === 'FIRE_TRUCK') {
            return '<rect x="2.5" y="5.6" width="11.5" height="1.6" rx=".5"/>' +
                '<rect x="4" y="7.2" width="1.3" height="2.4"/><rect x="11.2" y="7.2" width="1.3" height="2.4"/>' +
                '<rect x="1.5" y="9.5" width="13.5" height="7" rx="1.2"/>' +
                '<path d="M15 9.5h4l3.5 3.5v3.5H15z"/>' +
                '<path d="M16.4 10.9h2.1l2.1 2.1h-4.2z"' + cut + '/>' +
                '<rect x="3.2" y="11.6" width="9" height="1.2" rx=".4"' + cut + '/>' +
                ccWheel(6.5, 17.2, bg) + ccWheel(18.5, 17.2, bg);
        }
        var light = (type === 'POLICE_PATROL') ? '<rect x="9.6" y="4.4" width="4.8" height="1.7" rx=".6"/>' : '';
        return light +
            '<rect x="1.5" y="11.5" width="21" height="5.2" rx="1.6"/>' +
            '<path d="M5.2 11.5l2.1-3.9c.2-.4.6-.6 1-.6h7.4c.4 0 .8.2 1 .6l2.1 3.9z"/>' +
            '<path d="M8.6 8.4h2.8v2.9H7.2z"' + cut + '/>' +
            '<path d="M12.6 8.4h2.8l1.4 2.9h-4.2z"' + cut + '/>' +
            ccWheel(6.5, 17.2, bg) + ccWheel(17.5, 17.2, bg);
    }

    function ccCategoryKind(c) {
        var k = String(c || '').toUpperCase();
        if (/FIRE|KEBAKARAN/.test(k)) return 'FIRE';
        if (/TRAFFIC|ACCIDENT|LAKA|KECELAKAAN|CRASH/.test(k)) return 'TRAFFIC';
        if (/MEDIC|HEALTH|AMBULAN/.test(k)) return 'MEDICAL';
        if (/DISASTER|FLOOD|BANJIR|BENCANA|LANDSLIDE|LONGSOR|EARTHQUAKE|GEMPA/.test(k)) return 'DISASTER';
        if (/CRIME|THEFT|VIOLENCE|SECURITY|POLICE|ORDER|KRIMINAL/.test(k)) return 'CRIME';
        return 'OTHER';
    }

    var CC_SHAPES = {
        cross:  '<path d="M9.6 3.5h4.8v6.1h6.1v4.8h-6.1v6.1H9.6v-6.1H3.5V9.6h6.1z"/>',
        shield: '<path d="M12 2.8l7.8 2.9v6c0 4.7-3.2 8.2-7.8 9.5-4.6-1.3-7.8-4.8-7.8-9.5v-6z"/>',
        house:  '<path d="M12 3.2l9 7.4v10H15v-6H9v6H3v-10z"/>'
    };

    function ccIncidentGlyph(kind, bg) {
        var cut = ' fill="' + bg + '"';
        if (kind === 'FIRE') {
            return '<path d="M12 12c2-2.96 0-7-1-8 0 3.038-1.773 4.741-3 6-1.226 1.26-2 3.24-2 5a6 6 0 1 0 12 0c0-1.532-1.056-3.94-2-5-1.786 3-2.791 3-4 2z" stroke="#fff" stroke-width="1.2" stroke-linejoin="round"/>';
        }
        if (kind === 'TRAFFIC') {
            return '<rect x="1.2" y="11.2" width="16.3" height="5.6" rx="1.5"/>' +
                '<path d="M3.8 11.2l2-3.7h7.6l2.4 3.7z"/>' +
                ccWheel(6, 17.3, bg).replace('r="2.3"', 'r="2.1"') + ccWheel(13, 17.3, bg).replace('r="2.3"', 'r="2.1"') +
                '<path d="M20 2.6v3.6M16.6 4.2l2.4 2.6M23.4 4.2l-2.4 2.6" fill="none" stroke="#fff" stroke-width="1.9" stroke-linecap="round"/>';
        }
        if (kind === 'MEDICAL') return CC_SHAPES.cross;
        if (kind === 'DISASTER') {
            var w = function(y) {
                return '<path d="M2.5 ' + y + 'c2.2-2.4 4.2-2.4 6.4 0 2.2 2.4 4.2 2.4 6.4 0 2.2-2.4 4.2-2.4 6.4 0" fill="none" stroke="#fff" stroke-width="2.2" stroke-linecap="round"/>';
            };
            return w(7.5) + w(12.5) + w(17.5);
        }
        if (kind === 'CRIME') return CC_SHAPES.shield;
        return '<path d="M12 3.2l9.6 16.6H2.4z" stroke="#fff" stroke-width="1.2" stroke-linejoin="round"/>' +
            '<rect x="11" y="9.4" width="2" height="5.2" rx="1"' + cut + '/>' +
            '<circle cx="12" cy="17" r="1.15"' + cut + '/>';
    }

    function ccIncidentIconHtml(kind, color, count, isNew) {
        var badge = count > 1
            ? '<span style="position:absolute;top:-8px;right:-8px;min-width:20px;height:20px;padding:0 5px;box-sizing:border-box;border-radius:10px;background:#212529;border:2px solid #fff;color:#fff;font:700 11px/16px sans-serif;text-align:center;">' + count + '</span>'
            : '';
        return '<div class="cc-inc' + (isNew ? ' cc-inc-new' : '') + '" style="position:relative;width:40px;height:40px;border-radius:50%;background:' + color + ';border:3px solid #fff;box-sizing:border-box;display:flex;align-items:center;justify-content:center;box-shadow:0 2px 8px rgba(0,0,0,.55);">' +
            ccSvg(ccIncidentGlyph(kind, color), 24) + badge + '</div>';
    }

    function ccUnitIconHtml(type, status) {
        var st = ccUnitStyles[type] || ccUnitStyles.DEFAULT;
        var dot = status === 'AVAILABLE' ? '#28a745' : (status === 'BUSY' ? '#fd7e14' : '#adb5bd');
        return '<div style="position:relative;width:38px;height:38px;border-radius:10px;background:' + st.color + ';border:2px solid #fff;box-sizing:border-box;display:flex;align-items:center;justify-content:center;box-shadow:0 2px 6px rgba(0,0,0,.5);">' +
            ccSvg(ccVehicleGlyph(type, st.color), 28) +
            '<span style="position:absolute;right:-5px;bottom:-5px;width:12px;height:12px;border-radius:50%;background:' + dot + ';border:2px solid #fff;"></span></div>';
    }

    function ccFacilityIconHtml(type) {
        var st = ccFacilityStyles[type] || ccFacilityStyles.DEFAULT;
        return '<div style="width:30px;height:30px;border-radius:50%;background:' + st.color + ';border:2px solid #fff;box-sizing:border-box;display:flex;align-items:center;justify-content:center;box-shadow:0 2px 6px rgba(0,0,0,.5);">' +
            ccSvg(CC_SHAPES[st.glyph], 17) + '</div>';
    }

    function drawUnitMarker(u) {
        if (!ccMap || !u.lat || !u.lng) return;
        var lat = parseFloat(u.lat);
        var lng = parseFloat(u.lng);
        if (isNaN(lat) || isNaN(lng)) return;

        var sig = u.status + '|' + u.type + '|' + lat + '|' + lng;
        if (ccUnitMarkers[u.id]) {
            if (ccUnitMarkers[u.id]._sig === sig) return;
            ccMap.removeLayer(ccUnitMarkers[u.id]);
        }

        var ust = ccUnitStyles[u.type] || ccUnitStyles.DEFAULT;
        var marker = L.marker([lat, lng], {
            icon: L.divIcon({
                className: 'cc-icon',
                html: ccUnitIconHtml(u.type, u.status),
                iconSize: [38, 38],
                iconAnchor: [19, 19],
                popupAnchor: [0, -20]
            }),
            zIndexOffset: 200
        }).addTo(ccMap).bindPopup('<b>Unit: ' + ccEsc(u.code) + '</b><br>Tipe: ' + ccEsc(ust.label) + '<br>Status: ' + ccEsc(u.status));

        marker._sig = sig;
        ccUnitMarkers[u.id] = marker;
    }

    // 3. Laravel Reverb WebSocket Integration
    var ccWsText = 'Menghubungkan WebSocket...';
    var ccSyncText = '';
    var CC_OK = 'bg-success-subtle text-success border border-success';
    var CC_WARN = 'bg-warning-subtle text-warning border border-warning';
    var CC_BAD = 'bg-danger-subtle text-danger border border-danger';

    function refreshStatus() {
        var statusEl = document.getElementById('cc-status-text');
        if (!statusEl) return;
        var txt = ccWsText;
        if (ccSyncText) txt += ' | ' + ccSyncText;
        if (!window._sirenUnlocked) txt += ' | Sirene: klik di halaman sekali untuk mengaktifkan';
        statusEl.innerText = txt;
    }
    window.ccRefreshStatus = function() {
        refreshStatus();
        renderAlarmBanner();
    };

    function setLive(cls, badgeHtml, text) {
        var badgeEl = document.getElementById('cc-live-badge');
        if (badgeEl) {
            badgeEl.className = 'badge ' + cls + ' px-2 py-1';
            badgeEl.innerHTML = badgeHtml;
        }
        ccWsText = text;
        refreshStatus();
    }

    function setupLaravelReverb() {
        try {
            if (typeof Pusher === 'undefined') {
                setTimeout(setupLaravelReverb, 200);
                return;
            }

            Pusher.logToConsole = true;
            var host = window.location.hostname || 'localhost';

            var pusher = new Pusher('{{ env('REVERB_APP_KEY', 'xkw7qm7eolf62pvnxfqh') }}', {
                wsHost: host,
                wsPort: {{ env('REVERB_PORT', 8080) }},
                wssPort: {{ env('REVERB_PORT', 8080) }},
                forceTLS: false,
                disableStats: true,
                enabledTransports: ['ws', 'wss'],
                cluster: 'mt1'
            });

            pusher.connection.bind('state_change', function(states) {
                console.log('Reverb state:', states.previous, '->', states.current);
                if (states.current === 'connected') {
                    setLive(CC_OK, '● Reverb Real-Time Online', 'Reverb WebSocket Aktif & Siaga');
                } else if (states.current === 'connecting') {
                    setLive(CC_WARN, '● Menghubungkan Reverb...', 'Menghubungkan WebSocket...');
                } else {
                    setLive(CC_BAD, '● Reverb ' + states.current, 'Reverb ' + states.current + ' (cek reverb:start, host & port)');
                }
            });

            pusher.connection.bind('error', function(err) {
                console.warn('Reverb WS error:', err);
                setLive(CC_BAD, '● Reverb error', 'Reverb error (lihat Console)');
            });

            var channel = pusher.subscribe('command-center');

            channel.bind('pusher:subscription_succeeded', function() {
                console.log('✅ Subscribed: command-center');
            });
            channel.bind('pusher:subscription_error', function(status) {
                console.warn('Subscribe error:', status);
                setLive(CC_BAD, '● Subscribe gagal', 'Subscribe command-center gagal (' + status + ')');
            });

            // Tangkap SEMUA event di channel (nama event dari Laravel bisa berupa FQN class atau broadcastAs)
            channel.bind_global(function(eventName, data) {
                if (String(eventName).indexOf('pusher') === 0) return;
                console.log('🚨 REVERB EVENT:', eventName, data);
                if (/created/i.test(eventName)) {
                    handleIncomingIncident((data && data.incident) || data);
                } else {
                    window.manualSyncData();
                }
            });

        } catch (err) {
            console.error('Reverb Setup error:', err);
            setLive(CC_BAD, '● Reverb error', 'Reverb setup error: ' + err.message);
        }
    }

    // 3b. Alarm & notifikasi persisten sampai admin klik "Tangani" (atau status bukan NEW lagi)
    var CC_PENDING_KEY = 'cc_pending_alarms';
    var ccPending = {};
    var ccAlarmTimer = null;

    try {
        ccPending = JSON.parse(localStorage.getItem(CC_PENDING_KEY) || '{}') || {};
    } catch (e) {
        ccPending = {};
    }

    function savePending() {
        try { localStorage.setItem(CC_PENDING_KEY, JSON.stringify(ccPending)); } catch (e) {}
    }

    function hasPending() {
        return Object.keys(ccPending).length > 0;
    }

    function startAlarmLoop() {
        if (ccAlarmTimer || !hasPending()) return;
        window.playEmergencySirenSound();
        ccAlarmTimer = setInterval(function() {
            if (!hasPending()) { stopAlarmLoop(); return; }
            window.playEmergencySirenSound();
        }, 4000);
    }

    function stopAlarmLoop() {
        if (ccAlarmTimer) {
            clearInterval(ccAlarmTimer);
            ccAlarmTimer = null;
        }
    }

    function addPendingAlarm(inc) {
        if (!inc || inc.id === undefined || inc.id === null) return;
        ccPending[inc.id] = {
            id: inc.id,
            ulid: inc.ulid || null,
            incident_no: inc.incident_no || null,
            category: inc.category || null,
            description: inc.description || inc.address_text || null,
            at: Date.now()
        };
        savePending();
        renderAlarmBanner();
        startAlarmLoop();
    }

    function ackPending(id) {
        delete ccPending[id];
        savePending();
        renderAlarmBanner();
        if (!hasPending()) stopAlarmLoop();
    }

    function renderAlarmBanner() {
        var el = document.getElementById('cc-alarm-banner');
        if (!el) {
            el = document.createElement('div');
            el.id = 'cc-alarm-banner';
            document.body.appendChild(el);
            el.addEventListener('click', function(e) {
                var btn = e.target.closest ? e.target.closest('.cc-ack-btn') : null;
                if (!btn) return;
                var id = btn.getAttribute('data-id');
                var item = ccPending[id];
                ackPending(id);
                if (item && item.ulid) {
                    window.location.href = '/command-center/' + encodeURIComponent(item.ulid);
                }
            });
        }

        var ids = Object.keys(ccPending);
        if (!ids.length) {
            el.innerHTML = '';
            return;
        }

        var html = '';
        ids.forEach(function(id) {
            var it = ccPending[id];
            html += '<div class="cc-alarm-card">' +
                '<div class="fw-bold">🚨 LAPORAN DARURAT BARU</div>' +
                '<div class="small">[' + ccEsc(it.incident_no || ('#' + it.id)) + '] ' +
                ccEsc(ccCategoryLabels[it.category] || it.category || 'DARURAT') + ' - ' +
                ccEsc(it.description || 'Lokasi terdeteksi') + '</div>' +
                '<button type="button" class="btn btn-light btn-sm w-100 mt-2 fw-bold cc-ack-btn" data-id="' + ccEsc(id) + '">Tangani</button>' +
                '</div>';
        });
        if (!window._sirenUnlocked) {
            html += '<div class="small text-white bg-dark rounded p-2">Klik di halaman untuk mengaktifkan suara sirene</div>';
        }
        el.innerHTML = html;
    }

    function handleIncomingIncident(inc) {
        if (!inc) return;
        ccKnownIncidentIds.add(inc.id);

        // 1. Alarm & notifikasi persisten sampai ditangani
        if (!inc.status || inc.status === 'NEW') {
            addPendingAlarm(inc);
        }

        // 2. Add marker on map
        drawIncidentMarker(inc);

        // 4. Refresh cards list immediately
        window.manualSyncData();
    }

    // 4. Fallback Polling
    var isSyncing = false;
    var syncCount = 0;

    window.manualSyncData = async function() {
        if (isSyncing) return;
        isSyncing = true;
        var statusEl = document.getElementById('cc-status-text');

        try {
            var res = await fetch('/command-center/data', {
                headers: { 'Accept': 'application/json' }
            });

            if (res.ok) {
                var data = await res.json();
                syncCount++;
                ccSyncText = '';
                refreshStatus();

                if (data.success && Array.isArray(data.incidents)) {
                    var brandNewIncident = null;

                    data.incidents.forEach(function(inc) {
                        if (!ccKnownIncidentIds.has(inc.id)) {
                            ccKnownIncidentIds.add(inc.id);
                            brandNewIncident = inc;
                            if (syncCount > 1 && inc.status === 'NEW') addPendingAlarm(inc);
                        }
                        drawIncidentMarker(inc);
                    });

                    var activeIds = {};
                    data.incidents.forEach(function(inc) { activeIds[inc.id] = inc.status; });
                    Object.keys(ccIncidentData).forEach(function(k) {
                        if (!activeIds[k]) delete ccIncidentData[k];
                    });

                    // Alarm berhenti jika insiden sudah diverifikasi/ditangani (status bukan NEW lagi)
                    Object.keys(ccPending).forEach(function(k) {
                        var st = activeIds[k];
                        var stale = (Date.now() - (ccPending[k].at || 0)) > 30000;
                        if ((st && st !== 'NEW') || (!st && stale)) ackPending(k);
                    });
                    if (hasPending()) startAlarmLoop(); else stopAlarmLoop();


                    renderIncidentCards(data.incidents);
                    renderStats(data.todayStats);

                    if (Array.isArray(data.units)) {
                        data.units.forEach(drawUnitMarker);
                    }
                }
            } else {
                ccSyncText = 'Polling gagal: HTTP ' + res.status;
                refreshStatus();
            }
        } catch (err) {
            console.warn('Sync error:', err);
            ccSyncText = 'Polling error: ' + err.message;
            refreshStatus();
        } finally {
            isSyncing = false;
        }
    };

    function renderIncidentCards(incidents) {
        var container = document.getElementById('incident-list-container');
        var badge = document.getElementById('incident-count');
        if (!container) return;

        if (badge) badge.innerText = incidents.length + ' Laporan';

        if (incidents.length === 0) {
            container.innerHTML = '<div class="text-center py-5 text-muted">' +
                '<i class="ti ti-shield-check fs-1 text-success mb-2"></i>' +
                '<p>Tidak ada insiden darurat aktif saat ini.</p>' +
                '</div>';
            return;
        }

        var html = '';
        incidents.forEach(function(inc) {
            var statusClass = inc.status === 'NEW' ? 'danger' : (inc.status === 'VERIFIED' ? 'warning' : (inc.status === 'RESOLVED' ? 'success' : 'primary'));
            var statusLabel = inc.status === 'NEW' ? 'Laporan Baru' : (inc.status === 'VERIFIED' ? 'Terverifikasi' : (inc.status === 'DISPATCHED' ? 'Armada Bergerak' : inc.status));
            var categoryLabel = inc.category === 'FIRE' ? 'Kebakaran' : (inc.category === 'MEDICAL' ? 'Medis Darurat' : (inc.category === 'TRAFFIC' ? 'Laka Lantas' : (inc.category || 'DARURAT')));

            html += '<div class="card incident-card status-' + inc.status + ' mb-3 p-3" onclick="window.location.href=\'/command-center/' + inc.ulid + '\'">' +
                '<div class="d-flex justify-content-between align-items-start mb-1">' +
                '<span class="fw-bold text-dark fs-6">' + inc.incident_no + '</span>' +
                '<span class="badge bg-' + statusClass + '">' + statusLabel + '</span>' +
                '</div>' +
                '<div class="d-flex align-items-center gap-2 mb-2">' +
                '<span class="badge bg-secondary-subtle text-secondary small">' + categoryLabel + '</span>' +
                (inc.severity ? '<span class="badge bg-danger-subtle text-danger small">Tingkat ' + inc.severity + '</span>' : '') +
                '<small class="text-muted ms-auto">' + (inc.reported_at ? new Date(inc.reported_at).toLocaleTimeString() : 'Baru saja') + '</small>' +
                '</div>' +
                '<p class="text-secondary small mb-2 text-truncate">' +
                (inc.description || inc.address_text || 'Tidak ada keterangan tambahan.') +
                '</p>' +
                '<div class="d-flex justify-content-between align-items-center small text-muted border-top pt-2">' +
                '<span><i class="ti ti-map-pin me-1"></i>' + Number(inc.lat).toFixed(4) + ', ' + Number(inc.lng).toFixed(4) + '</span>' +
                '<span class="text-primary fw-semibold">Lihat Detail <i class="ti ti-chevron-right"></i></span>' +
                '</div>' +
                '</div>';
        });

        container.innerHTML = html;
    }

    function renderStats(stats) {
        if (!stats) return;
        if (document.getElementById('stat-today')) document.getElementById('stat-today').innerText = stats.total ?? 0;
        if (document.getElementById('stat-active')) document.getElementById('stat-active').innerText = stats.active ?? 0;
        if (document.getElementById('stat-critical')) document.getElementById('stat-critical').innerText = stats.critical ?? 0;
        if (document.getElementById('stat-resolved')) document.getElementById('stat-resolved').innerText = stats.resolved ?? 0;
    }

    // Eksekusi
    renderAlarmBanner();
    refreshStatus();
    initMap();
    setupLaravelReverb();
    window.manualSyncData();
    setInterval(window.manualSyncData, 3000);
})();
</script>
@endpush