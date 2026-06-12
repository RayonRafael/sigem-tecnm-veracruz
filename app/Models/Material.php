<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Material extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'material';
    protected $primaryKey = 'id_producto';
    protected $fillable = [
        'nombre', 'descripcion', 'modelo', 
        'id_unidad', 'id_marca', 'id_tipodematerial', 
        'requiere_control_individual', 'stock_actual', 'stock_minimo'
    ];

    // RELACIONES: Pertenece a una unidad, marca y tipo
    public function unidad()
    {
        return $this->belongsTo(UnidadMedida::class, 'id_unidad', 'id_unidad');
    }

    public function marca()
    {
        return $this->belongsTo(MarcaMaterial::class, 'id_marca', 'id_marca');
    }

    public function tipo()
    {
        return $this->belongsTo(TipoMaterial::class, 'id_tipodematerial', 'id_tipodematerial');
    }

    // RELACIONES: Un material tiene muchos inventarios
    public function inventarios()
    {
        return $this->hasMany(Inventario::class, 'id_producto', 'id_producto');
    }
}