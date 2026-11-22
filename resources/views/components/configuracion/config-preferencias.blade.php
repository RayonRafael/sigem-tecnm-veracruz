@props([
    'registrosPorHoja' => 10,
    'otroValor' => '',
    'mensajePreferencia' => '',
])

<div class="sigem-pref-card">
    <div class="sigem-pref-row">
        <div>
            <div class="sigem-pref-t">Registros por hoja</div>
            <div class="sigem-pref-d">
                Define la cantidad de registros que se visualizan de forma predeterminada por página
                en las tablas y catálogos del panel administrativo.
            </div>
        </div>

        <div style="display: flex; flex-direction: column; gap: 12px; align-items: flex-end;">
            {{-- Chips rápidos --}}
            <div class="sigem-pref-chips">
                @foreach ([10, 20, 50, 100, 200] as $opt)
                    <button type="button"
                            wire:click="seleccionarRegistrosPorHoja({{ $opt }})"
                            class="{{ $registrosPorHoja === $opt && empty($otroValor) ? 'on' : '' }}">
                        {{ $opt }}
                    </button>
                @endforeach
            </div>

            {{-- Input personalizado y botón guardar --}}
            <div class="sigem-pref-in">
                <span>Otro valor:</span>
                <input type="number"
                       wire:model.live="otroValor"
                       min="5"
                       max="500"
                       placeholder="5 - 500">
                <button type="button"
                        wire:click="guardarPreferencias"
                        wire:loading.attr="disabled"
                        class="sigem-rep-btn-primary">
                    <span wire:loading.remove wire:target="guardarPreferencias">Guardar</span>
                    <span wire:loading wire:target="guardarPreferencias" style="display: none;" class="sigem-btn-loading-content">
                        <svg class="sigem-btn-spinner" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" opacity="0.25"></circle><path d="M4 12a8 8 0 018-8" stroke="currentColor" stroke-width="3" stroke-linecap="round"></path></svg>
                        Guardando...
                    </span>
                </button>
            </div>

            {{-- Mensaje de estado --}}
            @if (! empty($mensajePreferencia))
                <div class="sigem-pref-msg ok">
                    {{ $mensajePreferencia }}
                </div>
            @endif
        </div>
    </div>
</div>
