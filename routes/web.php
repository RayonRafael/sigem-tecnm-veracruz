<?php

use App\Http\Controllers\SigemPdfController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('landing');
});

Route::get('/sigem/pdf/baja/{inventario}', [SigemPdfController::class, 'formatoBaja'])
    ->name('sigem.pdf.baja');
