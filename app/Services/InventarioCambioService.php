<?php

namespace App\Services;

use App\Enums\EstadoInventario;
use App\Filament\Concerns\ConModalesBajaCambio;
use App\Models\Area;
use App\Models\Departamento;
use App\Models\HistorialEstado;
use App\Models\Inventario;
use App\Models\Mantenimiento;
use App\Models\Proveedor;
use App\Models\Receptor;
use App\Models\User;
use App\Support\UbicacionHelper;
use Illuminate\Support\Facades\DB;

class InventarioCambioService
{
    public static function resolverUsuarioId(): ?int
    {
        return auth()->id()
            ?? User::where('email', 'admin@tecnm.edu.mx')->value('id')
            ?? User::first()?->id;
    }

    /**
     * @return array{aplicados: int, omitidos: int}
     */
    public static function aplicarModificarTodos(array $selectedIds, array $data, bool $esAdmin, ?int $userId = null, ?string $fallbackValor = null): array
    {
        $userId ??= self::resolverUsuarioId();
        $campo = $data['campo_a_modificar'];
        $aplicados = 0;
        $omitidos = 0;

        DB::transaction(function () use ($selectedIds, $data, $campo, $esAdmin, $userId, $fallbackValor, &$aplicados) {
            $records = Inventario::whereIn('id_inventario', $selectedIds)->get();

            foreach ($records as $record) {
                $estadoAnterior = $record->estado ?? EstadoInventario::Disponible->value;

                if ($campo === 'estado') {
                    $nuevoEstado = $data['nuevo_estado'];

                    if ($nuevoEstado === EstadoInventario::DadoDeBaja->value) {
                        if ($esAdmin) {
                            $record->darBajaDirecta('Modificación masiva', 'Actualización de estado masiva', $userId);
                        } else {
                            $record->solicitarBaja('Modificación masiva', 'Actualización de estado masiva', $userId);
                        }
                    } else {
                        $record->update(['estado' => $nuevoEstado]);

                        HistorialEstado::create([
                            'id_inventario' => $record->id_inventario,
                            'id_usuario' => $userId,
                            'estado_anterior' => $estadoAnterior,
                            'estado_nuevo' => $nuevoEstado,
                            'motivo_cambio' => "Modificación masiva de estado a {$nuevoEstado}",
                            'ubicacion_fisica' => $record->ubicacion_fisica,
                            'fecha_cambio' => now(),
                        ]);
                    }
                    $aplicados++;
                } elseif ($campo === 'area') {
                    $areaId = $data['nueva_area_id'];
                    $area = Area::find($areaId);
                    $deptoId = $area?->id_departamento ?? $record->departamento_id;

                    $record->departamento_id = $deptoId;
                    $record->area_id = $areaId;
                    $ubicacionNueva = UbicacionHelper::construir($deptoId, $areaId, $record->aula ?: $record->salon_id, null);
                    if ($ubicacionNueva) {
                        $record->ubicacion_fisica = substr($ubicacionNueva, 0, 150);
                    }
                    $record->save();

                    HistorialEstado::create([
                        'id_inventario' => $record->id_inventario,
                        'id_usuario' => $userId,
                        'estado_anterior' => $estadoAnterior,
                        'estado_nuevo' => $record->estado,
                        'motivo_cambio' => 'Modificación masiva de área: ' . ($area?->nombre ?? 'Nueva área'),
                        'ubicacion_fisica' => $record->ubicacion_fisica,
                        'fecha_cambio' => now(),
                    ]);
                    $aplicados++;
                } elseif ($campo === 'departamento') {
                    $deptoId = $data['nuevo_departamento_id'];
                    $depto = Departamento::find($deptoId);

                    $record->departamento_id = $deptoId;
                    $ubicacionNueva = UbicacionHelper::construir($deptoId, $record->area_id, $record->aula ?: $record->salon_id, null);
                    if ($ubicacionNueva) {
                        $record->ubicacion_fisica = substr($ubicacionNueva, 0, 150);
                    }
                    $record->save();

                    HistorialEstado::create([
                        'id_inventario' => $record->id_inventario,
                        'id_usuario' => $userId,
                        'estado_anterior' => $estadoAnterior,
                        'estado_nuevo' => $record->estado,
                        'motivo_cambio' => 'Modificación masiva de departamento: ' . ($depto?->nombre ?? 'Nuevo depto'),
                        'ubicacion_fisica' => $record->ubicacion_fisica,
                        'fecha_cambio' => now(),
                    ]);
                    $aplicados++;
                } elseif ($campo === 'ubicacion_fisica') {
                    $ubicacion = $data['nueva_ubicacion_fisica'];
                    $record->update(['ubicacion_fisica' => substr($ubicacion, 0, 150)]);

                    HistorialEstado::create([
                        'id_inventario' => $record->id_inventario,
                        'id_usuario' => $userId,
                        'estado_anterior' => $estadoAnterior,
                        'estado_nuevo' => $record->estado,
                        'motivo_cambio' => "Modificación masiva de ubicación: {$ubicacion}",
                        'ubicacion_fisica' => $record->ubicacion_fisica,
                        'fecha_cambio' => now(),
                    ]);
                    $aplicados++;
                } elseif ($campo === 'tipo_propiedad') {
                    $nuevoTipo = $data['nuevo_tipo_propiedad'] ?? $fallbackValor;
                    if (in_array($nuevoTipo, ['Propio', 'Rentado'], true)) {
                        $propiedadAnt = $record->tipo_propiedad ?? 'Propio';
                        $record->update(['tipo_propiedad' => $nuevoTipo]);

                        HistorialEstado::create([
                            'id_inventario' => $record->id_inventario,
                            'id_usuario' => $userId,
                            'estado_anterior' => $estadoAnterior,
                            'estado_nuevo' => $record->estado,
                            'motivo_cambio' => "Modificación masiva de propiedad: {$propiedadAnt} → {$nuevoTipo}",
                            'ubicacion_fisica' => $record->ubicacion_fisica,
                            'fecha_cambio' => now(),
                        ]);
                        $aplicados++;
                    }
                }
            }
        });

        return ['aplicados' => $aplicados, 'omitidos' => $omitidos];
    }

    /**
     * @return array{aplicados: int, omitidos: int}
     */
    public static function aplicarCambioMasivo(array $selectedIds, array $data, bool $esAdmin, ?int $userId = null): array
    {
        $userId ??= self::resolverUsuarioId();

        $nuevoEstado = ! empty($data['nuevo_estado']) ? $data['nuevo_estado'] : null;
        if ($nuevoEstado === 'Daño') {
            $nuevoEstado = EstadoInventario::Danado->value;
        }

        $nuevoDeptoId = ! empty($data['nuevo_departamento_id']) ? $data['nuevo_departamento_id'] : null;
        $nuevaAreaId = ! empty($data['nueva_area_id']) ? $data['nueva_area_id'] : null;
        $nuevaAula = ! empty($data['nueva_aula']) ? trim($data['nueva_aula']) : null;
        $nuevoReceptorId = ! empty($data['nuevo_receptor_id']) ? $data['nuevo_receptor_id'] : null;
        $motivoBaja = ! empty($data['motivo_baja']) ? $data['motivo_baja'] : 'Baja masiva';
        $descBaja = ! empty($data['descripcion_baja']) ? $data['descripcion_baja'] : 'Baja masiva';
        $descDano = ! empty($data['descripcion_dano']) ? $data['descripcion_dano'] : null;
        $nivelDano = ! empty($data['nivel_dano']) ? $data['nivel_dano'] : 'Menor';
        $tipoServicio = ! empty($data['tipo_servicio']) ? $data['tipo_servicio'] : 'Preventivo';
        $descFalla = ! empty($data['descripcion_falla']) ? $data['descripcion_falla'] : 'Falla reportada en cambio masivo';
        $proveedorId = ! empty($data['proveedor_id']) ? $data['proveedor_id'] : null;
        $motivoDevolucion = ! empty($data['motivo_devolucion']) ? $data['motivo_devolucion'] : 'Devolución masiva';
        $nuevoTipoPropiedad = ! empty($data['nuevo_tipo_propiedad']) ? $data['nuevo_tipo_propiedad'] : null;
        $motivo = ! empty($data['motivo']) ? $data['motivo'] : 'Cambio masivo';

        $estadosSinTipoPropiedad = [
            EstadoInventario::DadoDeBaja->value,
            EstadoInventario::SolicitudDeBaja->value,
            EstadoInventario::EnMantenimiento->value,
            EstadoInventario::Danado->value,
            EstadoInventario::DevueltoAProveedor->value,
            'Baja',
            'Daño',
        ];
        if ($nuevoEstado && in_array($nuevoEstado, $estadosSinTipoPropiedad, true)) {
            $nuevoTipoPropiedad = null;
        }

        $equipos = Inventario::whereIn('id_inventario', $selectedIds)->get();
        $propiedades = $equipos->pluck('tipo_propiedad')->unique()->filter();
        if ($propiedades->count() === 0) {
            $propiedadActual = 'Sin definir';
        } elseif ($propiedades->count() === 1) {
            $propiedadActual = $propiedades->first();
        } else {
            $propiedadActual = 'Mixto';
        }

        $aplicados = 0;
        $omitidos = 0;

        DB::transaction(function () use (
            $selectedIds, $esAdmin, $userId, $nuevoEstado, $nuevoDeptoId, $nuevaAreaId, $nuevaAula,
            $nuevoReceptorId, $motivoBaja, $descBaja, $descDano, $nivelDano,
            $tipoServicio, $descFalla, $proveedorId, $motivoDevolucion, $nuevoTipoPropiedad,
            $motivo, $propiedadActual, &$aplicados, &$omitidos
        ) {
            $records = Inventario::whereIn('id_inventario', $selectedIds)->get();

            foreach ($records as $record) {
                if (in_array($record->estado, [
                    EstadoInventario::DadoDeBaja->value,
                    EstadoInventario::SolicitudDeBaja->value,
                    EstadoInventario::CambioDeArea->value,
                    'Baja',
                ], true)) {
                    $omitidos++;
                    continue;
                }

                $estadoAnterior = $record->estado ?? EstadoInventario::Disponible->value;

                $areaDestino = $nuevaAreaId;
                $deptoDestino = $nuevoDeptoId;
                $aulaDestino = $nuevaAula;

                if ($areaDestino) {
                    $areaModel = Area::find($areaDestino);
                    if (! $deptoDestino && $areaModel) {
                        $deptoDestino = $areaModel->id_departamento;
                    }
                }

                if ($nuevoEstado === 'Cambio de propiedad') {
                    if ($nuevoTipoPropiedad) {
                        $record->update(['tipo_propiedad' => $nuevoTipoPropiedad]);
                        HistorialEstado::create([
                            'id_inventario' => $record->id_inventario,
                            'id_usuario' => $userId,
                            'estado_anterior' => $record->estado,
                            'estado_nuevo' => $record->estado,
                            'motivo_cambio' => "Cambio de propiedad de {$propiedadActual} a: {$nuevoTipoPropiedad}. {$motivo}",
                            'ubicacion_fisica' => $record->ubicacion_fisica,
                            'fecha_cambio' => now(),
                        ]);
                    }
                } elseif ($nuevoEstado === EstadoInventario::CambioDeArea->value) {
                    $deptoFinal = $deptoDestino ?: $record->departamento_id;
                    $areaFinal = $areaDestino ?: $record->area_id;

                    if ($esAdmin) {
                        $record->cambiarAreaDirecto($deptoFinal, $areaFinal, null, null, $motivo, $userId);
                    } else {
                        $record->solicitarCambioArea($deptoFinal, $areaFinal, null, null, $motivo, $userId);
                    }

                    $specs = is_array($record->especificaciones) ? $record->especificaciones : [];
                    $specs['cambio_origen_depto'] = $record->departamento?->nombre;
                    $specs['cambio_origen_area'] = $record->area?->nombre;
                    $specs['cambio_destino_depto'] = Departamento::find($deptoFinal)?->nombre;
                    $specs['cambio_destino_area'] = Area::find($areaFinal)?->nombre;
                    if ($aulaDestino) {
                        $specs['cambio_nueva_aula'] = $aulaDestino;
                        $specs['aula'] = $aulaDestino;
                        $record->aula = $aulaDestino;
                    }
                    $specs['cambio_motivo'] = $motivo;
                    $specs['fecha_cambio'] = now()->toDateString();
                    $record->salon_id = null;
                    $record->especificaciones = $specs;
                    $record->save();
                } elseif ($nuevoEstado === EstadoInventario::DadoDeBaja->value) {
                    if ($esAdmin) {
                        $record->darBajaDirecta($motivoBaja, $descBaja, $userId);
                    } else {
                        $record->solicitarBaja($motivoBaja, $descBaja, $userId);
                    }
                } elseif ($nuevoEstado === EstadoInventario::SolicitudDeBaja->value) {
                    $record->solicitarBaja($motivoBaja, $descBaja, $userId);
                } elseif ($nuevoEstado === EstadoInventario::EnMantenimiento->value) {
                    $record->update(['estado' => EstadoInventario::EnMantenimiento->value]);

                    $userModel = User::find($userId);
                    $tipoMant = in_array($tipoServicio, ['Preventivo', 'Correctivo', 'Mejora'], true) ? $tipoServicio : 'Preventivo';

                    Mantenimiento::create([
                        'id_inventario' => $record->id_inventario,
                        'id_usuario_solicita' => $userId,
                        'tipo_mantenimiento' => $tipoMant,
                        'tipo_servicio' => 'Personal Técnico',
                        'nombre_tecnico' => $userModel?->name ?? 'Personal Técnico',
                        'descripcion_falla' => $descFalla,
                        'fecha_solicitud' => now(),
                        'fecha_inicio' => now(),
                        'estado' => 'Solicitado',
                        'observaciones' => $motivo,
                    ]);

                    HistorialEstado::create([
                        'id_inventario' => $record->id_inventario,
                        'id_usuario' => $userId,
                        'estado_anterior' => $estadoAnterior,
                        'estado_nuevo' => EstadoInventario::EnMantenimiento->value,
                        'motivo_cambio' => "Enviado a mantenimiento masivo ({$tipoMant}): {$descFalla}",
                        'ubicacion_fisica' => $record->ubicacion_fisica,
                        'fecha_cambio' => now(),
                    ]);
                } elseif ($nuevoEstado === EstadoInventario::Danado->value) {
                    $specs = is_array($record->especificaciones) ? $record->especificaciones : [];
                    if ($descDano) {
                        $specs['descripcion_dano'] = $descDano;
                    }
                    if ($nivelDano) {
                        $specs['nivel_dano'] = $nivelDano;
                    }

                    $record->update([
                        'estado' => EstadoInventario::Danado->value,
                        'especificaciones' => $specs,
                    ]);

                    HistorialEstado::create([
                        'id_inventario' => $record->id_inventario,
                        'id_usuario' => $userId,
                        'estado_anterior' => $estadoAnterior,
                        'estado_nuevo' => EstadoInventario::Danado->value,
                        'motivo_cambio' => "Marcado como dañado ({$nivelDano}): " . ($descDano ?: $motivo),
                        'ubicacion_fisica' => $record->ubicacion_fisica,
                        'fecha_cambio' => now(),
                    ]);
                } elseif ($nuevoEstado === EstadoInventario::DevueltoAProveedor->value) {
                    $specs = is_array($record->especificaciones) ? $record->especificaciones : [];
                    if ($motivoDevolucion) {
                        $specs['motivo_devolucion'] = $motivoDevolucion;
                    }

                    $updatePayload = [
                        'estado' => EstadoInventario::DevueltoAProveedor->value,
                        'especificaciones' => $specs,
                    ];
                    if ($proveedorId) {
                        $updatePayload['id_proveedor'] = $proveedorId;
                    }

                    $record->update($updatePayload);

                    $provNombre = $proveedorId ? (Proveedor::find($proveedorId)?->nombre_empresa ?? "Proveedor #{$proveedorId}") : 'Proveedor';
                    HistorialEstado::create([
                        'id_inventario' => $record->id_inventario,
                        'id_usuario' => $userId,
                        'estado_anterior' => $estadoAnterior,
                        'estado_nuevo' => EstadoInventario::DevueltoAProveedor->value,
                        'motivo_cambio' => "Devuelto a {$provNombre}: {$motivoDevolucion}",
                        'ubicacion_fisica' => $record->ubicacion_fisica,
                        'fecha_cambio' => now(),
                    ]);
                } elseif (in_array($nuevoEstado, [EstadoInventario::Asignado->value, EstadoInventario::Reasignado->value], true)) {
                    $specs = is_array($record->especificaciones) ? $record->especificaciones : [];
                    $receptorNombre = '';
                    if ($nuevoReceptorId) {
                        $receptor = Receptor::find($nuevoReceptorId);
                        if ($receptor) {
                            $receptorNombre = trim("{$receptor->nombre} {$receptor->apellido_paterno} {$receptor->apellido_materno}");
                            $specs['id_receptor'] = $receptor->id_receptor;
                            $specs['receptor_nombre'] = $receptorNombre;
                        }
                    }

                    if ($areaDestino) {
                        $deptoFinal = $deptoDestino ?: $record->departamento_id;
                        if ($esAdmin) {
                            $record->cambiarAreaDirecto($deptoFinal, $areaDestino, null, null, $motivo, $userId);
                        } else {
                            $record->solicitarCambioArea($deptoFinal, $areaDestino, null, null, $motivo, $userId);
                        }
                        $record->salon_id = null;
                    }

                    $record->update([
                        'estado' => $nuevoEstado,
                        'fecha_asignacion' => now(),
                        'especificaciones' => $specs,
                    ]);

                    $detalleAsignacion = $receptorNombre ? " a {$receptorNombre}" : '';
                    HistorialEstado::create([
                        'id_inventario' => $record->id_inventario,
                        'id_usuario' => $userId,
                        'estado_anterior' => $estadoAnterior,
                        'estado_nuevo' => $nuevoEstado,
                        'motivo_cambio' => "{$nuevoEstado}{$detalleAsignacion}: {$motivo}",
                        'ubicacion_fisica' => $record->ubicacion_fisica,
                        'fecha_cambio' => now(),
                    ]);
                } else {
                    if ($areaDestino) {
                        $deptoFinal = $deptoDestino ?: $record->departamento_id;

                        if ($esAdmin) {
                            $record->cambiarAreaDirecto($deptoFinal, $areaDestino, null, null, $motivo, $userId);
                        } else {
                            $record->solicitarCambioArea($deptoFinal, $areaDestino, null, null, $motivo, $userId);
                        }

                        $record->salon_id = null;
                        if ($aulaDestino) {
                            $specs = is_array($record->especificaciones) ? $record->especificaciones : [];
                            $specs['aula'] = $aulaDestino;
                            $record->aula = $aulaDestino;
                            $record->especificaciones = $specs;
                        }
                        $record->save();
                    }

                    if ($nuevoEstado && $record->estado !== EstadoInventario::CambioDeArea->value) {
                        $record->update(['estado' => $nuevoEstado]);

                        HistorialEstado::create([
                            'id_inventario' => $record->id_inventario,
                            'id_usuario' => $userId,
                            'estado_anterior' => $estadoAnterior,
                            'estado_nuevo' => $nuevoEstado,
                            'motivo_cambio' => "Cambio masivo de estado a {$nuevoEstado}: {$motivo}",
                            'ubicacion_fisica' => $record->ubicacion_fisica,
                            'fecha_cambio' => now(),
                        ]);
                    }
                }

                if ($nuevoEstado !== 'Cambio de propiedad' && $nuevoTipoPropiedad && in_array($nuevoTipoPropiedad, ['Propio', 'Rentado'], true)) {
                    $propiedadAnt = $record->tipo_propiedad ?? 'Propio';
                    if ($propiedadAnt !== $nuevoTipoPropiedad) {
                        $record->update(['tipo_propiedad' => $nuevoTipoPropiedad]);

                        HistorialEstado::create([
                            'id_inventario' => $record->id_inventario,
                            'id_usuario' => $userId,
                            'estado_anterior' => $record->estado,
                            'estado_nuevo' => $record->estado,
                            'motivo_cambio' => "Cambio masivo de propiedad: {$propiedadAnt} → {$nuevoTipoPropiedad}: {$motivo}",
                            'ubicacion_fisica' => $record->ubicacion_fisica,
                            'fecha_cambio' => now(),
                        ]);
                    }
                }

                $aplicados++;
            }
        });

        return ['aplicados' => $aplicados, 'omitidos' => $omitidos];
    }

    /**
     * @return array{aplicados: int, omitidos: int}
     */
    public static function aplicarBajaMasiva(array $selectedIds, array $data, bool $esAdmin, ?int $userId = null): array
    {
        $userId ??= self::resolverUsuarioId();
        $motivo = $data['motivo_baja'];
        $obs = ! empty($data['observaciones_baja']) ? $data['observaciones_baja'] : 'Baja masiva';
        $datosAdicionales = ConModalesBajaCambio::extraerDatosAdicionalesBaja($data);
        $aplicados = 0;
        $omitidos = 0;

        DB::transaction(function () use ($selectedIds, $esAdmin, $userId, $motivo, $obs, $datosAdicionales, &$aplicados, &$omitidos) {
            $records = Inventario::whereIn('id_inventario', $selectedIds)->get();

            foreach ($records as $record) {
                if (in_array($record->estado, [
                    EstadoInventario::DadoDeBaja->value,
                    EstadoInventario::SolicitudDeBaja->value,
                    EstadoInventario::CambioDeArea->value,
                    'Baja',
                ], true)) {
                    $omitidos++;
                    continue;
                }

                if ($esAdmin) {
                    $record->darBajaDirecta($motivo, $obs, $userId, $datosAdicionales);
                } else {
                    $record->solicitarBaja($motivo, $obs, $userId, $datosAdicionales);
                }

                $aplicados++;
            }
        });

        return ['aplicados' => $aplicados, 'omitidos' => $omitidos];
    }
}
