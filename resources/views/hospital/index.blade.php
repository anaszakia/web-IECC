@extends('layouts.app')

@section('title', 'Hospital Emergency Portal - IECC')

@push('styles')
<style>
    @keyframes pulse-ring {
        0%, 100% { box-shadow: 0 0 0 0 rgba(220, 53, 69, .7); }
        50%      { box-shadow: 0 0 0 10px rgba(220, 53, 69, 0); }
    }
    .pulse-animation {
        animation: pulse-ring 1.8s infinite cubic-bezier(0.45, 0, 0.55, 1);
    }
    #hospital-alarm-banner {
        position: fixed;
        bottom: 24px;
        right: 24px;
        z-index: 9999;
        display: flex;
        flex-direction: column;
        gap: 12px;
        max-width: 380px;
    }
    .hospital-alarm-card {
        background: #dc3545;
        color: #fff;
        padding: 16px;
        border-radius: 12px;
        box-shadow: 0 10px 25px rgba(220, 53, 69, 0.5);
        border: 2px solid #fff;
        animation: pulse-ring 1.5s infinite;
    }
</style>
@endpush

@section('content')
<script>
// =========================================================================
// 1. WEB AUDIO API & ALARM NOTIFICATION ENGINE FOR HOSPITAL IGD PORTAL
// =========================================================================
window.playHospitalEmergencyAlarm = function() {
    try {
        var AudioCtx = window.AudioContext || window.webkitAudioContext;
        if (AudioCtx) {
            var ctx = window._hospAudioCtx || (window._hospAudioCtx = new AudioCtx());
            if (ctx.state === 'suspended') {
                ctx.resume();
            }

            var master = ctx.createGain();
            master.gain.setValueAtTime(0.6, ctx.currentTime);
            master.connect(ctx.destination);

            var start = ctx.currentTime + 0.02;
            var tones = [880, 1100, 880, 1100, 880, 1100];
            var step = 0.3;

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

window.testHospitalEmergencySound = function() {
    window.playHospitalEmergencyAlarm();
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'warning',
            title: '🚨 Tes Sirene IGD RS',
            text: 'Sirene darurat IGD aktif & siap membunyikan alarm saat ada pasien darurat masuk.',
            timer: 3500,
            showConfirmButton: false,
            background: '#dc3545',
            color: '#fff',
            iconColor: '#fff',
        });
    }
};

// Unlock Audio saat ada interaksi pertama
['click', 'keydown', 'touchstart'].forEach(function(ev) {
    document.addEventListener(ev, function() {
        var AC = window.AudioContext || window.webkitAudioContext;
        if (AC && !window._hospAudioCtx) {
            try { window._hospAudioCtx = new AC(); } catch (e) {}
        }
        if (window._hospAudioCtx && window._hospAudioCtx.state === 'suspended') {
            window._hospAudioCtx.resume().catch(function(){});
        }
        window._hospAudioUnlocked = true;
    }, { passive: true });
});
</script>

<div class="row g-4 mb-4">
    <div class="col-12 d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h3 class="mb-1 fw-bold"><i class="ti ti-building-hospital me-2 text-primary"></i>Hospital Pre-Arrival Portal</h3>
            <div class="d-flex align-items-center gap-2">
                <span class="text-muted" id="hosp-name-text">{{ $hospital ? $hospital->name : 'Portal IGD Rumah Sakit' }}</span>
                @if($hospital)
                    <span class="badge bg-secondary-subtle text-secondary small">{{ $hospital->type }}</span>
                @endif
            </div>
        </div>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            @if($isSuperAdmin && $allFacilities->count() > 1)
                <form action="{{ route('hospital.index') }}" method="GET" class="d-inline-block me-2">
                    <select name="facility_id" class="form-select form-select-sm" onchange="this.form.submit()">
                        @foreach($allFacilities as $fac)
                            <option value="{{ $fac->id }}" {{ ($hospital?->id == $fac->id) ? 'selected' : '' }}>
                                🏥 {{ $fac->name }} ({{ $fac->type }})
                            </option>
                        @endforeach
                    </select>
                </form>
            @endif

            <span id="hosp-status-badge" class="badge bg-{{ $hospital?->er_status === 'NORMAL' ? 'success' : ($hospital?->er_status === 'BUSY' ? 'warning' : 'danger') }} fs-6 px-3 py-2">
                Status IGD: {{ $hospital?->er_status ?? 'NORMAL' }}
            </span>
            <button type="button" class="btn btn-danger btn-sm d-flex align-items-center gap-2 shadow-sm px-3 py-2" onclick="window.testHospitalEmergencySound()">
                <i class="ti ti-volume"></i>
                <span class="fw-bold">Tes Sirene IGD</span>
            </button>
            <button type="button" class="btn btn-outline-secondary btn-sm px-3 py-2" onclick="window.syncHospitalData()">
                <i class="ti ti-refresh me-1"></i> Sinkronisasi
            </button>
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
                    <h2 class="fw-bold mb-0 text-primary" id="er-beds-available-stat">{{ $hospital?->er_beds_available ?? 0 }}</h2>
                    <span class="text-muted" id="er-beds-total-stat">/ {{ $hospital?->er_beds_total ?? 0 }} Total Bed</span>
                </div>
                <div class="progress mt-3" style="height: 8px;">
                    @php
                        $percentage = $hospital && $hospital->er_beds_total > 0 ? round(($hospital->er_beds_available / $hospital->er_beds_total) * 100) : 0;
                    @endphp
                    <div id="er-progress-bar" class="progress-bar bg-success" style="width: {{ $percentage }}%"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-4 col-md-6">
        <div class="card card-lg shadow-sm border-0">
            <div class="card-body">
                <span class="text-muted small fw-semibold">PASIEN MASUK (INCOMING)</span>
                <div class="d-flex align-items-baseline gap-2 mt-2">
                    <h2 class="fw-bold mb-0 text-danger" id="incoming-count-stat">{{ $incomingPatients->count() }}</h2>
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
                <span class="badge bg-danger pulse-animation" id="incoming-badge">{{ $incomingPatients->count() }} Pasien Incoming</span>
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
                    <tbody id="incoming-tbody">
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
                    <tbody id="received-tbody">
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

@push('scripts')
<script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
<script>
(function() {
    'use strict';

    var knownHandoverUlids = new Set([
        @foreach($incomingPatients as $p)
            '{{ $p->ulid }}',
        @endforeach
    ]);

    var isHospSyncing = false;
    var hospSyncCount = 0;
    var hospAlarmInterval = null;

    function startHospAlarmLoop() {
        if (hospAlarmInterval) return;
        window.playHospitalEmergencyAlarm();
        hospAlarmInterval = setInterval(function() {
            var banner = document.getElementById('hospital-alarm-banner');
            if (!banner || !banner.children.length) {
                clearInterval(hospAlarmInterval);
                hospAlarmInterval = null;
                return;
            }
            window.playHospitalEmergencyAlarm();
        }, 4000);
    }

    function showHandoverAlarmBanner(patient) {
        var banner = document.getElementById('hospital-alarm-banner');
        if (!banner) {
            banner = document.createElement('div');
            banner.id = 'hospital-alarm-banner';
            document.body.appendChild(banner);
        }

        var ulid = patient.ulid || String(patient.id || Math.random());
        var existing = document.getElementById('alarm-handover-' + ulid);
        if (existing) return;

        var card = document.createElement('div');
        card.className = 'hospital-alarm-card';
        card.id = 'alarm-handover-' + ulid;
        
        var incNo = (patient.incident && patient.incident.incident_no) ? patient.incident.incident_no : (patient.incident_no || 'DARURAT');
        var unitCode = (patient.unit && patient.unit.code) ? patient.unit.code : ((patient.assignment && patient.assignment.unit) ? patient.assignment.unit.code : 'Ambulans');
        var cond = patient.condition_text || 'Rujukan pra-kedatangan pasien IGD baru masuk dari armada.';

        card.innerHTML = 
            '<div class="d-flex justify-content-between align-items-center mb-1">' +
                '<strong class="fs-6">🚨 PASIEN DARURAT MENUJU IGD</strong>' +
                '<span class="badge bg-light text-danger">ETA: ' + (patient.eta_seconds ? Math.ceil(patient.eta_seconds / 60) + ' Mnt' : 'Segera') + '</span>' +
            '</div>' +
            '<div class="small mb-2">' +
                '<strong>[' + unitCode + ' - ' + incNo + ']</strong> ' + cond +
            '</div>' +
            '<button type="button" class="btn btn-light btn-sm w-100 fw-bold text-danger" onclick="window.location.reload();">Tutup Notifikasi, Matikan Alarm & Refresh Halaman</button>';

        banner.appendChild(card);
        startHospAlarmLoop();
    }

    var currentFacilityId = '{{ $hospital?->id }}';

    window.syncHospitalData = async function() {
        if (isHospSyncing) return;
        isHospSyncing = true;

        try {
            var url = '{{ route('hospital.data') }}' + (currentFacilityId ? '?facility_id=' + currentFacilityId : '');
            var res = await fetch(url, {
                headers: { 'Accept': 'application/json' }
            });

            if (res.ok) {
                var json = await res.json();
                hospSyncCount++;

                if (json.success) {
                    // Update stats
                    var count = json.incoming_count || 0;
                    var incomingBadge = document.getElementById('incoming-badge');
                    var incomingStat = document.getElementById('incoming-count-stat');
                    if (incomingBadge) incomingBadge.innerText = count + ' Pasien Incoming';
                    if (incomingStat) incomingStat.innerText = count;

                    // Update Kapasitas IGD
                    if (json.hospital) {
                        var availEl = document.getElementById('er-beds-available-stat');
                        var totalEl = document.getElementById('er-beds-total-stat');
                        var progEl = document.getElementById('er-progress-bar');
                        var statusEl = document.getElementById('hosp-status-badge');

                        if (availEl) availEl.innerText = json.hospital.er_beds_available ?? 0;
                        if (totalEl) totalEl.innerText = '/ ' + (json.hospital.er_beds_total ?? 0) + ' Total Bed';
                        if (progEl && json.hospital.er_beds_total > 0) {
                            var pct = Math.round(((json.hospital.er_beds_available || 0) / json.hospital.er_beds_total) * 100);
                            progEl.style.width = pct + '%';
                        }
                        if (statusEl) {
                            var st = json.hospital.er_status || 'NORMAL';
                            var cls = st === 'NORMAL' ? 'success' : (st === 'BUSY' ? 'warning' : 'danger');
                            statusEl.className = 'badge bg-' + cls + ' fs-6 px-3 py-2';
                            statusEl.innerText = 'Status IGD: ' + st;
                        }
                    }

                    // Check incoming new handovers
                    if (Array.isArray(json.incoming)) {
                        json.incoming.forEach(function(p) {
                            if (!knownHandoverUlids.has(p.ulid)) {
                                knownHandoverUlids.add(p.ulid);
                                if (hospSyncCount > 1) {
                                    showHandoverAlarmBanner(p);
                                }
                            }
                        });
                        renderIncomingTable(json.incoming);
                    }

                    if (Array.isArray(json.received_history)) {
                        renderReceivedTable(json.received_history);
                    }
                }
            }
        } catch (e) {
            console.log('Hospital sync error:', e);
        } finally {
            isHospSyncing = false;
        }
    };

    function renderIncomingTable(items) {
        var tbody = document.getElementById('incoming-tbody');
        if (!tbody) return;

        if (!items.length) {
            tbody.innerHTML = '<tr><td colspan="7" class="text-center py-5 text-muted">' +
                '<i class="ti ti-circle-check fs-1 text-success mb-2"></i>' +
                '<p>Tidak ada pasien darurat yang sedang dalam perjalanan menuju IGD saat ini.</p>' +
                '</td></tr>';
            return;
        }

        var html = '';
        items.forEach(function(p) {
            var incNo = p.incident ? p.incident.incident_no : '-';
            var sev = (p.incident && p.incident.severity) ? 'Level ' + p.incident.severity : 'Urgent';
            var unitCode = (p.assignment && p.assignment.unit) ? p.assignment.unit.code : 'Ambulans';
            var agencyName = (p.assignment && p.assignment.agency) ? p.assignment.agency.name : '';
            var cond = p.condition_text || 'Tidak ada catatan kondisi spesifik.';
            var avpu = p.consciousness || 'ALERT';
            var avpuClass = avpu === 'ALERT' ? 'success' : (avpu === 'UNRESPONSIVE' ? 'danger' : 'warning');
            var age = (p.age_estimate ? p.age_estimate + ' Thn' : 'N/A') + ' (' + (p.gender || '-') + ')';
            var eta = p.eta_seconds ? '~' + Math.ceil(p.eta_seconds / 60) + ' mnt' : 'Segera Tiba';
            var timeAgo = p.notified_at ? new Date(p.notified_at).toLocaleTimeString() : '';

            var csrf = '{{ csrf_token() }}';
            var actionUrl = '/hospital-portal/received/' + p.ulid;

            html += '<tr>' +
                '<td>' +
                    '<span class="fw-bold text-dark">' + incNo + '</span><br>' +
                    '<span class="badge bg-danger-subtle text-danger small">' + sev + '</span>' +
                '</td>' +
                '<td>' +
                    '<span class="fw-semibold">' + unitCode + '</span><br>' +
                    '<small class="text-muted">' + agencyName + '</small>' +
                '</td>' +
                '<td style="white-space: normal; max-width: 250px;">' +
                    '<span class="text-dark small">' + cond + '</span>' +
                '</td>' +
                '<td>' +
                    '<span class="badge bg-' + avpuClass + '">' + avpu + '</span>' +
                '</td>' +
                '<td>' + age + '</td>' +
                '<td>' +
                    '<span class="fw-semibold text-primary">' + eta + '</span><br>' +
                    '<small class="text-muted">' + timeAgo + '</small>' +
                '</td>' +
                '<td>' +
                    '<form action="' + actionUrl + '" method="POST">' +
                        '<input type="hidden" name="_token" value="' + csrf + '">' +
                        '<button type="submit" class="btn btn-sm btn-success">' +
                            '<i class="ti ti-check me-1"></i> Konfirmasi RECEIVED' +
                        '</button>' +
                    '</form>' +
                '</td>' +
            '</tr>';
        });

        tbody.innerHTML = html;
    }

    function renderReceivedTable(items) {
        var tbody = document.getElementById('received-tbody');
        if (!tbody) return;

        if (!items.length) {
            tbody.innerHTML = '<tr><td colspan="5" class="text-center py-3 text-muted">Belum ada riwayat pasien yang diterima hari ini.</td></tr>';
            return;
        }

        var html = '';
        items.forEach(function(h) {
            var incNo = h.incident ? h.incident.incident_no : '-';
            var unitCode = (h.assignment && h.assignment.unit) ? h.assignment.unit.code : '-';
            var cond = h.condition_text || '-';
            var receiver = h.receiver ? h.receiver.name : 'Petugas IGD';
            var receivedAt = h.received_at ? new Date(h.received_at).toLocaleTimeString() : '-';

            html += '<tr>' +
                '<td class="fw-semibold">' + incNo + '</td>' +
                '<td>' + unitCode + '</td>' +
                '<td style="white-space: normal; max-width: 250px;">' + cond + '</td>' +
                '<td>' + receiver + '</td>' +
                '<td>' + receivedAt + '</td>' +
            '</tr>';
        });

        tbody.innerHTML = html;
    }

    // =========================================================================
    // 2. LARAVEL REVERB WEBSOCKET CONNECTION
    // =========================================================================
    function setupHospitalReverb() {
        try {
            if (typeof Pusher === 'undefined') {
                setTimeout(setupHospitalReverb, 200);
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

            var channelHosp = pusher.subscribe('hospital-portal');
            var channelCC = pusher.subscribe('command-center');

            channelHosp.bind_global(function(eventName, data) {
                if (String(eventName).indexOf('pusher') === 0) return;
                console.log('🚨 REVERB HOSPITAL EVENT:', eventName, data);
                if (data) {
                    // Hanya bunyikan sirene / tampilkan banner jika rujukan ditujukan ke faskes yang sedang dibuka
                    if (!currentFacilityId || String(data.facility_id) === String(currentFacilityId)) {
                        showHandoverAlarmBanner(data);
                    }
                }
                window.syncHospitalData();
            });

            channelCC.bind_global(function(eventName, data) {
                if (String(eventName).indexOf('pusher') === 0) return;
                if (/handover/i.test(eventName)) {
                    console.log('🚨 REVERB HANDOVER ON CC:', eventName, data);
                    if (data) {
                        if (!currentFacilityId || String(data.facility_id) === String(currentFacilityId)) {
                            showHandoverAlarmBanner(data);
                        }
                    }
                    window.syncHospitalData();
                }
            });

            console.log('✅ Reverb WebSocket Subscribed: hospital-portal & command-center');
        } catch (err) {
            console.error('Reverb Setup error on Hospital Portal:', err);
        }
    }

    // Inisialisasi awal sinkronisasi data langsung
    window.syncHospitalData();

    // Inisialisasi Reverb dan backup Fast Polling setiap 3 detik
    setupHospitalReverb();
    setInterval(window.syncHospitalData, 3000);
})();
</script>
@endpush
@endsection

