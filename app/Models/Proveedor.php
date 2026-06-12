<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Proveedor extends Model
{
    use HasFactory;

    protected $table = 'proveedores';
    protected $primaryKey = 'id_proveedor';
    protected $fillable = ['nombre_empresa', 'rfc', 'contacto_nombre', 'contacto_telefono', 'contacto_email', 'activo'];

    public function inventarios()
    {
        return $this->hasMany(Inventario::class, 'id_proveedor', 'id_proveedor');
    }
}