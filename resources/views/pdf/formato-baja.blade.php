<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Trámite de Baja de Mobiliario y Equipo</title>
    <style>
        @page {
            margin: 10mm 13mm;
        }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 9.5px;
            color: #000000;
            margin: 0;
            padding: 0;
            line-height: 1.25;
        }

        .baja-pagina {
            width: 100%;
        }

        .page-break {
            page-break-after: always;
        }

        /* Encabezado institucional */
        .header-logos {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 6px;
        }

        .header-logos td {
            border: none;
            padding: 0;
            vertical-align: middle;
        }

        .logo-col-izq {
            width: 24%;
            text-align: left;
        }

        .logo-col-centro {
            width: 52%;
            text-align: center;
        }

        .logo-col-der {
            width: 24%;
            text-align: right;
        }

        .logo-sep-hdr {
            height: 38px;
            width: auto;
        }

        .logo-itv-hdr {
            height: 44px;
            width: auto;
            vertical-align: middle;
        }

        .logo-tecnm-hdr {
            height: 30px;
            width: auto;
            margin-left: 6px;
            vertical-align: middle;
        }

        .header-institucion {
            font-size: 11px;
            font-weight: bold;
            letter-spacing: 0.2px;
        }

        .header-tecnm {
            font-size: 10.5px;
            margin-top: 2px;
        }

        .header-deptos {
            text-align: center;
            font-size: 9.5px;
            line-height: 1.45;
            margin-top: 4px;
            margin-bottom: 4px;
        }

        .header-titulo {
            text-align: center;
            font-size: 11px;
            font-weight: bold;
            margin-bottom: 10px;
        }

        /* Marco unificado del formato */
        .baja-grid {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            border: 1px solid #000000;
        }

        .baja-grid td {
            border: 1px solid #000000;
            padding: 5px 8px;
            vertical-align: top;
        }

        .baja-grid td.no-padding {
            padding: 0;
        }

        .campo-label {
            font-size: 8.5px;
            color: #111827;
            margin-bottom: 3px;
        }

        .campo-valor {
            font-size: 10px;
            font-weight: bold;
            color: #000000;
        }

        .cell-center {
            text-align: center;
            vertical-align: middle !important;
        }

        .cell-area {
            height: 48px;
            text-align: center;
            vertical-align: middle !important;
        }

        .cell-desc-bien {
            height: 46px;
        }

        .cell-desc-bien .campo-label {
            text-align: center;
            margin-bottom: 6px;
        }

        /* Tabla interna Marca / Modelo / Serie */
        .subtabla-3 {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .subtabla-3 td {
            border: none;
            border-right: 1px solid #000000;
            padding: 5px 8px;
            width: 33.3333%;
            text-align: center;
            vertical-align: middle;
            height: 36px;
        }

        .subtabla-3 td:last-child {
            border-right: none;
        }

        /* Etiqueta y grilla de 28 celdas */
        .inv-label-cell {
            text-align: center;
            padding: 4px 8px !important;
            font-size: 9px;
        }

        .tabla-inventario {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .tabla-inventario td {
            border: none;
            border-right: 1px solid #000000;
            padding: 4px 0;
            text-align: center;
            vertical-align: middle;
            font-size: 10px;
            font-weight: bold;
            height: 18px;
        }

        .tabla-inventario td:last-child {
            border-right: none;
        }

        /* Descripción del daño */
        .cell-dano {
            height: 44px;
            vertical-align: middle !important;
            font-size: 9.5px;
        }

        /* Causa de la baja */
        .causa-header {
            text-align: center;
            font-size: 9px;
            padding: 4px 8px !important;
        }

        .causa-rows {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .causa-rows td {
            border: none;
            border-bottom: 1px solid #000000;
            padding: 3px 8px;
            font-size: 9px;
            line-height: 1.25;
            vertical-align: middle;
        }

        .causa-rows tr:last-child td {
            border-bottom: none;
        }

        .causa-rows td.causa-check {
            width: 6%;
            border-right: 1px solid #000000;
            text-align: center;
            font-size: 10px;
            font-weight: bold;
            padding: 3px 2px;
        }

        .causa-rows td.causa-texto {
            width: 94%;
        }

        /* Posible área de utilización */
        .cell-area-util {
            height: 54px;
            text-align: center;
        }

        .cell-area-util .campo-label {
            margin-bottom: 8px;
        }

        /* Responsable */
        .cell-responsable {
            height: 58px;
            text-align: center;
            vertical-align: bottom !important;
            padding-bottom: 6px !important;
        }

        .responsable-nombre {
            font-size: 10px;
            font-weight: bold;
            margin-bottom: 3px;
        }

        .responsable-label {
            font-size: 9px;
        }

        /* Observación dentro del marco */
        .cell-observacion {
            text-align: center;
            font-size: 9.5px;
            font-weight: bold;
            padding: 4px 8px !important;
        }

        /* Footer institucional */
        .footer-institucional {
            margin-top: 12px;
            text-align: center;
        }

        .footer-institucional img {
            height: 36px;
            width: auto;
            margin: 0 12px;
            vertical-align: middle;
        }
    </style>
</head>
<body>

@php
    $registrosArray = isset($registros)
        ? collect($registros)
        : collect([$inventario]);
@endphp

@foreach($registrosArray as $idx => $inv)
    @php
        $causas = \App\Support\SigemBajaFormatHelper::mapearCausas($inv->motivo_baja);

        $codigoFuente = mb_substr((string) ($inv->codigo ?? ''), 0, 28, 'UTF-8');
        $codigoChars = $codigoFuente === ''
            ? []
            : mb_str_split($codigoFuente, 1, 'UTF-8');
        $codigoChars = array_pad($codigoChars, 28, '');

        $folio = is_array($inv->especificaciones) ? ($inv->especificaciones['folio_sibsep'] ?? '') : '';
        $areaUtil = is_array($inv->especificaciones) ? ($inv->especificaciones['posible_area_utilizacion'] ?? '') : '';
        $descDano = $inv->observaciones_baja ?: '—';

        $responsable = $inv->departamento?->encargado
            ?: ($config['centro_computo']['jefe'] ?? '');

        $nombreBien = trim(collect([
            $inv->material?->nombre,
            filled($inv->especificacion_texto) && $inv->especificacion_texto !== $inv->material?->nombre
                ? $inv->especificacion_texto
                : null,
        ])->filter()->implode(' ')) ?: '—';

        $marcaBien = $inv->material?->marca?->nombre
            ?? (is_array($inv->especificaciones) ? ($inv->especificaciones['marca'] ?? null) : null)
            ?? '—';

        $fechaBaja = $inv->fecha_baja
            ? \Carbon\Carbon::parse($inv->fecha_baja)->format('d/m/Y')
            : now()->format('d/m/Y');
    @endphp

    <div class="baja-pagina{{ ! $loop->last ? ' page-break' : '' }}">
        <!-- ENCABEZADO -->
        <table class="header-logos">
            <tr>
                <td class="logo-col-izq">
                    @if(!empty($logoSep))
                        <img class="logo-sep-hdr" src="{{ $logoSep }}" alt="SEP">
                    @endif
                </td>
                <td class="logo-col-centro">
                    <div class="header-institucion">
                        {{ mb_strtoupper($config['institucion']['nombre'] ?? 'Instituto Tecnológico de Veracruz', 'UTF-8') }}
                    </div>
                    <div class="header-tecnm">
                        {{ mb_strtoupper($config['institucion']['tecnm'] ?? 'Tecnológico Nacional de México', 'UTF-8') }}
                    </div>
                </td>
                <td class="logo-col-der">
                    @if(!empty($logoItv))
                        <img class="logo-itv-hdr" src="{{ $logoItv }}" alt="ITV">
                    @endif
                    @if(!empty($logoTecnm))
                        <img class="logo-tecnm-hdr" src="{{ $logoTecnm }}" alt="TecNM">
                    @endif
                </td>
            </tr>
        </table>

        <div class="header-deptos">
            {{ mb_strtoupper($config['almacen']['subdireccion'] ?? 'Subdirección de Servicios Administrativos', 'UTF-8') }}<br>
            {{ mb_strtoupper($config['almacen']['departamento'] ?? 'Depto. de Recursos Materiales y Servicios', 'UTF-8') }}<br>
            {{ mb_strtoupper($config['almacen']['oficina'] ?? 'Oficina de Almacén e Inventario', 'UTF-8') }}
        </div>
        <div class="header-titulo">
            TRAMITE DE BAJA
        </div>

        <!-- TABLA PRINCIPAL -->
        <table class="baja-grid">
            <!-- ÁREA SOLICITANTE / FECHA / FOLIO SIBESEP -->
            <tr>
                <td class="no-padding" colspan="3">
                    <table class="subtabla-3">
                        <tr>
                            <td style="width: 44%;">
                                <div class="campo-label">ÁREA SOLICITANTE</div>
                                <div class="campo-valor">{{ mb_strtoupper($inv->departamento?->nombre ?? '—', 'UTF-8') }}</div>
                            </td>
                            <td style="width: 22%;">
                                <div class="campo-valor">{{ $fechaBaja }}</div>
                            </td>
                            <td style="width: 34%;">
                                <div class="campo-label">FOLIO SIBESEP</div>
                                <div class="campo-valor">{{ $folio }}</div>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>

            <!-- DESCRIPCIÓN ESPECIFICA DEL BIEN -->
            <tr>
                <td class="cell-desc-bien" colspan="3">
                    <div class="campo-label">DESCRIPCIÓN ESPECIFICA DEL BIEN</div>
                    <div class="campo-valor">{{ $nombreBien }}</div>
                </td>
            </tr>

            <!-- MARCA / MODELO / N°. SERIE -->
            <tr>
                <td class="no-padding" colspan="3">
                    <table class="subtabla-3">
                        <tr>
                            <td>
                                <div class="campo-label">MARCA</div>
                                <div class="campo-valor">{{ $marcaBien }}</div>
                            </td>
                            <td>
                                <div class="campo-label">MODELO</div>
                                <div class="campo-valor">{{ $inv->modelo ?: '—' }}</div>
                            </td>
                            <td>
                                <div class="campo-label">N°. SERIE</div>
                                <div class="campo-valor">{{ $inv->num_serie ?: 'S/N' }}</div>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>

            <!-- N°. DE INVENTARIO -->
            <tr>
                <td class="inv-label-cell" colspan="3">N°. DE INVENTARIO</td>
            </tr>
            <tr>
                <td class="no-padding" colspan="3">
                    <table class="tabla-inventario">
                        <tr>
                            @foreach($codigoChars as $char)
                                <td>{{ trim($char) !== '' ? $char : "\u{00A0}" }}</td>
                            @endforeach
                        </tr>
                    </table>
                </td>
            </tr>

            <!-- DESCRIPCIÓN DEL DAÑO -->
            <tr>
                <td class="cell-dano" colspan="3">{!! nl2br(e($descDano)) !!}</td>
            </tr>

            <!-- CAUSA DE LA BAJA -->
            <tr>
                <td class="causa-header" colspan="3">CAUSA DE LA BAJA</td>
            </tr>
            <tr>
                <td class="no-padding" colspan="3">
                    <table class="causa-rows">
                        @foreach($causas as $causa)
                            <tr>
                                <td class="causa-check">{{ $causa['marcada'] ? 'X' : "\u{00A0}" }}</td>
                                <td class="causa-texto">{{ $causa['label'] }}</td>
                            </tr>
                        @endforeach
                    </table>
                </td>
            </tr>

            <!-- POSIBLE ÁREA DE UTILIZACIÓN -->
            <tr>
                <td class="cell-area-util" colspan="3">
                    <div class="campo-label">POSIBLE ÁREA DE UTILIZACIÓN</div>
                    <div class="campo-valor">{{ $areaUtil }}</div>
                </td>
            </tr>

            <!-- RESPONSABLE DEL ÁREA -->
            <tr>
                <td class="cell-responsable" colspan="3">
                    <div class="responsable-nombre">{{ $responsable }}</div>
                    <div class="responsable-label">NOMBRE Y FIRMA DEL RESPONSABLE DEL ÁREA</div>
                </td>
            </tr>

            <!-- OBSERVACIÓN -->
            <tr>
                <td class="cell-observacion" colspan="3">
                    OBSERVACIÓN: LLENAR UN FORMATO POR CADA BIEN
                </td>
            </tr>
        </table>

        <!-- FOOTER -->
        <div class="footer-institucional">
            @if(!empty($logoItv))
                <img src="{{ $logoItv }}" alt="ITV">
            @endif
            @if(!empty($logoSep))
                <img src="{{ $logoSep }}" alt="SEP">
            @endif
            @if(!empty($logoIso14001))
                <img src="{{ $logoIso14001 }}" alt="ISO 14001">
            @endif
            @if(!empty($logoIso9001))
                <img src="{{ $logoIso9001 }}" alt="ISO 9001">
            @endif
        </div>
    </div>
@endforeach

</body>
</html>
