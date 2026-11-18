<?php

namespace App\Filament\Concerns;

use App\Models\Area;
use App\Models\Departamento;
use App\Models\Inventario;
use App\Support\SigemBajaFormatHelper;
use Filament\Forms;

trait ConModalesBajaCambio
{
    /**
     * Esquema del formulario slide-over para Cambio de Área.
     */
    public static function getModalCambioAreaFormSchema(bool $esAdmin = true): array
    {
        return [
            Forms\Components\Grid::make(2)
                ->schema([
                    Forms\Components\TextInput::make('departamento_actual')
                        ->label('Departamento actual')
                        ->default(fn (?Inventario $record) => $record?->departamento?->nombre ?? $record?->area?->departamento?->nombre ?? 'Sin departamento')
                        ->disabled()
                        ->dehydrated(false),
                    Forms\Components\TextInput::make('area_actual')
                        ->label('Área actual')
                        ->default(fn (?Inventario $record) => $record?->area?->nombre ?? 'Sin área')
                        ->disabled()
                        ->dehydrated(false),
                ]),
            Forms\Components\Grid::make(2)
                ->schema([
                    Forms\Components\Select::make('departamento_id')
                        ->label('Nuevo Departamento')
                        ->options(fn () => Departamento::orderBy('nombre')->pluck('nombre', 'id_departamento'))
                        ->required()
                        ->live()
                        ->afterStateUpdated(function (Forms\Set $set, $state) {
                            $set('area_id', null);
                            $encargado = $state ? Departamento::find($state)?->encargado : null;
                            $set('jefe_departamento', $encargado ?? '');
                        }),
                    Forms\Components\Select::make('area_id')
                        ->label('Nueva Área')
                        ->options(fn (Forms\Get $get) => $get('departamento_id')
                            ? Area::where('id_departamento', $get('departamento_id'))->orderBy('nombre')->pluck('nombre', 'id_area')
                            : [])
                        ->required(),
                ]),
            Forms\Components\TextInput::make('aula')
                ->label('Nueva Aula')
                ->placeholder('ej. E201')
                ->maxLength(50),
            Forms\Components\TextInput::make('jefe_departamento')
                ->label('Jefe de Depto. destino')
                ->placeholder('Nombre del jefe del departamento destino...')
                ->default(function (Forms\Get $get, ?Inventario $record) {
                    $deptoId = $get('departamento_id');
                    if ($deptoId) {
                        return Departamento::find($deptoId)?->encargado ?? '';
                    }

                    return $record?->departamento?->encargado ?? $record?->area?->departamento?->encargado ?? '';
                })
                ->required()
                ->maxLength(150),
            Forms\Components\Textarea::make('motivo')
                ->label('Motivo del cambio')
                ->placeholder('Explicación detallada del motivo del cambio...')
                ->required(),
        ];
    }

    /**
     * Opciones del checklist de campos para el Formato PDF de Cambio de Área.
     */
    public static function getCamposPdfCambioAreaOptions(): array
    {
        return [
            'num_serie' => 'Número de serie',
            'origen' => 'Origen (Depto + Área)',
            'destino' => 'Destino (Depto + Área)',
            'nueva_aula' => 'Nueva aula',
            'motivo' => 'Motivo del cambio',
            'jefe_origen' => 'Jefe de Depto. origen',
            'jefe_destino' => 'Jefe de Depto. destino',
            'descripcion_extendida' => 'Descripción extendida del equipo',
            'fecha_cambio' => 'Fecha del cambio',
        ];
    }

    /**
     * Campos marcados por defecto en el checklist del Formato PDF de Cambio de Área.
     */
    public static function getCamposPdfCambioAreaDefaults(): array
    {
        return ['num_serie', 'origen', 'destino', 'jefe_destino', 'fecha_cambio'];
    }

    /**
     * Esquema del slide-over de checklist posterior al Cambio de Área.
     */
    public static function getChecklistCambioAreaSchema(): array
    {
        return [
            Forms\Components\CheckboxList::make('campos_pdf')
                ->label('Selecciona qué datos mostrar en el formato')
                ->options(static::getCamposPdfCambioAreaOptions())
                ->default(static::getCamposPdfCambioAreaDefaults())
                ->columns(2),
        ];
    }

    /**
     * Persiste los metadatos de origen/destino/jefe/motivo del cambio de área para el formato PDF.
     */
    public static function guardarMetadatosCambioArea(
        Inventario $record,
        array $data,
        ?Departamento $origenDeptoObj = null,
        ?Area $origenAreaObj = null
    ): void {
        $record->refresh();
        $specs = is_array($record->especificaciones) ? $record->especificaciones : [];

        $deptoDestino = ! empty($data['departamento_id']) ? Departamento::find($data['departamento_id']) : null;
        $areaDestino = ! empty($data['area_id']) ? Area::find($data['area_id']) : null;

        if ($deptoDestino && filled($data['jefe_departamento'] ?? null)) {
            $deptoDestino->update(['encargado' => trim((string) $data['jefe_departamento'])]);
        }

        $specs['cambio_origen_depto'] = $origenDeptoObj?->nombre ?? 'Centro de Cómputo';
        $specs['cambio_origen_area'] = $origenAreaObj?->nombre ?? 'Centro de Cómputo';
        $specs['cambio_jefe_origen'] = $origenDeptoObj?->encargado ?? 'Sin jefe asignado';
        $specs['cambio_destino_depto'] = $deptoDestino?->nombre ?? ($record->departamento?->nombre ?? '');
        $specs['cambio_destino_area'] = $areaDestino?->nombre ?? ($record->area?->nombre ?? '');
        $specs['jefe_destino'] = filled($data['jefe_departamento'] ?? null)
            ? trim((string) $data['jefe_departamento'])
            : ($deptoDestino?->encargado ?? '');
        $nuevaAula = filled($data['aula'] ?? null) ? trim((string) $data['aula']) : null;
        $specs['cambio_nueva_aula'] = $nuevaAula;
        if ($nuevaAula !== null) {
            $specs['aula'] = $nuevaAula;
            $record->aula = $nuevaAula;
        }
        $specs['cambio_motivo'] = $data['motivo'] ?? null;

        $record->skipHistorialObserver = true;
        $record->especificaciones = $specs;
        $record->saveQuietly();
    }

    /**
     * Esquema del formulario modal para Baja de Equipo.
     */
    public static function getModalBajaFormSchema(bool $observacionesRequeridas = true): array
    {
        return [
            Forms\Components\Select::make('motivo_baja')
                ->label('Motivo de baja')
                ->options(fn () => SigemBajaFormatHelper::motivosBajaOptions())
                ->required(),
            Forms\Components\Textarea::make('observaciones_baja')
                ->label($observacionesRequeridas ? 'Observaciones detalladas' : 'Observaciones')
                ->placeholder($observacionesRequeridas
                    ? 'Explicación detallada del motivo de la baja...'
                    : 'Observaciones detalladas (opcional)...')
                ->required($observacionesRequeridas),
            Forms\Components\Grid::make(2)
                ->schema([
                    Forms\Components\TextInput::make('folio_sibsep')
                        ->label('Folio SIBESEP (opcional)')
                        ->placeholder('Vacío por defecto; lo asigna Recursos Materiales')
                        ->maxLength(100),
                    Forms\Components\Select::make('posible_area_utilizacion')
                        ->label('Posible área de utilización (opcional)')
                        ->placeholder('Seleccionar si aplica para reaprovechamiento...')
                        ->options(fn () => Area::orderBy('nombre')->pluck('nombre', 'nombre')->toArray())
                        ->searchable()
                        ->preload(),
                ]),
        ];
    }

    /**
     * Extrae los campos adicionales opcionales del formato de baja para guardarlos en especificaciones.
     */
    public static function extraerDatosAdicionalesBaja(array $data): array
    {
        return [
            'folio_sibsep' => $data['folio_sibsep'] ?? null,
            'posible_area_utilizacion' => $data['posible_area_utilizacion'] ?? null,
        ];
    }
}
