<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Salon extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'salones';

    protected $fillable = [
        'nombre',
        'area_id',
    ];

    // RELACIONES: Un salón pertenece a un área
    public function area()
    {
        return $this->belongsTo(Area::class, 'area_id', 'id_area');
    }
}
