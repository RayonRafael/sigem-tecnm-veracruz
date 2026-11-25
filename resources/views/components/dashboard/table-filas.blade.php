@props([
    'panel' => 'admin',
    'isSkeletonPreview' => false,
    'items' => [],
    'mostrarColumnaRenta' => false,
    'emptyMessage' => 'No hay registros de inventario disponibles.',
])

@if($isSkeletonPreview)
    {{-- SKELETON: TABLA INVENTARIO (5 FILAS) --}}
    <tbody>
        @for($i = 0; $i < 5; $i++)
            <tr class="sigem-skeleton-tr">
                <td style="width: 44px; text-align: center;"><span class="sigem-skeleton" style="width: 16px; height: 16px; border-radius: 4px; display: inline-block;"></span></td>
                <td><span class="sigem-skeleton sigem-skeleton-cell" style="width: 120px;"></span></td>
                <td>
                    <div style="display: flex; flex-direction: column; gap: 4px;">
                        <span class="sigem-skeleton sigem-skeleton-cell" style="width: 140px; height: 16px;"></span>
                        <span class="sigem-skeleton sigem-skeleton-cell" style="width: 90px; height: 12px;"></span>
                    </div>
                </td>
                <td><span class="sigem-skeleton sigem-skeleton-cell" style="width: 75px; height: 22px; border-radius: 9999px;"></span></td>
                <td><span class="sigem-skeleton sigem-skeleton-cell" style="width: 95px; height: 22px; border-radius: 9999px;"></span></td>
                <td><span class="sigem-skeleton sigem-skeleton-cell" style="width: 105px;"></span></td>
                <td><span class="sigem-skeleton sigem-skeleton-cell" style="width: 50px;"></span></td>
                <td><span class="sigem-skeleton sigem-skeleton-cell" style="width: 90px;"></span></td>
                <td><span class="sigem-skeleton sigem-skeleton-cell" style="width: 80px;"></span></td>
                <td><span class="sigem-skeleton sigem-skeleton-cell" style="width: 95px;"></span></td>
                <td class="fi-ta-actions-cell" style="width: 104px; text-align: center; white-space: nowrap;">
                    <div style="display: inline-flex; align-items: center; justify-content: center; gap: 4px;">
                        <span class="sigem-skeleton" style="width: 26px; height: 26px; border-radius: 6px;"></span>
                        <span class="sigem-skeleton" style="width: 26px; height: 26px; border-radius: 6px;"></span>
                        <span class="sigem-skeleton" style="width: 26px; height: 26px; border-radius: 6px;"></span>
                    </div>
                </td>
            </tr>
        @endfor
    </tbody>
@else
    <tbody wire:loading.remove wire:target="tableColumnSearches, resetTableColumnSearches, sortBy, setPage, previousPage, nextPage, perPage">
        @forelse($items as $inv)
            @php
                $deptoNombre = $inv->departamento?->nombre
                    ?? $inv->area?->departamento?->nombre
                    ?? ($inv->ubicacion_fisica ? (explode(' > ', $inv->ubicacion_fisica)[0] ?? null) : null)
                    ?? '—';

                $codigo = ! empty($inv->codigo) ? $inv->codigo : ($inv->num_serie ?: ('#' . ($inv->id_inventario ?? $inv->id)));
                $nombreEquipo = $inv->material?->nombre ?? $inv->subtipo ?? 'Equipo general';
                $categoria = $inv->material?->tipo?->nombre ?? $inv->subtipo ?? 'General';

                $areaUbicacion = $inv->area?->nombre;
                if (! $areaUbicacion && ! empty($inv->ubicacion_fisica)) {
                    $parts = explode(' > ', $inv->ubicacion_fisica);
                    $areaUbicacion = $parts[1] ?? $parts[0] ?? null;
                }
                $areaUbicacion = $areaUbicacion ?: '—';

                $aula = $inv->aula
                    ?? $inv->salon?->nombre
                    ?? ($inv->especificaciones['aula'] ?? null)
                    ?? (preg_match('/Aula\s+([A-Za-z0-9_-]+)/i', $inv->ubicacion_fisica ?? '', $m) ? $m[1] : null)
                    ?? '—';

                $numSerie = $inv->num_serie ?: '—';
                $ipAddress = $inv->ip_address ?? ($inv->especificaciones['ip_address'] ?? null) ?? '—';
                $macAddress = $inv->mac_address ?? ($inv->especificaciones['mac_address'] ?? null) ?? '—';

                $estado = $inv->estado ?? 'Sin estado';
                $pillCls = match ($estado) {
                    'Disponible' => 'ok',
                    'Asignado' => 'info',
                    'Reasignado' => 'reassign',
                    'En Mantenimiento', 'En mantenimiento' => 'warn',
                    'Solicitud de baja' => 'warn',
                    'Cambio de área' => 'info',
                    'Dado de baja', 'Baja', 'Dañado' => 'danger',
                    default => 'neutral',
                };
            @endphp
            <tr wire:key="inv-row-{{ $inv->id_inventario ?? $inv->id }}">
                {{-- 0. CHECKBOX SELECCIÓN --}}
                <td style="width: 44px; min-width: 44px; text-align: center; padding: 6px;">
                    <input
                        type="checkbox"
                        wire:model.live="selectedRows"
                        value="{{ (string) ($inv->id_inventario ?? $inv->id) }}"
                        class="sigem-checkbox"
                        title="Seleccionar equipo #{{ $inv->codigo ?: ($inv->id_inventario ?? $inv->id) }}"
                    />
                </td>

                {{-- 1. DEPARTAMENTO --}}
                <td style="min-width: 170px; max-width: 220px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="{{ $deptoNombre }}">
                    <span style="font-size: 13.5px; color: var(--sig-text);">{{ $deptoNombre }}</span>
                </td>

                {{-- 2. EQUIPO --}}
                <td style="min-width: 220px; max-width: 280px;">
                    <div style="display: flex; flex-direction: column;">
                        <strong style="color: var(--sig-text); line-height: 1.25; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="{{ $nombreEquipo }}">{{ $nombreEquipo }}</strong>
                        <span style="font-size: 12px; color: var(--sig-muted); margin-top: 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="ID: {{ $codigo }}">ID: {{ $codigo }}</span>
                    </div>
                </td>

                {{-- 3. CATEGORÍA --}}
                <td style="min-width: 120px; white-space: nowrap;">
                    <span class="sigem-pill neutral" style="font-weight: 500; white-space: nowrap;" title="{{ $categoria }}">{{ $categoria }}</span>
                </td>

                {{-- 4. ESTADO --}}
                <td style="min-width: 130px; white-space: nowrap;">
                    <span class="sigem-pill {{ $pillCls }}" style="white-space: nowrap;">
                        <span class="sigem-dot"></span>{{ $estado }}
                    </span>
                </td>

                {{-- 4b. ESTADO RENTA --}}
                @if($mostrarColumnaRenta)
                    <td style="min-width: 125px; white-space: nowrap;">
                        @if(($inv->tipo_propiedad ?? '') === 'Rentado' && $inv->renta_estado)
                            @php
                                $rentaEst = $inv->renta_estado;
                                $rentaFinStr = $inv->renta_fecha_fin?->format('d/m/Y') ?? '—';
                                $rentaDias = $inv->renta_dias_restantes ?? 0;
                                $rentaPillCls = match ($rentaEst) {
                                    'Vencida' => 'danger',
                                    'Por vencer' => 'warn',
                                    default => 'ok',
                                };
                                $rentaEmoji = match ($rentaEst) {
                                    'Vencida' => '🔴',
                                    'Por vencer' => '🟡',
                                    default => '🟢',
                                };
                                $rentaTooltip = "Vence el {$rentaFinStr} ({$rentaDias} días)";
                            @endphp
                            <span class="sigem-pill {{ $rentaPillCls }}" style="white-space: nowrap;" title="{{ $rentaTooltip }}">
                                {{ $rentaEmoji }} {{ $rentaEst }}
                            </span>
                        @else
                            <span style="color: var(--sig-muted);">—</span>
                        @endif
                    </td>
                @endif

                {{-- 5. ÁREA --}}
                <td style="min-width: 160px; max-width: 220px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="{{ $areaUbicacion }}">
                    <span style="font-size: 13.5px; color: var(--sig-muted);">{{ $areaUbicacion }}</span>
                </td>

                {{-- 6. AULA --}}
                <td style="min-width: 80px; white-space: nowrap;">
                    <span style="font-size: 13.5px; color: var(--sig-muted);">{{ $aula }}</span>
                </td>

                {{-- 7. NO. DE SERIE --}}
                <td style="min-width: 140px; max-width: 180px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="{{ $numSerie }}">
                    <span style="font-size: 12.5px; font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; color: var(--sig-text);">{{ $numSerie }}</span>
                </td>

                {{-- 8. IP --}}
                <td style="min-width: 120px; white-space: nowrap;" title="{{ $ipAddress }}">
                    <span style="font-size: 12.5px; font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; color: var(--sig-muted);">{{ $ipAddress }}</span>
                </td>

                {{-- 9. MAC --}}
                <td style="min-width: 140px; white-space: nowrap;" title="{{ $macAddress }}">
                    <span style="font-size: 12.5px; font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; color: var(--sig-muted);">{{ $macAddress }}</span>
                </td>

                {{-- 10. ACCIONES --}}
                <td class="fi-ta-actions-cell" style="min-width: 104px; width: 104px; text-align: center; white-space: nowrap;">
                    <div class="fi-ta-actions" style="display: inline-flex; align-items: center; justify-content: center; gap: 3px;">
                        <button type="button"
                                wire:click="verEquipo({{ $inv->id_inventario ?? $inv->id }})"
                                class="fi-icon-btn sigem-dash-btn"
                                title="Ver detalle"
                                aria-label="Ver detalle">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style="width:16px;height:16px;">
                                <path d="M10 12.5a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5Z" />
                                <path fill-rule="evenodd" d="M.664 10.59a1.651 1.651 0 0 1 0-1.186A10.004 10.004 0 0 1 10 3c4.257 0 7.893 2.66 9.336 6.41.147.381.146.804 0 1.186A10.004 10.004 0 0 1 10 17c-4.257 0-7.893-2.66-9.336-6.41ZM14 10a4 4 0 1 1-8 0 4 4 0 0 1 8 0Z" clip-rule="evenodd" />
                            </svg>
                        </button>
                        <button type="button"
                                wire:click="editarEquipo({{ $inv->id_inventario ?? $inv->id }})"
                                class="fi-icon-btn sigem-dash-btn"
                                title="Modificar"
                                aria-label="Modificar">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style="width:16px;height:16px;">
                                <path d="m5.433 13.917 1.262-3.155A4 4 0 0 1 7.58 9.42l6.92-6.918a2.121 2.121 0 0 1 3 3l-6.92 6.918c-.383.383-.84.685-1.343.886l-3.154 1.262a.5.5 0 0 1-.65-.65Z" />
                                <path d="M3.5 5.75c0-.69.56-1.25 1.25-1.25H10A.75.75 0 0 0 10 3H4.75A2.75 2.75 0 0 0 2 5.75v9.5A2.75 2.75 0 0 0 4.75 18h9.5A2.75 2.75 0 0 0 17 15.25V10a.75.75 0 0 0-1.5 0v5.25c0 .69-.56 1.25-1.25 1.25h-9.5c-.69 0-1.25-.56-1.25-1.25v-9.5Z" />
                            </svg>
                        </button>
                        @if($panel === 'admin')
                            <button type="button"
                                    wire:click="eliminarEquipo({{ $inv->id_inventario ?? $inv->id }})"
                                    class="fi-icon-btn sigem-dash-btn fi-icon-btn-danger"
                                    title="Eliminar"
                                    aria-label="Eliminar">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style="width:16px;height:16px;">
                                    <path fill-rule="evenodd" d="M8.75 1A2.75 2.75 0 0 0 6 3.75v.443c-.795.077-1.584.176-2.365.298a.75.75 0 1 0 .23 1.482l.149-.022.841 10.518A2.75 2.75 0 0 0 7.596 19h4.807a2.75 2.75 0 0 0 2.742-2.53l.841-10.52.149.023a.75.75 0 0 0 .23-1.482A41.03 41.03 0 0 0 14 4.193V3.75A2.75 2.75 0 0 0 11.25 1h-2.5ZM10 4c.84 0 1.673.025 2.5.075V3.75c0-.69-.56-1.25-1.25-1.25h-2.5c-.69 0-1.25.56-1.25 1.25v.325C8.327 4.025 9.16 4 10 4ZM8.58 7.72a.75.75 0 0 0-1.5.06l.3 7.5a.75.75 0 1 0 1.5-.06l-.3-7.5Zm4.34.06a.75.75 0 1 0-1.5-.06l-.3 7.5a.75.75 0 1 0 1.5.06l.3-7.5Z" clip-rule="evenodd" />
                                </svg>
                            </button>
                        @endif
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="{{ $mostrarColumnaRenta ? 12 : 11 }}" style="text-align: center; padding: 28px; color: var(--sig-muted);">
                    {{ $emptyMessage }}
                </td>
            </tr>
        @endforelse
    </tbody>

    {{-- SKELETON EN CARGA LIVEWIRE --}}
    <tbody wire:loading wire:target="tableColumnSearches, resetTableColumnSearches, sortBy, setPage, previousPage, nextPage, perPage" style="display: none;">
        @for($i = 0; $i < 5; $i++)
            <tr class="sigem-skeleton-tr">
                <td style="width: 44px; text-align: center;"><span class="sigem-skeleton" style="width: 16px; height: 16px; border-radius: 4px; display: inline-block;"></span></td>
                <td><span class="sigem-skeleton sigem-skeleton-cell" style="width: 120px;"></span></td>
                <td>
                    <div style="display: flex; flex-direction: column; gap: 4px;">
                        <span class="sigem-skeleton sigem-skeleton-cell" style="width: 140px; height: 16px;"></span>
                        <span class="sigem-skeleton sigem-skeleton-cell" style="width: 90px; height: 12px;"></span>
                    </div>
                </td>
                <td><span class="sigem-skeleton sigem-skeleton-cell" style="width: 75px; height: 22px; border-radius: 9999px;"></span></td>
                <td><span class="sigem-skeleton sigem-skeleton-cell" style="width: 95px; height: 22px; border-radius: 9999px;"></span></td>
                <td><span class="sigem-skeleton sigem-skeleton-cell" style="width: 105px;"></span></td>
                <td><span class="sigem-skeleton sigem-skeleton-cell" style="width: 50px;"></span></td>
                <td><span class="sigem-skeleton sigem-skeleton-cell" style="width: 90px;"></span></td>
                <td><span class="sigem-skeleton sigem-skeleton-cell" style="width: 80px;"></span></td>
                <td><span class="sigem-skeleton sigem-skeleton-cell" style="width: 95px;"></span></td>
                <td class="fi-ta-actions-cell" style="width: 104px; text-align: center; white-space: nowrap;">
                    <div style="display: inline-flex; align-items: center; justify-content: center; gap: 4px;">
                        <span class="sigem-skeleton" style="width: 26px; height: 26px; border-radius: 6px;"></span>
                        <span class="sigem-skeleton" style="width: 26px; height: 26px; border-radius: 6px;"></span>
                        <span class="sigem-skeleton" style="width: 26px; height: 26px; border-radius: 6px;"></span>
                    </div>
                </td>
            </tr>
        @endfor
    </tbody>
@endif
