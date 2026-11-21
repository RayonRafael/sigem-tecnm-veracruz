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
                $aulaNom = $rec->aula
                    ?? $rec->salon?->nombre
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

            $bajasSeleccionadas = \App\Support\SigemBajaFormatHelper::filtrarInventariosEnBaja($selectedRecords);
            $cantBajas = $bajasSeleccionadas->count();

            $estadoAct = $this->cambioNuevoEstado ?? '';
            $deptoAct = $this->cambioDepartamentoId ?? '';
            $areaAct = $this->cambioNuevaAreaId ?? '';

            // Carga perezosa de catálogos: solo consultar BD cuando se abre el formulario correspondiente
            $necesitaCatalogos = in_array($curStep, ['cambiar', 'modificar'], true);
            $todasLasAreas = $necesitaCatalogos
                ? \App\Models\Area::orderBy('nombre')->pluck('nombre', 'id_area')
                : collect();
            $todosLosDeptos = $necesitaCatalogos
                ? \App\Models\Departamento::orderBy('nombre')->pluck('nombre', 'id_departamento')
                : collect();
            $estadosEnum = $necesitaCatalogos ? \App\Enums\EstadoInventario::cases() : [];

            $areasDisponibles = ($necesitaCatalogos && !empty($deptoAct))
                ? \App\Models\Area::where('id_departamento', $deptoAct)->orderBy('nombre')->pluck('nombre', 'id_area')
                : $todasLasAreas;

            $todosLosReceptores = ($curStep === 'cambiar' && in_array($estadoAct, ['Asignado', 'Reasignado'], true))
                ? \App\Models\Receptor::orderBy('nombre')->get()->mapWithKeys(function ($r) {
                    $nom = trim("{$r->nombre} {$r->apellido_paterno} {$r->apellido_materno}");
                    return [$r->id_receptor => ($nom ?: "Receptor #{$r->id_receptor}") . ($r->cargo ? " ({$r->cargo})" : '')];
                })
                : collect();

            $todosLosProveedores = ($curStep === 'cambiar' && $estadoAct === 'Devuelto a Proveedor')
                ? \App\Models\Proveedor::orderBy('nombre_empresa')->pluck('nombre_empresa', 'id_proveedor')
                : collect();

            $motivosBajaOpciones = in_array($curStep, ['cambiar', 'baja'], true)
                ? \App\Support\SigemBajaFormatHelper::motivosBajaOptions()
                : [];

            // Calcular propiedad actual de los equipos seleccionados
            $propiedades = $selectedRecords->pluck('tipo_propiedad')->unique()->filter();
            if ($propiedades->count() === 0) {
                $propiedadActual = 'Sin definir';
            } elseif ($propiedades->count() === 1) {
                $propiedadActual = $propiedades->first();  // "Propio" o "Rentado"
            } else {
                $propiedadActual = 'Mixto';  // Equipos con diferente propiedad
            }
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

                            @if($cantBajas > 0)
                                <button type="button" wire:click="descargarFormatosBajaLote" class="sigem-sel-action-card is-pdf" title="Descargar Formatos de Baja (lote) en PDF">
                                    <span class="sigem-sel-action-icon">📄</span>
                                    <span class="sigem-sel-action-title">Formatos de Baja (lote)</span>
                                    <span class="sigem-sel-action-desc">PDF multipágina ({{ $cantBajas }} {{ $cantBajas === 1 ? 'bien' : 'bienes' }})</span>
                                </button>
                            @endif
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

                {{-- ── SECCIÓN 1: ESTADO ── --}}
                <div class="sigem-sel-form-group">
                    <label class="sigem-sel-form-label">Nuevo estado</label>
                    <select wire:model.live="cambioNuevoEstado" class="sigem-sel-form-control">
                        <option value="">-- sin cambio --</option>
                        @foreach($estadosEnum as $optEst)
                            <option value="{{ $optEst->value }}">{{ $optEst->value }}</option>
                        @endforeach
                        <option value="Cambio de propiedad">Cambio de propiedad</option>
                    </select>
                </div>

                {{-- ── SECCIÓN 2: CAMPOS CONTEXTUALES ── --}}
                @if($estadoAct === 'Cambio de área')
                    {{-- SI ES "Cambio de área": Depto, Área, Aula --}}
                    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px; margin-bottom: 16px;">
                        <div style="font-size: 11px; font-weight: 700; color: #475569; text-transform: uppercase; margin-bottom: 10px; display: flex; align-items: center; gap: 6px;">
                            <span>📍</span>
                            <span>Datos de nueva ubicación</span>
                        </div>

                        <div class="sigem-sel-form-group">
                            <label class="sigem-sel-form-label">Nuevo departamento</label>
                            <select wire:model.live="cambioDepartamentoId" class="sigem-sel-form-control">
                                <option value="">-- Seleccionar departamento --</option>
                                @foreach($todosLosDeptos as $dId => $dNom)
                                    <option value="{{ $dId }}">{{ $dNom }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="sigem-sel-form-group">
                            <label class="sigem-sel-form-label">Nueva área</label>
                            <select wire:model.live="cambioNuevaAreaId" class="sigem-sel-form-control">
                                <option value="">-- Seleccionar área --</option>
                                @foreach($areasDisponibles as $aId => $aNom)
                                    <option value="{{ $aId }}">{{ $aNom }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="sigem-sel-form-group" style="margin-bottom: 0;">
                            <label class="sigem-sel-form-label">Nueva aula</label>
                            <input type="text" wire:model="cambioAula" class="sigem-sel-form-control" placeholder="ej. E101">
                        </div>
                    </div>

                @elseif(in_array($estadoAct, ['Asignado', 'Reasignado'], true))
                    {{-- SI ES "Asignado" o "Reasignado": Receptor, Depto, Área --}}
                    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px; margin-bottom: 16px;">
                        <div style="font-size: 11px; font-weight: 700; color: #475569; text-transform: uppercase; margin-bottom: 10px; display: flex; align-items: center; gap: 6px;">
                            <span>👤</span>
                            <span>Asignación y ubicación</span>
                        </div>

                        <div class="sigem-sel-form-group">
                            <label class="sigem-sel-form-label">Nuevo receptor</label>
                            <select wire:model="cambioReceptorId" class="sigem-sel-form-control">
                                <option value="">-- Seleccionar receptor --</option>
                                @foreach($todosLosReceptores as $rId => $rNom)
                                    <option value="{{ $rId }}">{{ $rNom }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="sigem-sel-form-group">
                            <label class="sigem-sel-form-label">Nuevo departamento</label>
                            <select wire:model.live="cambioDepartamentoId" class="sigem-sel-form-control">
                                <option value="">-- sin cambio --</option>
                                @foreach($todosLosDeptos as $dId => $dNom)
                                    <option value="{{ $dId }}">{{ $dNom }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="sigem-sel-form-group" style="margin-bottom: 0;">
                            <label class="sigem-sel-form-label">Nueva área</label>
                            <select wire:model.live="cambioNuevaAreaId" class="sigem-sel-form-control">
                                <option value="">-- sin cambio --</option>
                                @foreach($areasDisponibles as $aId => $aNom)
                                    <option value="{{ $aId }}">{{ $aNom }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                @elseif(in_array($estadoAct, ['Dado de baja', 'Solicitud de baja'], true))
                    {{-- SI ES "Dado de baja" o "Solicitud de baja": Motivo de baja, Descripción --}}
                    <div style="background: #fef2f2; border: 1px solid #fecaca; border-radius: 8px; padding: 12px; margin-bottom: 16px;">
                        <div style="font-size: 11px; font-weight: 700; color: #991b1b; text-transform: uppercase; margin-bottom: 10px; display: flex; align-items: center; gap: 6px;">
                            <span>⚠️</span>
                            <span>Detalles del trámite de baja</span>
                        </div>

                        <div class="sigem-sel-form-group">
                            <label class="sigem-sel-form-label" style="color: #991b1b;">Motivo de baja</label>
                            <select wire:model="cambioMotivoBaja" class="sigem-sel-form-control">
                                <option value="">-- Seleccionar motivo --</option>
                                @foreach($motivosBajaOpciones as $mVal => $mLbl)
                                    <option value="{{ $mVal }}">{{ $mLbl }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="sigem-sel-form-group" style="margin-bottom: 0;">
                            <label class="sigem-sel-form-label" style="color: #991b1b;">Descripción</label>
                            <input type="text" wire:model="cambioDescripcionBaja" class="sigem-sel-form-control" placeholder="Descripción detallada de la baja...">
                        </div>
                    </div>

                @elseif(in_array($estadoAct, ['Dañado', 'Daño'], true))
                    {{-- SI ES "Daño": Descripción del daño, Nivel de daño --}}
                    <div style="background: #fef2f2; border: 1px solid #fecaca; border-radius: 8px; padding: 12px; margin-bottom: 16px;">
                        <div style="font-size: 11px; font-weight: 700; color: #991b1b; text-transform: uppercase; margin-bottom: 10px; display: flex; align-items: center; gap: 6px;">
                            <span>⚠️</span>
                            <span>Reporte de daño</span>
                        </div>

                        <div class="sigem-sel-form-group">
                            <label class="sigem-sel-form-label">Descripción del daño</label>
                            <input type="text" wire:model="cambioDescripcionDano" class="sigem-sel-form-control" placeholder="Detalle del daño presentado...">
                        </div>

                        <div class="sigem-sel-form-group" style="margin-bottom: 0;">
                            <label class="sigem-sel-form-label">Nivel de daño</label>
                            <select wire:model="cambioNivelDano" class="sigem-sel-form-control">
                                <option value="Menor">Menor</option>
                                <option value="Mayor">Mayor</option>
                            </select>
                        </div>
                    </div>

                @elseif($estadoAct === 'En Mantenimiento')
                    {{-- SI ES "En Mantenimiento": Tipo de servicio, Descripción falla --}}
                    <div style="background: #fffbeb; border: 1px solid #fde68a; border-radius: 8px; padding: 12px; margin-bottom: 16px;">
                        <div style="font-size: 11px; font-weight: 700; color: #92400e; text-transform: uppercase; margin-bottom: 10px; display: flex; align-items: center; gap: 6px;">
                            <span>🔧</span>
                            <span>Detalles del mantenimiento</span>
                        </div>

                        <div class="sigem-sel-form-group">
                            <label class="sigem-sel-form-label">Tipo de servicio</label>
                            <select wire:model="cambioTipoServicio" class="sigem-sel-form-control">
                                <option value="Preventivo">Preventivo</option>
                                <option value="Correctivo">Correctivo</option>
                                <option value="Mejora">Mejora</option>
                            </select>
                        </div>

                        <div class="sigem-sel-form-group" style="margin-bottom: 0;">
                            <label class="sigem-sel-form-label">Descripción de la falla</label>
                            <input type="text" wire:model="cambioDescripcionFalla" class="sigem-sel-form-control" placeholder="Falla o trabajo a realizar...">
                        </div>
                    </div>

                @elseif($estadoAct === 'Devuelto a Proveedor')
                    {{-- SI ES "Devuelto a Proveedor": Proveedor, Motivo devolución --}}
                    <div style="background: #fffbeb; border: 1px solid #fde68a; border-radius: 8px; padding: 12px; margin-bottom: 16px;">
                        <div style="font-size: 11px; font-weight: 700; color: #92400e; text-transform: uppercase; margin-bottom: 10px; display: flex; align-items: center; gap: 6px;">
                            <span>↩️</span>
                            <span>Datos de devolución</span>
                        </div>

                        <div class="sigem-sel-form-group">
                            <label class="sigem-sel-form-label">Proveedor</label>
                            <select wire:model="cambioProveedorId" class="sigem-sel-form-control">
                                <option value="">-- Seleccionar proveedor --</option>
                                @foreach($todosLosProveedores as $prId => $prNom)
                                    <option value="{{ $prId }}">{{ $prNom }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="sigem-sel-form-group" style="margin-bottom: 0;">
                            <label class="sigem-sel-form-label">Motivo de devolución</label>
                            <input type="text" wire:model="cambioMotivoDevolucion" class="sigem-sel-form-control" placeholder="Motivo de la devolución...">
                        </div>
                    </div>
                @elseif($estadoAct === 'Cambio de propiedad')
                    {{-- SI ES "Cambio de propiedad": Propiedad ACTUAL y NUEVA --}}
                    <div style="background: #f0f9ff; border: 1px solid #bae6fd; border-radius: 8px; padding: 12px; margin-bottom: 16px;">
                        {{-- Propiedad ACTUAL (solo lectura) --}}
                        <div class="sigem-sel-form-group">
                            <label class="sigem-sel-form-label">Propiedad actual</label>
                            <div style="background: #e2e8f0; border-radius: 6px; padding: 8px 12px; font-weight: 600; color: #334155;">
                                {{ $propiedadActual }}
                            </div>
                        </div>

                        {{-- Nueva propiedad (seleccionable) --}}
                        <div class="sigem-sel-form-group" style="margin-bottom: 0;">
                            <label class="sigem-sel-form-label">Nueva propiedad</label>
                            <select wire:model="cambioNuevoTipoPropiedad" class="sigem-sel-form-control">
                                <option value="">-- Seleccionar --</option>
                                <option value="Propio">Propio</option>
                                <option value="Rentado">Rentado</option>
                            </select>
                        </div>
                    </div>
                @elseif(empty($estadoAct))
                    <div class="sigem-sel-form-group">
                        <label class="sigem-sel-form-label">Nueva área</label>
                        <select wire:model.live="cambioNuevaAreaId" class="sigem-sel-form-control">
                            <option value="">-- sin cambio --</option>
                            @foreach($todasLasAreas as $aId => $aNom)
                                <option value="{{ $aId }}">{{ $aNom }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif

                {{-- ── SECCIÓN 3: CAMPOS COMUNES ── --}}
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
                        @foreach(\App\Support\SigemBajaFormatHelper::motivosBajaOptions() as $valor => $etiqueta)
                            <option value="{{ $valor }}">{{ $etiqueta }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="sigem-sel-form-group">
                    <label class="sigem-sel-form-label">Observaciones</label>
                    <textarea wire:model="bajaObservaciones" rows="3" class="sigem-sel-form-control sigem-sel-form-textarea" placeholder="Observaciones detalladas (opcional)..."></textarea>
                </div>

                <div class="sigem-sel-form-group">
                    <label class="sigem-sel-form-label">Folio SIBESEP (opcional)</label>
                    <input type="text" wire:model="bajaFolioSibsep" maxlength="100" class="sigem-sel-form-control" placeholder="Vacío por defecto; lo asigna Recursos Materiales">
                </div>

                <div class="sigem-sel-form-group">
                    <label class="sigem-sel-form-label">Posible área de utilización (opcional)</label>
                    <select wire:model="bajaPosibleAreaUtilizacion" class="sigem-sel-form-control">
                        <option value="">-- sin especificar --</option>
                        @foreach($todasLasAreas as $idArea => $nomArea)
                            <option value="{{ $nomArea }}">{{ $nomArea }}</option>
                        @endforeach
                    </select>
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
                        <option value="tipo_propiedad">Tipo de propiedad (Propio / Rentado)</option>
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
                    @elseif(($campoMod ?? '') === 'tipo_propiedad')
                        <select wire:model="modificarNuevoValor" class="sigem-sel-form-control">
                            <option value="">-- seleccione --</option>
                            <option value="Propio">Propio</option>
                            <option value="Rentado">Rentado</option>
                        </select>
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
