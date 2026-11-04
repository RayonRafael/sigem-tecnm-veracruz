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
    ];

    // Convertir fechas automáticamente a objetos Carbon
    protected $casts = [
        'fecha_registro' => 'date',
        'fecha_asignacion' => 'date',
        'fecha_cambio_area' => 'date',
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

    public function scopeReasignados($query)
    {
        return $query->where('estado', 'Reasignado');
    }

    public function scopeEnMantenimiento($query)
    {
        return $query->where('estado', 'En Mantenimiento');
    }

    public function scopeDanados($query)
    {
        return $query->whereIn('estado', ['Dañado', 'Baja']);
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
}
