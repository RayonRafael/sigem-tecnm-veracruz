<?php

namespace App\Filament\Concerns;

use App\Models\Area;
use App\Models\Departamento;
use App\Models\Inventario;
use App\Support\SigemBajaFormatHelper;
use Filament\Forms;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;

trait ConModalesBajaCambio
{
    /**
     * Esquema del formulario slide-over para Cambio de Área.
     */
    public static function getModalCambioAreaFormSchema(bool $esAdmin = true): array
    {
        return [
            Forms\Components\Placeholder::make('equipo_resumen')
                ->hiddenLabel()
                ->content(function (?Inventario $record): Htmlable {
                    $codigo = e($record?->codigo ?? 'S/C');
                    $nombre = e($record?->material?->nombre ?? $record?->subtipo ?? 'Equipo');
                    $serie = e($record?->num_serie ?? 'S/N');
                    $depto = e($record?->departamento?->nombre ?? $record?->area?->departamento?->nombre ?? 'Sin depto');
                    $area = e($record?->area?->nombre ?? 'Sin área');
                    $aula = $record?->aula_efectiva ? ' > ' . e($record->aula_efectiva) : '';

                    return new HtmlString("
                        <div style='background: #f0f4ff; border: 1px solid #c7d2fe; border-radius: 8px; padding: 12px 14px;'>
                            <div style='font-weight: 700; color: #1b396a; font-size: 13.5px;'>
                                📋 {$codigo} — {$nombre}
                            </div>
                            <div style='font-size: 12px; color: #4b5563; margin-top: 4px;'>
                                🏢 {$depto} > {$area}{$aula} &nbsp;·&nbsp; 🔢 S/N: {$serie}
                            </div>
                        </div>
                    ");
                })
                ->columnSpanFull(),

            Forms\Components\Placeholder::make('tip')
                ->hiddenLabel()
                ->content(new HtmlString("
                    <div style='display: flex; align-items: center; gap: 8px; background: #fffbeb; border: 1px solid #fcd34d; border-radius: 6px; padding: 8px 12px; font-size: 12px; color: #92400e;'>
                        <span>💡</span>
                        <span>Selecciona el nuevo departamento y área de destino. Al confirmar podrás elegir qué datos incluir en el Formato PDF.</span>
                    </div>
                "))
                ->columnSpanFull(),

            Forms\Components\Section::make('📍 Ubicación actual y destino')
                ->columns(2)
                ->compact()
                ->schema([
                    Forms\Components\TextInput::make('departamento_actual')
                        ->label('Departamento actual')
                        ->prefixIcon('heroicon-m-building-office-2')
                        ->default(fn (?Inventario $record) => $record?->departamento?->nombre ?? $record?->area?->departamento?->nombre ?? 'Sin departamento')
                        ->disabled()
                        ->dehydrated(false),
                    Forms\Components\TextInput::make('area_actual')
                        ->label('Área actual')
                        ->prefixIcon('heroicon-m-building-office')
                        ->default(fn (?Inventario $record) => $record?->area?->nombre ?? 'Sin área')
                        ->disabled()
                        ->dehydrated(false),
                    Forms\Components\Select::make('departamento_id')
                        ->label('Nuevo Departamento')
                        ->prefixIcon('heroicon-m-building-office')
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
                        ->prefixIcon('heroicon-m-rectangle-group')
                        ->options(fn (Forms\Get $get) => $get('departamento_id')
                            ? Area::where('id_departamento', $get('departamento_id'))->orderBy('nombre')->pluck('nombre', 'id_area')
                            : [])
                        ->required(),
                    Forms\Components\TextInput::make('aula')
                        ->label('Nueva Aula')
                        ->prefixIcon('heroicon-m-academic-cap')
                        ->placeholder('ej. E201')
                        ->maxLength(50)
                        ->columnSpanFull(),
                    Forms\Components\TextInput::make('jefe_departamento')
                        ->label('Jefe de Depto. destino')
                        ->prefixIcon('heroicon-m-user')
                        ->placeholder('Nombre del jefe del departamento destino...')
                        ->default(function (Forms\Get $get, ?Inventario $record) {
                            $deptoId = $get('departamento_id');
                            if ($deptoId) {
                                return Departamento::find($deptoId)?->encargado ?? '';
                            }

                            return $record?->departamento?->encargado ?? $record?->area?->departamento?->encargado ?? '';
                        })
                        ->required()
                        ->maxLength(150)
                        ->columnSpanFull(),
                ]),

            Forms\Components\Section::make('📝 Justificación del movimiento')
                ->compact()
                ->schema([
                    Forms\Components\Textarea::make('motivo')
                        ->label('Motivo del cambio')
                        ->placeholder('Explicación detallada del motivo del cambio...')
                        ->rows(3)
                        ->helperText('Este motivo se registrará en la bitácora y en el Formato PDF de Cambio de Área.')
                        ->required()
                        ->columnSpanFull(),
                ]),
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
            Forms\Components\Placeholder::make('exito')
                ->hiddenLabel()
                ->content(new HtmlString("
                    <div style='background: #ecfdf5; border: 1px solid #6ee7b7; border-radius: 8px; padding: 12px 14px;'>
                        <div style='font-weight: 700; color: #065f46; font-size: 13.5px;'>
                            ✅ Cambio registrado correctamente
                        </div>
                        <div style='font-size: 12px; color: #047857; margin-top: 2px;'>
                            Selecciona los datos que deseas incluir en el Formato PDF de Cambio de Área.
                        </div>
                    </div>
                "))
                ->columnSpanFull(),

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
     * Esquema del formulario modal/slide-over para Baja de Equipo.
     */
    public static function getModalBajaFormSchema(bool $observacionesRequeridas = true): array
    {
        return [
            Forms\Components\Placeholder::make('equipo_resumen_baja')
                ->hiddenLabel()
                ->visible(fn (?Inventario $record): bool => $record !== null)
                ->content(function (?Inventario $record): Htmlable {
                    $codigo = e($record?->codigo ?? 'S/C');
                    $nombre = e($record?->material?->nombre ?? $record?->subtipo ?? 'Equipo');
                    $serie = e($record?->num_serie ?? 'S/N');
                    $depto = e($record?->departamento?->nombre ?? $record?->area?->departamento?->nombre ?? 'Sin depto');
                    $area = e($record?->area?->nombre ?? 'Sin área');

                    return new HtmlString("
                        <div style='background: #fef2f2; border: 1px solid #fecaca; border-radius: 8px; padding: 12px 14px;'>
                            <div style='font-weight: 700; color: #991b1b; font-size: 13.5px;'>
                                📋 {$codigo} — {$nombre}
                            </div>
                            <div style='font-size: 12px; color: #7f1d1d; margin-top: 4px;'>
                                🏢 {$depto} > {$area} &nbsp;·&nbsp; 🔢 S/N: {$serie}
                            </div>
                        </div>
                    ");
                })
                ->columnSpanFull(),

            Forms\Components\Section::make('⚠️ Datos del trámite de baja')
                ->columns(2)
                ->compact()
                ->schema([
                    Forms\Components\Select::make('motivo_baja')
                        ->label('Motivo de baja')
                        ->prefixIcon('heroicon-m-exclamation-triangle')
                        ->options(fn () => SigemBajaFormatHelper::motivosBajaOptions())
                        ->required()
                        ->columnSpanFull(),
                    Forms\Components\Textarea::make('observaciones_baja')
                        ->label($observacionesRequeridas ? 'Observaciones detalladas' : 'Observaciones')
                        ->placeholder($observacionesRequeridas
                            ? 'Explicación detallada del motivo de la baja...'
                            : 'Observaciones detalladas (opcional)...')
                        ->rows(3)
                        ->required($observacionesRequeridas)
                        ->columnSpanFull(),
                    Forms\Components\TextInput::make('folio_sibsep')
                        ->label('Folio SIBESEP (opcional)')
                        ->prefixIcon('heroicon-m-hashtag')
                        ->placeholder('Vacío por defecto; lo asigna Recursos Materiales')
                        ->maxLength(100),
                    Forms\Components\Select::make('posible_area_utilizacion')
                        ->label('Posible área de utilización (opcional)')
                        ->prefixIcon('heroicon-m-rectangle-group')
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
