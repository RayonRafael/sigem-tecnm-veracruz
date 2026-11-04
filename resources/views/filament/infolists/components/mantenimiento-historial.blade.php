@php
    $record = $getRecord();
    $events = [];

    if ($record) {
        if ($record->fecha_fin || in_array($record->estado, ['Completado', 'Terminado'])) {
            $events[] = [
                'fecha' => ($record->fecha_fin ? $record->fecha_fin->format('d/m/Y') : ($record->updated_at ? $record->updated_at->format('d/m/Y') : date('d/m/Y'))),
                'usuario' => $record->nombre_tecnico ?? 'Técnico asignado',
                'accion' => 'Mantenimiento completado',
                'detalle' => 'Servicio finalizado exitosamente. El equipo queda disponible.',
                'color' => '#16a34a',
            ];
        }

        if ($record->fecha_inicio || in_array($record->estado, ['En proceso', 'Completado', 'Terminado'])) {
            $events[] = [
                'fecha' => ($record->fecha_inicio ? $record->fecha_inicio->format('d/m/Y') : ($record->updated_at ? $record->updated_at->format('d/m/Y') : date('d/m/Y'))),
                'usuario' => 'Administrador',
                'accion' => 'Mantenimiento autorizado',
                'detalle' => 'Autorizado por el Administrador — en reparación.',
                'color' => '#d97706',
            ];
        }

        $events[] = [
            'fecha' => ($record->fecha_solicitud ? $record->fecha_solicitud->format('d/m/Y') : ($record->created_at ? $record->created_at->format('d/m/Y') : date('d/m/Y'))),
            'usuario' => $record->nombre_tecnico ?? 'Técnico',
            'accion' => 'Reporte de falla',
            'detalle' => 'Reporte #' . str_pad($record->id_mantenimiento ?? 1, 4, '0', STR_PAD_LEFT) . ' creado' . ($record->descripcion_falla ? ' (' . \Illuminate\Support\Str::limit($record->descripcion_falla, 50) . ')' : ''),
            'color' => '#2563eb',
        ];
    }
@endphp

<div class="w-full">
    <div style="margin-bottom: 14px; background: #f8fafc; border: 1px solid #e2e8f0; padding: 10px 14px; border-radius: 8px; font-size: 13px; color: #64748b; display: flex; align-items: center; gap: 8px;">
        <span>🕘</span>
        <span>Historial de servicio: autorizaciones, inicio de labores y finalización del reporte.</span>
    </div>

    <div style="display: flex; flex-direction: column; gap: 14px; padding-left: 6px;">
        @forelse($events as $ev)
            <div style="border-left: 3px solid {{ $ev['color'] }}; padding-left: 12px; position: relative;">
                <span style="font-size: 12px; color: #64748b; display: block; margin-bottom: 2px;">
                    {{ $ev['fecha'] }} · {{ $ev['usuario'] }}
                </span>
                <b style="font-size: 14px; color: #1e293b; display: block; margin-bottom: 2px;">
                    {{ $ev['accion'] }}
                </b>
                <div style="font-size: 13px; color: #475569;">
                    {{ $ev['detalle'] }}
                </div>
            </div>
        @empty
            <div style="font-size: 13px; color: #94a3b8; font-style: italic;">
                No hay eventos registrados en el historial.
            </div>
        @endforelse
    </div>
</div>

