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
            $requierenAtencion = ($materialesStockBajoCount ?? 0) + ($inventarioDanados ?? 0) + ($inventarioSolicitudesBaja ?? 0) + ($inventarioSolicitudesCambioArea ?? 0);
            $isSkeletonPreview = request()->boolean('skeleton');
        @endphp

        {{-- 1. HERO --}}
        <x-dashboard.header
            panel="admin"
            :saludo="$saludo"
            :nombre-corto="$nombreCorto"
        />

        {{-- 2. KPIS (4 TARJETAS SUPERIORES) --}}
        <x-dashboard.kpis
            panel="admin"
            :is-skeleton-preview="$isSkeletonPreview"
            :total-activos="$totalActivos ?? 0"
            :creados-este-mes="$creadosEsteMes ?? 0"
            :inventario-disponibles="$inventarioDisponibles ?? 0"
            :porcentaje-disponibles="$porcentajeDisponibles"
            :inventario-en-mantenimiento="$inventarioEnMantenimiento ?? 0"
            :mantenimientos-pendientes="$mantenimientosPendientes ?? 0"
            :requieren-atencion="$requierenAtencion"
        />

        {{-- 3. ACCIONES RÁPIDAS (4 CARDS CON FLECHA "→") --}}
        <x-dashboard.quick-cards
            panel="admin"
            :is-skeleton-preview="$isSkeletonPreview"
            :mantenimientos-pendientes="$mantenimientosPendientes ?? 0"
            :solicitudes-pendientes="$solicitudesPendientes ?? 0"
        />

        <div class="sigem-quick-desc-none"></div>

        {{-- 4. REGISTROS DE INVENTARIO Y RESUMEN LATERAL --}}
        <x-dashboard.table
            panel="admin"
            :is-skeleton-preview="$isSkeletonPreview"
            :departamentos-filtro="$departamentosFiltro ?? []"
            :categorias-filtro="$categoriasFiltro ?? []"
            :areas-filtro="$areasFiltro ?? []"
            :inventarios-paginados="$inventariosPaginados ?? null"
            :inventarios-completos="$inventariosCompletos ?? []"
            :sort-column="$sortColumn ?? 'created_at'"
            :sort-direction="$sortDirection ?? 'desc'"
            :per-page="$perPage ?? 10"
            :total-activos="$totalActivos ?? 0"
            :inventario-disponibles="$inventarioDisponibles ?? 0"
            :inventario-en-mantenimiento="$inventarioEnMantenimiento ?? 0"
            :inventario-asignados="$inventarioAsignados ?? 0"
            :inventario-danados="$inventarioDanados ?? 0"
            :categorias-top="$categoriasTop ?? []"
            :selected-rows="$selectedRows ?? []"
            :select-all="$selectAll ?? false"
        />

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
