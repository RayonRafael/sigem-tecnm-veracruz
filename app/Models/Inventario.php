<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Inventario extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'inventario';
    protected $primaryKey = 'id_inventario';
    
    protected $fillable = [
        'num_serie', 'id_producto', 'id_usuario', 'id_proveedor', 'estado', 'estado_registro',
        'tipo_propiedad', 'ubicacion_fisica', 'fecha_registro', 'fecha_factura', 'num_factura',
        'fecha_baja', 'fecha_inicio_renta', 'fecha_fin_renta', 'observaciones_renta',
        'observaciones_generales', 'garantia_fecha_fin', 'garantia_estado',
        'aprobado', 'aprobado_por', 'fecha_aprobacion'
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
}