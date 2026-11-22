<?php

namespace App\Models\Concerns;

use App\Models\Receptor;

trait ConUbicacionInventario
{
    public function getTipoEquipoAttribute(): ?string
    {
        return $this->especificaciones['tipo_equipo'] ?? null;
    }

    public function setTipoEquipoAttribute(?string $value): void
    {
        $specs = $this->especificaciones ?? [];
        if ($value !== null && $value !== '') {
            $specs['tipo_equipo'] = $value;
        } else {
            unset($specs['tipo_equipo']);
        }
        $this->especificaciones = $specs;
    }

    public function getEdificioIdAttribute(): ?int
    {
        return isset($this->especificaciones['edificio_id']) && $this->especificaciones['edificio_id'] !== ''
            ? (int) $this->especificaciones['edificio_id']
            : null;
    }

    public function setEdificioIdAttribute($value): void
    {
        $specs = $this->especificaciones ?? [];
        if ($value !== null && $value !== '') {
            $specs['edificio_id'] = (int) $value;
        } else {
            unset($specs['edificio_id']);
        }
        $this->especificaciones = $specs;
    }

    public function getIdReceptorAttribute(): ?int
    {
        return isset($this->especificaciones['id_receptor']) && $this->especificaciones['id_receptor'] !== ''
            ? (int) $this->especificaciones['id_receptor']
            : null;
    }

    public function setIdReceptorAttribute($value): void
    {
        $specs = $this->especificaciones ?? [];
        if ($value !== null && $value !== '') {
            $receptorId = (int) $value;
            $specs['id_receptor'] = $receptorId;
            if ($receptor = Receptor::find($receptorId)) {
                $specs['receptor_nombre'] = $receptor->nombre_completo;
            }
        } else {
            unset($specs['id_receptor'], $specs['receptor_nombre']);
        }
        $this->especificaciones = $specs;
    }

    public function getReceptorNombreAttribute(): ?string
    {
        return $this->especificaciones['receptor_nombre']
            ?? $this->receptor?->nombre_completo;
    }

    public function getDepartamentoIdAttribute(): ?int
    {
        if (array_key_exists('departamento_id', $this->attributes) && $this->attributes['departamento_id'] !== null && $this->attributes['departamento_id'] !== '') {
            return (int) $this->attributes['departamento_id'];
        }

        return isset($this->especificaciones['departamento_id']) && $this->especificaciones['departamento_id'] !== ''
            ? (int) $this->especificaciones['departamento_id']
            : null;
    }

    public function setDepartamentoIdAttribute($value): void
    {
        $val = ($value !== null && $value !== '') ? (int) $value : null;
        $this->attributes['departamento_id'] = $val;

        $specs = $this->especificaciones ?? [];
        if ($val !== null) {
            $specs['departamento_id'] = $val;
        } else {
            unset($specs['departamento_id']);
        }
        $this->especificaciones = $specs;
    }

    public function getAreaIdAttribute(): ?int
    {
        if (array_key_exists('area_id', $this->attributes) && $this->attributes['area_id'] !== null && $this->attributes['area_id'] !== '') {
            return (int) $this->attributes['area_id'];
        }

        return isset($this->especificaciones['area_id']) && $this->especificaciones['area_id'] !== ''
            ? (int) $this->especificaciones['area_id']
            : null;
    }

    public function setAreaIdAttribute($value): void
    {
        $val = ($value !== null && $value !== '') ? (int) $value : null;
        $this->attributes['area_id'] = $val;

        $specs = $this->especificaciones ?? [];
        if ($val !== null) {
            $specs['area_id'] = $val;
        } else {
            unset($specs['area_id']);
        }
        $this->especificaciones = $specs;
    }

    public function getSalonIdAttribute(): ?int
    {
        if (array_key_exists('salon_id', $this->attributes) && $this->attributes['salon_id'] !== null && $this->attributes['salon_id'] !== '') {
            return (int) $this->attributes['salon_id'];
        }

        return isset($this->especificaciones['salon_id']) && $this->especificaciones['salon_id'] !== ''
            ? (int) $this->especificaciones['salon_id']
            : null;
    }

    public function setSalonIdAttribute($value): void
    {
        $val = ($value !== null && $value !== '') ? (int) $value : null;
        $this->attributes['salon_id'] = $val;

        $specs = $this->especificaciones ?? [];
        if ($val !== null) {
            $specs['salon_id'] = $val;
        } else {
            unset($specs['salon_id']);
        }
        $this->especificaciones = $specs;
    }
}
