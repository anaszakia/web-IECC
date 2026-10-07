@extends('layouts.app')

@section('title', 'Tambah Role')

@section('content')

    <div class="d-flex justify-content-between align-items-center mb-6">
        <div>
            <h4 class="mb-0">Tambah Role</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('roles.index') }}">Roles</a></li>
                    <li class="breadcrumb-item active">Tambah</li>
                </ol>
            </nav>
        </div>
        <a href="{{ route('roles.index') }}" class="btn btn-white">
            <i class="ti ti-arrow-left me-1"></i> Kembali
        </a>
    </div>

    <div class="row">
        <div class="col-xl-8">
            <div class="card card-lg">
                <div class="card-body">
                    <form action="{{ route('roles.store') }}" method="POST">
                        @csrf

                        {{-- Nama Role --}}
                        <div class="mb-4">
                            <label class="form-label">Nama Role <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="roleName"
                                class="form-control @error('name') is-invalid @enderror"
                                value="{{ old('name') }}" placeholder="Contoh: Administrator" required />
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Slug --}}
                        <div class="mb-4">
                            <label class="form-label">Slug <span class="text-danger">*</span></label>
                            <input type="text" name="slug" id="roleSlug"
                                class="form-control @error('slug') is-invalid @enderror"
                                value="{{ old('slug') }}" placeholder="Contoh: admin" required />
                            @error('slug')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">Digunakan untuk middleware <code>role:slug</code>. Huruf kecil, tanpa spasi.</div>
                        </div>

                        <div class="row g-4">
                            {{-- Akses Menu --}}
                            <div class="col-md-5">
                                <label class="form-label">Akses Menu</label>
                                <div class="border rounded-3 p-3" style="max-height: 350px; overflow-y: auto;">
                                    @forelse ($menus as $parent)
                                        <div class="mb-3">
                                            <div class="form-check mb-1">
                                                <input class="form-check-input parent-check" type="checkbox"
                                                    name="menus[]" value="{{ $parent->id }}"
                                                    id="menu_{{ $parent->id }}"
                                                    data-parent="{{ $parent->id }}"
                                                    {{ in_array($parent->id, old('menus', [])) ? 'checked' : '' }} />
                                                <label class="form-check-label fw-semibold" for="menu_{{ $parent->id }}">
                                                    @if ($parent->icon)
                                                        <i class="{{ $parent->icon }} me-1"></i>
                                                    @endif
                                                    {{ $parent->name }}
                                                </label>
                                            </div>
                                            @foreach ($parent->children as $child)
                                                <div class="form-check ms-4 mb-1">
                                                    <input class="form-check-input child-check" type="checkbox"
                                                        name="menus[]" value="{{ $child->id }}"
                                                        id="menu_{{ $child->id }}"
                                                        data-group="{{ $parent->id }}"
                                                        {{ in_array($child->id, old('menus', [])) ? 'checked' : '' }} />
                                                    <label class="form-check-label" for="menu_{{ $child->id }}">
                                                        <i class="ti ti-corner-down-right text-muted me-1"></i>
                                                        {{ $child->name }}
                                                    </label>
                                                </div>
                                            @endforeach
                                        </div>
                                    @empty
                                        <p class="text-muted mb-0 small">Belum ada menu tersedia.</p>
                                    @endforelse
                                </div>
                                <div class="mt-2 d-flex gap-2">
                                    <button type="button" class="btn btn-sm btn-white" id="checkAllMenus">Pilih Semua</button>
                                    <button type="button" class="btn btn-sm btn-white" id="uncheckAllMenus">Hapus Semua</button>
                                </div>
                            </div>

                            {{-- Permissions --}}
                            <div class="col-md-7">
                                <label class="form-label">Permissions</label>
                                <div class="border rounded-3 p-3" style="max-height: 350px; overflow-y: auto;">
                                    @forelse ($permissions as $group => $items)
                                        <div class="mb-3">
                                            {{-- Group header + check all group --}}
                                            <div class="d-flex align-items-center justify-content-between mb-2">
                                                <span class="text-uppercase text-muted fw-bold small">
                                                    <i class="ti ti-shield me-1"></i>{{ $group }}
                                                </span>
                                                <div class="form-check mb-0">
                                                    <input class="form-check-input group-check-all" type="checkbox"
                                                        id="group_{{ $group }}"
                                                        data-group="perm_group_{{ $group }}" />
                                                    <label class="form-check-label small text-muted" for="group_{{ $group }}">
                                                        Semua
                                                    </label>
                                                </div>
                                            </div>
                                            {{-- Permission items --}}
                                            <div class="row g-1 perm_group_{{ $group }}">
                                                @foreach ($items as $permission)
                                                    <div class="col-6">
                                                        <div class="form-check">
                                                            <input class="form-check-input perm-item perm_group_{{ $group }}_item"
                                                                type="checkbox"
                                                                name="permissions[]" value="{{ $permission->id }}"
                                                                id="perm_{{ $permission->id }}"
                                                                data-perm-group="perm_group_{{ $group }}"
                                                                {{ in_array($permission->id, old('permissions', [])) ? 'checked' : '' }} />
                                                            <label class="form-check-label small" for="perm_{{ $permission->id }}">
                                                                {{ $permission->name }}
                                                            </label>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                        @if (!$loop->last)
                                            <hr class="my-2">
                                        @endif
                                    @empty
                                        <p class="text-muted mb-0 small">Belum ada permission tersedia.</p>
                                    @endforelse
                                </div>
                                <div class="mt-2 d-flex gap-2">
                                    <button type="button" class="btn btn-sm btn-white" id="checkAllPerms">Pilih Semua</button>
                                    <button type="button" class="btn btn-sm btn-white" id="uncheckAllPerms">Hapus Semua</button>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex gap-2 mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="ti ti-check me-1"></i> Simpan
                            </button>
                            <a href="{{ route('roles.index') }}" class="btn btn-white">Batal</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
<script>
    // Auto-generate slug dari nama
    document.getElementById('roleName').addEventListener('input', function () {
        document.getElementById('roleSlug').value = this.value
            .toLowerCase()
            .replace(/\s+/g, '-')
            .replace(/[^a-z0-9-]/g, '');
    });

    // Menu: check all / uncheck all
    document.getElementById('checkAllMenus').addEventListener('click', () => {
        document.querySelectorAll('input[name="menus[]"]').forEach(cb => cb.checked = true);
    });
    document.getElementById('uncheckAllMenus').addEventListener('click', () => {
        document.querySelectorAll('input[name="menus[]"]').forEach(cb => cb.checked = false);
    });

    // Menu: centang parent otomatis jika child dicentang
    document.querySelectorAll('.child-check').forEach(child => {
        child.addEventListener('change', function () {
            const parent = document.querySelector(`[data-parent="${this.dataset.group}"]`);
            if (this.checked && parent) parent.checked = true;
        });
    });

    // Permission: check all / uncheck all
    document.getElementById('checkAllPerms').addEventListener('click', () => {
        document.querySelectorAll('input[name="permissions[]"]').forEach(cb => cb.checked = true);
        document.querySelectorAll('.group-check-all').forEach(cb => cb.checked = true);
    });
    document.getElementById('uncheckAllPerms').addEventListener('click', () => {
        document.querySelectorAll('input[name="permissions[]"]').forEach(cb => cb.checked = false);
        document.querySelectorAll('.group-check-all').forEach(cb => cb.checked = false);
    });

    // Permission: check all per group
    document.querySelectorAll('.group-check-all').forEach(groupCb => {
        groupCb.addEventListener('change', function () {
            const groupClass = this.dataset.group;
            document.querySelectorAll(`.${groupClass}_item`).forEach(cb => cb.checked = this.checked);
        });
    });

    // Permission: update group checkbox jika semua item di-check/uncheck
    document.querySelectorAll('.perm-item').forEach(item => {
        item.addEventListener('change', function () {
            const groupClass = this.dataset.permGroup;
            const items      = document.querySelectorAll(`.${groupClass}_item`);
            const allChecked = [...items].every(cb => cb.checked);
            const groupCb    = document.querySelector(`[data-group="${groupClass}"]`);
            if (groupCb) groupCb.checked = allChecked;
        });
    });
</script>
@endpush