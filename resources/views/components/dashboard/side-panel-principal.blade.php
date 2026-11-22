@props([
    'selectedRows' => [],
    'selectedRecords' => null,
    'cantSel' => 0,
    'resumenEstados' => [],
    'cantBajas' => 0,
    'getEstadoStyle' => null,
    'formatItemDesc' => null,
])

@php
    $records = $selectedRecords ?? collect();
    $selRecord = $records->first();
@endphp

@if($cantSel === 1)
    @php
        $selId = $selRecord?->id_inventario ?? $selRecord?->id ?? reset($selectedRows);
        $selCodigo = $selRecord ? ($selRecord->codigo ?: ($selRecord->num_serie ?: ('#' . $selId))) : ('#' . $selId);
        $selNombre = $selRecord?->material?->nombre ?? $selRecord?->subtipo ?? 'Equipo general';
    @endphp

    {{-- HEADER (1 EQUIPO) --}}
    <div class="sigem-sel-header">
        <div style="min-width: 0; flex: 1;">
            <div class="sigem-sel-header-title" title="{{ $selCodigo }}">{{ $selCodigo }}</div>
            <div class="sigem-sel-header-sub" title="{{ $selNombre }}">{{ $selNombre }}</div>
        </div>
        <button type="button" wire:click="deseleccionarTodos" class="sigem-sel-close-btn" title="Cerrar">✕</button>
    </div>

    {{-- BODY (1 EQUIPO) --}}
    <div class="sigem-sel-body">
        <div>
            <div class="sigem-sel-section-label">ACCIONES</div>
            <div class="sigem-sel-actions-grid">
                <button type="button" wire:click="verEquipo({{ $selId }})" class="sigem-sel-action-card" title="Ver ficha del equipo">
                    <span class="sigem-sel-action-icon">👁</span>
                    <span class="sigem-sel-action-title">Ver ficha</span>
                    <span class="sigem-sel-action-desc">Características, IP/MAC e historial</span>
                </button>

                <button type="button" wire:click="setSlideOverStep('modificar')" class="sigem-sel-action-card" title="Modificar datos del equipo">
                    <span class="sigem-sel-action-icon">✏️</span>
                    <span class="sigem-sel-action-title">Modificar</span>
                    <span class="sigem-sel-action-desc">Editar los datos del equipo</span>
                </button>

                <button type="button" wire:click="setSlideOverStep('cambio')" class="sigem-sel-action-card" title="Cambio de estado o área">
                    <span class="sigem-sel-action-icon">🔄</span>
                    <span class="sigem-sel-action-title">Cambio de estado o área</span>
                    <span class="sigem-sel-action-desc">Cambiar su estado o moverlo de área</span>
                </button>

                <button type="button" wire:click="setSlideOverStep('baja')" class="sigem-sel-action-card is-danger" title="Dar de baja">
                    <span class="sigem-sel-action-icon">↓</span>
                    <span class="sigem-sel-action-title">Dar de baja</span>
                    <span class="sigem-sel-action-desc">Pasa a Solicitud de baja</span>
                </button>

                <button type="button" wire:click="exportarSeleccion" class="sigem-sel-action-card is-export" title="Exportar a Excel">
                    <span class="sigem-sel-action-icon">📥</span>
                    <span class="sigem-sel-action-title">Exportar</span>
                    <span class="sigem-sel-action-desc">Descargar en Excel</span>
                </button>
            </div>
        </div>

        <div>
            <div class="sigem-sel-section-label">RESUMEN POR ESTADO</div>
            <div class="sigem-sel-state-pills">
                @foreach($resumenEstados as $resItem)
                    <span class="sigem-sel-state-badge" style="background-color: {{ $resItem['info']['bg'] }}; color: {{ $resItem['info']['text'] }};">
                        <span style="width: 8px; height: 8px; border-radius: 50%; background-color: {{ $resItem['info']['dot'] }}; display: inline-block; flex-shrink: 0;"></span>
                        <span>{{ $resItem['info']['label'] }} · {{ $resItem['count'] }}</span>
                    </span>
                @endforeach
            </div>
        </div>

        @if($selRecord)
            @php
                $selStInfo = $getEstadoStyle($selRecord->estado);
                $selDesc = $formatItemDesc($selRecord);
            @endphp
            <div>
                <div class="sigem-sel-section-label">EQUIPO SELECCIONADO</div>
                <div class="sigem-sel-items-list">
                    <div class="sigem-sel-item-card">
                        <div style="min-width: 0; flex: 1;">
                            <div class="sigem-sel-item-code">{{ $selCodigo }}</div>
                            <div class="sigem-sel-item-desc" title="{{ $selDesc }}">{{ $selDesc }}</div>
                        </div>
                        <div style="display: inline-flex; align-items: center; gap: 6px; flex-shrink: 0;">
                            <span class="sigem-sel-mini-badge" style="background-color: {{ $selStInfo['bg'] }}; color: {{ $selStInfo['text'] }};">
                                <span style="width: 7px; height: 7px; border-radius: 50%; background-color: {{ $selStInfo['dot'] }}; display: inline-block; flex-shrink: 0;"></span>
                                <span>{{ $selStInfo['label'] }}</span>
                            </span>
                            <button type="button" wire:click="deseleccionarTodos" class="sigem-sel-item-remove" title="Quitar de la selección">✕</button>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <div class="sigem-sel-bitacora-note">
            <span>🔒</span>
            <span>Cada acción queda registrada en la bitácora con tu usuario</span>
        </div>
    </div>
@else
    {{-- HEADER (MÚLTIPLES EQUIPOS) --}}
    <div class="sigem-sel-header">
        <div style="min-width: 0; flex: 1;">
            <div class="sigem-sel-header-title">
                {{ $cantSel }} equipos seleccionados
                <small style="font-weight: 400; font-size: 11.5px; color: var(--sig-muted);">(solo esta página)</small>
            </div>
            <div class="sigem-sel-header-sub">Elige una acción para aplicarla a todos</div>
        </div>
        <button type="button" wire:click="deseleccionarTodos" class="sigem-sel-close-btn" title="Cerrar">✕</button>
    </div>

    {{-- BODY (MÚLTIPLES EQUIPOS) --}}
    <div class="sigem-sel-body">
        <div>
            <div class="sigem-sel-section-label">ACCIONES</div>
            <div class="sigem-sel-actions-grid">
                <button type="button" wire:click="setSlideOverStep('modificar')" class="sigem-sel-action-card" title="Modificar todos los equipos seleccionados">
                    <span class="sigem-sel-action-icon">✏️</span>
                    <span class="sigem-sel-action-title">Modificar todos</span>
                    <span class="sigem-sel-action-desc">Cambiar un mismo campo en todos</span>
                </button>

                <button type="button" wire:click="setSlideOverStep('cambio')" class="sigem-sel-action-card" title="Cambio masivo de estado o área">
                    <span class="sigem-sel-action-icon">🔄</span>
                    <span class="sigem-sel-action-title">Cambio de estado o área</span>
                    <span class="sigem-sel-action-desc">Cambiar su estado o moverlo de área</span>
                </button>

                <button type="button" wire:click="setSlideOverStep('baja')" class="sigem-sel-action-card is-danger" title="Dar de baja">
                    <span class="sigem-sel-action-icon">↓</span>
                    <span class="sigem-sel-action-title">Dar de baja</span>
                    <span class="sigem-sel-action-desc">Pasa a Solicitud de baja</span>
                </button>

                <button type="button" wire:click="exportarSeleccion" class="sigem-sel-action-card is-export" title="Exportar selección a Excel">
                    <span class="sigem-sel-action-icon">📥</span>
                    <span class="sigem-sel-action-title">Exportar selección</span>
                    <span class="sigem-sel-action-desc">Descargar en Excel</span>
                </button>

                @if($cantBajas > 0)
                    <button type="button" wire:click="descargarFormatosBajaLote" class="sigem-sel-action-card is-pdf" title="Descargar Formatos de Baja (lote) en PDF">
                        <span class="sigem-sel-action-icon">📄</span>
                        <span class="sigem-sel-action-title">Formatos de Baja (lote)</span>
                        <span class="sigem-sel-action-desc">PDF multipágina ({{ $cantBajas }} {{ $cantBajas === 1 ? 'bien' : 'bienes' }})</span>
                    </button>
                @endif
            </div>
        </div>

        <div>
            <div class="sigem-sel-section-label">RESUMEN POR ESTADO</div>
            <div class="sigem-sel-state-pills">
                @foreach($resumenEstados as $resItem)
                    <span class="sigem-sel-state-badge" style="background-color: {{ $resItem['info']['bg'] }}; color: {{ $resItem['info']['text'] }};">
                        <span style="width: 8px; height: 8px; border-radius: 50%; background-color: {{ $resItem['info']['dot'] }}; display: inline-block; flex-shrink: 0;"></span>
                        <span>{{ $resItem['info']['label'] }} · {{ $resItem['count'] }}</span>
                    </span>
                @endforeach
            </div>
        </div>

        <div>
            <div class="sigem-sel-section-label">EQUIPOS SELECCIONADOS ({{ $cantSel }})</div>
            <div class="sigem-sel-items-list">
                @foreach($records as $pItem)
                    @php
                        $pId = (string) ($pItem->id_inventario ?? $pItem->id);
                        $pCodigo = $pItem->codigo ?: ($pItem->num_serie ?: ('#' . $pId));
                        $pDesc = $formatItemDesc($pItem);
                        $pStInfo = $getEstadoStyle($pItem->estado);
                    @endphp
                    <div class="sigem-sel-item-card">
                        <div style="min-width: 0; flex: 1;">
                            <div class="sigem-sel-item-code">{{ $pCodigo }}</div>
                            <div class="sigem-sel-item-desc" title="{{ $pDesc }}">{{ $pDesc }}</div>
                        </div>
                        <div style="display: inline-flex; align-items: center; gap: 6px; flex-shrink: 0;">
                            <span class="sigem-sel-mini-badge" style="background-color: {{ $pStInfo['bg'] }}; color: {{ $pStInfo['text'] }};">
                                <span style="width: 7px; height: 7px; border-radius: 50%; background-color: {{ $pStInfo['dot'] }}; display: inline-block; flex-shrink: 0;"></span>
                                <span>{{ $pStInfo['label'] }}</span>
                            </span>
                            <button type="button"
                                    x-on:click="$wire.set('selectedRows', ($wire.selectedRows || []).filter(id => String(id) !== '{{ $pId }}'))"
                                    class="sigem-sel-item-remove"
                                    title="Quitar de la selección">✕</button>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="sigem-sel-bitacora-note">
            <span>🔒</span>
            <span>Cada acción queda registrada en la bitácora con tu usuario</span>
        </div>
    </div>
@endif

{{-- FOOTER PASO 1 --}}
<div class="sigem-sel-footer">
    <button type="button" wire:click="deseleccionarTodos" class="sigem-sel-footer-btn" title="Quitar selección">
        ✕ Quitar selección
    </button>
    <button type="button" wire:click="deseleccionarTodos" class="sigem-sel-footer-btn" title="Cerrar panel">
        Cerrar
    </button>
</div>
