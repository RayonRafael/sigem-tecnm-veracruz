<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MarcaMaterial extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'marca_material';
    protected $primaryKey = 'id_marca';
    protected $fillable = ['nombre'];

    // RELACIONES: Una marca tiene muchos materiales
    public function materiales()
    {
        return $this->hasMany(Material::class, 'id_marca', 'id_marca');
    }
}