<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Solicitud extends Model
{
    use HasFactory, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['estado', 'fecha_autorizacion', 'autorizado_por', 'fecha_devolucion_real', 'observaciones', 'tipo_movimiento'])
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

    protected $table = 'solicitud';

    protected $primaryKey = 'id_solicitud';

    protected $fillable = [
        'fecha_solicitud', 'observaciones', 'fecha_autorizacion', 'autorizado_por',
        'estado', 'fecha_devolucion_estimada', 'fecha_devolucion_real',
        'id_usuario', 'id_receptor', 'tipo_movimiento',
    ];

    protected $casts = [
        'fecha_solicitud' => 'date',
        'fecha_autorizacion' => 'date',
        'fecha_devolucion_estimada' => 'date',
        'fecha_devolucion_real' => 'date',
    ];

    public function usuario()
    {
        return $this->belongsTo(User::class, 'id_usuario', 'id');
    }

    public function receptor()
    {
        return $this->belongsTo(Receptor::class, 'id_receptor', 'id_receptor');
    }

    public function autorizadoPor()
    {
        return $this->belongsTo(User::class, 'autorizado_por', 'id');
    }

    public function detalles()
    {
        return $this->hasMany(DetalleSolicitud::class, 'id_solicitud', 'id_solicitud');
    }

    // Scopes de estado
    public function scopePendientes($query)
    {
        return $query->where('estado', 'Pendiente');
    }

    public function scopeAutorizadas($query)
    {
        return $query->where('estado', 'Autorizado');
    }

    public function scopeRechazadas($query)
    {
        return $query->where('estado', 'Rechazado');
    }
}
