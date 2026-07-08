<?php

namespace App\Providers;

use App\Models\Inventario;
use App\Models\Material;
use App\Models\Mantenimiento;
use App\Models\Solicitud;
use App\Models\Proveedor;
use App\Observers\InventarioObserver;
use App\Observers\MaterialObserver;
use App\Observers\MantenimientoObserver;
use App\Observers\SolicitudObserver;
use App\Observers\ProveedorObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(\Filament\Http\Responses\Auth\Contracts\LogoutResponse::class, function () {
            return new class implements \Filament\Http\Responses\Auth\Contracts\LogoutResponse {
                public function toResponse($request): \Illuminate\Http\RedirectResponse
                {
                    return redirect()->to('/login');
                }
            };
        });
    }

    public function boot(): void
    {
        Inventario::observe(InventarioObserver::class);
        Material::observe(MaterialObserver::class);
        Mantenimiento::observe(MantenimientoObserver::class);
        Solicitud::observe(SolicitudObserver::class);
        Proveedor::observe(ProveedorObserver::class);
    }
}