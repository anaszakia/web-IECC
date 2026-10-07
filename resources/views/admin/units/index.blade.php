@extends('layouts.app')

@section('title', 'Manajemen Unit Armada')

@section('content')

    <div class="d-flex justify-content-between align-items-center mb-6">
        <div>
            <h4 class="mb-0">Manajemen Unit Armada</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Master Units</li>
                </ol>
            </nav>
        </div>
        @if(can('units.create'))
            <a href="{{ route('units.create') }}" class="btn btn-primary">
                <i class="ti ti-plus me-1"></i> Tambah Unit
            </a>
        @endif
    </div>

    <div class="card card-lg">
        <div class="card-body p-0">
            {{-- Filter & Search Form --}}
            <div class="p-4 border-bottom bg-light bg-opacity-25">
                <form action="{{ route('units.index') }}" method="GET">
                    <div class="row g-2 align-items-center">
                        <div class="col-md-4">
                            <div class="input-group">
                                <span class="input-group-text bg-white">
                                    <i class="ti ti-search"></i>
                                </span>
                                <input type="text"
                                    name="search"
                                    class="form-control"
                                    value="{{ request('search') }}"
                                    placeholder="Cari Kode Unit (cth: AMB-01)...">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <select name="agency_id" class="form-select">
                                <option value="">-- Semua Instansi --</option>
                                @foreach ($agencies as $agency)
                                    <option value="{{ $agency->id }}" {{ request('agency_id') == $agency->id ? 'selected' : '' }}>
                                        {{ $agency->name }} ({{ $agency->code }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select name="type" class="form-select">
                                <option value="">-- Semua Tipe --</option>
                                <option value="AMBULANCE" {{ request('type') == 'AMBULANCE' ? 'selected' : '' }}>Ambulans</option>
                                <option value="FIRE_TRUCK" {{ request('type') == 'FIRE_TRUCK' ? 'selected' : '' }}>Damkar</option>
                                <option value="POLICE_PATROL" {{ request('type') == 'POLICE_PATROL' ? 'selected' : '' }}>Patroli Polisi</option>
                                <option value="RESCUE_TEAM" {{ request('type') == 'RESCUE_TEAM' ? 'selected' : '' }}>Tim Rescue</option>
                                <option value="TRAFFIC_UNIT" {{ request('type') == 'TRAFFIC_UNIT' ? 'selected' : '' }}>Satlantas</option>
                                <option value="OTHER" {{ request('type') == 'OTHER' ? 'selected' : '' }}>Lainnya</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select name="status" class="form-select">
                                <option value="">-- Status --</option>
                                <option value="AVAILABLE" {{ request('status') == 'AVAILABLE' ? 'selected' : '' }}>Available</option>
                                <option value="BUSY" {{ request('status') == 'BUSY' ? 'selected' : '' }}>Busy</option>
                                <option value="OFFLINE" {{ request('status') == 'OFFLINE' ? 'selected' : '' }}>Offline</option>
                                <option value="MAINTENANCE" {{ request('status') == 'MAINTENANCE' ? 'selected' : '' }}>Maintenance</option>
                            </select>
                        </div>
                        <div class="col-md-auto">
                            <button type="submit" class="btn btn-primary">
                                Filter
                            </button>
                            @if(request()->anyFilled(['search', 'agency_id', 'type', 'status']))
                                <a href="{{ route('units.index') }}" class="btn btn-white">
                                    Reset
                                </a>
                            @endif
                        </div>
                    </div>
                </form>
            </div>

            <div class="table-responsive">
                <table class="table table-hover table-centered mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 50px;">#</th>
                            <th>Kode Unit</th>
                            <th>Instansi & Pangkalan</th>
                            <th>Tipe Armada</th>
                            <th>Status Kesiapan</th>
                            <th>Crew / Petugas</th>
                            <th>Lokasi GPS</th>
                            <th class="text-end" style="width: 140px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($units as $unit)
                            <tr>
                                <td>{{ $units->firstItem() + $loop->index }}</td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="p-2 rounded-2 bg-primary-subtle text-primary fw-bold fs-6">
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
                                            <a href="{{ route('units.show', $unit) }}" class="fw-bold text-dark text-decoration-none hover-primary">
                                                {{ $unit->code }}
                                            </a>
                                            <div class="text-muted font-monospace" style="font-size: 11px;">{{ $unit->ulid }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="fw-medium text-dark">{{ $unit->agency?->name ?? '-' }}</div>
                                    <small class="text-muted">
                                        <i class="ti ti-building me-1"></i>{{ $unit->baseFacility?->name ?? 'Pangkalan Tidak Diatur' }}
                                    </small>
                                </td>
                                <td>
                                    @php
                                        $typeBadges = [
                                            'AMBULANCE'     => ['bg' => 'bg-danger-subtle text-danger', 'label' => 'Ambulans', 'icon' => 'ti-ambulance'],
                                            'FIRE_TRUCK'    => ['bg' => 'bg-warning-subtle text-warning-emphasis', 'label' => 'Damkar', 'icon' => 'ti-flame'],
                                            'POLICE_PATROL' => ['bg' => 'bg-info-subtle text-info-emphasis', 'label' => 'Patroli Polisi', 'icon' => 'ti-shield'],
                                            'RESCUE_TEAM'   => ['bg' => 'bg-success-subtle text-success', 'label' => 'Rescue Team', 'icon' => 'ti-lifebuoy'],
                                            'TRAFFIC_UNIT'  => ['bg' => 'bg-primary-subtle text-primary', 'label' => 'Satlantas', 'icon' => 'ti-traffic-cone'],
                                            'OTHER'         => ['bg' => 'bg-secondary-subtle text-secondary', 'label' => 'Lainnya', 'icon' => 'ti-truck'],
                                        ];
                                        $badge = $typeBadges[$unit->type] ?? $typeBadges['OTHER'];
                                    @endphp
                                    <span class="badge {{ $badge['bg'] }} px-2 py-1">
                                        <i class="ti {{ $badge['icon'] }} me-1"></i>{{ $badge['label'] }}
                                    </span>
                                </td>
                                <td>
                                    @php
                                        $statusBadges = [
                                            'AVAILABLE'   => 'bg-success text-white',
                                            'BUSY'        => 'bg-danger text-white',
                                            'OFFLINE'     => 'bg-secondary text-white',
                                            'MAINTENANCE' => 'bg-warning text-dark',
                                        ];
                                    @endphp
                                    <div class="d-flex flex-column gap-1 align-items-start">
                                        <span class="badge {{ $statusBadges[$unit->status] ?? 'bg-secondary' }}">
                                            {{ $unit->status }}
                                        </span>
                                        @if($unit->crew_ready)
                                            <small class="badge bg-success-subtle text-success border border-success-subtle">
                                                <i class="ti ti-check me-1"></i>Regu Siap
                                            </small>
                                        @else
                                            <small class="badge bg-danger-subtle text-danger border border-danger-subtle">
                                                <i class="ti ti-x me-1"></i>Regu Belum Siap
                                            </small>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <a href="{{ route('units.show', $unit) }}" class="btn btn-sm btn-outline-secondary rounded-pill px-3">
                                        <i class="ti ti-users me-1"></i> {{ $unit->members_count }} Anggota
                                    </a>
                                </td>
                                <td>
                                    @if($unit->lat && $unit->lng)
                                        <div class="text-nowrap small">
                                            <i class="ti ti-map-pin text-danger me-1"></i>{{ number_format($unit->lat, 4) }}, {{ number_format($unit->lng, 4) }}
                                        </div>
                                        @if($unit->last_seen_at)
                                            <small class="text-muted d-block" style="font-size: 11px;">
                                                Aktif {{ $unit->last_seen_at->diffForHumans() }}
                                            </small>
                                        @endif
                                    @else
                                        <span class="text-muted small">Belum ada sinyal GPS</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <div class="d-inline-flex gap-1">
                                        @if(can('units.view'))
                                            <a href="{{ route('units.show', $unit) }}"
                                                class="btn btn-sm btn-white" title="Detail & Atur Anggota">
                                                <i class="ti ti-eye"></i>
                                            </a>
                                        @endif
                                        @if(can('units.edit'))
                                            <a href="{{ route('units.edit', $unit) }}"
                                                class="btn btn-sm btn-white" title="Edit Unit">
                                                <i class="ti ti-edit"></i>
                                            </a>
                                        @endif
                                        @if(can('units.delete'))
                                            <form action="{{ route('units.destroy', $unit) }}" method="POST" class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="button"
                                                    class="btn btn-sm btn-white text-danger"
                                                    data-confirm="Apakah Anda yakin ingin menghapus unit armada {{ $unit->code }}?"
                                                    title="Hapus">
                                                    <i class="ti ti-trash"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-6">
                                    <div class="py-4">
                                        <i class="ti ti-truck-off fs-1 text-secondary mb-2 d-block"></i>
                                        @if(request()->anyFilled(['search', 'agency_id', 'type', 'status']))
                                            <p class="mb-2">Tidak ditemukan unit dengan kriteria filter tersebut.</p>
                                            <a href="{{ route('units.index') }}" class="btn btn-sm btn-outline-primary">Reset Filter</a>
                                        @else
                                            <p class="mb-2">Belum ada unit armada yang terdaftar di sistem.</p>
                                            @if(can('units.create'))
                                                <a href="{{ route('units.create') }}" class="btn btn-sm btn-primary">
                                                    <i class="ti ti-plus me-1"></i> Tambah Unit Pertama
                                                </a>
                                            @endif
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($units->hasPages())
                <div class="px-4 py-3 border-top">
                    {{ $units->links() }}
                </div>
            @endif
        </div>
    </div>

@endsection
