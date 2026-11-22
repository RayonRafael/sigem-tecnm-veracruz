<?php

namespace App\Models\Concerns;

use App\Models\Material;

trait ConCodigoInventario
{
    /**
     * Auto-genera un código institucional con formato MARCA-MODELO-### (ej. HP-LJ4103-001).
     */
    public function generarCodigo(): string
    {
        $material = $this->material ?: ($this->id_producto ? Material::with('marca')->find($this->id_producto) : null);

        $marcaRaw = $material?->marca?->nombre ?? '';
        $modeloRaw = $this->modelo ?: ($material?->modelo ?? '');

        $marca = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $marcaRaw));
        if (empty($marca)) {
            $marca = 'ACT';
        }

        $modelo = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $modeloRaw));
        if (empty($modelo)) {
            $modelo = 'GEN';
        }

        $prefix = "{$marca}-{$modelo}";

        $codigosExistentes = static::withTrashed()
            ->where('codigo', 'LIKE', "{$prefix}-%")
            ->pluck('codigo')
            ->map(function ($cod) {
                return preg_match('/-(\d+)$/', $cod, $matches) ? (int) $matches[1] : 0;
            });

        $maxConsecutivo = $codigosExistentes->max() ?? 0;
        $consecutivo = $maxConsecutivo + 1;

        $codigo = sprintf('%s-%03d', $prefix, $consecutivo);

        while (static::withTrashed()->where('codigo', $codigo)->exists()) {
            $consecutivo++;
            $codigo = sprintf('%s-%03d', $prefix, $consecutivo);
        }

        return $codigo;
    }
}
