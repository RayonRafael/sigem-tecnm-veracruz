@props([
    'rep',
])

<div class="sigem-new-card sigem-table-card" wire:key="sigem-new-table-{{ $rep['id'] }}">
    <div class="sigem-new-card-top">
        <div class="sigem-new-card-title">{{ $rep['emoji'] }} {{ $rep['titulo'] }}</div>
        @if (! empty($rep['totales_label']))
            <span class="sigem-badge-totales">{{ $rep['totales_label'] }}</span>
        @endif
    </div>

    <div class="sigem-new-card-desc">{{ $rep['descripcion'] }}</div>

    {{-- TABLA TOP 5 REGISTROS --}}
    <div class="sigem-mini-table-box">
        <div class="sigem-mini-table-title">📋 Vista previa (top 5):</div>
        <table class="sigem-mini-table">
            <thead>
                <tr>
                    @foreach (($rep['top5_headers'] ?? []) as $th)
                        <th>{{ $th }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse (($rep['top5_rows'] ?? []) as $tr)
                    <tr>
                        @foreach ($tr as $td)
                            <td class="{{ preg_match('/^(\d{2}\/\d{2}\/\d{4}|\d{2}:\d{2})$/', trim((string) $td)) ? 'sigem-td-nowrap' : '' }}">{{ $td }}</td>
                        @endforeach
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ count($rep['top5_headers'] ?? [1]) }}" style="text-align: center; color: #94a3b8; padding: 6px;">
                            Sin registros disponibles
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- PIE CON ACCIONES --}}
    <div class="sigem-new-card-foot">
        <button type="button"
                wire:click="verNuevoReporte('{{ $rep['id'] }}')"
                class="sigem-btn-preview"
                title="Ver vista previa detallada">
            👁️ Detalle
        </button>

        <div style="display: inline-flex; align-items: center; gap: 6px;">
            <button type="button"
                    wire:click="exportarNuevoReporte('{{ $rep['id'] }}', 'xlsx')"
                    class="sigem-btn-export-excel"
                    title="Descargar como archivo XLSX de Excel">
                📥 Excel
            </button>
            <button type="button"
                    wire:click="exportarNuevoReporte('{{ $rep['id'] }}', 'csv')"
                    class="sigem-btn-export-csv"
                    title="Descargar como archivo CSV">
                📥 CSV
            </button>
        </div>
    </div>
</div>
