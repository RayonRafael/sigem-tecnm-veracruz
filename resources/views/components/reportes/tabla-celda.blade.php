@props([
    'header' => '',
    'valor' => '',
])

@php
    $raw = (string) $valor;
    $trimmed = trim($raw);
    $headerNorm = mb_strtolower(trim((string) $header), 'UTF-8');
    $tieneHtml = str_contains($raw, '<');

    $esNumerica = (bool) preg_match('/^(cantidad|total|propios|rentados|solicitudes|completadas|pendientes|porcentaje|%|días|dias|mín|min|máx|max|equipos|deptos|áreas|areas|usuarios|acciones|costo|año|frecuencia)/u', $headerNorm)
        || str_contains($headerNorm, 'cantidad')
        || str_contains($headerNorm, 'porcentaje')
        || str_contains($headerNorm, 'días');

    $esFechaHora = (bool) preg_match('/^(\d{2}\/\d{2}\/\d{4}|\d{2}:\d{2})$/', trim(strip_tags($raw)));
    $tdClass = trim(($esNumerica ? 'sigem-col-num ' : '') . ($esFechaHora ? 'sigem-td-nowrap' : ''));

    $html = null;
    if (! $tieneHtml && $trimmed !== '' && $trimmed !== '—' && $trimmed !== 'N/A') {
        if (in_array($headerNorm, ['estado', 'estado anterior', 'estado nuevo'], true)) {
            $pillCls = match (true) {
                str_contains($trimmed, 'Vencida'),
                in_array($trimmed, ['Dado de baja', 'Baja', 'Dañado', 'Rechazado', 'Cancelado'], true) => 'danger',
                str_contains($trimmed, 'Por vencer'),
                in_array($trimmed, ['En Mantenimiento', 'En mantenimiento', 'Pendiente', 'Pendiente Revision Admin', 'Solicitado', 'Solicitud de baja', 'Devuelto a Proveedor'], true) => 'warn',
                str_contains($trimmed, 'Vigente'),
                in_array($trimmed, ['Disponible', 'Completado', 'Completada', 'Autorizado', 'Autorizada', 'Entregado'], true) => 'ok',
                $trimmed === 'Reasignado' => 'reassign',
                in_array($trimmed, ['Asignado', 'Cambio de área'], true) => 'info',
                default => 'neutral',
            };
            $tieneEmoji = str_contains($trimmed, '🟢') || str_contains($trimmed, '🟡') || str_contains($trimmed, '🔴');
            $dot = $tieneEmoji ? '' : '<span class="sigem-dot"></span>';
            $html = '<span class="sigem-pill ' . $pillCls . '" style="white-space: nowrap;">' . $dot . e($trimmed) . '</span>';
        } elseif ($headerNorm === 'tipo propiedad' || ($headerNorm === 'tipo' && in_array($trimmed, ['Propio', 'Rentado'], true))) {
            $pillCls = $trimmed === 'Rentado' ? 'warn' : 'info';
            $html = '<span class="sigem-pill ' . $pillCls . '">' . e($trimmed) . '</span>';
        } elseif ($headerNorm === 'tipo servicio') {
            $pillCls = match ($trimmed) {
                'Preventivo' => 'ok',
                'Correctivo' => 'warn',
                'Mejora' => 'info',
                default => 'neutral',
            };
            $html = '<span class="sigem-pill ' . $pillCls . '">' . e($trimmed) . '</span>';
        } elseif ($headerNorm === 'tipo') {
            $html = '<span class="sigem-pill neutral">' . e($trimmed) . '</span>';
        } elseif (in_array($headerNorm, ['propios', 'rentados', 'completadas', 'pendientes', 'días vencida'], true) && is_numeric($trimmed)) {
            $num = (int) $trimmed;
            if ($num === 0) {
                $html = '<span class="sigem-num-zero">0</span>';
            } else {
                $pillCls = match ($headerNorm) {
                    'propios' => 'info',
                    'rentados', 'pendientes' => 'warn',
                    'completadas' => 'ok',
                    'días vencida' => 'danger',
                    default => 'neutral',
                };
                $html = '<span class="sigem-pill ' . $pillCls . '">' . $num . '</span>';
            }
        } elseif (in_array($headerNorm, ['código', 'codigo', 'folio'], true)) {
            $html = '<span class="sigem-td-folio">' . e($trimmed) . '</span>';
        }
    }
@endphp

<td class="{{ $tdClass }}">@if ($html !== null){!! $html !!}@else{!! $raw !!}@endif</td>
