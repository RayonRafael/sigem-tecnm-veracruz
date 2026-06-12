<?php

namespace App\Providers;

use App\Models\Mantenimiento;
use App\Models\Solicitud;
use App\Observers\MantenimientoObserver;
use App\Observers\SolicitudObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Aquí "enchufamos" los espías a sus modelos
        Mantenimiento::observe(MantenimientoObserver::class);
        Solicitud::observe(SolicitudObserver::class);
    }
}