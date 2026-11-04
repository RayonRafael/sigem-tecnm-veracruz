@php
    $statePath = $getStatePath();
    $opciones = [
        [
            'id' => 'Salida',
            'nombre' => 'Salida',
            'icono' => '📤',
            'desc' => 'El material sale del almacén',
        ],
        [
            'id' => 'Entrada',
            'nombre' => 'Entrada',
            'icono' => '📥',
            'desc' => 'El material entra al almacén',
        ],
        [
            'id' => 'Traslado',
            'nombre' => 'Traslado',
            'icono' => '🔄',
            'desc' => 'El material cambia de ubicación',
        ],
    ];
@endphp

<div
    x-data="{
        state: $wire.$entangle('{{ $statePath }}'),
        select(val) {
            this.state = val;
            $wire.set('{{ $statePath }}', val);
        }
    }"
    class="w-full"
>
    <style>
        .choice-grid-solicitud {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
            width: 100%;
        }
        @media (max-width: 768px) {
            .choice-grid-solicitud {
                grid-template-columns: 1fr;
            }
        }
        .choice-card-sol {
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            padding: 14px 16px;
            cursor: pointer;
            display: flex;
            gap: 12px;
            align-items: flex-start;
            transition: all .15s ease-in-out;
            background: #ffffff;
            text-align: left;
            user-select: none;
        }
        .dark .choice-card-sol {
            background: #1e293b;
            border-color: #334155;
        }
        .choice-card-sol:hover {
            border-color: #93c5fd;
            background: #f8fafc;
        }
        .dark .choice-card-sol:hover {
            border-color: #3b82f6;
            background: #1e293b;
        }
        .choice-card-sol.sel {
            border-color: #1b396a;
            background: #eef2f9;
            box-shadow: 0 0 0 1px #1b396a;
        }
        .dark .choice-card-sol.sel {
            border-color: #3b82f6;
            background: rgba(59, 130, 246, 0.15);
            box-shadow: 0 0 0 1px #3b82f6;
        }
        .choice-ico-sol {
            font-size: 24px;
            line-height: 1.2;
            flex-shrink: 0;
        }
        .choice-title-sol {
            font-size: 15px;
            font-weight: 700;
            display: block;
            color: #1e293b;
            line-height: 1.3;
        }
        .dark .choice-title-sol {
            color: #f1f5f9;
        }
        .choice-card-sol.sel .choice-title-sol {
            color: #1b396a;
        }
        .dark .choice-card-sol.sel .choice-title-sol {
            color: #60a5fa;
        }
        .choice-desc-sol {
            font-size: 13px;
            color: #64748b;
            display: block;
            margin-top: 3px;
            line-height: 1.4;
        }
        .dark .choice-desc-sol {
            color: #94a3b8;
        }
    </style>

    <div class="choice-grid-solicitud">
        @foreach($opciones as $opc)
            <div
                class="choice-card-sol"
                :class="{ 'sel': (state || 'Salida') === '{{ $opc['id'] }}' }"
                @click="select('{{ $opc['id'] }}')"
                role="button"
                tabindex="0"
            >
                <div class="choice-ico-sol">{{ $opc['icono'] }}</div>
                <div>
                    <span class="choice-title-sol">{{ $opc['nombre'] }}</span>
                    <span class="choice-desc-sol">{{ $opc['desc'] }}</span>
                </div>
            </div>
        @endforeach
    </div>
</div>

