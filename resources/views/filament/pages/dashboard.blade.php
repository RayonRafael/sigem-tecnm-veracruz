<x-filament-panels::page>
    <div x-data="sigemDashboard()" class="sigem-professional" x-cloak>

        @php
            $user = auth()->user();
            $initial = substr($user->name ?? 'U', 0, 1);
            $role = $user->roles?->first()?->name ?? 'Usuario';
            $hour = (int) date('H');
            $saludo = $hour < 12 ? 'Buenos días' : ($hour < 19 ? 'Buenas tardes' : 'Buenas noches');
            $nombreCorto = explode(' ', $user->name ?? 'Usuario')[0];
            $porcentajeDisponibles = ($totalActivos ?? 0) > 0 ? round((($inventarioDisponibles ?? 0) / $totalActivos) * 100) : 0;
            $requierenAtencion = ($materialesStockBajoCount ?? 0) + ($inventarioDanados ?? 0);
        @endphp

        <!-- 1. HERO -->
        <div class="sigem-hero">
            <div>
                <h1 id="sigem-hero-hi">👋 <span x-text="getGreeting()">{{ $saludo }}</span>, {{ $nombreCorto }}</h1>
                <p>Supervisa las solicitudes de material y autorizaciones de mantenimiento del Centro de Cómputo.</p>
            </div>
        </div>

        <!-- 2. ACCIONES RÁPIDAS (4 CARDS) -->
        <div class="sigem-quick-grid">
            <!-- 1. Autorizar mantenimiento -->
            <a class="sigem-quick" href="{{ url('/admin/mantenimientos') }}">
                <div class="arrow">→</div>
                <div class="sigem-quick-ico sigem-i-warning">🔧</div>
                <div class="sigem-quick-title">
                    Autorizar mantenimiento
                    @if(($mantenimientosPendientes ?? 0) > 0)
                        <span class="sigem-qbadge">{{ $mantenimientosPendientes }}</span>
                    @endif
                </div>
                <div class="sigem-quick-desc">Hay reportes esperando tu revisión</div>
            </a>

            <!-- 2. Revisar solicitudes -->
            <a class="sigem-quick" href="{{ url('/admin/solicituds') }}">
                <div class="arrow">→</div>
                <div class="sigem-quick-ico sigem-i-primary">📋</div>
                <div class="sigem-quick-title">
                    Revisar solicitudes
                    @if(($solicitudesPendientes ?? 0) > 0)
                        <span class="sigem-qbadge">{{ $solicitudesPendientes }}</span>
                    @endif
                </div>
                <div class="sigem-quick-desc">Solicitudes de material por autorizar</div>
            </a>

            <!-- 3. Registrar activo -->
            <a class="sigem-quick" href="javascript:void(0)" wire:click="mountAction('registrarEquipo')">
                <div class="arrow">→</div>
                <div class="sigem-quick-ico sigem-i-success">＋</div>
                <div class="sigem-quick-title">Registrar activo</div>
                <div class="sigem-quick-desc">Alta de nuevo equipo en inventario</div>
            </a>

            <!-- 4. Ver bitácora -->
            {{-- Fase 10: Enlazado a la pestaña Bitácora de Configuración y Catálogos (/admin/configuracion) --}}
            <a class="sigem-quick" href="{{ url('/admin/configuracion') }}">
                <div class="arrow">→</div>
                <div class="sigem-quick-ico sigem-i-info">🕘</div>
                <div class="sigem-quick-title">Ver bitácora</div>
                <div class="sigem-quick-desc">Registro de actividad y movimientos</div>
            </a>
        </div>

        <!-- 3. KPIS (4 MÉTRICAS) -->
        <div class="sigem-stats-grid">
            <!-- 1. Equipos registrados -->
            <div class="sigem-stat">
                <div class="sigem-stat-ico sigem-i-primary">📦</div>
                <div class="sigem-stat-val">{{ $totalActivos ?? 0 }}</div>
                <div class="sigem-stat-label">Equipos registrados</div>
                <div class="sigem-stat-hint sigem-h-ok">↗ {{ $creadosEsteMes ?? 0 }} nuevos este mes</div>
            </div>

            <!-- 2. Disponibles -->
            <div class="sigem-stat">
                <div class="sigem-stat-ico sigem-i-success">✓</div>
                <div class="sigem-stat-val">{{ $inventarioDisponibles ?? 0 }}</div>
                <div class="sigem-stat-label">Disponibles para uso</div>
                <div class="sigem-stat-hint sigem-h-info">{{ $porcentajeDisponibles }}% del parque</div>
            </div>

            <!-- 3. En mantenimiento -->
            <div class="sigem-stat">
                <div class="sigem-stat-ico sigem-i-warning">🔧</div>
                <div class="sigem-stat-val">{{ $inventarioEnMantenimiento ?? 0 }}</div>
                <div class="sigem-stat-label">En mantenimiento</div>
                <div class="sigem-stat-hint sigem-h-warn">{{ $mantenimientosPendientes ?? 0 }} pendientes de revisión</div>
            </div>

            <!-- 4. Requieren atención -->
            <div class="sigem-stat">
                <div class="sigem-stat-ico sigem-i-danger">⚠️</div>
                <div class="sigem-stat-val">{{ $requierenAtencion }}</div>
                <div class="sigem-stat-label">Requieren atención</div>
                <div class="sigem-stat-hint sigem-h-warn">{{ $materialesStockBajoCount ?? 0 }} stock bajo · {{ $inventarioDanados ?? 0 }} dañados</div>
            </div>
        </div>

        <!-- 4. REGISTROS DE INVENTARIO -->
        <div class="sigem-quick-desc-none"></div> <!-- separación limpia -->
        <div x-data="{ showGrafica: false }" style="margin-top: 14px;">
            <div class="sigem-section-head">
                <div>
                    <h2>💻 Inventario general</h2>
                    <p>Busca, filtra y gestiona los equipos registrados en el sistema.</p>
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
                                        <th>CÓDIGO</th>
                                        <th>EQUIPO</th>
                                        <th>CATEGORÍA</th>
                                        <th>ESTADO</th>
                                        <th>ÁREA</th>
                                        <th style="text-align: right;">ACCIONES</th>
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
                                                <span class="sigem-pill {{ $pillCls }}">
                                                    <span class="sigem-dot"></span>{{ $estado }}
                                                </span>
                                            </td>
                                            <td>
                                                <span style="font-size: 14px; color: var(--sig-muted);">{{ $areaUbicacion }}</span>
                                            </td>
                                            <td style="text-align: right; white-space: nowrap;">
                                                <div style="display: inline-flex; align-items: center; justify-content: flex-end; gap: 4px;">
                                                    <button type="button"
                                                            wire:click="verEquipo({{ $inv->id_inventario }})"
                                                            class="sigem-rep-btn-sec fi-icon-btn"
                                                            title="Ver detalle"
                                                            aria-label="Ver detalle"
                                                            style="width:32px;height:32px;padding:0;display:inline-flex;align-items:center;justify-content:center;cursor:pointer;">
                                                    </button>
                                                    <button type="button"
                                                            wire:click="editarEquipo({{ $inv->id_inventario }})"
                                                            class="sigem-rep-btn-sec fi-icon-btn"
                                                            title="Editar"
                                                            aria-label="Editar"
                                                            style="width:32px;height:32px;padding:0;display:inline-flex;align-items:center;justify-content:center;cursor:pointer;">
                                                    </button>
                                                    <button type="button"
                                                            wire:click="eliminarEquipo({{ $inv->id_inventario }})"
                                                            class="sigem-rep-btn-sec fi-icon-btn"
                                                            title="Eliminar"
                                                            aria-label="Eliminar"
                                                            style="width:32px;height:32px;padding:0;display:inline-flex;align-items:center;justify-content:center;cursor:pointer;">
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" style="text-align: center; padding: 32px; color: var(--sig-muted);">
                                                No hay equipos registrados en el inventario.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <div class="sigem-dash-more">
                            <a href="{{ url('/admin/inventarios') }}">
                                Ver todo el inventario →
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Panel lateral derecho: Gráfica del inventario -->
                <aside class="sigem-inv-side" x-show="showGrafica">
                    <div class="sigem-inv-side-title">Resumen del inventario</div>

                    @php
                        $dashTotal = $totalActivos ?? 0;
                        $circunf = 263.89;
                        $donutSegs = [
                            ['label' => 'Disponible', 'cant' => $inventarioDisponibles ?? 0, 'color' => '#059669'],
                            ['label' => 'En mantenimiento', 'cant' => $inventarioEnMantenimiento ?? 0, 'color' => '#d97706'],
                            ['label' => 'Asignado', 'cant' => $inventarioAsignados ?? 0, 'color' => '#0891b2'],
                            ['label' => 'Dañado', 'cant' => $inventarioDanados ?? 0, 'color' => '#dc2626'],
                        ];
                        $dashOffset = 0;
                    @endphp

                    <!-- 1. Dona SVG -->
                    <div class="sigem-donut">
                        <svg viewBox="0 0 120 120" width="120" height="120">
                            <circle cx="60" cy="60" r="42" fill="none" stroke="#f1f5f9" stroke-width="16" />
                            @if($dashTotal > 0)
                                @foreach($donutSegs as $seg)
                                    @if($seg['cant'] > 0)
                                        @php
                                            $dLen = round(($seg['cant'] / $dashTotal) * $circunf, 2);
                                            $dGap = round($circunf - $dLen, 2);
                                            $curOff = $dashOffset;
                                            $dashOffset += $dLen;
                                        @endphp
                                        <circle
                                            cx="60"
                                            cy="60"
                                            r="42"
                                            fill="none"
                                            stroke="{{ $seg['color'] }}"
                                            stroke-width="16"
                                            stroke-dasharray="{{ $dLen }} {{ $dGap }}"
                                            stroke-dashoffset="{{ -$curOff }}"
                                            transform="rotate(-90 60 60)"
                                        />
                                    @endif
                                @endforeach
                            @else
                                <circle cx="60" cy="60" r="42" fill="none" stroke="#e5e7eb" stroke-width="16" />
                            @endif
                        </svg>
                        <div class="sigem-donut-center">
                            <b>{{ $dashTotal }}</b>
                            <span>equipos</span>
                        </div>
                    </div>

                    <!-- 2. Leyenda de estados -->
                    <div style="margin-top: 14px; display: flex; flex-direction: column; gap: 3px;">
                        @foreach($donutSegs as $seg)
                            <div class="sigem-lg" style="cursor: default;">
                                <i style="background: {{ $seg['color'] }};"></i>
                                <span>{{ $seg['label'] }}</span>
                                <b>{{ $seg['cant'] }}</b>
                            </div>
                        @endforeach
                    </div>
                </aside>
            </div>
        </div>

    </div>

    @push('scripts')
        <script>
            const registerSigemDashboard = () => {
                Alpine.data('sigemDashboard', () => ({
                    getGreeting() {
                        const hour = new Date().getHours();
                        if (hour < 12) return 'Buenos días';
                        if (hour < 19) return 'Buenas tardes';
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
    @endpush
</x-filament-panels::page>