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
use App\Filament\Resources\UserResource;
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
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\HtmlString;
use Spatie\Activitylog\Models\Activity;

class ConfiguracionPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationGroup = 'Administración';

    protected static ?string $navigationLabel = 'Configuración';

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'configuracion';

    protected static ?string $title = '⚙️ Configuración y Catálogos';

    protected static string $view = 'filament.pages.configuracion';

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
        'usuarios' => 'Usuario',
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
            [
                'id' => 'usuarios',
                'emoji' => '👥',
                'nombre' => 'Usuarios',
                'singular' => 'usuario',
                'total' => User::count(),
                'resource' => UserResource::class,
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
                        $this->renderActionButtons('editarDepartamento', 'verDepartamento', 'eliminarDepartamento', $d->getKey()),
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
                        $this->renderActionButtons('editarArea', 'verArea', 'eliminarArea', $a->getKey()),
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
                        $this->renderActionButtons('editarMaterial', 'verMaterial', 'eliminarMaterial', $m->getKey()),
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
                        $this->renderActionButtons('editarMarca', 'verMarca', 'eliminarMarca', $mc->getKey()),
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
                        $this->renderActionButtons('editarTipo', 'verTipo', 'eliminarTipo', $t->getKey()),
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
                        $this->renderActionButtons('editarUnidad', 'verUnidad', 'eliminarUnidad', $u->getKey()),
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
                        $this->renderActionButtons('editarProveedor', 'verProveedor', 'eliminarProveedor', $p->getKey()),
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
                        $this->renderActionButtons('editarReceptor', 'verReceptor', 'eliminarReceptor', $r->getKey()),
                    ];
                }
                break;

            case 'usuarios':
                $headers = ['ID', 'Usuario', 'Correo electrónico', 'No. control', 'Rol', 'Estado', 'Acciones'];
                $usuarios = User::with('roles')->orderBy('name')->limit(25)->get();
                foreach ($usuarios as $u) {
                    $nombreCompleto = trim("{$u->name} {$u->apellido_paterno} {$u->apellido_materno}");
                    $rol = $u->roles->first()?->name ?? $u->tipo_usuario ?? 'Sin rol';
                    $esAdmin = $u->hasRole(RoleEnum::ADMIN->value) || $u->tipo_usuario === RoleEnum::ADMIN->value;
                    $esServicio = $u->hasRole(RoleEnum::SERVICIO_SOCIAL->value) || $u->tipo_usuario === RoleEnum::SERVICIO_TIPO->value;

                    $rolBadge = match (true) {
                        $esAdmin => '<span class="sigem-pill ok">👑 ' . e($rol) . '</span>',
                        $esServicio => '<span class="sigem-pill info">🎓 ' . e($rol) . '</span>',
                        default => '<span class="sigem-pill">● ' . e($rol) . '</span>',
                    };

                    $estadoBadge = $u->activo
                        ? '<span class="sigem-pill ok">● Activo</span>'
                        : '<span class="sigem-pill danger">● Inactivo</span>';

                    $puedeEliminar = $u->email !== 'admin@tecnm.edu.mx';

                    $rows[] = [
                        '<span class="sigem-td-folio">#' . $u->id . '</span>',
                        '<strong>' . e($nombreCompleto ?: $u->name) . '</strong>',
                        e($u->email),
                        e($u->num_control ?: '—'),
                        $rolBadge,
                        $estadoBadge,
                        $this->renderActionButtons('editarUsuario', 'verUsuario', 'eliminarUsuario', $u->getKey(), $puedeEliminar),
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

    public function crearDepartamentoAction(): Actions\CreateAction { return $this->accionCrear('crearDepartamento', DepartamentoResource::class, 'Departamento creado', 'departamento'); }

    public function editarDepartamentoAction(): Actions\EditAction { return $this->accionEditar('editarDepartamento', DepartamentoResource::class, 'Departamento actualizado', 'departamento'); }

    public function verDepartamentoAction(): Actions\ViewAction { return $this->accionVer('verDepartamento', DepartamentoResource::class, 'departamento'); }

    public function eliminarDepartamentoAction(): Actions\DeleteAction { return $this->accionEliminar('eliminarDepartamento', DepartamentoResource::class, 'Departamento eliminado', 'departamento'); }

    public function crearAreaAction(): Actions\CreateAction { return $this->accionCrear('crearArea', AreaResource::class, 'Área creada', 'área'); }

    public function editarAreaAction(): Actions\EditAction { return $this->accionEditar('editarArea', AreaResource::class, 'Área actualizada', 'área'); }

    public function verAreaAction(): Actions\ViewAction { return $this->accionVer('verArea', AreaResource::class, 'área'); }

    public function eliminarAreaAction(): Actions\DeleteAction { return $this->accionEliminar('eliminarArea', AreaResource::class, 'Área eliminada', 'área'); }

    public function crearMaterialAction(): Actions\CreateAction { return $this->accionCrear('crearMaterial', MaterialResource::class, 'Material creado', 'material'); }

    public function editarMaterialAction(): Actions\EditAction { return $this->accionEditar('editarMaterial', MaterialResource::class, 'Material actualizado', 'material'); }

    public function verMaterialAction(): Actions\ViewAction { return $this->accionVer('verMaterial', MaterialResource::class, 'material'); }

    public function eliminarMaterialAction(): Actions\DeleteAction { return $this->accionEliminar('eliminarMaterial', MaterialResource::class, 'Material eliminado', 'material'); }

    public function crearMarcaAction(): Actions\CreateAction { return $this->accionCrear('crearMarca', MarcaMaterialResource::class, 'Marca creada', 'marca'); }

    public function editarMarcaAction(): Actions\EditAction { return $this->accionEditar('editarMarca', MarcaMaterialResource::class, 'Marca actualizada', 'marca'); }

    public function verMarcaAction(): Actions\ViewAction { return $this->accionVer('verMarca', MarcaMaterialResource::class, 'marca'); }

    public function eliminarMarcaAction(): Actions\DeleteAction { return $this->accionEliminar('eliminarMarca', MarcaMaterialResource::class, 'Marca eliminada', 'marca'); }

    public function crearTipoAction(): Actions\CreateAction { return $this->accionCrear('crearTipo', TipoMaterialResource::class, 'Tipo de material creado', 'tipo de material'); }

    public function editarTipoAction(): Actions\EditAction { return $this->accionEditar('editarTipo', TipoMaterialResource::class, 'Tipo de material actualizado', 'tipo de material'); }

    public function verTipoAction(): Actions\ViewAction { return $this->accionVer('verTipo', TipoMaterialResource::class, 'tipo de material'); }

    public function eliminarTipoAction(): Actions\DeleteAction { return $this->accionEliminar('eliminarTipo', TipoMaterialResource::class, 'Tipo de material eliminado', 'tipo de material'); }

    public function crearUnidadAction(): Actions\CreateAction { return $this->accionCrear('crearUnidad', UnidadMedidaResource::class, 'Unidad de medida creada', 'unidad de medida'); }

    public function editarUnidadAction(): Actions\EditAction { return $this->accionEditar('editarUnidad', UnidadMedidaResource::class, 'Unidad de medida actualizada', 'unidad de medida'); }

    public function verUnidadAction(): Actions\ViewAction { return $this->accionVer('verUnidad', UnidadMedidaResource::class, 'unidad de medida'); }

    public function eliminarUnidadAction(): Actions\DeleteAction { return $this->accionEliminar('eliminarUnidad', UnidadMedidaResource::class, 'Unidad de medida eliminada', 'unidad de medida'); }

    public function crearProveedorAction(): Actions\CreateAction { return $this->accionCrear('crearProveedor', ProveedorResource::class, 'Proveedor creado', 'proveedor'); }

    public function editarProveedorAction(): Actions\EditAction { return $this->accionEditar('editarProveedor', ProveedorResource::class, 'Proveedor actualizado', 'proveedor'); }

    public function verProveedorAction(): Actions\ViewAction { return $this->accionVer('verProveedor', ProveedorResource::class, 'proveedor'); }

    public function eliminarProveedorAction(): Actions\DeleteAction { return $this->accionEliminar('eliminarProveedor', ProveedorResource::class, 'Proveedor eliminado', 'proveedor'); }

    public function crearReceptorAction(): Actions\CreateAction { return $this->accionCrear('crearReceptor', ReceptorResource::class, 'Receptor creado', 'receptor'); }

    public function editarReceptorAction(): Actions\EditAction { return $this->accionEditar('editarReceptor', ReceptorResource::class, 'Receptor actualizado', 'receptor'); }

    public function verReceptorAction(): Actions\ViewAction { return $this->accionVer('verReceptor', ReceptorResource::class, 'receptor'); }

    public function eliminarReceptorAction(): Actions\DeleteAction { return $this->accionEliminar('eliminarReceptor', ReceptorResource::class, 'Receptor eliminado', 'receptor'); }

    public function crearUsuarioAction(): Actions\CreateAction { return $this->accionCrear('crearUsuario', UserResource::class, 'Usuario creado exitosamente', 'usuario'); }

    public function editarUsuarioAction(): Actions\EditAction { return $this->accionEditar('editarUsuario', UserResource::class, 'Usuario actualizado exitosamente', 'usuario'); }

    public function verUsuarioAction(): Actions\ViewAction { return $this->accionVer('verUsuario', UserResource::class, 'usuario'); }

    public function eliminarUsuarioAction(): Actions\DeleteAction { return $this->accionEliminar('eliminarUsuario', UserResource::class, 'Usuario eliminado exitosamente', 'usuario'); }

    protected function accionCrear(string $nombre, string $resource, string $mensaje, string $singular): Actions\CreateAction
    {
        return Actions\CreateAction::make($nombre)
            ->model($resource::getModel())
            ->modalHeading(fn () => '+ Nuevo ' . $singular)
            ->modalDescription(fn () => 'Da de alta un registro en el catálogo institucional.')
            ->form(fn (Form $form): Form => $resource::form($form))
            ->mutateFormDataUsing(function (array $data) use ($resource): array {
                if ($resource === UserResource::class && isset($data['roles'])) {
                    $data['tipo_usuario'] = UserResource::mapRoleToTipoUsuario($data['roles']);
                }

                return $data;
            })
            ->slideOver()
            ->createAnother(false)
            ->successNotificationTitle($mensaje)
            ->after(fn () => $this->refreshCatalogoData());
    }

    protected function accionEditar(string $nombre, string $resource, string $mensaje, string $singular): Actions\EditAction
    {
        $model = $resource::getModel();

        return Actions\EditAction::make($nombre)
            ->model($model)
            ->modalHeading(function (?Model $record, array $arguments = []) use ($model) {
                $rec = $record ?? $model::find($arguments['record'] ?? null);
                if ($rec instanceof User) {
                    return 'Editar: ' . ($rec->name ?? 'usuario');
                }
                if ($rec instanceof Receptor) {
                    return 'Editar: ' . (trim(($rec->nombre ?? '') . ' ' . ($rec->apellido_paterno ?? '')) ?: 'receptor');
                }

                return 'Editar: ' . trim($rec?->nombre ?? $rec?->nombre_empresa ?? 'registro');
            })
            ->modalDescription(function (?Model $record, array $arguments = []) use ($model) {
                $rec = $record ?? $model::find($arguments['record'] ?? null);
                if ($rec instanceof User) {
                    $rol = $rec->getRoleNames()->first() ?? $rec->tipo_usuario ?? 'Sin rol';
                    return new HtmlString(
                        e($rec->email ?? '—') . ' · ' . e($rol) . ' · ID: #' . e($rec->getKey())
                    );
                }
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

                return new HtmlString('ID: #' . e($rec?->getKey()));
            })
            ->record(fn (array $arguments) => $model::find($arguments['record'] ?? null))
            ->form(fn (Form $form): Form => $resource::form($form))
            ->mutateFormDataUsing(function (array $data) use ($resource): array {
                if ($resource === UserResource::class && isset($data['roles'])) {
                    $data['tipo_usuario'] = UserResource::mapRoleToTipoUsuario($data['roles']);
                }

                return $data;
            })
            ->slideOver()
            ->successNotificationTitle($mensaje)
            ->after(fn () => $this->refreshCatalogoData());
    }

    protected function accionVer(string $nombre, string $resource, string $singular): Actions\ViewAction
    {
        $model = $resource::getModel();
        $entidad = str_replace('ver', '', $nombre);

        $action = Actions\ViewAction::make($nombre)
            ->model($model)
            ->modalHeading(function (?Model $record, array $arguments = []) use ($model) {
                $rec = $record ?? $model::find($arguments['record'] ?? null);
                if ($rec instanceof User) {
                    $nombre = trim(($rec->name ?? '') . ' ' . ($rec->apellido_paterno ?? ''));
                    return $nombre ?: 'Detalle de usuario';
                }
                if ($rec instanceof Receptor) {
                    return trim(($rec->nombre ?? '') . ' ' . ($rec->apellido_paterno ?? '') . ' ' . ($rec->apellido_materno ?? '')) ?: 'Detalle de receptor';
                }

                return trim($rec?->nombre ?? $rec?->nombre_empresa ?? 'Detalle');
            })
            ->modalDescription(function (?Model $record, array $arguments = []) use ($model, $singular) {
                $rec = $record ?? $model::find($arguments['record'] ?? null);
                if ($rec instanceof User) {
                    $rol = $rec->getRoleNames()->first() ?? $rec->tipo_usuario ?? 'Sin rol';
                    return new HtmlString(
                        e($rec->email ?? '—') . ' · ' . e($rol) . ' · ID: #' . e($rec->getKey())
                    );
                }
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
                    '🏢 ' . e($singular) . ' · ID: #' . e($rec?->getKey())
                );
            })
            ->modalCancelActionLabel('Cerrar')
            ->record(fn (array $arguments) => $model::find($arguments['record'] ?? null))
            ->slideOver();

        if (method_exists($resource, 'infolist')) {
            $action->infolist(fn (Infolist $infolist): Infolist => $resource::infolist($infolist));
        }

        $action->form(fn (Form $form): Form => $resource::form($form));

        $action->extraModalFooterActions([
            Actions\Action::make('editar_desde_detalle')
                ->label('Editar')
                ->color('warning')
                ->icon('heroicon-m-pencil-square')
                ->action(fn ($record, $livewire) => $livewire->mountAction(
                    'editar' . $entidad, ['record' => $record->getKey()]
                )),
            Actions\Action::make('eliminar_desde_detalle')
                ->label('Eliminar')
                ->color('danger')
                ->icon('heroicon-m-trash')
                ->hidden(fn (?Model $record): bool => $record instanceof User && $record->email === 'admin@tecnm.edu.mx')
                ->action(fn ($record, $livewire) => $livewire->mountAction(
                    'eliminar' . $entidad, ['record' => $record->getKey()]
                )),
        ]);

        return $action;
    }

    protected function accionEliminar(string $nombre, string $resource, string $mensaje, string $singular): Actions\DeleteAction
    {
        $model = $resource::getModel();

        return Actions\DeleteAction::make($nombre)
            ->model($model)
            ->modalHeading(function (?Model $record, array $arguments = []) use ($model) {
                $rec = $record ?? $model::find($arguments['record'] ?? null);
                if ($rec instanceof User) {
                    return '¿Eliminar usuario ' . ($rec->name ?? 'registro') . '?';
                }
                return '¿Eliminar ' . trim($rec?->nombre ?? $rec?->nombre_empresa ?? 'registro') . '?';
            })
            ->modalDescription(function (?Model $record, array $arguments = []) use ($model) {
                $rec = $record ?? $model::find($arguments['record'] ?? null);
                if ($rec instanceof User) {
                    return new HtmlString(e($rec->email ?? '—') . ' · ID: #' . e($rec->getKey()) . ' · Esta acción no se puede deshacer');
                }
                return new HtmlString('ID: #' . e($rec?->getKey()) . ' · Esta acción no se puede deshacer');
            })
            ->record(fn (array $arguments) => $model::find($arguments['record'] ?? null))
            ->hidden(static function (?Model $record = null): bool {
                if (! $record) {
                    return false;
                }

                if ($record instanceof User && $record->email === 'admin@tecnm.edu.mx') {
                    return true;
                }

                if (! method_exists($record, 'trashed')) {
                    return false;
                }

                return $record->trashed();
            })
            ->using(function (?Model $record, Actions\DeleteAction $action, array $arguments = []) use ($model) {
                $record ??= $model::find($arguments['record'] ?? null);

                if (! $record) {
                    return false;
                }

                try {
                    return $record->delete();
                } catch (\Throwable $e) {
                    Notification::make()
                        ->title('No se puede eliminar el registro')
                        ->body('El registro está en uso por otros elementos del sistema.')
                        ->danger()
                        ->send();

                    $action->halt();

                    return false;
                }
            })
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

    protected function renderActionButtons(string $accionEditar, string $accionVer, string $accionEliminar, int|string $id, bool $puedeEliminar = true): string
    {
        $btnEliminar = $puedeEliminar ? '
            <button type="button" wire:click="mountAction(\'' . $accionEliminar . '\', { record: ' . json_encode($id) . ' })"
                    class="fi-icon-btn sigem-cat-btn fi-icon-btn-danger" title="Eliminar" aria-label="Eliminar">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style="width:16px;height:16px;">
                <path fill-rule="evenodd" d="M8.75 1A2.75 2.75 0 0 0 6 3.75v.443c-.795.077-1.584.176-2.365.298a.75.75 0 1 0 .23 1.482l.149-.022.841 10.518A2.75 2.75 0 0 0 7.596 19h4.807a2.75 2.75 0 0 0 2.742-2.53l.841-10.52.149.023a.75.75 0 0 0 .23-1.482A41.03 41.03 0 0 0 14 4.193V3.75A2.75 2.75 0 0 0 11.25 1h-2.5ZM10 4c.84 0 1.673.025 2.5.075V3.75c0-.69-.56-1.25-1.25-1.25h-2.5c-.69 0-1.25.56-1.25 1.25v.325C8.327 4.025 9.16 4 10 4ZM8.58 7.72a.75.75 0 0 0-1.5.06l.3 7.5a.75.75 0 1 0 1.5-.06l-.3-7.5Zm4.34.06a.75.75 0 1 0-1.5-.06l-.3 7.5a.75.75 0 1 0 1.5.06l.3-7.5Z" clip-rule="evenodd" />
              </svg>
            </button>
        ' : '';

        return '
          <div class="sigem-row-actions" style="display:inline-flex;align-items:center;justify-content:center;gap:3px;">
            <button type="button" wire:click="mountAction(\'' . $accionVer . '\', { record: ' . json_encode($id) . ' })"
                    class="fi-icon-btn sigem-cat-btn" title="Ver detalle" aria-label="Ver detalle">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style="width:16px;height:16px;">
                <path d="M10 12.5a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5Z" />
                <path fill-rule="evenodd" d="M.664 10.59a1.651 1.651 0 0 1 0-1.186A10.004 10.004 0 0 1 10 3c4.257 0 7.893 2.66 9.336 6.41.147.381.146.804 0 1.186A10.004 10.004 0 0 1 10 17c-4.257 0-7.893-2.66-9.336-6.41ZM14 10a4 4 0 1 1-8 0 4 4 0 0 1 8 0Z" clip-rule="evenodd" />
              </svg>
            </button>
            <button type="button" wire:click="mountAction(\'' . $accionEditar . '\', { record: ' . json_encode($id) . ' })"
                    class="fi-icon-btn sigem-cat-btn" title="Editar" aria-label="Editar">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style="width:16px;height:16px;">
                <path d="m5.433 13.917 1.262-3.155A4 4 0 0 1 7.58 9.42l6.92-6.918a2.121 2.121 0 0 1 3 3l-6.92 6.918c-.383.383-.84.685-1.343.886l-3.154 1.262a.5.5 0 0 1-.65-.65Z" />
                <path d="M3.5 5.75c0-.69.56-1.25 1.25-1.25H10A.75.75 0 0 0 10 3H4.75A2.75 2.75 0 0 0 2 5.75v9.5A2.75 2.75 0 0 0 4.75 18h9.5A2.75 2.75 0 0 0 17 15.25V10a.75.75 0 0 0-1.5 0v5.25c0 .69-.56 1.25-1.25 1.25h-9.5c-.69 0-1.25-.56-1.25-1.25v-9.5Z" />
              </svg>
            </button>
            ' . $btnEliminar . '
          </div>
        ';
    }
}
