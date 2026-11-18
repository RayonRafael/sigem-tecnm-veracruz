<?php

use App\Http\Controllers\SigemPdfController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('landing');
});

Route::get('/sigem/pdf/baja/{inventario}', [SigemPdfController::class, 'formatoBaja'])
    ->name('sigem.pdf.baja');

Route::get('/sigem/pdf/cambio-area/{inventario}', [SigemPdfController::class, 'cambioArea'])
    ->middleware('auth')
    ->name('pdf.cambio.area');

Route::get('/sigem/pdf/cambio-area-sigem/{inventario}', [SigemPdfController::class, 'cambioArea'])
    ->middleware('auth')
    ->name('sigem.pdf.cambio-area');
