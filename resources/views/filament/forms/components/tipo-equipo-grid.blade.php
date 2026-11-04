@php
    $statePath = $getStatePath();
    $tipos = [
        ['id' => 'pc', 'nombre' => 'Computadora', 'icono' => '🖥️', 'desc' => 'Monitor, teclado, mouse y CPU'],
        ['id' => 'aio', 'nombre' => 'All in One', 'icono' => '🖥️', 'desc' => 'Equipo + teclado y mouse'],
        ['id' => 'lap', 'nombre' => 'Laptop', 'icono' => '💻', 'desc' => 'Laptop / Notebook'],
        ['id' => 'cpu', 'nombre' => 'CPU', 'icono' => '🗄️', 'desc' => 'Computadora de Escritorio / CPU'],
        ['id' => 'mon', 'nombre' => 'Monitor', 'icono' => '🖵', 'desc' => 'Monitor / Pantalla'],
        ['id' => 'tec', 'nombre' => 'Teclado', 'icono' => '⌨️', 'desc' => 'Teclado'],
        ['id' => 'mou', 'nombre' => 'Mouse', 'icono' => '🖱️', 'desc' => 'Mouse (Ratón)'],
        ['id' => 'imp', 'nombre' => 'Impresora', 'icono' => '🖨️', 'desc' => 'Impresora Láser o Inyección'],
        ['id' => 'pro', 'nombre' => 'Proyector', 'icono' => '📽️', 'desc' => 'Proyector / Cañón'],
        ['id' => 'nob', 'nombre' => 'No-Break', 'icono' => '🔋', 'desc' => 'No-Break / Regulador'],
        ['id' => 'swi', 'nombre' => 'Switch de red', 'icono' => '🔌', 'desc' => 'Switch de Red'],
        ['id' => 'otro', 'nombre' => 'Otro artículo', 'icono' => '📦', 'desc' => 'Cualquier otro tipo del catálogo'],
    ];
@endphp

<div
    x-data="{
        state: $wire.$entangle('{{ $statePath }}'),
        selectTipo(id) {
            this.state = id;
            $wire.set('{{ $statePath }}', id);
        }
    }"
    class="w-full"
>
    <style>
        .tipo-grid-wrapper {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 0.5rem;
            width: 100%;
        }
        @media (max-width: 600px) {
            .tipo-grid-wrapper {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        .tipo-grid-btn {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            padding: 0.625rem 0.625rem;
            border: 1.5px solid #e2e8f0;
            border-radius: 0.5rem;
            background-color: #ffffff;
            cursor: pointer;
            transition: all 0.15s ease-in-out;
            text-align: left;
            position: relative;
            outline: none;
            width: 100%;
            min-height: 70px;
            box-sizing: border-box;
        }
        .dark .tipo-grid-btn {
            background-color: #1e293b;
            border-color: #334155;
        }
        .tipo-grid-btn:hover {
            border-color: #93c5fd;
            background-color: #f8fafc;
        }
        .dark .tipo-grid-btn:hover {
            border-color: #3b82f6;
            background-color: #1e293b;
        }
        .tipo-grid-btn.on {
            border-color: #2563eb;
            background-color: #eff6ff;
            box-shadow: 0 0 0 1px #2563eb;
        }
        .dark .tipo-grid-btn.on {
            border-color: #3b82f6;
            background-color: rgba(59, 130, 246, 0.15);
            box-shadow: 0 0 0 1px #3b82f6;
        }
        .tipo-grid-icon {
            font-size: 1.375rem;
            line-height: 1;
            margin-bottom: 0.25rem;
        }
        .tipo-grid-name {
            font-size: 0.8125rem;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 0.125rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            width: 100%;
        }
        .dark .tipo-grid-name {
            color: #f1f5f9;
        }
        .tipo-grid-btn.on .tipo-grid-name {
            color: #1d4ed8;
        }
        .dark .tipo-grid-btn.on .tipo-grid-name {
            color: #93c5fd;
        }
        .tipo-grid-sub {
            font-size: 0.6875rem;
            color: #64748b;
            line-height: 1.25;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .dark .tipo-grid-sub {
            color: #94a3b8;
        }
        .tipo-grid-btn.on .tipo-grid-sub {
            color: #2563eb;
        }
        .dark .tipo-grid-btn.on .tipo-grid-sub {
            color: #bfdbfe;
        }
    </style>

    <div class="tipo-grid-wrapper" role="radiogroup" aria-label="¿Qué vas a registrar?">
        @foreach($tipos as $tipo)
            <button
                type="button"
                data-tipo="{{ $tipo['id'] }}"
                @click="selectTipo('{{ $tipo['id'] }}')"
                :class="{ 'on': (state || 'pc') === '{{ $tipo['id'] }}' }"
                class="tipo-grid-btn"
                role="radio"
                :aria-checked="(state || 'pc') === '{{ $tipo['id'] }}'"
            >
                <div class="tipo-grid-icon">{{ $tipo['icono'] }}</div>
                <div class="tipo-grid-name">{{ $tipo['nombre'] }}</div>
                <div class="tipo-grid-sub">{{ $tipo['desc'] }}</div>
            </button>
        @endforeach
    </div>
</div>

