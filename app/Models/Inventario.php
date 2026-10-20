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
            ->logOnly(['num_serie', 'especificaciones', 'estado', 'ubicacion_fisica', 'tipo_propiedad', 'id_usuario', 'estado_registro', 'aprobado', 'aprobado_por', 'fecha_aprobacion', 'fecha_baja'])
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
        'num_serie', 'especificaciones', 'id_producto', 'id_usuario', 'id_proveedor', 'estado', 'estado_registro',
        'tipo_propiedad', 'ubicacion_fisica', 'fecha_registro', 'fecha_factura', 'num_factura',
        'fecha_baja', 'fecha_inicio_renta', 'fecha_fin_renta', 'observaciones_renta',
        'observaciones_generales', 'garantia_fecha_fin', 'garantia_estado',
        'aprobado', 'aprobado_por', 'fecha_aprobacion',
        'tipo_equipo', 'edificio_id', 'departamento_id', 'area_id', 'salon_id',
    ];

    // Convertir fechas automáticamente a objetos Carbon
    protected $casts = [
        'fecha_registro' => 'date',
        'fecha_factura' => 'date',
        'fecha_baja' => 'date',
        'fecha_inicio_renta' => 'date',
        'fecha_fin_renta' => 'date',
        'garantia_fecha_fin' => 'date',
        'aprobado' => 'boolean',
        'fecha_aprobacion' => 'datetime',
        'especificaciones' => 'array',
    ];

    public function material()
    {
        return $this->belongsTo(Material::class, 'id_producto', 'id_producto');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'id_usuario', 'id');
    }

    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class, 'id_proveedor', 'id_proveedor');
    }

    public function aprobadoPor()
    {
        return $this->belongsTo(User::class, 'aprobado_por', 'id');
    }

    public function detallesSolicitud()
    {
        return $this->hasMany(DetalleSolicitud::class, 'id_inventario', 'id_inventario');
    }

    public function mantenimientos()
    {
        return $this->hasMany(Mantenimiento::class, 'id_inventario', 'id_inventario');
    }

    public function historial()
    {
        return $this->hasMany(HistorialEstado::class, 'id_inventario', 'id_inventario');
    }

    // Scopes de estado
    public function scopeDisponibles($query)
    {
        return $query->where('estado', 'Disponible');
    }

    public function scopeAsignados($query)
    {
        return $query->where('estado', 'Asignado');
    }

    public function scopeEnMantenimiento($query)
    {
        return $query->where('estado', 'En Mantenimiento');
    }

    public function scopeDanados($query)
    {
        return $query->whereIn('estado', ['Dañado', 'Baja']);
    }

    // Accessors / Mutators para campos dinámicos en especificaciones (JSON)
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
        return isset($this->especificaciones['departamento_id']) && $this->especificaciones['departamento_id'] !== ''
            ? (int) $this->especificaciones['departamento_id']
            : null;
    }

    public function setDepartamentoIdAttribute($value): void
    {
        $specs = $this->especificaciones ?? [];
        if ($value !== null && $value !== '') {
            $specs['departamento_id'] = (int) $value;
        } else {
            unset($specs['departamento_id']);
        }
        $this->especificaciones = $specs;
    }

    public function getAreaIdAttribute(): ?int
    {
        return isset($this->especificaciones['area_id']) && $this->especificaciones['area_id'] !== ''
            ? (int) $this->especificaciones['area_id']
            : null;
    }

    public function setAreaIdAttribute($value): void
    {
        $specs = $this->especificaciones ?? [];
        if ($value !== null && $value !== '') {
            $specs['area_id'] = (int) $value;
        } else {
            unset($specs['area_id']);
        }
        $this->especificaciones = $specs;
    }

    public function getSalonIdAttribute(): ?int
    {
        return isset($this->especificaciones['salon_id']) && $this->especificaciones['salon_id'] !== ''
            ? (int) $this->especificaciones['salon_id']
            : null;
    }

    public function setSalonIdAttribute($value): void
    {
        $specs = $this->especificaciones ?? [];
        if ($value !== null && $value !== '') {
            $specs['salon_id'] = (int) $value;
        } else {
            unset($specs['salon_id']);
        }
        $this->especificaciones = $specs;
    }
}
