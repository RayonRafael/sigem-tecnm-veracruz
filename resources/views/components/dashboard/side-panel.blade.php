@props([
    'panel' => 'admin',
    'selectedRows' => [],
    'slideOverStep' => 'principal',
    'modificarCampo' => '',
])

@if(count($selectedRows ?? []) > 0)
    <aside class="sigem-selection-side-panel"
           x-data
           x-show="$wire.showSidePanel || ($wire.selectedRows && $wire.selectedRows.length > 0)"
           x-cloak>
        @php
            $cantSel = count($selectedRows);
            $selectedRecords = !empty($selectedRows)
                ? \App\Models\Inventario::with(['material.tipo', 'departamento', 'area', 'salon'])
                    ->whereIn('id_inventario', $selectedRows)
                    ->get()
                : collect();
            $selRecord = $selectedRecords->first();
            $curStep = $this->slideOverStep ?? $slideOverStep ?? 'principal';
            $campoMod = $this->modificarCampo ?? $modificarCampo ?? '';

            // Vista previa de códigos para formularios
            $codigosArr = $selectedRecords->map(fn($r) => '#' . ($r->codigo ?: ($r->num_serie ?: ($r->id_inventario ?? $r->id))))->toArray();
            $codigosPreview = count($codigosArr) <= 5
                ? implode(', ', $codigosArr)
                : implode(', ', array_slice($codigosArr, 0, 5)) . ' (+ ' . (count($codigosArr) - 5) . ' más)';

            $getEstadoStyle = function (?string $st) {
                $estadoReal = match ($st) {
                    'En mantenimiento' => 'En Mantenimiento',
                    'Baja' => 'Dado de baja',
                    default => $st ?: 'Disponible',
                };
                return match ($estadoReal) {
                    'Disponible' => ['label' => 'Disponible', 'dot' => '#22c55e', 'bg' => '#dcfce7', 'text' => '#166534'],
                    'Asignado' => ['label' => 'Asignado', 'dot' => '#3b82f6', 'bg' => '#dbeafe', 'text' => '#1e40af'],
                    'Reasignado' => ['label' => 'Reasignado', 'dot' => '#3b82f6', 'bg' => '#dbeafe', 'text' => '#1e40af'],
                    'En Mantenimiento' => ['label' => 'En Mantenimiento', 'dot' => '#f59e0b', 'bg' => '#fef3c7', 'text' => '#92400e'],
                    'Cambio de área' => ['label' => 'Cambio de área', 'dot' => '#9ca3af', 'bg' => '#f3f4f6', 'text' => '#374151'],
                    'Solicitud de baja' => ['label' => 'Solicitud de baja', 'dot' => '#f59e0b', 'bg' => '#fef3c7', 'text' => '#92400e'],
                    'Dado de baja' => ['label' => 'Dado de baja', 'dot' => '#ef4444', 'bg' => '#fee2e2', 'text' => '#991b1b'],
                    'Dañado' => ['label' => 'Dañado', 'dot' => '#ef4444', 'bg' => '#fee2e2', 'text' => '#991b1b'],
                    'Devuelto a Proveedor' => ['label' => 'Devuelto a Proveedor', 'dot' => '#f59e0b', 'bg' => '#fef3c7', 'text' => '#92400e'],
                    default => ['label' => $estadoReal, 'dot' => '#9ca3af', 'bg' => '#f3f4f6', 'text' => '#374151'],
                };
            };

            $formatItemDesc = function ($rec) {
                $matNom = $rec->material?->nombre ?? $rec->subtipo ?? 'Equipo';
                $areaNom = $rec->area?->nombre;
                if (empty($areaNom) && !empty($rec->ubicacion_fisica)) {
                    $parts = explode(' > ', $rec->ubicacion_fisica);
                    $areaNom = $parts[1] ?? $parts[0] ?? null;
                }
                $areaNom = $areaNom ?: 'Sin área';
                $aulaNom = $rec->salon?->nombre
                    ?? ($rec->especificaciones['aula'] ?? null)
                    ?? (preg_match('/Aula\s+([A-Za-z0-9_-]+)/i', $rec->ubicacion_fisica ?? '', $m) ? $m[1] : null);
                return $matNom . ' · ' . $areaNom . (!empty($aulaNom) ? ' · Aula ' . $aulaNom : '');
            };

            $resumenEstados = [];
            foreach ($selectedRecords as $rec) {
                $stData = $getEstadoStyle($rec->estado);
                $lbl = $stData['label'];
                if (!isset($resumenEstados[$lbl])) {
                    $resumenEstados[$lbl] = ['info' => $stData, 'count' => 0];
                }
                $resumenEstados[$lbl]['count']++;
            }

            $todasLasAreas = \App\Models\Area::orderBy('nombre')->pluck('nombre', 'id_area');
            $todosLosDeptos = \App\Models\Departamento::orderBy('nombre')->pluck('nombre', 'id_departamento');
            $estadosEnum = \App\Enums\EstadoInventario::cases();
        @endphp

        @if($curStep === 'principal')
            {{-- =========================================================================
                 PASO 1: PANEL PRINCIPAL (ACCIONES + RESUMEN + SELECCIÓN)
                 ========================================================================= --}}
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
                    {{-- SECCIÓN ACCIONES (GRID 2 COLUMNAS) --}}
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

                    {{-- SECCIÓN RESUMEN POR ESTADO --}}
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

                    {{-- SECCIÓN EQUIPO SELECCIONADO --}}
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

                    {{-- NOTA BITÁCORA --}}
                    <div class="sigem-sel-bitacora-note">
                        <span>🔒</span>
                        <span>Cada acción queda registrada en la bitácora con tu usuario</span>
                    </div>
                </div>
            @else
                {{-- HEADER (MÚLTIPLES EQUIPOS) --}}
                <div class="sigem-sel-header">
                    <div style="min-width: 0; flex: 1;">
                        <div class="sigem-sel-header-title">{{ $cantSel }} equipos seleccionados</div>
                        <div class="sigem-sel-header-sub">Elige una acción para aplicarla a todos</div>
                    </div>
                    <button type="button" wire:click="deseleccionarTodos" class="sigem-sel-close-btn" title="Cerrar">✕</button>
                </div>

                {{-- BODY (MÚLTIPLES EQUIPOS) --}}
                <div class="sigem-sel-body">
                    {{-- SECCIÓN ACCIONES (GRID 2 COLUMNAS) --}}
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
                        </div>
                    </div>

                    {{-- SECCIÓN RESUMEN POR ESTADO --}}
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

                    {{-- SECCIÓN EQUIPOS SELECCIONADOS (N) --}}
                    <div>
                        <div class="sigem-sel-section-label">EQUIPOS SELECCIONADOS ({{ $cantSel }})</div>
                        <div class="sigem-sel-items-list">
                            @foreach($selectedRecords as $pItem)
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

                    {{-- NOTA BITÁCORA --}}
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

        @elseif($curStep === 'cambio')
            {{-- =========================================================================
                 PASO 2: FORMULARIO "CAMBIO DE ESTADO O ÁREA" (DENTRO DEL SLIDE-OVER)
                 ========================================================================= --}}
            <div class="sigem-sel-header">
                <button type="button" wire:click="volverAPanelPrincipal" class="sigem-sel-back-btn" title="Volver al panel principal">
                    ← Volver
                </button>
                <button type="button" wire:click="deseleccionarTodos" class="sigem-sel-close-btn" title="Cerrar">✕</button>
            </div>

            <div class="sigem-sel-body">
                <div>
                    <div class="sigem-sel-header-title" style="display: flex; align-items: center; gap: 8px;">
                        <span>🔄</span>
                        <span>Cambio</span>
                    </div>
                    <div class="sigem-sel-header-sub">
                        {{ $cantSel }} equipo{{ $cantSel > 1 ? 's seleccionados' : ' seleccionado' }}
                    </div>
                </div>

                <div class="sigem-sel-form-summary">
                    <div style="display: flex; align-items: flex-start; gap: 8px;">
                        <span style="font-size: 14px;">📋</span>
                        <div style="font-size: 12.5px; color: #1f2937; font-weight: 600; line-height: 1.4; word-break: break-all;">
                            {{ $codigosPreview }}
                        </div>
                    </div>
                    <div class="sigem-sel-form-tip">
                        <span>💡</span>
                        <span>Cambia el estado o el área. Queda registrado en el historial.</span>
                    </div>
                </div>

                <div class="sigem-sel-form-group">
                    <label class="sigem-sel-form-label">Nuevo estado</label>
                    <select wire:model="cambioNuevoEstado" class="sigem-sel-form-control">
                        <option value="">-- sin cambio --</option>
                        @foreach($estadosEnum as $optEst)
                            <option value="{{ $optEst->value }}">{{ $optEst->value }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="sigem-sel-form-group">
                    <label class="sigem-sel-form-label">Nueva área</label>
                    <select wire:model="cambioNuevaAreaId" class="sigem-sel-form-control">
                        <option value="">-- sin cambio --</option>
                        @foreach($todasLasAreas as $aId => $aNom)
                            <option value="{{ $aId }}">{{ $aNom }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="sigem-sel-form-group">
                    <label class="sigem-sel-form-label">Motivo del cambio</label>
                    <textarea wire:model="cambioMotivo" rows="3" class="sigem-sel-form-control sigem-sel-form-textarea" placeholder="Motivo del cambio (opcional)..."></textarea>
                </div>
            </div>

            <div class="sigem-sel-footer">
                <button type="button" wire:click="volverAPanelPrincipal" class="sigem-sel-footer-btn">
                    Cancelar
                </button>
                <button type="button" wire:click="ejecutarCambioSlideOver" class="sigem-btn-primary" style="padding: 8px 18px; font-size: 13.5px;">
                    Aplicar cambio
                </button>
            </div>

        @elseif($curStep === 'baja')
            {{-- =========================================================================
                 PASO 2: FORMULARIO "DAR DE BAJA" (DENTRO DEL SLIDE-OVER)
                 ========================================================================= --}}
            <div class="sigem-sel-header">
                <button type="button" wire:click="volverAPanelPrincipal" class="sigem-sel-back-btn" title="Volver al panel principal">
                    ← Volver
                </button>
                <button type="button" wire:click="deseleccionarTodos" class="sigem-sel-close-btn" title="Cerrar">✕</button>
            </div>

            <div class="sigem-sel-body">
                <div>
                    <div class="sigem-sel-header-title" style="display: flex; align-items: center; gap: 8px; color: #dc2626;">
                        <span>↓</span>
                        <span style="color: #111827;">Baja</span>
                    </div>
                    <div class="sigem-sel-header-sub">
                        {{ $cantSel }} equipo{{ $cantSel > 1 ? 's seleccionados' : ' seleccionado' }}
                    </div>
                </div>

                <div class="sigem-sel-form-summary">
                    <div style="display: flex; align-items: flex-start; gap: 8px;">
                        <span style="font-size: 14px;">📋</span>
                        <div style="font-size: 12.5px; color: #1f2937; font-weight: 600; line-height: 1.4; word-break: break-all;">
                            {{ $codigosPreview }}
                        </div>
                    </div>
                    <div class="sigem-sel-form-tip">
                        <span>💡</span>
                        <span>{{ $panel === 'admin' ? 'Los equipos pasarán a estado Dado de baja de forma inmediata.' : 'Los equipos pasarán a Solicitud de baja hasta que se confirme.' }}</span>
                    </div>
                </div>

                <div class="sigem-sel-form-group">
                    <label class="sigem-sel-form-label">Motivo de baja *</label>
                    <select wire:model="bajaMotivo" class="sigem-sel-form-control" required>
                        <option value="">-- seleccione --</option>
                        <option value="Daño irreparable">Daño irreparable</option>
                        <option value="Obsolescencia">Obsolescencia</option>
                        <option value="Pérdida">Pérdida</option>
                        <option value="Robo">Robo</option>
                        <option value="Otro">Otro</option>
                    </select>
                </div>

                <div class="sigem-sel-form-group">
                    <label class="sigem-sel-form-label">Observaciones</label>
                    <textarea wire:model="bajaObservaciones" rows="3" class="sigem-sel-form-control sigem-sel-form-textarea" placeholder="Observaciones detalladas (opcional)..."></textarea>
                </div>
            </div>

            <div class="sigem-sel-footer">
                <button type="button" wire:click="volverAPanelPrincipal" class="sigem-sel-footer-btn">
                    Cancelar
                </button>
                <button type="button" wire:click="ejecutarBajaSlideOver" class="sigem-btn-primary" style="background: #dc2626; border-color: #dc2626; padding: 8px 18px; font-size: 13.5px;">
                    {{ $panel === 'admin' ? 'Dar de baja' : 'Solicitar baja' }}
                </button>
            </div>

        @elseif($curStep === 'modificar')
            {{-- =========================================================================
                 PASO 2: FORMULARIO "MODIFICAR TODOS" (DENTRO DEL SLIDE-OVER)
                 ========================================================================= --}}
            <div class="sigem-sel-header">
                <button type="button" wire:click="volverAPanelPrincipal" class="sigem-sel-back-btn" title="Volver al panel principal">
                    ← Volver
                </button>
                <button type="button" wire:click="deseleccionarTodos" class="sigem-sel-close-btn" title="Cerrar">✕</button>
            </div>

            <div class="sigem-sel-body">
                <div>
                    <div class="sigem-sel-header-title" style="display: flex; align-items: center; gap: 8px;">
                        <span>✏️</span>
                        <span>{{ $cantSel === 1 ? 'Modificar equipo' : 'Modificar todos' }}</span>
                    </div>
                    <div class="sigem-sel-header-sub">
                        {{ $cantSel }} equipo{{ $cantSel > 1 ? 's seleccionados' : ' seleccionado' }}
                    </div>
                </div>

                <div class="sigem-sel-form-summary">
                    <div style="display: flex; align-items: flex-start; gap: 8px;">
                        <span style="font-size: 14px;">📋</span>
                        <div style="font-size: 12.5px; color: #1f2937; font-weight: 600; line-height: 1.4; word-break: break-all;">
                            {{ $codigosPreview }}
                        </div>
                    </div>
                    <div class="sigem-sel-form-tip">
                        <span>💡</span>
                        <span>Elige el campo y el nuevo valor.</span>
                    </div>
                </div>

                <div class="sigem-sel-form-group">
                    <label class="sigem-sel-form-label">Campo a modificar *</label>
                    <select wire:model.live="modificarCampo" class="sigem-sel-form-control" required>
                        <option value="">-- seleccione --</option>
                        <option value="estado">Estado</option>
                        <option value="area">Área</option>
                        <option value="departamento">Departamento</option>
                        <option value="ubicacion_fisica">Ubicación física</option>
                    </select>
                </div>

                <div class="sigem-sel-form-group">
                    <label class="sigem-sel-form-label">Nuevo valor *</label>
                    @if(($campoMod ?? '') === 'estado')
                        <select wire:model="modificarNuevoValor" class="sigem-sel-form-control">
                            <option value="">-- seleccione --</option>
                            @foreach($estadosEnum as $optEst)
                                <option value="{{ $optEst->value }}">{{ $optEst->value }}</option>
                            @endforeach
                        </select>
                    @elseif(($campoMod ?? '') === 'area')
                        <select wire:model="modificarNuevoValor" class="sigem-sel-form-control">
                            <option value="">-- seleccione --</option>
                            @foreach($todasLasAreas as $aId => $aNom)
                                <option value="{{ $aId }}">{{ $aNom }}</option>
                            @endforeach
                        </select>
                    @elseif(($campoMod ?? '') === 'departamento')
                        <select wire:model="modificarNuevoValor" class="sigem-sel-form-control">
                            <option value="">-- seleccione --</option>
                            @foreach($todosLosDeptos as $dId => $dNom)
                                <option value="{{ $dId }}">{{ $dNom }}</option>
                            @endforeach
                        </select>
                    @elseif(($campoMod ?? '') === 'ubicacion_fisica')
                        <input type="text" wire:model="modificarNuevoValor" class="sigem-sel-form-control" placeholder="ej. Depto. de Sistemas > Oficina > Aula 101" />
                    @else
                        <input type="text" disabled class="sigem-sel-form-control" placeholder="Seleccione primero un campo a modificar..." style="opacity: 0.6; cursor: not-allowed; background: #f3f4f6;" />
                    @endif
                </div>
            </div>

            <div class="sigem-sel-footer">
                <button type="button" wire:click="volverAPanelPrincipal" class="sigem-sel-footer-btn">
                    Cancelar
                </button>
                <button type="button" wire:click="ejecutarModificarSlideOver" class="sigem-btn-primary" style="padding: 8px 18px; font-size: 13.5px;">
                    {{ $cantSel === 1 ? 'Aplicar al equipo' : 'Aplicar a todos' }}
                </button>
            </div>
        @endif
    </aside>
@endif
