@extends('layouts.app')

@section('title', 'Manajemen Fasilitas & Pos Layanan')

@section('content')

    <div class="d-flex justify-content-between align-items-center mb-6">
        <div>
            <h4 class="mb-0">Manajemen Fasilitas & Pos Layanan</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Master Fasilitas</li>
                </ol>
            </nav>
        </div>
        @if(can('facilities.create'))
            <a href="{{ route('facilities.create') }}" class="btn btn-primary">
                <i class="ti ti-plus me-1"></i> Tambah Fasilitas
            </a>
        @endif
    </div>

    <div class="card card-lg">
        <div class="card-body p-0">
            {{-- Filter & Search Form --}}
            <div class="p-4 border-bottom bg-light bg-opacity-25">
                <form action="{{ route('facilities.index') }}" method="GET">
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
                                    placeholder="Cari Nama / Alamat / Kontak...">
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
                                <option value="HOSPITAL" {{ request('type') == 'HOSPITAL' ? 'selected' : '' }}>Rumah Sakit (RSUD/RS)</option>
                                <option value="PUSKESMAS" {{ request('type') == 'PUSKESMAS' ? 'selected' : '' }}>Puskesmas</option>
                                <option value="FIRE_STATION" {{ request('type') == 'FIRE_STATION' ? 'selected' : '' }}>Pos Damkar</option>
                                <option value="POLICE_STATION" {{ request('type') == 'POLICE_STATION' ? 'selected' : '' }}>Kantor Polisi / Polsek</option>
                                <option value="OTHER" {{ request('type') == 'OTHER' ? 'selected' : '' }}>Lainnya</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select name="er_status" class="form-select">
                                <option value="">-- Status IGD --</option>
                                <option value="NORMAL" {{ request('er_status') == 'NORMAL' ? 'selected' : '' }}>Normal (Tersedia)</option>
                                <option value="BUSY" {{ request('er_status') == 'BUSY' ? 'selected' : '' }}>Padat (Busy)</option>
                                <option value="FULL" {{ request('er_status') == 'FULL' ? 'selected' : '' }}>Penuh (Full)</option>
                            </select>
                        </div>
                        <div class="col-md-auto">
                            <button type="submit" class="btn btn-primary">
                                Filter
                            </button>
                            @if(request()->anyFilled(['search', 'agency_id', 'type', 'er_status']))
                                <a href="{{ route('facilities.index') }}" class="btn btn-white">
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
                            <th>Nama Fasilitas</th>
                            <th>Tipe & Instansi</th>
                            <th>Kapasitas IGD</th>
                            <th>Status IGD</th>
                            <th>Kontak & Alamat</th>
                            <th>Lokasi GPS</th>
                            <th class="text-end" style="width: 140px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($facilities as $index => $facility)
                            <tr>
                                <td>{{ $facilities->firstItem() + $index }}</td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar avatar-sm rounded bg-light-primary text-primary d-flex align-items-center justify-content-center me-3" style="width: 38px; height: 38px;">
                                            @if($facility->type === 'HOSPITAL')
                                                <i class="ti ti-building-hospital fs-4 text-danger"></i>
                                            @elseif($facility->type === 'PUSKESMAS')
                                                <i class="ti ti-first-aid-kit fs-4 text-success"></i>
                                            @elseif($facility->type === 'FIRE_STATION')
                                                <i class="ti ti-flame fs-4 text-warning"></i>
                                            @elseif($facility->type === 'POLICE_STATION')
                                                <i class="ti ti-shield-check fs-4 text-primary"></i>
                                            @else
                                                <i class="ti ti-building fs-4 text-secondary"></i>
                                            @endif
                                        </div>
                                        <div>
                                            <div class="fw-semibold text-dark">{{ $facility->name }}</div>
                                            @if($facility->units_count > 0)
                                                <span class="badge bg-secondary-subtle text-secondary fs-xs">
                                                    <i class="ti ti-truck me-1"></i> {{ $facility->units_count }} Unit Pangkalan
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    @php
                                        $typeLabels = [
                                            'HOSPITAL' => ['label' => 'Rumah Sakit', 'badge' => 'bg-danger-subtle text-danger border-danger-subtle'],
                                            'PUSKESMAS' => ['label' => 'Puskesmas', 'badge' => 'bg-success-subtle text-success border-success-subtle'],
                                            'FIRE_STATION' => ['label' => 'Pos Damkar', 'badge' => 'bg-warning-subtle text-warning border-warning-subtle'],
                                            'POLICE_STATION' => ['label' => 'Kantor Polisi', 'badge' => 'bg-primary-subtle text-primary border-primary-subtle'],
                                            'OTHER' => ['label' => 'Lainnya', 'badge' => 'bg-secondary-subtle text-secondary border-secondary-subtle'],
                                        ];
                                        $typeInfo = $typeLabels[$facility->type] ?? ['label' => $facility->type, 'badge' => 'bg-light text-dark'];
                                    @endphp
                                    <span class="badge border {{ $typeInfo['badge'] }} mb-1">
                                        {{ $typeInfo['label'] }}
                                    </span>
                                    <div class="text-muted fs-xs">
                                        {{ $facility->agency->name ?? '-' }}
                                    </div>
                                </td>
                                <td>
                                    @if(in_array($facility->type, ['HOSPITAL', 'PUSKESMAS']))
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="fw-bold text-dark fs-5">{{ $facility->er_beds_available }}</div>
                                            <div class="text-muted fs-xs">/ {{ $facility->er_beds_total }} Bed</div>
                                        </div>
                                        <div class="progress mt-1" style="height: 5px; width: 100px;">
                                            @php
                                                $pct = $facility->er_beds_total > 0 ? ($facility->er_beds_available / $facility->er_beds_total) * 100 : 0;
                                                $barColor = $pct > 40 ? 'bg-success' : ($pct > 15 ? 'bg-warning' : 'bg-danger');
                                            @endphp
                                            <div class="progress-bar {{ $barColor }}" style="width: {{ $pct }}%"></div>
                                        </div>
                                    @else
                                        <span class="text-muted fs-xs">-</span>
                                    @endif
                                </td>
                                <td>
                                    @if(in_array($facility->type, ['HOSPITAL', 'PUSKESMAS']))
                                        @if($facility->er_status === 'NORMAL')
                                            <span class="badge bg-success text-white">NORMAL</span>
                                        @elseif($facility->er_status === 'BUSY')
                                            <span class="badge bg-warning text-dark">PADAT</span>
                                        @elseif($facility->er_status === 'FULL')
                                            <span class="badge bg-danger text-white">PENUH</span>
                                        @else
                                            <span class="badge bg-secondary text-white">{{ $facility->er_status }}</span>
                                        @endif
                                    @else
                                        <span class="badge bg-light text-muted border">STANDBY</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="fs-sm">
                                        @if($facility->phone)
                                            <div><i class="ti ti-phone text-muted me-1"></i> <span class="fw-medium">{{ $facility->phone }}</span></div>
                                        @endif
                                        @if($facility->address)
                                            <div class="text-muted text-truncate" style="max-width: 220px;" title="{{ $facility->address }}">
                                                <i class="ti ti-map-pin text-muted me-1"></i> {{ $facility->address }}
                                            </div>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    @if($facility->lat && $facility->lng)
                                        <a href="https://www.google.com/maps?q={{ $facility->lat }},{{ $facility->lng }}" target="_blank" class="btn btn-xs btn-outline-secondary d-inline-flex align-items-center gap-1">
                                            <i class="ti ti-map-pin-filled text-danger"></i>
                                            <span class="fs-xs font-monospace">{{ number_format($facility->lat, 4) }}, {{ number_format($facility->lng, 4) }}</span>
                                        </a>
                                    @else
                                        <span class="text-muted fs-xs font-monospace">Belum diset</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <div class="d-flex justify-content-end gap-1">
                                        @if(can('facilities.update'))
                                            <a href="{{ route('facilities.edit', $facility) }}"
                                                class="btn btn-icon btn-sm btn-ghost-primary"
                                                data-bs-toggle="tooltip"
                                                title="Edit Fasilitas">
                                                <i class="ti ti-pencil fs-5"></i>
                                            </a>
                                        @endif

                                        @if(can('facilities.delete'))
                                            <button type="button"
                                                class="btn btn-icon btn-sm btn-ghost-danger delete-facility-btn"
                                                data-id="{{ $facility->id }}"
                                                data-name="{{ $facility->name }}"
                                                data-bs-toggle="tooltip"
                                                title="Hapus Fasilitas">
                                                <i class="ti ti-trash fs-5"></i>
                                            </button>

                                            <form id="delete-form-{{ $facility->id }}"
                                                action="{{ route('facilities.destroy', $facility) }}"
                                                method="POST"
                                                class="d-none">
                                                @csrf
                                                @method('DELETE')
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">
                                    <div class="d-flex flex-column align-items-center">
                                        <i class="ti ti-building-hospital-off fs-1 text-secondary opacity-50 mb-2"></i>
                                        <p class="mb-0 fw-medium">Belum ada data fasilitas / faskes yang ditemukan.</p>
                                        <small class="text-muted">Gunakan tombol 'Tambah Fasilitas' untuk membuat faskes baru.</small>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($facilities->hasPages())
                <div class="p-4 border-top d-flex justify-content-between align-items-center">
                    <div class="text-muted fs-sm">
                        Menampilkan {{ $facilities->firstItem() }} sampai {{ $facilities->lastItem() }} dari {{ $facilities->total() }} fasilitas
                    </div>
                    <div>
                        {{ $facilities->links() }}
                    </div>
                </div>
            @endif
        </div>
    </div>

@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const deleteButtons = document.querySelectorAll('.delete-facility-btn');
        deleteButtons.forEach(button => {
            button.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                const name = this.getAttribute('data-name');

                Swal.fire({
                    title: 'Hapus Fasilitas?',
                    text: `Apakah Anda yakin ingin menghapus "${name}"?`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Ya, Hapus!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        document.getElementById(`delete-form-${id}`).submit();
                    }
                });
            });
        });
    });
</script>
@endpush
