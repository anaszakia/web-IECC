@extends('layouts.app')

@section('title', 'Edit Role')

@section('content')

    <div class="d-flex justify-content-between align-items-center mb-6">
        <div>
            <h4 class="mb-0">Edit Role</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('roles.index') }}">Roles</a></li>
                    <li class="breadcrumb-item active">Edit</li>
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
                    <form action="{{ route('roles.update', $role) }}" method="POST">
                        @csrf
                        @method('PUT')

                        {{-- Nama Role --}}
                        <div class="mb-4">
                            <label class="form-label">Nama Role <span class="text-danger">*</span></label>
                            <input type="text" name="name"
                                class="form-control @error('name') is-invalid @enderror"
                                value="{{ old('name', $role->name) }}" required />
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Slug --}}
                        <div class="mb-4">
                            <label class="form-label">Slug <span class="text-danger">*</span></label>
                            <input type="text" name="slug"
                                class="form-control @error('slug') is-invalid @enderror"
                                value="{{ old('slug', $role->slug) }}" required />
                            @error('slug')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">Digunakan untuk middleware <code>role:slug</code>.</div>
                        </div>

                        @php
                            $selectedMenus = old('menus', $role->menus->pluck('id')->toArray());
                            $selectedPerms = old('permissions', $role->permissions->pluck('id')->toArray());
                        @endphp

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
                                                    {{ in_array($parent->id, $selectedMenus) ? 'checked' : '' }} />
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
                                                        {{ in_array($child->id, $selectedMenus) ? 'checked' : '' }} />
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
                                        @php
                                            $groupIds    = $items->pluck('id')->toArray();
                                            $allChecked  = count(array_intersect($groupIds, $selectedPerms)) === count($groupIds);
                                        @endphp
                                        <div class="mb-3">
                                            <div class="d-flex align-items-center justify-content-between mb-2">
                                                <span class="text-uppercase text-muted fw-bold small">
                                                    <i class="ti ti-shield me-1"></i>{{ $group }}
                                                </span>
                                                <div class="form-check mb-0">
                                                    <input class="form-check-input group-check-all" type="checkbox"
                                                        id="group_{{ $group }}"
                                                        data-group="perm_group_{{ $group }}"
                                                        {{ $allChecked ? 'checked' : '' }} />
                                                    <label class="form-check-label small text-muted" for="group_{{ $group }}">
                                                        Semua
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="row g-1 perm_group_{{ $group }}">
                                                @foreach ($items as $permission)
                                                    <div class="col-6">
                                                        <div class="form-check">
                                                            <input class="form-check-input perm-item perm_group_{{ $group }}_item"
                                                                type="checkbox"
                                                                name="permissions[]" value="{{ $permission->id }}"
                                                                id="perm_{{ $permission->id }}"
                                                                data-perm-group="perm_group_{{ $group }}"
                                                                {{ in_array($permission->id, $selectedPerms) ? 'checked' : '' }} />
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
                                <i class="ti ti-check me-1"></i> Update
                            </button>
                            <a href="{{ route('roles.index') }}" class="btn btn-white">Batal</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Danger Zone --}}
        <div class="col-xl-3">
            <div class="card card-lg border-danger">
                <div class="card-body">
                    <h6 class="mb-2 text-danger">Danger Zone</h6>
                    <p class="text-muted small mb-3">
                        Role ini digunakan oleh <strong>{{ $role->users_count ?? $role->users()->count() }} user</strong>.
                        Hapus hanya jika tidak ada user yang menggunakan role ini.
                    </p>
                    <form action="{{ route('roles.destroy', $role) }}" method="POST"
                        onsubmit="return confirm('Yakin hapus role {{ $role->name }}?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger w-100"
                            {{ ($role->users_count ?? $role->users()->count()) > 0 ? 'disabled' : '' }}>
                            <i class="ti ti-trash me-1"></i> Hapus Role
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
<script>
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