<?php

namespace App\Livewire;

use App\Filament\Pages\ConfiguracionPage;
use App\Filament\Pages\ReportesPage;
use App\Filament\Resources\InventarioResource;
use App\Filament\Resources\MantenimientoResource;
use App\Filament\Resources\SolicitudResource;
use App\Models\Inventario;
use App\Models\Mantenimiento;
use App\Models\Solicitud;
use Livewire\Attributes\On;
use Livewire\Component;

class CommandPalette extends Component
{
    public bool $abierto = false;

    public string $q = '';

    public int $sel = 0;

    #[On('abrir-command-palette')]
    public function abrir(): void
    {
        $this->abierto = true;
        $this->q = '';
        $this->sel = 0;
    }

    #[On('cerrar-command-palette')]
    public function cerrar(): void
    {
        $this->abierto = false;
    }

    public function updatedQ(): void
    {
        $this->sel = 0;
    }

    protected function normalize(string $text): string
    {
        return trim(strtr(
            mb_strtolower($text, 'UTF-8'),
            [
                'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n',
                'Á' => 'a', 'É' => 'e', 'Í' => 'i', 'Ó' => 'o', 'Ú' => 'u', 'Ü' => 'u', 'Ñ' => 'n',
            ]
        ));
    }

    protected function matches(string $source, string $term): bool
    {
        $srcNorm = $this->normalize($source);
        $termNorm = $this->normalize($term);

        if ($termNorm === '') {
            return true;
        }

        return str_contains($srcNorm, $termNorm);
    }

    protected function getSearchTerms(string $term): array
    {
        $term = trim($term);
        if ($term === '') {
            return [];
        }

        $clean = $this->normalize($term);
        $terms = [$term, $clean, mb_strtolower($term, 'UTF-8')];

        // Generar variantes de acentos comunes para SQLite/MySQL
        foreach (['a' => 'á', 'e' => 'é', 'i' => 'í', 'o' => 'ó', 'u' => 'ú', 'n' => 'ñ'] as $sin => $con) {
            if (str_contains($clean, $sin)) {
                $terms[] = str_replace($sin, $con, $clean);
            }
        }

        return array_values(array_unique(array_filter($terms)));
    }

    protected function isServicioSocial(): bool
    {
        return filament()->getCurrentPanel()?->getId() === 'servicio-social' || request()->is('servicio-social*');
    }

    public function getSecciones(): array
    {
        if ($this->isServicioSocial()) {
            return [
                [
                    'titulo' => 'Inventario general',
                    'subtitulo' => 'Consulta de activos del instituto',
                    'icono' => '📦',
                    'url' => \App\Filament\ServicioSocial\Resources\InventarioResource::getUrl('index'),
                    'keywords' => 'inventario general activos equipos productos stock series',
                ],
                [
                    'titulo' => 'Solicitudes',
                    'subtitulo' => 'Mis peticiones de material y asignaciones',
                    'icono' => '📋',
                    'url' => \App\Filament\ServicioSocial\Resources\SolicitudResource::getUrl('index'),
                    'keywords' => 'solicitudes pedidos peticiones asignacion folios',
                ],
                [
                    'titulo' => 'Mantenimiento',
                    'subtitulo' => 'Mis órdenes de servicio y reparación',
                    'icono' => '🔧',
                    'url' => \App\Filament\ServicioSocial\Resources\MantenimientoResource::getUrl('index'),
                    'keywords' => 'mantenimiento servicio reparacion falla preventivo correctivo tecnico',
                ],
                [
                    'titulo' => 'Reportes',
                    'subtitulo' => 'Métricas, estadísticas y reportes autorizados',
                    'icono' => '📊',
                    'url' => \App\Filament\ServicioSocial\Pages\ReportesPage::getUrl(),
                    'keywords' => 'reportes metricas graficas estadisticas autorizados',
                ],
                [
                    'titulo' => 'Catálogos',
                    'subtitulo' => 'Consulta de catálogos base en modo lectura',
                    'icono' => '📁',
                    'url' => \App\Filament\ServicioSocial\Pages\CatalogosPage::getUrl(),
                    'keywords' => 'catalogos departamentos areas materiales proveedores marcas tipos unidades receptores solo lectura',
                ],
            ];
        }

        return [
            [
                'titulo' => 'Inventario general',
                'subtitulo' => 'Consulta y gestión de activos del instituto',
                'icono' => '📦',
                'url' => InventarioResource::getUrl('index'),
                'keywords' => 'inventario general activos equipos productos stock series',
            ],
            [
                'titulo' => 'Solicitudes',
                'subtitulo' => 'Peticiones de material, asignaciones y rentas',
                'icono' => '📋',
                'url' => SolicitudResource::getUrl('index'),
                'keywords' => 'solicitudes pedidos peticiones asignacion renta devolucion folios',
            ],
            [
                'titulo' => 'Mantenimiento',
                'subtitulo' => 'Órdenes de servicio preventivo y correctivo',
                'icono' => '🔧',
                'url' => MantenimientoResource::getUrl('index'),
                'keywords' => 'mantenimiento servicio reparacion falla preventivo correctivo tecnico',
            ],
            [
                'titulo' => 'Reportes',
                'subtitulo' => 'Métricas, estadísticas y exportación de datos',
                'icono' => '📊',
                'url' => ReportesPage::getUrl(),
                'keywords' => 'reportes metricas graficas estadisticas informes exportar',
            ],
            [
                'titulo' => 'Configuración y Catálogos',
                'subtitulo' => 'Catálogos base, bitácora y preferencias',
                'icono' => '⚙️',
                'url' => ConfiguracionPage::getUrl(),
                'keywords' => 'configuracion catalogos bitacora preferencias ajustes areas departamentos materiales proveedores',
            ],
        ];
    }

    public function getGruposResultadosProperty(): array
    {
        $query = trim($this->q);
        $grupos = [];

        // 1. Grupo IR A (secciones)
        $secciones = $this->getSecciones();
        if ($query === '') {
            $grupos['IR A'] = $secciones;

            return $grupos;
        }

        $seccionesFiltradas = array_values(array_filter($secciones, function ($s) use ($query) {
            return $this->matches($s['titulo'] . ' ' . $s['subtitulo'] . ' ' . $s['keywords'], $query);
        }));

        if (! empty($seccionesFiltradas)) {
            $grupos['IR A'] = $seccionesFiltradas;
        }

        $terms = $this->getSearchTerms($query);

        // 2. Grupo EQUIPOS (Inventario)
        $equipos = Inventario::with(['material'])
            ->where(function ($sub) use ($terms) {
                foreach ($terms as $t) {
                    $sub->orWhere('num_serie', 'like', "%{$t}%")
                        ->orWhere('ubicacion_fisica', 'like', "%{$t}%")
                        ->orWhereHas('material', function ($mq) use ($t) {
                            $mq->where('nombre', 'like', "%{$t}%")
                                ->orWhere('modelo', 'like', "%{$t}%")
                                ->orWhere('descripcion', 'like', "%{$t}%");
                        });
                }
            })
            ->limit(6)
            ->get();

        if ($equipos->isNotEmpty()) {
            $isSS = $this->isServicioSocial();
            $grupos['EQUIPOS'] = $equipos->map(function ($i) use ($isSS) {
                $codigo = $i->num_serie ?: 'S/N';
                $nombre = $i->material?->nombre ?? 'Equipo';
                $estado = $i->estado ?? 'Sin estado';
                $area = $i->ubicacion_fisica ?: 'Sin área asignada';

                $url = $isSS
                    ? \App\Filament\ServicioSocial\Resources\InventarioResource::getUrl('index') . '?tableSearch=' . urlencode($codigo)
                    : InventarioResource::getUrl('edit', ['record' => $i]);

                return [
                    'titulo' => "{$codigo} · {$nombre}",
                    'subtitulo' => "{$estado} · {$area}",
                    'icono' => '💻',
                    'url' => $url,
                ];
            })->all();
        }

        // 3. Grupo SOLICITUDES
        $solicitudesQuery = Solicitud::with(['usuario', 'receptor'])
            ->where(function ($sub) use ($terms) {
                foreach ($terms as $t) {
                    $sub->orWhere('id_solicitud', 'like', "%{$t}%")
                        ->orWhere('tipo_movimiento', 'like', "%{$t}%")
                        ->orWhere('estado', 'like', "%{$t}%")
                        ->orWhereHas('usuario', function ($uq) use ($t) {
                            $uq->where('name', 'like', "%{$t}%");
                        })
                        ->orWhereHas('receptor', function ($rq) use ($t) {
                            $rq->where('nombre', 'like', "%{$t}%");
                        });
                }
            });

        if ($this->isServicioSocial()) {
            $solicitudesQuery->where('id_usuario', auth()->id());
        }

        $solicitudes = $solicitudesQuery->limit(6)->get();

        if ($solicitudes->isNotEmpty()) {
            $isSS = $this->isServicioSocial();
            $grupos['SOLICITUDES'] = $solicitudes->map(function ($s) use ($isSS) {
                $folio = 'FOLIO-' . str_pad($s->id_solicitud, 5, '0', STR_PAD_LEFT);
                $tipo = $s->tipo_movimiento ?? 'Solicitud';
                $estado = $s->estado ?? 'Pendiente';
                $fecha = $s->fecha_solicitud ? $s->fecha_solicitud->format('d/m/Y') : '';
                $sub = "{$tipo} · {$estado}" . ($fecha ? " · {$fecha}" : '');

                $url = $isSS
                    ? \App\Filament\ServicioSocial\Resources\SolicitudResource::getUrl('index') . '?tableSearch=' . urlencode($folio)
                    : SolicitudResource::getUrl('edit', ['record' => $s]);

                return [
                    'titulo' => $folio,
                    'subtitulo' => $sub,
                    'icono' => '📄',
                    'url' => $url,
                ];
            })->all();
        }

        // 4. Grupo MANTENIMIENTOS
        $mantenimientosQuery = Mantenimiento::with(['inventario.material'])
            ->where(function ($sub) use ($terms) {
                foreach ($terms as $t) {
                    $sub->orWhere('id_mantenimiento', 'like', "%{$t}%")
                        ->orWhere('tipo_servicio', 'like', "%{$t}%")
                        ->orWhere('tipo_mantenimiento', 'like', "%{$t}%")
                        ->orWhere('estado', 'like', "%{$t}%")
                        ->orWhere('nombre_tecnico', 'like', "%{$t}%")
                        ->orWhereHas('inventario', function ($iq) use ($t) {
                            $iq->where('num_serie', 'like', "%{$t}%")
                                ->orWhereHas('material', function ($mq) use ($t) {
                                    $mq->where('nombre', 'like', "%{$t}%");
                                });
                        });
                }
            });

        if ($this->isServicioSocial()) {
            $mantenimientosQuery->where('id_usuario_solicita', auth()->id());
        }

        $mantenimientos = $mantenimientosQuery->limit(6)->get();

        if ($mantenimientos->isNotEmpty()) {
            $isSS = $this->isServicioSocial();
            $grupos['MANTENIMIENTOS'] = $mantenimientos->map(function ($m) use ($isSS) {
                $equipo = $m->inventario?->material?->nombre ?? ($m->inventario?->num_serie ?? 'Equipo');
                $folio = "#{$m->id_mantenimiento} · {$equipo}";
                $tipo = $m->tipo_mantenimiento ?? 'Mantenimiento';
                $estado = $m->estado === 'Pendiente Revision Admin' ? 'En revisión' : ($m->estado ?? 'Solicitado');

                $url = $isSS
                    ? \App\Filament\ServicioSocial\Resources\MantenimientoResource::getUrl('index')
                    : MantenimientoResource::getUrl('edit', ['record' => $m]);

                return [
                    'titulo' => $folio,
                    'subtitulo' => "{$tipo} · {$estado}",
                    'icono' => '🛠️',
                    'url' => $url,
                ];
            })->all();
        }

        return $grupos;
    }

    public function render()
    {
        return view('livewire.command-palette', [
            'grupos' => $this->gruposResultados,
        ]);
    }
}
