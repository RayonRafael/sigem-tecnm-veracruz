<?php

namespace App\Filament\ServicioSocial\Pages;

use App\Enums\RoleEnum;
use App\Filament\Concerns\ConDashboardBreadcrumb;
use App\Filament\Resources\DepartamentoResource;
use App\Filament\Resources\ProveedorResource;
use App\Filament\ServicioSocial\Resources\AreaResource;
use App\Filament\ServicioSocial\Resources\MarcaMaterialResource;
use App\Filament\ServicioSocial\Resources\MaterialResource;
use App\Filament\ServicioSocial\Resources\ReceptorResource;
use App\Filament\ServicioSocial\Resources\TipoMaterialResource;
use App\Filament\ServicioSocial\Resources\UnidadMedidaResource;
use App\Models\Area;
use App\Models\Departamento;
use App\Models\MarcaMaterial;
use App\Models\Material;
use App\Models\Proveedor;
use App\Models\Receptor;
use App\Models\TipoMaterial;
use App\Models\UnidadMedida;
use Filament\Actions;
use Filament\Forms\Form;
use Filament\Infolists\Infolist;
use Filament\Pages\Page;

class CatalogosPage extends Page
{
    use ConDashboardBreadcrumb;

    protected static ?string $navigationIcon = 'heroicon-o-folder';

    protected static ?string $navigationGroup = 'Gestión de inventario';

    protected static ?string $navigationLabel = 'Catálogos';

    protected static ?int $navigationSort = 4;

    protected static ?string $slug = 'catalogos';

    protected static ?string $title = 'Catálogos';

    protected static string $view = 'filament.servicio-social.pages.catalogos';

    public string $catalogoActivo = 'departamentos';

    public string $search = '';

    public static function canAccess(): bool
    {
        return auth()->user() && (
            auth()->user()->hasRole(RoleEnum::SERVICIO_SOCIAL->value) ||
            auth()->user()->hasPermissionTo(RoleEnum::PERM_ACCESS_SERVICIO_SOCIAL) ||
            auth()->user()->hasRole(RoleEnum::ADMIN->value)
        );
    }

    public function setCatalogo(string $catalogo): void
    {
        if (array_key_exists($catalogo, $this->getCatalogosConfig())) {
            $this->catalogoActivo = $catalogo;
            $this->search = '';
        }
    }

    protected function getCatalogosConfig(): array
    {
        return [
            'departamentos' => ['nombre' => 'Departamentos', 'emoji' => '🏢', 'singular' => 'departamento'],
            'areas' => ['nombre' => 'Áreas', 'emoji' => '📍', 'singular' => 'área'],
            'materiales' => ['nombre' => 'Materiales', 'emoji' => '🏷️', 'singular' => 'material'],
            'marcas' => ['nombre' => 'Marcas', 'emoji' => '🔖', 'singular' => 'marca'],
            'tipos' => ['nombre' => 'Tipos de material', 'emoji' => '📑', 'singular' => 'tipo de material'],
            'unidades' => ['nombre' => 'Unidades de medida', 'emoji' => '⚖️', 'singular' => 'unidad de medida'],
            'proveedores' => ['nombre' => 'Proveedores', 'emoji' => '🚚', 'singular' => 'proveedor'],
            'receptores' => ['nombre' => 'Receptores', 'emoji' => '👤', 'singular' => 'receptor'],
        ];
    }

    public function getCatalogosList(): array
    {
        return [
            ['id' => 'departamentos', 'nombre' => 'Departamentos', 'emoji' => '🏢', 'total' => Departamento::count()],
            ['id' => 'areas', 'nombre' => 'Áreas', 'emoji' => '📍', 'total' => Area::count()],
            ['id' => 'materiales', 'nombre' => 'Materiales', 'emoji' => '🏷️', 'total' => Material::count()],
            ['id' => 'marcas', 'nombre' => 'Marcas', 'emoji' => '🔖', 'total' => MarcaMaterial::count()],
            ['id' => 'tipos', 'nombre' => 'Tipos de material', 'emoji' => '📑', 'total' => TipoMaterial::count()],
            ['id' => 'unidades', 'nombre' => 'Unidades de medida', 'emoji' => '⚖️', 'total' => UnidadMedida::count()],
            ['id' => 'proveedores', 'nombre' => 'Proveedores', 'emoji' => '🚚', 'total' => Proveedor::count()],
            ['id' => 'receptores', 'nombre' => 'Receptores', 'emoji' => '👤', 'total' => Receptor::count()],
        ];
    }

    public function getDatosCatalogoActivo(): array
    {
        $config = $this->getCatalogosConfig()[$this->catalogoActivo] ?? $this->getCatalogosConfig()['departamentos'];
        $headers = [];
        $rows = [];
        $term = trim($this->search);

        switch ($this->catalogoActivo) {
            case 'departamentos':
                $headers = ['ID', 'Departamento', 'Detalle'];
                $query = Departamento::orderBy('nombre');
                if ($term !== '') {
                    $query->where('nombre', 'like', "%{$term}%");
                }
                foreach ($query->limit(25)->get() as $d) {
                    $rows[] = [
                        '<span class="sigem-td-folio">#' . $d->id_departamento . '</span>',
                        '<strong>' . e($d->nombre) . '</strong>',
                        $this->renderActionButtons('verDepartamento', $d->getKey()),
                    ];
                }
                break;

            case 'areas':
                $headers = ['ID', 'Área', 'Departamento', 'Detalle'];
                $query = Area::with('departamento')->orderBy('nombre');
                if ($term !== '') {
                    $query->where(function ($q) use ($term) {
                        $q->where('nombre', 'like', "%{$term}%")
                          ->orWhereHas('departamento', fn ($dq) => $dq->where('nombre', 'like', "%{$term}%"));
                    });
                }
                foreach ($query->limit(25)->get() as $a) {
                    $rows[] = [
                        '<span class="sigem-td-folio">#' . $a->id_area . '</span>',
                        '<strong>' . e($a->nombre) . '</strong>',
                        e($a->departamento?->nombre ?? 'N/A'),
                        $this->renderActionButtons('verArea', $a->getKey()),
                    ];
                }
                break;

            case 'materiales':
                $headers = ['Modelo / ID', 'Material / Equipo', 'Marca', 'Tipo', 'Stock', 'Detalle'];
                $query = Material::with(['marca', 'tipo'])->orderBy('nombre');
                if ($term !== '') {
                    $query->where(function ($q) use ($term) {
                        $q->where('nombre', 'like', "%{$term}%")
                          ->orWhere('modelo', 'like', "%{$term}%")
                          ->orWhereHas('marca', fn ($mq) => $mq->where('nombre', 'like', "%{$term}%"))
                          ->orWhereHas('tipo', fn ($tq) => $tq->where('nombre', 'like', "%{$term}%"));
                    });
                }
                foreach ($query->limit(25)->get() as $m) {
                    $codigo = $m->modelo ?: ('#' . $m->id_producto);
                    $rows[] = [
                        '<span class="sigem-td-folio">' . e($codigo) . '</span>',
                        '<strong>' . e($m->nombre) . '</strong>',
                        e($m->marca?->nombre ?? 'N/A'),
                        e($m->tipo?->nombre ?? 'N/A'),
                        '<span class="sigem-pill ok">' . $m->stock_actual . '</span>',
                        $this->renderActionButtons('verMaterial', $m->getKey()),
                    ];
                }
                break;

            case 'marcas':
                $headers = ['ID', 'Marca de material', 'Detalle'];
                $query = MarcaMaterial::orderBy('nombre');
                if ($term !== '') {
                    $query->where('nombre', 'like', "%{$term}%");
                }
                foreach ($query->limit(25)->get() as $mc) {
                    $rows[] = [
                        '<span class="sigem-td-folio">#' . $mc->id_marca . '</span>',
                        '<strong>' . e($mc->nombre) . '</strong>',
                        $this->renderActionButtons('verMarca', $mc->getKey()),
                    ];
                }
                break;

            case 'tipos':
                $headers = ['ID', 'Tipo de material', 'Detalle'];
                $query = TipoMaterial::orderBy('nombre');
                if ($term !== '') {
                    $query->where('nombre', 'like', "%{$term}%");
                }
                foreach ($query->limit(25)->get() as $t) {
                    $rows[] = [
                        '<span class="sigem-td-folio">#' . $t->id_tipodematerial . '</span>',
                        '<strong>' . e($t->nombre) . '</strong>',
                        $this->renderActionButtons('verTipo', $t->getKey()),
                    ];
                }
                break;

            case 'unidades':
                $headers = ['ID', 'Unidad de medida', 'Detalle'];
                $query = UnidadMedida::orderBy('nombre');
                if ($term !== '') {
                    $query->where('nombre', 'like', "%{$term}%");
                }
                foreach ($query->limit(25)->get() as $u) {
                    $rows[] = [
                        '<span class="sigem-td-folio">#' . $u->id_unidad . '</span>',
                        '<strong>' . e($u->nombre) . '</strong>',
                        $this->renderActionButtons('verUnidad', $u->getKey()),
                    ];
                }
                break;

            case 'proveedores':
                $headers = ['ID', 'Proveedor', 'Contacto / Teléfono', 'Detalle'];
                $query = Proveedor::orderBy('nombre');
                if ($term !== '') {
                    $query->where(function ($q) use ($term) {
                        $q->where('nombre', 'like', "%{$term}%")
                          ->orWhere('contacto', 'like', "%{$term}%")
                          ->orWhere('telefono', 'like', "%{$term}%");
                    });
                }
                foreach ($query->limit(25)->get() as $p) {
                    $rows[] = [
                        '<span class="sigem-td-folio">#' . $p->id_proveedor . '</span>',
                        '<strong>' . e($p->nombre) . '</strong>',
                        e($p->contacto ?? ($p->telefono ?? 'N/A')),
                        $this->renderActionButtons('verProveedor', $p->getKey()),
                    ];
                }
                break;

            case 'receptores':
                $headers = ['ID', 'Nombre', 'Área', 'Detalle'];
                $query = Receptor::with('area')->orderBy('nombre');
                if ($term !== '') {
                    $query->where(function ($q) use ($term) {
                        $q->where('nombre', 'like', "%{$term}%")
                          ->orWhereHas('area', fn ($aq) => $aq->where('nombre', 'like', "%{$term}%"));
                    });
                }
                foreach ($query->limit(25)->get() as $r) {
                    $rows[] = [
                        '<span class="sigem-td-folio">#' . $r->id_receptor . '</span>',
                        '<strong>' . e($r->nombre) . '</strong>',
                        e($r->area?->nombre ?? 'N/A'),
                        $this->renderActionButtons('verReceptor', $r->getKey()),
                    ];
                }
                break;
        }

        return [
            'id' => $this->catalogoActivo,
            'nombre' => $config['nombre'],
            'emoji' => $config['emoji'],
            'singular' => $config['singular'],
            'headers' => $headers,
            'rows' => $rows,
            'total' => count($rows),
        ];
    }

    protected function renderActionButtons(string $accionVer, int|string $id): string
    {
        return '<button type="button" wire:click="mountAction(\'' . $accionVer . '\', { record: ' . $id . ' })" class="sigem-btn-icon" title="Ver detalles">👁</button>';
    }

    // Acciones de Slide-Over para visualización (solo lectura)
    public function verDepartamentoAction(): Actions\ViewAction { return $this->accionVer('verDepartamento', DepartamentoResource::class, 'departamento'); }
    public function verAreaAction(): Actions\ViewAction { return $this->accionVer('verArea', AreaResource::class, 'área'); }
    public function verMaterialAction(): Actions\ViewAction { return $this->accionVer('verMaterial', MaterialResource::class, 'material'); }
    public function verMarcaAction(): Actions\ViewAction { return $this->accionVer('verMarca', MarcaMaterialResource::class, 'marca'); }
    public function verTipoAction(): Actions\ViewAction { return $this->accionVer('verTipo', TipoMaterialResource::class, 'tipo de material'); }
    public function verUnidadAction(): Actions\ViewAction { return $this->accionVer('verUnidad', UnidadMedidaResource::class, 'unidad de medida'); }
    public function verProveedorAction(): Actions\ViewAction { return $this->accionVer('verProveedor', ProveedorResource::class, 'proveedor'); }
    public function verReceptorAction(): Actions\ViewAction { return $this->accionVer('verReceptor', ReceptorResource::class, 'receptor'); }

    protected function accionVer(string $nombre, string $resource, string $singular): Actions\ViewAction
    {
        $model = $resource::getModel();

        $action = Actions\ViewAction::make($nombre)
            ->model($model)
            ->modalHeading('Detalle de ' . $singular . ' (Solo lectura)')
            ->record(fn (array $arguments) => $model::find($arguments['record'] ?? null))
            ->authorize(true)
            ->slideOver();

        if (method_exists($resource, 'infolist')) {
            $action->infolist(fn (Infolist $infolist): Infolist => $resource::infolist($infolist));
        }

        $action->form(fn (Form $form): Form => $resource::form($form));

        return $action;
    }
}
