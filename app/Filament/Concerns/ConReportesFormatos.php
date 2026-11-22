<?php

namespace App\Filament\Concerns;

use App\Models\Inventario;
use App\Models\Mantenimiento;
use App\Models\Solicitud;
use App\Support\SigemBajaFormatHelper;
use App\Support\SigemPdfGenerator;
use App\Support\SigemReportExporter;
use Filament\Notifications\Notification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Computed;

trait ConReportesFormatos
{
    public ?string $formatoSeleccionadoCambioArea = '';

    public ?string $formatoFechaDesde = null;

    public ?string $formatoFechaHasta = null;

    public ?string $formatoDepartamentoId = '';

    public ?string $formatoSeleccionadoEntrega = '';

    public ?string $formatoSeleccionadoOrden = '';

    public ?string $formatoSeleccionadoVerificacion = '';

    /** @var array<int|string> */
    public array $formatosSeleccionadosBaja = [];

    public string $formatoFiltroTipo = 'baja';

    public string $pestanaFormatoActiva = 'entrega';

    /**
     * Metadata de los Formatos Institucionales en PDF del TecNM Veracruz.
     *
     * @return list<array{id: string, emoji: string, titulo: string, subtitulo: string, descripcion: string}>
     */
    public function getFormatosInstitucionales(bool $incluirCambioArea = false): array
    {
        $formatos = [
            [
                'id' => 'entrega',
                'emoji' => '📄',
                'titulo' => 'Entrega de Equipo',
                'subtitulo' => 'Formato 1',
                'descripcion' => 'Genera acta oficial de entrega de equipo o bien con receptor y unidad orgánica.',
            ],
            [
                'id' => 'orden_trabajo',
                'emoji' => '📄',
                'titulo' => 'Orden de Trabajo',
                'subtitulo' => 'Formato 2',
                'descripcion' => 'Genera orden de trabajo de mantenimiento y servicio técnico del Centro de Cómputo.',
            ],
            [
                'id' => 'lista_verificacion',
                'emoji' => '📄',
                'titulo' => 'Lista Verificación',
                'subtitulo' => 'Formato 3',
                'descripcion' => 'Genera lista de verificación con hallazgo, atención y validación técnica.',
            ],
            [
                'id' => 'baja',
                'emoji' => '📄',
                'titulo' => 'Trámite de Baja',
                'subtitulo' => 'Formato 4',
                'descripcion' => 'Genera trámite de baja de mobiliario y equipo individual o en lote multipágina.',
            ],
        ];

        if ($incluirCambioArea) {
            $formatos[] = [
                'id' => 'cambio_area',
                'emoji' => '🔄',
                'titulo' => 'Formato de Cambio de Área',
                'subtitulo' => 'Formato 5',
                'descripcion' => 'Constancia oficial de reubicación y traslado de bienes entre departamentos y áreas.',
            ];
        }

        return $formatos;
    }

    public function cambiarPestanaFormato(string $pestana): void
    {
        $validas = ['entrega', 'orden_trabajo', 'lista_verificacion', 'baja', 'cambio_area', 'filtrar'];
        if (in_array($pestana, $validas, true)) {
            $this->pestanaFormatoActiva = $pestana;
        }
    }

    public function updatedFormatoFechaDesde(): void
    {
        $this->reiniciarSeleccionesFormatos();
    }

    public function updatedFormatoFechaHasta(): void
    {
        $this->reiniciarSeleccionesFormatos();
    }

    public function updatedFormatoDepartamentoId(): void
    {
        $this->reiniciarSeleccionesFormatos();
    }

    protected function reiniciarSeleccionesFormatos(): void
    {
        $this->formatoSeleccionadoEntrega = '';
        $this->formatoSeleccionadoOrden = '';
        $this->formatoSeleccionadoVerificacion = '';
        $this->formatoSeleccionadoCambioArea = '';
        $this->formatosSeleccionadosBaja = [];
    }

    public function limpiarFiltrosFormatos(): void
    {
        $this->formatoFechaDesde = null;
        $this->formatoFechaHasta = null;
        $this->formatoDepartamentoId = '';
        $this->reiniciarSeleccionesFormatos();
    }

    protected function aplicarFiltroFechaFormato($query, string $columnaFecha): void
    {
        if (filled($this->formatoFechaDesde)) {
            $desde = $this->formatoFechaDesde;
            $query->where(function ($q) use ($columnaFecha, $desde): void {
                $q->whereDate($columnaFecha, '>=', $desde)
                    ->orWhere(fn ($sub) => $sub->whereNull($columnaFecha)->whereDate('created_at', '>=', $desde));
            });
        }

        if (filled($this->formatoFechaHasta)) {
            $hasta = $this->formatoFechaHasta;
            $query->where(function ($q) use ($columnaFecha, $hasta): void {
                $q->whereDate($columnaFecha, '<=', $hasta)
                    ->orWhere(fn ($sub) => $sub->whereNull($columnaFecha)->whereDate('created_at', '<=', $hasta));
            });
        }
    }

    protected function aplicarFiltroDeptoInventario($query, ?int $deptoId): void
    {
        if (! $deptoId) {
            return;
        }

        $query->where(function ($q) use ($deptoId): void {
            $q->where('departamento_id', $deptoId)
                ->orWhereHas('area', fn ($a) => $a->where('id_departamento', $deptoId));
        });
    }

    protected function baseQuerySolicitudesParaFormato()
    {
        $query = Solicitud::query()
            ->where('estado', '!=', 'Rechazado')
            ->whereIn('tipo_movimiento', [
                'Asignacion Permanente',
                'Asignacion Temporal',
                'Renta Externa',
                'Salida',
                'Traslado',
                'Préstamo',
                'Adaptación',
            ]);

        if (! $this->esUsuarioAdminReportes()) {
            $query->where('id_usuario', auth()->id() ?? 0);
        }

        $this->aplicarFiltroFechaFormato($query, 'fecha_solicitud');

        $deptoId = filled($this->formatoDepartamentoId) ? (int) $this->formatoDepartamentoId : null;
        if ($deptoId) {
            $query->whereHas('receptor.area', fn ($a) => $a->where('id_departamento', $deptoId));
        }

        return $query;
    }

    public function getSolicitudesParaFormato(): Collection
    {
        return $this->baseQuerySolicitudesParaFormato()
            ->with(['receptor.area.departamento', 'detalles.material'])
            ->orderByDesc('fecha_solicitud')
            ->orderByDesc('id_solicitud')
            ->limit(100)
            ->get();
    }

    protected function baseQueryMantenimientosParaFormato(bool $withRelations = true)
    {
        $query = Mantenimiento::query()
            ->where('estado', '!=', 'Cancelado');

        if ($withRelations) {
            $query->with(['inventario.material', 'inventario.departamento', 'inventario.area.departamento', 'usuarioSolicita']);
        }

        if (! $this->esUsuarioAdminReportes()) {
            $query->where('id_usuario_solicita', auth()->id() ?? 0);
        }

        $this->aplicarFiltroFechaFormato($query, 'fecha_solicitud');

        $deptoId = filled($this->formatoDepartamentoId) ? (int) $this->formatoDepartamentoId : null;
        if ($deptoId) {
            $query->whereHas('inventario', fn ($inv) => $this->aplicarFiltroDeptoInventario($inv, $deptoId));
        }

        return $query;
    }

    public function getMantenimientosParaOrdenFormato(): Collection
    {
        return $this->baseQueryMantenimientosParaFormato()
            ->orderByDesc('fecha_solicitud')
            ->orderByDesc('id_mantenimiento')
            ->limit(100)
            ->get();
    }

    public function getMantenimientosParaVerificacionFormato(): Collection
    {
        return $this->baseQueryMantenimientosParaFormato()
            ->orderByRaw("CASE WHEN estado = 'Completado' THEN 0 ELSE 1 END")
            ->orderByDesc('fecha_fin')
            ->orderByDesc('fecha_solicitud')
            ->orderByDesc('id_mantenimiento')
            ->limit(100)
            ->get();
    }

    protected function baseQueryInventariosBajaParaFormato()
    {
        $query = Inventario::query()
            ->whereIn('estado', SigemBajaFormatHelper::ESTADOS_BAJA);

        $this->aplicarFiltroFechaFormato($query, 'fecha_baja');

        $deptoId = filled($this->formatoDepartamentoId) ? (int) $this->formatoDepartamentoId : null;
        $this->aplicarFiltroDeptoInventario($query, $deptoId);

        return $query;
    }

    public function getInventariosBajaParaFormato(): Collection
    {
        return $this->baseQueryInventariosBajaParaFormato()
            ->with(['material.marca', 'departamento', 'area.departamento'])
            ->orderByDesc('fecha_baja')
            ->orderByDesc('id_inventario')
            ->limit(SigemBajaFormatHelper::MAX_LOTE_BAJAS)
            ->get();
    }

    protected function baseQueryInventariosCambioAreaParaFormato()
    {
        $query = Inventario::query()
            ->where(function ($q) {
                $q->whereNotNull('fecha_cambio_area')
                    ->orWhere('estado', 'Cambio de área')
                    ->orWhereNotNull('especificaciones->cambio_origen_depto');
            });

        $this->aplicarFiltroFechaFormato($query, 'fecha_cambio_area');

        $deptoId = filled($this->formatoDepartamentoId) ? (int) $this->formatoDepartamentoId : null;
        $this->aplicarFiltroDeptoInventario($query, $deptoId);

        return $query;
    }

    public function getInventariosCambioAreaParaFormato(): Collection
    {
        return $this->baseQueryInventariosCambioAreaParaFormato()
            ->with(['material.marca', 'departamento', 'area.departamento', 'salon'])
            ->orderByDesc('fecha_cambio_area')
            ->orderByDesc('id_inventario')
            ->limit(100)
            ->get();
    }

    /**
     * Contadores ligeros cacheados para las pestañas de Formatos PDF.
     *
     * @return array{entrega: int, orden_trabajo: int, lista_verificacion: int, baja: int, cambio_area: int}
     */
    #[Computed]
    public function conteosFormatos(): array
    {
        $ver = SigemReportExporter::cacheVersion();
        $scope = $this->esUsuarioAdminReportes() ? 'admin' : ('u' . (auth()->id() ?? 0));
        $hash = md5(json_encode([
            $scope,
            (string) $this->formatoFechaDesde,
            (string) $this->formatoFechaHasta,
            (string) $this->formatoDepartamentoId,
        ]));

        return Cache::remember("sigem_fmt_cnt_v{$ver}_{$hash}", SigemReportExporter::CACHE_TTL_SECONDS, function () {
            $cntEntrega = min(100, (int) $this->baseQuerySolicitudesParaFormato()->count());
            $cntMant = min(100, (int) $this->baseQueryMantenimientosParaFormato(false)->count());
            $cntBaja = min(SigemBajaFormatHelper::MAX_LOTE_BAJAS, (int) $this->baseQueryInventariosBajaParaFormato()->count());
            $cntCambio = min(100, (int) $this->baseQueryInventariosCambioAreaParaFormato()->count());

            return [
                'entrega' => $cntEntrega,
                'orden_trabajo' => $cntMant,
                'lista_verificacion' => $cntMant,
                'baja' => $cntBaja,
                'cambio_area' => $cntCambio,
            ];
        });
    }

    public function getFilasFiltradasFormatos(): Collection
    {
        return match ($this->formatoFiltroTipo) {
            'entrega' => $this->getSolicitudesParaFormato()->map(fn ($s) => [
                'tipo_nombre' => 'Entrega',
                'tipo_clave' => 'entrega',
                'id' => $s->id_solicitud,
                'codigo' => 'FOLIO-' . str_pad((string) $s->id_solicitud, 5, '0', STR_PAD_LEFT),
                'depto' => $s->receptor?->area?->departamento?->nombre ?? ($s->receptor?->area?->nombre ?? '—'),
                'fecha' => $s->fecha_solicitud ? $s->fecha_solicitud->format('d/m/Y') : '—',
            ]),
            'orden_trabajo' => $this->getMantenimientosParaOrdenFormato()->map(fn ($m) => [
                'tipo_nombre' => 'Orden de Trabajo',
                'tipo_clave' => 'orden_trabajo',
                'id' => $m->id_mantenimiento,
                'codigo' => 'MANT-' . str_pad((string) $m->id_mantenimiento, 4, '0', STR_PAD_LEFT),
                'depto' => $m->inventario?->departamento?->nombre ?? ($m->inventario?->area?->departamento?->nombre ?? '—'),
                'fecha' => $m->fecha_solicitud ? $m->fecha_solicitud->format('d/m/Y') : '—',
            ]),
            'lista_verificacion' => $this->getMantenimientosParaVerificacionFormato()->map(fn ($m) => [
                'tipo_nombre' => 'Lista Verificación',
                'tipo_clave' => 'lista_verificacion',
                'id' => $m->id_mantenimiento,
                'codigo' => 'MANT-' . str_pad((string) $m->id_mantenimiento, 4, '0', STR_PAD_LEFT),
                'depto' => $m->inventario?->departamento?->nombre ?? ($m->inventario?->area?->departamento?->nombre ?? '—'),
                'fecha' => $m->fecha_fin ? $m->fecha_fin->format('d/m/Y') : ($m->fecha_solicitud ? $m->fecha_solicitud->format('d/m/Y') : '—'),
            ]),
            'cambio_area' => $this->getInventariosCambioAreaParaFormato()->map(fn ($c) => [
                'tipo_nombre' => 'Cambio de Área',
                'tipo_clave' => 'cambio_area',
                'id' => $c->id_inventario,
                'codigo' => $c->codigo ?: ($c->num_serie ?: ('#' . $c->id_inventario)),
                'depto' => $c->departamento?->nombre ?? ($c->area?->departamento?->nombre ?? '—'),
                'fecha' => $c->fecha_cambio_area ? Carbon::parse($c->fecha_cambio_area)->format('d/m/Y') : '—',
            ]),
            default => $this->getInventariosBajaParaFormato()->map(fn ($b) => [
                'tipo_nombre' => 'Baja',
                'tipo_clave' => 'baja',
                'id' => $b->id_inventario,
                'codigo' => $b->codigo ?: ($b->num_serie ?: ('#' . $b->id_inventario)),
                'depto' => $b->departamento?->nombre ?? ($b->area?->departamento?->nombre ?? '—'),
                'fecha' => $b->fecha_baja ? Carbon::parse($b->fecha_baja)->format('d/m/Y') : '—',
            ]),
        };
    }

    /**
     * @template TModel of \Illuminate\Database\Eloquent\Model
     * @param  Collection<int, TModel>  $candidatos
     * @param  class-string<TModel>  $modeloClase
     * @return TModel|null
     */
    protected function resolverRegistroFormatoAutorizado(
        Collection $candidatos,
        ?string $seleccionId,
        string $clavePk,
        string $modeloClase,
        string $campoUsuario,
        string $mensajeSinRegistros
    ) {
        $seleccionadoId = filled($seleccionId) ? (int) $seleccionId : null;

        if ($seleccionadoId !== null && ! $this->esUsuarioAdminReportes()) {
            $registroDestino = $modeloClase::find($seleccionadoId);
            if ($registroDestino && (int) $registroDestino->{$campoUsuario} !== (int) (auth()->id() ?? 0)) {
                abort(403, 'No tienes permiso para descargar formatos de otros usuarios.');
            }
        }

        $registro = $seleccionadoId !== null
            ? $candidatos->firstWhere($clavePk, $seleccionadoId)
            : $candidatos->first();

        if (! $registro) {
            $this->notificarSinRegistrosFormato($mensajeSinRegistros);

            return null;
        }

        if (! $this->esUsuarioAdminReportes() && (int) $registro->{$campoUsuario} !== (int) (auth()->id() ?? 0)) {
            abort(403, 'No tienes permiso para descargar formatos de otros usuarios.');
        }

        return $registro;
    }

    public function descargarFormatoInstitucional(string $formatoId, $registroId = null)
    {
        $idStr = filled($registroId) ? (string) $registroId : null;

        if ($formatoId === 'entrega') {
            $solicitud = $this->resolverRegistroFormatoAutorizado(
                $this->getSolicitudesParaFormato(),
                $idStr ?? $this->formatoSeleccionadoEntrega,
                'id_solicitud',
                Solicitud::class,
                'id_usuario',
                'No hay solicitudes de entrega que coincidan con los filtros.'
            );

            return $solicitud ? SigemPdfGenerator::descargarActaEntrega($solicitud) : null;
        }

        if ($formatoId === 'orden_trabajo') {
            $mant = $this->resolverRegistroFormatoAutorizado(
                $this->getMantenimientosParaOrdenFormato(),
                $idStr ?? $this->formatoSeleccionadoOrden,
                'id_mantenimiento',
                Mantenimiento::class,
                'id_usuario_solicita',
                'No hay mantenimientos que coincidan con los filtros.'
            );

            return $mant ? SigemPdfGenerator::descargarOrdenTrabajo($mant) : null;
        }

        if ($formatoId === 'lista_verificacion') {
            $mant = $this->resolverRegistroFormatoAutorizado(
                $this->getMantenimientosParaVerificacionFormato(),
                $idStr ?? $this->formatoSeleccionadoVerificacion,
                'id_mantenimiento',
                Mantenimiento::class,
                'id_usuario_solicita',
                'No hay mantenimientos disponibles para Lista de Verificación.'
            );

            return $mant ? SigemPdfGenerator::descargarListaVerificacion($mant) : null;
        }

        if ($formatoId === 'baja') {
            return $this->descargarFormatoBajaSeleccion($idStr);
        }

        if ($formatoId === 'cambio_area') {
            $inv = $this->resolverRegistroFormatoAutorizado(
                $this->getInventariosCambioAreaParaFormato(),
                $idStr ?? $this->formatoSeleccionadoCambioArea,
                'id_inventario',
                Inventario::class,
                'id_usuario',
                'No hay equipos con cambio de área que coincidan con los filtros.'
            );

            return $inv ? SigemPdfGenerator::descargarCambioArea($inv) : null;
        }

        return null;
    }

    protected function descargarFormatoBajaSeleccion(?string $idStr)
    {
        $candidatos = $this->getInventariosBajaParaFormato();
        if ($idStr !== null) {
            $inv = $candidatos->firstWhere('id_inventario', (int) $idStr) ?? Inventario::find((int) $idStr);
            if ($inv) {
                return SigemPdfGenerator::descargarFormatoBaja($inv);
            }
        }

        $ids = array_values(array_filter(array_map('intval', $this->formatosSeleccionadosBaja)));
        $bajas = ! empty($ids)
            ? $candidatos->whereIn('id_inventario', $ids)->values()
            : $candidatos;

        if ($bajas->isEmpty()) {
            $this->notificarSinRegistrosFormato('No hay equipos dados de baja o en solicitud que coincidan con los filtros.');

            return null;
        }

        if ($bajas->count() > SigemBajaFormatHelper::MAX_LOTE_BAJAS) {
            $bajas = $bajas->take(SigemBajaFormatHelper::MAX_LOTE_BAJAS)->values();
            Notification::make()
                ->title('Lote limitado a ' . SigemBajaFormatHelper::MAX_LOTE_BAJAS . ' equipos')
                ->body('Se generará el PDF con los primeros ' . SigemBajaFormatHelper::MAX_LOTE_BAJAS . ' equipos para optimizar el rendimiento.')
                ->warning()
                ->send();
        }

        if ($bajas->count() === 1) {
            return SigemPdfGenerator::descargarFormatoBaja($bajas->first());
        }

        return SigemPdfGenerator::descargarFormatosBajaLote($bajas);
    }

    public function generarFormatosFiltrados()
    {
        return $this->descargarFormatoInstitucional($this->formatoFiltroTipo);
    }

    protected function notificarSinRegistrosFormato(string $mensaje): void
    {
        Notification::make()
            ->title($mensaje)
            ->warning()
            ->send();
    }
}
