<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mantenimiento extends Model
{
    use HasFactory;

    protected $table = 'mantenimiento';
    protected $primaryKey = 'id_mantenimiento';
    
    protected $fillable = [
        'id_inventario', 'id_usuario_solicita', 'nombre_tecnico', 'num_control_tecnico',
        'tipo_servicio', 'tipo_mantenimiento', 'descripcion_falla', 'descripcion_trabajo',
        'fecha_solicitud', 'fecha_inicio', 'fecha_fin', 'estado', 'observaciones'
    ];

    protected $casts = [
        'fecha_solicitud' => 'date',
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
    ];

    public function inventario()
    {
        return $this->belongsTo(Inventario::class, 'id_inventario', 'id_inventario');
    }

    public function usuarioSolicita()
    {
        return $this->belongsTo(User::class, 'id_usuario_solicita', 'id');
    }
}