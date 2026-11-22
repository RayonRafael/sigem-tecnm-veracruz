<?php

namespace App\Models\Concerns;

use App\Support\SigemReportExporter;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

trait ConRentaInventario
{
    /**
     * Fecha fin de renta: toma especificaciones['renta_fecha_fin'], respaldo en fecha_fin_renta,
     * o calcula automáticamente fecha_registro + 3 años cuando tipo_propiedad = 'Rentado'.
     */
    public function getRentaFechaFinAttribute(): ?Carbon
    {
        if ($this->tipo_propiedad !== 'Rentado') {
            return null;
        }

        if (! empty($this->especificaciones['renta_fecha_fin'])) {
            return Carbon::parse($this->especificaciones['renta_fecha_fin'])->startOfDay();
        }

        if ($this->fecha_fin_renta) {
            return Carbon::parse($this->fecha_fin_renta)->startOfDay();
        }

        if ($this->fecha_registro) {
            return Carbon::parse($this->fecha_registro)->startOfDay()->addYears(3);
        }

        return null;
    }

    /**
     * Estado semafórico de la renta: 'Vencida' (🔴), 'Por vencer' (🟡 0-6 meses), 'Vigente' (🟢 > 6 meses).
     */
    public function getRentaEstadoAttribute(): ?string
    {
        if ($this->tipo_propiedad !== 'Rentado') {
            return null;
        }

        $fin = $this->renta_fecha_fin;
        if (! $fin) {
            return null;
        }

        $hoy = Carbon::now()->startOfDay();

        if ($fin->lt($hoy)) {
            return 'Vencida';
        }

        if ($fin->lte($hoy->copy()->addMonths(6))) {
            return 'Por vencer';
        }

        return 'Vigente';
    }

    /**
     * Días restantes (positivo) o días transcurridos desde vencimiento (negativo).
     */
    public function getRentaDiasRestantesAttribute(): ?int
    {
        if ($this->tipo_propiedad !== 'Rentado') {
            return null;
        }

        $fin = $this->renta_fecha_fin;

        return $fin ? (int) Carbon::now()->startOfDay()->diffInDays($fin->copy()->startOfDay(), false) : null;
    }

    /**
     * Resumen consolidado de equipos rentados para Dashboard y alertas (cacheado O(1) con versión de reportes).
     *
     * @return array{total: int, vigentes: int, por_vencer: int, vencidas: int}
     */
    public static function resumenRentas(): array
    {
        $ver = SigemReportExporter::cacheVersion();

        return Cache::remember("sigem_resumen_rentas_v{$ver}", SigemReportExporter::CACHE_TTL_SECONDS, function () {
            $rentados = static::query()
                ->select(['id_inventario', 'tipo_propiedad', 'fecha_registro', 'fecha_fin_renta', 'especificaciones'])
                ->where('tipo_propiedad', 'Rentado')
                ->get();

            $vigentes = 0;
            $porVencer = 0;
            $vencidas = 0;

            foreach ($rentados as $inv) {
                match ($inv->renta_estado) {
                    'Vencida' => $vencidas++,
                    'Por vencer' => $porVencer++,
                    'Vigente' => $vigentes++,
                    default => null,
                };
            }

            return [
                'total' => $rentados->count(),
                'vigentes' => $vigentes,
                'por_vencer' => $porVencer,
                'vencidas' => $vencidas,
            ];
        });
    }
}
