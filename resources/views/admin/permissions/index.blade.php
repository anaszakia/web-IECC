@extends('layouts.app')

@section('title', 'Permission Management')

@section('content')

    <div class="d-flex justify-content-between align-items-center mb-6">
        <div>
            <h4 class="mb-0">Permission Management</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Permissions</li>
                </ol>
            </nav>
        </div>
        <div class="d-flex gap-2">
            <form action="{{ route('permissions.sync-routes') }}" method="POST" onsubmit="return confirm('Pindai semua route dan sinkronkan permission ke database?')">
                @csrf
                <button type="submit" class="btn btn-outline-primary">
                    <i class="ti ti-refresh me-1"></i> Sync dari Route
                </button>
            </form>
            <a href="{{ route('permissions.create') }}" class="btn btn-primary">
                <i class="ti ti-plus me-1"></i> Tambah Permission
            </a>
        </div>
    </div>

    <div class="card card-lg">
        <div class="card-body p-0">
            <div class="p-4 border-bottom">
                <form action="{{ route('permissions.index') }}" method="GET">
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
                                    placeholder="Cari nama permission atau slug">
                            </div>
                        </div>
                        <div class="col-md-auto">
                            <button type="submit" class="btn btn-primary">
                                Cari
                            </button>
                            @if(request('search'))
                                <a href="{{ route('permissions.index') }}" class="btn btn-white">
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
                            <th>Nama Permission</th>
                            <th>Slug</th>
                            <th>Role yang Memiliki</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($permissions as $permission)
                            <tr>
                                <td>{{ $permissions->firstItem() + $loop->index }}</td>
                                <td>
                                    <strong>{{ $permission->name }}</strong>
                                </td>
                                <td>
                                    <code class="bg-light px-2 py-1 rounded">{{ $permission->slug }}</code>
                                </td>
                                <td>
                                    @forelse ($permission->roles as $role)
                                        <span class="badge bg-primary-subtle text-primary-emphasis me-1">
                                            {{ $role->name }}
                                        </span>
                                    @empty
                                        <span class="text-muted">Tidak ada role</span>
                                    @endforelse
                                </td>
                                <td>
                                    <div class="d-flex gap-2">
                                        <a href="{{ route('permissions.edit', $permission) }}"
                                            class="btn btn-sm btn-white">
                                            <i class="ti ti-edit"></i>
                                        </a>
                                        <form action="{{ route('permissions.destroy', $permission) }}" method="POST">
                                            @csrf
                                            @method('DELETE')
                                            <button type="button" class="btn btn-sm btn-white text-danger"
                                                data-confirm="Yakin hapus permission {{ $permission->name }}?">
                                                <i class="ti ti-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-6">
                                    @if(request('search'))
                                        Permission tidak ditemukan.
                                    @else
                                        Belum ada permission.
                                        <a href="{{ route('permissions.create') }}">Tambah sekarang</a>
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($permissions->hasPages())
                <div class="px-4 py-3 border-top">
                    {{ $permissions->links() }}
                </div>
            @endif
        </div>
    </div>

@endsection
