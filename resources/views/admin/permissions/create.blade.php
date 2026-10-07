@extends('layouts.app')

@section('title', 'Tambah Permission')

@section('content')

    <div class="d-flex justify-content-between align-items-center mb-6">
        <div>
            <h4 class="mb-0">Tambah Permission</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('permissions.index') }}">Permissions</a></li>
                    <li class="breadcrumb-item active">Tambah</li>
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
                    <form action="{{ route('permissions.store') }}" method="POST">
                        @csrf

                        <div class="mb-4">
                            <label class="form-label">Nama Permission <span class="text-danger">*</span></label>
                            <input type="text" name="name"
                                class="form-control @error('name') is-invalid @enderror"
                                value="{{ old('name') }}"
                                placeholder="Contoh: Lihat User" required />
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label class="form-label">Slug <span class="text-danger">*</span></label>
                            <input type="text" name="slug"
                                class="form-control @error('slug') is-invalid @enderror"
                                value="{{ old('slug') }}"
                                placeholder="Contoh: users.view" required />
                            @error('slug')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">
                                Format: <code>resource.action</code> — contoh:
                                <code>users.view</code>, <code>users.create</code>,
                                <code>menus.edit</code>, <code>roles.delete</code>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label">Assign ke Role</label>
                            <div class="row g-3">
                                @foreach ($roles as $role)
                                    <div class="col-md-4">
                                        <div class="form-check border rounded-3 p-3">
                                            <input class="form-check-input" type="checkbox"
                                                name="roles[]" value="{{ $role->id }}"
                                                id="role_{{ $role->id }}"
                                                {{ in_array($role->id, old('roles', [])) ? 'checked' : '' }} />
                                            <label class="form-check-label w-100" for="role_{{ $role->id }}">
                                                <span class="fw-semibold">{{ $role->name }}</span>
                                                <br>
                                                <small class="text-muted">{{ $role->slug }}</small>
                                            </label>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            <div class="form-text">Kosongkan jika belum ingin assign ke role manapun.</div>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="ti ti-check me-1"></i> Simpan
                            </button>
                            <a href="{{ route('permissions.index') }}" class="btn btn-white">Batal</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Info Card --}}
        <div class="col-xl-4">
            <div class="card card-lg">
                <div class="card-body">
                    <h6 class="mb-3">
                        <i class="ti ti-info-circle me-1 text-primary"></i>
                        Panduan Slug
                    </h6>
                    <p class="text-muted small mb-3">
                        Slug digunakan sebagai identifier unik untuk permission. Gunakan format:
                    </p>
                    <div class="d-flex flex-column gap-2">
                        @foreach ([
                            ['users.view', 'Melihat daftar user'],
                            ['users.create', 'Membuat user baru'],
                            ['users.edit', 'Mengedit user'],
                            ['users.delete', 'Menghapus user'],
                            ['menus.view', 'Melihat daftar menu'],
                            ['roles.view', 'Melihat daftar role'],
                        ] as [$slug, $desc])
                            <div class="d-flex align-items-center justify-content-between">
                                <code class="small">{{ $slug }}</code>
                                <small class="text-muted">{{ $desc }}</small>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
<script>
    // Auto generate slug dari nama
    document.querySelector('[name="name"]').addEventListener('input', function () {
        const slug = this.value
            .toLowerCase()
            .replace(/[^a-z0-9\s.]/g, '')
            .replace(/\s+/g, '.');
        document.querySelector('[name="slug"]').value = slug;
    });
</script>
@endpush