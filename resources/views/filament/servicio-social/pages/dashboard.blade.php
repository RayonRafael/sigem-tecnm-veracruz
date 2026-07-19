<div>
<x-filament-panels::page>
    <div x-data="sigemDashboardSS()" class="sigem-professional" x-cloak>
        <style>
            .sigem-professional {
                /* Color Palette */
                --brand-50: #eff6ff; --brand-100: #dbeafe; --brand-500: #3b82f6; --brand-600: #2563eb; --brand-700: #1d4ed8;
                --slate-50: #f8fafc; --slate-100: #f1f5f9; --slate-200: #e2e8f0; --slate-600: #475569; --slate-700: #334155; --slate-900: #0f172a;
                --emerald-50: #ecfdf5; --emerald-500: #10b981; --emerald-600: #059669;
                --amber-50: #fffbeb; --amber-500: #f59e0b; --amber-600: #d97706;
                --red-50: #fef2f2; --red-500: #ef4444; --red-600: #dc2626;
                --cyan-500: #06b6d4; --indigo-500: #6366f1; --violet-500: #8b5cf6; --rose-500: #f43f5e;
                --purple-500: #a855f7; --fuchsia-500: #d946ef; --teal-500: #14b8a6;
                
                font-family: 'DM Sans', sans-serif;
                color: var(--slate-900);
                background-color: transparent;
                padding-bottom: 40px;
            }
            .sigem-professional * { box-sizing: border-box; }
            .mono-text { font-family: 'JetBrains Mono', monospace; }

            /* Scrollbar */
            .sigem-professional ::-webkit-scrollbar { width: 6px; height: 6px; }
            .sigem-professional ::-webkit-scrollbar-track { background: transparent; }
            .sigem-professional ::-webkit-scrollbar-thumb { background: var(--slate-200); border-radius: 3px; }
            .sigem-professional ::-webkit-scrollbar-thumb:hover { background: var(--slate-600); }

            [x-cloak] { display: none !important; }

            /* Section 1: Header */
            .top-header { position: sticky; top: 0; z-index: 40; background: white; border-bottom: 1px solid var(--slate-200); display: flex; justify-content: space-between; align-items: center; padding: 12px 24px; margin: -24px -24px 24px -24px; }
            .header-brand { display: flex; align-items: center; gap: 12px; }
            .brand-logo { width: 40px; height: 40px; border-radius: 10px; background: linear-gradient(135deg, var(--brand-600), var(--brand-700)); display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 20px; box-shadow: 0 4px 6px -1px rgba(37, 99, 235, 0.2); }
            .brand-text-container { display: flex; flex-direction: column; }
            .brand-title { font-weight: 600; font-size: 16px; color: var(--slate-900); }
            .brand-subtitle { font-size: 12px; color: var(--slate-600); }
            .header-user { display: flex; align-items: center; gap: 16px; }
            .bell-btn { position: relative; color: var(--slate-600); background: transparent; border: none; cursor: pointer; padding: 4px; transition: 0.18s; }
            .bell-btn:hover { color: var(--brand-600); }
            .bell-dot { position: absolute; top: 4px; right: 4px; width: 8px; height: 8px; background: var(--red-500); border-radius: 50%; border: 2px solid white; }
            .header-divider { width: 1px; height: 24px; background: var(--slate-200); }
            .user-info { display: flex; align-items: center; gap: 12px; }
            .user-text { text-align: right; }
            .user-name { font-weight: 600; font-size: 14px; color: var(--slate-900); line-height: 1.2; }
            .user-role { font-size: 12px; color: var(--slate-600); }
            .user-avatar { width: 36px; height: 36px; border-radius: 50%; background: linear-gradient(135deg, var(--slate-700), var(--slate-900)); color: white; display: flex; align-items: center; justify-content: center; font-weight: 600; font-size: 14px; }

            /* Section 2: Banner */
            .welcome-banner { background: linear-gradient(to left, var(--brand-700), var(--brand-600)); border-radius: 20px; padding: 32px 40px; box-shadow: 0 10px 15px -3px rgba(37, 99, 235, 0.2); display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; color: white; }
            .banner-greeting { font-size: 28px; font-weight: 600; margin: 0 0 8px 0; }
            .banner-sub { color: var(--brand-100); font-size: 15px; margin: 0; }
            .banner-sub strong { color: white; font-weight: 600; }
            .banner-actions { display: flex; gap: 12px; }
            .btn { display: inline-flex; align-items: center; justify-content: center; gap: 8px; font-size: 14px; font-weight: 600; padding: 10px 20px; border-radius: 10px; transition: all 0.18s; border: none; cursor: pointer; text-decoration: none; }
            .btn-glass { background: rgba(255, 255, 255, 0.15); backdrop-filter: blur(8px); color: white; }
            .btn-glass:hover { background: rgba(255, 255, 255, 0.25); }
            .btn-white { background: white; color: var(--brand-600); box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); }
            .btn-white:hover { background: var(--slate-50); transform: translateY(-2px); }

            /* Grid Layouts */
            .grid-4 { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 24px; }
            .grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 24px; }
            
            /* Section 3: Stats */
            .stat-card { background: white; border: 1px solid var(--slate-200); border-radius: 20px; padding: 24px; transition: all 0.18s; position: relative; overflow: hidden; }
            .stat-card:hover { transform: translateY(-2px); box-shadow: 0 10px 15px -3px rgba(0,0,0,0.05); }
            .stat-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; }
            .stat-title { font-size: 12px; font-weight: 600; color: var(--slate-600); text-transform: uppercase; letter-spacing: 0.5px; }
            .stat-icon { width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center; }
            .stat-val { font-size: 32px; font-weight: 700; color: var(--slate-900); margin: 0 0 4px 0; line-height: 1; }
            .stat-sub { font-size: 13px; font-weight: 500; display: flex; align-items: center; gap: 4px; }
            
            .text-emerald { color: var(--emerald-600); }
            .text-amber { color: var(--amber-600); }
            .text-red { color: var(--red-600); }
            .bg-emerald { background: var(--emerald-50); color: var(--emerald-600); }
            .bg-amber { background: var(--amber-50); color: var(--amber-600); }
            .bg-red { background: var(--red-50); color: var(--red-600); }
            .bg-brand { background: var(--brand-50); color: var(--brand-600); }

            .sparkline { width: 100%; height: 40px; margin-top: 16px; }

            /* Section 4: Catalogs */
            .section-block { background: white; border: 1px solid var(--slate-200); border-radius: 20px; overflow: hidden; margin-bottom: 24px; }
            .block-header { padding: 24px; border-bottom: 1px solid var(--slate-200); display: flex; justify-content: space-between; align-items: center; }
            .block-title-wrap { display: flex; align-items: center; gap: 12px; }
            .block-icon { width: 44px; height: 44px; border-radius: 12px; background: var(--brand-50); color: var(--brand-600); display: flex; align-items: center; justify-content: center; }
            .block-title { font-size: 18px; font-weight: 600; color: var(--slate-900); margin: 0 0 4px 0; }
            .block-subtitle { font-size: 13px; color: var(--slate-600); margin: 0; }
            
            .catalog-search { display: flex; align-items: center; gap: 12px; }
            .search-input-wrap { position: relative; }
            .search-icon { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--slate-600); }
            .search-input { width: 260px; padding: 10px 12px 10px 36px; border: 1px solid var(--slate-200); border-radius: 10px; font-size: 14px; font-family: 'DM Sans', sans-serif; transition: 0.18s; outline: none; }
            .search-input:focus { border-color: var(--brand-500); box-shadow: 0 0 0 3px var(--brand-50); }

            .recent-row { background: var(--slate-50); padding: 16px 24px; border-bottom: 1px solid var(--slate-200); display: flex; align-items: center; gap: 16px; overflow-x: auto; }
            .recent-label { font-size: 11px; font-weight: 600; color: var(--slate-600); letter-spacing: 1px; display: flex; align-items: center; gap: 6px; }
            .recent-chip { background: white; border: 1px solid var(--slate-200); padding: 6px 12px; border-radius: 99px; font-size: 13px; font-weight: 500; color: var(--slate-700); display: flex; align-items: center; gap: 6px; cursor: pointer; transition: 0.18s; white-space: nowrap; }
            .recent-chip:hover { border-color: var(--brand-500); color: var(--brand-600); }
            
            .catalog-group { padding: 32px 24px; border-bottom: 1px solid var(--slate-200); }
            .group-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
            .group-title-wrap { display: flex; align-items: center; gap: 12px; }
            .group-title-text { font-size: 16px; font-weight: 600; color: var(--slate-900); }
            .group-title-sub { color: var(--slate-600); font-weight: 400; font-size: 14px; }
            .group-count { font-size: 13px; color: var(--slate-600); }
            
            .cat-card { background: white; border: 1px solid var(--slate-200); border-radius: 16px; padding: 20px; cursor: pointer; transition: all 0.18s; position: relative; }
            .cat-card:hover { transform: translateY(-2px); box-shadow: 0 10px 15px -3px rgba(0,0,0,0.05); border-color: var(--slate-300); }
            .cat-card-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 16px; }
            .cat-icon { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; color: white; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); }
            .cat-icon-sm { width: 40px; height: 40px; }
            
            .cat-title { font-size: 16px; font-weight: 600; color: var(--slate-900); margin: 0 0 4px 0; }
            .cat-desc { font-size: 13px; color: var(--slate-600); margin: 0 0 16px 0; }
            .cat-footer { display: flex; justify-content: space-between; align-items: flex-end; }
            .cat-number { font-size: 24px; font-weight: 700; color: var(--slate-900); line-height: 1; }
            .cat-num-label { font-size: 12px; color: var(--slate-600); font-weight: 500; }
            
            .hover-preview { position: absolute; bottom: 0; left: 0; right: 0; background: white; padding: 12px 20px; border-top: 1px solid var(--slate-200); border-radius: 0 0 16px 16px; font-size: 12px; color: var(--slate-600); opacity: 0; transform: translateY(10px); transition: all 0.2s; pointer-events: none; }
            .cat-card:hover .hover-preview { opacity: 1; transform: translateY(0); }
            .cat-card:hover .cat-footer { opacity: 0; }
            
            .avatar-stack { display: flex; align-items: center; }
            .avatar-sm { width: 24px; height: 24px; border-radius: 50%; border: 2px solid white; margin-left: -8px; display: flex; align-items: center; justify-content: center; font-size: 10px; font-weight: 600; color: white; }
            .avatar-sm:first-child { margin-left: 0; }
            
            .block-footer { background: var(--slate-50); padding: 16px 24px; display: flex; justify-content: space-between; align-items: center; font-size: 13px; color: var(--slate-600); }
            .kbd { background: white; border: 1px solid var(--slate-200); border-radius: 4px; padding: 2px 6px; font-family: monospace; font-size: 11px; box-shadow: 0 1px 2px rgba(0,0,0,0.05); }

            /* Section 5: Modules */
            .module-card { background: white; border: 1px solid var(--slate-200); border-radius: 20px; overflow: hidden; display: flex; flex-direction: column; transition: 0.18s; }
            .module-card:hover { box-shadow: 0 10px 15px -3px rgba(0,0,0,0.05); }
            .mod-header { padding: 20px; border-bottom: 1px solid var(--slate-200); display: flex; align-items: center; gap: 12px; }
            .mod-title { font-size: 16px; font-weight: 600; color: var(--slate-900); flex: 1; }
            .mod-badge { background: var(--slate-100); color: var(--slate-700); padding: 4px 10px; border-radius: 99px; font-size: 12px; font-weight: 600; }
            
            .mod-stats { display: flex; padding: 16px 20px; gap: 16px; background: var(--slate-50); border-bottom: 1px solid var(--slate-200); }
            .mod-stat { flex: 1; }
            .mod-stat-val { font-size: 18px; font-weight: 700; color: var(--slate-900); line-height: 1; margin-bottom: 4px; }
            .mod-stat-label { font-size: 11px; color: var(--slate-600); text-transform: uppercase; font-weight: 600; }
            
            .mini-table-wrap { padding: 0 20px; flex: 1; }
            .mod-table { width: 100%; border-collapse: collapse; }
            .mod-table th { padding: 12px 4px; text-align: left; font-size: 11px; font-weight: 600; color: var(--slate-600); text-transform: uppercase; border-bottom: 1px solid var(--slate-200); }
            .mod-table td { padding: 12px 4px; font-size: 13px; color: var(--slate-900); border-bottom: 1px solid var(--slate-100); }
            .mod-table tr:last-child td { border-bottom: none; }
            
            .badge { display: inline-flex; padding: 4px 8px; border-radius: 6px; font-size: 11px; font-weight: 600; line-height: 1; }
            .badge-green { background: var(--emerald-50); color: var(--emerald-600); }
            .badge-blue { background: var(--brand-50); color: var(--brand-600); }
            .badge-amber { background: var(--amber-50); color: var(--amber-600); }
            .badge-red { background: var(--red-50); color: var(--red-600); }
            
            .mod-footer { padding: 16px 20px; border-top: 1px solid var(--slate-200); display: flex; justify-content: space-between; align-items: center; }
            .link-blue { color: var(--brand-600); font-size: 13px; font-weight: 600; text-decoration: none; }
            .link-blue:hover { text-decoration: underline; }
            .btn-blue { background: var(--brand-600); color: white; border: none; padding: 8px 16px; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer; transition: 0.18s; }
            .btn-blue:hover { background: var(--brand-700); }
            .btn-green { background: var(--emerald-600); color: white; border: none; padding: 8px 16px; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer; transition: 0.18s; }
            .btn-green:hover { background: var(--emerald-500); }

            /* Section 6: Activity */
            .timeline { padding: 24px; }
            .timeline-item { display: flex; gap: 16px; margin-bottom: 20px; position: relative; }
            .timeline-item:last-child { margin-bottom: 0; }
            .timeline-item::before { content: ''; position: absolute; left: 5px; top: 12px; bottom: -20px; width: 2px; background: var(--slate-100); z-index: 0; }
            .timeline-item:last-child::before { display: none; }
            .dot { width: 12px; height: 12px; border-radius: 50%; border: 2px solid white; position: relative; z-index: 1; margin-top: 4px; }
            .dot.create { background: var(--emerald-500); }
            .dot.update { background: var(--brand-500); }
            .dot.auth { background: var(--amber-500); }
            .dot.delete { background: var(--red-500); }
            .tl-content { flex: 1; }
            .tl-text { font-size: 14px; color: var(--slate-700); margin: 0 0 4px 0; }
            .tl-time { font-size: 12px; color: var(--slate-500); }

            /* Slide-over */
            .slide-overlay { position: fixed; inset: 0; background: rgba(15, 23, 42, 0.4); backdrop-filter: blur(4px); z-index: 100; transition: opacity 0.3s; }
            .slide-panel { position: fixed; top: 0; right: 0; bottom: 0; width: 75%; background: var(--slate-50); z-index: 101; box-shadow: -10px 0 25px rgba(0,0,0,0.1); display: flex; flex-direction: column; }
            .slide-header { padding: 24px 32px; background: white; border-bottom: 1px solid var(--slate-200); display: flex; justify-content: space-between; align-items: center; }
            .slide-title-area { display: flex; align-items: center; gap: 16px; }
            .slide-title { font-size: 20px; font-weight: 700; color: var(--slate-900); margin: 0 0 4px 0; }
            .slide-subtitle { font-size: 13px; color: var(--slate-600); margin: 0; }
            .slide-actions { display: flex; gap: 12px; align-items: center; }
            .btn-close { background: transparent; border: none; cursor: pointer; color: var(--slate-600); padding: 8px; border-radius: 50%; transition: 0.18s; }
            .btn-close:hover { background: var(--red-50); color: var(--red-600); }
            
            .slide-body { flex: 1; overflow-y: auto; padding: 32px; }
            
            /* Slide Table */
            .slide-table-card { background: white; border: 1px solid var(--slate-200); border-radius: 16px; overflow: hidden; }
            .table-toolbar { padding: 16px 24px; border-bottom: 1px solid var(--slate-200); display: flex; justify-content: space-between; align-items: center; }
            .table-results { font-size: 13px; color: var(--slate-600); font-weight: 500; }
            .st-wrap { width: 100%; border-collapse: collapse; }
            .st-wrap th { position: sticky; top: 0; background: var(--slate-50); padding: 14px 24px; text-align: left; font-size: 12px; font-weight: 600; color: var(--slate-600); text-transform: uppercase; border-bottom: 1px solid var(--slate-200); z-index: 10; }
            .st-wrap td { padding: 16px 24px; font-size: 14px; color: var(--slate-900); border-bottom: 1px solid var(--slate-100); }
            .st-wrap tr:hover td { background: var(--slate-50); }

            .slide-footer { background: white; border-top: 1px solid var(--slate-200); padding: 16px 32px; display: flex; justify-content: flex-end; align-items: center; }

            @media (max-width: 1024px) {
                .grid-4 { grid-template-columns: repeat(2, 1fr); }
                .grid-3 { grid-template-columns: 1fr; }
                .slide-panel { width: 90%; }
            }
            @media (max-width: 640px) {
                .grid-4 { grid-template-columns: 1fr; }
                .slide-panel { width: 100%; }
            }
        </style>

        @php
            $hour = now()->hour;
            $greeting = 'Buenos días';
            if ($hour >= 12 && $hour < 18) {
                $greeting = 'Buenas tardes';
            } elseif ($hour >= 18 || $hour < 6) {
                $greeting = 'Buenas noches';
            }
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
                <button class="bell-btn">
                    <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>
                    @if($misSolicitudesPendientes > 0)
                    <div class="bell-dot"></div>
                    @endif
                </button>
                <div class="header-divider"></div>
                <div class="user-info" x-data="{ open: false }" style="position:relative;">
                    <div class="user-text">
                        <div class="user-name">{{ $user->name ?? 'Usuario' }}</div>
                        <div class="user-role">{{ $role }}</div>
                    </div>
                    <button type="button" class="user-avatar" @click="open = !open" style="border:none; cursor:pointer; outline:none;">
                        {{ strtoupper($initial) }}
                    </button>
                    
                    <div x-show="open" @click.away="open = false" x-transition.opacity.duration.200ms class="user-dropdown" style="display:none; position:absolute; top:48px; right:0; background:white; border:1px solid var(--slate-200); border-radius:12px; box-shadow:0 10px 15px -3px rgba(0,0,0,0.1); width:200px; z-index:50; overflow:hidden;">
                        <div style="padding:12px 16px; border-bottom:1px solid var(--slate-100);">
                            <div style="font-weight:600; font-size:14px; color:var(--slate-900); white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">{{ $user->name ?? 'Usuario' }}</div>
                            <div style="font-size:12px; color:var(--slate-600); white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">{{ $user->email ?? '' }}</div>
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
                <h1 class="banner-greeting">{{ $greeting }}, {{ explode(' ', $user->name)[0] ?? 'Usuario' }}</h1>
                <p class="banner-sub">Tienes <strong>{{ $misSolicitudesPendientes ?? 0 }} solicitudes</strong> pendientes de revisión.</p>
            </div>
            <div class="banner-actions">
                <button class="btn btn-glass" @click="openCatalog('modulo-solicitudes')">Ver mis solicitudes</button>
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

        <!-- 4. MODULES (SS Scope) -->
        <div class="grid-3">
            <!-- Inventario -->
            <div class="module-card">
                <div class="mod-header">
                    <div class="stat-icon bg-emerald" style="width: 36px; height: 36px; border-radius: 8px;"><svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path></svg></div>
                    <div class="mod-title">Inventario General</div>
                    <div class="mod-badge">Consultas</div>
                </div>
                <div class="mod-stats">
                    <div class="mod-stat"><div class="mod-stat-val">{{ $inventarioDisponibles ?? 0 }}</div><div class="mod-stat-label">Disponibles</div></div>
                    <div class="mod-stat"><div class="mod-stat-val">{{ $inventarioEnMantenimiento ?? 0 }}</div><div class="mod-stat-label">Mantenimiento</div></div>
                    <div class="mod-stat"><div class="mod-stat-val">{{ $inventarioDanados ?? 0 }}</div><div class="mod-stat-label">Dañados</div></div>
                </div>
                <div class="mini-table-wrap">
                    <table class="mod-table">
                        <thead><tr><th>Código</th><th>Equipo</th><th>Estado</th></tr></thead>
                        <tbody>
                            @forelse($inventariosRecientes ?? [] as $inv)
                            <tr>
                                <td class="mono-text">{{ $inv->num_serie }}</td>
                                <td>{{ Str::limit($inv->material?->nombre ?? 'N/A', 15) }}</td>
                                <td>
                                    @php
                                        $cls = 'badge-slate';
                                        if($inv->estado === 'Disponible') $cls = 'badge-green';
                                        elseif($inv->estado === 'Asignado') $cls = 'badge-blue';
                                        elseif($inv->estado === 'En Mantenimiento') $cls = 'badge-amber';
                                        elseif(in_array($inv->estado, ['Dañado', 'Baja'])) $cls = 'badge-red';
                                    @endphp
                                    <span class="badge {{ $cls }}">{{ $inv->estado }}</span>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="3" style="text-align: center; color: var(--slate-500); padding: 24px;">Sin registros.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mod-footer">
                    <button class="link-blue" style="background:transparent;border:none;padding:0;cursor:pointer;" @click="openCatalog('modulo-inventario')">Ver todo →</button>
                </div>
            </div>

            <!-- Solicitudes -->
            <div class="module-card">
                <div class="mod-header">
                    <div class="stat-icon bg-brand" style="width: 36px; height: 36px; border-radius: 8px;"><svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg></div>
                    <div class="mod-title">Mis Solicitudes</div>
                    <div class="mod-badge" style="background:var(--amber-50); color:var(--amber-600);">{{ $misSolicitudesPendientes ?? 0 }} Pendientes</div>
                </div>
                <div class="mod-stats">
                    <div class="mod-stat"><div class="mod-stat-val">{{ $misSolicitudesPendientes ?? 0 }}</div><div class="mod-stat-label">Pendientes</div></div>
                    <div class="mod-stat"><div class="mod-stat-val">{{ $solicitudesAutorizadas ?? 0 }}</div><div class="mod-stat-label">Autorizadas</div></div>
                    <div class="mod-stat"><div class="mod-stat-val">{{ $solicitudesRechazadas ?? 0 }}</div><div class="mod-stat-label">Rechazadas</div></div>
                </div>
                <div class="mini-table-wrap">
                    <table class="mod-table">
                        <thead><tr><th>#</th><th>Solicitante</th><th>Estado</th></tr></thead>
                        <tbody>
                            @forelse($solicitudesRecientes ?? [] as $sol)
                            <tr>
                                <td class="mono-text">S-{{ str_pad($sol->id_solicitud, 4, '0', STR_PAD_LEFT) }}</td>
                                <td>{{ explode(' ', $sol->usuario?->name)[0] ?? 'Usuario' }}</td>
                                <td>
                                    @php
                                        $cls = 'badge-slate';
                                        if($sol->estado === 'Pendiente') $cls = 'badge-amber';
                                        elseif($sol->estado === 'Autorizado' || $sol->estado === 'Completado') $cls = 'badge-green';
                                        elseif(in_array($sol->estado, ['Rechazado', 'Cancelado'])) $cls = 'badge-red';
                                    @endphp
                                    <span class="badge {{ $cls }}">{{ $sol->estado }}</span>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="3" style="text-align: center; color: var(--slate-500); padding: 24px;">No tienes solicitudes recientes.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mod-footer">
                    <button class="link-blue" style="background:transparent;border:none;padding:0;cursor:pointer;" @click="openCatalog('modulo-solicitudes')">Ver todas →</button>
                    <a href="{{ url('/servicio-social/solicituds/create') }}" class="btn-blue">Nueva</a>
                </div>
            </div>

            <!-- Mantenimiento -->
            <div class="module-card">
                <div class="mod-header">
                    <div class="stat-icon bg-amber" style="width: 36px; height: 36px; border-radius: 8px;"><svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path></svg></div>
                    <div class="mod-title">Mis Mantenimientos</div>
                    <div class="mod-badge">{{ $misMantenimientos ?? 0 }} Total</div>
                </div>
                <div class="mod-stats">
                    <div class="mod-stat"><div class="mod-stat-val">{{ $mantenimientoEnRevision ?? 0 }}</div><div class="mod-stat-label">En revisión</div></div>
                    <div class="mod-stat"><div class="mod-stat-val">{{ $mantenimientoEnProceso ?? 0 }}</div><div class="mod-stat-label">En proceso</div></div>
                    <div class="mod-stat"><div class="mod-stat-val">{{ $mantenimientoCompletados ?? 0 }}</div><div class="mod-stat-label">Completados</div></div>
                </div>
                <div class="mini-table-wrap">
                    <table class="mod-table">
                        <thead><tr><th>#</th><th>Equipo</th><th>Estado</th></tr></thead>
                        <tbody>
                            @forelse($mantenimientosRecientes ?? [] as $mant)
                            <tr>
                                <td class="mono-text">M-{{ str_pad($mant->id_mantenimiento, 4, '0', STR_PAD_LEFT) }}</td>
                                <td>{{ $mant->inventario?->num_serie ?? 'N/A' }}</td>
                                <td>
                                    @php
                                        $cls = 'badge-slate';
                                        if(in_array($mant->estado, ['Solicitado', 'Pendiente Revision Admin'])) $cls = 'badge-red';
                                        elseif($mant->estado === 'En proceso') $cls = 'badge-amber';
                                        elseif($mant->estado === 'Completado') $cls = 'badge-green';
                                    @endphp
                                    <span class="badge {{ $cls }}">{{ $mant->estado }}</span>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="3" style="text-align: center; color: var(--slate-500); padding: 24px;">No tienes mantenimientos recientes.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mod-footer">
                    <button class="link-blue" style="background:transparent;border:none;padding:0;cursor:pointer;" @click="openCatalog('modulo-mantenimiento')">Ver todos →</button>
                    <a href="{{ url('/servicio-social/mantenimientos/create') }}" class="btn-blue">Nuevo</a>
                </div>
            </div>
        </div>

        <!-- 5. CATALOGS (READ ONLY) -->
        <div class="section-block">
            <div class="block-header">
                <div class="block-title-wrap">
                    <div class="block-icon"><svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg></div>
                    <div>
                        <h2 class="block-title">Catálogos del Sistema</h2>
                        <p class="block-subtitle">Modo sólo lectura · {{ $totalRegistrosCatalogos ?? 0 }} registros</p>
                    </div>
                </div>
                <div class="catalog-search">
                    <div class="search-input-wrap">
                        <svg class="search-icon" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                        <input type="text" class="search-input" x-model="searchQuery" id="catSearchInput" placeholder="Buscar catálogos...">
                    </div>
                </div>
            </div>

            <div class="recent-row">
                <div class="recent-label"><svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg> BÚSQUEDA RÁPIDA</div>
                <button class="recent-chip" @click="openCatalog('departamentos')"><svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" class="text-brand-500"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path></svg> Departamentos</button>
                <button class="recent-chip" @click="openCatalog('materiales')"><svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" class="text-emerald-500"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path></svg> Materiales</button>
                <button class="btn-text" style="padding: 4px 8px; font-size: 12px; margin-left: auto;">Limpiar</button>
            </div>

            <!-- G1: ORG -->
            <div class="catalog-group" x-show="matchesSearch('departamentos', 'áreas', 'usuarios')">
                <div class="group-header">
                    <div class="group-title-wrap">
                        <div class="stat-icon bg-brand" style="width: 32px; height: 32px; border-radius: 8px;"><svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 21h18"></path><path d="M5 21V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v16"></path><path d="M9 21v-4a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v4"></path></svg></div>
                        <div>
                            <span class="group-title-text">Organización</span>
                            <span class="group-title-sub">· estructura institucional</span>
                        </div>
                    </div>
                </div>
                
                <div class="grid-3">
                    <div class="cat-card" x-show="matchesSearch('departamentos')" @click="openCatalog('departamentos')">
                        <div class="cat-card-header">
                            <div class="cat-icon" style="background: linear-gradient(135deg, var(--brand-500), var(--brand-600));"><svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path></svg></div>
                        </div>
                        <h3 class="cat-title">Departamentos</h3>
                        <p class="cat-desc">Áreas académicas y administrativas</p>
                        <div class="cat-footer">
                            <div>
                                <div class="cat-number">{{ count($departamentosList ?? []) }}</div>
                                <div class="cat-num-label">registros</div>
                            </div>
                        </div>
                        <div class="hover-preview">Ver detalles del catálogo</div>
                    </div>
                    
                    <div class="cat-card" x-show="matchesSearch('áreas')" @click="openCatalog('areas')">
                        <div class="cat-card-header">
                            <div class="cat-icon" style="background: linear-gradient(135deg, #22d3ee, var(--cyan-500));"><svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><line x1="3" y1="9" x2="21" y2="9"></line><line x1="9" y1="21" x2="9" y2="9"></line></svg></div>
                        </div>
                        <h3 class="cat-title">Áreas</h3>
                        <p class="cat-desc">Espacios físicos y oficinas</p>
                        <div class="cat-footer">
                            <div>
                                <div class="cat-number">{{ count($areasList ?? []) }}</div>
                                <div class="cat-num-label">registros</div>
                            </div>
                        </div>
                        <div class="hover-preview">Ver detalles del catálogo</div>
                    </div>
                    
                    <div class="cat-card" x-show="matchesSearch('usuarios')" @click="openCatalog('usuarios')">
                        <div class="cat-card-header">
                            <div class="cat-icon" style="background: linear-gradient(135deg, #818cf8, var(--indigo-500));"><svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg></div>
                        </div>
                        <h3 class="cat-title">Usuarios</h3>
                        <p class="cat-desc">Personal y cuentas del sistema</p>
                        <div class="cat-footer">
                            <div>
                                <div class="cat-number">{{ count($usuariosList ?? []) }}</div>
                                <div class="cat-num-label">registros</div>
                            </div>
                        </div>
                        <div class="hover-preview">Ver detalles del catálogo</div>
                    </div>
                </div>
            </div>

            <!-- G2: INVENTARIO -->
            <div class="catalog-group" x-show="matchesSearch('materiales', 'marcas', 'tipos', 'unidades')">
                <div class="group-header">
                    <div class="group-title-wrap">
                        <div class="stat-icon bg-emerald" style="width: 32px; height: 32px; border-radius: 8px;"><svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path></svg></div>
                        <div>
                            <span class="group-title-text">Inventario</span>
                            <span class="group-title-sub">· configuración de activos</span>
                        </div>
                    </div>
                </div>
                
                <div class="grid-4">
                    <div class="cat-card" x-show="matchesSearch('materiales')" @click="openCatalog('materiales')">
                        <div class="cat-card-header" style="margin-bottom: 8px;">
                            <div class="cat-icon cat-icon-sm" style="background: linear-gradient(135deg, #34d399, var(--emerald-500));"><svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"></path><line x1="7" y1="7" x2="7.01" y2="7"></line></svg></div>
                        </div>
                        <h3 class="cat-title">Materiales</h3>
                        <div class="cat-footer">
                            <span class="cat-number" style="font-size: 20px;">{{ count($materialesList ?? []) }}</span>
                            <span class="cat-num-label">registros</span>
                        </div>
                    </div>

                    <div class="cat-card" x-show="matchesSearch('marcas')" @click="openCatalog('marcas')">
                        <div class="cat-card-header" style="margin-bottom: 8px;">
                            <div class="cat-icon cat-icon-sm" style="background: linear-gradient(135deg, #2dd4bf, var(--teal-500));"><svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"></circle><path d="M12 8l4 4-4 4M8 12h8"></path></svg></div>
                        </div>
                        <h3 class="cat-title">Marcas</h3>
                        <div class="cat-footer">
                            <span class="cat-number" style="font-size: 20px;">{{ count($marcasList ?? []) }}</span>
                            <span class="cat-num-label">registros</span>
                        </div>
                    </div>

                    <div class="cat-card" x-show="matchesSearch('tipos')" @click="openCatalog('tipos')">
                        <div class="cat-card-header" style="margin-bottom: 8px;">
                            <div class="cat-icon cat-icon-sm" style="background: linear-gradient(135deg, #a78bfa, var(--violet-500));"><svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg></div>
                        </div>
                        <h3 class="cat-title">Tipos de Mat.</h3>
                        <div class="cat-footer">
                            <span class="cat-number" style="font-size: 20px;">{{ count($tiposList ?? []) }}</span>
                            <span class="cat-num-label">registros</span>
                        </div>
                    </div>

                    <div class="cat-card" x-show="matchesSearch('unidades')" @click="openCatalog('unidades')">
                        <div class="cat-card-header" style="margin-bottom: 8px;">
                            <div class="cat-icon cat-icon-sm" style="background: linear-gradient(135deg, #fb7185, var(--rose-500));"><svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path></svg></div>
                        </div>
                        <h3 class="cat-title">Unidades</h3>
                        <div class="cat-footer">
                            <span class="cat-number" style="font-size: 20px;">{{ count($unidadesList ?? []) }}</span>
                            <span class="cat-num-label">registros</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- G3: CONTACTOS -->
            <div class="catalog-group" x-show="matchesSearch('proveedores', 'receptores')" style="border-bottom: none;">
                <div class="group-header">
                    <div class="group-title-wrap">
                        <div class="stat-icon" style="background: var(--purple-500); opacity: 0.1; width: 32px; height: 32px; border-radius: 8px; position:absolute;"></div>
                        <div class="stat-icon" style="color: var(--purple-500); width: 32px; height: 32px; border-radius: 8px; z-index:1;"><svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg></div>
                        <div>
                            <span class="group-title-text">Contactos</span>
                            <span class="group-title-sub">· personas y proveedores</span>
                        </div>
                    </div>
                </div>
                
                <div class="grid-4" style="grid-template-columns: repeat(2, 1fr);">
                    <div class="cat-card" x-show="matchesSearch('proveedores')" @click="openCatalog('proveedores')">
                        <div class="cat-card-header">
                            <div class="cat-icon" style="background: linear-gradient(135deg, #c084fc, var(--purple-500));"><svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><line x1="3" y1="9" x2="21" y2="9"></line><line x1="9" y1="21" x2="9" y2="9"></line></svg></div>
                        </div>
                        <h3 class="cat-title">Proveedores</h3>
                        <p class="cat-desc">Empresas y distribuidores</p>
                        <div class="cat-footer">
                            <div>
                                <div class="cat-number">{{ count($proveedoresList ?? []) }}</div>
                                <div class="cat-num-label">registros</div>
                            </div>
                        </div>
                        <div class="hover-preview">Ver detalles del catálogo</div>
                    </div>
                    
                    <div class="cat-card" x-show="matchesSearch('receptores')" @click="openCatalog('receptores')">
                        <div class="cat-card-header">
                            <div class="cat-icon" style="background: linear-gradient(135deg, #e879f9, var(--fuchsia-500));"><svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg></div>
                        </div>
                        <h3 class="cat-title">Receptores</h3>
                        <p class="cat-desc">Personas que reciben asignaciones</p>
                        <div class="cat-footer">
                            <div>
                                <div class="cat-number">{{ count($receptoresList ?? []) }}</div>
                                <div class="cat-num-label">registros</div>
                            </div>
                        </div>
                        <div class="hover-preview">Ver detalles del catálogo</div>
                    </div>
                </div>
            </div>

            <div class="block-footer">
                <div>Tip: presiona <span class="kbd">/</span> para buscar catálogos</div>
            </div>
        </div>

        <!-- 6. ACTIVITY -->
        <div class="section-block">
            <div class="block-header" style="border-bottom: none; padding-bottom: 0;">
                <h2 class="block-title" style="font-size: 16px;">Mi Actividad Reciente</h2>
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
                        <p class="tl-text">Has {{ strtolower($act->accion) }} en <em>{{ $act->tabla_afectada }}</em></p>
                        <div class="tl-time">hace {{ \Carbon\Carbon::parse($act->fecha_hora)->diffInMinutes() }} min</div>
                    </div>
                </div>
                @empty
                <p style="color: var(--slate-500); text-align: center;">Sin actividad reciente.</p>
                @endforelse
            </div>
        </div>

        <!-- SLIDE-OVER (Alpine Component) -->
        <div x-show="activeCatalog" style="display: none;" class="relative z-50" aria-labelledby="slide-over-title" role="dialog" aria-modal="true">
            <!-- Overlay -->
            <div x-show="activeCatalog" 
                 x-transition:enter="transition-opacity ease-linear duration-300" 
                 x-transition:enter-start="opacity-0" 
                 x-transition:enter-end="opacity-100" 
                 x-transition:leave="transition-opacity ease-linear duration-300" 
                 x-transition:leave-start="opacity-100" 
                 x-transition:leave-end="opacity-0" 
                 class="slide-overlay" @click="closeCatalog()"></div>

            <div class="fixed inset-0 overflow-hidden" style="z-index: 101; pointer-events: none;">
                <div class="absolute inset-0 overflow-hidden">
                    <div class="pointer-events-none fixed inset-y-0 right-0 flex max-w-full pl-10" style="width: 100%;">
                        <!-- Panel -->
                        <div x-show="activeCatalog" 
                             x-transition:enter="transform transition ease-in-out duration-300" 
                             x-transition:enter-start="translate-x-full" 
                             x-transition:enter-end="translate-x-0" 
                             x-transition:leave="transform transition ease-in-out duration-200" 
                             x-transition:leave-start="translate-x-0" 
                             x-transition:leave-end="translate-x-full" 
                             class="pointer-events-auto w-full slide-panel" style="max-width: 800px; width: 100%; margin-left: auto;"
                             @keydown.escape.window="closeCatalog()">
                            
                            <!-- Header -->
                            <div class="slide-header">
                                <div class="slide-title-area">
                                    <div>
                                        <h2 id="slide-over-title" class="slide-title" x-text="getCatalogTitle()">Catálogo</h2>
                                        <p class="slide-subtitle">Consulta de registros (Solo Lectura)</p>
                                    </div>
                                </div>
                                <div class="slide-actions">
                                    <button type="button" class="btn-close" @click="closeCatalog()">
                                        <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                                    </button>
                                </div>
                            </div>

                            <!-- Body -->
                            <div class="slide-body">
                                
                                <!-- TABLE VIEW ONLY -->
                                <div class="slide-table-card">
                                    <div class="table-toolbar">
                                        <div class="catalog-search" style="width: 300px;">
                                            <div class="search-input-wrap" style="width: 100%;">
                                                <svg class="search-icon" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                                                <input type="text" class="search-input" style="width: 100%; padding: 8px 12px 8px 36px;" x-model="tableSearch" placeholder="Buscar registros...">
                                            </div>
                                        </div>
                                        <div class="table-results"><span x-text="getFilteredData().length"></span> resultados</div>
                                    </div>

                                    <div style="overflow-x: auto;">
                                        <table class="st-wrap">
                                            <thead>
                                                <tr x-html="getTableHeaders()"></tr>
                                            </thead>
                                            <tbody x-html="getTableRows()"></tbody>
                                        </table>
                                        <div x-show="!getTableRows()" style="padding: 40px; text-align: center; color: var(--slate-500);">
                                            Sin registros encontrados.
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Footer -->
                            <div class="slide-footer">
                                <div style="font-size: 13px; color: var(--slate-600);">por página: <strong style="color:var(--slate-900);">10</strong> <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="display:inline-block;vertical-align:middle;"><polyline points="6 9 12 15 18 9"></polyline></svg></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Inject data to JS -->
        <script>
            document.addEventListener('alpine:init', () => {
                Alpine.data('sigemDashboardSS', () => ({
                    searchQuery: '',
                    activeCatalog: null,
                    tableSearch: '',
                    
                    data: {
                        departamentos: @json($departamentosList ?? []),
                        areas: @json($areasList ?? []),
                        usuarios: @json($usuariosList ?? []),
                        materiales: @json($materialesList ?? []),
                        marcas: @json($marcasList ?? []),
                        tipos: @json($tiposList ?? []),
                        unidades: @json($unidadesList ?? []),
                        proveedores: @json($proveedoresList ?? []),
                        receptores: @json($receptoresList ?? []),
                        'modulo-inventario': @json($inventariosCompletos ?? []),
                        'modulo-solicitudes': @json($solicitudesCompletas ?? []),
                        'modulo-mantenimiento': @json($mantenimientosCompletos ?? []),
                    },

                    init() {
                        window.addEventListener('keydown', (e) => {
                            if (e.key === '/' && this.activeCatalog === null) {
                                e.preventDefault();
                                document.getElementById('catSearchInput').focus();
                            }
                        });
                    },

                    matchesSearch(...terms) {
                        if (this.searchQuery === '') return true;
                        const q = this.searchQuery.toLowerCase();
                        return terms.some(t => t.toLowerCase().includes(q));
                    },

                    openCatalog(id) {
                        this.activeCatalog = id;
                        this.tableSearch = '';
                    },

                    closeCatalog() {
                        this.activeCatalog = null;
                    },

                    getCatalogTitle() {
                        const titles = {
                            departamentos: 'Departamentos', areas: 'Áreas', usuarios: 'Usuarios',
                            materiales: 'Materiales', marcas: 'Marcas', tipos: 'Tipos de Material',
                            unidades: 'Unidades de Medida', proveedores: 'Proveedores', receptores: 'Receptores',
                            'modulo-inventario': 'Inventario General', 'modulo-solicitudes': 'Mis Solicitudes', 'modulo-mantenimiento': 'Mis Mantenimientos'
                        };
                        return titles[this.activeCatalog] || 'Catálogo';
                    },

                    getTableHeaders() {
                        const headers = {
                            departamentos: ['Nombre'],
                            areas: ['Nombre', 'Departamento', 'Estado'],
                            usuarios: ['Nombre', 'Email', 'Rol'],
                            materiales: ['Nombre', 'Tipo', 'Unidad', 'Marca'],
                            marcas: ['Nombre', 'Materiales'],
                            tipos: ['Nombre'],
                            unidades: ['Nombre'],
                            proveedores: ['Empresa', 'Contacto', 'RFC'],
                            receptores: ['Nombre', 'Área', 'Departamento'],
                            'modulo-inventario': ['N/S', 'Material', 'Estado'],
                            'modulo-solicitudes': ['Folio', 'Usuario', 'Estado'],
                            'modulo-mantenimiento': ['Activo', 'Técnico', 'Estado']
                        };
                        const cols = headers[this.activeCatalog] || [];
                        return cols.map(c => `<th>${c}</th>`).join('');
                    },

                    getFilteredData() {
                        const items = this.data[this.activeCatalog] || [];
                        if (!this.tableSearch) return items;
                        const q = this.tableSearch.toLowerCase();
                        return items.filter(item => JSON.stringify(item).toLowerCase().includes(q));
                    },

                    getTableRows() {
                        const items = this.getFilteredData();
                        if (items.length === 0) return '';
                        
                        return items.map(item => {
                            let cols = '';
                            if (this.activeCatalog === 'departamentos') {
                                cols = `<td>${item.nombre}</td>`;
                            } else if (this.activeCatalog === 'areas') {
                                cols = `<td>${item.nombre}</td><td>${item.departamento?.nombre || ''}</td><td><span class="badge badge-green">Activo</span></td>`;
                            } else if (this.activeCatalog === 'usuarios') {
                                let roles = item.roles?.map(r=>r.name).join(', ') || '';
                                cols = `<td>${item.name}</td><td>${item.email}</td><td>${roles}</td>`;
                            } else if (this.activeCatalog === 'materiales') {
                                cols = `<td>${item.nombre}</td><td>${item.tipo?.nombre || ''}</td><td>${item.unidad?.nombre || ''}</td><td>${item.marca?.nombre || ''}</td>`;
                            } else if (this.activeCatalog === 'marcas') {
                                cols = `<td>${item.nombre}</td><td>${item.materiales_count || 0}</td>`;
                            } else if (this.activeCatalog === 'tipos') {
                                cols = `<td>${item.nombre}</td>`;
                            } else if (this.activeCatalog === 'unidades') {
                                cols = `<td>${item.nombre}</td>`;
                            } else if (this.activeCatalog === 'proveedores') {
                                cols = `<td>${item.nombre_empresa}</td><td>${item.contacto_nombre || ''}</td><td class="mono-text">${item.rfc || ''}</td>`;
                            } else if (this.activeCatalog === 'receptores') {
                                cols = `<td>${item.nombre} ${item.apellido_paterno || ''}</td><td>${item.area?.nombre || ''}</td><td>${item.area?.departamento?.nombre || ''}</td>`;
                            } else if (this.activeCatalog === 'modulo-inventario') {
                                let badge = ['Disponible'].includes(item.estado) ? 'badge-green' : (item.estado === 'Asignado' ? 'badge-blue' : 'badge-amber');
                                cols = `<td class="mono-text">${item.num_serie}</td><td>${item.material?.nombre || ''}</td><td><span class="badge ${badge}">${item.estado}</span></td>`;
                            } else if (this.activeCatalog === 'modulo-solicitudes') {
                                let badge = item.estado === 'Autorizado' ? 'badge-green' : (item.estado === 'Pendiente' ? 'badge-amber' : 'badge-red');
                                cols = `<td class="mono-text">SOL-${String(item.id_solicitud).padStart(4,'0')}</td><td>${item.usuario?.name || ''}</td><td><span class="badge ${badge}">${item.estado}</span></td>`;
                            } else if (this.activeCatalog === 'modulo-mantenimiento') {
                                let badge = item.estado === 'Completado' ? 'badge-green' : (item.estado === 'En proceso' ? 'badge-amber' : 'badge-red');
                                cols = `<td class="mono-text">${item.inventario?.num_serie || ''}</td><td>${item.nombre_tecnico || 'N/A'}</td><td><span class="badge ${badge}">${item.estado}</span></td>`;
                            }
                            
                            return `<tr>${cols}</tr>`;
                        }).join('');
                    }
                }));
            });
        </script>
    </div>
</x-filament-panels::page>
</div>
