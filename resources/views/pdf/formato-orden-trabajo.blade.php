@extends('pdf.layouts.formato-base')

@section('titulo', 'Solicitud-Orden de Trabajo de Mantenimientos y/o Servicios')

@section('estilos')
    <style>
        @page {
            margin: 14mm 16mm;
        }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 10.5px;
            color: #000000;
            line-height: 1.3;
        }

        .iso-header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .iso-header-table td {
            border: 1px solid #000000;
            padding: 5px 8px;
            vertical-align: middle;
        }

        .header-title {
            text-align: center;
            font-size: 11px;
            font-weight: bold;
            line-height: 1.25;
        }

        .header-ref {
            text-align: center;
            font-size: 9.5px;
            font-weight: bold;
            line-height: 1.3;
        }

        .header-logo-cell {
            text-align: center;
            vertical-align: middle;
            padding: 6px;
        }

        .header-logo-cell img {
            width: 54px;
            height: auto;
            display: block;
            margin: 0 auto 4px auto;
        }

        .header-inst {
            font-size: 9px;
            font-weight: bold;
            line-height: 1.15;
        }

        .checkbox-wrapper {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 24px;
        }

        .checkbox-wrapper > tbody > tr > td.spacer {
            width: 58%;
            border: none;
            padding: 0;
        }

        .checkbox-wrapper > tbody > tr > td.box-container {
            width: 42%;
            border: none;
            padding: 0;
        }

        .checkbox-table {
            width: 100%;
            border-collapse: collapse;
        }

        .checkbox-table td {
            border: 1px solid #000000;
            padding: 3px 8px;
            font-size: 10px;
        }

        .checkbox-table td.mark-cell {
            width: 28px;
            text-align: center;
            font-weight: bold;
            padding: 3px 4px;
        }

        .field-box {
            border: 1px solid #000000;
            padding: 9px 10px;
            margin-bottom: 16px;
            font-size: 10.5px;
        }

        .field-label {
            font-weight: bold;
        }

        .solicitante-box {
            min-height: 22px;
        }

        .desc-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }

        .desc-table th,
        .desc-table td {
            border: 1px solid #000000;
            padding: 8px 10px;
            text-align: left;
        }

        .desc-table th {
            font-weight: bold;
            font-size: 10.5px;
        }

        .desc-body {
            height: 270px;
            vertical-align: top;
            font-size: 10.5px;
            line-height: 1.45;
        }

        .footer-note {
            font-size: 10px;
            margin-top: 6px;
        }
    </style>
@endsection

@section('encabezado')
    <!-- Encabezado oficial con referencia ISO 9001 / ISO 14001 + Logo ITV -->
    <table class="iso-header-table">
        <tr>
            <td style="width: 54%;">
                <div class="header-title">
                    Solicitud-Orden de Trabajo de Mantenimientos<br>
                    y/o Servicios
                </div>
            </td>
            <td style="width: 21%;">&nbsp;</td>
            <td class="header-logo-cell" style="width: 25%;" rowspan="2">
                @if(!empty($logoItv))
                    <img src="{{ $logoItv }}" alt="{{ $config['institucion']['nombre'] }}">
                @endif
                <div class="header-inst">{{ $config['institucion']['nombre'] }}</div>
            </td>
        </tr>
        <tr>
            <td>
                <div class="header-ref">
                    Docto No Controlado Hace Referencia a la norma {{ $config['normas']['iso_9001'] }}:<br>
                    {{ $config['normas']['referencia_9001'] }}<br>
                    Referencia a la Norma {{ $config['normas']['iso_14001'] }} {{ $config['normas']['referencia_14001'] }}
                </div>
            </td>
            <td>&nbsp;</td>
        </tr>
    </table>

    <!-- Tabla de checkboxes alineada a la derecha -->
    <table class="checkbox-wrapper">
        <tr>
            <td class="spacer">&nbsp;</td>
            <td class="box-container">
                <table class="checkbox-table">
                    <tr>
                        <td>{{ $config['recursos_materiales']['nombre'] }}</td>
                        <td class="mark-cell">&nbsp;</td>
                    </tr>
                    <tr>
                        <td>Mantenimiento de Equipo</td>
                        <td class="mark-cell">&nbsp;</td>
                    </tr>
                    <tr>
                        <td>{{ $config['centro_computo']['nombre'] }}</td>
                        <td class="mark-cell">X</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
@endsection

@section('contenido')
    <!-- Área Solicitante -->
    <div class="field-box">
        <span class="field-label">Área Solicitante:</span>&nbsp;&nbsp;&nbsp;{{ $departamento }}
    </div>

    <!-- Nombre y Firma Solicitante -->
    <div class="field-box solicitante-box">
        <span class="field-label">Nombre y Firma Solicitante:</span>&nbsp;&nbsp;&nbsp;{{ $solicitante }}
    </div>

    <!-- Fecha de Elaboración -->
    <div class="field-box">
        <span class="field-label">Fecha de Elaboración:</span>&nbsp;&nbsp;&nbsp;{{ $fecha }}
    </div>

    <!-- Descripción del servicio solicitado o falla a reparar -->
    <table class="desc-table">
        <tr>
            <th>Descripción del servicio solicitado o falla a reparar:</th>
        </tr>
        <tr>
            <td class="desc-body">{!! nl2br(e($descripcion_falla)) !!}</td>
        </tr>
    </table>

    <!-- Copia al pie -->
    <div class="footer-note">
        C.c.p. Área Solicitante.
    </div>
@endsection

