<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TipoMaterial extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'tipo_material';
    protected $primaryKey = 'id_tipodematerial';
    protected $fillable = ['nombre'];

    public function materiales()
    {
        return $this->hasMany(Material::class, 'id_tipodematerial', 'id_tipodematerial');
    }
}