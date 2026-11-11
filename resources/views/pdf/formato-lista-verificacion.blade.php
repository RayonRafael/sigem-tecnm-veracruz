@extends('pdf.layouts.formato-base')

@section('titulo', 'Mantenimiento - Lista de Verificación')

@section('estilos')
        @page {
            margin: 12mm 12mm;
        }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 10.5px;
            color: #000000;
            line-height: 1.3;
        }

        .lv-header-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #000000;
            margin-bottom: 12px;
        }

        .lv-header-table td {
            border: none;
            padding: 6px 10px;
            vertical-align: middle;
        }

        .lv-logo-cell {
            width: 18%;
            text-align: left;
        }

        .lv-logo-cell img {
            width: 84px;
            height: auto;
            display: block;
        }

        .lv-titles-cell {
            width: 64%;
            text-align: center;
        }

        .lv-spacer-cell {
            width: 18%;
        }

        .header-institucion {
            font-size: 13px;
            font-weight: bold;
            letter-spacing: 0.2px;
            margin-bottom: 4px;
        }

        .header-depto {
            font-size: 11px;
            font-weight: bold;
            margin-bottom: 4px;
        }

        .header-titulo {
            font-size: 11px;
            font-weight: bold;
        }

        .tabla-responsables {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 18px;
        }

        .tabla-responsables td {
            border: 1px solid #000000;
            padding: 7px 9px;
            width: 50%;
            height: 22px;
            vertical-align: top;
            font-size: 10.5px;
        }

        .fecha-line {
            text-align: right;
            margin-bottom: 6px;
            font-weight: bold;
            font-size: 10.5px;
        }

        .tabla-verificacion {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }

        .tabla-verificacion th {
            border: 1px solid #000000;
            padding: 5px 8px;
            font-size: 10.5px;
            font-weight: bold;
            text-align: center;
            vertical-align: middle;
        }

        .tabla-verificacion tbody td {
            border: 1px solid #000000;
            padding: 8px;
            height: 475px;
            vertical-align: top;
            font-size: 10.5px;
            line-height: 1.4;
        }

        .col-espacio {
            width: 26%;
            text-align: left;
        }

        .col-hallazgo {
            width: 58%;
            text-align: left;
        }

        .col-atendido {
            width: 16%;
        }

        .col-si {
            width: 8%;
            text-align: center !important;
        }

        .col-no {
            width: 8%;
            text-align: center !important;
        }

        .titulo-firmas {
            margin-top: 4px;
            margin-bottom: 4px;
            font-size: 10.5px;
        }

        .tabla-firmas {
            width: 100%;
            border-collapse: collapse;
        }

        .tabla-firmas td {
            border: 1px solid #000000;
            padding: 6px 9px;
            width: 50%;
            height: 28px;
            vertical-align: top;
            font-size: 10.5px;
        }
@endsection

@section('encabezado')
    <!-- Encabezado oficial: Logo ITV a la izquierda y títulos centrados -->
    <table class="lv-header-table">
        <tr>
            <td class="lv-logo-cell">
                @if(!empty($logoItv))
                    <img src="{{ $logoItv }}" alt="{{ $config['institucion']['nombre'] ?? 'Instituto Tecnológico de Veracruz' }}">
                @endif
            </td>
            <td class="lv-titles-cell">
                <div class="header-institucion">{{ mb_strtoupper($config['institucion']['nombre'] ?? 'INSTITUTO TECNOLÓGICO DE VERACRUZ', 'UTF-8') }}</div>
                <div class="header-depto">MANTENIMIENTO</div>
                <div class="header-titulo">LISTA DE VERIFICACIÓN</div>
            </td>
            <td class="lv-spacer-cell">&nbsp;</td>
        </tr>
    </table>

    <!-- Tabla de responsables -->
    <table class="tabla-responsables">
        <tr>
            <td>{{ $config['centro_computo']['nombre'] ?? 'Centro de Cómputo' }}</td>
            <td>{{ $jefeCentroComputo }}</td>
        </tr>
        <tr>
            <td>{{ $subdireccion }}</td>
            <td>{{ $jefeSubdireccion }}</td>
        </tr>
    </table>

    <!-- Fecha -->
    <div class="fecha-line">Fecha: {{ $fecha }}</div>
@endsection

@section('contenido')
    <!-- Tabla principal de verificación -->
    <table class="tabla-verificacion">
        <thead>
            <tr>
                <th class="col-espacio" rowspan="2">Espacio Revisado</th>
                <th class="col-hallazgo" rowspan="2">Hallazgo</th>
                <th class="col-atendido" colspan="2">Atendido</th>
            </tr>
            <tr>
                <th class="col-si">SÍ</th>
                <th class="col-no">NO</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="col-espacio">{{ $espacioRevisado }}</td>
                <td class="col-hallazgo">{!! nl2br(e($hallazgo)) !!}</td>
                <td class="col-si">{{ $atendidoSi ? 'X' : '' }}</td>
                <td class="col-no">{{ $atendidoSi ? '' : 'X' }}</td>
            </tr>
        </tbody>
    </table>

    <!-- Título Firmas -->
    <div class="titulo-firmas">Realizó</div>

    <!-- Tabla de firmas -->
    <table class="tabla-firmas">
        <tr>
            <td>{{ ($config['centro_computo']['nombre'] ?? 'Centro de Cómputo') . ' Realizó' }}</td>
            <td>{{ $nombreTecnico }}</td>
        </tr>
        <tr>
            <td>Vo.Bo. Recibió</td>
            <td>&nbsp;</td>
        </tr>
    </table>
@endsection
