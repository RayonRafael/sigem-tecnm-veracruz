@php
    $record = $getRecord();
    $events = [];

    if ($record) {
        if ($record->fecha_autorizacion || in_array($record->estado, ['Autorizado', 'Entregado', 'Completado'])) {
            $events[] = [
                'fecha' => ($record->fecha_autorizacion ? $record->fecha_autorizacion->format('d/m/Y') : ($record->updated_at ? $record->updated_at->format('d/m/Y') : date('d/m/Y'))),
                'usuario' => $record->autorizadoPor?->name ?? 'Administrador',
                'accion' => 'Solicitud autorizada',
                'detalle' => 'Autorizada por ' . ($record->autorizadoPor?->name ?? 'el Administrador'),
                'color' => '#16a34a',
            ];
        } elseif ($record->estado === 'Rechazado') {
            $events[] = [
                'fecha' => ($record->updated_at ? $record->updated_at->format('d/m/Y') : date('d/m/Y')),
                'usuario' => $record->autorizadoPor?->name ?? 'Administrador',
                'accion' => 'Solicitud rechazada',
                'detalle' => 'Revisada por el Administrador',
                'color' => '#dc2626',
            ];
        }

        $events[] = [
            'fecha' => ($record->fecha_solicitud ? $record->fecha_solicitud->format('d/m/Y') : ($record->created_at ? $record->created_at->format('d/m/Y') : date('d/m/Y'))),
            'usuario' => $record->usuario?->name ?? 'Alumno Servicio Social',
            'accion' => 'Solicitud enviada',
            'detalle' => 'Enviada por ' . ($record->usuario?->name ?? 'Alumno Servicio Social'),
            'color' => '#2563eb',
        ];
    }
@endphp

<div class="w-full">
    <div style="margin-bottom: 14px; background: #f8fafc; border: 1px solid #e2e8f0; padding: 10px 14px; border-radius: 8px; font-size: 13px; color: #64748b; display: flex; align-items: center; gap: 8px;">
        <span>🕘</span>
        <span>Historial de la solicitud: cambios de estado, revisiones y autorizaciones.</span>
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

