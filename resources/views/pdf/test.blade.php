<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Test PDF SIGEM</title>
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 12px; }
        .header { text-align: center; margin-bottom: 20px; }
        .footer { text-align: center; margin-top: 20px; font-size: 10px; color: #666; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $config['institucion']['nombre'] }}</h1>
        <p>{{ $config['institucion']['tecnm'] }}</p>
        <hr>
    </div>

    <h2>Test de Generación PDF</h2>
    <p>Este es un PDF de prueba para verificar la infraestructura.</p>
    <p>Fecha: {{ now()->format('d/m/Y') }}</p>

    <div class="footer">
        <hr>
        <p>{{ $config['institucion']['direccion'] }}</p>
        <p>Tel: {{ $config['institucion']['telefono'] }} | {{ $config['institucion']['web'] }}</p>
    </div>
</body>
</html>

