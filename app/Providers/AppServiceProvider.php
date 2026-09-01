<?php

namespace App\Providers;

use App\Models\Inventario;
use App\Models\Material;
use App\Models\Mantenimiento;
use App\Models\Solicitud;
use App\Models\Proveedor;
use App\Observers\InventarioObserver;
use App\Observers\MantenimientoObserver;
use App\Observers\SolicitudObserver;
use Illuminate\Support\Facades\URL;
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
        app()->setLocale('es');

        if (config('app.env') === 'production') {
            URL::forceScheme('https');
        }

        \Illuminate\Support\Facades\Gate::policy(\App\Models\User::class, \App\Policies\UserPolicy::class);
        \Illuminate\Support\Facades\Gate::policy(\App\Models\Inventario::class, \App\Policies\InventarioPolicy::class);
        \Illuminate\Support\Facades\Gate::policy(\App\Models\Solicitud::class, \App\Policies\SolicitudPolicy::class);
        \Illuminate\Support\Facades\Gate::policy(\App\Models\Mantenimiento::class, \App\Policies\MantenimientoPolicy::class);
        \Illuminate\Support\Facades\Gate::policy(\App\Models\Departamento::class, \App\Policies\DepartamentoPolicy::class);
        \Illuminate\Support\Facades\Gate::policy(\App\Models\Area::class, \App\Policies\AreaPolicy::class);
        \Illuminate\Support\Facades\Gate::policy(\App\Models\Material::class, \App\Policies\MaterialPolicy::class);
        \Illuminate\Support\Facades\Gate::policy(\App\Models\MarcaMaterial::class, \App\Policies\MarcaMaterialPolicy::class);
        \Illuminate\Support\Facades\Gate::policy(\App\Models\TipoMaterial::class, \App\Policies\TipoMaterialPolicy::class);
        \Illuminate\Support\Facades\Gate::policy(\App\Models\UnidadMedida::class, \App\Policies\UnidadMedidaPolicy::class);
        \Illuminate\Support\Facades\Gate::policy(\App\Models\Proveedor::class, \App\Policies\ProveedorPolicy::class);
        \Illuminate\Support\Facades\Gate::policy(\App\Models\Receptor::class, \App\Policies\ReceptorPolicy::class);

        Inventario::observe(InventarioObserver::class);
        Mantenimiento::observe(MantenimientoObserver::class);
        Solicitud::observe(SolicitudObserver::class);
    }
}