@extends('layouts.app')

@section('title', 'Tambah User')

@section('content')

    <div class="d-flex justify-content-between align-items-center mb-6">
        <div>
            <h4 class="mb-0">Tambah User</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('users.index') }}">Users</a></li>
                    <li class="breadcrumb-item active">Tambah</li>
                </ol>
            </nav>
        </div>
        <a href="{{ route('users.index') }}" class="btn btn-white">
            <i class="ti ti-arrow-left me-1"></i> Kembali
        </a>
    </div>

    <form action="{{ route('users.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="row">

            {{-- Form Utama --}}
            <div class="col-xl-8">
                <div class="card card-lg mb-4">
                    <div class="card-body">
                        <h6 class="mb-4 text-muted text-uppercase" style="font-size:11px; letter-spacing:.05em;">
                            Informasi Akun
                        </h6>

                        <div class="mb-4">
                            <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                            <input type="text" name="name"
                                class="form-control @error('name') is-invalid @enderror"
                                value="{{ old('name') }}"
                                placeholder="Masukkan nama lengkap" required />
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email" name="email"
                                class="form-control @error('email') is-invalid @enderror"
                                value="{{ old('email') }}"
                                placeholder="contoh@email.com" required />
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label class="form-label">Password <span class="text-danger">*</span></label>
                                <div class="password-field position-relative">
                                    <input type="password" name="password"
                                        class="form-control fakePassword @error('password') is-invalid @enderror"
                                        placeholder="Min. 8 karakter" required />
                                    <span><i class="ti ti-eye-off passwordToggler"></i></span>
                                    @error('password')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Konfirmasi Password <span class="text-danger">*</span></label>
                                <div class="password-field position-relative">
                                    <input type="password" name="password_confirmation"
                                        class="form-control fakePassword"
                                        placeholder="Ulangi password" required />
                                    <span><i class="ti ti-eye-off passwordToggler"></i></span>
                                </div>
                            </div>
                        </div>

                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label class="form-label">No. Telepon</label>
                                <input type="text" name="phone"
                                    class="form-control @error('phone') is-invalid @enderror"
                                    value="{{ old('phone') }}"
                                    placeholder="08xxxxxxxxxx" />
                                @error('phone')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Role</label>
                                <select name="role_id" class="form-select">
                                    <option value="">-- Pilih Role --</option>
                                    @foreach ($roles as $role)
                                        <option value="{{ $role->id }}"
                                            {{ old('role_id') == $role->id ? 'selected' : '' }}>
                                            {{ $role->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label">Alamat</label>
                            <textarea name="address" rows="3"
                                class="form-control @error('address') is-invalid @enderror"
                                placeholder="Masukkan alamat lengkap">{{ old('address') }}</textarea>
                            @error('address')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            {{-- Upload Avatar --}}
            <div class="col-xl-4">
                <div class="card card-lg mb-4">
                    <div class="card-body">
                        <h6 class="mb-4 text-muted text-uppercase" style="font-size:11px; letter-spacing:.05em;">
                            Foto Profil
                        </h6>

                        {{-- Preview --}}
                        <div class="text-center mb-4">
                            <div class="position-relative d-inline-block">
                                <img id="avatarPreview"
                                    src="https://ui-avatars.com/api/?name=User&background=0d6efd&color=fff&size=128"
                                    alt="Preview"
                                    class="rounded-circle object-fit-cover border"
                                    width="120" height="120" />
                                <label for="avatarInput"
                                    class="position-absolute bottom-0 end-0 btn btn-sm btn-primary rounded-circle p-1"
                                    style="width:30px; height:30px; cursor:pointer;">
                                    <i class="ti ti-camera" style="font-size:14px;"></i>
                                </label>
                            </div>
                        </div>

                        <input type="file" name="avatar" id="avatarInput"
                            class="d-none @error('avatar') is-invalid @enderror"
                            accept="image/jpg,image/jpeg,image/png,image/webp" />
                        @error('avatar')
                            <div class="text-danger small mb-2">{{ $message }}</div>
                        @enderror

                        <div class="text-center">
                            <label for="avatarInput" class="btn btn-white btn-sm w-100">
                                <i class="ti ti-upload me-1"></i> Pilih Foto
                            </label>
                            <small class="text-muted d-block mt-2">
                                JPG, PNG, WEBP. Maks 2MB
                            </small>
                        </div>

                        {{-- Info nama file --}}
                        <div id="fileInfo" class="mt-3 p-2 bg-light rounded-3 d-none">
                            <div class="d-flex align-items-center gap-2">
                                <i class="ti ti-file-check text-success"></i>
                                <small id="fileName" class="text-muted text-truncate"></small>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Tombol Submit --}}
                <div class="card card-lg">
                    <div class="card-body">
                        <button type="submit" class="btn btn-primary w-100 mb-2">
                            <i class="ti ti-check me-1"></i> Simpan User
                        </button>
                        <a href="{{ route('users.index') }}" class="btn btn-white w-100">
                            Batal
                        </a>
                    </div>
                </div>
            </div>

        </div>
    </form>

@endsection

@push('scripts')
<script>
    document.getElementById('avatarInput').addEventListener('change', function () {
        const file = this.files[0];
        if (!file) return;

        // Preview gambar
        const reader = new FileReader();
        reader.onload = (e) => {
            document.getElementById('avatarPreview').src = e.target.result;
        };
        reader.readAsDataURL(file);

        // Tampilkan nama file
        document.getElementById('fileName').textContent = file.name;
        document.getElementById('fileInfo').classList.remove('d-none');
    });
</script>
@endpush