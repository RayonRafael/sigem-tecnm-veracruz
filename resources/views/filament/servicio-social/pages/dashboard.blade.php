<x-filament-panels::page>
    <style>
        .sigem-dashboard {
            --bg: #f0f4f8;
            --sidebar-bg: #0b1d3a;
            --sidebar-hover: #122a52;
            --sidebar-active: #1a3a6e;
            --sidebar-text: #8fa8cc;
            --sidebar-text-active: #ffffff;
            --card-bg: #ffffff;
            --accent: #1b65d4;
            --accent-light: #e8f0fe;
            --accent-dark: #0e4bad;
            --success: #0f9d58;
            --success-light: #e6f7ed;
            --warning: #f4a623;
            --warning-light: #fef7e6;
            --danger: #d93025;
            --danger-light: #fce8e6;
            --info: #1a73e8;
            --info-light: #e8f0fe;
            --text-primary: #1a1a2e;
            --text-secondary: #5f6b7a;
            --text-muted: #8b95a5;
            --border: #e2e8f0;
            --border-light: #f1f5f9;
            --radius: 12px;
            --radius-sm: 8px;
            --shadow: 0 1px 3px rgba(0,0,0,0.06), 0 1px 2px rgba(0,0,0,0.04);
            --shadow-md: 0 4px 12px rgba(0,0,0,0.08);
            --shadow-lg: 0 10px 30px rgba(0,0,0,0.1);

            font-family: 'DM Sans', sans-serif;
            color: var(--text-primary);
        }

        .sigem-dashboard * {
            box-sizing: border-box;
        }

        /* ===== DASHBOARD WIDGETS ===== */
        .sigem-dashboard .stats-grid-3 {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 28px;
        }

        .sigem-dashboard .stat-card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 22px 24px;
            position: relative;
            overflow: hidden;
            transition: all 0.2s ease;
            cursor: default;
            box-shadow: var(--shadow);
        }

        .sigem-dashboard .stat-card:hover {
            box-shadow: var(--shadow-md);
            transform: translateY(-2px);
            border-color: transparent;
        }

        .sigem-dashboard .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
        }

        .sigem-dashboard .stat-card.blue::before { background: linear-gradient(90deg, var(--accent), #4f9cf7); }
        .sigem-dashboard .stat-card.orange::before { background: linear-gradient(90deg, var(--warning), #fbbf24); }
        .sigem-dashboard .stat-card.green::before { background: linear-gradient(90deg, var(--success), #34d399); }

        .sigem-dashboard .stat-icon {
            width: 44px;
            height: 44px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 14px;
        }

        .sigem-dashboard .stat-icon svg {
            width: 22px;
            height: 22px;
            fill: none;
            stroke-width: 1.8;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        .sigem-dashboard .stat-card.blue .stat-icon { background: var(--accent-light); }
        .sigem-dashboard .stat-card.blue .stat-icon svg { stroke: var(--accent); }
        .sigem-dashboard .stat-card.orange .stat-icon { background: var(--warning-light); }
        .sigem-dashboard .stat-card.orange .stat-icon svg { stroke: var(--warning); }
        .sigem-dashboard .stat-card.green .stat-icon { background: var(--success-light); }
        .sigem-dashboard .stat-card.green .stat-icon svg { stroke: var(--success); }

        .sigem-dashboard .stat-value {
            font-family: 'JetBrains Mono', monospace;
            font-size: 32px;
            font-weight: 700;
            letter-spacing: -1px;
            color: var(--text-primary);
            line-height: 1;
            margin-bottom: 6px;
        }

        .sigem-dashboard .stat-label {
            font-size: 13px;
            color: var(--text-secondary);
            font-weight: 500;
        }

        /* ===== CHARTS & PANELS ===== */
        .sigem-dashboard .grid-2-equal {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 28px;
        }

        .sigem-dashboard .panel {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            overflow: hidden;
            box-shadow: var(--shadow);
            display: flex;
            flex-direction: column;
        }

        .sigem-dashboard .panel-header {
            padding: 18px 22px;
            border-bottom: 1px solid var(--border-light);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .sigem-dashboard .panel-title {
            font-size: 15px;
            font-weight: 700;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .sigem-dashboard .panel-title svg {
            width: 18px;
            height: 18px;
            stroke: var(--accent);
            fill: none;
            stroke-width: 1.8;
        }

        .sigem-dashboard .panel-action {
            font-size: 12px;
            color: var(--accent);
            text-decoration: none;
            font-weight: 600;
            cursor: pointer;
        }

        .sigem-dashboard .panel-action:hover {
            text-decoration: underline;
        }

        .sigem-dashboard .panel-body {
            padding: 20px 22px;
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        /* ===== ACTIVITY LIST ===== */
        .sigem-dashboard .activity-list {
            display: flex;
            flex-direction: column;
        }

        .sigem-dashboard .activity-item {
            display: flex;
            align-items: flex-start;
            gap: 14px;
            padding: 14px 0;
            border-bottom: 1px solid var(--border-light);
        }

        .sigem-dashboard .activity-item:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }

        .sigem-dashboard .activity-item:first-child {
            padding-top: 0;
        }

        .sigem-dashboard .activity-dot {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            margin-top: 2px;
        }

        .sigem-dashboard .activity-dot svg {
            width: 16px;
            height: 16px;
            stroke: currentColor;
            fill: none;
            stroke-width: 2;
        }

        .sigem-dashboard .activity-dot.blue { background: var(--accent-light); color: var(--accent); }
        .sigem-dashboard .activity-dot.green { background: var(--success-light); color: var(--success); }
        .sigem-dashboard .activity-dot.orange { background: var(--warning-light); color: var(--warning); }
        .sigem-dashboard .activity-dot.red { background: var(--danger-light); color: var(--danger); }

        .sigem-dashboard .activity-content {
            flex: 1;
        }

        .sigem-dashboard .activity-text {
            font-size: 13px;
            color: var(--text-primary);
            line-height: 1.5;
        }

        .sigem-dashboard .activity-text strong {
            font-weight: 600;
        }

        .sigem-dashboard .activity-time {
            font-size: 11px;
            color: var(--text-muted);
            margin-top: 3px;
        }

        /* ===== EMPTY STATE ===== */
        .sigem-dashboard .empty-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
            text-align: center;
            flex: 1;
        }

        .sigem-dashboard .empty-icon {
            width: 54px;
            height: 54px;
            border-radius: 50%;
            background: var(--border-light);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 16px;
            color: var(--text-muted);
        }

        .sigem-dashboard .empty-icon svg {
            width: 26px;
            height: 26px;
            stroke: currentColor;
            fill: none;
            stroke-width: 1.5;
        }

        .sigem-dashboard .empty-title {
            font-size: 15px;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 6px;
        }

        .sigem-dashboard .empty-desc {
            font-size: 13px;
            color: var(--text-muted);
            max-width: 280px;
        }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 1200px) {
            .sigem-dashboard .stats-grid-3 {
                grid-template-columns: repeat(2, 1fr);
            }
            .sigem-dashboard .grid-2-equal {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .sigem-dashboard .stats-grid-3 {
                grid-template-columns: 1fr;
            }
        }

        /* ===== STAGGER ANIMATIONS ===== */
        .sigem-dashboard .stat-card, 
        .sigem-dashboard .panel {
            opacity: 0;
            animation: slideUp 0.4s ease forwards;
        }
        .sigem-dashboard .stat-card:nth-child(1), .sigem-dashboard .panel:nth-child(1) { animation-delay: 0.05s; }
        .sigem-dashboard .stat-card:nth-child(2), .sigem-dashboard .panel:nth-child(2) { animation-delay: 0.1s; }
        .sigem-dashboard .stat-card:nth-child(3) { animation-delay: 0.15s; }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(16px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>

    <div class="sigem-dashboard">
        <!-- Stats Cards -->
        <div class="stats-grid-3">
            <div class="stat-card blue">
                <div class="stat-icon">
                    <svg viewBox="0 0 24 24"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>
                </div>
                <div class="stat-value">{{ $misRegistrosHoy ?? 0 }}</div>
                <div class="stat-label">Mis registros hoy</div>
            </div>
            <div class="stat-card orange">
                <div class="stat-icon">
                    <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                </div>
                <div class="stat-value">{{ $pendientesAprobacion ?? 0 }}</div>
                <div class="stat-label">Pendientes de aprobación</div>
            </div>
            <div class="stat-card green">
                <div class="stat-icon">
                    <svg viewBox="0 0 24 24"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                </div>
                <div class="stat-value">{{ $reportesActivos ?? 0 }}</div>
                <div class="stat-label">Reportes activos</div>
            </div>
        </div>

        <!-- Activity & Tasks Row -->
        <div class="grid-2-equal">
            <div class="panel">
                <div class="panel-header">
                    <div class="panel-title">
                        <svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                        Mis tareas pendientes
                    </div>
                    <a href="{{ url('/servicio-social/inventarios') }}" class="panel-action">Ver inventario</a>
                </div>
                <div class="panel-body">
                    @if(isset($tareasPendientes) && $tareasPendientes->count() > 0)
                        <div class="activity-list">
                            @foreach($tareasPendientes as $tarea)
                                <div class="activity-item">
                                    <div class="activity-dot orange">
                                        <svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>
                                    </div>
                                    <div class="activity-content">
                                        <div class="activity-text"><strong>Registro de Inventario</strong> (N/S: {{ $tarea->num_serie }}) en espera de aprobación</div>
                                        <div class="activity-time">{{ $tarea->created_at ? $tarea->created_at->diffForHumans() : 'Recientemente' }} — Estado: Pendiente</div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="empty-state">
                            <div class="empty-icon">
                                <svg viewBox="0 0 24 24" stroke="currentColor" fill="none" stroke-width="1.5"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                            </div>
                            <div class="empty-title">Sin tareas pendientes</div>
                            <div class="empty-desc">No tienes registros en espera de aprobación por el administrador.</div>
                        </div>
                    @endif
                </div>
            </div>

            <div class="panel">
                <div class="panel-header">
                    <div class="panel-title">
                        <svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                        Actividad reciente
                    </div>
                </div>
                <div class="panel-body">
                    @php
                        $actividadesColores = ['blue', 'green', 'orange', 'red'];
                    @endphp
                    @if(isset($actividadReciente) && $actividadReciente->count() > 0)
                        <div class="activity-list">
                            @foreach($actividadReciente as $index => $actividad)
                                @php
                                    $color = $actividadesColores[$index % count($actividadesColores)];
                                @endphp
                                <div class="activity-item">
                                    <div class="activity-dot {{ $color }}">
                                        <svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
                                    </div>
                                    <div class="activity-content">
                                        <div class="activity-text"><strong>{{ $actividad->usuario ? $actividad->usuario->name : 'Usuario' }}</strong> {{ $actividad->accion }} en {{ $actividad->tabla_afectada }}</div>
                                        <div class="activity-time">{{ $actividad->fecha_hora ? \Carbon\Carbon::parse($actividad->fecha_hora)->diffForHumans() : 'Recientemente' }}</div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="empty-state">
                            <div class="empty-icon">
                                <svg viewBox="0 0 24 24" stroke="currentColor" fill="none" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                            </div>
                            <div class="empty-title">Sin actividad reciente</div>
                            <div class="empty-desc">No has registrado movimientos recientes en el sistema.</div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>
