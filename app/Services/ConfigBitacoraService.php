<?php

namespace App\Services;

use App\Models\Inventario;
use App\Models\Mantenimiento;
use App\Models\User;
use Spatie\Activitylog\Models\Activity;

class ConfigBitacoraService
{
    public static function getBitacoraData(array $filtros = []): array
    {
        $query = Activity::with(['causer.roles'])->latest('created_at');

        if (! empty($filtros['search'])) {
            $s = trim((string) $filtros['search']);
            $invMatchIds = Inventario::withTrashed()
                ->where(function ($iq) use ($s) {
                    $iq->where('codigo', 'like', "%{$s}%")
                        ->orWhere('num_serie', 'like', "%{$s}%");
                })
                ->pluck('id_inventario')
                ->all();

            $query->where(function ($q) use ($s, $invMatchIds) {
                $q->where('description', 'like', "%{$s}%")
                    ->orWhere('event', 'like', "%{$s}%")
                    ->orWhere('subject_type', 'like', "%{$s}%")
                    ->orWhere('properties', 'like', "%{$s}%")
                    ->orWhereHasMorph('causer', [User::class], function ($uq) use ($s) {
                        $uq->where('name', 'like', "%{$s}%")
                            ->orWhere('email', 'like', "%{$s}%");
                    });

                if (! empty($invMatchIds)) {
                    $q->orWhere(function ($sq) use ($invMatchIds) {
                        $sq->where('subject_type', Inventario::class)
                            ->whereIn('subject_id', $invMatchIds);
                    });
                }
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

        // Precarga en lote de Inventario (incluyendo soft-deleted) y Mantenimiento sin N+1
        $invIds = $activities
            ->where('subject_type', Inventario::class)
            ->pluck('subject_id')
            ->filter()
            ->unique()
            ->values()
            ->all();

        $mantIds = $activities
            ->where('subject_type', Mantenimiento::class)
            ->pluck('subject_id')
            ->filter()
            ->unique()
            ->values()
            ->all();

        $mantsById = ! empty($mantIds)
            ? Mantenimiento::whereIn('id_mantenimiento', $mantIds)
                ->get(['id_mantenimiento', 'id_inventario'])
                ->keyBy('id_mantenimiento')
            : collect();

        $invIdsFromMant = $mantsById->pluck('id_inventario')->filter()->unique()->all();
        $allInvIds = array_values(array_unique(array_merge($invIds, $invIdsFromMant)));

        $inventariosById = ! empty($allInvIds)
            ? Inventario::withTrashed()
                ->whereIn('id_inventario', $allInvIds)
                ->get(['id_inventario', 'codigo', 'num_serie'])
                ->keyBy('id_inventario')
            : collect();

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

            $equipoLabel = self::resolverEquipoActivity($a, $inventariosById, $mantsById);

            $rows[] = [
                'id' => $a->id,
                'fecha' => $a->created_at ? $a->created_at->format('d/m/Y H:i') : 'N/A',
                'usuario' => '<strong>' . e($user?->name ?? 'Sistema') . '</strong>',
                'rol' => $rolPill,
                'accion' => '<span class="sigem-pill ' . $accionTipo . '">' . e($accionLabel) . '</span>',
                'equipo' => $equipoLabel,
                'detalle' => e($a->description ?? class_basename($a->subject_type ?? 'Sistema')),
            ];
        }

        return $rows;
    }

    /**
     * Resuelve el identificador legible del equipo (código o S/N) para un registro de activity_log.
     * Retorna '—' si el evento no corresponde a un equipo de inventario.
     */
    protected static function resolverEquipoActivity(Activity $activity, $inventariosById, $mantsById): string
    {
        $props = $activity->properties ? $activity->properties->toArray() : [];

        if ($activity->subject_type === Inventario::class) {
            $inv = $activity->subject_id ? $inventariosById->get($activity->subject_id) : null;
            $candidatos = [
                $inv?->codigo,
                $props['equipo_codigo'] ?? null,
                $props['attributes']['codigo'] ?? null,
                $props['old']['codigo'] ?? null,
                $inv?->num_serie,
                $props['equipo_num_serie'] ?? null,
                $props['attributes']['num_serie'] ?? null,
                $props['old']['num_serie'] ?? null,
            ];

            foreach ($candidatos as $cand) {
                $clean = trim((string) $cand);
                if ($clean !== '') {
                    return $clean;
                }
            }

            return $activity->subject_id ? ('#' . $activity->subject_id) : '—';
        }

        if ($activity->subject_type === Mantenimiento::class && $activity->subject_id) {
            $mant = $mantsById->get($activity->subject_id);
            $inv = $mant?->id_inventario ? $inventariosById->get($mant->id_inventario) : null;
            if ($inv) {
                $cod = trim((string) ($inv->codigo ?? ''));
                if ($cod !== '') {
                    return $cod;
                }
                $sn = trim((string) ($inv->num_serie ?? ''));
                if ($sn !== '') {
                    return $sn;
                }

                return '#' . $inv->id_inventario;
            }
        }

        return '—';
    }

    public static function getBitacoraUsuarios(): array
    {
        return User::orderBy('name')->pluck('name', 'id')->toArray();
    }
}
