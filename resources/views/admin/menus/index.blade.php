@extends('layouts.app')

@section('title', 'Menu Management')

@section('content')

    <div class="d-flex justify-content-between align-items-center mb-6">
        <div>
            <h4 class="mb-0">Menu Management</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Menus</li>
                </ol>
            </nav>
        </div>
        <a href="{{ route('menus.create') }}" class="btn btn-primary">
            <i class="ti ti-plus me-1"></i> Tambah Menu
        </a>
    </div>

    <div class="card card-lg">
        <div class="card-body p-0">
            <div class="p-4 border-bottom">
                <form action="{{ route('menus.index') }}" method="GET">
                    <div class="row g-2 align-items-center">
                        <div class="col-md-8 col-lg-6">
                            <div class="input-group">
                                <span class="input-group-text bg-white">
                                    <i class="ti ti-search"></i>
                                </span>
                                <input type="text"
                                    name="search"
                                    class="form-control"
                                    value="{{ request('search') }}"
                                    placeholder="Cari nama menu, URL, atau icon">
                            </div>
                        </div>
                        <div class="col-md-auto">
                            <button type="submit" class="btn btn-primary">
                                Cari
                            </button>
                            @if(request('search'))
                                <a href="{{ route('menus.index') }}" class="btn btn-white">
                                    Reset
                                </a>
                            @endif
                        </div>
                    </div>
                </form>
            </div>

            <div class="table-responsive">
                <table class="table table-hover table-centered mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Nama Menu</th>
                            <th>Icon</th>
                            <th>URL</th>
                            <th>Parent</th>
                            <th>Urutan</th>
                            <th>Role Akses</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($menus as $menu)
                            <tr>
                                <td>{{ $menus->firstItem() + $loop->index }}</td>
                                <td>
                                    <strong>{{ $menu->name }}</strong>
                                    @if ($menu->children_count > 0)
                                        <span class="badge bg-info-subtle text-info-emphasis ms-1">
                                            {{ $menu->children_count }} sub-menu
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    @if ($menu->icon)
                                        <i class="{{ $menu->icon }} fs-5"></i>
                                        <small class="text-muted ms-1">{{ $menu->icon }}</small>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    <code>{{ $menu->url ?? '-' }}</code>
                                </td>
                                <td>{{ $menu->parent?->name ?? '-' }}</td>
                                <td>{{ $menu->order }}</td>
                                <td>
                                    @forelse ($menu->roles as $role)
                                        <span class="badge bg-primary-subtle text-primary-emphasis">
                                            {{ $role->name }}
                                        </span>
                                    @empty
                                        <span class="text-muted">Semua</span>
                                    @endforelse
                                </td>
                                <td>
                                    @if ($menu->is_active)
                                        <span class="badge bg-success-subtle text-success-emphasis">Aktif</span>
                                    @else
                                        <span class="badge bg-danger-subtle text-danger-emphasis">Nonaktif</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="d-flex gap-2">
                                        <a href="{{ route('menus.edit', $menu) }}"
                                            class="btn btn-sm btn-white">
                                            <i class="ti ti-edit"></i>
                                        </a>
                                        <form action="{{ route('menus.destroy', $menu) }}" method="POST">
                                            @csrf
                                            @method('DELETE')
                                            <button type="button" class="btn btn-sm btn-white text-danger"
                                                data-confirm="Yakin hapus menu {{ $menu->name }}?">
                                                <i class="ti ti-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>

                            {{-- Sub menus --}}
                            @foreach ($menu->children as $child)
                                <tr class="table-light">
                                    <td></td>
                                    <td class="ps-6">
                                        <i class="ti ti-corner-down-right text-muted me-1"></i>
                                        {{ $child->name }}
                                    </td>
                                    <td>
                                        @if ($child->icon)
                                            <i class="{{ $child->icon }}"></i>
                                            <small class="text-muted ms-1">{{ $child->icon }}</small>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td><code>{{ $child->url ?? '-' }}</code></td>
                                    <td>{{ $menu->name }}</td>
                                    <td>{{ $child->order }}</td>
                                    <td>
                                        @forelse ($child->roles as $role)
                                            <span class="badge bg-primary-subtle text-primary-emphasis">
                                                {{ $role->name }}
                                            </span>
                                        @empty
                                            <span class="text-muted">Semua</span>
                                        @endforelse
                                    </td>
                                    <td>
                                        @if ($child->is_active)
                                            <span class="badge bg-success-subtle text-success-emphasis">Aktif</span>
                                        @else
                                            <span class="badge bg-danger-subtle text-danger-emphasis">Nonaktif</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="d-flex gap-2">
                                            <a href="{{ route('menus.edit', $child) }}"
                                                class="btn btn-sm btn-white">
                                                <i class="ti ti-edit"></i>
                                            </a>
                                            <form action="{{ route('menus.destroy', $menu) }}" method="POST">
                                                @csrf
                                                @method('DELETE')
                                                <button type="button" class="btn btn-sm btn-white text-danger"
                                                    data-confirm="Yakin hapus menu {{ $menu->name }}?">
                                                    <i class="ti ti-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach

                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted py-6">
                                    @if(request('search'))
                                        Menu tidak ditemukan.
                                    @else
                                        Belum ada menu. <a href="{{ route('menus.create') }}">Tambah sekarang</a>
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($menus->hasPages())
                <div class="px-4 py-3 border-top">
                    {{ $menus->links() }}
                </div>
            @endif
        </div>
    </div>

@endsection
