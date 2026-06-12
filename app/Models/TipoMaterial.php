<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TipoMaterial extends Model
{
    use HasFactory;

    protected $table = 'tipo_material';
    protected $primaryKey = 'id_tipodematerial';
    protected $fillable = ['nombre'];

    public function materiales()
    {
        return $this->hasMany(Material::class, 'id_tipodematerial', 'id_tipodematerial');
    }
}