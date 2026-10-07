@extends('layouts.app')

@section('title', 'Edit Menu')

@section('content')

    <div class="d-flex justify-content-between align-items-center mb-6">
        <div>
            <h4 class="mb-0">Edit Menu</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('menus.index') }}">Menus</a></li>
                    <li class="breadcrumb-item active">Edit</li>
                </ol>
            </nav>
        </div>
        <a href="{{ route('menus.index') }}" class="btn btn-white">
            <i class="ti ti-arrow-left me-1"></i> Kembali
        </a>
    </div>

    <div class="row">
        <div class="col-xl-8">
            <div class="card card-lg">
                <div class="card-body">
                    <form action="{{ route('menus.update', $menu) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="mb-4">
                            <label class="form-label">Nama Menu <span class="text-danger">*</span></label>
                            <input type="text" name="name"
                                class="form-control @error('name') is-invalid @enderror"
                                value="{{ old('name', $menu->name) }}" required />
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label class="form-label">URL</label>
                            <input type="text" name="url"
                                class="form-control"
                                value="{{ old('url', $menu->url) }}"
                                placeholder="Contoh: /dashboard" />
                        </div>

                        <div class="mb-4">
                            <label class="form-label">Icon</label>
                            <input type="text" name="icon"
                                class="form-control"
                                value="{{ old('icon', $menu->icon) }}"
                                placeholder="Contoh: ti ti-dashboard" />
                            <div class="form-text">
                                <a href="https://tabler.io/icons" target="_blank">Lihat daftar icon</a>
                            </div>
                        </div>

                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label class="form-label">Parent Menu</label>
                                <select name="parent_id" class="form-select">
                                    <option value="">-- Tidak ada (menu utama) --</option>
                                    @foreach ($parents as $parent)
                                        <option value="{{ $parent->id }}"
                                            {{ old('parent_id', $menu->parent_id) == $parent->id ? 'selected' : '' }}>
                                            {{ $parent->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Urutan</label>
                                <input type="number" name="order"
                                    class="form-control"
                                    value="{{ old('order', $menu->order) }}" min="0" />
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label">Role yang bisa akses</label>
                            <div class="d-flex flex-wrap gap-3">
                                @foreach ($roles as $role)
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox"
                                            name="roles[]" value="{{ $role->id }}"
                                            id="role_{{ $role->id }}"
                                            {{ $menu->roles->contains($role->id) ? 'checked' : '' }} />
                                        <label class="form-check-label" for="role_{{ $role->id }}">
                                            {{ $role->name }}
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="mb-4">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox"
                                    name="is_active" id="is_active"
                                    {{ old('is_active', $menu->is_active) ? 'checked' : '' }} />
                                <label class="form-check-label" for="is_active">Menu Aktif</label>
                            </div>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="ti ti-check me-1"></i> Update
                            </button>
                            <a href="{{ route('menus.index') }}" class="btn btn-white">Batal</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Preview --}}
        <div class="col-xl-4">
            <div class="card card-lg">
                <div class="card-body">
                    <h6 class="mb-4">Preview Icon</h6>
                    <div class="d-flex align-items-center gap-3 p-4 bg-light rounded-3">
                        <i id="iconPreview" class="{{ $menu->icon ?? 'ti ti-menu-2' }} fs-3"></i>
                        <span id="namePreview" class="fw-semibold">{{ $menu->name }}</span>
                    </div>
                </div>
            </div>
            <div class="card card-lg mt-4">
                <div class="card-body">
                    <h6 class="mb-4 text-danger">Danger Zone</h6>
                    <form action="{{ route('menus.destroy', $menu) }}" method="POST"
                        onsubmit="return confirm('Yakin hapus menu {{ $menu->name }}?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger w-100">
                            <i class="ti ti-trash me-1"></i> Hapus Menu Ini
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
<script>
    document.querySelector('[name="icon"]').addEventListener('input', function() {
        document.getElementById('iconPreview').className = (this.value || 'ti ti-menu-2') + ' fs-3';
    });
    document.querySelector('[name="name"]').addEventListener('input', function() {
        document.getElementById('namePreview').textContent = this.value || 'Nama Menu';
    });
</script>
@endpush