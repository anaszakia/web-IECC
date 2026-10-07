@extends('layouts.app')

@section('title', 'Edit User')

@section('content')

    <div class="d-flex justify-content-between align-items-center mb-6">
        <div>
            <h4 class="mb-0">Edit User</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('users.index') }}">Users</a></li>
                    <li class="breadcrumb-item active">Edit</li>
                </ol>
            </nav>
        </div>
        <a href="{{ route('users.index') }}" class="btn btn-white">
            <i class="ti ti-arrow-left me-1"></i> Kembali
        </a>
    </div>

    <form action="{{ route('users.update', $user) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')
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
                                value="{{ old('name', $user->name) }}" required />
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email" name="email"
                                class="form-control @error('email') is-invalid @enderror"
                                value="{{ old('email', $user->email) }}" required />
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label class="form-label">
                                    Password
                                    <small class="text-muted">(kosongkan jika tidak diubah)</small>
                                </label>
                                <div class="password-field position-relative">
                                    <input type="password" name="password"
                                        class="form-control fakePassword @error('password') is-invalid @enderror"
                                        placeholder="Min. 8 karakter" />
                                    <span><i class="ti ti-eye-off passwordToggler"></i></span>
                                    @error('password')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Konfirmasi Password</label>
                                <div class="password-field position-relative">
                                    <input type="password" name="password_confirmation"
                                        class="form-control fakePassword"
                                        placeholder="Ulangi password baru" />
                                    <span><i class="ti ti-eye-off passwordToggler"></i></span>
                                </div>
                            </div>
                        </div>

                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label class="form-label">No. Telepon</label>
                                <input type="text" name="phone"
                                    class="form-control"
                                    value="{{ old('phone', $user->phone) }}"
                                    placeholder="08xxxxxxxxxx" />
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Role</label>
                                <select name="role_id" class="form-select">
                                    <option value="">-- Pilih Role --</option>
                                    @foreach ($roles as $role)
                                        <option value="{{ $role->id }}"
                                            {{ old('role_id', $user->role_id) == $role->id ? 'selected' : '' }}>
                                            {{ $role->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label">Alamat</label>
                            <textarea name="address" rows="3" class="form-control"
                                placeholder="Masukkan alamat lengkap">{{ old('address', $user->address) }}</textarea>
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
                                    src="{{ minio_avatar($user->avatar, $user->name) }}"
                                    alt="{{ $user->name }}"
                                    class="rounded-circle object-fit-cover border"
                                    width="120" height="120"
                                    onerror="this.src='https://ui-avatars.com/api/?name={{ urlencode($user->name) }}&background=0d6efd&color=fff&size=128'" />
                                <label for="avatarInput"
                                    class="position-absolute bottom-0 end-0 btn btn-sm btn-primary rounded-circle p-1"
                                    style="width:30px; height:30px; cursor:pointer;">
                                    <i class="ti ti-camera" style="font-size:14px;"></i>
                                </label>
                            </div>
                        </div>

                        <input type="file" name="avatar" id="avatarInput"
                            class="d-none" accept="image/jpg,image/jpeg,image/png,image/webp" />

                        <div class="text-center mb-3">
                            <label for="avatarInput" class="btn btn-white btn-sm w-100">
                                <i class="ti ti-upload me-1"></i> Ganti Foto
                            </label>
                            <small class="text-muted d-block mt-2">JPG, PNG, WEBP. Maks 2MB</small>
                        </div>

                        {{-- Info nama file --}}
                        <div id="fileInfo" class="mb-3 p-2 bg-light rounded-3 d-none">
                            <div class="d-flex align-items-center gap-2">
                                <i class="ti ti-file-check text-success"></i>
                                <small id="fileName" class="text-muted text-truncate"></small>
                            </div>
                        </div>

                        {{-- Hapus avatar --}}
                        @if($user->avatar)
                            <div class="form-check border rounded-3 p-3">
                                <input class="form-check-input" type="checkbox"
                                    name="remove_avatar" id="removeAvatar" value="1" />
                                <label class="form-check-label text-danger small" for="removeAvatar">
                                    <i class="ti ti-trash me-1"></i>
                                    Hapus foto profil saat ini
                                </label>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Tombol Submit --}}
                <div class="card card-lg mb-4">
                    <div class="card-body">
                        <button type="submit" class="btn btn-primary w-100 mb-2">
                            <i class="ti ti-check me-1"></i> Update User
                        </button>
                        <a href="{{ route('users.index') }}" class="btn btn-white w-100">
                            Batal
                        </a>
                    </div>
                </div>

    </form>

                {{-- Danger Zone --}}
                @if(can('users.delete') && $user->id !== session('user_id'))
                    <div class="card card-lg border-danger">
                        <div class="card-body">
                            <h6 class="mb-1 text-danger">Danger Zone</h6>
                            <p class="text-muted small mb-3">
                                Menghapus user akan menghapus semua data terkait termasuk foto profil.
                            </p>
                            <form action="{{ route('users.destroy', $user) }}" method="POST">
                                @csrf
                                @method('DELETE')
                                <button type="button" class="btn btn-danger w-100"
                                    data-confirm="Yakin hapus user {{ $user->name }}? Tindakan ini tidak bisa dibatalkan!">
                                    <i class="ti ti-trash me-1"></i> Hapus User Ini
                                </button>
                            </form>
                        </div>
                    </div>
                @endif
            </div>

        </div>

@endsection

@push('scripts')
<script>
    document.getElementById('avatarInput').addEventListener('change', function () {
        const file = this.files[0];
        if (!file) return;

        // Validasi ukuran file (2MB)
        if (file.size > 2 * 1024 * 1024) {
            Swal.fire({
                icon: 'error',
                title: 'File terlalu besar!',
                text: 'Ukuran foto maksimal 2MB.',
                confirmButtonColor: '#0d6efd',
            });
            this.value = '';
            return;
        }

        // Preview gambar
        const reader = new FileReader();
        reader.onload = (e) => {
            document.getElementById('avatarPreview').src = e.target.result;
        };
        reader.readAsDataURL(file);

        // Tampilkan nama file
        document.getElementById('fileName').textContent = file.name;
        document.getElementById('fileInfo').classList.remove('d-none');

        // Uncheck remove avatar jika ada file baru
        const removeCheckbox = document.getElementById('removeAvatar');
        if (removeCheckbox) removeCheckbox.checked = false;
    });

    // Jika remove avatar dicentang, reset preview
    const removeCheckbox = document.getElementById('removeAvatar');
    if (removeCheckbox) {
        removeCheckbox.addEventListener('change', function () {
            if (this.checked) {
                document.getElementById('avatarPreview').src =
                    'https://ui-avatars.com/api/?name={{ urlencode($user->name) }}&background=0d6efd&color=fff&size=128';
                document.getElementById('avatarInput').value = '';
                document.getElementById('fileInfo').classList.add('d-none');
            }
        });
    }
</script>
@endpush