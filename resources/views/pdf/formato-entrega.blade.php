@extends('pdf.layouts.formato-base')

@section('titulo', 'Entrega de Equipo / Bien')

@section('estilos')
        @page {
            margin: 11mm 14mm;
        }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 10.5px;
            color: #000000;
            line-height: 1.3;
        }

        .formato-encabezado {
            margin-bottom: 10px;
        }

        .header-logos {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }

        .header-logos td {
            border: none;
            padding: 0;
            vertical-align: middle;
        }

        .logo-izq {
            width: 68%;
            text-align: left;
        }

        .logo-sep {
            height: 40px;
            width: auto;
            vertical-align: middle;
        }

        .logo-divider {
            display: inline-block;
            border-left: 1px solid #9ca3af;
            height: 32px;
            margin: 0 10px;
            vertical-align: middle;
        }

        .logo-tecnm {
            height: 38px;
            width: auto;
            vertical-align: middle;
        }

        .logo-der {
            width: 32%;
            text-align: right;
        }

        .logo-itv {
            height: 54px;
            width: auto;
        }

        .header-inst-block {
            text-align: right;
            margin-bottom: 12px;
            line-height: 1.35;
        }

        .header-institucion {
            font-size: 10.5px;
            font-weight: bold;
        }

        .header-subdireccion,
        .header-centro {
            font-size: 10px;
        }

        .lugar-fecha {
            text-align: right;
            font-size: 10.5px;
            margin-bottom: 4px;
        }

        .field-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 9px;
        }

        .field-table td {
            border: 1.5px solid #000000;
            padding: 6px 10px;
            vertical-align: top;
        }

        .field-box-sm td {
            height: 44px;
        }

        .field-box-md td {
            height: 52px;
        }

        .field-box-lg td {
            height: 64px;
        }

        .field-label {
            font-size: 10px;
            color: #111827;
            margin-bottom: 3px;
        }

        .field-value {
            font-size: 10.5px;
            font-weight: normal;
            color: #000000;
        }

        .fecha-hora-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }

        .fecha-hora-table td {
            border: 1.5px solid #000000;
            padding: 8px 10px;
            font-size: 10.5px;
        }

        .underline-val {
            display: inline-block;
            border-bottom: 1px solid #000000;
            padding: 0 22px 1px 18px;
            margin-left: 4px;
            margin-right: 18px;
        }

        .firmas-container {
            width: 100%;
            border-collapse: collapse;
            margin-top: 32px;
        }

        .firmas-container td {
            border: none;
            width: 50%;
            text-align: center;
            vertical-align: bottom;
            padding: 0 18px;
        }

        .firma-nombre {
            font-size: 10px;
            font-weight: normal;
            margin-bottom: 4px;
        }

        .firma-line {
            border-top: 1px solid #000000;
            padding-top: 4px;
            font-size: 10px;
        }

        .formato-footer {
            margin-top: 24px;
        }

        .footer-table {
            width: 100%;
            border-collapse: collapse;
            border-top: 1px solid #d1d5db;
            padding-top: 8px;
        }

        .footer-table td {
            border: none;
            padding-top: 8px;
            vertical-align: middle;
        }

        .footer-info {
            width: 62%;
            text-align: left;
            font-size: 8.5px;
            color: #374151;
            line-height: 1.35;
        }

        .footer-seals {
            width: 38%;
            text-align: right;
        }

        .footer-seals img {
            height: 30px;
            width: auto;
            margin-left: 6px;
            vertical-align: middle;
        }
@endsection

@section('encabezado')
    <!-- Logos institucionales SEP | TecNM e ITV -->
    <table class="header-logos">
        <tr>
            <td class="logo-izq">
                @if(!empty($logoSep))
                    <img class="logo-sep" src="{{ $logoSep }}" alt="SEP">
                @endif
                @if(!empty($logoSep) && !empty($logoTecnm))
                    <span class="logo-divider"></span>
                @endif
                @if(!empty($logoTecnm))
                    <img class="logo-tecnm" src="{{ $logoTecnm }}" alt="TecNM">
                @endif
            </td>
            <td class="logo-der">
                @if(!empty($logoItv))
                    <img class="logo-itv" src="{{ $logoItv }}" alt="ITV">
                @endif
            </td>
        </tr>
    </table>

    <!-- Institución y área emisora alineadas a la derecha -->
    <div class="header-inst-block">
        <div class="header-institucion">{{ $config['institucion']['nombre'] ?? 'Instituto Tecnológico de Veracruz' }}</div>
        <div class="header-subdireccion">Subdirección Administrativo</div>
        <div class="header-centro">{{ $config['centro_computo']['nombre'] ?? 'Centro de Cómputo' }}</div>
    </div>

    <!-- Lugar y fecha -->
    <div class="lugar-fecha">
        {{ $config['institucion']['ciudad'] ?? 'H. Veracruz, Veracruz' }}, {{ $fecha }}
    </div>
@endsection

@section('contenido')
    <!-- Bien que se entrega -->
    <table class="field-table field-box-sm">
        <tr>
            <td>
                <div class="field-label">Bien que se entrega:</div>
                <div class="field-value">{{ $bienEntrega }}</div>
            </td>
        </tr>
    </table>

    <!-- Unidad Orgánica -->
    <table class="field-table field-box-sm">
        <tr>
            <td>
                <div class="field-label">Unidad Orgánica:</div>
                <div class="field-value">{{ $unidadOrganica }}</div>
            </td>
        </tr>
    </table>

    <!-- Fecha y Hora -->
    <table class="fecha-hora-table">
        <tr>
            <td>
                Fecha: <span class="underline-val">{{ $fecha }}</span>
                Hora: <span class="underline-val">{{ $hora }}</span>
            </td>
        </tr>
    </table>

    <!-- Descripción del Equipo/Bien -->
    <table class="field-table field-box-lg">
        <tr>
            <td>
                <div class="field-label">Descripción del Equipo/Bien:</div>
                <div class="field-value">{!! nl2br(e($descripcion)) !!}</div>
            </td>
        </tr>
    </table>

    <!-- Marca/modelo/Serie -->
    <table class="field-table field-box-lg">
        <tr>
            <td>
                <div class="field-label">Marca/modelo/Serie:</div>
                <div class="field-value">{{ $marcaModeloSerie }}</div>
            </td>
        </tr>
    </table>

    <!-- Observaciones -->
    <table class="field-table field-box-md">
        <tr>
            <td>
                <div class="field-label">Observaciones:</div>
                <div class="field-value">{!! nl2br(e($observaciones)) !!}</div>
            </td>
        </tr>
    </table>

    <!-- Firmas -->
    <table class="firmas-container">
        <tr>
            <td>
                <div class="firma-nombre">{{ $nombreEntrega }}</div>
                <div class="firma-line">
                    Nombre y firma de quien hace la entrega
                </div>
            </td>
            <td>
                <div class="firma-nombre">{{ $nombreRecepcion }}</div>
                <div class="firma-line">
                    Nombre y firma de quien hace la recepción
                </div>
            </td>
        </tr>
    </table>
@endsection

@section('footer')
    <!-- Footer institucional -->
    <table class="footer-table">
        <tr>
            <td class="footer-info">
                {{ $config['institucion']['direccion'] ?? '' }}<br>
                Tel. {{ $config['institucion']['telefono'] ?? '' }} &middot;
                e-mail: {{ $config['institucion']['email'] ?? '' }} &middot;
                {{ $config['institucion']['web'] ?? '' }}
            </td>
            <td class="footer-seals">
                @if(!empty($logoItv))
                    <img src="{{ $logoItv }}" alt="ITV">
                @endif
                @if(!empty($logoIso14001))
                    <img src="{{ $logoIso14001 }}" alt="ISO 14001">
                @endif
                @if(!empty($logoIso9001))
                    <img src="{{ $logoIso9001 }}" alt="ISO 9001">
                @endif
            </td>
        </tr>
    </table>
@endsection

