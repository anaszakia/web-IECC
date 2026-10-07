@extends('layouts.app')

@section('title', 'Role Management')

@section('content')

    <div class="d-flex justify-content-between align-items-center mb-6">
        <div>
            <h4 class="mb-0">Role Management</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Roles</li>
                </ol>
            </nav>
        </div>
        <a href="{{ route('roles.create') }}" class="btn btn-primary">
            <i class="ti ti-plus me-1"></i> Tambah Role
        </a>
    </div>

    <div class="card card-lg">
        <div class="card-body p-0">
            <div class="p-4 border-bottom">
                <form action="{{ route('roles.index') }}" method="GET">
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
                                    placeholder="Cari nama role atau slug">
                            </div>
                        </div>
                        <div class="col-md-auto">
                            <button type="submit" class="btn btn-primary">
                                Cari
                            </button>
                            @if(request('search'))
                                <a href="{{ route('roles.index') }}" class="btn btn-white">
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
                            <th>Nama Role</th>
                            <th>Slug</th>
                            <th>Jumlah User</th>
                            <th>Dibuat</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($roles as $role)
                            <tr>
                                <td>{{ $roles->firstItem() + $loop->index }}</td>
                                <td>
                                    <span class="badge bg-primary-subtle text-primary-emphasis fs-6">
                                        {{ $role->name }}
                                    </span>
                                </td>
                                <td><code>{{ $role->slug }}</code></td>
                                <td>
                                    <span class="badge bg-secondary-subtle text-secondary-emphasis">
                                        {{ $role->users_count }} user
                                    </span>
                                </td>
                                <td>{{ $role->created_at->format('d M Y') }}</td>
                                <td>
                                    <div class="d-flex gap-2">
                                        <a href="{{ route('roles.edit', $role) }}" class="btn btn-sm btn-white">
                                            <i class="ti ti-edit"></i>
                                        </a>
                                        <form action="{{ route('roles.destroy', $role) }}" method="POST">
                                            @csrf
                                            @method('DELETE')
                                            <button type="button" class="btn btn-sm btn-white text-danger"
                                                data-confirm="Yakin hapus role {{ $role->name }}?">
                                                <i class="ti ti-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-6">
                                    @if(request('search'))
                                        Role tidak ditemukan.
                                    @else
                                        Belum ada role. <a href="{{ route('roles.create') }}">Tambah sekarang</a>
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($roles->hasPages())
                <div class="px-4 py-3 border-top">
                    {{ $roles->links() }}
                </div>
            @endif
        </div>
    </div>

@endsection
