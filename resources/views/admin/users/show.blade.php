@extends('layouts.app')

@section('title', 'Detail User')

@section('content')

    <div class="d-flex justify-content-between align-items-center mb-6">
        <div>
            <h4 class="mb-0">Detail User</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('users.index') }}">Users</a></li>
                    <li class="breadcrumb-item active">Detail</li>
                </ol>
            </nav>
        </div>
        <div class="d-flex gap-2">
            @if(can('users.edit'))
                <a href="{{ route('users.edit', $user) }}" class="btn btn-primary">
                    <i class="ti ti-edit me-1"></i> Edit
                </a>
            @endif
            <a href="{{ route('users.index') }}" class="btn btn-white">
                <i class="ti ti-arrow-left me-1"></i> Kembali
            </a>
        </div>
    </div>

    <div class="row">
        {{-- Profil Card --}}
        <div class="col-xl-4">
            <div class="card card-lg mb-4 text-center">
                <div class="card-body">
                    <img src="{{ minio_avatar($user->avatar, $user->name) }}"
                        alt="{{ $user->name }}"
                        class="rounded-circle object-fit-cover border mb-4"
                        width="100" height="100"
                        onerror="this.src='https://ui-avatars.com/api/?name={{ urlencode($user->name) }}&background=0d6efd&color=fff&size=128'" />

                    <h5 class="mb-1">{{ $user->name }}</h5>
                    <p class="text-muted mb-3">{{ $user->email }}</p>

                    @if($user->role)
                        <span class="badge bg-primary-subtle text-primary-emphasis px-3 py-2">
                            {{ $user->role->name }}
                        </span>
                    @endif

                    <hr>

                    <div class="text-start">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <i class="ti ti-phone text-muted"></i>
                            <span class="small">{{ $user->phone ?? 'Belum diisi' }}</span>
                        </div>
                        <div class="d-flex align-items-start gap-2 mb-2">
                            <i class="ti ti-map-pin text-muted mt-1"></i>
                            <span class="small">{{ $user->address ?? 'Belum diisi' }}</span>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <i class="ti ti-calendar text-muted"></i>
                            <span class="small">Bergabung {{ tgl_indo($user->created_at) }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Info Detail --}}
        <div class="col-xl-8">
            <div class="card card-lg mb-4">
                <div class="card-body">
                    <h6 class="mb-4 text-muted text-uppercase" style="font-size:11px; letter-spacing:.05em;">
                        Informasi Lengkap
                    </h6>
                    <table class="table table-borderless mb-0">
                        <tr>
                            <td class="text-muted" width="180">Nama</td>
                            <td><strong>{{ $user->name }}</strong></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Email</td>
                            <td>{{ $user->email }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">No. Telepon</td>
                            <td>{{ $user->phone ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Role</td>
                            <td>
                                @if($user->role)
                                    <span class="badge bg-primary-subtle text-primary-emphasis">
                                        {{ $user->role->name }}
                                    </span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td class="text-muted">Alamat</td>
                            <td>{{ $user->address ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Bergabung</td>
                            <td>{{ tgl_jam($user->created_at) }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Terakhir Update</td>
                            <td>{{ tgl_jam($user->updated_at) }}</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>

@endsection