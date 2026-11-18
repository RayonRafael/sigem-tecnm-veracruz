@extends('pdf.layouts.formato-base')

@section('titulo', 'Formato de Cambio de Área — SIGEM')

@section('estilos')
    @page {
        margin: 12mm 16mm 14mm 16mm;
    }

    body {
        font-family: 'DejaVu Sans', sans-serif;
        font-size: 9.5pt;
        color: #000000;
        line-height: 1.35;
    }

    .header-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 8px;
    }

    .header-table td {
        border: none;
        padding: 0;
        vertical-align: top;
    }

    .header-left {
        width: 58%;
        text-align: left;
    }

    .header-left img {
        height: 48px;
        max-width: 100%;
    }

    .header-right {
        width: 42%;
        text-align: right;
    }

    .flag-img {
        height: 58px;
        margin-bottom: 6px;
    }

    .inst-text {
        font-size: 8.5pt;
        line-height: 1.25;
        color: #111827;
        margin-bottom: 8px;
    }

    .inst-title {
        font-weight: bold;
    }

    .fecha-row {
        font-size: 9.5pt;
        text-align: right;
        margin-top: 4px;
        margin-bottom: 12px;
    }

    .fecha-highlight {
        background-color: #000000;
        color: #ffffff;
        padding: 2px 6px;
        font-weight: normal;
    }

    .destinatario-block {
        margin-top: 8px;
        margin-bottom: 12px;
        line-height: 1.35;
        font-size: 9.5pt;
        font-weight: bold;
    }

    .presente {
        margin-top: 2px;
    }

    .parrafo-intro {
        text-align: justify;
        font-size: 9.5pt;
        line-height: 1.45;
        margin-bottom: 10px;
    }

    .origen-destino-box {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 12px;
        font-size: 8.5pt;
    }

    .origen-destino-box td {
        border: 1px solid #d1d5db;
        padding: 5px 8px;
        width: 50%;
        vertical-align: top;
    }

    .od-label {
        font-weight: bold;
        color: #111827;
    }

    .equipos-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 8px;
        margin-bottom: 16px;
        font-size: 9pt;
    }

    .equipos-table th {
        border: 1px solid #000000;
        background-color: #b3b3b3;
        padding: 5px 6px;
        text-align: left;
        font-weight: bold;
        color: #000000;
    }

    .equipos-table td {
        border: 1px solid #000000;
        padding: 6px 6px;
        vertical-align: top;
    }

    .col-cant {
        width: 10%;
    }

    .col-desc {
        width: 34%;
    }

    .col-marca {
        width: 12%;
    }

    .col-modelo {
        width: 18%;
    }

    .col-serie {
        width: 26%;
    }

    .parrafo-cierre {
        text-align: left;
        font-size: 9pt;
        line-height: 1.4;
        margin-top: 14px;
        margin-bottom: 24px;
    }

    .firmas-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 18px;
        margin-bottom: 20px;
    }

    .firmas-table td {
        border: none;
        width: 50%;
        text-align: center;
        vertical-align: top;
        padding: 0 10px;
    }

    .firma-header {
        font-size: 9.5pt;
        font-weight: normal;
        margin-bottom: 42px;
    }

    .firma-nombre {
        font-size: 9.5pt;
        font-weight: bold;
        line-height: 1.3;
    }

    .firma-cargo {
        font-size: 9pt;
        font-weight: bold;
        line-height: 1.3;
        margin-top: 2px;
    }

    .footer-container {
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        width: 100%;
    }

    .footer-table {
        width: 100%;
        border-collapse: collapse;
    }

    .footer-table td {
        border: none;
        padding: 0;
        vertical-align: bottom;
    }

    .footer-left {
        width: 28%;
        text-align: left;
    }

    .footer-left img {
        height: 52px;
        max-width: 100%;
    }

    .footer-right {
        width: 72%;
        text-align: left;
    }

    .footer-certs {
        text-align: right;
        margin-bottom: 3px;
    }

    .footer-certs img {
        height: 34px;
        max-width: 100%;
    }

    .footer-bar {
        border-top: 3px solid #9d2449;
        padding-top: 4px;
        font-size: 7.5pt;
        line-height: 1.35;
        color: #111827;
    }
@endsection

@section('encabezado')
    <table class="header-table">
        <tr>
            <td class="header-left">
                @if(!empty($logoEducacion))
                    <img src="{{ $logoEducacion }}" alt="Educación · TecNM">
                @elseif(!empty($logoSep) && !empty($logoTecnm))
                    <img src="{{ $logoSep }}" alt="SEP" style="height: 38px;">
                    <img src="{{ $logoTecnm }}" alt="TecNM" style="height: 36px; margin-left: 8px;">
                @endif
            </td>
            <td class="header-right">
                @if(!empty($logoBandera))
                    <div><img class="flag-img" src="{{ $logoBandera }}" alt="Bandera"></div>
                @endif
                <div class="inst-text">
                    <div class="inst-title">{{ $config['institucion']['nombre'] ?? 'Instituto Tecnológico de Veracruz' }}</div>
                    <div>{{ $config['almacen']['subdireccion'] ?? 'Subdirección de Servicios Administrativos' }}</div>
                    <div>Departamento de {{ $config['centro_computo']['nombre'] ?? 'Centro de Cómputo' }}</div>
                </div>
                <div class="fecha-row">
                    H. Veracruz, Ver., <span class="fecha-highlight">{{ $fecha }}</span>
                </div>
            </td>
        </tr>
    </table>
@endsection

@section('contenido')
    <!-- Destinatario (Jefe del departamento de destino) -->
    <div class="destinatario-block">
        <div>{{ $destinatarioNombre }}</div>
        <div>{{ $destinatarioCargo }}</div>
        @if(!empty($destinatarioDepto) && !str_contains($destinatarioCargo, $destinatarioDepto))
            <div>{{ $destinatarioDepto }}</div>
        @endif
        <div class="presente">PRESENTE</div>
    </div>

    <!-- Asunto e introducción oficial -->
    <p class="parrafo-intro">
        @if(!empty($motivo))
            Por este medio le envío un cordial saludo, el motivo principal del mismo es informarle que se realizará el cambio de área del siguiente equipo ({{ $motivo }}) para el departamento que honra dirigir. El equipo es un equipo de reutilización en buenas condiciones, siendo el siguiente el que se entrega:
        @else
            Por este medio le envío un cordial saludo, el motivo principal del mismo es informarle que se realizará el cambio de área del siguiente equipo de cómputo para el departamento que honra dirigir. El equipo es un equipo de reutilización en buenas condiciones, siendo el siguiente el que se entrega:
        @endif
    </p>

    <!-- Origen y Destino -->
    <table class="origen-destino-box">
        <tr>
            <td>
                <span class="od-label">Origen:</span> {{ $origenDepto ?? 'Centro de Cómputo' }}
                @if(!empty($origenArea) && $origenArea !== ($origenDepto ?? ''))
                    — {{ $origenArea }}
                @endif
            </td>
            <td>
                <span class="od-label">Destino:</span> {{ $destinoDepto ?? $destinatarioDepto }}
                @if(!empty($destinoArea) && $destinoArea !== ($destinoDepto ?? ''))
                    — {{ $destinoArea }}
                @endif
            </td>
        </tr>
    </table>

    <!-- Tabla de equipos -->
    <table class="equipos-table">
        <thead>
            <tr>
                <th class="col-cant">Cantidad</th>
                <th class="col-desc">Descripción</th>
                <th class="col-marca">Marca</th>
                <th class="col-modelo">Modelo</th>
                <th class="col-serie">Serie</th>
            </tr>
        </thead>
        <tbody>
            @forelse($equipos as $item)
                @php
                    $desc = trim(($item->material?->nombre ?? '') . ($item->especificacion_texto ? ' – ' . $item->especificacion_texto : ($item->material?->descripcion ? ' – ' . $item->material->descripcion : '')));
                    if ($desc === '') {
                        $desc = $item->subtipo ?? 'Equipo de cómputo';
                    }
                    $marca = $item->material?->marca?->nombre
                        ?? ($item->especificaciones['marca'] ?? null)
                        ?? ($item->especificaciones['cpu_marca'] ?? null)
                        ?? '—';
                    $modelo = $item->modelo
                        ?? $item->material?->modelo
                        ?? ($item->especificaciones['modelo'] ?? null)
                        ?? '—';
                    $serie = $item->num_serie ?: ($item->codigo ?: '—');
                @endphp
                <tr>
                    <td class="col-cant">1</td>
                    <td class="col-desc">{{ $desc }}</td>
                    <td class="col-marca">{{ $marca }}</td>
                    <td class="col-modelo">{{ $modelo }}</td>
                    <td class="col-serie">{{ $serie }}</td>
                </tr>
            @empty
                <tr>
                    <td class="col-cant">1</td>
                    <td class="col-desc">{{ $inventario->material?->nombre ?? 'Equipo' }}</td>
                    <td class="col-marca">{{ $inventario->material?->marca?->nombre ?? '—' }}</td>
                    <td class="col-modelo">{{ $inventario->modelo ?? '—' }}</td>
                    <td class="col-serie">{{ $inventario->num_serie ?: ($inventario->codigo ?: '—') }}</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <!-- Cierre oficial -->
    <p class="parrafo-cierre">
        Sin más por el momento quedo a sus más finas atenciones y al pendiente de cualquier duda o aclaración al respecto
    </p>

    <!-- Firmas: ENTREGA (origen) / RECIBE (destino) -->
    <table class="firmas-table">
        <tr>
            <td>
                <div class="firma-header">ENTREGA</div>
                <div class="firma-nombre">{{ $nombreEntrega }}</div>
                <div class="firma-cargo">
                    {{ $cargoEntrega }}<br>
                    DEPARTAMENTO DE {{ $deptoEntrega }}
                </div>
            </td>
            <td>
                <div class="firma-header">RECIBE</div>
                <div class="firma-nombre">{{ $destinatarioNombre }}</div>
                <div class="firma-cargo">
                    {{ $destinatarioCargo }}<br>
                    @if(!str_contains($destinatarioCargo, $destinatarioDepto))
                        {{ $destinatarioDepto }}
                    @endif
                </div>
            </td>
        </tr>
    </table>
@endsection

@section('footer')
    <div class="footer-container">
        <table class="footer-table">
            <tr>
                <td class="footer-left">
                    @if(!empty($logoMargaritaMaza))
                        <img src="{{ $logoMargaritaMaza }}" alt="2026 Margarita Maza">
                    @endif
                </td>
                <td class="footer-right">
                    <div class="footer-certs">
                        @if(!empty($logoCertificaciones))
                            <img src="{{ $logoCertificaciones }}" alt="Certificaciones Oficiales">
                        @elseif(!empty($logoItv))
                            <img src="{{ $logoItv }}" alt="ITV" style="height: 28px;">
                        @endif
                    </div>
                    <div class="footer-bar">
                        {{ $config['institucion']['direccion'] ?? 'Calz. Miguel Ángel de Quevedo 2779, Col. Formando Hogar. C.P. 91897 Veracruz, Ver.' }}<br>
                        <strong>Teléfono:</strong> {{ $config['institucion']['telefono'] ?? '(229)934 1500' }} &nbsp;&nbsp;
                        <strong>e-mail:</strong> {{ $config['institucion']['email'] ?? 'web_veracruz@tecnm.mx' }} &nbsp;&nbsp;
                        <strong>{{ $config['institucion']['web'] ?? 'www.veracruz.tecnm.mx' }}</strong>
                    </div>
                </td>
            </tr>
        </table>
    </div>
@endsection

