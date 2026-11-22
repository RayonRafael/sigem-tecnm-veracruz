<?php

namespace App\Services;

use App\Models\User;
use Spatie\Activitylog\Models\Activity;

class ConfigBitacoraService
{
    public static function getBitacoraData(array $filtros = []): array
    {
        $query = Activity::with(['causer.roles'])->latest('created_at');

        if (! empty($filtros['search'])) {
            $s = trim((string) $filtros['search']);
            $query->where(function ($q) use ($s) {
                $q->where('description', 'like', "%{$s}%")
                    ->orWhere('event', 'like', "%{$s}%")
                    ->orWhere('subject_type', 'like', "%{$s}%")
                    ->orWhereHasMorph('causer', [User::class], function ($uq) use ($s) {
                        $uq->where('name', 'like', "%{$s}%")
                            ->orWhere('email', 'like', "%{$s}%");
                    });
            });
        }

        if (! empty($filtros['usuario'])) {
            $query->where('causer_id', $filtros['usuario']);
        }

        if (! empty($filtros['rol'])) {
            $rol = $filtros['rol'];
            $query->whereHasMorph('causer', [User::class], function ($q) use ($rol) {
                $q->whereHas('roles', function ($rq) use ($rol) {
                    $rq->where('name', $rol);
                });
            });
        }

        if (! empty($filtros['accion'])) {
            $query->where('event', $filtros['accion']);
        }

        $activities = $query->limit(60)->get();
        $rows = [];

        foreach ($activities as $a) {
            $user = $a->causer;
            $rolName = $user?->roles?->first()?->name;

            $rolPill = match ($rolName) {
                'Administrador' => '<span class="sigem-rol-pill adm">Administrador</span>',
                'Servicio Social' => '<span class="sigem-rol-pill ss">Servicio Social</span>',
                default => '<span class="sigem-rol-pill" style="background:var(--sig-bg);color:var(--sig-muted)">' . e($rolName ?? 'Sistema') . '</span>',
            };

            $accionTipo = match (strtolower($a->event ?? '')) {
                'created', 'creado', 'alta' => 'ok',
                'updated', 'actualizado', 'modificado' => 'info',
                'deleted', 'eliminado', 'baja' => 'danger',
                default => 'neutral',
            };

            $accionLabel = match (strtolower($a->event ?? '')) {
                'created' => 'Creación',
                'updated' => 'Actualización',
                'deleted' => 'Eliminación',
                default => ucfirst($a->event ?? 'Registro'),
            };

            $rows[] = [
                'fecha' => $a->created_at ? $a->created_at->format('d/m/Y H:i') : 'N/A',
                'usuario' => '<strong>' . e($user?->name ?? 'Sistema') . '</strong>',
                'rol' => $rolPill,
                'accion' => '<span class="sigem-pill ' . $accionTipo . '">' . e($accionLabel) . '</span>',
                'detalle' => e($a->description ?? class_basename($a->subject_type ?? 'Sistema')),
            ];
        }

        return $rows;
    }

    public static function getBitacoraUsuarios(): array
    {
        return User::orderBy('name')->pluck('name', 'id')->toArray();
    }
}
