<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Receptor extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'receptor';

    protected $primaryKey = 'id_receptor';

    protected $fillable = ['nombre', 'apellido_paterno', 'apellido_materno', 'tipo', 'num_control', 'cargo', 'email', 'telefono', 'id_area'];

    public function getNombreCompletoAttribute(): string
    {
        return trim(implode(' ', array_filter([
            $this->nombre,
            $this->apellido_paterno,
            $this->apellido_materno,
        ])));
    }

    public function area()
    {
        return $this->belongsTo(Area::class, 'id_area', 'id_area');
    }

    public function solicitudes()
    {
        return $this->hasMany(Solicitud::class, 'id_receptor', 'id_receptor');
    }
}
