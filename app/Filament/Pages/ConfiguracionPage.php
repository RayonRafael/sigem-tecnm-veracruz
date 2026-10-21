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

    protected static ?string $title = 'Configuración & Catálogos';

    protected static string $view = 'filament.pages.configuracion';

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
        $createUrl = $cat['resource']::getUrl('create');
        $indexUrl = $cat['resource']::getUrl('index');

        // TODO [Fase 10 - Documentado]: Vista previa embebida en pestaña Catálogos; cada catálogo redirige a su ListRecords nativo completo al hacer clic.
        switch ($cat['id']) {
            case 'departamentos':
                $headers = ['ID', 'Nombre del departamento', 'Acciones'];
                $deptos = Departamento::orderBy('nombre')->limit(25)->get();
                foreach ($deptos as $d) {
                    $editUrl = DepartamentoResource::getUrl('edit', ['record' => $d]);
                    $rows[] = [
                        '<span class="sigem-td-folio">#' . $d->id_departamento . '</span>',
                        '<strong>' . e($d->nombre) . '</strong>',
                        '<a href="' . $editUrl . '" class="sigem-rep-btn-sec" style="padding:3px 9px;font-size:13px">Editar</a>',
                    ];
                }
                break;

            case 'areas':
                $headers = ['ID', 'Área', 'Departamento', 'Acciones'];
                $areas = Area::with('departamento')->orderBy('nombre')->limit(25)->get();
                foreach ($areas as $a) {
                    $editUrl = AreaResource::getUrl('edit', ['record' => $a]);
                    $rows[] = [
                        '<span class="sigem-td-folio">#' . $a->id_area . '</span>',
                        '<strong>' . e($a->nombre) . '</strong>',
                        e($a->departamento?->nombre ?? 'N/A'),
                        '<a href="' . $editUrl . '" class="sigem-rep-btn-sec" style="padding:3px 9px;font-size:13px">Editar</a>',
                    ];
                }
                break;

            case 'materiales':
                $headers = ['Modelo / ID', 'Material / Equipo', 'Marca', 'Tipo', 'Stock', 'Acciones'];
                $materiales = Material::with(['marca', 'tipo'])->orderBy('nombre')->limit(25)->get();
                foreach ($materiales as $m) {
                    $editUrl = MaterialResource::getUrl('edit', ['record' => $m]);
                    $codigo = $m->modelo ?: ('#' . $m->id_producto);
                    $rows[] = [
                        '<span class="sigem-td-folio">' . e($codigo) . '</span>',
                        '<strong>' . e($m->nombre) . '</strong>',
                        e($m->marca?->nombre ?? 'N/A'),
                        e($m->tipo?->nombre ?? 'N/A'),
                        '<span class="sigem-pill ok">' . $m->stock_actual . '</span>',
                        '<a href="' . $editUrl . '" class="sigem-rep-btn-sec" style="padding:3px 9px;font-size:13px">Editar</a>',
                    ];
                }
                break;

            case 'marcas':
                $headers = ['ID', 'Marca de material', 'Acciones'];
                $marcas = MarcaMaterial::orderBy('nombre')->limit(25)->get();
                foreach ($marcas as $mc) {
                    $editUrl = MarcaMaterialResource::getUrl('edit', ['record' => $mc]);
                    $rows[] = [
                        '<span class="sigem-td-folio">#' . $mc->id_marca . '</span>',
                        '<strong>' . e($mc->nombre) . '</strong>',
                        '<a href="' . $editUrl . '" class="sigem-rep-btn-sec" style="padding:3px 9px;font-size:13px">Editar</a>',
                    ];
                }
                break;

            case 'tipos':
                $headers = ['ID', 'Tipo de material', 'Acciones'];
                $tipos = TipoMaterial::orderBy('nombre')->limit(25)->get();
                foreach ($tipos as $t) {
                    $editUrl = TipoMaterialResource::getUrl('edit', ['record' => $t]);
                    $rows[] = [
                        '<span class="sigem-td-folio">#' . $t->id_tipodematerial . '</span>',
                        '<strong>' . e($t->nombre) . '</strong>',
                        '<a href="' . $editUrl . '" class="sigem-rep-btn-sec" style="padding:3px 9px;font-size:13px">Editar</a>',
                    ];
                }
                break;

            case 'unidades':
                $headers = ['ID', 'Unidad de medida', 'Acciones'];
                $unidades = UnidadMedida::orderBy('nombre')->limit(25)->get();
                foreach ($unidades as $u) {
                    $editUrl = UnidadMedidaResource::getUrl('edit', ['record' => $u]);
                    $rows[] = [
                        '<span class="sigem-td-folio">#' . $u->id_unidad . '</span>',
                        '<strong>' . e($u->nombre) . '</strong>',
                        '<a href="' . $editUrl . '" class="sigem-rep-btn-sec" style="padding:3px 9px;font-size:13px">Editar</a>',
                    ];
                }
                break;

            case 'proveedores':
                $headers = ['ID', 'Proveedor', 'RFC', 'Contacto', 'Acciones'];
                $proveedores = Proveedor::orderBy('nombre_empresa')->limit(25)->get();
                foreach ($proveedores as $p) {
                    $editUrl = ProveedorResource::getUrl('edit', ['record' => $p]);
                    $contacto = trim(($p->contacto_nombre ? $p->contacto_nombre . ' · ' : '') . ($p->contacto_telefono ?? '') . ' ' . ($p->contacto_email ?? ''));
                    $rows[] = [
                        '<span class="sigem-td-folio">#' . $p->id_proveedor . '</span>',
                        '<strong>' . e($p->nombre_empresa) . '</strong>',
                        e($p->rfc ?? 'N/A'),
                        e($contacto ?: 'Sin datos'),
                        '<a href="' . $editUrl . '" class="sigem-rep-btn-sec" style="padding:3px 9px;font-size:13px">Editar</a>',
                    ];
                }
                break;

            case 'receptores':
                $headers = ['ID', 'Receptor', 'Área', 'Contacto', 'Acciones'];
                $receptores = Receptor::with('area')->orderBy('nombre')->limit(25)->get();
                foreach ($receptores as $r) {
                    $editUrl = ReceptorResource::getUrl('edit', ['record' => $r]);
                    $nombreCompleto = trim("{$r->nombre} {$r->apellido_paterno} {$r->apellido_materno}");
                    $contacto = trim(($r->telefono ?? '') . ' ' . ($r->email ?? ''));
                    $rows[] = [
                        '<span class="sigem-td-folio">#' . $r->id_receptor . '</span>',
                        '<strong>' . e($nombreCompleto ?: $r->nombre) . '</strong>',
                        e($r->area?->nombre ?? 'N/A'),
                        e($contacto ?: 'Sin datos'),
                        '<a href="' . $editUrl . '" class="sigem-rep-btn-sec" style="padding:3px 9px;font-size:13px">Editar</a>',
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
            'createUrl' => $createUrl,
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
}
