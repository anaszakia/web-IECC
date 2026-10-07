@extends('layouts.app')

@section('title', 'User Management')

@section('content')

    <div class="d-flex justify-content-between align-items-center mb-6">
        <div>
            <h4 class="mb-0">User Management</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Users</li>
                </ol>
            </nav>
        </div>
        @if(can('users.create'))
            <a href="{{ route('users.create') }}" class="btn btn-primary">
                <i class="ti ti-plus me-1"></i> Tambah User
            </a>
        @endif
    </div>

    <div class="card card-lg">
        <div class="card-body p-0">
            <div class="p-4 border-bottom">
                <form action="{{ route('users.index') }}" method="GET">
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
                                    placeholder="Cari nama, email, atau phone">
                            </div>
                        </div>
                        <div class="col-md-auto">
                            <button type="submit" class="btn btn-primary">
                                Cari
                            </button>
                            @if(request('search'))
                                <a href="{{ route('users.index') }}" class="btn btn-white">
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
                            <th>User</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Role</th>
                            <th>Bergabung</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($users as $user)
                            <tr>
                                <td>{{ $users->firstItem() + $loop->index }}</td>
                                <td>
                                    <div class="d-flex align-items-center gap-3">
                                        <img src="{{ minio_avatar($user->avatar, $user->name) }}"
                                            alt="{{ $user->name }}"
                                            class="rounded-circle object-fit-cover"
                                            width="38" height="38"
                                            style="background: #f0f0f0; display: flex; align-items: center; justify-content: center;"
                                            onerror="this.src='https://ui-avatars.com/api/?name={{ urlencode($user->name) }}&background=0d6efd&color=fff&size=128'" />
                                        <div>
                                            <div class="fw-semibold">{{ $user->name }}</div>
                                            @if($user->id === session('user_id'))
                                                <small class="text-success">● Anda</small>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td>{{ $user->email }}</td>
                                <td>{{ $user->phone ?? '-' }}</td>
                                <td>
                                    @php
                                        $displayRole = $user->role ?? $user->roles->first();
                                    @endphp

                                    @if($displayRole)
                                        <span class="badge bg-primary-subtle text-primary-emphasis">
                                            {{ $displayRole->name }}
                                        </span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>{{ tgl_indo($user->created_at) }}</td>
                                <td>
                                    <div class="d-flex gap-2">
                                        @if(can('users.view'))
                                            <a href="{{ route('users.show', $user) }}"
                                                class="btn btn-sm btn-white" title="Detail">
                                                <i class="ti ti-eye"></i>
                                            </a>
                                        @endif
                                        @if(can('users.edit'))
                                            <a href="{{ route('users.edit', $user) }}"
                                                class="btn btn-sm btn-white" title="Edit">
                                                <i class="ti ti-edit"></i>
                                            </a>
                                        @endif
                                        @if(can('users.delete') && $user->id !== session('user_id'))
                                            <form action="{{ route('users.destroy', $user) }}" method="POST">
                                                @csrf
                                                @method('DELETE')
                                                <button type="button"
                                                    class="btn btn-sm btn-white text-danger"
                                                    data-confirm="Yakin hapus user {{ $user->name }}?"
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
                                <td colspan="7" class="text-center text-muted py-6">
                                    @if(request('search'))
                                        User tidak ditemukan.
                                    @else
                                        Belum ada user.
                                        <a href="{{ route('users.create') }}">Tambah sekarang</a>
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($users->hasPages())
                <div class="px-4 py-3 border-top">
                    {{ $users->links() }}
                </div>
            @endif
        </div>
    </div>

@endsection
