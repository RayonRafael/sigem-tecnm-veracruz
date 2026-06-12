<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Departamento extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'departamento';
    protected $primaryKey = 'id_departamento';
    protected $fillable = ['nombre'];

    // RELACIONES: Un departamento tiene muchas áreas
    public function areas()
    {
        return $this->hasMany(Area::class, 'id_departamento', 'id_departamento');
    }
}