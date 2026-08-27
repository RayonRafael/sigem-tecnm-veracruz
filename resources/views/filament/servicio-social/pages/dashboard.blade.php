<div>
<x-filament-panels::page>
    <div x-data="sigemDashboardSS()" class="sigem-professional" x-cloak>

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
                    <span class="brand-title">SIGEM – Servicio Social</span>
                    <span class="brand-subtitle">Módulo de Usuarios</span>
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
                            <form method="POST" action="{{ route('filament.servicio-social.auth.logout') }}">
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
                <h1 class="banner-greeting"><span x-text="getGreeting()"></span>, {{ explode(' ', $user->name)[0] ?? 'Usuario' }}</h1>
                <p class="banner-sub">Tienes <strong>{{ $misSolicitudesPendientes ?? 0 }} solicitudes</strong> pendientes de revisión.</p>
            </div>
            <div class="banner-actions">
                <a href="{{ url('/servicio-social/solicituds') }}" class="btn btn-glass" style="text-decoration:none;">Ver mis solicitudes</a>
            </div>
        </div>

        <!-- 3. STATS (SS Scope) -->
        <div class="grid-4">
            <!-- Stat 1 -->
            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-title">TOTAL ACTIVOS EN SISTEMA</span>
                    <div class="stat-icon bg-brand"><svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg></div>
                </div>
                <h2 class="stat-val mono-text">{{ $totalActivos ?? 0 }}</h2>
                <div class="stat-sub text-brand">Registros globales</div>
            </div>
            
            <!-- Stat 2 -->
            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-title">MIS SOLICITUDES</span>
                    <div class="stat-icon bg-emerald"><svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg></div>
                </div>
                <h2 class="stat-val mono-text">{{ $misSolicitudes ?? 0 }}</h2>
                <div class="stat-sub text-emerald">{{ $solicitudesAutorizadas ?? 0 }} autorizadas</div>
            </div>

            <!-- Stat 3 -->
            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-title">MIS MANTENIMIENTOS</span>
                    <div class="stat-icon bg-amber"><svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path></svg></div>
                </div>
                <h2 class="stat-val mono-text">{{ $misMantenimientos ?? 0 }}</h2>
                <div class="stat-sub text-amber">{{ $mantenimientoEnProceso ?? 0 }} en proceso</div>
            </div>

            <!-- Stat 4 -->
            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-title">SOLICITUDES PENDIENTES</span>
                    <div class="stat-icon bg-red"><svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg></div>
                </div>
                <h2 class="stat-val mono-text">{{ $misSolicitudesPendientes ?? 0 }}</h2>
                <div class="stat-sub {{ ($misSolicitudesPendientes ?? 0) > 0 ? 'text-red' : 'text-emerald' }}">
                    {{ ($misSolicitudesPendientes ?? 0) > 0 ? 'en espera de autorización' : 'al día' }}
                </div>
            </div>
        </div>

        <!-- 4. ACCESOS RÁPIDOS -->
        <div class="section-block" style="margin-top: 24px;">
            <div class="block-header" style="border-bottom: none; padding-bottom: 0;">
                <h2 class="block-title" style="font-size: 16px;">Accesos Rápidos</h2>
            </div>
            <div class="grid-3" style="margin-top: 16px;">
                <a href="{{ url('/servicio-social/solicituds/create') }}" class="card redirect" style="display:flex; justify-content:space-between; align-items:center; background: var(--card-bg, #fff); border: 1px solid var(--border, #e5e7eb); border-radius: 0.75rem; padding: 1.5rem; box-shadow: 0 1px 3px rgba(0,0,0,0.1); text-decoration: none; transition: transform 0.2s;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='translateY(0)'">
                    <div>
                        <h3 class="text-primary-util" style="margin:0; font-size: 1rem;">Nueva Solicitud</h3>
                        <p style="color: var(--blue, #2563eb); margin: 4px 0 0 0; font-size: 0.875rem;">/servicio-social/solicituds/create ➔</p>
                    </div>
                </a>
                
                <a href="{{ url('/servicio-social/mantenimientos/create') }}" class="card redirect" style="display:flex; justify-content:space-between; align-items:center; background: var(--card-bg, #fff); border: 1px solid var(--border, #e5e7eb); border-radius: 0.75rem; padding: 1.5rem; box-shadow: 0 1px 3px rgba(0,0,0,0.1); text-decoration: none; transition: transform 0.2s;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='translateY(0)'">
                    <div>
                        <h3 class="text-primary-util" style="margin:0; font-size: 1rem;">Reportar Falla</h3>
                        <p style="color: var(--blue, #2563eb); margin: 4px 0 0 0; font-size: 0.875rem;">/servicio-social/mantenimientos/create ➔</p>
                    </div>
                </a>
                
                <a href="{{ url('/servicio-social/inventarios') }}" class="card redirect" style="display:flex; justify-content:space-between; align-items:center; background: var(--card-bg, #fff); border: 1px solid var(--border, #e5e7eb); border-radius: 0.75rem; padding: 1.5rem; box-shadow: 0 1px 3px rgba(0,0,0,0.1); text-decoration: none; transition: transform 0.2s;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='translateY(0)'">
                    <div>
                        <h3 class="text-primary-util" style="margin:0; font-size: 1rem;">Ver Inventario</h3>
                        <p style="color: var(--blue, #2563eb); margin: 4px 0 0 0; font-size: 0.875rem;">/servicio-social/inventarios ➔</p>
                    </div>
                </a>
            </div>
        </div>

        <!-- 5. CATALOGS -->
        <div class="section-block" style="margin-top: 24px;">
            <div class="block-header" style="border-bottom: none; padding-bottom: 0;">
                <h2 class="block-title" style="font-size: 16px;">Catálogos del Sistema</h2>
            </div>
            <div class="grid-4" style="margin-top: 16px;">
                @php
                    $catalogs = [
                        ['name' => 'Materiales', 'url' => '/servicio-social/materials'],
                        ['name' => 'Marcas', 'url' => '/servicio-social/marca-materials'],
                        ['name' => 'Tipos', 'url' => '/servicio-social/tipo-materials'],
                        ['name' => 'Unidades', 'url' => '/servicio-social/unidad-medidas'],
                        ['name' => 'Receptores', 'url' => '/servicio-social/receptors'],
                        ['name' => 'Áreas', 'url' => '/servicio-social/areas'],
                    ];
                @endphp
                
                @foreach($catalogs as $cat)
                    <a href="{{ url($cat['url']) }}" class="card redirect" style="display:flex; flex-direction:column; justify-content:center; align-items:center; background: var(--card-bg, #fff); border: 1px solid var(--border, #e5e7eb); border-radius: 0.75rem; padding: 1.5rem; box-shadow: 0 1px 3px rgba(0,0,0,0.1); text-decoration: none; transition: transform 0.2s;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='translateY(0)'">
                        <h3 class="text-primary-util" style="margin:0 0 8px 0; font-size: 1rem;">{{ $cat['name'] }}</h3>
                        <p style="color: var(--blue, #2563eb); margin: 0; font-size: 0.75rem; text-align: center;">{{ $cat['url'] }} ➔</p>
                    </a>
                @endforeach
            </div>
        </div>

        <!-- 6. ACTIVITY -->
        <div class="section-block" style="margin-top: 24px;">
            <div class="block-header" style="border-bottom: none; padding-bottom: 0;">
                <h2 class="block-title text-primary-util" style="font-size: 16px;">Mi Actividad Reciente</h2>
            </div>
            <div class="timeline" style="margin-top: 16px; padding: 16px; background: var(--card-bg, #fff); border: 1px solid var(--border, #e5e7eb); border-radius: 0.75rem;">
                @forelse($actividadReciente ?? [] as $act)
                    @php
                        $dot = 'create';
                        $acc = strtolower($act->accion);
                        if(str_contains($acc, 'crea')) $dot = 'create';
                        elseif(str_contains($acc, 'actualiza') || str_contains($acc, 'edita')) $dot = 'update';
                        elseif(str_contains($acc, 'autoriza')) $dot = 'auth';
                        elseif(str_contains($acc, 'elimina')) $dot = 'delete';
                    @endphp
                <div class="timeline-item" style="display: flex; gap: 12px; margin-bottom: 12px;">
                    <div class="dot {{ $dot }}" style="margin-top: 6px;"></div>
                    <div class="tl-content">
                        <p class="tl-text text-primary-util" style="margin: 0; font-size: 0.875rem;">Has {{ strtolower($act->accion) }} en <em>{{ $act->tabla_afectada }}</em></p>
                        <div class="tl-time text-secondary-util" style="font-size: 0.75rem;">hace {{ \Carbon\Carbon::parse($act->fecha_hora)->diffInMinutes() }} min</div>
                    </div>
                </div>
                @empty
                <div class="block-footer" style="display:flex; justify-content: center;">
                    <p class="text-muted-util" style="margin: 0; font-size: 0.875rem;">Sin actividad reciente.</p>
                </div>
                @endforelse
            </div>
        </div>

        <!-- Inject data to JS -->
        <script>
            const registerSigemDashboardSS = () => {
                Alpine.data('sigemDashboardSS', () => ({
                    getGreeting() {
                        const hour = new Date().getHours();
                        if (hour >= 6 && hour < 12) return 'Buenos días';
                        if (hour >= 12 && hour < 18) return 'Buenas tardes';
                        return 'Buenas noches';
                    }
                }));
            };

            if (window.Alpine) {
                registerSigemDashboardSS();
            } else {
                document.addEventListener('alpine:init', registerSigemDashboardSS);
            }
        </script>
    </div>
</x-filament-panels::page>
</div>
