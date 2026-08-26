<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('landing');
});

use App\Http\Controllers\Auth\LoginController;

Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login'])->name('login.submit');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

Route::middleware(['auth'])->group(function () {
    Route::post('/admin/mantenimientos/{id}/autorizar', function ($id) {
        $mant = \App\Models\Mantenimiento::findOrFail($id);
        $mant->update(['estado' => 'En proceso']);
        return back()->with('success', 'Mantenimiento autorizado correctamente.');
    })->name('mantenimientos.autorizar');

    Route::post('/admin/mantenimientos/{id}/completar', function ($id) {
        $mant = \App\Models\Mantenimiento::findOrFail($id);
        $mant->update(['estado' => 'Completado', 'fecha_fin' => now()]);
        return back()->with('success', 'Mantenimiento completado correctamente.');
    })->name('mantenimientos.completar');
});