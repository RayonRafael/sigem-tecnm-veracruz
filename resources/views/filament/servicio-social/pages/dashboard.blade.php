<x-filament-panels::page>
    <div x-data="sigemDashboardSS()" class="sigem-professional" x-cloak>

        @php
            $user = auth()->user();
            $hour = (int) date('H');
            $saludo = $hour < 12 ? 'Buenos días' : ($hour < 19 ? 'Buenas tardes' : 'Buenas noches');
            $nombreCorto = explode(' ', $user->name ?? 'Usuario')[0];
        @endphp

        {{-- 1. HERO CON ACCIONES --}}
        <div class="sigem-hero">
            <div>
                <h1 id="sigem-hero-hi">👋 <span x-text="getGreeting()">{{ $saludo }}</span>, {{ $nombreCorto }}</h1>
                <p>Gestiona tus solicitudes de material, reportes de mantenimiento y consulta el inventario del instituto.</p>
            </div>
        </div>

        {{-- 2. FILA 1 — 4 TARJETAS DE ACCIÓN --}}
        <div class="sigem-quick-grid">
            {{-- 1. Crear solicitud --}}
            <a class="sigem-quick" href="{{ url('/servicio-social/solicituds?abrir=create') }}">
                <div class="arrow">→</div>
                <div class="sigem-quick-ico sigem-i-primary">📋</div>
                <div class="sigem-quick-title">Crear solicitud</div>
                <div class="sigem-quick-desc">Petición de material o consumibles</div>
            </a>

            {{-- 2. Reportar mantenimiento --}}
            <a class="sigem-quick" href="{{ url('/servicio-social/mantenimientos?abrir=create') }}">
                <div class="arrow">→</div>
                <div class="sigem-quick-ico sigem-i-warning">🔧</div>
                <div class="sigem-quick-title">Reportar mantenimiento</div>
                <div class="sigem-quick-desc">Reporte de falla técnica o servicio</div>
            </a>

            {{-- 3. Registrar equipo --}}
            <a class="sigem-quick" href="javascript:void(0)" wire:click="mountAction('registrarEquipo')">
                <div class="arrow">→</div>
                <div class="sigem-quick-ico sigem-i-success">＋</div>
                <div class="sigem-quick-title">Registrar equipo</div>
                <div class="sigem-quick-desc">Alta de equipo en inventario</div>
            </a>

            {{-- 4. Seguir mis trámites --}}
            <a class="sigem-quick" href="{{ url('/servicio-social/solicituds') }}">
                <div class="arrow">→</div>
                <div class="sigem-quick-ico sigem-i-info">🕘</div>
                <div class="sigem-quick-title">
                    Seguir mis trámites
                    @if(($misSolicitudesPendientes ?? 0) > 0)
                        <span class="sigem-qbadge">{{ $misSolicitudesPendientes }}</span>
                    @endif
                </div>
                <div class="sigem-quick-desc">Estado de solicitudes y órdenes</div>
            </a>
        </div>

        {{-- 3. FILA 2 — 4 TARJETAS KPI --}}
        <div class="sigem-stats-grid">
            {{-- 1. Equipos registrados --}}
            <div class="sigem-stat">
                <div class="sigem-stat-ico sigem-i-primary">📦</div>
                <div class="sigem-stat-val">{{ $totalActivos ?? 0 }}</div>
                <div class="sigem-stat-label">Equipos registrados</div>
                <div class="sigem-stat-hint sigem-h-info">✏️ Alta, cambios y bajas</div>
            </div>

            {{-- 2. Mis solicitudes --}}
            <div class="sigem-stat">
                <div class="sigem-stat-ico sigem-i-primary">📋</div>
                <div class="sigem-stat-val">{{ $misSolicitudes ?? 0 }}</div>
                <div class="sigem-stat-label">Mis solicitudes</div>
                <div class="sigem-stat-hint sigem-h-ok">✓ {{ $solicitudesAutorizadas ?? 0 }} autorizadas · {{ $misSolicitudesPendientes ?? 0 }} pendientes</div>
            </div>

            {{-- 3. Mis mantenimientos --}}
            <div class="sigem-stat">
                <div class="sigem-stat-ico sigem-i-warning">🔧</div>
                <div class="sigem-stat-val">{{ $misMantenimientos ?? 0 }}</div>
                <div class="sigem-stat-label">Mis mantenimientos</div>
                <div class="sigem-stat-hint sigem-h-warn">{{ $mantenimientoEnRevision ?? 0 }} en revisión</div>
            </div>

            {{-- 4. Equipos disponibles --}}
            <div class="sigem-stat">
                <div class="sigem-stat-ico sigem-i-success">✓</div>
                <div class="sigem-stat-val">{{ $inventarioDisponibles ?? 0 }}</div>
                <div class="sigem-stat-label">Equipos disponibles</div>
                <div class="sigem-stat-hint sigem-h-info">de {{ $totalActivos ?? 0 }} artículos registrados</div>
            </div>
        </div>

        {{-- 4. INVENTARIO GENERAL DIRECTAMENTE DEBAJO --}}
        <div x-data="{ showGrafica: false }" style="margin-top: 14px;">
            <div class="sigem-section-head">
                <div>
                    <h2>💻 Inventario general</h2>
                    <p>Consulta los equipos y activos registrados en el sistema.</p>
                </div>
                <div class="sigem-page-actions">
                    <button type="button"
                            class="sigem-rep-btn-sec"
                            x-on:click="showGrafica = !showGrafica"
                            :style="showGrafica ? 'background: var(--sig-border);' : ''"
                            title="Alternar vista de gráfica de dona del inventario">
                        &#x1F4CA; Gráfica
                    </button>
                    <button type="button"
                            wire:click="mountAction('registrarEquipo')"
                            class="sigem-btn-primary"
                            title="Registrar un nuevo equipo en el inventario">
                        &#xFF0B; Registrar equipo
                    </button>
                </div>
            </div>

            <div class="sigem-inv-layout" :class="showGrafica ? '' : 'sigem-inv-no-chart'" style="margin-top: 14px;">
                <div class="sigem-inv-main" style="min-width: 0;">
                    <div class="sigem-rep-view sigem-dash-table" style="margin-bottom: 0;">
                        <div class="sigem-rep-table-wrap">
                            <table class="fi-ta-table">
                                <thead>
                                    <tr>
                                        <th style="width: 130px;">CÓDIGO</th>
                                        <th>EQUIPO</th>
                                        <th style="width: 125px;">CATEGORÍA</th>
                                        <th style="width: 130px;">ESTADO</th>
                                        <th style="width: 130px;">ÁREA</th>
                                        <th style="width: 104px; text-align: center;">ACCIONES</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                        $inventariosTabla = collect($inventariosCompletos ?? [])->take(10);
                                    @endphp
                                    @forelse($inventariosTabla as $inv)
                                        @php
                                            $codigo = $inv->num_serie ?: ('#' . $inv->id_inventario);
                                            $nombreEquipo = $inv->material?->nombre ?? 'Equipo general';
                                            $serieSub = $inv->num_serie ? 'S/N: ' . $inv->num_serie : 'Sin número de serie';
                                            $categoria = $inv->material?->tipo?->nombre ?? 'General';
                                            $areaUbicacion = $inv->ubicacion_fisica ?: 'Sin área asignada';
                                            $estado = $inv->estado ?? 'Sin estado';

                                            $pillCls = match ($estado) {
                                                'Disponible' => 'ok',
                                                'Asignado' => 'info',
                                                'Reasignado' => 'reassign',
                                                'En Mantenimiento' => 'warn',
                                                'Dañado' => 'danger',
                                                'Baja' => 'neutral',
                                                default => 'neutral',
                                            };
                                        @endphp
                                        <tr>
                                            <td>
                                                <span class="sigem-td-folio" style="font-weight: 700;">{{ $codigo }}</span>
                                            </td>
                                            <td>
                                                <div style="display: flex; flex-direction: column;">
                                                    <strong style="color: var(--sig-text);">{{ $nombreEquipo }}</strong>
                                                    <span style="font-size: 13px; color: var(--sig-muted);">{{ $serieSub }}</span>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="sigem-pill neutral" style="font-weight: 500;">{{ $categoria }}</span>
                                            </td>
                                            <td>
                                                <span class="sigem-pill {{ $pillCls }}">{{ $estado }}</span>
                                            </td>
                                            <td>
                                                <span style="font-size: 14.5px; color: var(--sig-muted);">{{ $areaUbicacion }}</span>
                                            </td>
                                            <td class="fi-ta-actions-cell" style="width: 104px; text-align: center; white-space: nowrap;">
                                                <div class="fi-ta-actions" style="display: inline-flex; align-items: center; justify-content: center; gap: 3px;">
                                                    <button type="button"
                                                            wire:click="verEquipo({{ $inv->id_inventario }})"
                                                            class="fi-icon-btn sigem-dash-btn"
                                                            title="Ver detalle"
                                                            aria-label="Ver detalle">
                                                    </button>
                                                    <button type="button"
                                                            wire:click="editarEquipo({{ $inv->id_inventario }})"
                                                            class="fi-icon-btn sigem-dash-btn"
                                                            title="Editar"
                                                            aria-label="Editar">
                                                    </button>
                                                    <button type="button"
                                                            wire:click="eliminarEquipo({{ $inv->id_inventario }})"
                                                            class="fi-icon-btn sigem-dash-btn"
                                                            title="Eliminar"
                                                            aria-label="Eliminar">
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="text-center py-6 text-muted-util" style="text-align: center; padding: 24px;">
                                                No hay registros de inventario disponibles.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                {{-- Panel Lateral: Gráfica de Dona --}}
                <div class="sigem-inv-side" x-show="showGrafica" x-transition.opacity style="display: none;">
                    <div class="sigem-chart-card">
                        <div class="sigem-chart-head">
                            <h3>Estado del Inventario</h3>
                            <button type="button" class="sigem-chart-close" x-on:click="showGrafica = false" title="Cerrar gráfica">✕</button>
                        </div>
                        <p class="sigem-chart-sub">Proporción de equipos por condición actual</p>

                        @php
                            $disp = $inventarioDisponibles ?? 0;
                            $mant = $inventarioEnMantenimiento ?? 0;
                            $asig = $inventarioAsignados ?? 0;
                            $dan  = $inventarioDanados ?? 0;
                            $totalChart = $disp + $mant + $asig + $dan;
                            $totalChart = $totalChart > 0 ? $totalChart : 1;

                            $pDisp = round(($disp / $totalChart) * 100, 1);
                            $pMant = round(($mant / $totalChart) * 100, 1);
                            $pAsig = round(($asig / $totalChart) * 100, 1);
                            $pDan  = round(($dan  / $totalChart) * 100, 1);

                            $cDisp = $pDisp;
                            $cMant = $cDisp + $pMant;
                            $cAsig = $cMant + $pAsig;
                        @endphp

                        <div class="sigem-donut-wrap">
                            <div class="sigem-donut" style="background: conic-gradient(
                                var(--sig-success) 0% {{ $cDisp }}%,
                                var(--sig-warning) {{ $cDisp }}% {{ $cMant }}%,
                                var(--sig-info) {{ $cMant }}% {{ $cAsig }}%,
                                var(--sig-danger) {{ $cAsig }}% 100%
                            );">
                                <div class="sigem-donut-hole">
                                    <span class="sigem-donut-total">{{ $totalChart > 1 ? ($disp + $mant + $asig + $dan) : $disp }}</span>
                                    <span class="sigem-donut-label">Total</span>
                                </div>
                            </div>
                        </div>

                        <div class="sigem-donut-legend">
                            <div class="sigem-legend-item">
                                <span class="sigem-legend-dot" style="background: var(--sig-success);"></span>
                                <span class="sigem-legend-name">Disponible</span>
                                <span class="sigem-legend-val">{{ $disp }} ({{ $pDisp }}%)</span>
                            </div>
                            <div class="sigem-legend-item">
                                <span class="sigem-legend-dot" style="background: var(--sig-warning);"></span>
                                <span class="sigem-legend-name">Mantenimiento</span>
                                <span class="sigem-legend-val">{{ $mant }} ({{ $pMant }}%)</span>
                            </div>
                            <div class="sigem-legend-item">
                                <span class="sigem-legend-dot" style="background: var(--sig-info);"></span>
                                <span class="sigem-legend-name">Asignado</span>
                                <span class="sigem-legend-val">{{ $asig }} ({{ $pAsig }}%)</span>
                            </div>
                            <div class="sigem-legend-item">
                                <span class="sigem-legend-dot" style="background: var(--sig-danger);"></span>
                                <span class="sigem-legend-name">Dañado / Baja</span>
                                <span class="sigem-legend-val">{{ $dan }} ({{ $pDan }}%)</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <script>
        function sigemDashboardSS() {
            return {
                getGreeting() {
                    const hour = new Date().getHours();
                    if (hour < 12) return 'Buenos días';
                    if (hour < 19) return 'Buenas tardes';
                    return 'Buenas noches';
                }
            };
        }
    </script>
</x-filament-panels::page>
