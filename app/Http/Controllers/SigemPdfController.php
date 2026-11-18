<?php

namespace App\Http\Controllers;

use App\Models\Inventario;
use App\Support\SigemBajaFormatHelper;
use App\Support\SigemPdfGenerator;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SigemPdfController
{
    /**
     * Descarga el Formato 4 (Trámite de Baja) individual para un bien en proceso o estado de baja.
     */
    public function formatoBaja(Request $request, Inventario $inventario): StreamedResponse
    {
        $user = $request->user();
        abort_unless($user && $user->can('view', $inventario), 403);
        abort_unless(SigemBajaFormatHelper::esEstadoBaja($inventario->estado), 404);

        return SigemPdfGenerator::descargarFormatoBaja($inventario);
    }

    /**
     * Descarga el Formato de Cambio de Área para un equipo de inventario.
     */
    public function cambioArea(Inventario $inventario): StreamedResponse
    {
        $user = auth()->user();
        if (! $user) {
            abort(403);
        }

        $tieneRoles = method_exists($user, 'roles') && $user->roles()->exists();
        if ($tieneRoles && ! $user->can('view', $inventario)) {
            abort(403);
        }

        return SigemPdfGenerator::descargarCambioArea($inventario);
    }
}
