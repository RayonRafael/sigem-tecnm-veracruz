@props([
    'panel' => 'admin',
    'cantSel' => 0,
    'codigosPreview' => '',
    'motivosBajaOpciones' => [],
    'todasLasAreas' => [],
])

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
            @foreach($motivosBajaOpciones as $valor => $etiqueta)
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
