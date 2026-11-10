<x-filament-panels::page>
    <div x-data="sigemDashboardSS()" class="sigem-professional" x-cloak>

        @php
            $user = auth()->user();
            $hour = (int) date('H');
            $saludo = $hour < 12 ? 'Buenos días' : ($hour < 19 ? 'Buenas tardes' : 'Buenas noches');
            $nombreCorto = explode(' ', $user->name ?? 'Usuario')[0];
            $isSkeletonPreview = request()->boolean('skeleton');
        @endphp

        {{-- CONTENEDOR FLEX PRINCIPAL: MAIN + PANEL LATERAL (EMPUJA EL CONTENIDO) --}}
        <div class="sigem-dashboard-layout {{ count($selectedRows ?? []) > 0 ? 'has-panel-open' : '' }}"
             :class="{ 'has-panel-open': ($wire.selectedRows && $wire.selectedRows.length > 0) }">
            <div class="sigem-dashboard-main">
                {{-- 1. HERO CON ACCIONES --}}
                <x-dashboard.header
                    panel="servicio-social"
                    :saludo="$saludo"
                    :nombre-corto="$nombreCorto"
                />

                {{-- 2. KPIS (4 TARJETAS SUPERIORES) --}}
                <x-dashboard.kpis
                    panel="servicio-social"
                    :is-skeleton-preview="$isSkeletonPreview"
                    :total-activos="$totalActivos ?? 0"
                    :mis-solicitudes="$misSolicitudes ?? 0"
                    :solicitudes-autorizadas="$solicitudesAutorizadas ?? 0"
                    :mis-mantenimientos="$misMantenimientos ?? 0"
                    :mantenimiento-en-revision="$mantenimientoEnRevision ?? 0"
                    :inventario-disponibles="$inventarioDisponibles ?? 0"
                />

                {{-- 3. QUICK CARDS (4 TARJETAS CON FLECHA "→") --}}
                <x-dashboard.quick-cards
                    panel="servicio-social"
                    :is-skeleton-preview="$isSkeletonPreview"
                    :mis-solicitudes-pendientes="$misSolicitudesPendientes ?? 0"
                />

                {{-- 4. INVENTARIO GENERAL DIRECTAMENTE DEBAJO --}}
                <x-dashboard.table
                    panel="servicio-social"
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
                    :show-side-panel="$showSidePanel ?? false"
                    :slide-over-step="$slideOverStep ?? 'principal'"
                    :modificar-campo="$modificarCampo ?? ''"
                />
            </div>

            {{-- 5. PANEL LATERAL DE ACCIONES / SELECCIÓN --}}
            <x-dashboard.side-panel
                panel="servicio-social"
                :selected-rows="$selectedRows ?? []"
                :slide-over-step="$slideOverStep ?? 'principal'"
                :modificar-campo="$modificarCampo ?? ''"
            />
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
