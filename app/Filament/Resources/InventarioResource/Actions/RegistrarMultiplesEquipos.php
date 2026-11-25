<?php

namespace App\Filament\Resources\InventarioResource\Actions;

use App\Enums\RoleEnum;
use App\Filament\Resources\InventarioResource;
use App\Models\Area;
use App\Models\Departamento;
use App\Models\Inventario;
use App\Models\MarcaMaterial;
use App\Models\Receptor;
use App\Models\Salon;
use App\Support\SigemUniqueValidator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RegistrarMultiplesEquipos
{
    public static function ejecutar(array $data): Inventario
    {
        $tipoValidar = $data['tipo_equipo'] ?? 'pc';
        $seriesAValidar = [];

        if ($tipoValidar === 'pc') {
            if (! (bool) ($data['especificaciones']['no_tiene_monitor'] ?? false)) {
                $seriesAValidar['especificaciones.monitor_sn'] = $data['especificaciones']['monitor_sn'] ?? null;
            }
            if (! (bool) ($data['especificaciones']['no_tiene_teclado'] ?? false)) {
                $seriesAValidar['especificaciones.teclado_sn'] = $data['especificaciones']['teclado_sn'] ?? null;
            }
            if (! (bool) ($data['especificaciones']['no_tiene_mouse'] ?? false)) {
                $seriesAValidar['especificaciones.mouse_sn'] = $data['especificaciones']['mouse_sn'] ?? null;
            }
            $seriesAValidar['num_serie'] = $data['num_serie'] ?? null;
        } elseif ($tipoValidar === 'aio') {
            if (! (bool) ($data['especificaciones']['no_tiene_teclado'] ?? false)) {
                $seriesAValidar['especificaciones.teclado_sn'] = $data['especificaciones']['teclado_sn'] ?? null;
            }
            if (! (bool) ($data['especificaciones']['no_tiene_mouse'] ?? false)) {
                $seriesAValidar['especificaciones.mouse_sn'] = $data['especificaciones']['mouse_sn'] ?? null;
            }
            $seriesAValidar['num_serie'] = $data['num_serie'] ?? null;
        } else {
            $seriesAValidar['num_serie'] = $data['num_serie'] ?? null;
        }

        $erroresSeries = SigemUniqueValidator::validarSeriesLote($seriesAValidar);
        if (! empty($erroresSeries)) {
            throw ValidationException::withMessages($erroresSeries);
        }

        return DB::transaction(function () use ($data) {
            $tipo = $data['tipo_equipo'] ?? 'pc';
            $estacion = $data['estacion'] ?? null;
            $departamentoId = $data['departamento_id'] ?? null;
            $areaId = $data['area_id'] ?? null;
            $aula = $data['aula'] ?? ($data['especificaciones']['aula'] ?? null);
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
                $marcaObj = SigemUniqueValidator::buscarPorValorNormalizado(MarcaMaterial::class, 'nombre', $clean);
                if ($marcaObj instanceof MarcaMaterial) {
                    if ($marcaObj->trashed()) {
                        $marcaObj->restore();
                    }
                    $clean = $marcaObj->nombre;
                } else {
                    MarcaMaterial::create(['nombre' => $clean]);
                }
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

            $usuarioId = $data['id_usuario']
                ?? auth()->id()
                ?? \App\Models\User::where('email', 'admin@tecnm.edu.mx')->value('id')
                ?? \App\Models\User::first()?->id;

            // Resolver Receptor opcional (se guarda en especificaciones sin alterar esquema BD)
            $receptorId = $data['id_receptor'] ?? ($data['especificaciones']['id_receptor'] ?? null);
            $receptorSpecs = [];
            if (filled($receptorId)) {
                $receptorModel = Receptor::find((int) $receptorId);
                if ($receptorModel) {
                    $receptorSpecs = [
                        'id_receptor' => $receptorModel->id_receptor,
                        'receptor_nombre' => $receptorModel->nombre_completo,
                    ];
                }
            }

            // Regla de negocio: Equipos rentados tienen vigencia máxima de 3 años desde fecha_registro
            $tipoPropiedad = $data['tipo_propiedad'] ?? 'Propio';
            $fechaRegistro = $data['fecha_registro'] ?? now();
            $rentaFechaFinStr = null;

            if ($tipoPropiedad === 'Rentado') {
                $fechaRegCarbon = Carbon::parse($fechaRegistro)->startOfDay();
                $manualFin = $data['renta_fecha_fin']
                    ?? ($data['especificaciones']['renta_fecha_fin'] ?? ($data['fecha_fin_renta'] ?? null));
                $rentaFinCarbon = filled($manualFin)
                    ? Carbon::parse($manualFin)->startOfDay()
                    : $fechaRegCarbon->copy()->addYears(3);

                $rentaFechaFinStr = $rentaFinCarbon->format('Y-m-d');
                $receptorSpecs['renta_fecha_fin'] = $rentaFechaFinStr;
            }

            $baseData = [
                'departamento_id' => $departamentoId,
                'area_id' => $areaId,
                'salon_id' => $salonId,
                'aula' => $aula,
                'estacion' => $estacion,
                'estado' => $data['estado'] ?? 'Disponible',
                'tipo_propiedad' => $tipoPropiedad,
                'fecha_registro' => $fechaRegistro,
                'id_proveedor' => $data['id_proveedor'] ?? null,
                'num_factura' => $data['num_factura'] ?? null,
                'fecha_factura' => $data['fecha_factura'] ?? null,
                'garantia_fecha_fin' => $data['garantia_fecha_fin'] ?? null,
                'garantia_estado' => ! empty($data['garantia_estado']) ? $data['garantia_estado'] : 'sin_garantia',
                'fecha_inicio_renta' => $tipoPropiedad === 'Rentado' ? ($data['fecha_inicio_renta'] ?? $fechaRegistro) : null,
                'fecha_fin_renta' => $rentaFechaFinStr,
                'observaciones_renta' => $tipoPropiedad === 'Rentado' ? ($data['observaciones_renta'] ?? null) : null,
                'id_usuario' => $usuarioId,
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
                    $monSpecs = array_merge([
                        'tipo_equipo' => 'pc',
                        'componente' => 'monitor',
                        'estacion' => $estacion,
                        'aula' => $aula,
                        'marca' => $monMarca,
                    ], $receptorSpecs);
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
                    $tecSpecs = array_merge([
                        'tipo_equipo' => 'pc',
                        'componente' => 'teclado',
                        'estacion' => $estacion,
                        'aula' => $aula,
                        'marca' => $tecMarca,
                    ], $receptorSpecs);
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
                    $mouSpecs = array_merge([
                        'tipo_equipo' => 'pc',
                        'componente' => 'mouse',
                        'estacion' => $estacion,
                        'aula' => $aula,
                        'marca' => $mouMarca,
                    ], $receptorSpecs);
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
                $cpuSpecs = array_merge([
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
                ], $receptorSpecs);

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
                    $tecSpecs = array_merge([
                        'tipo_equipo' => 'aio',
                        'componente' => 'teclado',
                        'estacion' => $estacion,
                        'aula' => $aula,
                        'marca' => $tecMarca,
                    ], $receptorSpecs);
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
                    $mouSpecs = array_merge([
                        'tipo_equipo' => 'aio',
                        'componente' => 'mouse',
                        'estacion' => $estacion,
                        'aula' => $aula,
                        'marca' => $mouMarca,
                    ], $receptorSpecs);
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
                $aioSpecs = array_merge([
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
                ], $receptorSpecs);

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

                $singleSpecs = array_merge([
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
                ], $receptorSpecs);

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
