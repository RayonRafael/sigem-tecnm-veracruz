<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>@yield('titulo', 'Formato Institucional SIGEM')</title>
    <style>
        @page {
            margin-top: {{ $config['pdf']['margen_top'] ?? '15mm' }};
            margin-bottom: {{ $config['pdf']['margen_bottom'] ?? '15mm' }};
            margin-left: {{ $config['pdf']['margen_left'] ?? '15mm' }};
            margin-right: {{ $config['pdf']['margen_right'] ?? '15mm' }};
        }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 11px;
            color: #111827;
            margin: 0;
            padding: 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        .formato-encabezado {
            width: 100%;
            margin-bottom: 12px;
        }

        .formato-contenido {
            width: 100%;
        }

        .formato-footer {
            width: 100%;
            margin-top: 16px;
            font-size: 9px;
            color: #4b5563;
        }

        .page-break {
            page-break-after: always;
        }

        @yield('estilos')
    </style>
</head>
<body>
    {{-- Espacio para encabezado institucional (se define por cada formato específico) --}}
    @hasSection('encabezado')
        <div class="formato-encabezado">
            @yield('encabezado')
        </div>
    @endif

    {{-- Contenido principal del formato --}}
    <div class="formato-contenido">
        @yield('contenido')
    </div>

    {{-- Espacio para footer institucional (se define por cada formato específico) --}}
    @hasSection('footer')
        <div class="formato-footer">
            @yield('footer')
        </div>
    @endif
</body>
</html>

