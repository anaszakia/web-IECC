@extends('layouts.app')

@section('title', 'Edit Permission')

@section('content')

    <div class="d-flex justify-content-between align-items-center mb-6">
        <div>
            <h4 class="mb-0">Edit Permission</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('permissions.index') }}">Permissions</a></li>
                    <li class="breadcrumb-item active">Edit</li>
                </ol>
            </nav>
        </div>
        <a href="{{ route('permissions.index') }}" class="btn btn-white">
            <i class="ti ti-arrow-left me-1"></i> Kembali
        </a>
    </div>

    <div class="row">
        <div class="col-xl-8">
            <div class="card card-lg">
                <div class="card-body">
                    <form action="{{ route('permissions.update', $permission) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="mb-4">
                            <label class="form-label">Nama Permission <span class="text-danger">*</span></label>
                            <input type="text" name="name"
                                class="form-control @error('name') is-invalid @enderror"
                                value="{{ old('name', $permission->name) }}" required />
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label class="form-label">Slug <span class="text-danger">*</span></label>
                            <input type="text" name="slug"
                                class="form-control @error('slug') is-invalid @enderror"
                                value="{{ old('slug', $permission->slug) }}" required />
                            @error('slug')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">Format: <code>resource.action</code></div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label">Assign ke Role</label>
                            <div class="row g-3">
                                @foreach ($roles as $role)
                                    <div class="col-md-4">
                                        <div class="form-check border rounded-3 p-3
                                            {{ $permission->roles->contains($role->id) ? 'border-primary bg-primary bg-opacity-10' : '' }}">
                                            <input class="form-check-input" type="checkbox"
                                                name="roles[]" value="{{ $role->id }}"
                                                id="role_{{ $role->id }}"
                                                {{ $permission->roles->contains($role->id) ? 'checked' : '' }} />
                                            <label class="form-check-label w-100" for="role_{{ $role->id }}">
                                                <span class="fw-semibold">{{ $role->name }}</span>
                                                <br>
                                                <small class="text-muted">{{ $role->slug }}</small>
                                            </label>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="ti ti-check me-1"></i> Update
                            </button>
                            <a href="{{ route('permissions.index') }}" class="btn btn-white">Batal</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Danger Zone --}}
        <div class="col-xl-4">
            <div class="card card-lg">
                <div class="card-body">
                    <h6 class="mb-1">Info Permission</h6>
                    <p class="text-muted small mb-3">Digunakan di middleware:</p>
                    <code class="d-block p-3 bg-light rounded-3 mb-0">
                        middleware('permission:{{ $permission->slug }}')
                    </code>
                </div>
            </div>
            <div class="card card-lg mt-4">
                <div class="card-body">
                    <h6 class="mb-1 text-danger">Danger Zone</h6>
                    <p class="text-muted small mb-3">
                        Menghapus permission akan mencabut akses dari semua role yang memilikinya.
                    </p>
                    <form action="{{ route('permissions.destroy', $permission) }}" method="POST"
                        onsubmit="return confirm('Yakin hapus permission {{ $permission->name }}?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger w-100">
                            <i class="ti ti-trash me-1"></i> Hapus Permission Ini
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

@endsection