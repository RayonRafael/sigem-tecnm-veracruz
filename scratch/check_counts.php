<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo json_encode([
    'solicitudes' => App\Models\Solicitud::count(),
    'mantenimientos' => App\Models\Mantenimiento::count(),
    'users' => App\Models\User::count(),
    'inventarios' => App\Models\Inventario::count(),
], JSON_PRETTY_PRINT);

