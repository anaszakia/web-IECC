@php
    $userId = session('user_id');
    $user   = $userId ? \App\Models\User::with('roles.menus.children')->find($userId) : null;

    $assignedMenuIds = $user?->roles
        ->flatMap(fn($role) => $role->menus)
        ->pluck('id')
        ->toArray() ?? [];

    $menus = $user?->roles
        ->flatMap(fn($role) => $role->menus)
        ->whereNull('parent_id')
        ->unique('id')
        ->sortBy('order')
        ?? collect();
@endphp


{{-- ==================== MINI SIDEBAR ==================== --}}
<div id="miniSidebar">
    <div class="brand-logo">
        <a class="d-none d-md-flex align-items-center gap-2" href="{{ url('/') }}">
            <img src="{{ asset('images/brand/logo/logo-icon.svg') }}" alt="" />
            <span class="fw-bold fs-4 site-logo-text">Dasher</span>
        </a>
    </div>

    <ul class="navbar-nav flex-column">
        @forelse ($menus as $menu)
            @if ($menu->children->count())
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button"
                        data-bs-toggle="dropdown" aria-expanded="false">
                        <span class="nav-icon">
                            @if ($menu->icon)
                                <i class="{{ $menu->icon }}"></i>
                            @else
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                                    viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                    <path d="M9 5h-2a2 2 0 0 0 -2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2 -2v-12a2 2 0 0 0 -2 -2h-2" />
                                    <path d="M9 3m0 2a2 2 0 0 1 2 -2h2a2 2 0 0 1 2 2v0a2 2 0 0 1 -2 2h-2a2 2 0 0 1 -2 -2z" />
                                </svg>
                            @endif
                        </span>
                        <span class="text">{{ $menu->name }}</span>
                    </a>
                    <ul class="dropdown-menu flex-column">
                        @foreach ($menu->children->sortBy('order') as $child)
                            @if (in_array($child->id, $assignedMenuIds))
                                @if ($child->children->count())
                                    <li class="dropdown-submenu">
                                        <a class="nav-link dropdown-toggle" href="#!" role="button"
                                            data-bs-toggle="dropdown" aria-expanded="false">
                                            {{ $child->name }}
                                        </a>
                                        <ul class="dropdown-menu">
                                            @foreach ($child->children->sortBy('order') as $grandchild)
                                                @if (in_array($grandchild->id, $assignedMenuIds))
                                                    <li class="nav-item">
                                                        <a class="nav-link" href="{{ $grandchild->url ?? '#!' }}">
                                                            {{ $grandchild->name }}
                                                        </a>
                                                    </li>
                                                @endif
                                            @endforeach
                                        </ul>
                                    </li>
                                @else
                                    <li class="nav-item">
                                        <a class="nav-link" href="{{ $child->url ?? '#!' }}">
                                            {{ $child->name }}
                                        </a>
                                    </li>
                                @endif
                            @endif
                        @endforeach
                    </ul>
                </li>
            @else
                <li class="nav-item">
                    <a class="nav-link" href="{{ $menu->url ?? '#!' }}">
                        <span class="nav-icon">
                            @if ($menu->icon)
                                <i class="{{ $menu->icon }}"></i>
                            @else
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                                    viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                    <path d="M14 3v4a1 1 0 0 0 1 1h4" />
                                    <path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z" />
                                </svg>
                            @endif
                        </span>
                        <span class="text">{{ $menu->name }}</span>
                    </a>
                </li>
            @endif
        @empty
            <li class="nav-item">
                <span class="nav-link text-muted">Tidak ada menu tersedia</span>
            </li>
        @endforelse

        {{-- User Info --}}
        <li>
            <div class="text-center py-5 upgrade-ui">
                <div>
                    <img src="{{ $user->avatar ? app(\App\Services\MinioService::class)->url($user->avatar) : 'https://ui-avatars.com/api/?name=' . urlencode($user->name ?? 'Guest') . '&background=0d6efd&color=fff&size=128' }}"
                        alt="{{ $user->name ?? 'Guest' }}"
                        class="avatar avatar-md rounded-circle object-fit-cover"
                        onerror="this.src='https://ui-avatars.com/api/?name={{ urlencode($user->name ?? 'Guest') }}&background=0d6efd&color=fff&size=128'">
                    <div class="my-3">
                        <h5 class="mb-1 fs-6">{{ $user->name ?? 'Guest' }}</h5>
                        <span class="text-secondary">
                            {{ $user?->roles->first()?->name ?? 'No Role' }}
                        </span>
                    </div>
                    <a href="#!" class="btn btn-primary d-none">Buy Pro</a>
                </div>
            </div>
        </li>
    </ul>
</div>


{{-- ==================== OFFCANVAS SIDEBAR (Mobile) ==================== --}}
<div class="offcanvasNav offcanvas offcanvas-start" tabindex="-1" id="offcanvasExample"
    aria-labelledby="offcanvasExampleLabel">
    <div class="offcanvas-header">
        <a class="d-flex align-items-center gap-2" href="{{ url('/') }}">
            <img src="{{ asset('images/brand/logo/logo-icon.svg') }}" alt="">
            <span class="fw-bold fs-4 site-logo-text">Dasher</span>
        </a>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>

    <div class="offcanvas-body p-0">
        <ul class="navbar-nav flex-column">
            @forelse ($menus as $menu)
                @if ($menu->children->count())
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button"
                            data-bs-toggle="dropdown" aria-expanded="false">
                            <span class="nav-icon">
                                @if ($menu->icon)
                                    <i class="{{ $menu->icon }}"></i>
                                @else
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                                        viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                        stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                        <path d="M9 5h-2a2 2 0 0 0 -2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2 -2v-12a2 2 0 0 0 -2 -2h-2" />
                                        <path d="M9 3m0 2a2 2 0 0 1 2 -2h2a2 2 0 0 1 2 2v0a2 2 0 0 1 -2 2h-2a2 2 0 0 1 -2 -2z" />
                                    </svg>
                                @endif
                            </span>
                            <span class="text">{{ $menu->name }}</span>
                        </a>
                        <ul class="dropdown-menu flex-column">
                            @foreach ($menu->children->sortBy('order') as $child)
                                @if (in_array($child->id, $assignedMenuIds))
                                    @if ($child->children->count())
                                        <li class="dropdown-submenu">
                                            <a class="nav-link dropdown-toggle" href="#!" role="button"
                                                data-bs-toggle="dropdown" aria-expanded="false">
                                                {{ $child->name }}
                                            </a>
                                            <ul class="dropdown-menu">
                                                @foreach ($child->children->sortBy('order') as $grandchild)
                                                    @if (in_array($grandchild->id, $assignedMenuIds))
                                                        <li class="nav-item">
                                                            <a class="nav-link" href="{{ $grandchild->url ?? '#!' }}">
                                                                {{ $grandchild->name }}
                                                            </a>
                                                        </li>
                                                    @endif
                                                @endforeach
                                            </ul>
                                        </li>
                                    @else
                                        <li class="nav-item">
                                            <a class="nav-link" href="{{ $child->url ?? '#!' }}">
                                                {{ $child->name }}
                                            </a>
                                        </li>
                                    @endif
                                @endif
                            @endforeach
                        </ul>
                    </li>
                @else
                    <li class="nav-item">
                        <a class="nav-link" href="{{ $menu->url ?? '#!' }}">
                            <span class="nav-icon">
                                @if ($menu->icon)
                                    <i class="{{ $menu->icon }}"></i>
                                @else
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                                        viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                        stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                        <path d="M14 3v4a1 1 0 0 0 1 1h4" />
                                        <path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z" />
                                    </svg>
                                @endif
                            </span>
                            <span class="text">{{ $menu->name }}</span>
                        </a>
                    </li>
                @endif
            @empty
                <li class="nav-item">
                    <span class="nav-link text-muted">Tidak ada menu tersedia</span>
                </li>
            @endforelse

            {{-- User Info --}}
            <li>
                <div class="text-center py-5 upgrade-ui">
                    <div>
                        <img src="{{ minio_avatar($user?->avatar, $user?->name ?? 'Guest') }}"
                            alt="{{ $user->name ?? 'Guest' }}"
                            class="avatar avatar-md rounded-circle object-fit-cover"
                            onerror="this.src='https://ui-avatars.com/api/?name={{ urlencode($user->name ?? 'Guest') }}&background=0d6efd&color=fff&size=128'">
                        <div class="my-3">
                            <h5 class="mb-1 fs-6">{{ $user->name ?? 'Guest' }}</h5>
                            <span class="text-secondary">
                                {{ $user?->roles->first()?->name ?? 'No Role' }}
                            </span>
                        </div>
                        <a href="#!" class="btn btn-primary d-none">Buy Pro</a>
                    </div>
                </div>
            </li>
        </ul>
    </div>
</div>