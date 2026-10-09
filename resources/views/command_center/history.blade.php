@extends('layouts.app')

@section('title', 'Riwayat Kejadian & Arsip Insiden - IECC')

@section('content')
<div class="row g-4 mb-4">
    {{-- Header & Action Bar --}}
    <div class="col-12 d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h3 class="mb-1 fw-bold"><i class="ti ti-history me-2 text-primary"></i>Riwayat & Arsip Kejadian</h3>
            <p class="text-muted mb-0">Daftar rekaman seluruh insiden darurat, penanganan, dan penyelesaian laporan kota</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('command-center.index') }}" class="btn btn-primary d-flex align-items-center gap-2 shadow-sm px-3 py-2">
                <i class="ti ti-broadcast fs-5"></i>
                <span class="fw-bold">Buka Command Center</span>
            </a>
        </div>
    </div>

    {{-- Kartu Statistik Riwayat --}}
    <div class="col-xl-3 col-md-6">
        <div class="card card-lg shadow-sm border-0">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-muted small fw-semibold">TOTAL SELURUH INSIDEN</span>
                    <h2 class="fw-bold mb-0 mt-1">{{ number_format($stats['total']) }}</h2>
                </div>
                <div class="icon-shape icon-lg rounded-circle bg-primary-subtle text-primary">
                    <i class="ti ti-archive fs-3"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card card-lg shadow-sm border-0">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-muted small fw-semibold">SELESAI DITANGANI (RESOLVED)</span>
                    <h2 class="fw-bold mb-0 mt-1 text-success">{{ number_format($stats['resolved']) }}</h2>
                </div>
                <div class="icon-shape icon-lg rounded-circle bg-success-subtle text-success">
                    <i class="ti ti-circle-check fs-3"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card card-lg shadow-sm border-0">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-muted small fw-semibold">DITUTUP RESMI (CLOSED)</span>
                    <h2 class="fw-bold mb-0 mt-1 text-info">{{ number_format($stats['closed']) }}</h2>
                </div>
                <div class="icon-shape icon-lg rounded-circle bg-info-subtle text-info">
                    <i class="ti ti-lock-check fs-3"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card card-lg shadow-sm border-0">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-muted small fw-semibold">DIBATALKAN / TIDAK VALID</span>
                    <h2 class="fw-bold mb-0 mt-1 text-secondary">{{ number_format($stats['cancelled']) }}</h2>
                </div>
                <div class="icon-shape icon-lg rounded-circle bg-secondary-subtle text-secondary">
                    <i class="ti ti-ban fs-3"></i>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Filter Card --}}
<div class="card shadow-sm border-0 mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('incidents.history') }}" class="row g-3">
            <div class="col-md-3">
                <label class="form-label small fw-bold text-muted">Cari Insiden / Alamat</label>
                <div class="input-group">
                    <span class="input-group-text bg-light"><i class="ti ti-search"></i></span>
                    <input type="text" name="search" class="form-control" placeholder="No Insiden, keterangan, alamat..." value="{{ request('search') }}">
                </div>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-bold text-muted">Status</label>
                <select name="status" class="form-select">
                    <option value="">Semua Status</option>
                    <option value="NEW" {{ request('status') === 'NEW' ? 'selected' : '' }}>Laporan Baru (NEW)</option>
                    <option value="VERIFIED" {{ request('status') === 'VERIFIED' ? 'selected' : '' }}>Terverifikasi (VERIFIED)</option>
                    <option value="DISPATCHED" {{ request('status') === 'DISPATCHED' ? 'selected' : '' }}>Armada Bergerak (DISPATCHED)</option>
                    <option value="RESOLVED" {{ request('status') === 'RESOLVED' ? 'selected' : '' }}>Selesai di TKP (RESOLVED)</option>
                    <option value="CLOSED" {{ request('status') === 'CLOSED' ? 'selected' : '' }}>Ditutup Resmi (CLOSED)</option>
                    <option value="FALSE_REPORT" {{ request('status') === 'FALSE_REPORT' ? 'selected' : '' }}>Laporan Palsu / Hoax (FALSE_REPORT)</option>
                    <option value="DUPLICATE" {{ request('status') === 'DUPLICATE' ? 'selected' : '' }}>Duplikat (DUPLICATE)</option>
                    <option value="CANCELLED" {{ request('status') === 'CANCELLED' ? 'selected' : '' }}>Dibatalkan (CANCELLED)</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-bold text-muted">Kategori</label>
                <select name="category" class="form-select">
                    <option value="">Semua Kategori</option>
                    <option value="FIRE" {{ request('category') === 'FIRE' ? 'selected' : '' }}>Kebakaran (FIRE)</option>
                    <option value="MEDICAL" {{ request('category') === 'MEDICAL' ? 'selected' : '' }}>Medis Darurat (MEDICAL)</option>
                    <option value="TRAFFIC" {{ request('category') === 'TRAFFIC' ? 'selected' : '' }}>Laka Lantas (TRAFFIC)</option>
                    <option value="CRIME" {{ request('category') === 'CRIME' ? 'selected' : '' }}>Kriminalitas (CRIME)</option>
                    <option value="DISASTER" {{ request('category') === 'DISASTER' ? 'selected' : '' }}>Bencana Alam (DISASTER)</option>
                    <option value="OTHER" {{ request('category') === 'OTHER' ? 'selected' : '' }}>Lain-lain (OTHER)</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-bold text-muted">Tingkat Darurat</label>
                <select name="severity" class="form-select">
                    <option value="">Semua Tingkat</option>
                    <option value="1" {{ request('severity') == '1' ? 'selected' : '' }}>Tingkat 1 (Rendah)</option>
                    <option value="2" {{ request('severity') == '2' ? 'selected' : '' }}>Tingkat 2 (Sedang)</option>
                    <option value="3" {{ request('severity') == '3' ? 'selected' : '' }}>Tingkat 3 (Tinggi)</option>
                    <option value="4" {{ request('severity') == '4' ? 'selected' : '' }}>Tingkat 4 (Kritis / Darurat)</option>
                </select>
            </div>
            <div class="col-md-3 d-flex align-items-end gap-2">
                <button type="submit" class="btn btn-primary flex-grow-1">
                    <i class="ti ti-filter me-1"></i> Terapkan Filter
                </button>
                <a href="{{ route('incidents.history') }}" class="btn btn-outline-secondary">
                    <i class="ti ti-rotate"></i> Reset
                </a>
            </div>
        </form>
    </div>
</div>

{{-- Tabel Riwayat Insiden --}}
<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4" style="width: 170px;">No. Insiden</th>
                        <th>Kategori & Tingkat</th>
                        <th>Lokasi & Keterangan</th>
                        <th>Armada Terlibat</th>
                        <th>Waktu Lapor / Selesai</th>
                        <th>Status</th>
                        <th class="text-end pe-4" style="width: 120px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($incidents as $inc)
                        @php
                            $statusBg = match($inc->status) {
                                'NEW' => 'danger',
                                'VERIFIED' => 'warning',
                                'DISPATCHED', 'ACCEPTED', 'ARRIVED' => 'primary',
                                'RESOLVED' => 'success',
                                'CLOSED' => 'secondary',
                                'FALSE_REPORT', 'REJECTED' => 'dark',
                                'CANCELLED', 'DUPLICATE' => 'secondary',
                                default => 'light text-dark',
                            };
                        @endphp
                        <tr>
                            <td class="ps-4">
                                <span class="fw-bold font-monospace text-primary">{{ $inc->incident_no }}</span>
                            </td>
                            <td>
                                <div class="d-flex flex-column gap-1">
                                    <span class="badge bg-secondary-subtle text-secondary fw-semibold" style="width: fit-content;">
                                        {{ $inc->category_label }}
                                    </span>
                                    @if($inc->severity)
                                        <span class="badge bg-danger-subtle text-danger" style="width: fit-content;">
                                            Tingkat {{ $inc->severity }}
                                        </span>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <p class="mb-1 fw-semibold text-dark text-truncate" style="max-width: 320px;">
                                    {{ $inc->address_text ?: $inc->description }}
                                </p>
                                <small class="text-muted">
                                    <i class="ti ti-map-pin me-1"></i>{{ number_format($inc->lat, 5) }}, {{ number_format($inc->lng, 5) }}
                                </small>
                            </td>
                            <td>
                                @if($inc->assignments->count() > 0)
                                    <div class="d-flex flex-wrap gap-1">
                                        @foreach($inc->assignments as $asg)
                                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
                                                {{ $asg->unit?->code ?? 'Unit' }} ({{ $asg->status }})
                                            </span>
                                        @endforeach
                                    </div>
                                @else
                                    <span class="text-muted small">-</span>
                                @endif
                            </td>
                            <td>
                                <div class="small">
                                    <div><i class="ti ti-clock me-1 text-muted"></i>{{ $inc->reported_at?->format('d/m/Y H:i') }}</div>
                                    @if($inc->resolved_at)
                                        <div class="text-success"><i class="ti ti-check me-1"></i>{{ $inc->resolved_at?->format('d/m/Y H:i') }}</div>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-{{ $statusBg }}">
                                    {{ $inc->status_label }}
                                </span>
                            </td>
                            <td class="text-end pe-4">
                                <a href="{{ route('command-center.show', $inc->ulid) }}" class="btn btn-sm btn-outline-primary d-inline-flex align-items-center gap-1">
                                    <span>Detail</span>
                                    <i class="ti ti-chevron-right"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="ti ti-database-off fs-1 mb-2 d-block text-secondary"></i>
                                <p class="mb-0">Tidak ada riwayat kejadian yang sesuai dengan kriteria filter pencarian.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($incidents->hasPages())
        <div class="card-footer bg-white border-top py-3">
            <div class="d-flex justify-content-between align-items-center">
                <span class="text-muted small">
                    Menampilkan {{ $incidents->firstItem() ?? 0 }} - {{ $incidents->lastItem() ?? 0 }} dari {{ $incidents->total() }} riwayat insiden
                </span>
                <div>
                    {{ $incidents->links() }}
                </div>
            </div>
        </div>
    @endif
</div>
@endsection
