<?php

namespace App\Filament\Resources\InventarioResource\Actions;

use App\Filament\Resources\InventarioResource;
use App\Models\Area;
use App\Models\Inventario;
use App\Models\MarcaMaterial;
use App\Models\Material;
use App\Models\Receptor;
use App\Models\Salon;
use App\Support\SigemUniqueValidator;
use Filament\Forms;
use Filament\Tables;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\ValidationException;

class EditarEquipoAction
{
    public static function make(): Tables\Actions\EditAction
    {
        return Tables\Actions\EditAction::make()
            ->iconButton()
            ->tooltip('Modificar')
            ->extraAttributes(['title' => 'Modificar', 'class' => 'icon-btn'])
            ->modalHeading(fn (?Inventario $record) => 'Editar: ' . ($record?->material?->nombre ?? $record?->subtipo ?? 'equipo'))
            ->modalDescription(fn (?Inventario $record) => new HtmlString(
                'ID: ' . e($record?->codigo ?? ('#' . $record?->id_inventario)) . ' · ' . e($record?->material?->tipo?->nombre ?? $record?->subtipo ?? 'General')
            ))
            ->slideOver()
            ->modalSubmitActionLabel('Guardar cambios')
            ->modalCancelActionLabel('Cancelar')
            ->mutateRecordDataUsing(fn (array $data, Inventario $record): array => InventarioResource::hidratarDatosEdicion($data, $record))
            ->using(fn (Inventario $record, array $data): Inventario => InventarioResource::guardarCambiosEquipo($record, $data))
            ->successNotificationTitle('Registro actualizado correctamente');
    }

    public static function obtenerTipoEquipoNormalizado(?Inventario $record = null, ?Forms\Get $get = null): string
    {
        if (! $record && $get) {
            $record = $get('record');
        }

        if (! $record) {
            $subtipo = strtolower(trim((string) ($get ? $get('subtipo') : '')));
            $nombre = strtolower(trim((string) ($get ? $get('nombre_equipo') : '')));
            $tipoMat = strtolower(trim((string) ($get ? $get('categoria_display') : '')));
            $combined = " {$subtipo} {$nombre} {$tipoMat} ";
            if (str_contains($combined, 'impresora') || str_contains($combined, 'multifuncional')) return 'imp';
            if (str_contains($combined, 'switch') || str_contains($combined, 'conmutador')) return 'swi';
            if (str_contains($combined, 'monitor') || str_contains($combined, 'pantalla')) return 'mon';
            if (str_contains($combined, 'all in one') || str_contains($combined, 'aio')) return 'aio';
            if (str_contains($combined, 'laptop') || str_contains($combined, 'notebook')) return 'lap';
            if (str_contains($combined, 'cpu') || str_contains($combined, 'computadora') || str_contains($combined, 'gabinete')) return 'cpu';

            return 'otro';
        }

        $subtipo = strtolower(trim((string) ($record->subtipo ?? '')));
        $matNombre = strtolower(trim((string) ($record->material?->nombre ?? '')));
        $matTipo = strtolower(trim((string) ($record->material?->tipo?->nombre ?? '')));
        $componente = strtolower(trim((string) ($record->especificaciones['componente'] ?? '')));
        $tipoEquipo = strtolower(trim((string) ($record->tipo_equipo ?? ($record->especificaciones['tipo_equipo'] ?? ''))));

        $combined = " {$subtipo} {$matNombre} {$matTipo} ";

        if (str_contains($combined, 'all in one') || str_contains($combined, 'all-in-one') || str_contains($combined, 'aio') || $tipoEquipo === 'aio') {
            return 'aio';
        }

        if (str_contains($combined, 'laptop') || str_contains($combined, 'notebook') || str_contains($combined, 'portátil') || str_contains($combined, 'portatil') || $tipoEquipo === 'lap') {
            return 'lap';
        }

        if (str_contains($combined, 'impresora') || str_contains($combined, 'multifuncional') || $tipoEquipo === 'imp' || filled($record->tipo_impresora) || isset($record->especificaciones['tipo_impresora'])) {
            return 'imp';
        }

        if (str_contains($combined, 'switch') || str_contains($combined, 'conmutador') || $tipoEquipo === 'swi' || isset($record->especificaciones['num_puertos'])) {
            return 'swi';
        }

        if (str_contains($combined, 'monitor') || str_contains($combined, 'pantalla') || $componente === 'mon' || $tipoEquipo === 'mon') {
            return 'mon';
        }

        if (
            str_contains($combined, 'teclado') || str_contains($combined, 'keyboard') || $componente === 'tec' || $tipoEquipo === 'tec'
            || str_contains($combined, 'mouse') || str_contains($combined, 'ratón') || str_contains($combined, 'raton') || $componente === 'mou' || $tipoEquipo === 'mou'
            || str_contains($combined, 'proyector') || str_contains($combined, 'cañón') || str_contains($combined, 'canon') || $tipoEquipo === 'proy'
            || str_contains($combined, 'no-break') || str_contains($combined, 'nobreak') || str_contains($combined, 'no break') || str_contains($combined, 'regulador') || str_contains($combined, 'ups') || $tipoEquipo === 'nob'
        ) {
            return 'periferico';
        }

        if (str_contains($combined, 'cpu') || str_contains($combined, 'gabinete') || str_contains($combined, 'computadora') || str_contains($combined, 'escritorio') || str_contains($combined, 'optiplex') || str_contains($combined, 'prodesk') || $componente === 'cpu' || $tipoEquipo === 'cpu' || $tipoEquipo === 'pc') {
            return 'cpu';
        }

        return 'otro';
    }

    public static function hidratarDatosEdicion(array $data, ?Inventario $record = null): array
    {
        if (! $record) {
            return $data;
        }

        $data['codigo'] = $record->codigo ?: ('#' . $record->id_inventario);
        $data['nombre_equipo'] = $record->material?->nombre ?? $record->subtipo ?? '';
        $data['marca'] = $record->especificaciones['marca']
            ?? $record->especificaciones['cpu_marca']
            ?? $record->material?->marca?->nombre
            ?? '';
        $data['departamento_id'] = $record->departamento_id ?? $record->area?->id_departamento;
        $data['id_receptor'] = $record->id_receptor;
        $data['aula'] = $record->aula
            ?? $record->salon?->nombre
            ?? ($record->especificaciones['aula'] ?? null)
            ?? (preg_match('/Aula\s+([A-Za-z0-9_-]+)/i', $record->ubicacion_fisica ?? '', $m) ? $m[1] : null)
            ?? '';
        $data['categoria_display'] = $record->material?->tipo?->nombre ?? $record->subtipo ?? 'General';
        $data['especificacion_texto'] = $record->especificacion_texto ?? ($record->especificaciones['especificaciones'] ?? '');
        $data['procesador'] = $record->procesador ?? ($record->especificaciones['procesador'] ?? '');
        $data['ram'] = $record->ram ?? ($record->especificaciones['ram'] ?? '');
        $data['disco'] = $record->disco ?? ($record->especificaciones['disco'] ?? '');
        $data['sistema_operativo'] = $record->sistema_operativo ?? ($record->especificaciones['sistema_operativo'] ?? '');
        $data['tipo_impresora'] = $record->tipo_impresora ?? ($record->especificaciones['tipo_impresora'] ?? '—');
        $data['num_puertos'] = $record->especificaciones['num_puertos'] ?? '—';
        $data['ip_address'] = $record->ip_address ?? ($record->especificaciones['ip_address'] ?? '');
        $data['mac_address'] = $record->mac_address ?? ($record->especificaciones['mac_address'] ?? '');
        $data['observaciones_adicionales'] = $record->observaciones_generales ?? '';
        $data['renta_fecha_fin'] = $record->tipo_propiedad === 'Rentado'
            ? ($record->especificaciones['renta_fecha_fin'] ?? $record->fecha_fin_renta?->format('Y-m-d'))
            : null;

        return $data;
    }

    public static function guardarCambiosEquipo(Inventario $record, array $data): Inventario
    {
        if (array_key_exists('num_serie', $data) && filled($data['num_serie'])) {
            $errorSn = SigemUniqueValidator::validarSerieInventario((string) $data['num_serie'], $record->getKey());
            if ($errorSn !== null) {
                throw ValidationException::withMessages(['num_serie' => $errorSn]);
            }
        }

        return DB::transaction(function () use ($record, $data) {
            // 1. Nombre y Marca en el registro de inventario
            $nuevoNombre = null;
            if (isset($data['nombre_equipo']) && filled($data['nombre_equipo'])) {
                $nuevoNombre = trim((string) $data['nombre_equipo']);
                $record->subtipo = substr($nuevoNombre, 0, 100);
            }

            $marcaModel = null;
            if (isset($data['marca']) && filled($data['marca'])) {
                $nuevaMarca = trim((string) $data['marca']);
                $specs = $record->especificaciones ?? [];
                $specs['marca'] = $nuevaMarca;
                $record->especificaciones = $specs;

                $marcaModel = SigemUniqueValidator::buscarPorValorNormalizado(MarcaMaterial::class, 'nombre', $nuevaMarca);
                if ($marcaModel instanceof MarcaMaterial) {
                    if ($marcaModel->trashed()) {
                        $marcaModel->restore();
                    }
                    $specs['marca'] = $marcaModel->nombre;
                    $record->especificaciones = $specs;
                } else {
                    $marcaModel = MarcaMaterial::create(['nombre' => $nuevaMarca]);
                }
            }

            // 2. Actualizar Material sin afectar otros equipos que compartan id_producto (FIX INT-01)
            $cambioMaterial = $nuevoNombre !== null
                || $marcaModel !== null
                || (isset($data['modelo']) && filled($data['modelo']));

            if ($record->material && $cambioMaterial) {
                $targetNombre = $nuevoNombre ?? $record->material->nombre;
                $targetMarcaId = $marcaModel ? $marcaModel->id_marca : $record->material->id_marca;
                $targetModelo = (isset($data['modelo']) && filled($data['modelo']))
                    ? trim((string) $data['modelo'])
                    : ($record->modelo ?? $record->material->modelo);

                $compartido = Inventario::withTrashed()
                    ->where('id_producto', $record->id_producto)
                    ->where('id_inventario', '!=', $record->id_inventario)
                    ->exists();

                if ($compartido) {
                    $queryMat = Material::withTrashed()
                        ->where('nombre', substr($targetNombre, 0, 150))
                        ->where('id_marca', $targetMarcaId);

                    if ($targetModelo !== null && $targetModelo !== '') {
                        $queryMat->where('modelo', substr($targetModelo, 0, 100));
                    } else {
                        $queryMat->whereNull('modelo');
                    }

                    $nuevoMaterial = $queryMat->first();
                    if ($nuevoMaterial && $nuevoMaterial->trashed()) {
                        $nuevoMaterial->restore();
                    } elseif (! $nuevoMaterial) {
                        $nuevoMaterial = Material::create([
                            'nombre' => substr($targetNombre, 0, 150),
                            'id_marca' => $targetMarcaId,
                            'modelo' => $targetModelo ? substr($targetModelo, 0, 100) : null,
                            'id_tipodematerial' => $record->material->id_tipodematerial,
                            'id_unidad' => $record->material->id_unidad,
                            'requiere_control_individual' => $record->material->requiere_control_individual ?? true,
                            'stock_minimo' => $record->material->stock_minimo ?? 0,
                        ]);
                    }

                    $record->id_producto = $nuevoMaterial->id_producto;
                    $record->setRelation('material', $nuevoMaterial);
                    $data['id_producto'] = $nuevoMaterial->id_producto;
                } else {
                    $record->material->update([
                        'nombre' => substr($targetNombre, 0, 150),
                        'id_marca' => $targetMarcaId,
                        'modelo' => $targetModelo ? substr($targetModelo, 0, 100) : null,
                    ]);
                }
            }

            // 3. Aula (columna física + especificaciones + sincronización limpia de salon_id)
            if (array_key_exists('aula', $data)) {
                $nuevaAula = filled($data['aula']) ? trim((string) $data['aula']) : null;
                $record->aula = $nuevaAula;
                $data['aula'] = $nuevaAula;

                $specs = $record->especificaciones ?? [];
                if ($nuevaAula !== null) {
                    $specs['aula'] = $nuevaAula;
                } else {
                    unset($specs['aula']);
                }
                $record->especificaciones = $specs;

                $targetAreaId = $data['area_id'] ?? $record->area_id;
                if ($targetAreaId && $nuevaAula !== null) {
                    $salon = Salon::where('area_id', $targetAreaId)
                        ->where('nombre', $nuevaAula)
                        ->first();
                    $record->salon_id = $salon?->id;
                } else {
                    $record->salon_id = null;
                }
            }

            // 4. IP & MAC
            if (isset($data['ip_address'])) {
                $record->ip_address = $data['ip_address'];
                $specs = $record->especificaciones ?? [];
                $specs['ip_address'] = $data['ip_address'];
                $record->especificaciones = $specs;
            }
            if (isset($data['mac_address'])) {
                $record->mac_address = $data['mac_address'];
                $specs = $record->especificaciones ?? [];
                $specs['mac_address'] = $data['mac_address'];
                $record->especificaciones = $specs;
            }

            // 5. Departamento, Área y Receptor
            if (isset($data['departamento_id']) && filled($data['departamento_id'])) {
                $record->departamento_id = (int) $data['departamento_id'];
            }
            if (isset($data['area_id']) && filled($data['area_id'])) {
                $record->area_id = $data['area_id'];
                $area = Area::find($data['area_id']);
                if ($area && $area->id_departamento) {
                    $record->departamento_id = $area->id_departamento;
                }
            }
            if (array_key_exists('id_receptor', $data)) {
                $specs = $record->especificaciones ?? [];
                if (filled($data['id_receptor'])) {
                    $receptor = Receptor::find((int) $data['id_receptor']);
                    if ($receptor) {
                        $specs['id_receptor'] = $receptor->id_receptor;
                        $specs['receptor_nombre'] = $receptor->nombre_completo;
                    }
                } else {
                    unset($specs['id_receptor'], $specs['receptor_nombre']);
                }
                $record->especificaciones = $specs;
                unset($data['id_receptor']);
            }

            // 6. Datos técnicos, modelo y número de serie
            $specs = $record->especificaciones ?? [];
            foreach (['num_serie', 'modelo', 'procesador', 'ram', 'disco', 'sistema_operativo'] as $campo) {
                if (array_key_exists($campo, $data)) {
                    $val = filled($data[$campo]) ? trim((string) $data[$campo]) : null;
                    $record->{$campo} = $val;
                    if ($val !== null) {
                        $specs[$campo] = $val;
                    } else {
                        unset($specs[$campo]);
                    }
                }
            }
            $record->especificaciones = $specs;

            // 7. Observaciones adicionales (FIX INT-11)
            if (array_key_exists('observaciones_adicionales', $data)) {
                $record->observaciones_generales = $data['observaciones_adicionales'] ?? null;
                unset($data['observaciones_adicionales']);
            }

            // 7b. Seguimiento de renta (3 años máximo, guardado en especificaciones sin migración)
            $tipoPropFinal = $data['tipo_propiedad'] ?? $record->tipo_propiedad;
            $specs = $record->especificaciones ?? [];
            if ($tipoPropFinal === 'Propio') {
                unset($specs['renta_fecha_fin']);
                $record->fecha_fin_renta = null;
            } elseif ($tipoPropFinal === 'Rentado') {
                if (array_key_exists('renta_fecha_fin', $data) && filled($data['renta_fecha_fin'])) {
                    $finStr = Carbon::parse($data['renta_fecha_fin'])->format('Y-m-d');
                    $specs['renta_fecha_fin'] = $finStr;
                    $record->fecha_fin_renta = $finStr;
                } elseif (empty($specs['renta_fecha_fin']) && ! $record->fecha_fin_renta) {
                    $fechaRegRef = $data['fecha_registro'] ?? $record->fecha_registro ?? now();
                    $finStr = Carbon::parse($fechaRegRef)->addYears(3)->format('Y-m-d');
                    $specs['renta_fecha_fin'] = $finStr;
                    $record->fecha_fin_renta = $finStr;
                }
            }
            $record->especificaciones = $specs;
            unset($data['renta_fecha_fin']);

            // 8. Fill other allowable fields
            $record->fill($data);
            $record->save();

            return $record;
        });
    }
}
