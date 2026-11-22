<?php

namespace App\Filament\Pages;

use App\Enums\RoleEnum;
use App\Filament\Concerns\ConAccionesCatalogoConfig;
use App\Services\ConfigBitacoraService;
use App\Services\ConfigCatalogosService;
use App\Services\ConfigPreferenciasService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class ConfiguracionPage extends Page
{
    use ConAccionesCatalogoConfig;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationGroup = 'Administración';

    protected static ?string $navigationLabel = 'Configuración';

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'configuracion';

    protected static ?string $title = '⚙️ Configuración y Catálogos';

    protected static string $view = 'filament.pages.configuracion';

    public string $tabActiva = 'catalogos';

    public string $catalogoActivo = 'departamentos';

    // Filtros de Catálogos
    public string $catalogoSearch = '';
    public string $filtroDepartamento = '';
    public string $filtroTipoMaterial = '';
    public string $filtroMarca = '';
    public string $filtroRol = '';
    public string $filtroEstadoUsuario = '';

    // Filtros de Bitácora
    public string $bitacoraSearch = '';
    public string $bitacoraUsuario = '';
    public string $bitacoraRol = '';
    public string $bitacoraAccion = '';

    // Preferencias
    public int $registrosPorHoja = 10;
    public string $otroValor = '';
    public string $mensajePreferencia = '';

    public function getTitle(): string
    {
        return '⚙️ Configuración y Catálogos';
    }

    public function getSubheading(): ?string
    {
        return null;
    }

    public function getBreadcrumbs(): array
    {
        return [];
    }

    public static function canAccess(): bool
    {
        return auth()->user() && (
            auth()->user()->hasRole(RoleEnum::ADMIN->value) ||
            auth()->user()->hasPermissionTo(RoleEnum::PERM_ACCESS_ADMIN)
        );
    }

    public function mount(): void
    {
        $tab = request()->query('tab');
        if (in_array($tab, ['catalogos', 'bitacora', 'preferencias'], true)) {
            $this->tabActiva = $tab;
        }

        $this->registrosPorHoja = ConfigPreferenciasService::obtenerRegistrosPorHoja();
    }

    public function setTab(string $tab): void
    {
        if (in_array($tab, ['catalogos', 'bitacora', 'preferencias'], true)) {
            $this->tabActiva = $tab;
        }
    }

    public function setCatalogo(string $cat): void
    {
        $this->catalogoActivo = $cat;
        $this->limpiarFiltrosCatalogo();
    }

    public function limpiarFiltrosCatalogo(): void
    {
        $this->catalogoSearch = '';
        $this->filtroDepartamento = '';
        $this->filtroTipoMaterial = '';
        $this->filtroMarca = '';
        $this->filtroRol = '';
        $this->filtroEstadoUsuario = '';
    }

    public function getPlaceholderCatalogo(): string
    {
        return ConfigCatalogosService::getPlaceholder($this->catalogoActivo);
    }

    public function tieneFiltrosCatalogoActivos(): bool
    {
        return filled($this->catalogoSearch)
            || filled($this->filtroDepartamento)
            || filled($this->filtroTipoMaterial)
            || filled($this->filtroMarca)
            || filled($this->filtroRol)
            || filled($this->filtroEstadoUsuario);
    }

    public function limpiarFiltrosBitacora(): void
    {
        $this->bitacoraSearch = '';
        $this->bitacoraUsuario = '';
        $this->bitacoraRol = '';
        $this->bitacoraAccion = '';
    }

    public function seleccionarRegistrosPorHoja(int $valor): void
    {
        $this->registrosPorHoja = $valor;
        $this->otroValor = '';
        $this->mensajePreferencia = '';
    }

    public function guardarPreferencias(): void
    {
        $res = ConfigPreferenciasService::guardarRegistrosPorHoja($this->registrosPorHoja, $this->otroValor);
        $this->registrosPorHoja = $res['valor'];
        $this->mensajePreferencia = $res['mensaje'];

        if ($res['ok']) {
            Notification::make()
                ->title('Preferencias guardadas exitosamente')
                ->success()
                ->send();
        }
    }

    public function getCatalogosList(): array
    {
        return ConfigCatalogosService::getCatalogosList();
    }

    public function getOpcionesFiltrosCatalogoActivo(): array
    {
        return ConfigCatalogosService::getOpcionesFiltros($this->catalogoActivo);
    }

    public function getDatosCatalogoActivo(): array
    {
        return ConfigCatalogosService::getDatosCatalogoActivo($this->catalogoActivo, [
            'search' => $this->catalogoSearch,
            'departamento' => $this->filtroDepartamento,
            'tipo_material' => $this->filtroTipoMaterial,
            'marca' => $this->filtroMarca,
            'rol' => $this->filtroRol,
            'estado_usuario' => $this->filtroEstadoUsuario,
        ]);
    }

    public function getBitacoraData(): array
    {
        return ConfigBitacoraService::getBitacoraData([
            'search' => $this->bitacoraSearch,
            'usuario' => $this->bitacoraUsuario,
            'rol' => $this->bitacoraRol,
            'accion' => $this->bitacoraAccion,
        ]);
    }

    public function getBitacoraUsuarios(): array
    {
        return ConfigBitacoraService::getBitacoraUsuarios();
    }

    public function refreshCatalogoData(): void
    {
        $this->dispatch('catalogo-actualizado');
    }
}
