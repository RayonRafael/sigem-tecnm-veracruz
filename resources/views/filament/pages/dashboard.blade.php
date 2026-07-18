<x-filament-panels::page>
    <div x-data="{ activeModal: null }" class="sigem-dashboard">
        <style>
            /* CSS from previous version */
            .sigem-dashboard {
                --bg: #f0f4f8;
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
                --shadow-lg: 0 10px 25px rgba(0,0,0,0.15);

                font-family: 'DM Sans', sans-serif;
                color: var(--text-primary);
            }

            .sigem-dashboard * { box-sizing: border-box; }

            .bento-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; }
            .bento-item { background: var(--card-bg); border: 1px solid var(--border); border-radius: var(--radius); padding: 24px; box-shadow: var(--shadow); transition: transform 0.2s ease, box-shadow 0.2s ease; display: flex; flex-direction: column; }
            .bento-item:hover { transform: scale(1.01); box-shadow: var(--shadow-md); }
            .span-2 { grid-column: span 2; }
            .bento-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; }
            .bento-title { font-size: 16px; font-weight: 700; color: var(--text-primary); display: flex; align-items: center; gap: 8px; }
            .bento-title svg { width: 20px; height: 20px; stroke: var(--accent); fill: none; stroke-width: 2; }

            .btn { display: inline-flex; align-items: center; gap: 6px; padding: 8px 14px; border-radius: var(--radius-sm); font-size: 13px; font-weight: 600; border: none; cursor: pointer; transition: all 0.15s ease; text-decoration: none; }
            .btn-primary { background: var(--accent); color: #fff; }
            .btn-primary:hover { background: var(--accent-dark); }
            .btn-secondary { background: var(--bg); color: var(--text-primary); border: 1px solid var(--border); }
            .btn-secondary:hover { border-color: var(--accent); color: var(--accent); }
            .btn-danger { background: var(--danger-light); color: var(--danger); }
            .btn-danger:hover { background: var(--danger); color: #fff; }
            .btn-sm { padding: 4px 8px; font-size: 11px; }
            .btn-icon { padding: 6px; }

            .welcome-stats { display: flex; gap: 20px; margin: 20px 0; }
            .welcome-stat-item { flex: 1; background: var(--bg); padding: 16px; border-radius: var(--radius-sm); border: 1px solid var(--border-light); }
            .welcome-stat-value { font-family: 'JetBrains Mono', monospace; font-size: 28px; font-weight: 700; line-height: 1.2; }
            .welcome-stat-label { font-size: 13px; color: var(--text-secondary); font-weight: 500; }
            
            .alert-banner { display: flex; align-items: center; gap: 12px; padding: 12px 16px; border-radius: var(--radius-sm); margin-bottom: 20px; font-size: 13px; font-weight: 500; }
            .alert-banner.warning { background: var(--warning-light); border: 1px solid #fcd34d; color: #92400e; }
            .alert-banner svg { width: 18px; height: 18px; flex-shrink: 0; stroke: currentColor; fill: none; stroke-width: 2; }

            .mini-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
            .mini-table th { text-align: left; padding: 8px; font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; border-bottom: 1px solid var(--border); }
            .mini-table td { padding: 10px 8px; font-size: 13px; border-bottom: 1px solid var(--border-light); color: var(--text-primary); }
            .mini-table tr:last-child td { border-bottom: none; }
            .mono-text { font-family: 'JetBrains Mono', monospace; font-size: 12px; font-weight: 600; }

            .badge-status { display: inline-flex; align-items: center; gap: 5px; padding: 3px 8px; border-radius: 12px; font-size: 11px; font-weight: 600; }
            .badge-status.active { background: var(--success-light); color: var(--success); }
            .badge-status.warning { background: var(--warning-light); color: #b45309; }
            .badge-status.danger { background: var(--danger-light); color: var(--danger); }
            .badge-status.info { background: var(--info-light); color: var(--info); }
            .badge-status::before { content: ''; width: 5px; height: 5px; border-radius: 50%; background: currentColor; }

            .catalog-grid { display: grid; grid-template-columns: repeat(9, 1fr); gap: 12px; margin-top: 10px; }
            .catalog-item { display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 8px; padding: 16px 8px; border-radius: var(--radius-sm); background: var(--bg); border: 1px solid var(--border); cursor: pointer; transition: all 0.2s; text-align: center; }
            .catalog-item:hover { background: var(--accent-light); border-color: var(--accent); transform: translateY(-2px); }
            .catalog-icon { width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; border-radius: 8px; background: var(--card-bg); box-shadow: 0 2px 4px rgba(0,0,0,0.05); color: var(--accent); }
            .catalog-icon svg { width: 18px; height: 18px; stroke: currentColor; fill: none; stroke-width: 1.8; }
            .catalog-label { font-size: 11px; font-weight: 600; color: var(--text-primary); line-height: 1.2; }

            .activity-item { display: flex; align-items: flex-start; gap: 12px; padding: 12px 0; border-bottom: 1px solid var(--border-light); }
            .activity-item:last-child { border-bottom: none; }
            .activity-dot { width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0; background: var(--accent-light); color: var(--accent); }
            .activity-dot svg { width: 14px; height: 14px; stroke: currentColor; fill: none; stroke-width: 2; }
            .activity-text { font-size: 13px; color: var(--text-primary); line-height: 1.4; }
            .activity-time { font-size: 11px; color: var(--text-muted); margin-top: 2px; }

            /* MODALS */
            .modal-backdrop { position: fixed; inset: 0; background: rgba(11, 29, 58, 0.4); backdrop-filter: blur(4px); z-index: 1000; display: flex; align-items: center; justify-content: center; padding: 20px; }
            .modal-window { background: var(--card-bg); border-radius: var(--radius); width: 100%; max-width: 900px; box-shadow: var(--shadow-lg); overflow: hidden; display: flex; flex-direction: column; max-height: 90vh; }
            .modal-header { padding: 20px 24px; border-bottom: 1px solid var(--border); display: flex; align-items: center; justify-content: space-between; }
            .modal-title { font-size: 18px; font-weight: 700; display: flex; align-items: center; gap: 8px; }
            .modal-title svg { width: 22px; height: 22px; stroke: var(--accent); fill: none; }
            .modal-close { background: none; border: none; cursor: pointer; color: var(--text-muted); padding: 4px; }
            .modal-close:hover { color: var(--danger); }
            .modal-close svg { width: 20px; height: 20px; stroke: currentColor; fill: none; stroke-width: 2; }
            .modal-body { padding: 0; overflow-y: auto; background: var(--bg); }
            
            /* CRUD Table in Modal */
            .crud-table-wrap { width: 100%; overflow-x: auto; }
            .crud-table { width: 100%; border-collapse: collapse; background: var(--card-bg); }
            .crud-table th { text-align: left; padding: 12px 16px; font-size: 12px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; border-bottom: 1px solid var(--border); background: var(--bg); position: sticky; top: 0; z-index: 10; }
            .crud-table td { padding: 12px 16px; font-size: 13px; border-bottom: 1px solid var(--border-light); color: var(--text-primary); vertical-align: middle; }
            .crud-table tr:hover td { background: var(--accent-light); }
            .crud-actions { display: flex; gap: 6px; }

            .empty-state { padding: 40px 20px; text-align: center; color: var(--text-muted); }
            .empty-state svg { width: 48px; height: 48px; stroke: var(--border); margin-bottom: 12px; }

            @media (max-width: 1024px) {
                .bento-grid { grid-template-columns: 1fr; }
                .span-2 { grid-column: span 1; }
                .catalog-grid { grid-template-columns: repeat(3, 1fr); }
                .welcome-stats { flex-wrap: wrap; }
            }
        </style>

        <div class="bento-grid">
            
            <!-- 1. WELCOME CARD (Span 2) -->
            <div class="bento-item span-2">
                <div class="bento-header">
                    <div class="bento-title">
                        <svg viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                        Resumen del Sistema
                    </div>
                    <div style="display: flex; gap: 8px;">
                        <a href="{{ url('/admin/inventarios/create') }}" class="btn btn-primary">Nuevo Activo</a>
                        <a href="{{ url('/admin/solicituds/create') }}" class="btn btn-secondary">Nueva Solicitud</a>
                        <a href="{{ url('/admin/mantenimientos/create') }}" class="btn btn-secondary">Reportar Falla</a>
                    </div>
                </div>

                @if(($materialesStockBajoCount ?? 0) > 0)
                <div class="alert-banner warning">
                    <svg viewBox="0 0 24 24"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                    <span><strong>{{ $materialesStockBajoCount }} materiales</strong> por debajo del stock mínimo. Verifica los catálogos.</span>
                </div>
                @endif

                <div class="welcome-stats">
                    <div class="welcome-stat-item" style="border-top: 3px solid var(--accent);">
                        <div class="welcome-stat-value">{{ $totalActivos ?? 0 }}</div>
                        <div class="welcome-stat-label">Total de Activos</div>
                    </div>
                    <div class="welcome-stat-item" style="border-top: 3px solid var(--success);">
                        <div class="welcome-stat-value">{{ $activosBueno ?? 0 }}</div>
                        <div class="welcome-stat-label">En Buen Estado</div>
                    </div>
                    <div class="welcome-stat-item" style="border-top: 3px solid var(--warning);">
                        <div class="welcome-stat-value">{{ $mantenimientosPendientes ?? 0 }}</div>
                        <div class="welcome-stat-label">Mantenimientos Pend.</div>
                    </div>
                    <div class="welcome-stat-item" style="border-top: 3px solid var(--danger);">
                        <div class="welcome-stat-value">{{ $materialesStockBajoCount ?? 0 }}</div>
                        <div class="welcome-stat-label">Alertas de Stock</div>
                    </div>
                </div>
            </div>

            <!-- 2. ACTIVIDAD RECIENTE -->
            <div class="bento-item">
                <div class="bento-header">
                    <div class="bento-title">
                        <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                        Actividad Reciente
                    </div>
                </div>
                <div class="activity-list">
                    @forelse($actividadReciente ?? [] as $actividad)
                        <div class="activity-item">
                            <div class="activity-dot">
                                <svg viewBox="0 0 24 24"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
                            </div>
                            <div>
                                <div class="activity-text"><strong>{{ $actividad->usuario?->name ?? 'Usuario' }}</strong> {{ $actividad->accion }} en {{ $actividad->tabla_afectada }}</div>
                                <div class="activity-time">{{ \Carbon\Carbon::parse($actividad->fecha_hora)->diffForHumans() }}</div>
                            </div>
                        </div>
                    @empty
                        <div style="padding: 20px; text-align: center; color: var(--text-muted); font-size: 13px;">Sin actividad reciente.</div>
                    @endforelse
                </div>
            </div>

            <!-- 3. INVENTARIO -->
            <div class="bento-item">
                <div class="bento-header">
                    <div class="bento-title">
                        <svg viewBox="0 0 24 24"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>
                        Últimos Registros
                    </div>
                    <button class="btn btn-secondary btn-sm" @click="activeModal = 'modulo-inventario'">Ver todos</button>
                </div>
                <table class="mini-table">
                    <thead><tr><th>N/S</th><th>Material</th><th>Estado</th></tr></thead>
                    <tbody>
                        @forelse($inventariosRecientes ?? [] as $inv)
                        <tr>
                            <td class="mono-text">{{ $inv->num_serie }}</td>
                            <td>{{ Str::limit($inv->material?->nombre ?? 'N/A', 25) }}</td>
                            <td><span class="badge-status {{ in_array($inv->estado, ['Bueno','Operativo']) ? 'active' : 'warning' }}">{{ $inv->estado }}</span></td>
                        </tr>
                        @empty
                        <tr><td colspan="3" style="text-align: center; color: var(--text-muted);">No hay registros.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- 4. SOLICITUDES -->
            <div class="bento-item">
                <div class="bento-header">
                    <div class="bento-title">
                        <svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                        Solicitudes Pendientes
                    </div>
                    <button class="btn btn-secondary btn-sm" @click="activeModal = 'modulo-solicitudes'">Ver todas</button>
                </div>
                <table class="mini-table">
                    <thead><tr><th>Folio</th><th>Usuario</th><th>Fecha</th></tr></thead>
                    <tbody>
                        @forelse($solicitudesRecientes ?? [] as $sol)
                        <tr>
                            <td class="mono-text">SOL-{{ str_pad($sol->id_solicitud, 4, '0', STR_PAD_LEFT) }}</td>
                            <td>{{ Str::limit($sol->usuario?->name ?? 'N/A', 20) }}</td>
                            <td>{{ $sol->fecha_solicitud ? $sol->fecha_solicitud->format('d/m/Y') : 'N/A' }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="3" style="text-align: center; color: var(--text-muted);">No hay solicitudes pendientes.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- 5. MANTENIMIENTO -->
            <div class="bento-item">
                <div class="bento-header">
                    <div class="bento-title">
                        <svg viewBox="0 0 24 24"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>
                        Mantenimientos
                    </div>
                    <button class="btn btn-secondary btn-sm" @click="activeModal = 'modulo-mantenimiento'">Ver todos</button>
                </div>
                <table class="mini-table">
                    <thead><tr><th>Activo</th><th>Técnico</th><th>Estado</th></tr></thead>
                    <tbody>
                        @forelse($mantenimientosRecientes ?? [] as $mant)
                        <tr>
                            <td class="mono-text">{{ $mant->inventario?->num_serie ?? 'N/A' }}</td>
                            <td>{{ Str::limit($mant->nombre_tecnico ?? 'Sin asignar', 20) }}</td>
                            <td><span class="badge-status warning">{{ $mant->estado }}</span></td>
                        </tr>
                        @empty
                        <tr><td colspan="3" style="text-align: center; color: var(--text-muted);">No hay mantenimientos pendientes.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- 6. CATÁLOGOS (Span 2) -->
            <div class="bento-item span-2">
                <div class="bento-header">
                    <div class="bento-title">
                        <svg viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                        Catálogos del Sistema
                    </div>
                </div>
                
                @php
                    $catalogos = [
                        ['id' => 'departamentos', 'nombre' => 'Departamentos', 'icon' => '<path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/>'],
                        ['id' => 'materiales', 'nombre' => 'Materiales', 'icon' => '<circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>'],
                        ['id' => 'areas', 'nombre' => 'Áreas', 'icon' => '<rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="9" y1="21" x2="9" y2="9"/>'],
                        ['id' => 'marcas', 'nombre' => 'Marcas', 'icon' => '<path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/>'],
                        ['id' => 'tipos', 'nombre' => 'Tipos', 'icon' => '<path d="M4 7V4h16v3"/><path d="M9 20h6"/><path d="M12 4v16"/>'],
                        ['id' => 'unidades', 'nombre' => 'Unidades', 'icon' => '<line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/>'],
                        ['id' => 'proveedores', 'nombre' => 'Proveedores', 'icon' => '<rect x="2" y="7" width="20" height="14" rx="2" ry="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/>'],
                        ['id' => 'receptores', 'nombre' => 'Receptores', 'icon' => '<path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><line x1="20" y1="8" x2="20" y2="14"/><line x1="23" y1="11" x2="17" y2="11"/>'],
                        ['id' => 'usuarios', 'nombre' => 'Usuarios', 'icon' => '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>'],
                    ];
                @endphp

                <div class="catalog-grid">
                    @foreach($catalogos as $cat)
                    <div class="catalog-item" @click="activeModal = 'cat-{{ $cat['id'] }}'">
                        <div class="catalog-icon">
                            <svg viewBox="0 0 24 24">{!! $cat['icon'] !!}</svg>
                        </div>
                        <div class="catalog-label">{{ $cat['nombre'] }}</div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- MODALS (ALPINE.JS) -->
        
        <!-- Componente reusable de modal macro -->
        @php
            $modalMacros = [
                // 9 Catalogs
                [
                    'id' => 'cat-departamentos', 'title' => 'Departamentos', 'create' => url('/admin/departamentos/create'),
                    'cols' => ['Nombre', 'Descripción'],
                    'data' => $departamentosList ?? [],
                    'editPrefix' => '/admin/departamentos/',
                    'row' => fn($i) => ["<td>{$i->nombre}</td>", "<td>{$i->descripcion}</td>"]
                ],
                [
                    'id' => 'cat-materiales', 'title' => 'Materiales', 'create' => url('/admin/materials/create'),
                    'cols' => ['Nombre', 'Tipo', 'Unidad', 'Stock Mín.'],
                    'data' => $materialesList ?? [],
                    'editPrefix' => '/admin/materials/',
                    'row' => fn($i) => ["<td>{$i->nombre}</td>", "<td>".($i->tipo?->nombre ?? '')."</td>", "<td>".($i->unidad?->abreviatura ?? '')."</td>", "<td>{$i->stock_minimo}</td>"]
                ],
                [
                    'id' => 'cat-areas', 'title' => 'Áreas', 'create' => url('/admin/areas/create'),
                    'cols' => ['Nombre', 'Departamento'],
                    'data' => $areasList ?? [],
                    'editPrefix' => '/admin/areas/',
                    'row' => fn($i) => ["<td>{$i->nombre}</td>", "<td>".($i->departamento?->nombre ?? '')."</td>"]
                ],
                [
                    'id' => 'cat-marcas', 'title' => 'Marcas', 'create' => url('/admin/marca-materials/create'),
                    'cols' => ['Nombre', 'Cant. Materiales'],
                    'data' => $marcasList ?? [],
                    'editPrefix' => '/admin/marca-materials/',
                    'row' => fn($i) => ["<td>{$i->nombre}</td>", "<td>{$i->materiales_count}</td>"]
                ],
                [
                    'id' => 'cat-tipos', 'title' => 'Tipos de Material', 'create' => url('/admin/tipo-materials/create'),
                    'cols' => ['Nombre'],
                    'data' => $tiposList ?? [],
                    'editPrefix' => '/admin/tipo-materials/',
                    'row' => fn($i) => ["<td>{$i->nombre}</td>"]
                ],
                [
                    'id' => 'cat-unidades', 'title' => 'Unidades de Medida', 'create' => url('/admin/unidad-medidas/create'),
                    'cols' => ['Nombre', 'Abreviatura'],
                    'data' => $unidadesList ?? [],
                    'editPrefix' => '/admin/unidad-medidas/',
                    'row' => fn($i) => ["<td>{$i->nombre}</td>", "<td>{$i->abreviatura}</td>"]
                ],
                [
                    'id' => 'cat-proveedores', 'title' => 'Proveedores', 'create' => url('/admin/proveedors/create'),
                    'cols' => ['Empresa', 'Contacto', 'RFC'],
                    'data' => $proveedoresList ?? [],
                    'editPrefix' => '/admin/proveedors/',
                    'row' => fn($i) => ["<td>{$i->nombre_empresa}</td>", "<td>{$i->nombre_contacto}</td>", "<td>{$i->rfc}</td>"]
                ],
                [
                    'id' => 'cat-receptores', 'title' => 'Receptores', 'create' => url('/admin/receptors/create'),
                    'cols' => ['Nombre', 'Departamento'],
                    'data' => $receptoresList ?? [],
                    'editPrefix' => '/admin/receptors/',
                    'row' => fn($i) => ["<td>{$i->nombre} {$i->apellido_paterno}</td>", "<td>".($i->area?->departamento?->nombre ?? 'N/A')."</td>"]
                ],
                [
                    'id' => 'cat-usuarios', 'title' => 'Usuarios', 'create' => url('/admin/users/create'),
                    'cols' => ['Nombre', 'Email'],
                    'data' => $usuariosList ?? [],
                    'editPrefix' => '/admin/users/',
                    'row' => fn($i) => ["<td>{$i->name}</td>", "<td>{$i->email}</td>"]
                ],
                // 3 Modules
                [
                    'id' => 'modulo-inventario', 'title' => 'Inventario General', 'create' => url('/admin/inventarios/create'),
                    'cols' => ['N/S', 'Material', 'Marca', 'Estado', 'Ubicación'],
                    'data' => $inventariosCompletos ?? [],
                    'editPrefix' => '/admin/inventarios/',
                    'row' => fn($i) => ["<td class='mono-text'>{$i->num_serie}</td>", "<td>".($i->material?->nombre ?? '')."</td>", "<td>".($i->material?->marca?->nombre ?? '')."</td>", "<td><span class='badge-status ".(in_array($i->estado, ['Bueno','Operativo']) ? 'active' : 'warning')."'>{$i->estado}</span></td>", "<td>{$i->ubicacion_fisica}</td>"]
                ],
                [
                    'id' => 'modulo-solicitudes', 'title' => 'Solicitudes', 'create' => url('/admin/solicituds/create'),
                    'cols' => ['Folio', 'Usuario', 'Fecha', 'Estado'],
                    'data' => $solicitudesCompletas ?? [],
                    'editPrefix' => '/admin/solicituds/',
                    'row' => fn($i) => ["<td class='mono-text'>SOL-".str_pad($i->id_solicitud, 4, '0', STR_PAD_LEFT)."</td>", "<td>".($i->usuario?->name ?? '')."</td>", "<td>".($i->fecha_solicitud ? $i->fecha_solicitud->format('d/m/Y') : '')."</td>", "<td><span class='badge-status warning'>{$i->estado}</span></td>"]
                ],
                [
                    'id' => 'modulo-mantenimiento', 'title' => 'Mantenimientos', 'create' => url('/admin/mantenimientos/create'),
                    'cols' => ['Activo', 'Técnico', 'Fecha', 'Estado'],
                    'data' => $mantenimientosCompletos ?? [],
                    'editPrefix' => '/admin/mantenimientos/',
                    'row' => fn($i) => ["<td class='mono-text'>".($i->inventario?->num_serie ?? '')."</td>", "<td>{$i->nombre_tecnico}</td>", "<td>".($i->fecha_solicitud ? $i->fecha_solicitud->format('d/m/Y') : '')."</td>", "<td><span class='badge-status warning'>{$i->estado}</span></td>"]
                ],
            ];
        @endphp

        @foreach($modalMacros as $m)
        <div class="modal-backdrop" 
             x-show="activeModal === '{{ $m['id'] }}'" 
             x-transition.opacity
             @click.self="activeModal = null"
             @keydown.escape.window="activeModal = null"
             style="display: none;">
            
            <div class="modal-window"
                 x-show="activeModal === '{{ $m['id'] }}'"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 transform scale-95"
                 x-transition:enter-end="opacity-100 transform scale-100"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100 transform scale-100"
                 x-transition:leave-end="opacity-0 transform scale-95">
                
                <div class="modal-header">
                    <div class="modal-title">
                        {{ $m['title'] }}
                    </div>
                    <div style="display: flex; gap: 12px; align-items: center;">
                        <a href="{{ $m['create'] }}" class="btn btn-primary">Nuevo Registro</a>
                        <button class="modal-close" @click="activeModal = null">
                            <svg viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                        </button>
                    </div>
                </div>
                
                <div class="modal-body">
                    @if(count($m['data']) > 0)
                        <div class="crud-table-wrap">
                            <table class="crud-table">
                                <thead>
                                    <tr>
                                        @foreach($m['cols'] as $col)
                                            <th>{{ $col }}</th>
                                        @endforeach
                                        <th style="width: 100px;">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($m['data'] as $item)
                                        @php
                                            $key = $item->getKey();
                                        @endphp
                                        <tr>
                                            {!! implode('', $m['row']($item)) !!}
                                            <td>
                                                <div class="crud-actions">
                                                    <a href="{{ url($m['editPrefix'] . $key . '/edit') }}" class="btn btn-secondary btn-icon" title="Editar">
                                                        <svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" fill="none" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                                    </a>
                                                    <!-- Para eliminar se requeriría un formulario, usar la vista de Filament si desean eliminar o dejar solo edición -->
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="empty-state">
                            <svg viewBox="0 0 24 24" fill="none" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><line x1="9" y1="9" x2="15" y2="15"/><line x1="15" y1="9" x2="9" y2="15"/></svg>
                            <p>No hay registros en este módulo.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
        @endforeach

    </div>
</x-filament-panels::page>