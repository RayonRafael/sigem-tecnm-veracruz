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
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;

class CatalogosPage extends Page
{
    use ConDashboardBreadcrumb;

    protected static ?string $navigationIcon = 'heroicon-o-folder';

    protected static ?string $navigationGroup = 'Gestión de inventario';

    protected static ?string $navigationLabel = 'Catálogos';

    protected static ?int $navigationSort = 4;

    protected static ?string $slug = 'catalogos';

    protected static ?string $title = '📁 Catálogos';

    protected static string $view = 'filament.servicio-social.pages.catalogos';

    public function getTitle(): string
    {
        return '📁 Catálogos';
    }

    public function getSubheading(): ?string
    {
        return null;
    }

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

    public function getPlaceholder(): string
    {
        return match ($this->catalogoActivo) {
            'departamentos' => 'Buscar por nombre del departamento...',
            'areas' => 'Buscar por nombre o departamento...',
            'materiales' => 'Buscar por nombre, modelo, marca o tipo...',
            'marcas' => 'Buscar por nombre de la marca...',
            'tipos' => 'Buscar por nombre del tipo...',
            'unidades' => 'Buscar por nombre de la unidad...',
            'proveedores' => 'Buscar por empresa, RFC, contacto o correo...',
            'receptores' => 'Buscar por nombre, cargo, área o correo...',
            default => 'Buscar en este catálogo...',
        };
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
                $query = Proveedor::orderBy('nombre_empresa');
                if ($term !== '') {
                    $query->where(function ($q) use ($term) {
                        $q->where('nombre_empresa', 'like', "%{$term}%")
                          ->orWhere('contacto_nombre', 'like', "%{$term}%")
                          ->orWhere('contacto_telefono', 'like', "%{$term}%");
                    });
                }
                foreach ($query->limit(25)->get() as $p) {
                    $contacto = trim(($p->contacto_nombre ? $p->contacto_nombre . ' · ' : '') . ($p->contacto_telefono ?? ''));
                    $rows[] = [
                        '<span class="sigem-td-folio">#' . $p->id_proveedor . '</span>',
                        '<strong>' . e($p->nombre_empresa) . '</strong>',
                        e($contacto ?: 'N/A'),
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
                    $nombreCompleto = trim("{$r->nombre} {$r->apellido_paterno} {$r->apellido_materno}");
                    $rows[] = [
                        '<span class="sigem-td-folio">#' . $r->id_receptor . '</span>',
                        '<strong>' . e($nombreCompleto ?: $r->nombre) . '</strong>',
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
        return '
          <div class="sigem-row-actions" style="display:inline-flex;align-items:center;justify-content:center;gap:3px;">
            <button type="button" wire:click="mountAction(\'' . $accionVer . '\', { record: ' . json_encode($id) . ' })"
                    class="fi-icon-btn sigem-cat-btn" title="Ver detalle" aria-label="Ver detalle">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style="width:16px;height:16px;">
                <path d="M10 12.5a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5Z" />
                <path fill-rule="evenodd" d="M.664 10.59a1.651 1.651 0 0 1 0-1.186A10.004 10.004 0 0 1 10 3c4.257 0 7.893 2.66 9.336 6.41.147.381.146.804 0 1.186A10.004 10.004 0 0 1 10 17c-4.257 0-7.893-2.66-9.336-6.41ZM14 10a4 4 0 1 1-8 0 4 4 0 0 1 8 0Z" clip-rule="evenodd" />
              </svg>
            </button>
          </div>
        ';
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
            ->modalHeading(function (?Model $record, array $arguments = []) use ($model) {
                $rec = $record ?? $model::find($arguments['record'] ?? null);
                if ($rec instanceof Receptor) {
                    return trim(($rec->nombre ?? '') . ' ' . ($rec->apellido_paterno ?? '') . ' ' . ($rec->apellido_materno ?? '')) ?: 'Detalle de receptor';
                }

                return trim($rec?->nombre ?? $rec?->nombre_empresa ?? 'Detalle');
            })
            ->modalDescription(function (?Model $record, array $arguments = []) use ($model, $singular) {
                $rec = $record ?? $model::find($arguments['record'] ?? null);
                if ($rec instanceof Proveedor) {
                    return new HtmlString(
                        e($rec->contacto_email ?? '—') . ' · ID: #' . e($rec->getKey())
                    );
                }
                if ($rec instanceof Receptor) {
                    return new HtmlString(
                        e($rec->cargo ?? '—') . ' · ID: #' . e($rec->getKey())
                    );
                }

                return new HtmlString(
                    '🏢 ' . e($singular) . ' · 👁 Solo lectura · ID: #' . e($rec?->getKey())
                );
            })
            ->modalCancelActionLabel('Cerrar')
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
