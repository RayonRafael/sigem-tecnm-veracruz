@props([
    'cantSel' => 0,
    'codigosPreview' => '',
    'estadoAct' => '',
    'estadosEnum' => [],
    'todosLosDeptos' => [],
    'todasLasAreas' => [],
    'areasDisponibles' => [],
    'todosLosReceptores' => [],
    'todosLosProveedores' => [],
    'motivosBajaOpciones' => [],
    'propiedadActual' => 'Sin definir',
])

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

    {{-- SECCIÓN 1: ESTADO --}}
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

    {{-- SECCIÓN 2: CAMPOS CONTEXTUALES --}}
    @if($estadoAct === 'Cambio de área')
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
        <div style="background: #f0f9ff; border: 1px solid #bae6fd; border-radius: 8px; padding: 12px; margin-bottom: 16px;">
            <div class="sigem-sel-form-group">
                <label class="sigem-sel-form-label">Propiedad actual</label>
                <div style="background: #e2e8f0; border-radius: 6px; padding: 8px 12px; font-weight: 600; color: #334155;">
                    {{ $propiedadActual }}
                </div>
            </div>

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

    {{-- SECCIÓN 3: CAMPOS COMUNES --}}
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
