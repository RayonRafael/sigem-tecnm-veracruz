<?php

namespace App\Filament\Resources\InventarioResource\Actions;

use App\Enums\RoleEnum;
use App\Filament\Resources\InventarioResource;
use App\Models\Area;
use App\Models\Departamento;
use App\Models\Inventario;
use App\Models\MarcaMaterial;
use App\Models\Salon;
use Illuminate\Support\Facades\DB;

class RegistrarMultiplesEquipos
{
    public static function ejecutar(array $data): Inventario
    {
        return DB::transaction(function () use ($data) {
            $tipo = $data['tipo_equipo'] ?? 'pc';
            $estacion = $data['estacion'] ?? null;
            $departamentoId = $data['departamento_id'] ?? null;
            $areaId = $data['area_id'] ?? null;
            $aula = $data['especificaciones']['aula'] ?? null;
            $salonId = $data['salon_id'] ?? null;

            if (empty($salonId) && ! empty($aula)) {
                $salon = Salon::where('nombre', $aula)
                    ->when($areaId, fn ($q) => $q->where('area_id', $areaId))
                    ->first();
                if ($salon) {
                    $salonId = $salon->id;
                }
            }

            // Ubicación física descriptiva
            if (empty($data['ubicacion_fisica'])) {
                $partes = [];
                if ($departamentoId && ($depto = Departamento::find($departamentoId))) {
                    $partes[] = $depto->nombre;
                }
                if ($areaId && ($area = Area::find($areaId))) {
                    $partes[] = $area->nombre;
                }
                if (! empty($aula)) {
                    $partes[] = "Aula {$aula}";
                }
                $ubicacionFisica = ! empty($partes) ? implode(' > ', $partes) : null;
            } else {
                $ubicacionFisica = $data['ubicacion_fisica'];
            }

            // Determinar aprobación según rol / panel
            $isServicioSocial = (function_exists('filament') && filament()->getCurrentPanel()?->getId() === 'servicio-social')
                || (auth()->user()?->hasRole(RoleEnum::SERVICIO_SOCIAL->value) ?? false);

            $aprobado = isset($data['aprobado'])
                ? (bool) $data['aprobado']
                : ! $isServicioSocial;

            $estadoRegistro = $data['estado_registro']
                ?? ($isServicioSocial ? 'Pendiente' : 'Aprobado');

            $aprobadoPor = isset($data['aprobado_por'])
                ? $data['aprobado_por']
                : ($isServicioSocial ? null : auth()->id());

            $fechaAprobacion = $aprobado ? now() : null;

            $limpiarMarca = function (?string $m): ?string {
                if (! filled($m)) return null;
                $clean = trim($m);
                if (str_starts_with($clean, '__NEW__:')) {
                    $clean = trim(substr($clean, 8));
                }
                MarcaMaterial::firstOrCreate(['nombre' => $clean]);
                return $clean;
            };

            $limpiarEspec = function (?string $e): ?string {
                if (! filled($e)) return null;
                $clean = trim($e);
                if (str_starts_with($clean, '__NEW__:')) {
                    $clean = trim(substr($clean, 8));
                }
                InventarioResource::agregarEspecificacionGlobal($clean);
                return $clean;
            };

            $baseData = [
                'departamento_id' => $departamentoId,
                'area_id' => $areaId,
                'salon_id' => $salonId,
                'estacion' => $estacion,
                'estado' => $data['estado'] ?? 'Disponible',
                'tipo_propiedad' => $data['tipo_propiedad'] ?? 'Propio',
                'fecha_registro' => $data['fecha_registro'] ?? now(),
                'id_usuario' => $data['id_usuario'] ?? auth()->id() ?? 1,
                'ubicacion_fisica' => $ubicacionFisica,
                'aprobado' => $aprobado,
                'estado_registro' => $estadoRegistro,
                'aprobado_por' => $aprobadoPor,
                'fecha_aprobacion' => $fechaAprobacion,
            ];

            $registrosCreados = [];
            $primaryRecord = null;

            if ($tipo === 'pc') {
                // 1. Monitor (si no tiene "no tiene" y tiene datos)
                $noTieneMonitor = (bool) ($data['especificaciones']['no_tiene_monitor'] ?? false);
                if (! $noTieneMonitor && filled($data['especificaciones']['monitor_sn'] ?? null)) {
                    $monMarca = $limpiarMarca($data['especificaciones']['monitor_marca'] ?? null);
                    $monSpecsTexto = $limpiarEspec($data['especificaciones']['monitor_specs'] ?? null);
                    $monSpecs = [
                        'tipo_equipo' => 'pc',
                        'componente' => 'monitor',
                        'estacion' => $estacion,
                        'aula' => $aula,
                        'marca' => $monMarca,
                    ];
                    $monitor = Inventario::create(array_merge($baseData, [
                        'subtipo' => 'Monitor',
                        'num_serie' => $data['especificaciones']['monitor_sn'],
                        'modelo' => $data['especificaciones']['monitor_modelo'] ?? null,
                        'especificacion_texto' => $monSpecsTexto,
                        'observaciones_generales' => $data['especificaciones']['monitor_desc'] ?? null,
                        'especificaciones' => $monSpecs,
                    ]));
                    $registrosCreados[] = $monitor;
                }

                // 2. Teclado (si no tiene "no tiene" y tiene datos)
                $noTieneTeclado = (bool) ($data['especificaciones']['no_tiene_teclado'] ?? false);
                if (! $noTieneTeclado && filled($data['especificaciones']['teclado_sn'] ?? null)) {
                    $tecMarca = $limpiarMarca($data['especificaciones']['teclado_marca'] ?? null);
                    $tecSpecsTexto = $limpiarEspec($data['especificaciones']['teclado_specs'] ?? null);
                    $tecSpecs = [
                        'tipo_equipo' => 'pc',
                        'componente' => 'teclado',
                        'estacion' => $estacion,
                        'aula' => $aula,
                        'marca' => $tecMarca,
                    ];
                    $teclado = Inventario::create(array_merge($baseData, [
                        'subtipo' => 'Teclado',
                        'num_serie' => $data['especificaciones']['teclado_sn'],
                        'modelo' => $data['especificaciones']['teclado_modelo'] ?? null,
                        'especificacion_texto' => $tecSpecsTexto,
                        'observaciones_generales' => $data['especificaciones']['teclado_desc'] ?? null,
                        'especificaciones' => $tecSpecs,
                    ]));
                    $registrosCreados[] = $teclado;
                }

                // 3. Mouse (si no tiene "no tiene" y tiene datos)
                $noTieneMouse = (bool) ($data['especificaciones']['no_tiene_mouse'] ?? false);
                if (! $noTieneMouse && filled($data['especificaciones']['mouse_sn'] ?? null)) {
                    $mouMarca = $limpiarMarca($data['especificaciones']['mouse_marca'] ?? null);
                    $mouSpecsTexto = $limpiarEspec($data['especificaciones']['mouse_specs'] ?? null);
                    $mouSpecs = [
                        'tipo_equipo' => 'pc',
                        'componente' => 'mouse',
                        'estacion' => $estacion,
                        'aula' => $aula,
                        'marca' => $mouMarca,
                    ];
                    $mouse = Inventario::create(array_merge($baseData, [
                        'subtipo' => 'Mouse',
                        'num_serie' => $data['especificaciones']['mouse_sn'],
                        'modelo' => $data['especificaciones']['mouse_modelo'] ?? null,
                        'especificacion_texto' => $mouSpecsTexto,
                        'observaciones_generales' => $data['especificaciones']['mouse_desc'] ?? null,
                        'especificaciones' => $mouSpecs,
                    ]));
                    $registrosCreados[] = $mouse;
                }

                // 4. CPU (SIEMPRE se crea para pc)
                $cpuMarca = $limpiarMarca($data['especificaciones']['cpu_marca'] ?? $data['especificaciones']['marca'] ?? null);
                $cpuSpecsTexto = $limpiarEspec($data['especificacion_texto'] ?? null);
                $cpuSpecs = [
                    'tipo_equipo' => 'pc',
                    'componente' => 'cpu',
                    'estacion' => $estacion,
                    'aula' => $aula,
                    'marca' => $cpuMarca,
                    'ip_address' => $data['especificaciones']['ip_address'] ?? $data['ip_address'] ?? null,
                    'mac_address' => $data['especificaciones']['mac_address'] ?? $data['mac_address'] ?? null,
                    'procesador' => $data['especificaciones']['procesador'] ?? $data['procesador'] ?? null,
                    'ram' => $data['especificaciones']['ram'] ?? $data['ram'] ?? null,
                    'disco' => $data['especificaciones']['disco'] ?? $data['disco'] ?? null,
                    'sistema_operativo' => $data['especificaciones']['sistema_operativo'] ?? $data['sistema_operativo'] ?? null,
                    '_total_registrados' => count($registrosCreados) + 1,
                ];

                $cpu = Inventario::create(array_merge($baseData, [
                    'subtipo' => 'CPU',
                    'num_serie' => $data['num_serie'] ?? null,
                    'modelo' => $data['modelo'] ?? null,
                    'especificacion_texto' => $cpuSpecsTexto,
                    'observaciones_generales' => $data['observaciones_generales'] ?? null,
                    'ip_address' => $data['especificaciones']['ip_address'] ?? $data['ip_address'] ?? null,
                    'mac_address' => $data['especificaciones']['mac_address'] ?? $data['mac_address'] ?? null,
                    'procesador' => $data['especificaciones']['procesador'] ?? $data['procesador'] ?? null,
                    'ram' => $data['especificaciones']['ram'] ?? $data['ram'] ?? null,
                    'disco' => $data['especificaciones']['disco'] ?? $data['disco'] ?? null,
                    'sistema_operativo' => $data['especificaciones']['sistema_operativo'] ?? $data['sistema_operativo'] ?? null,
                    'especificaciones' => $cpuSpecs,
                ]));

                $primaryRecord = $cpu;

            } elseif ($tipo === 'aio') {
                // 1. Teclado
                $noTieneTeclado = (bool) ($data['especificaciones']['no_tiene_teclado'] ?? false);
                if (! $noTieneTeclado && filled($data['especificaciones']['teclado_sn'] ?? null)) {
                    $tecMarca = $limpiarMarca($data['especificaciones']['teclado_marca'] ?? null);
                    $tecSpecsTexto = $limpiarEspec($data['especificaciones']['teclado_specs'] ?? null);
                    $tecSpecs = [
                        'tipo_equipo' => 'aio',
                        'componente' => 'teclado',
                        'estacion' => $estacion,
                        'aula' => $aula,
                        'marca' => $tecMarca,
                    ];
                    $teclado = Inventario::create(array_merge($baseData, [
                        'subtipo' => 'Teclado',
                        'num_serie' => $data['especificaciones']['teclado_sn'],
                        'modelo' => $data['especificaciones']['teclado_modelo'] ?? null,
                        'especificacion_texto' => $tecSpecsTexto,
                        'observaciones_generales' => $data['especificaciones']['teclado_desc'] ?? null,
                        'especificaciones' => $tecSpecs,
                    ]));
                    $registrosCreados[] = $teclado;
                }

                // 2. Mouse
                $noTieneMouse = (bool) ($data['especificaciones']['no_tiene_mouse'] ?? false);
                if (! $noTieneMouse && filled($data['especificaciones']['mouse_sn'] ?? null)) {
                    $mouMarca = $limpiarMarca($data['especificaciones']['mouse_marca'] ?? null);
                    $mouSpecsTexto = $limpiarEspec($data['especificaciones']['mouse_specs'] ?? null);
                    $mouSpecs = [
                        'tipo_equipo' => 'aio',
                        'componente' => 'mouse',
                        'estacion' => $estacion,
                        'aula' => $aula,
                        'marca' => $mouMarca,
                    ];
                    $mouse = Inventario::create(array_merge($baseData, [
                        'subtipo' => 'Mouse',
                        'num_serie' => $data['especificaciones']['mouse_sn'],
                        'modelo' => $data['especificaciones']['mouse_modelo'] ?? null,
                        'especificacion_texto' => $mouSpecsTexto,
                        'observaciones_generales' => $data['especificaciones']['mouse_desc'] ?? null,
                        'especificaciones' => $mouSpecs,
                    ]));
                    $registrosCreados[] = $mouse;
                }

                // 3. All in One (SIEMPRE se crea)
                $aioMarca = $limpiarMarca($data['especificaciones']['marca'] ?? null);
                $aioSpecsTexto = $limpiarEspec($data['especificacion_texto'] ?? null);
                $aioSpecs = [
                    'tipo_equipo' => 'aio',
                    'componente' => 'aio',
                    'estacion' => $estacion,
                    'aula' => $aula,
                    'marca' => $aioMarca,
                    'ip_address' => $data['especificaciones']['ip_address'] ?? $data['ip_address'] ?? null,
                    'mac_address' => $data['especificaciones']['mac_address'] ?? $data['mac_address'] ?? null,
                    'procesador' => $data['especificaciones']['procesador'] ?? $data['procesador'] ?? null,
                    'ram' => $data['especificaciones']['ram'] ?? $data['ram'] ?? null,
                    'disco' => $data['especificaciones']['disco'] ?? $data['disco'] ?? null,
                    'sistema_operativo' => $data['especificaciones']['sistema_operativo'] ?? $data['sistema_operativo'] ?? null,
                    '_total_registrados' => count($registrosCreados) + 1,
                ];

                $aio = Inventario::create(array_merge($baseData, [
                    'subtipo' => 'All in One',
                    'num_serie' => $data['num_serie'] ?? null,
                    'modelo' => $data['modelo'] ?? null,
                    'especificacion_texto' => $aioSpecsTexto,
                    'observaciones_generales' => $data['observaciones_generales'] ?? null,
                    'ip_address' => $data['especificaciones']['ip_address'] ?? $data['ip_address'] ?? null,
                    'mac_address' => $data['especificaciones']['mac_address'] ?? $data['mac_address'] ?? null,
                    'procesador' => $data['especificaciones']['procesador'] ?? $data['procesador'] ?? null,
                    'ram' => $data['especificaciones']['ram'] ?? $data['ram'] ?? null,
                    'disco' => $data['especificaciones']['disco'] ?? $data['disco'] ?? null,
                    'sistema_operativo' => $data['especificaciones']['sistema_operativo'] ?? $data['sistema_operativo'] ?? null,
                    'especificaciones' => $aioSpecs,
                ]));

                $primaryRecord = $aio;

            } else {
                // Equipos individuales
                $mapSubtipos = [
                    'lap' => 'Laptop',
                    'cpu' => 'CPU',
                    'mon' => 'Monitor',
                    'tec' => 'Teclado',
                    'mou' => 'Mouse',
                    'imp' => 'Impresora',
                    'pro' => 'Proyector',
                    'nob' => 'No-Break',
                    'swi' => 'Switch de red',
                    'otro' => 'Otro artículo',
                ];
                $subtipo = $mapSubtipos[$tipo] ?? ($data['subtipo'] ?? 'Otro artículo');
                $marca = $limpiarMarca($data['especificaciones']['marca'] ?? null);
                $specsTexto = $limpiarEspec($data['especificacion_texto'] ?? null);

                $singleSpecs = [
                    'tipo_equipo' => $tipo,
                    'componente' => $tipo,
                    'estacion' => $estacion,
                    'aula' => $aula,
                    'marca' => $marca,
                    'num_puertos' => $data['especificaciones']['num_puertos'] ?? null,
                    'ip_address' => $data['especificaciones']['ip_address'] ?? $data['ip_address'] ?? null,
                    'mac_address' => $data['especificaciones']['mac_address'] ?? $data['mac_address'] ?? null,
                    'procesador' => $data['especificaciones']['procesador'] ?? $data['procesador'] ?? null,
                    'ram' => $data['especificaciones']['ram'] ?? $data['ram'] ?? null,
                    'disco' => $data['especificaciones']['disco'] ?? $data['disco'] ?? null,
                    'sistema_operativo' => $data['especificaciones']['sistema_operativo'] ?? $data['sistema_operativo'] ?? null,
                    '_total_registrados' => 1,
                ];

                $itemData = array_merge($baseData, [
                    'subtipo' => $subtipo,
                    'num_serie' => $data['num_serie'] ?? null,
                    'modelo' => $data['modelo'] ?? null,
                    'especificacion_texto' => $specsTexto,
                    'observaciones_generales' => $data['observaciones_generales'] ?? null,
                    'tipo_impresora' => $data['tipo_impresora'] ?? null,
                    'ip_address' => $data['especificaciones']['ip_address'] ?? $data['ip_address'] ?? null,
                    'mac_address' => $data['especificaciones']['mac_address'] ?? $data['mac_address'] ?? null,
                    'procesador' => $data['especificaciones']['procesador'] ?? $data['procesador'] ?? null,
                    'ram' => $data['especificaciones']['ram'] ?? $data['ram'] ?? null,
                    'disco' => $data['especificaciones']['disco'] ?? $data['disco'] ?? null,
                    'sistema_operativo' => $data['especificaciones']['sistema_operativo'] ?? $data['sistema_operativo'] ?? null,
                    'especificaciones' => $singleSpecs,
                ]);

                if ($tipo === 'otro' && ! empty($data['id_producto'])) {
                    $itemData['id_producto'] = $data['id_producto'];
                }

                $primaryRecord = Inventario::create($itemData);
            }

            return $primaryRecord;
        });
    }
}
