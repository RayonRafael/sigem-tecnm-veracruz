<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BitacoraSistema extends Model
{
    use HasFactory;

    protected $table = 'bitacora_sistema';
    protected $primaryKey = 'id_bitacora';
    public $timestamps = false;
    
    protected $fillable = [
        'id_usuario', 'accion', 'tabla_afectada', 'registro_id', 
        'ip_address', 'user_agent', 'detalles', 'datos_anteriores', 'datos_nuevos'
    ];

    // Convertir los campos JSON automáticamente a arrays de PHP
    protected $casts = [
        'datos_anteriores' => 'array',
        'datos_nuevos' => 'array',
    ];

    public function usuario()
    {
        return $this->belongsTo(User::class, 'id_usuario', 'id');
    }
}