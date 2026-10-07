@extends('layouts.app')

@section('title', 'Detail Unit Armada - ' . $unit->code)

@section('content')

    <div class="d-flex justify-content-between align-items-center mb-6">
        <div>
            <div class="d-flex align-items-center gap-2">
                <h4 class="mb-0">Unit Armada: {{ $unit->code }}</h4>
                @php
                    $statusBadges = [
                        'AVAILABLE'   => 'bg-success text-white',
                        'BUSY'        => 'bg-danger text-white',
                        'OFFLINE'     => 'bg-secondary text-white',
                        'MAINTENANCE' => 'bg-warning text-dark',
                    ];
                @endphp
                <span class="badge {{ $statusBadges[$unit->status] ?? 'bg-secondary' }}">
                    {{ $unit->status }}
                </span>
                @if($unit->crew_ready)
                    <span class="badge bg-success-subtle text-success border border-success-subtle">
                        <i class="ti ti-check me-1"></i>Regu Siap
                    </span>
                @else
                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle">
                        <i class="ti ti-x me-1"></i>Regu Belum Siap
                    </span>
                @endif
            </div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('units.index') }}">Master Units</a></li>
                    <li class="breadcrumb-item active">{{ $unit->code }}</li>
                </ol>
            </nav>
        </div>
        <div class="d-flex gap-2">
            @if(can('units.edit'))
                <a href="{{ route('units.edit', $unit) }}" class="btn btn-primary">
                    <i class="ti ti-edit me-1"></i> Edit Unit
                </a>
            @endif
            <a href="{{ route('units.index') }}" class="btn btn-white">
                <i class="ti ti-arrow-left me-1"></i> Kembali
            </a>
        </div>
    </div>

    <div class="row">
        {{-- Kolom Kiri: Informasi Unit & Riwayat Tugas --}}
        <div class="col-xl-5">
            <div class="card card-lg mb-4">
                <div class="card-body">
                    <h6 class="mb-4 text-muted text-uppercase" style="font-size:11px; letter-spacing:.05em;">
                        Informasi Spesifikasi Armada
                    </h6>

                    <div class="d-flex align-items-center gap-3 p-3 bg-light rounded-3 mb-4">
                        <div class="p-3 bg-white rounded-3 shadow-sm text-primary fs-3">
                            @if($unit->type === 'AMBULANCE')
                                <i class="ti ti-ambulance"></i>
                            @elseif($unit->type === 'FIRE_TRUCK')
                                <i class="ti ti-flame"></i>
                            @elseif($unit->type === 'POLICE_PATROL')
                                <i class="ti ti-shield"></i>
                            @else
                                <i class="ti ti-truck"></i>
                            @endif
                        </div>
                        <div>
                            <div class="fw-bold fs-5 text-dark">{{ $unit->code }}</div>
                            <div class="text-muted">{{ $unit->agency?->name ?? '-' }}</div>
                        </div>
                    </div>

                    <table class="table table-borderless table-sm mb-0">
                        <tbody>
                            <tr>
                                <td class="text-muted" style="width: 140px;">Tipe Armada</td>
                                <td class="fw-semibold">{{ $unit->type }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Pangkalan (Base)</td>
                                <td class="fw-semibold">{{ $unit->baseFacility?->name ?? '-' }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">ULID Referensi</td>
                                <td class="text-muted font-monospace small">{{ $unit->ulid }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Koordinat Terakhir</td>
                                <td>
                                    @if($unit->lat && $unit->lng)
                                        <span class="badge bg-light text-dark border">
                                            {{ $unit->lat }}, {{ $unit->lng }}
                                        </span>
                                    @else
                                        <span class="text-muted small">Belum ada sinyal GPS</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td class="text-muted">Terakhir Aktif</td>
                                <td>
                                    {{ $unit->last_seen_at ? tgl_indo($unit->last_seen_at) . ' (' . $unit->last_seen_at->diffForHumans() . ')' : '-' }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- 5 Penugasan Terakhir --}}
            <div class="card card-lg mb-4">
                <div class="card-body">
                    <h6 class="mb-4 text-muted text-uppercase" style="font-size:11px; letter-spacing:.05em;">
                        Riwayat Penugasan Terakhir
                    </h6>

                    @forelse($unit->assignments as $assignment)
                        <div class="p-3 border rounded-3 mb-2">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="fw-bold text-dark">
                                    {{ $assignment->incident?->incident_no ?? 'Insiden #' . $assignment->incident_id }}
                                </span>
                                <span class="badge bg-secondary-subtle text-secondary">
                                    {{ $assignment->status }}
                                </span>
                            </div>
                            <div class="text-muted small mb-1">
                                {{ $assignment->incident?->category }} - {{ Str::limit($assignment->incident?->description, 50) }}
                            </div>
                            <small class="text-muted" style="font-size: 11px;">
                                <i class="ti ti-clock me-1"></i>{{ tgl_indo($assignment->dispatched_at ?? $assignment->created_at) }}
                            </small>
                        </div>
                    @empty
                        <div class="text-center text-muted py-3">
                            <small>Belum ada riwayat penugasan insiden untuk unit ini.</small>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Kolom Kanan: Manajemen Anggota Tim (Crew Members) --}}
        <div class="col-xl-7">
            <div class="card card-lg mb-4">
                <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0">Anggota Regu Armada (Crew)</h5>
                        <small class="text-muted">Petugas lapangan yang ditugaskan mengawaki unit ini</small>
                    </div>
                    @if(can('units.edit'))
                        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addMemberModal">
                            <i class="ti ti-user-plus me-1"></i> Tambah Anggota
                        </button>
                    @endif
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-centered mb-0 align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Petugas</th>
                                    <th>Jabatan / Role</th>
                                    <th>Status Tugas</th>
                                    <th class="text-end">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($unit->members as $member)
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <img src="{{ minio_avatar($member->user?->avatar, $member->user?->name ?? 'User') }}"
                                                    alt="{{ $member->user?->name }}"
                                                    class="rounded-circle object-fit-cover border"
                                                    width="36" height="36"
                                                    onerror="this.src='https://ui-avatars.com/api/?name={{ urlencode($member->user?->name ?? 'User') }}&background=0d6efd&color=fff&size=128'" />
                                                <div>
                                                    <div class="fw-semibold text-dark">{{ $member->user?->name ?? 'User Tidak Ditemukan' }}</div>
                                                    <small class="text-muted">{{ $member->user?->email }} • {{ $member->user?->phone ?? '-' }}</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark border">
                                                {{ $member->role }}
                                            </span>
                                        </td>
                                        <td>
                                            @if($member->on_duty)
                                                <span class="badge bg-success-subtle text-success border border-success-subtle">
                                                    <i class="ti ti-point-filled"></i> On Duty
                                                </span>
                                            @else
                                                <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">
                                                    Off Duty
                                                </span>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            <div class="d-inline-flex gap-1">
                                                @if(can('units.edit'))
                                                    <button type="button" class="btn btn-sm btn-white"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#editMemberModal-{{ $member->ulid }}"
                                                        title="Ubah Jabatan/Status">
                                                        <i class="ti ti-edit"></i>
                                                    </button>
                                                @endif
                                                @if(can('units.delete') || can('units.edit'))
                                                    <form action="{{ route('units.members.destroy', [$unit, $member]) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="button" class="btn btn-sm btn-white text-danger"
                                                            data-confirm="Keluarkan {{ $member->user?->name }} dari unit {{ $unit->code }}?"
                                                            title="Keluarkan dari Unit">
                                                            <i class="ti ti-trash"></i>
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>

                                    {{-- Modal Edit Anggota --}}
                                    <div class="modal fade" id="editMemberModal-{{ $member->ulid }}" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <form action="{{ route('units.members.update', [$unit, $member]) }}" method="POST">
                                                    @csrf
                                                    @method('PUT')
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Ubah Status Anggota Tim</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="mb-3">
                                                            <label class="form-label">Nama Petugas</label>
                                                            <input type="text" class="form-control" value="{{ $member->user?->name }}" disabled />
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label">Jabatan di Unit <span class="text-danger">*</span></label>
                                                            <select name="role" class="form-select" required>
                                                                <option value="Leader / Komandan Regu" {{ $member->role == 'Leader / Komandan Regu' ? 'selected' : '' }}>Leader / Komandan Regu</option>
                                                                <option value="Driver / Pengemudi" {{ $member->role == 'Driver / Pengemudi' ? 'selected' : '' }}>Driver / Pengemudi</option>
                                                                <option value="Paramedic / Medis" {{ $member->role == 'Paramedic / Medis' ? 'selected' : '' }}>Paramedic / Medis</option>
                                                                <option value="Dokter" {{ $member->role == 'Dokter' ? 'selected' : '' }}>Dokter</option>
                                                                <option value="Firefighter / Pemadam" {{ $member->role == 'Firefighter / Pemadam' ? 'selected' : '' }}>Firefighter / Pemadam</option>
                                                                <option value="Police Officer" {{ $member->role == 'Police Officer' ? 'selected' : '' }}>Police Officer</option>
                                                                <option value="Rescue Specialist" {{ $member->role == 'Rescue Specialist' ? 'selected' : '' }}>Rescue Specialist</option>
                                                                <option value="Anggota Regu" {{ $member->role == 'Anggota Regu' ? 'selected' : '' }}>Anggota Regu</option>
                                                            </select>
                                                        </div>
                                                        <div class="form-check form-switch mb-3">
                                                            <input class="form-check-input" type="checkbox" name="on_duty" id="on_duty_{{ $member->ulid }}" value="1" {{ $member->on_duty ? 'checked' : '' }}>
                                                            <label class="form-check-label fw-semibold" for="on_duty_{{ $member->ulid }}">
                                                                Sedang Bertugas (On Duty)
                                                            </label>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                                                        <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-5">
                                            <i class="ti ti-users-minus fs-2 text-secondary mb-2 d-block"></i>
                                            Belum ada petugas yang dimasukkan ke unit ini.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Tambah Anggota --}}
    <div class="modal fade" id="addMemberModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('units.members.store', $unit) }}" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Tambah Anggota ke Unit {{ $unit->code }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Pilih Petugas (User) <span class="text-danger">*</span></label>
                            <select name="user_id" class="form-select" required>
                                <option value="">-- Pilih User Petugas --</option>
                                @foreach($availableUsers as $u)
                                    <option value="{{ $u->id }}">
                                        {{ $u->name }} ({{ $u->email }}) - {{ $u->user_type }}
                                    </option>
                                @endforeach
                            </select>
                            <small class="text-muted">Hanya menampilkan user aktif yang belum terdaftar di unit ini.</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Jabatan di Unit <span class="text-danger">*</span></label>
                            <select name="role" class="form-select" required>
                                <option value="Leader / Komandan Regu">Leader / Komandan Regu</option>
                                <option value="Driver / Pengemudi">Driver / Pengemudi</option>
                                <option value="Paramedic / Medis">Paramedic / Medis</option>
                                <option value="Dokter">Dokter</option>
                                <option value="Firefighter / Pemadam">Firefighter / Pemadam</option>
                                <option value="Police Officer">Police Officer</option>
                                <option value="Rescue Specialist">Rescue Specialist</option>
                                <option value="Anggota Regu" selected>Anggota Regu</option>
                            </select>
                        </div>
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" name="on_duty" id="new_on_duty" value="1" checked>
                            <label class="form-check-label fw-semibold" for="new_on_duty">
                                Sedang Bertugas (On Duty)
                            </label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="ti ti-plus me-1"></i> Tambahkan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endsection
