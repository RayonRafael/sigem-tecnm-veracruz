<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Edificio extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'edificios';

    protected $fillable = [
        'nombre',
        'letra',
    ];

    // RELACIONES: Un edificio tiene muchos departamentos
    public function departamentos()
    {
        return $this->hasMany(Departamento::class, 'edificio_id');
    }
}
