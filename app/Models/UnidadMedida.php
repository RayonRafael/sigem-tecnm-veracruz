<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UnidadMedida extends Model
{
    use HasFactory;

    protected $table = 'unidad_medida';
    protected $primaryKey = 'id_unidad';
    protected $fillable = ['nombre'];

    public function materiales()
    {
        return $this->hasMany(Material::class, 'id_unidad', 'id_unidad');
    }
}