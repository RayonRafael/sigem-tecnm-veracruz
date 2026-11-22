<?php

namespace App\Models;

use App\Models\Concerns\ConCodigoInventario;
use App\Models\Concerns\ConRentaInventario;
use App\Models\Concerns\ConUbicacionInventario;
use App\Services\InventarioBajaService;
use App\Services\InventarioCambioAreaService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Inventario extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;
    use ConCodigoInventario;
    use ConRentaInventario;
    use ConUbicacionInventario;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'num_serie', 'subtipo', 'especificaciones', 'estado', 'ubicacion_fisica', 'tipo_propiedad',
                'id_usuario', 'estado_registro', 'aprobado', 'aprobado_por', 'fecha_aprobacion', 'fecha_baja',
                'fecha_asignacion', 'fecha_cambio_area', 'ram', 'disco', 'procesador', 'mac_address', 'ip_address',
                'codigo', 'estacion', 'tipo_impresora', 'sistema_operativo', 'modelo', 'especificacion_texto',
                'departamento_id', 'area_id', 'salon_id', 'aula',
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
        'tipo_equipo', 'edificio_id', 'departamento_id', 'area_id', 'salon_id', 'aula', 'id_receptor',
        'codigo', 'estacion', 'tipo_impresora', 'sistema_operativo', 'modelo', 'especificacion_texto',
        'motivo_baja', 'observaciones_baja', 'id_usuario_baja', 'estado_previo_baja',
        'estado_previo_cambio', 'ubicacion_solicitada',
    ];

    public bool $skipHistorialObserver = false;

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

    public function receptor()
    {
        return $this->belongsTo(Receptor::class, 'id_receptor', 'id_receptor');
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

    public function resolverUsuarioId(?int $usuarioId = null): ?int
    {
        return $usuarioId
            ?? auth()->id()
            ?? $this->id_usuario
            ?? User::where('email', 'admin@tecnm.edu.mx')->value('id')
            ?? User::first()?->id;
    }

    public function darBajaDirecta(string $motivo, string $observaciones, ?int $usuarioId = null, array $datosAdicionales = []): void
    {
        InventarioBajaService::darBajaDirecta($this, $motivo, $observaciones, $usuarioId, $datosAdicionales);
    }

    public function solicitarBaja(string $motivo, string $observaciones, ?int $usuarioId = null, array $datosAdicionales = []): void
    {
        InventarioBajaService::solicitarBaja($this, $motivo, $observaciones, $usuarioId, $datosAdicionales);
    }

    public function aprobarBaja(?int $usuarioId = null): void
    {
        InventarioBajaService::aprobarBaja($this, $usuarioId);
    }

    public function rechazarBaja(?string $observaciones = null, ?int $usuarioId = null): void
    {
        InventarioBajaService::rechazarBaja($this, $observaciones, $usuarioId);
    }

    public function reactivar(?int $usuarioId = null): void
    {
        InventarioBajaService::reactivar($this, $usuarioId);
    }

    public function cambiarAreaDirecto($departamentoId, $areaId, $salonId = null, $estacion = null, ?string $motivo = null, ?int $usuarioId = null): void
    {
        InventarioCambioAreaService::cambiarAreaDirecto($this, $departamentoId, $areaId, $salonId, $estacion, $motivo, $usuarioId);
    }

    public function solicitarCambioArea($departamentoId, $areaId, $salonId = null, $estacion = null, ?string $motivo = null, ?int $usuarioId = null): void
    {
        InventarioCambioAreaService::solicitarCambioArea($this, $departamentoId, $areaId, $salonId, $estacion, $motivo, $usuarioId);
    }

    public function aprobarCambioArea(?int $usuarioId = null): void
    {
        InventarioCambioAreaService::aprobarCambioArea($this, $usuarioId);
    }

    public function rechazarCambioArea(?string $motivoRechazo = null, ?int $usuarioId = null): void
    {
        InventarioCambioAreaService::rechazarCambioArea($this, $motivoRechazo, $usuarioId);
    }
}
