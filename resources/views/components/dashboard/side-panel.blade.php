@props([
    'panel' => 'admin',
    'selectedRows' => [],
    'slideOverStep' => 'principal',
    'modificarCampo' => '',
])

@if(count($selectedRows ?? []) > 0)
    <aside class="sigem-selection-side-panel"
           x-data
           x-show="$wire.showSidePanel || ($wire.selectedRows && $wire.selectedRows.length > 0)"
           x-cloak>
        @php
            $cantSel = count($selectedRows);
            $selectedRecords = ! empty($selectedRows)
                ? \App\Models\Inventario::with(['material.tipo', 'departamento', 'area', 'salon'])
                    ->whereIn('id_inventario', $selectedRows)
                    ->get()
                : collect();
            $curStep = $this->slideOverStep ?? $slideOverStep ?? 'principal';
            $campoMod = $this->modificarCampo ?? $modificarCampo ?? '';

            $codigosArr = $selectedRecords->map(fn ($r) => '#' . ($r->codigo ?: ($r->num_serie ?: ($r->id_inventario ?? $r->id))))->toArray();
            $codigosPreview = count($codigosArr) <= 5
                ? implode(', ', $codigosArr)
                : implode(', ', array_slice($codigosArr, 0, 5)) . ' (+ ' . (count($codigosArr) - 5) . ' más)';

            $getEstadoStyle = function (?string $st) {
                $estadoReal = match ($st) {
                    'En mantenimiento' => 'En Mantenimiento',
                    'Baja' => 'Dado de baja',
                    default => $st ?: 'Disponible',
                };

                return match ($estadoReal) {
                    'Disponible' => ['label' => 'Disponible', 'dot' => '#22c55e', 'bg' => '#dcfce7', 'text' => '#166534'],
                    'Asignado' => ['label' => 'Asignado', 'dot' => '#3b82f6', 'bg' => '#dbeafe', 'text' => '#1e40af'],
                    'Reasignado' => ['label' => 'Reasignado', 'dot' => '#3b82f6', 'bg' => '#dbeafe', 'text' => '#1e40af'],
                    'En Mantenimiento' => ['label' => 'En Mantenimiento', 'dot' => '#f59e0b', 'bg' => '#fef3c7', 'text' => '#92400e'],
                    'Cambio de área' => ['label' => 'Cambio de área', 'dot' => '#9ca3af', 'bg' => '#f3f4f6', 'text' => '#374151'],
                    'Solicitud de baja' => ['label' => 'Solicitud de baja', 'dot' => '#f59e0b', 'bg' => '#fef3c7', 'text' => '#92400e'],
                    'Dado de baja' => ['label' => 'Dado de baja', 'dot' => '#ef4444', 'bg' => '#fee2e2', 'text' => '#991b1b'],
                    'Dañado' => ['label' => 'Dañado', 'dot' => '#ef4444', 'bg' => '#fee2e2', 'text' => '#991b1b'],
                    'Devuelto a Proveedor' => ['label' => 'Devuelto a Proveedor', 'dot' => '#f59e0b', 'bg' => '#fef3c7', 'text' => '#92400e'],
                    default => ['label' => $estadoReal, 'dot' => '#9ca3af', 'bg' => '#f3f4f6', 'text' => '#374151'],
                };
            };

            $formatItemDesc = function ($rec) {
                $matNom = $rec->material?->nombre ?? $rec->subtipo ?? 'Equipo';
                $areaNom = $rec->area?->nombre;
                if (empty($areaNom) && ! empty($rec->ubicacion_fisica)) {
                    $parts = explode(' > ', $rec->ubicacion_fisica);
                    $areaNom = $parts[1] ?? $parts[0] ?? null;
                }
                $areaNom = $areaNom ?: 'Sin área';
                $aulaNom = $rec->aula
                    ?? $rec->salon?->nombre
                    ?? ($rec->especificaciones['aula'] ?? null)
                    ?? (preg_match('/Aula\s+([A-Za-z0-9_-]+)/i', $rec->ubicacion_fisica ?? '', $m) ? $m[1] : null);

                return $matNom . ' · ' . $areaNom . (! empty($aulaNom) ? ' · Aula ' . $aulaNom : '');
            };

            $resumenEstados = [];
            foreach ($selectedRecords as $rec) {
                $stData = $getEstadoStyle($rec->estado);
                $lbl = $stData['label'];
                if (! isset($resumenEstados[$lbl])) {
                    $resumenEstados[$lbl] = ['info' => $stData, 'count' => 0];
                }
                $resumenEstados[$lbl]['count']++;
            }

            $cantBajas = \App\Support\SigemBajaFormatHelper::filtrarInventariosEnBaja($selectedRecords)->count();
            $estadoAct = $this->cambioNuevoEstado ?? '';
            $deptoAct = $this->cambioDepartamentoId ?? '';

            $necesitaCatalogos = in_array($curStep, ['cambio', 'cambiar', 'modificar', 'baja'], true);
            $todasLasAreas = $necesitaCatalogos ? \App\Models\Area::orderBy('nombre')->pluck('nombre', 'id_area') : collect();
            $todosLosDeptos = in_array($curStep, ['cambio', 'cambiar', 'modificar'], true)
                ? \App\Models\Departamento::orderBy('nombre')->pluck('nombre', 'id_departamento')
                : collect();
            $estadosEnum = in_array($curStep, ['cambio', 'cambiar', 'modificar'], true) ? \App\Enums\EstadoInventario::cases() : [];

            $areasDisponibles = (in_array($curStep, ['cambio', 'cambiar'], true) && ! empty($deptoAct))
                ? \App\Models\Area::where('id_departamento', $deptoAct)->orderBy('nombre')->pluck('nombre', 'id_area')
                : $todasLasAreas;

            $todosLosReceptores = (in_array($curStep, ['cambio', 'cambiar'], true) && in_array($estadoAct, ['Asignado', 'Reasignado'], true))
                ? \App\Models\Receptor::orderBy('nombre')->get()->mapWithKeys(function ($r) {
                    $nom = trim("{$r->nombre} {$r->apellido_paterno} {$r->apellido_materno}");

                    return [$r->id_receptor => ($nom ?: "Receptor #{$r->id_receptor}") . ($r->cargo ? " ({$r->cargo})" : '')];
                })
                : collect();

            $todosLosProveedores = (in_array($curStep, ['cambio', 'cambiar'], true) && $estadoAct === 'Devuelto a Proveedor')
                ? \App\Models\Proveedor::orderBy('nombre_empresa')->pluck('nombre_empresa', 'id_proveedor')
                : collect();

            $motivosBajaOpciones = in_array($curStep, ['cambio', 'cambiar', 'baja'], true)
                ? \App\Support\SigemBajaFormatHelper::motivosBajaOptions()
                : [];

            $propiedades = $selectedRecords->pluck('tipo_propiedad')->unique()->filter();
            $propiedadActual = match ($propiedades->count()) {
                0 => 'Sin definir',
                1 => $propiedades->first(),
                default => 'Mixto',
            };
        @endphp

        @if($curStep === 'principal')
            <x-dashboard.side-panel-principal
                :selected-rows="$selectedRows"
                :selected-records="$selectedRecords"
                :cant-sel="$cantSel"
                :resumen-estados="$resumenEstados"
                :cant-bajas="$cantBajas"
                :get-estado-style="$getEstadoStyle"
                :format-item-desc="$formatItemDesc"
            />
        @elseif($curStep === 'cambio')
            <x-dashboard.side-panel-cambio
                :cant-sel="$cantSel"
                :codigos-preview="$codigosPreview"
                :estado-act="$estadoAct"
                :estados-enum="$estadosEnum"
                :todos-los-deptos="$todosLosDeptos"
                :todas-las-areas="$todasLasAreas"
                :areas-disponibles="$areasDisponibles"
                :todos-los-receptores="$todosLosReceptores"
                :todos-los-proveedores="$todosLosProveedores"
                :motivos-baja-opciones="$motivosBajaOpciones"
                :propiedad-actual="$propiedadActual"
            />
        @elseif($curStep === 'baja')
            <x-dashboard.side-panel-baja
                :panel="$panel"
                :cant-sel="$cantSel"
                :codigos-preview="$codigosPreview"
                :motivos-baja-opciones="$motivosBajaOpciones"
                :todas-las-areas="$todasLasAreas"
            />
        @elseif($curStep === 'modificar')
            <x-dashboard.side-panel-modificar
                :cant-sel="$cantSel"
                :codigos-preview="$codigosPreview"
                :campo-mod="$campoMod"
                :estados-enum="$estadosEnum"
                :todas-las-areas="$todasLasAreas"
                :todos-los-deptos="$todosLosDeptos"
            />
        @endif
    </aside>
@endif
