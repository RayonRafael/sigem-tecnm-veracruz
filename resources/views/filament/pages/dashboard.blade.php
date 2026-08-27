<div>
<x-filament-panels::page>
    <div x-data="sigemDashboard()" class="sigem-professional" x-cloak>

        @php
            $user = auth()->user();
            $initial = substr($user->name ?? 'U', 0, 1);
            $role = $user->roles?->first()?->name ?? 'Usuario';
        @endphp

        <!-- 1. HEADER -->
        <header class="top-header">
            <div class="header-brand">
                <div class="brand-logo">S</div>
                <div class="brand-text-container">
                    <span class="brand-title text-primary-util">SIGEM – TecNM Veracruz</span>
                    <span class="brand-subtitle text-secondary-util">Sistema de Gestión de Inventario</span>
                </div>
            </div>
            <div class="header-user">
                <button type="button" class="btn-theme" title="Alternar tema"
                    x-data="{ 
                        theme: localStorage.getItem('theme') || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'),
                        toggle() {
                            this.theme = this.theme === 'dark' ? 'light' : 'dark';
                            localStorage.setItem('theme', this.theme);
                            if (this.theme === 'dark') {
                                document.documentElement.classList.add('dark');
                            } else {
                                document.documentElement.classList.remove('dark');
                            }
                        }
                    }" 
                    @click="toggle()">
                    <svg x-show="theme === 'dark'" style="display:none;" viewBox="0 0 24 24" width="20" height="20" stroke="currentColor" fill="none" stroke-width="2"><circle cx="12" cy="12" r="5"></circle><line x1="12" y1="1" x2="12" y2="3"></line><line x1="12" y1="21" x2="12" y2="23"></line><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line><line x1="1" y1="12" x2="3" y2="12"></line><line x1="21" y1="12" x2="23" y2="12"></line><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line></svg>
                    <svg x-show="theme !== 'dark'" viewBox="0 0 24 24" width="20" height="20" stroke="currentColor" fill="none" stroke-width="2"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path></svg>
                </button>
                <div class="user-info" x-data="{ open: false }" style="position:relative;">
                    <div class="user-text">
                        <div class="user-name text-primary-util">{{ $user->name ?? 'Usuario' }}</div>
                        <div class="user-role text-secondary-util">{{ $role }}</div>
                    </div>
                    <button type="button" class="user-avatar" @click="open = !open" style="border:none; cursor:pointer; outline:none;">
                        {{ strtoupper($initial) }}
                    </button>
                    
                    <div x-show="open" @click.away="open = false" x-transition.opacity.duration.200ms class="user-dropdown" style="display:none; position:absolute; top:48px; right:0; background:white; border:1px solid var(--slate-200); border-radius:12px; box-shadow:0 10px 15px -3px rgba(0,0,0,0.1); width:200px; z-index:50; overflow:hidden;">
                        <div style="padding:12px 16px; border-bottom:1px solid var(--slate-100);">
                            <div class="text-primary-util" style="font-weight:600; font-size:14px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">{{ $user->name ?? 'Usuario' }}</div>
                            <div class="text-secondary-util" style="font-size:12px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">{{ $user->email ?? '' }}</div>
                        </div>
                        <div style="padding:8px;">
                            <form method="POST" action="{{ route('filament.admin.auth.logout') }}">
                                @csrf
                                <button type="submit" style="width:100%; text-align:left; background:transparent; border:none; padding:8px 12px; font-size:13px; font-weight:500; color:var(--red-600); cursor:pointer; display:flex; align-items:center; gap:8px; border-radius:6px; transition:0.18s;" onmouseover="this.style.background='var(--red-50)'" onmouseout="this.style.background='transparent'">
                                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
                                    Cerrar sesión
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- 2. BANNER -->
        <div class="welcome-banner">
            <div>
                <h1 class="banner-greeting text-primary-util"><span x-text="getGreeting()"></span>, {{ explode(' ', $user->name)[0] ?? 'Usuario' }}</h1>
                <p class="banner-sub text-secondary-util">Tienes <strong>{{ $solicitudesPendientes ?? 0 }} solicitudes</strong> por autorizar y <strong>{{ $mantenimientosPendientes ?? 0 }} mantenimientos</strong> programados.</p>
            </div>
            <div class="banner-actions">
                <a href="{{ url('/admin/solicituds') }}" class="btn btn-glass">Ver pendientes</a>
                <a href="{{ url('/admin/inventarios/create') }}" class="btn btn-white">
                    <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                    Nuevo Activo
                </a>
            </div>
        </div>

        <!-- 3. STATS -->
        <div class="grid-4">
            <!-- Stat 1 -->
            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-title text-secondary-util">TOTAL ACTIVOS</span>
                    <div class="stat-icon bg-brand"><svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg></div>
                </div>
                <h2 class="stat-val mono-text text-primary-util">{{ $totalActivos ?? 0 }}</h2>
                <div class="stat-sub text-emerald">
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"></polyline><polyline points="17 6 23 6 23 12"></polyline></svg>
                    +{{ $creadosEsteMes ?? 0 }} este mes
                </div>

            </div>
            
            <!-- Stat 2 -->
            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-title text-secondary-util">EN BUEN ESTADO</span>
                    <div class="stat-icon bg-emerald"><svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg></div>
                </div>
                <h2 class="stat-val mono-text text-primary-util">{{ $activosBueno ?? 0 }}</h2>
                <div class="stat-sub text-secondary-util">{{ $porcentajeBuenEstado ?? 0 }}% del inventario</div>
                <div class="progress-bar-wrap hidden-mobile">
                    <div class="progress-bar-fill" style="width: {{ $porcentajeBuenEstado ?? 0 }}%;"></div>
                </div>
            </div>

            <!-- Stat 3 -->
            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-title text-secondary-util">MANTENIMIENTOS</span>
                    <div class="stat-icon bg-amber"><svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path></svg></div>
                </div>
                <h2 class="stat-val mono-text text-primary-util">{{ $mantenimientosTotales ?? 0 }}</h2>
                <div class="stat-sub text-amber-util">{{ $mantenimientosPendientes ?? 0 }} pendientes/revisión</div>

            </div>

            <!-- Stat 4 -->
            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-title text-secondary-util">ALERTAS STOCK</span>
                    <div class="stat-icon bg-red"><svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg></div>
                </div>
                <h2 class="stat-val mono-text text-primary-util">{{ $materialesStockBajoCount ?? 0 }}</h2>
                <div class="stat-sub {{ ($materialesStockBajoCount ?? 0) > 0 ? 'text-red' : 'text-emerald' }}">
                    {{ ($materialesStockBajoCount ?? 0) > 0 ? 'requiere atención' : 'todo en orden' }}
                </div>
                <div class="severity-bars hidden-mobile">
                    @php $sev = min(5, max(0, $materialesStockBajoCount ?? 0)); @endphp
                    <div class="severity-bar {{ $sev > 0 ? 'active' : '' }}"></div>
                    <div class="severity-bar {{ $sev > 1 ? 'active' : '' }}"></div>
                    <div class="severity-bar {{ $sev > 2 ? 'active' : '' }}"></div>
                    <div class="severity-bar {{ $sev > 3 ? 'active-light' : '' }}"></div>
                    <div class="severity-bar {{ $sev > 4 ? 'active-light' : '' }}"></div>
                </div>
            </div>
        </div>

        <!-- ACCESOS RÁPIDOS -->
        <h3 style="margin: 2rem 0 1rem 0; color: var(--text-primary, #111827); font-size: 1.125rem;">Accesos Rápidos</h3>
        <div class="grid-3" style="margin-bottom: 2rem;">
            <a href="{{ url('/admin/inventarios') }}" class="card redirect" style="display:flex; justify-content:space-between; align-items:center; background: var(--card-bg, #fff); border: 1px solid var(--border, #e5e7eb); border-radius: 0.75rem; padding: 1.5rem; box-shadow: 0 1px 3px rgba(0,0,0,0.1); text-decoration: none; transition: transform 0.2s;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='translateY(0)'">
                <div>
                    <h3 style="margin:0; font-size: 1.125rem; color: var(--text-primary, #111827);">Inventario General</h3>
                    <p style="color: var(--blue, #2563eb); margin: 4px 0 0 0; font-size: 0.875rem;">/admin/inventarios ➔</p>
                </div>
            </a>
            <a href="{{ url('/admin/solicituds') }}" class="card redirect" style="display:flex; justify-content:space-between; align-items:center; background: var(--card-bg, #fff); border: 1px solid var(--border, #e5e7eb); border-radius: 0.75rem; padding: 1.5rem; box-shadow: 0 1px 3px rgba(0,0,0,0.1); text-decoration: none; transition: transform 0.2s;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='translateY(0)'">
                <div>
                    <h3 style="margin:0; font-size: 1.125rem; color: var(--text-primary, #111827);">Solicitudes</h3>
                    <p style="color: var(--blue, #2563eb); margin: 4px 0 0 0; font-size: 0.875rem;">/admin/solicituds ➔</p>
                </div>
            </a>
            <a href="{{ url('/admin/mantenimientos') }}" class="card redirect" style="display:flex; justify-content:space-between; align-items:center; background: var(--card-bg, #fff); border: 1px solid var(--border, #e5e7eb); border-radius: 0.75rem; padding: 1.5rem; box-shadow: 0 1px 3px rgba(0,0,0,0.1); text-decoration: none; transition: transform 0.2s;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='translateY(0)'">
                <div>
                    <h3 style="margin:0; font-size: 1.125rem; color: var(--text-primary, #111827);">Mantenimiento</h3>
                    <p style="color: var(--blue, #2563eb); margin: 4px 0 0 0; font-size: 0.875rem;">/admin/mantenimientos ➔</p>
                </div>
            </a>
        </div>

        <!-- CATÁLOGOS NATIVOS -->
        <div class="section-block" style="margin-bottom: 2rem;">
            <div class="block-header" style="border-bottom: none; padding-bottom: 1rem;">
                <h2 class="block-title text-primary-util" style="font-size: 16px;">Catálogos del Sistema</h2>
            </div>
            <div class="grid-4">
                @php
                    $catalogs = [
                        ['name' => 'Departamentos', 'url' => '/admin/departamentos'],
                        ['name' => 'Áreas', 'url' => '/admin/areas'],
                        ['name' => 'Materiales', 'url' => '/admin/materials'],
                        ['name' => 'Marcas', 'url' => '/admin/marca-materials'],
                        ['name' => 'Tipos de Mat.', 'url' => '/admin/tipo-materials'],
                        ['name' => 'Unidades', 'url' => '/admin/unidad-medidas'],
                        ['name' => 'Proveedores', 'url' => '/admin/proveedors'],
                        ['name' => 'Receptores', 'url' => '/admin/receptors'],
                        ['name' => 'Usuarios', 'url' => '/admin/users'],
                    ];
                @endphp
                @foreach($catalogs as $cat)
                <a href="{{ url($cat['url']) }}" class="cat-card redirect" style="display: block; background: var(--card-bg, #fff); border: 1px solid var(--border, #e5e7eb); border-radius: 0.75rem; padding: 1.5rem; text-decoration: none; transition: transform 0.2s; box-shadow: 0 1px 3px rgba(0,0,0,0.1);" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='translateY(0)'">
                    <h4 style="margin:0; font-size: 1rem; color: var(--text-primary, #111827);">{{ $cat['name'] }}</h4>
                    <p style="color: var(--blue, #2563eb); margin: 4px 0 0 0; font-size: 0.75rem;">{{ $cat['url'] }} ➔</p>
                </a>
                @endforeach
            </div>
        </div>

        <!-- 6. ACTIVITY -->
        <div class="section-block">
            <div class="block-header" style="border-bottom: none; padding-bottom: 0;">
                <h2 class="block-title text-primary-util" style="font-size: 16px;">Actividad Reciente</h2>
            </div>
            <div class="timeline">
                @forelse($actividadReciente ?? [] as $act)
                    @php
                        $dot = 'create';
                        $acc = strtolower($act->accion);
                        if(str_contains($acc, 'crea')) $dot = 'create';
                        elseif(str_contains($acc, 'actualiza') || str_contains($acc, 'edita')) $dot = 'update';
                        elseif(str_contains($acc, 'autoriza')) $dot = 'auth';
                        elseif(str_contains($acc, 'elimina')) $dot = 'delete';
                    @endphp
                <div class="timeline-item">
                    <div class="dot {{ $dot }}"></div>
                    <div class="tl-content">
                        <p class="tl-text text-primary-util"><strong>{{ $act->usuario?->name ?? 'Usuario' }}</strong> {{ strtolower($act->accion) }} en <em>{{ $act->tabla_afectada }}</em></p>
                        <div class="tl-time text-muted-util">hace {{ \Carbon\Carbon::parse($act->fecha_hora)->diffInMinutes() }} min</div>
                    </div>
                </div>
                @empty
                <p class="text-muted-util" style="text-align: center;">Sin actividad reciente.</p>
                @endforelse
            </div>
            <div class="block-footer" style="background: white; border-top: 1px solid var(--slate-100); justify-content: center;">
                <a href="#" class="link-blue">Ver bitácora completa →</a>
            </div>
        </div>

        <!-- Alpine Component Simplified -->
        <script>
            const registerSigemDashboard = () => {
                Alpine.data('sigemDashboard', () => ({
                    getGreeting() {
                        const hour = new Date().getHours();
                        if (hour >= 6 && hour < 12) return 'Buenos días';
                        if (hour >= 12 && hour < 18) return 'Buenas tardes';
                        return 'Buenas noches';
                    }
                }));
            };

            if (window.Alpine) {
                registerSigemDashboard();
            } else {
                document.addEventListener('alpine:init', registerSigemDashboard);
            }
        </script>
    </div>
</x-filament-panels::page>
</div>