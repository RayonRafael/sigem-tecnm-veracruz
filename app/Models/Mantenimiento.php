<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Mantenimiento extends Model
{
    use HasFactory, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['estado', 'tipo_servicio', 'tipo_mantenimiento', 'descripcion_falla', 'descripcion_trabajo', 'fecha_inicio', 'fecha_fin', 'observaciones'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(function (string $eventName) {
                $acciones = [
                    'created' => 'creado',
                    'updated' => 'actualizado',
                    'deleted' => 'eliminado',
                ];
                return $acciones[$eventName] ?? $eventName;
            });
    }

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

    // Scopes de estado
    public function scopeEnRevision($query)
    {
        return $query->whereIn('estado', ['Pendiente Revision Admin', 'Solicitado']);
    }

    public function scopeEnProceso($query)
    {
        return $query->where('estado', 'En proceso');
    }

    public function scopeCompletados($query)
    {
        return $query->where('estado', 'Completado');
    }
}