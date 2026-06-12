<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements FilamentUser
{
    use HasFactory, Notifiable;

    /**
     * Los atributos que se pueden asignar en masa.
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'apellido_paterno',
        'apellido_materno',
        'num_control',
        'carrera',
        'RFC',
        'tipo_usuario',
        'activo',
    ];

    /**
     * Los atributos que deben ocultarse para la serialización.
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Obtener los atributos que deben ser convertidos.
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'activo' => 'boolean',
        ];
    }

    /**
     * Método requerido por Filament para permitir el acceso al panel.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return true; // Por ahora permitimos el acceso a todos.
    }

    // ==========================================
    // RELACIONES CON OTROS MODELOS
    // ==========================================

    public function inventarios()
    {
        return $this->hasMany(Inventario::class, 'id_usuario', 'id');
    }

    public function solicitudes()
    {
        return $this->hasMany(Solicitud::class, 'id_usuario', 'id');
    }

    public function autorizaciones()
    {
        return $this->hasMany(Solicitud::class, 'autorizado_por', 'id');
    }

    public function mantenimientosSolicitados()
    {
        return $this->hasMany(Mantenimiento::class, 'id_usuario_solicita', 'id');
    }
    
    public function historialEstados()
    {
        return $this->hasMany(HistorialEstado::class, 'id_usuario', 'id');
    }
    
    public function bitacora()
    {
        return $this->hasMany(BitacoraSistema::class, 'id_usuario', 'id');
    }
}