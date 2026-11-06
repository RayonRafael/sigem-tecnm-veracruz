<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Inventario extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'num_serie', 'subtipo', 'especificaciones', 'estado', 'ubicacion_fisica', 'tipo_propiedad',
                'id_usuario', 'estado_registro', 'aprobado', 'aprobado_por', 'fecha_aprobacion', 'fecha_baja',
                'fecha_asignacion', 'fecha_cambio_area', 'ram', 'disco', 'procesador', 'mac_address', 'ip_address',
                'codigo', 'estacion', 'tipo_impresora', 'sistema_operativo', 'modelo', 'especificacion_texto',
                'departamento_id', 'area_id', 'salon_id',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(function (string $eventName) {
                $acciones = [
                    'created' => 'creado',
                    'updated' => 'actualizado',
                    'deleted' => 'eliminado',
                ];

                return $acciones[$eventName] ?? $eventName;
            });
    }

    protected $table = 'inventario';

    protected $primaryKey = 'id_inventario';

    protected $fillable = [
        'num_serie', 'subtipo', 'especificaciones', 'id_producto', 'id_usuario', 'id_proveedor', 'estado', 'estado_registro',
        'tipo_propiedad', 'ubicacion_fisica', 'fecha_registro', 'fecha_asignacion', 'fecha_cambio_area',
        'fecha_factura', 'num_factura',
        'fecha_baja', 'fecha_inicio_renta', 'fecha_fin_renta', 'observaciones_renta',
        'observaciones_generales', 'garantia_fecha_fin', 'garantia_estado',
        'aprobado', 'aprobado_por', 'fecha_aprobacion',
        'ram', 'disco', 'procesador', 'mac_address', 'ip_address',
        'tipo_equipo', 'edificio_id', 'departamento_id', 'area_id', 'salon_id',
        'codigo', 'estacion', 'tipo_impresora', 'sistema_operativo', 'modelo', 'especificacion_texto',
        'motivo_baja', 'observaciones_baja', 'id_usuario_baja', 'estado_previo_baja',
        'estado_previo_cambio', 'ubicacion_solicitada',
    ];

    public bool $skipHistorialObserver = false;

    // Convertir fechas automáticamente a objetos Carbon
    protected $casts = [
        'fecha_registro' => 'date',
        'fecha_asignacion' => 'date',
        'fecha_cambio_area' => 'date',
        'fecha_factura' => 'date',
        'fecha_baja' => 'datetime',
        'fecha_inicio_renta' => 'date',
        'fecha_fin_renta' => 'date',
        'garantia_fecha_fin' => 'date',
        'aprobado' => 'boolean',
        'fecha_aprobacion' => 'datetime',
        'especificaciones' => 'array',
        'ubicacion_solicitada' => 'array',
    ];

    public function material()
    {
        return $this->belongsTo(Material::class, 'id_producto', 'id_producto');
    }

    public function departamento()
    {
        return $this->belongsTo(Departamento::class, 'departamento_id', 'id_departamento');
    }

    public function area()
    {
        return $this->belongsTo(Area::class, 'area_id', 'id_area');
    }

    public function salon()
    {
        return $this->belongsTo(Salon::class, 'salon_id', 'id');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'id_usuario', 'id');
    }

    public function usuarioBaja()
    {
        return $this->belongsTo(User::class, 'id_usuario_baja', 'id');
    }

    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class, 'id_proveedor', 'id_proveedor');
    }

    public function aprobadoPor()
    {
        return $this->belongsTo(User::class, 'aprobado_por', 'id');
    }

    public function mantenimientos()
    {
        return $this->hasMany(Mantenimiento::class, 'id_inventario', 'id_inventario');
    }

    public function historial()
    {
        return $this->hasMany(HistorialEstado::class, 'id_inventario', 'id_inventario');
    }

    /**
     * Auto-genera un código institucional con formato MARCA-MODELO-### (ej. HP-LJ4103-001).
     */
    public function generarCodigo(): string
    {
        $material = $this->material ?: ($this->id_producto ? Material::with('marca')->find($this->id_producto) : null);

        $marcaRaw = $material?->marca?->nombre ?? '';
        $modeloRaw = $this->modelo ?: ($material?->modelo ?? '');

        $marca = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $marcaRaw));
        if (empty($marca)) {
            $marca = 'ACT';
        }

        $modelo = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $modeloRaw));
        if (empty($modelo)) {
            $modelo = 'GEN';
        }

        $prefix = "{$marca}-{$modelo}";

        // Buscar el último registro que coincida con el prefijo
        $ultimoCodigo = static::where('codigo', 'LIKE', "{$prefix}-%")
            ->orderByRaw('LENGTH(codigo) DESC, codigo DESC')
            ->value('codigo');

        $consecutivo = 1;
        if ($ultimoCodigo && preg_match('/-(\d+)$/', $ultimoCodigo, $matches)) {
            $consecutivo = ((int) $matches[1]) + 1;
        }

        $codigoGenerado = sprintf('%s-%03d', $prefix, $consecutivo);

        while (static::where('codigo', $codigoGenerado)->exists()) {
            $consecutivo++;
            $codigoGenerado = sprintf('%s-%03d', $prefix, $consecutivo);
        }

        return $codigoGenerado;
    }

    // Accessors / Mutators para campos dinámicos en especificaciones (JSON) y columnas físicas
    public function getTipoEquipoAttribute(): ?string
    {
        return $this->especificaciones['tipo_equipo'] ?? null;
    }

    public function setTipoEquipoAttribute(?string $value): void
    {
        $specs = $this->especificaciones ?? [];
        if ($value !== null && $value !== '') {
            $specs['tipo_equipo'] = $value;
        } else {
            unset($specs['tipo_equipo']);
        }
        $this->especificaciones = $specs;
    }

    public function getEdificioIdAttribute(): ?int
    {
        return isset($this->especificaciones['edificio_id']) && $this->especificaciones['edificio_id'] !== ''
            ? (int) $this->especificaciones['edificio_id']
            : null;
    }

    public function setEdificioIdAttribute($value): void
    {
        $specs = $this->especificaciones ?? [];
        if ($value !== null && $value !== '') {
            $specs['edificio_id'] = (int) $value;
        } else {
            unset($specs['edificio_id']);
        }
        $this->especificaciones = $specs;
    }

    public function getDepartamentoIdAttribute(): ?int
    {
        if (array_key_exists('departamento_id', $this->attributes) && $this->attributes['departamento_id'] !== null && $this->attributes['departamento_id'] !== '') {
            return (int) $this->attributes['departamento_id'];
        }

        return isset($this->especificaciones['departamento_id']) && $this->especificaciones['departamento_id'] !== ''
            ? (int) $this->especificaciones['departamento_id']
            : null;
    }

    public function setDepartamentoIdAttribute($value): void
    {
        $val = ($value !== null && $value !== '') ? (int) $value : null;
        $this->attributes['departamento_id'] = $val;

        $specs = $this->especificaciones ?? [];
        if ($val !== null) {
            $specs['departamento_id'] = $val;
        } else {
            unset($specs['departamento_id']);
        }
        $this->especificaciones = $specs;
    }

    public function getAreaIdAttribute(): ?int
    {
        if (array_key_exists('area_id', $this->attributes) && $this->attributes['area_id'] !== null && $this->attributes['area_id'] !== '') {
            return (int) $this->attributes['area_id'];
        }

        return isset($this->especificaciones['area_id']) && $this->especificaciones['area_id'] !== ''
            ? (int) $this->especificaciones['area_id']
            : null;
    }

    public function setAreaIdAttribute($value): void
    {
        $val = ($value !== null && $value !== '') ? (int) $value : null;
        $this->attributes['area_id'] = $val;

        $specs = $this->especificaciones ?? [];
        if ($val !== null) {
            $specs['area_id'] = $val;
        } else {
            unset($specs['area_id']);
        }
        $this->especificaciones = $specs;
    }

    public function getSalonIdAttribute(): ?int
    {
        if (array_key_exists('salon_id', $this->attributes) && $this->attributes['salon_id'] !== null && $this->attributes['salon_id'] !== '') {
            return (int) $this->attributes['salon_id'];
        }

        return isset($this->especificaciones['salon_id']) && $this->especificaciones['salon_id'] !== ''
            ? (int) $this->especificaciones['salon_id']
            : null;
    }

    public function setSalonIdAttribute($value): void
    {
        $val = ($value !== null && $value !== '') ? (int) $value : null;
        $this->attributes['salon_id'] = $val;

        $specs = $this->especificaciones ?? [];
        if ($val !== null) {
            $specs['salon_id'] = $val;
        } else {
            unset($specs['salon_id']);
        }
        $this->especificaciones = $specs;
    }

    public function darBajaDirecta(string $motivo, string $observaciones, ?int $usuarioId = null): void
    {
        $userId = $usuarioId ?? auth()->id() ?? $this->id_usuario ?? 1;
        $estadoAnterior = $this->estado ?? 'Disponible';

        $this->update([
            'estado' => 'Dado de baja',
            'fecha_baja' => now(),
            'motivo_baja' => $motivo,
            'observaciones_baja' => $observaciones,
            'id_usuario_baja' => $userId,
        ]);

        HistorialEstado::create([
            'id_inventario' => $this->id_inventario,
            'id_usuario' => $userId,
            'estado_anterior' => $estadoAnterior,
            'estado_nuevo' => 'Dado de baja',
            'motivo_cambio' => "Baja directa: {$motivo}",
            'ubicacion_fisica' => $this->ubicacion_fisica,
            'fecha_cambio' => now(),
        ]);
    }

    public function solicitarBaja(string $motivo, string $observaciones, ?int $usuarioId = null): void
    {
        $userId = $usuarioId ?? auth()->id() ?? $this->id_usuario ?? 1;
        $estadoAnterior = $this->estado ?? 'Disponible';

        $this->update([
            'estado' => 'Solicitud de baja',
            'estado_previo_baja' => $estadoAnterior,
            'motivo_baja' => $motivo,
            'observaciones_baja' => $observaciones,
            'id_usuario_baja' => $userId,
        ]);

        HistorialEstado::create([
            'id_inventario' => $this->id_inventario,
            'id_usuario' => $userId,
            'estado_anterior' => $estadoAnterior,
            'estado_nuevo' => 'Solicitud de baja',
            'motivo_cambio' => "Solicitud de baja: {$motivo}",
            'ubicacion_fisica' => $this->ubicacion_fisica,
            'fecha_cambio' => now(),
        ]);
    }

    public function aprobarBaja(?int $usuarioId = null): void
    {
        $userId = $usuarioId ?? auth()->id() ?? 1;
        $motivo = $this->motivo_baja ?? 'Sin motivo especificado';

        $this->update([
            'estado' => 'Dado de baja',
            'fecha_baja' => now(),
            'id_usuario_baja' => $userId,
        ]);

        HistorialEstado::create([
            'id_inventario' => $this->id_inventario,
            'id_usuario' => $userId,
            'estado_anterior' => 'Solicitud de baja',
            'estado_nuevo' => 'Dado de baja',
            'motivo_cambio' => "Baja aprobada: {$motivo}",
            'ubicacion_fisica' => $this->ubicacion_fisica,
            'fecha_cambio' => now(),
        ]);
    }

    public function rechazarBaja(?string $observaciones = null, ?int $usuarioId = null): void
    {
        $userId = $usuarioId ?? auth()->id() ?? 1;
        $estadoRestaurar = $this->estado_previo_baja ?? 'Disponible';
        $obsTexto = !empty($observaciones) ? $observaciones : ($this->observaciones_baja ?? 'Sin observaciones');

        $this->update([
            'estado' => $estadoRestaurar,
            'estado_previo_baja' => null,
            'motivo_baja' => null,
            'observaciones_baja' => null,
            'id_usuario_baja' => null,
            'fecha_baja' => null,
        ]);

        HistorialEstado::create([
            'id_inventario' => $this->id_inventario,
            'id_usuario' => $userId,
            'estado_anterior' => 'Solicitud de baja',
            'estado_nuevo' => $estadoRestaurar,
            'motivo_cambio' => "Baja rechazada: {$obsTexto}",
            'ubicacion_fisica' => $this->ubicacion_fisica,
            'fecha_cambio' => now(),
        ]);
    }

    public function reactivar(?int $usuarioId = null): void
    {
        $userId = $usuarioId ?? auth()->id() ?? 1;

        $this->update([
            'estado' => 'Disponible',
            'fecha_baja' => null,
            'motivo_baja' => null,
            'observaciones_baja' => null,
            'id_usuario_baja' => null,
            'estado_previo_baja' => null,
        ]);

        HistorialEstado::create([
            'id_inventario' => $this->id_inventario,
            'id_usuario' => $userId,
            'estado_anterior' => 'Dado de baja',
            'estado_nuevo' => 'Disponible',
            'motivo_cambio' => 'Reactivación del equipo',
            'ubicacion_fisica' => $this->ubicacion_fisica,
            'fecha_cambio' => now(),
        ]);
    }

    public function cambiarAreaDirecto($departamentoId, $areaId, $salonId = null, $estacion = null, ?string $motivo = null, ?int $usuarioId = null): void
    {
        $userId = $usuarioId ?? auth()->id() ?? $this->id_usuario ?? 1;
        $estadoAnterior = $this->estado ?? 'Disponible';

        if (!empty($salonId) && !is_numeric($salonId) && is_string($salonId)) {
            $salonObj = Salon::firstOrCreate(['nombre' => $salonId, 'area_id' => $areaId]);
            $salonId = $salonObj->id;
        }

        $ubicacionAnterior = $this->ubicacion_fisica
            ?: (\App\Support\UbicacionHelper::construir($this->departamento_id, $this->area_id, $this->salon_id, $this->estacion) ?: 'Sin ubicación previa');

        $ubicacionNueva = \App\Support\UbicacionHelper::construir($departamentoId, $areaId, $salonId, $estacion);

        $this->skipHistorialObserver = true;

        $this->departamento_id = $departamentoId;
        $this->area_id = $areaId;
        $this->salon_id = $salonId;
        $this->estacion = $estacion;
        $this->fecha_cambio_area = now();
        if (!empty($ubicacionNueva)) {
            $this->ubicacion_fisica = substr($ubicacionNueva, 0, 150);
        }
        $this->save();

        $textoAnterior = $ubicacionAnterior ?: 'Sin ubicación previa';
        $textoNuevo = $this->ubicacion_fisica ?: ($ubicacionNueva ?: 'Sin ubicación asignada');
        $motivoTexto = "Cambio de área: {$textoAnterior} → {$textoNuevo}";

        HistorialEstado::create([
            'id_inventario' => $this->id_inventario,
            'id_usuario' => $userId,
            'estado_anterior' => $estadoAnterior,
            'estado_nuevo' => $this->estado ?? 'Disponible',
            'motivo_cambio' => $motivoTexto,
            'ubicacion_fisica' => $this->ubicacion_fisica,
            'fecha_cambio' => now(),
        ]);
    }

    public function solicitarCambioArea($departamentoId, $areaId, $salonId = null, $estacion = null, ?string $motivo = null, ?int $usuarioId = null): void
    {
        $userId = $usuarioId ?? auth()->id() ?? $this->id_usuario ?? 1;
        $estadoAnterior = $this->estado ?? 'Disponible';

        if (!empty($salonId) && !is_numeric($salonId) && is_string($salonId)) {
            $salonObj = Salon::firstOrCreate(['nombre' => $salonId, 'area_id' => $areaId]);
            $salonId = $salonObj->id;
        }

        $solicitudData = [
            'departamento_id' => $departamentoId,
            'area_id' => $areaId,
            'salon_id' => $salonId,
            'estacion' => $estacion,
            'motivo' => $motivo,
        ];

        $this->update([
            'estado' => 'Cambio de área',
            'estado_previo_cambio' => $estadoAnterior,
            'ubicacion_solicitada' => $solicitudData,
        ]);

        $motivoHistorial = filled($motivo) ? "Solicitud de cambio de área: {$motivo}" : "Solicitud de cambio de área";

        HistorialEstado::create([
            'id_inventario' => $this->id_inventario,
            'id_usuario' => $userId,
            'estado_anterior' => $estadoAnterior,
            'estado_nuevo' => 'Cambio de área',
            'motivo_cambio' => $motivoHistorial,
            'ubicacion_fisica' => $this->ubicacion_fisica,
            'fecha_cambio' => now(),
        ]);
    }

    public function aprobarCambioArea(?int $usuarioId = null): void
    {
        $userId = $usuarioId ?? auth()->id() ?? 1;
        $solicitud = $this->ubicacion_solicitada ?? [];
        $estadoPrevio = $this->estado_previo_cambio ?? 'Disponible';

        $depto = $solicitud['departamento_id'] ?? $this->departamento_id;
        $area = $solicitud['area_id'] ?? $this->area_id;
        $salon = $solicitud['salon_id'] ?? null;
        $estacion = $solicitud['estacion'] ?? null;

        $this->skipHistorialObserver = true;

        $this->departamento_id = $depto;
        $this->area_id = $area;
        $this->salon_id = $salon;
        $this->estacion = $estacion;
        $this->estado = $estadoPrevio;
        $this->fecha_cambio_area = now();
        $this->ubicacion_solicitada = null;
        $this->estado_previo_cambio = null;

        $ubicacionNueva = \App\Support\UbicacionHelper::construir($depto, $area, $salon, $estacion);
        if (!empty($ubicacionNueva)) {
            $this->ubicacion_fisica = substr($ubicacionNueva, 0, 150);
        }
        $this->save();

        $ubicacionTexto = $this->ubicacion_fisica ?: ($ubicacionNueva ?: 'Sin ubicación asignada');

        HistorialEstado::create([
            'id_inventario' => $this->id_inventario,
            'id_usuario' => $userId,
            'estado_anterior' => 'Cambio de área',
            'estado_nuevo' => $estadoPrevio,
            'motivo_cambio' => "Cambio de área aprobado: {$ubicacionTexto}",
            'ubicacion_fisica' => $this->ubicacion_fisica,
            'fecha_cambio' => now(),
        ]);
    }

    public function rechazarCambioArea(?string $motivoRechazo = null, ?int $usuarioId = null): void
    {
        $userId = $usuarioId ?? auth()->id() ?? 1;
        $estadoPrevio = $this->estado_previo_cambio ?? 'Disponible';

        $this->update([
            'estado' => $estadoPrevio,
            'ubicacion_solicitada' => null,
            'estado_previo_cambio' => null,
        ]);

        $motivoTexto = filled($motivoRechazo) ? "Cambio de área rechazado: {$motivoRechazo}" : "Cambio de área rechazado";

        HistorialEstado::create([
            'id_inventario' => $this->id_inventario,
            'id_usuario' => $userId,
            'estado_anterior' => 'Cambio de área',
            'estado_nuevo' => $estadoPrevio,
            'motivo_cambio' => $motivoTexto,
            'ubicacion_fisica' => $this->ubicacion_fisica,
            'fecha_cambio' => now(),
        ]);
    }
}
