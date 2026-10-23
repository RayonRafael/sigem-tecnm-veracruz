<?php

namespace App\Filament\Pages;

use App\Enums\RoleEnum;
use App\Filament\Resources\AreaResource;
use App\Filament\Resources\DepartamentoResource;
use App\Filament\Resources\MarcaMaterialResource;
use App\Filament\Resources\MaterialResource;
use App\Filament\Resources\ProveedorResource;
use App\Filament\Resources\ReceptorResource;
use App\Filament\Resources\TipoMaterialResource;
use App\Filament\Resources\UnidadMedidaResource;
use App\Models\Area;
use App\Models\Departamento;
use App\Models\MarcaMaterial;
use App\Models\Material;
use App\Models\Proveedor;
use App\Models\Receptor;
use App\Models\TipoMaterial;
use App\Models\UnidadMedida;
use App\Models\User;
use Filament\Actions;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Cache;
use Spatie\Activitylog\Models\Activity;

class ConfiguracionPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationGroup = 'Administración';

    protected static ?string $navigationLabel = 'Configuración';

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'configuracion';

    protected static ?string $title = 'Configuración y Catálogos';

    protected static string $view = 'filament.pages.configuracion';

    /**
     * id de catálogo => sufijo de las acciones de página (crear{X}Action / editar{X}Action).
     */
    protected const ENTIDADES_CATALOGO = [
        'departamentos' => 'Departamento',
        'areas' => 'Area',
        'materiales' => 'Material',
        'marcas' => 'Marca',
        'tipos' => 'Tipo',
        'unidades' => 'Unidad',
        'proveedores' => 'Proveedor',
        'receptores' => 'Receptor',
    ];

    public string $tabActiva = 'catalogos';

    public string $catalogoActivo = 'departamentos';

    // Filtros de Bitácora
    public string $bitacoraSearch = '';

    public string $bitacoraUsuario = '';

    public string $bitacoraRol = '';

    public string $bitacoraAccion = '';

    // Preferencias
    public int $registrosPorHoja = 10;

    public string $otroValor = '';

    public string $mensajePreferencia = '';

    public static function canAccess(): bool
    {
        return auth()->user() && (
            auth()->user()->hasRole(RoleEnum::ADMIN->value) ||
            auth()->user()->hasPermissionTo(RoleEnum::PERM_ACCESS_ADMIN)
        );
    }

    public function mount(): void
    {
        $this->registrosPorHoja = (int) session(
            'sigem_registros_por_hoja',
            Cache::get('sigem_registros_por_hoja_' . auth()->id(), 10)
        );
    }

    public function setTab(string $tab): void
    {
        if (in_array($tab, ['catalogos', 'bitacora', 'preferencias'])) {
            $this->tabActiva = $tab;
        }
    }

    public function setCatalogo(string $cat): void
    {
        $this->catalogoActivo = $cat;
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
        // Resuelto Fase 10: Conectado centralizadamente vía App\Support\SigemPagination y Table::configureUsing
        if (! empty($this->otroValor)) {
            $val = (int) $this->otroValor;
            if ($val < 5 || $val > 500) {
                $this->mensajePreferencia = 'El valor personalizado debe estar entre 5 y 500.';

                return;
            }
            $this->registrosPorHoja = $val;
        }

        session(['sigem_registros_por_hoja' => $this->registrosPorHoja]);
        Cache::forever('sigem_registros_por_hoja_' . auth()->id(), $this->registrosPorHoja);

        $this->mensajePreferencia = "✓ Preferencias guardadas ({$this->registrosPorHoja} registros por hoja).";

        Notification::make()
            ->title('Preferencias guardadas exitosamente')
            ->success()
            ->send();
    }

    public function getCatalogosList(): array
    {
        return [
            [
                'id' => 'departamentos',
                'emoji' => '🏢',
                'nombre' => 'Departamentos',
                'singular' => 'departamento',
                'total' => Departamento::count(),
                'resource' => DepartamentoResource::class,
            ],
            [
                'id' => 'areas',
                'emoji' => '📍',
                'nombre' => 'Áreas',
                'singular' => 'área',
                'total' => Area::count(),
                'resource' => AreaResource::class,
            ],
            [
                'id' => 'materiales',
                'emoji' => '📦',
                'nombre' => 'Materiales',
                'singular' => 'material',
                'total' => Material::count(),
                'resource' => MaterialResource::class,
            ],
            [
                'id' => 'marcas',
                'emoji' => '🏷️',
                'nombre' => 'Marcas',
                'singular' => 'marca',
                'total' => MarcaMaterial::count(),
                'resource' => MarcaMaterialResource::class,
            ],
            [
                'id' => 'tipos',
                'emoji' => '🗂️',
                'nombre' => 'Tipos de material',
                'singular' => 'tipo de material',
                'total' => TipoMaterial::count(),
                'resource' => TipoMaterialResource::class,
            ],
            [
                'id' => 'unidades',
                'emoji' => '📐',
                'nombre' => 'Unidades de medida',
                'singular' => 'unidad de medida',
                'total' => UnidadMedida::count(),
                'resource' => UnidadMedidaResource::class,
            ],
            [
                'id' => 'proveedores',
                'emoji' => '🚚',
                'nombre' => 'Proveedores',
                'singular' => 'proveedor',
                'total' => Proveedor::count(),
                'resource' => ProveedorResource::class,
            ],
            [
                'id' => 'receptores',
                'emoji' => '👤',
                'nombre' => 'Receptores',
                'singular' => 'receptor',
                'total' => Receptor::count(),
                'resource' => ReceptorResource::class,
            ],
        ];
    }

    public function getDatosCatalogoActivo(): array
    {
        $cat = collect($this->getCatalogosList())->firstWhere('id', $this->catalogoActivo);
        if (! $cat) {
            $cat = $this->getCatalogosList()[0];
        }

        $headers = [];
        $rows = [];
        $crearAccion = 'crear' . self::ENTIDADES_CATALOGO[$cat['id']];
        $indexUrl = $cat['resource']::getUrl('index');

        // TODO [Fase 10 - Documentado]: Vista previa embebida en pestaña Catálogos; cada catálogo redirige a su ListRecords nativo completo al hacer clic.
        switch ($cat['id']) {
            case 'departamentos':
                $headers = ['ID', 'Nombre del departamento', 'Acciones'];
                $deptos = Departamento::orderBy('nombre')->limit(25)->get();
                foreach ($deptos as $d) {
                    $rows[] = [
                        '<span class="sigem-td-folio">#' . $d->id_departamento . '</span>',
                        '<strong>' . e($d->nombre) . '</strong>',
                        $this->renderEditIconButton('editarDepartamento', $d->getKey()),
                    ];
                }
                break;

            case 'areas':
                $headers = ['ID', 'Área', 'Departamento', 'Acciones'];
                $areas = Area::with('departamento')->orderBy('nombre')->limit(25)->get();
                foreach ($areas as $a) {
                    $rows[] = [
                        '<span class="sigem-td-folio">#' . $a->id_area . '</span>',
                        '<strong>' . e($a->nombre) . '</strong>',
                        e($a->departamento?->nombre ?? 'N/A'),
                        $this->renderEditIconButton('editarArea', $a->getKey()),
                    ];
                }
                break;

            case 'materiales':
                $headers = ['Modelo / ID', 'Material / Equipo', 'Marca', 'Tipo', 'Stock', 'Acciones'];
                $materiales = Material::with(['marca', 'tipo'])->orderBy('nombre')->limit(25)->get();
                foreach ($materiales as $m) {
                    $codigo = $m->modelo ?: ('#' . $m->id_producto);
                    $rows[] = [
                        '<span class="sigem-td-folio">' . e($codigo) . '</span>',
                        '<strong>' . e($m->nombre) . '</strong>',
                        e($m->marca?->nombre ?? 'N/A'),
                        e($m->tipo?->nombre ?? 'N/A'),
                        '<span class="sigem-pill ok">' . $m->stock_actual . '</span>',
                        $this->renderEditIconButton('editarMaterial', $m->getKey()),
                    ];
                }
                break;

            case 'marcas':
                $headers = ['ID', 'Marca de material', 'Acciones'];
                $marcas = MarcaMaterial::orderBy('nombre')->limit(25)->get();
                foreach ($marcas as $mc) {
                    $rows[] = [
                        '<span class="sigem-td-folio">#' . $mc->id_marca . '</span>',
                        '<strong>' . e($mc->nombre) . '</strong>',
                        $this->renderEditIconButton('editarMarca', $mc->getKey()),
                    ];
                }
                break;

            case 'tipos':
                $headers = ['ID', 'Tipo de material', 'Acciones'];
                $tipos = TipoMaterial::orderBy('nombre')->limit(25)->get();
                foreach ($tipos as $t) {
                    $rows[] = [
                        '<span class="sigem-td-folio">#' . $t->id_tipodematerial . '</span>',
                        '<strong>' . e($t->nombre) . '</strong>',
                        $this->renderEditIconButton('editarTipo', $t->getKey()),
                    ];
                }
                break;

            case 'unidades':
                $headers = ['ID', 'Unidad de medida', 'Acciones'];
                $unidades = UnidadMedida::orderBy('nombre')->limit(25)->get();
                foreach ($unidades as $u) {
                    $rows[] = [
                        '<span class="sigem-td-folio">#' . $u->id_unidad . '</span>',
                        '<strong>' . e($u->nombre) . '</strong>',
                        $this->renderEditIconButton('editarUnidad', $u->getKey()),
                    ];
                }
                break;

            case 'proveedores':
                $headers = ['ID', 'Proveedor', 'RFC', 'Contacto', 'Acciones'];
                $proveedores = Proveedor::orderBy('nombre_empresa')->limit(25)->get();
                foreach ($proveedores as $p) {
                    $contacto = trim(($p->contacto_nombre ? $p->contacto_nombre . ' · ' : '') . ($p->contacto_telefono ?? '') . ' ' . ($p->contacto_email ?? ''));
                    $rows[] = [
                        '<span class="sigem-td-folio">#' . $p->id_proveedor . '</span>',
                        '<strong>' . e($p->nombre_empresa) . '</strong>',
                        e($p->rfc ?? 'N/A'),
                        e($contacto ?: 'Sin datos'),
                        $this->renderEditIconButton('editarProveedor', $p->getKey()),
                    ];
                }
                break;

            case 'receptores':
                $headers = ['ID', 'Receptor', 'Área', 'Contacto', 'Acciones'];
                $receptores = Receptor::with('area')->orderBy('nombre')->limit(25)->get();
                foreach ($receptores as $r) {
                    $nombreCompleto = trim("{$r->nombre} {$r->apellido_paterno} {$r->apellido_materno}");
                    $contacto = trim(($r->telefono ?? '') . ' ' . ($r->email ?? ''));
                    $rows[] = [
                        '<span class="sigem-td-folio">#' . $r->id_receptor . '</span>',
                        '<strong>' . e($nombreCompleto ?: $r->nombre) . '</strong>',
                        e($r->area?->nombre ?? 'N/A'),
                        e($contacto ?: 'Sin datos'),
                        $this->renderEditIconButton('editarReceptor', $r->getKey()),
                    ];
                }
                break;
        }

        return [
            'id' => $cat['id'],
            'emoji' => $cat['emoji'],
            'nombre' => $cat['nombre'],
            'singular' => $cat['singular'],
            'total' => $cat['total'],
            'crearAccion' => $crearAccion,
            'indexUrl' => $indexUrl,
            'headers' => $headers,
            'rows' => $rows,
        ];
    }

    public function getBitacoraData(): array
    {
        $query = Activity::with(['causer.roles'])->latest('created_at');

        if (! empty($this->bitacoraSearch)) {
            $s = trim($this->bitacoraSearch);
            $query->where(function ($q) use ($s) {
                $q->where('description', 'like', "%{$s}%")
                    ->orWhere('event', 'like', "%{$s}%")
                    ->orWhere('subject_type', 'like', "%{$s}%")
                    ->orWhereHasMorph('causer', [User::class], function ($uq) use ($s) {
                        $uq->where('name', 'like', "%{$s}%")
                            ->orWhere('email', 'like', "%{$s}%");
                    });
            });
        }

        if (! empty($this->bitacoraUsuario)) {
            $query->where('causer_id', $this->bitacoraUsuario);
        }

        if (! empty($this->bitacoraRol)) {
            $rol = $this->bitacoraRol;
            $query->whereHasMorph('causer', [User::class], function ($q) use ($rol) {
                $q->whereHas('roles', function ($rq) use ($rol) {
                    $rq->where('name', $rol);
                });
            });
        }

        if (! empty($this->bitacoraAccion)) {
            $query->where('event', $this->bitacoraAccion);
        }

        $activities = $query->limit(60)->get();
        $rows = [];

        foreach ($activities as $a) {
            $user = $a->causer;
            $rolName = $user?->roles?->first()?->name;

            $rolPill = match ($rolName) {
                'Administrador' => '<span class="sigem-rol-pill adm">Administrador</span>',
                'Servicio Social' => '<span class="sigem-rol-pill ss">Servicio Social</span>',
                default => '<span class="sigem-rol-pill" style="background:var(--sig-bg);color:var(--sig-muted)">' . e($rolName ?? 'Sistema') . '</span>',
            };

            $accionTipo = match (strtolower($a->event ?? '')) {
                'created', 'creado', 'alta' => 'ok',
                'updated', 'actualizado', 'modificado' => 'info',
                'deleted', 'eliminado', 'baja' => 'danger',
                default => 'neutral',
            };

            $accionLabel = match (strtolower($a->event ?? '')) {
                'created' => 'Creación',
                'updated' => 'Actualización',
                'deleted' => 'Eliminación',
                default => ucfirst($a->event ?? 'Registro'),
            };

            $rows[] = [
                'fecha' => $a->created_at ? $a->created_at->format('d/m/Y H:i') : 'N/A',
                'usuario' => '<strong>' . e($user?->name ?? 'Sistema') . '</strong>',
                'rol' => $rolPill,
                'accion' => '<span class="sigem-pill ' . $accionTipo . '">' . e($accionLabel) . '</span>',
                'detalle' => e($a->description ?? class_basename($a->subject_type ?? 'Sistema')),
            ];
        }

        return $rows;
    }

    public function getBitacoraUsuarios(): array
    {
        return User::orderBy('name')->pluck('name', 'id')->toArray();
    }

    // ------------------------------------------------------------------
    // Acciones de página (slide-over) para los catálogos embebidos.
    // El formulario se reutiliza de cada Resource::form(); no se duplican campos.
    // ------------------------------------------------------------------

    public function crearDepartamentoAction(): Actions\CreateAction { return $this->accionCrear('crearDepartamento', DepartamentoResource::class, 'Departamento creado'); }

    public function editarDepartamentoAction(): Actions\EditAction { return $this->accionEditar('editarDepartamento', DepartamentoResource::class, 'Departamento actualizado'); }

    public function crearAreaAction(): Actions\CreateAction { return $this->accionCrear('crearArea', AreaResource::class, 'Área creada'); }

    public function editarAreaAction(): Actions\EditAction { return $this->accionEditar('editarArea', AreaResource::class, 'Área actualizada'); }

    public function crearMaterialAction(): Actions\CreateAction { return $this->accionCrear('crearMaterial', MaterialResource::class, 'Material creado'); }

    public function editarMaterialAction(): Actions\EditAction { return $this->accionEditar('editarMaterial', MaterialResource::class, 'Material actualizado'); }

    public function crearMarcaAction(): Actions\CreateAction { return $this->accionCrear('crearMarca', MarcaMaterialResource::class, 'Marca creada'); }

    public function editarMarcaAction(): Actions\EditAction { return $this->accionEditar('editarMarca', MarcaMaterialResource::class, 'Marca actualizada'); }

    public function crearTipoAction(): Actions\CreateAction { return $this->accionCrear('crearTipo', TipoMaterialResource::class, 'Tipo de material creado'); }

    public function editarTipoAction(): Actions\EditAction { return $this->accionEditar('editarTipo', TipoMaterialResource::class, 'Tipo de material actualizado'); }

    public function crearUnidadAction(): Actions\CreateAction { return $this->accionCrear('crearUnidad', UnidadMedidaResource::class, 'Unidad de medida creada'); }

    public function editarUnidadAction(): Actions\EditAction { return $this->accionEditar('editarUnidad', UnidadMedidaResource::class, 'Unidad de medida actualizada'); }

    public function crearProveedorAction(): Actions\CreateAction { return $this->accionCrear('crearProveedor', ProveedorResource::class, 'Proveedor creado'); }

    public function editarProveedorAction(): Actions\EditAction { return $this->accionEditar('editarProveedor', ProveedorResource::class, 'Proveedor actualizado'); }

    public function crearReceptorAction(): Actions\CreateAction { return $this->accionCrear('crearReceptor', ReceptorResource::class, 'Receptor creado'); }

    public function editarReceptorAction(): Actions\EditAction { return $this->accionEditar('editarReceptor', ReceptorResource::class, 'Receptor actualizado'); }

    protected function accionCrear(string $nombre, string $resource, string $mensaje): Actions\CreateAction
    {
        return Actions\CreateAction::make($nombre)
            ->model($resource::getModel())
            ->form(fn (Form $form): Form => $resource::form($form))
            ->slideOver()
            ->createAnother(false)
            ->successNotificationTitle($mensaje)
            ->after(fn () => $this->refreshCatalogoData());
    }

    protected function accionEditar(string $nombre, string $resource, string $mensaje): Actions\EditAction
    {
        $model = $resource::getModel();

        return Actions\EditAction::make($nombre)
            ->model($model)
            ->record(fn (array $arguments) => $model::find($arguments['record'] ?? null))
            ->form(fn (Form $form): Form => $resource::form($form))
            ->slideOver()
            ->successNotificationTitle($mensaje)
            ->after(fn () => $this->refreshCatalogoData());
    }

    /**
     * Las filas y totales de la tabla embebida se calculan en cada render
     * (getDatosCatalogoActivo / getCatalogosList); al terminar una acción Livewire
     * vuelve a renderizar la página, por lo que este método solo avisa del cambio.
     */
    public function refreshCatalogoData(): void
    {
        $this->dispatch('catalogo-actualizado');
    }

    protected function renderEditIconButton(string $accion, int|string $id): string
    {
        return '<button type="button" wire:click="mountAction(\'' . $accion . '\', { record: ' . json_encode($id) . ' })" class="sigem-rep-btn-sec fi-icon-btn" title="Editar" aria-label="Editar" style="width:32px;height:32px;padding:0;display:inline-flex;align-items:center;justify-content:center;cursor:pointer;"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style="width:16px;height:16px;"><path d="m5.433 13.917 1.262-3.155A4 4 0 0 1 7.58 9.42l6.92-6.918a2.121 2.121 0 0 1 3 3l-6.92 6.918c-.383.383-.84.685-1.343.886l-3.154 1.262a.5.5 0 0 1-.65-.65Z" /><path d="M3.5 5.75c0-.69.56-1.25 1.25-1.25H10A.75.75 0 0 0 10 3H4.75A2.75 2.75 0 0 0 2 5.75v9.5A2.75 2.75 0 0 0 4.75 18h9.5A2.75 2.75 0 0 0 17 15.25V10a.75.75 0 0 0-1.5 0v5.25c0 .69-.56 1.25-1.25 1.25h-9.5c-.69 0-1.25-.56-1.25-1.25v-9.5Z" /></svg></button>';
    }
}
