<x-filament-panels::page>
    @php
        // 1. Conteos agrupados por estado
        $resumenEstados = \App\Models\Inventario::query()
            ->selectRaw('estado, count(*) as total')
            ->groupBy('estado')
            ->pluck('total', 'estado')
            ->toArray();

        $totalEquipos = array_sum($resumenEstados);

        // Estados y colores aprobados por el cliente
        $estadosDef = [
            'Disponible'       => ['label' => 'Disponible',       'color' => '#059669', 'cant' => $resumenEstados['Disponible'] ?? 0],
            'En Mantenimiento' => ['label' => 'En mantenimiento', 'color' => '#d97706', 'cant' => $resumenEstados['En Mantenimiento'] ?? 0],
            'Asignado'         => ['label' => 'Asignado',         'color' => '#0891b2', 'cant' => $resumenEstados['Asignado'] ?? 0],
            'Dañado'           => ['label' => 'Dañado',           'color' => '#dc2626', 'cant' => $resumenEstados['Dañado'] ?? 0],
        ];

        // Otros estados (Baja, Devuelto a Proveedor u otros)
        $otrosCount = 0;
        foreach ($resumenEstados as $est => $cant) {
            if (!array_key_exists($est, $estadosDef)) {
                $otrosCount += $cant;
            }
        }
        $estadosDef['Otros'] = ['label' => 'Otros', 'color' => '#9ca3af', 'cant' => $otrosCount];

        // Circunferencia del círculo (r = 42)
        $circunferencia = 2 * pi() * 42; // ~263.89
        $offsetAcumulado = 0;

        // 2. Conteos agrupados por categoría (top 8 + Otras)
        $todasCategorias = \App\Models\Inventario::query()
            ->join('material', 'inventario.id_producto', '=', 'material.id_producto')
            ->leftJoin('tipo_material', 'material.id_tipodematerial', '=', 'tipo_material.id_tipodematerial')
            ->selectRaw("COALESCE(tipo_material.nombre, 'General') as cat, count(*) as total")
            ->groupBy('cat')
            ->orderByDesc('total')
            ->pluck('total', 'cat')
            ->toArray();

        $categoriasTop = [];
        $otrasCategoriasCount = 0;
        $idx = 0;
        foreach ($todasCategorias as $catNom => $catCant) {
            if ($idx < 8) {
                $categoriasTop[$catNom] = $catCant;
            } else {
                $otrasCategoriasCount += $catCant;
            }
            $idx++;
        }
        if ($otrasCategoriasCount > 0) {
            $categoriasTop['Otras categorías'] = $otrasCategoriasCount;
        }
    @endphp

    <div class="sigem-inv-layout">
        <!-- Contenido existente de la tabla (Master-detail) INTACTO -->
        <div style="min-width: 0;">
            <div style="display: flex; gap: 1.5rem; align-items: flex-start; flex-wrap: wrap;">
                <!-- Tabla a la izquierda -->
                <div style="flex: 1 1 {{ $activeRecord ? '62%' : '100%' }}; min-width: 0;">
                    {{ $this->table }}
                </div>
                
                <!-- Panel de Infolist a la derecha (cuando hay registro seleccionado) -->
                @if($activeRecord)
                    <div style="flex: 1 1 34%; min-width: 300px; position: sticky; top: 1.5rem; max-height: 85vh; overflow-y: auto;" class="bg-white dark:bg-gray-900 rounded-xl shadow-sm ring-1 ring-gray-950/5 p-4">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-bold">Detalle del Equipo</h3>
                            <x-filament::button color="gray" size="sm" wire:click="$set('activeRecord', null)">Cerrar</x-filament::button>
                        </div>
                        {{ $this->infolist }}
                    </div>
                @endif
            </div>
        </div>

        <!-- Panel lateral derecho: Resumen del inventario -->
        <aside class="sigem-inv-side">
            <div class="sigem-inv-side-title">Resumen del inventario</div>

            <!-- 1. Dona SVG -->
            <div class="sigem-donut">
                <svg viewBox="0 0 120 120" width="120" height="120">
                    <circle cx="60" cy="60" r="42" fill="none" stroke="#f1f5f9" stroke-width="16" />
                    @if($totalEquipos > 0)
                        @foreach($estadosDef as $estKey => $item)
                            @if($item['cant'] > 0)
                                @php
                                    $dashLen = round(($item['cant'] / $totalEquipos) * $circunferencia, 2);
                                    $dashGap = round($circunferencia - $dashLen, 2);
                                    $currentOffset = $offsetAcumulado;
                                    $offsetAcumulado += $dashLen;
                                @endphp
                                <circle
                                    cx="60"
                                    cy="60"
                                    r="42"
                                    fill="none"
                                    stroke="{{ $item['color'] }}"
                                    stroke-width="16"
                                    stroke-dasharray="{{ $dashLen }} {{ $dashGap }}"
                                    stroke-dashoffset="{{ -$currentOffset }}"
                                    transform="rotate(-90 60 60)"
                                />
                            @endif
                        @endforeach
                    @else
                        <circle cx="60" cy="60" r="42" fill="none" stroke="#e5e7eb" stroke-width="16" />
                    @endif
                </svg>
                <div class="sigem-donut-center">
                    <b>{{ $totalEquipos }}</b>
                    <span>equipos</span>
                </div>
            </div>

            <!-- 2. Leyenda de estados -->
            <div style="margin-top: 14px; display: flex; flex-direction: column; gap: 3px;">
                @foreach($estadosDef as $estKey => $item)
                    <!-- TODO: clic para filtrar por estado $estKey -->
                    <div class="sigem-lg">
                        <i style="background: {{ $item['color'] }};"></i>
                        <span>{{ $item['label'] }}</span>
                        <b>{{ $item['cant'] }}</b>
                    </div>
                @endforeach
            </div>

            <!-- 3. Barras por categoría -->
            <div style="margin-top: 18px; border-top: 1px solid var(--sig-border); padding-top: 14px;">
                <div style="font-size: 13.5px; font-weight: 700; color: var(--sig-muted); text-transform: uppercase; letter-spacing: .05em; margin-bottom: 8px;">
                    Por categoría
                </div>
                @forelse($categoriasTop as $catNom => $catCant)
                    @php
                        $pct = $totalEquipos > 0 ? round(($catCant / $totalEquipos) * 100) : 0;
                    @endphp
                    <!-- TODO: clic para filtrar por categoría $catNom -->
                    <div class="sigem-cb">
                        <span title="{{ $catNom }}">{{ $catNom }}</span>
                        <div><span style="width: {{ $pct }}%;"></span></div>
                        <span style="text-align: right; font-weight: 600;">{{ $catCant }}</span>
                    </div>
                @empty
                    <div style="font-size: 13px; color: var(--sig-muted);">Sin categorías registradas</div>
                @endforelse
            </div>
        </aside>
    </div>
</x-filament-panels::page>
