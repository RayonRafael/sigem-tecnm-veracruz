<?php

namespace App\Services;

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
use Spatie\Permission\Models\Role;

class ConfigCatalogosService
{
    public const ENTIDADES_CATALOGO = [
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

    public static function getPlaceholder(string $catalogoActivo): string
    {
        return match ($catalogoActivo) {
            'departamentos' => 'Buscar por nombre del departamento...',
            'areas' => 'Buscar por nombre o departamento...',
            'materiales' => 'Buscar por nombre, modelo, marca o tipo...',
            'marcas' => 'Buscar por nombre de la marca...',
            'tipos' => 'Buscar por nombre del tipo...',
            'unidades' => 'Buscar por nombre de la unidad...',
            'proveedores' => 'Buscar por empresa, RFC, contacto o correo...',
            'receptores' => 'Buscar por nombre, cargo, área o correo...',
            'usuarios' => 'Buscar por nombre, correo o número de control...',
            default => 'Buscar en este catálogo...',
        };
    }

    public static function getCatalogosList(): array
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

    public static function getOpcionesFiltros(string $catalogoId): array
    {
        return match ($catalogoId) {
            'areas', 'receptores' => [
                'departamentos' => Departamento::orderBy('nombre')->pluck('nombre', 'id_departamento')->toArray(),
            ],
            'materiales' => [
                'tipos' => TipoMaterial::orderBy('nombre')->pluck('nombre', 'id_tipodematerial')->toArray(),
                'marcas' => MarcaMaterial::orderBy('nombre')->pluck('nombre', 'id_marca')->toArray(),
            ],
            'usuarios' => [
                'roles' => Role::pluck('name', 'name')->toArray(),
            ],
            default => [],
        };
    }

    public static function getDatosCatalogoActivo(string $catalogoActivo, array $filtros = []): array
    {
        $catalogos = self::getCatalogosList();
        $cat = collect($catalogos)->firstWhere('id', $catalogoActivo) ?? $catalogos[0];

        $headers = [];
        $rows = [];
        $crearAccion = 'crear' . self::ENTIDADES_CATALOGO[$cat['id']];
        $indexUrl = $cat['resource']::getUrl('index');
        $term = trim((string) ($filtros['search'] ?? ''));
        $filtroDepto = (string) ($filtros['departamento'] ?? '');
        $filtroTipo = (string) ($filtros['tipo_material'] ?? '');
        $filtroMarca = (string) ($filtros['marca'] ?? '');
        $filtroRol = (string) ($filtros['rol'] ?? '');
        $filtroEstadoUsuario = (string) ($filtros['estado_usuario'] ?? '');

        switch ($cat['id']) {
            case 'departamentos':
                $headers = ['ID', 'Nombre del departamento', 'Encargado', 'Acciones'];
                $deptosQ = Departamento::orderBy('nombre');
                if ($term !== '') {
                    $deptosQ->where(function ($q) use ($term) {
                        $q->where('nombre', 'like', "%{$term}%")
                            ->orWhere('encargado', 'like', "%{$term}%");
                    });
                }
                foreach ($deptosQ->limit(25)->get() as $d) {
                    $rows[] = [
                        '<span class="sigem-td-folio">#' . $d->id_departamento . '</span>',
                        '<strong>' . e($d->nombre) . '</strong>',
                        e($d->encargado ?: '—'),
                        self::renderActionButtons('editarDepartamento', 'verDepartamento', 'eliminarDepartamento', $d->getKey()),
                    ];
                }
                break;

            case 'areas':
                $headers = ['ID', 'Área', 'Departamento', 'Acciones'];
                $areasQ = Area::with('departamento')->orderBy('nombre');
                if ($term !== '') {
                    $areasQ->where(function ($q) use ($term) {
                        $q->where('nombre', 'like', "%{$term}%")
                            ->orWhereHas('departamento', fn ($dq) => $dq->where('nombre', 'like', "%{$term}%"));
                    });
                }
                if ($filtroDepto !== '') {
                    $areasQ->where('id_departamento', $filtroDepto);
                }
                foreach ($areasQ->limit(25)->get() as $a) {
                    $rows[] = [
                        '<span class="sigem-td-folio">#' . $a->id_area . '</span>',
                        '<strong>' . e($a->nombre) . '</strong>',
                        e($a->departamento?->nombre ?? 'N/A'),
                        self::renderActionButtons('editarArea', 'verArea', 'eliminarArea', $a->getKey()),
                    ];
                }
                break;

            case 'materiales':
                $headers = ['Modelo / ID', 'Material / Equipo', 'Marca', 'Tipo', 'Stock', 'Acciones'];
                $materialesQ = Material::withStockDerivado()->with(['marca', 'tipo'])->orderBy('nombre');
                if ($term !== '') {
                    $materialesQ->where(function ($q) use ($term) {
                        $q->where('nombre', 'like', "%{$term}%")
                            ->orWhere('modelo', 'like', "%{$term}%")
                            ->orWhereHas('marca', fn ($mq) => $mq->where('nombre', 'like', "%{$term}%"))
                            ->orWhereHas('tipo', fn ($tq) => $tq->where('nombre', 'like', "%{$term}%"));
                    });
                }
                if ($filtroTipo !== '') {
                    $materialesQ->where('id_tipodematerial', $filtroTipo);
                }
                if ($filtroMarca !== '') {
                    $materialesQ->where('id_marca', $filtroMarca);
                }
                foreach ($materialesQ->limit(25)->get() as $m) {
                    $codigo = $m->modelo ?: ('#' . $m->id_producto);
                    $rows[] = [
                        '<span class="sigem-td-folio">' . e($codigo) . '</span>',
                        '<strong>' . e($m->nombre) . '</strong>',
                        e($m->marca?->nombre ?? 'N/A'),
                        e($m->tipo?->nombre ?? 'N/A'),
                        '<span class="sigem-pill ok">' . $m->stock_actual . '</span>',
                        self::renderActionButtons('editarMaterial', 'verMaterial', 'eliminarMaterial', $m->getKey()),
                    ];
                }
                break;

            case 'marcas':
                $headers = ['ID', 'Marca de material', 'Acciones'];
                $marcasQ = MarcaMaterial::orderBy('nombre');
                if ($term !== '') {
                    $marcasQ->where('nombre', 'like', "%{$term}%");
                }
                foreach ($marcasQ->limit(25)->get() as $mc) {
                    $rows[] = [
                        '<span class="sigem-td-folio">#' . $mc->id_marca . '</span>',
                        '<strong>' . e($mc->nombre) . '</strong>',
                        self::renderActionButtons('editarMarca', 'verMarca', 'eliminarMarca', $mc->getKey()),
                    ];
                }
                break;

            case 'tipos':
                $headers = ['ID', 'Tipo de material', 'Familia', 'Acciones'];
                $tiposQ = TipoMaterial::orderBy('nombre');
                if ($term !== '') {
                    $tiposQ->where(function ($q) use ($term) {
                        $q->where('nombre', 'like', "%{$term}%")
                            ->orWhere('familia', 'like', "%{$term}%");
                    });
                }
                foreach ($tiposQ->limit(25)->get() as $t) {
                    $familiaCell = $t->familia
                        ? '<span class="sigem-pill info">' . e($t->familia) . '</span>'
                        : '—';
                    $rows[] = [
                        '<span class="sigem-td-folio">#' . $t->id_tipodematerial . '</span>',
                        '<strong>' . e($t->nombre) . '</strong>',
                        $familiaCell,
                        self::renderActionButtons('editarTipo', 'verTipo', 'eliminarTipo', $t->getKey()),
                    ];
                }
                break;

            case 'unidades':
                $headers = ['ID', 'Unidad de medida', 'Acciones'];
                $unidadesQ = UnidadMedida::orderBy('nombre');
                if ($term !== '') {
                    $unidadesQ->where('nombre', 'like', "%{$term}%");
                }
                foreach ($unidadesQ->limit(25)->get() as $u) {
                    $rows[] = [
                        '<span class="sigem-td-folio">#' . $u->id_unidad . '</span>',
                        '<strong>' . e($u->nombre) . '</strong>',
                        self::renderActionButtons('editarUnidad', 'verUnidad', 'eliminarUnidad', $u->getKey()),
                    ];
                }
                break;

            case 'proveedores':
                $headers = ['ID', 'Proveedor', 'RFC', 'Contacto', 'Dirección', 'Acciones'];
                $proveedoresQ = Proveedor::orderBy('nombre_empresa');
                if ($term !== '') {
                    $proveedoresQ->where(function ($q) use ($term) {
                        $q->where('nombre_empresa', 'like', "%{$term}%")
                            ->orWhere('rfc', 'like', "%{$term}%")
                            ->orWhere('direccion', 'like', "%{$term}%")
                            ->orWhere('contacto_nombre', 'like', "%{$term}%")
                            ->orWhere('contacto_email', 'like', "%{$term}%");
                    });
                }
                foreach ($proveedoresQ->limit(25)->get() as $p) {
                    $contacto = trim(($p->contacto_nombre ? $p->contacto_nombre . ' · ' : '') . ($p->contacto_telefono ?? '') . ' ' . ($p->contacto_email ?? ''));
                    $rows[] = [
                        '<span class="sigem-td-folio">#' . $p->id_proveedor . '</span>',
                        '<strong>' . e($p->nombre_empresa) . '</strong>',
                        e($p->rfc ?? 'N/A'),
                        e($contacto ?: 'Sin datos'),
                        e($p->direccion ?: '—'),
                        self::renderActionButtons('editarProveedor', 'verProveedor', 'eliminarProveedor', $p->getKey()),
                    ];
                }
                break;

            case 'receptores':
                $headers = ['ID', 'Receptor', 'Tipo', 'No. Control', 'Departamento', 'Área', 'Contacto', 'Acciones'];
                $receptoresQ = Receptor::with('area.departamento')->orderBy('nombre');
                if ($term !== '') {
                    $receptoresQ->where(function ($q) use ($term) {
                        $q->where('nombre', 'like', "%{$term}%")
                            ->orWhere('apellido_paterno', 'like', "%{$term}%")
                            ->orWhere('tipo', 'like', "%{$term}%")
                            ->orWhere('num_control', 'like', "%{$term}%")
                            ->orWhere('cargo', 'like', "%{$term}%")
                            ->orWhere('email', 'like', "%{$term}%")
                            ->orWhereHas('area', fn ($aq) => $aq->where('nombre', 'like', "%{$term}%"));
                    });
                }
                if ($filtroDepto !== '') {
                    $receptoresQ->whereHas('area', fn ($aq) => $aq->where('id_departamento', $filtroDepto));
                }
                foreach ($receptoresQ->limit(25)->get() as $r) {
                    $nombreCompleto = trim("{$r->nombre} {$r->apellido_paterno} {$r->apellido_materno}");
                    $contacto = trim(($r->telefono ?? '') . ' ' . ($r->email ?? ''));
                    $tipoPill = $r->tipo ? '<span class="sigem-pill info">' . e($r->tipo) . '</span>' : '—';
                    $rows[] = [
                        '<span class="sigem-td-folio">#' . $r->id_receptor . '</span>',
                        '<strong>' . e($nombreCompleto ?: $r->nombre) . '</strong>',
                        $tipoPill,
                        e($r->num_control ?: '—'),
                        e($r->area?->departamento?->nombre ?? '—'),
                        e($r->area?->nombre ?? 'N/A'),
                        e($contacto ?: 'Sin datos'),
                        self::renderActionButtons('editarReceptor', 'verReceptor', 'eliminarReceptor', $r->getKey()),
                    ];
                }
                break;

            case 'usuarios':
                $headers = ['ID', 'Usuario', 'Correo electrónico', 'No. control', 'Rol', 'Estado', 'Acciones'];
                $usuariosQ = User::with('roles')->orderBy('name');
                if ($term !== '') {
                    $usuariosQ->where(function ($q) use ($term) {
                        $q->where('name', 'like', "%{$term}%")
                            ->orWhere('email', 'like', "%{$term}%")
                            ->orWhere('num_control', 'like', "%{$term}%");
                    });
                }
                if ($filtroRol !== '') {
                    $usuariosQ->whereHas('roles', fn ($rq) => $rq->where('name', $filtroRol));
                }
                if ($filtroEstadoUsuario !== '') {
                    $usuariosQ->where('activo', (bool) $filtroEstadoUsuario);
                }
                foreach ($usuariosQ->limit(25)->get() as $u) {
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
                        self::renderActionButtons('editarUsuario', 'verUsuario', 'eliminarUsuario', $u->getKey(), $puedeEliminar),
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

    public static function renderActionButtons(string $accionEditar, string $accionVer, string $accionEliminar, int|string $id, bool $puedeEliminar = true): string
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
