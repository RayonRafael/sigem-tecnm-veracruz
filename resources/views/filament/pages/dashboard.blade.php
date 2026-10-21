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
            <a class="sigem-quick" href="{{ url('/admin/inventarios/create') }}">
                <div class="arrow">→</div>
                <div class="sigem-quick-ico sigem-i-success">＋</div>
                <div class="sigem-quick-title">Registrar activo</div>
                <div class="sigem-quick-desc">Alta de nuevo equipo en inventario</div>
            </a>

            <!-- 4. Ver bitácora -->
            {{-- Fase 10: Enlazado a la pestaña Bitácora de Configuración & Catálogos (/admin/configuracion) --}}
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
                <div class="sigem-stat-ico sigem-i-danger">⚠</div>
                <div class="sigem-stat-val">{{ $requierenAtencion }}</div>
                <div class="sigem-stat-label">Requieren atención</div>
                <div class="sigem-stat-hint sigem-h-warn">{{ $materialesStockBajoCount ?? 0 }} stock bajo · {{ $inventarioDanados ?? 0 }} dañados</div>
            </div>
        </div>

        <!-- 4. REGISTROS DE INVENTARIO -->
        <div class="sigem-quick-desc-none"></div> <!-- separación limpia -->
        <div class="sigem-section-head">
            <h2>📋 Registros de Inventario</h2>
            <p>Busca, filtra y gestiona los equipos registrados en el sistema.</p>
        </div>

        {{-- // TODO: Replicar el panel de resumen lateral (dona + leyenda) en el dashboard si el cliente lo requiere en una fase posterior --}}
        <div class="sigem-rep-view sigem-dash-table">
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
                                    'En Mantenimiento' => 'warn',
                                    'Asignado' => 'info',
                                    'Dañado', 'Baja' => 'danger',
                                    default => 'neutral',
                                };

                                $editUrl = \App\Filament\Resources\InventarioResource::getUrl('edit', ['record' => $inv]);
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
                                <td style="text-align: right;">
                                    <a href="{{ $editUrl }}" class="sigem-rep-btn-sec" style="padding: 4px 11px; font-size: 13px; text-decoration: none; display: inline-flex; align-items: center;">
                                        Ver detalle
                                    </a>
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