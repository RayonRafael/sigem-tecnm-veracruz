@props([
    'cantSel' => 0,
    'codigosPreview' => '',
    'campoMod' => '',
    'estadosEnum' => [],
    'todasLasAreas' => [],
    'todosLosDeptos' => [],
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
