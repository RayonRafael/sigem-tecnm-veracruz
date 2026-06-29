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

        /* ===== ALERT BANNER ===== */
        .sigem-dashboard .alert-banner {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px 18px;
            border-radius: var(--radius-sm);
            margin-bottom: 24px;
            font-size: 14px;
            font-weight: 500;
        }

        .sigem-dashboard .alert-banner svg {
            width: 18px;
            height: 18px;
            flex-shrink: 0;
            stroke: currentColor;
            fill: none;
            stroke-width: 2;
        }

        .sigem-dashboard .alert-banner.warning {
            background: var(--warning-light);
            border: 1px solid #fcd34d;
            color: #92400e;
        }

        .sigem-dashboard .alert-banner.info {
            background: var(--accent-light);
            border: 1px solid #93c5fd;
            color: var(--accent-dark);
        }

        /* ===== DASHBOARD WIDGETS ===== */
        .sigem-dashboard .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
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
        .sigem-dashboard .stat-card.green::before { background: linear-gradient(90deg, var(--success), #34d399); }
        .sigem-dashboard .stat-card.orange::before { background: linear-gradient(90deg, var(--warning), #fbbf24); }
        .sigem-dashboard .stat-card.red::before { background: linear-gradient(90deg, var(--danger), #f87171); }

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
        .sigem-dashboard .stat-card.green .stat-icon { background: var(--success-light); }
        .sigem-dashboard .stat-card.green .stat-icon svg { stroke: var(--success); }
        .sigem-dashboard .stat-card.orange .stat-icon { background: var(--warning-light); }
        .sigem-dashboard .stat-card.orange .stat-icon svg { stroke: var(--warning); }
        .sigem-dashboard .stat-card.red .stat-icon { background: var(--danger-light); }
        .sigem-dashboard .stat-card.red .stat-icon svg { stroke: var(--danger); }

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

        .sigem-dashboard .stat-change {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 12px;
            font-weight: 600;
            margin-top: 12px;
            padding: 3px 8px;
            border-radius: 20px;
        }

        .sigem-dashboard .stat-change.up {
            background: var(--success-light);
            color: var(--success);
        }

        .sigem-dashboard .stat-change.down {
            background: var(--danger-light);
            color: var(--danger);
        }

        .sigem-dashboard .stat-change svg {
            width: 12px;
            height: 12px;
            stroke: currentColor;
            fill: none;
            stroke-width: 2.5;
        }

        /* ===== CHARTS & PANELS ===== */
        .sigem-dashboard .grid-2 {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
            margin-bottom: 28px;
        }

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

        /* ===== BAR CHART (CSS only) ===== */
        .sigem-dashboard .bar-chart {
            display: flex;
            align-items: flex-end;
            gap: 12px;
            height: 180px;
            padding-top: 10px;
            width: 100%;
        }

        .sigem-dashboard .bar-group {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
            height: 100%;
            justify-content: flex-end;
            min-width: 0;
        }

        .sigem-dashboard .bar {
            width: 100%;
            border-radius: 6px 6px 2px 2px;
            position: relative;
            transition: all 0.3s ease;
            cursor: pointer;
            min-height: 4px;
        }

        .sigem-dashboard .bar:hover {
            filter: brightness(1.1);
            transform: scaleY(1.02);
            transform-origin: bottom;
        }

        .sigem-dashboard .bar.blue { background: linear-gradient(180deg, var(--accent), #4f9cf7); }
        .sigem-dashboard .bar.green { background: linear-gradient(180deg, var(--success), #34d399); }
        .sigem-dashboard .bar.orange { background: linear-gradient(180deg, var(--warning), #fbbf24); }
        .sigem-dashboard .bar.red { background: linear-gradient(180deg, var(--danger), #f87171); }
        .sigem-dashboard .bar.purple { background: linear-gradient(180deg, #7c3aed, #a78bfa); }

        .sigem-dashboard .bar-label {
            font-size: 11px;
            color: var(--text-muted);
            font-weight: 500;
            text-align: center;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 100%;
            width: 100%;
        }

        .sigem-dashboard .bar-value {
            font-family: 'JetBrains Mono', monospace;
            font-size: 12px;
            font-weight: 700;
            color: var(--text-primary);
        }

        /* ===== DONUT CHART (CSS only) ===== */
        .sigem-dashboard .donut-container {
            display: flex;
            align-items: center;
            gap: 24px;
            justify-content: center;
            flex-wrap: wrap;
            height: 100%;
        }

        .sigem-dashboard .donut {
            width: 140px;
            height: 140px;
            border-radius: 50%;
            position: relative;
            flex-shrink: 0;
            box-shadow: var(--shadow);
        }

        .sigem-dashboard .donut-center {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            text-align: center;
            background: var(--card-bg);
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            box-shadow: inset 0 1px 3px rgba(0,0,0,0.05);
        }

        .sigem-dashboard .donut-center-value {
            font-family: 'JetBrains Mono', monospace;
            font-size: 22px;
            font-weight: 700;
            color: var(--text-primary);
            line-height: 1;
        }

        .sigem-dashboard .donut-center-label {
            font-size: 10px;
            color: var(--text-muted);
            font-weight: 500;
            margin-top: 4px;
        }

        .sigem-dashboard .donut-legend {
            display: flex;
            flex-direction: column;
            gap: 10px;
            flex: 1;
            min-width: 140px;
        }

        .sigem-dashboard .legend-item {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .sigem-dashboard .legend-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            flex-shrink: 0;
        }

        .sigem-dashboard .legend-label {
            font-size: 13px;
            color: var(--text-secondary);
            flex: 1;
        }

        .sigem-dashboard .legend-value {
            font-family: 'JetBrains Mono', monospace;
            font-size: 13px;
            font-weight: 700;
            color: var(--text-primary);
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
            .sigem-dashboard .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            .sigem-dashboard .grid-2,
            .sigem-dashboard .grid-2-equal {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .sigem-dashboard .stats-grid {
                grid-template-columns: 1fr;
            }
        }

        /* ===== STAGGER ANIMATIONS ===== */
        .sigem-dashboard .stat-card, 
        .sigem-dashboard .panel, 
        .sigem-dashboard .alert-banner {
            opacity: 0;
            animation: slideUp 0.4s ease forwards;
        }
        .sigem-dashboard .stat-card:nth-child(1), .sigem-dashboard .panel:nth-child(1) { animation-delay: 0.05s; }
        .sigem-dashboard .stat-card:nth-child(2), .sigem-dashboard .panel:nth-child(2) { animation-delay: 0.1s; }
        .sigem-dashboard .stat-card:nth-child(3), .sigem-dashboard .panel:nth-child(3) { animation-delay: 0.15s; }
        .sigem-dashboard .stat-card:nth-child(4), .sigem-dashboard .panel:nth-child(4) { animation-delay: 0.2s; }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(16px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>

    <div class="sigem-dashboard">
        <!-- Alert Banner -->
        @if(($materialesStockBajo ?? 0) > 0)
        <div class="alert-banner warning">
            <svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
            <span><strong>{{ $materialesStockBajo }} {{ $materialesStockBajo == 1 ? 'material' : 'materiales' }}</strong> por debajo del stock mínimo — <a href="{{ url('/admin/materials') }}" style="color: inherit; font-weight: 700;">Ver detalles</a></span>
        </div>
        @endif

        @php
            $tActivos = $totalActivos ?? 0;
            $activosBueno = ($activosPorEstado['Bueno'] ?? 0) + ($activosPorEstado['Operativo'] ?? 0);
            $porcentajeBueno = $tActivos > 0 ? round(($activosBueno / $tActivos) * 100) : 0;
            $mPendientes = $mantenimientosPendientes ?? 0;
            $mStockBajo = $materialesStockBajo ?? 0;
        @endphp

        <!-- Stats Cards -->
        <div class="stats-grid">
            <div class="stat-card blue">
                <div class="stat-icon">
                    <svg viewBox="0 0 24 24"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>
                </div>
                <div class="stat-value">{{ $tActivos }}</div>
                <div class="stat-label">Activos registrados</div>
            </div>
            <div class="stat-card green">
                <div class="stat-icon">
                    <svg viewBox="0 0 24 24"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                </div>
                <div class="stat-value">{{ $activosBueno }}</div>
                <div class="stat-label">En buen estado</div>
                @if($tActivos > 0)
                <div class="stat-change up">
                    <svg viewBox="0 0 24 24"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/></svg>
                    {{ $porcentajeBueno }}% del total
                </div>
                @endif
            </div>
            <div class="stat-card orange">
                <div class="stat-icon">
                    <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                </div>
                <div class="stat-value">{{ $mPendientes }}</div>
                <div class="stat-label">Mantenimientos pendientes</div>
            </div>
            <div class="stat-card red">
                <div class="stat-icon">
                    <svg viewBox="0 0 24 24"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                </div>
                <div class="stat-value">{{ $mStockBajo }}</div>
                <div class="stat-label">Stock bajo mínimo</div>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="grid-2">
            <div class="panel">
                <div class="panel-header">
                    <div class="panel-title">
                        <svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
                        Inventario por departamento
                    </div>
                    <a href="{{ url('/admin/inventarios') }}" class="panel-action">Ver todo</a>
                </div>
                <div class="panel-body">
                    @php
                        $ubicaciones = isset($inventarioPorUbicacion) && $inventarioPorUbicacion->count() > 0 ? $inventarioPorUbicacion : collect([]);
                        $maxUbicacion = $ubicaciones->max('total') ?: 1;
                        $coloresBarras = ['blue', 'green', 'orange', 'purple', 'blue', 'red'];
                    @endphp
                    @if($ubicaciones->count() > 0)
                        <div class="bar-chart">
                            @foreach($ubicaciones as $index => $ubicacion)
                                @php
                                    $colorBarra = $coloresBarras[$index % count($coloresBarras)];
                                    $altura = round(($ubicacion->total / $maxUbicacion) * 85);
                                    if ($altura < 15) $altura = 15; // Altura mínima visual
                                @endphp
                                <div class="bar-group">
                                    <div class="bar-value">{{ $ubicacion->total }}</div>
                                    <div class="bar {{ $colorBarra }}" style="height: {{ $altura }}%"></div>
                                    <div class="bar-label" title="{{ $ubicacion->ubicacion_fisica ?: 'General' }}">{{ $ubicacion->ubicacion_fisica ?: 'General' }}</div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="empty-state">
                            <div class="empty-icon">
                                <svg viewBox="0 0 24 24"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
                            </div>
                            <div class="empty-title">Sin inventario registrado</div>
                            <div class="empty-desc">No hay activos asignados a departamentos o ubicaciones.</div>
                        </div>
                    @endif
                </div>
            </div>

            <div class="panel">
                <div class="panel-header">
                    <div class="panel-title">
                        <svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M21.21 15.89A10 10 0 1 1 8 2.83"/><path d="M22 12A10 10 0 0 0 12 2v10z"/></svg>
                        Estado del inventario
                    </div>
                </div>
                <div class="panel-body">
                    @php
                        $activosPorEstadoArr = $activosPorEstado ?? [];
                        $totalEstado = array_sum($activosPorEstadoArr);
                        $cBueno = ($activosPorEstadoArr['Bueno'] ?? 0) + ($activosPorEstadoArr['Operativo'] ?? 0);
                        $cRegular = ($activosPorEstadoArr['Regular'] ?? 0) + ($activosPorEstadoArr['En Mantenimiento'] ?? 0);
                        $cDaniado = ($activosPorEstadoArr['Dañado'] ?? 0) + ($activosPorEstadoArr['De Baja'] ?? 0);
                        $cMantenimiento = ($activosPorEstadoArr['En mantenimiento'] ?? 0);
                        
                        if ($totalEstado > 0) {
                            $pBueno = round(($cBueno / $totalEstado) * 100);
                            $pRegular = $pBueno + round(($cRegular / $totalEstado) * 100);
                            $pDaniado = $pRegular + round(($cDaniado / $totalEstado) * 100);
                            if ($pRegular > 100) $pRegular = 100;
                            if ($pDaniado > 100) $pDaniado = 100;
                        }
                    @endphp
                    <div class="donut-container">
                        @if($totalEstado == 0)
                            <div class="donut" style="background: #e2e8f0;">
                                <div class="donut-center">
                                    <div class="donut-center-value">0</div>
                                    <div class="donut-center-label">Total</div>
                                </div>
                            </div>
                            <div class="donut-legend">
                                <div class="legend-item">
                                    <div class="legend-dot" style="background: var(--success)"></div>
                                    <div class="legend-label">Bueno</div>
                                    <div class="legend-value">0</div>
                                </div>
                                <div class="legend-item">
                                    <div class="legend-dot" style="background: var(--warning)"></div>
                                    <div class="legend-label">Regular</div>
                                    <div class="legend-value">0</div>
                                </div>
                                <div class="legend-item">
                                    <div class="legend-dot" style="background: var(--danger)"></div>
                                    <div class="legend-label">Dañado</div>
                                    <div class="legend-value">0</div>
                                </div>
                            </div>
                        @else
                            <div class="donut" style="background: conic-gradient(var(--success) 0% {{ $pBueno }}%, var(--warning) {{ $pBueno }}% {{ $pRegular }}%, var(--danger) {{ $pRegular }}% {{ $pDaniado }}%, #94a3b8 {{ $pDaniado }}% 100%);">
                                <div class="donut-center">
                                    <div class="donut-center-value">{{ $totalEstado }}</div>
                                    <div class="donut-center-label">Total</div>
                                </div>
                            </div>
                            <div class="donut-legend">
                                <div class="legend-item">
                                    <div class="legend-dot" style="background: var(--success)"></div>
                                    <div class="legend-label">Bueno / Operativo</div>
                                    <div class="legend-value">{{ $cBueno }}</div>
                                </div>
                                <div class="legend-item">
                                    <div class="legend-dot" style="background: var(--warning)"></div>
                                    <div class="legend-label">Regular / Mantenimiento</div>
                                    <div class="legend-value">{{ $cRegular }}</div>
                                </div>
                                <div class="legend-item">
                                    <div class="legend-dot" style="background: var(--danger)"></div>
                                    <div class="legend-label">Dañado / De Baja</div>
                                    <div class="legend-value">{{ $cDaniado }}</div>
                                </div>
                                @if($cMantenimiento > 0)
                                <div class="legend-item">
                                    <div class="legend-dot" style="background: #94a3b8"></div>
                                    <div class="legend-label">En mantenimiento</div>
                                    <div class="legend-value">{{ $cMantenimiento }}</div>
                                </div>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Activity & Alerts Row -->
        <div class="grid-2-equal">
            <div class="panel">
                <div class="panel-header">
                    <div class="panel-title">
                        <svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                        Actividad reciente
                    </div>
                </div>
                <div class="panel-body">
                    @php
                        $actividadesColores = ['blue', 'orange', 'green', 'red'];
                    @endphp
                    @if(isset($actividadReciente) && $actividadReciente->count() > 0)
                        <div class="activity-list">
                            @foreach($actividadReciente as $index => $actividad)
                                @php
                                    $color = $actividadesColores[$index % count($actividadesColores)];
                                @endphp
                                <div class="activity-item">
                                    <div class="activity-dot {{ $color }}">
                                        @if($color == 'blue')
                                            <svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
                                        @elseif($color == 'orange')
                                            <svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>
                                        @elseif($color == 'green')
                                            <svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                                        @else
                                            <svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                                        @endif
                                    </div>
                                    <div class="activity-content">
                                        @if(($tipoActividad ?? '') == 'bitacora')
                                            <div class="activity-text"><strong>{{ $actividad->usuario ? $actividad->usuario->name : 'Usuario' }}</strong> {{ $actividad->accion }} en {{ $actividad->tabla_afectada }}</div>
                                            <div class="activity-time">{{ $actividad->fecha_hora ? \Carbon\Carbon::parse($actividad->fecha_hora)->diffForHumans() : 'Recientemente' }}</div>
                                        @else
                                            <div class="activity-text"><strong>{{ $actividad->usuario ? $actividad->usuario->name : 'Usuario' }}</strong> registró solicitud de {{ $actividad->tipo_movimiento ?? 'movimiento' }}</div>
                                            <div class="activity-time">{{ $actividad->created_at ? $actividad->created_at->diffForHumans() : 'Recientemente' }}</div>
                                        @endif
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
                            <div class="empty-desc">No se han registrado movimientos recientes en el sistema.</div>
                        </div>
                    @endif
                </div>
            </div>

            <div class="panel">
                <div class="panel-header">
                    <div class="panel-title">
                        <svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                        Solicitudes pendientes
                    </div>
                    <a href="{{ url('/admin/solicituds') }}" class="panel-action">Ver todas</a>
                </div>
                <div class="panel-body">
                    @if(isset($solicitudesPendientes) && $solicitudesPendientes->count() > 0)
                        <div class="activity-list">
                            @foreach($solicitudesPendientes as $solicitud)
                                <div class="activity-item">
                                    <div class="activity-dot orange">
                                        <svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                                    </div>
                                    <div class="activity-content">
                                        <div class="activity-text"><strong>SOL-{{ str_pad($solicitud->id_solicitud, 4, '0', STR_PAD_LEFT) }}</strong> — {{ $solicitud->tipo_movimiento ?? 'Movimiento de inventario' }}</div>
                                        <div class="activity-time">Pendiente de autorización · {{ $solicitud->receptor ? $solicitud->receptor->nombre . ' ' . $solicitud->receptor->apellido_paterno : ($solicitud->usuario ? $solicitud->usuario->name : 'Área solicitante') }}</div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="empty-state">
                            <div class="empty-icon">
                                <svg viewBox="0 0 24 24" stroke="currentColor" fill="none" stroke-width="1.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                            </div>
                            <div class="empty-title">Sin solicitudes pendientes</div>
                            <div class="empty-desc">No hay solicitudes pendientes de autorización en este momento.</div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>