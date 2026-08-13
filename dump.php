<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$user = App\Models\User::first();
Auth::login($user);

$request = Illuminate\Http\Request::create('/admin', 'GET');
$response = $kernel->handle($request);
file_put_contents('dashboard.html', $response->getContent());
