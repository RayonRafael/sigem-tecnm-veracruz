@php
    $statePath = $getStatePath();
    $opciones = [
        [
            'id' => 'Correctivo',
            'nombre' => 'Correctivo',
            'icono' => '🔧',
            'desc' => 'Reparar una falla existente',
        ],
        [
            'id' => 'Preventivo',
            'nombre' => 'Preventivo',
            'icono' => '🛡️',
            'desc' => 'Mantenimiento programado',
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
        .choice-grid-mantenimiento {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
            width: 100%;
        }
        @media (max-width: 640px) {
            .choice-grid-mantenimiento {
                grid-template-columns: 1fr;
            }
        }
        .choice-card-mant {
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
        .dark .choice-card-mant {
            background: #1e293b;
            border-color: #334155;
        }
        .choice-card-mant:hover {
            border-color: #93c5fd;
            background: #f8fafc;
        }
        .dark .choice-card-mant:hover {
            border-color: #3b82f6;
            background: #1e293b;
        }
        .choice-card-mant.sel {
            border-color: #1b396a;
            background: #eef2f9;
            box-shadow: 0 0 0 1px #1b396a;
        }
        .dark .choice-card-mant.sel {
            border-color: #3b82f6;
            background: rgba(59, 130, 246, 0.15);
            box-shadow: 0 0 0 1px #3b82f6;
        }
        .choice-ico-mant {
            font-size: 24px;
            line-height: 1.2;
            flex-shrink: 0;
        }
        .choice-title-mant {
            font-size: 15px;
            font-weight: 700;
            display: block;
            color: #1e293b;
            line-height: 1.3;
        }
        .dark .choice-title-mant {
            color: #f1f5f9;
        }
        .choice-card-mant.sel .choice-title-mant {
            color: #1b396a;
        }
        .dark .choice-card-mant.sel .choice-title-mant {
            color: #60a5fa;
        }
        .choice-desc-mant {
            font-size: 13px;
            color: #64748b;
            display: block;
            margin-top: 3px;
            line-height: 1.4;
        }
        .dark .choice-desc-mant {
            color: #94a3b8;
        }
    </style>

    <div class="choice-grid-mantenimiento">
        @foreach($opciones as $opc)
            <div
                class="choice-card-mant"
                :class="{ 'sel': (state || 'Correctivo') === '{{ $opc['id'] }}' }"
                @click="select('{{ $opc['id'] }}')"
                role="button"
                tabindex="0"
            >
                <div class="choice-ico-mant">{{ $opc['icono'] }}</div>
                <div>
                    <span class="choice-title-mant">{{ $opc['nombre'] }}</span>
                    <span class="choice-desc-mant">{{ $opc['desc'] }}</span>
                </div>
            </div>
        @endforeach
    </div>
</div>

