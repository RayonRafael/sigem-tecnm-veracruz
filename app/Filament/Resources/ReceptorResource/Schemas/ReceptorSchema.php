<?php

namespace App\Filament\Resources\ReceptorResource\Schemas;

use App\Filament\Resources\ReceptorResource;
use App\Filament\Schemas\CatalogoBaseFormSchema;
use App\Filament\Schemas\CatalogoBaseTableSchema;
use App\Models\Area;
use App\Models\Departamento;
use App\Models\Receptor;
use App\Support\SigemUniqueValidator;
use App\Support\SubformSlideOverHelper;
use Filament\Forms;
use Filament\Forms\Components\Actions\Action;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Tables;
use Illuminate\Support\HtmlString;

class ReceptorSchema
{
    public static function getFormSchema(): array
    {
        return [
            CatalogoBaseFormSchema::section('👤 Datos del receptor', 'heroicon-m-user-circle', [
                Forms\Components\TextInput::make('nombre')
                    ->label('Nombre')
                    ->placeholder('Ej. Roberto')
                    ->required()
                    ->prefixIcon('heroicon-m-user')
                    ->maxLength(100)
                    ->live(onBlur: true)
                    ->rule(SigemUniqueValidator::reglaReceptorUnico()),
                Forms\Components\TextInput::make('apellido_paterno')
                    ->label('Apellido paterno')
                    ->placeholder('Ej. Hernández')
                    ->required()
                    ->prefixIcon('heroicon-m-user')
                    ->maxLength(100)
                    ->live(onBlur: true),
                Forms\Components\TextInput::make('apellido_materno')
                    ->label('Apellido materno')
                    ->placeholder('Ej. García')
                    ->required()
                    ->prefixIcon('heroicon-m-user')
                    ->maxLength(100)
                    ->live(onBlur: true),
                Forms\Components\Placeholder::make('alerta_similitud_receptor')
                    ->label('')
                    ->hiddenLabel()
                    ->visible(function (Forms\Get $get, ?Receptor $record = null): bool {
                        $analisis = SigemUniqueValidator::analizarReceptor(
                            is_scalar($get('nombre')) ? (string) $get('nombre') : null,
                            is_scalar($get('apellido_paterno')) ? (string) $get('apellido_paterno') : null,
                            is_scalar($get('apellido_materno')) ? (string) $get('apellido_materno') : null,
                            $record?->getKey(),
                        );

                        return $analisis['accion'] === SigemUniqueValidator::ACCION_ADVERTIR;
                    })
                    ->content(function (Forms\Get $get, ?Receptor $record = null) {
                        $analisis = SigemUniqueValidator::analizarReceptor(
                            is_scalar($get('nombre')) ? (string) $get('nombre') : null,
                            is_scalar($get('apellido_paterno')) ? (string) $get('apellido_paterno') : null,
                            is_scalar($get('apellido_materno')) ? (string) $get('apellido_materno') : null,
                            $record?->getKey(),
                        );

                        return SigemUniqueValidator::renderAlertaHtml($analisis);
                    })
                    ->columnSpanFull(),
                Forms\Components\Select::make('tipo')
                    ->label('Tipo')
                    ->placeholder('Selecciona el tipo...')
                    ->options(ReceptorResource::getTiposOptions())
                    ->nullable()
                    ->prefixIcon('heroicon-m-identification'),
                Forms\Components\TextInput::make('num_control')
                    ->label('No. Control / Empleado')
                    ->placeholder('Ej. 20240001')
                    ->maxLength(30)
                    ->nullable()
                    ->prefixIcon('heroicon-m-hashtag'),
                Forms\Components\TextInput::make('cargo')
                    ->label('Cargo / Puesto')
                    ->placeholder('Ej. Ing. de Sistemas')
                    ->maxLength(100)
                    ->prefixIcon('heroicon-m-briefcase'),
                Forms\Components\TextInput::make('email')
                    ->label('Correo electrónico')
                    ->placeholder('Ej. receptor@veracruz.tecnm.mx')
                    ->email()
                    ->prefixIcon('heroicon-m-envelope')
                    ->maxLength(100)
                    ->default(null),
                Forms\Components\TextInput::make('telefono')
                    ->label('Teléfono')
                    ->placeholder('Ej. 229 123 4567')
                    ->tel()
                    ->prefixIcon('heroicon-m-device-phone-mobile')
                    ->maxLength(20)
                    ->default(null),
                Forms\Components\Select::make('departamento_id_virtual')
                    ->label('Departamento')
                    ->placeholder('Selecciona el departamento...')
                    ->options(fn (): array => Departamento::orderBy('nombre')->pluck('nombre', 'id_departamento')->toArray())
                    ->searchable()
                    ->preload()
                    ->live()
                    ->dehydrated(false)
                    ->prefixIcon('heroicon-m-building-office-2')
                    ->afterStateHydrated(function (Forms\Components\Select $component, ?Receptor $record): void {
                        if ($record && $record->id_area) {
                            $deptId = $record->area?->id_departamento
                                ?? Area::where('id_area', $record->id_area)->value('id_departamento');
                            if ($deptId) {
                                $component->state($deptId);
                            }
                        }
                    })
                    ->afterStateUpdated(fn (Forms\Set $set) => $set('id_area', null)),
                Forms\Components\Select::make('id_area')
                    ->label('Área')
                    ->placeholder(fn (Forms\Get $get): string => $get('departamento_id_virtual')
                        ? 'Selecciona el área...'
                        : 'Selecciona...')
                    ->options(function (Forms\Get $get): array {
                        $deptoId = $get('departamento_id_virtual');
                        if ($deptoId) {
                            return Area::where('id_departamento', $deptoId)
                                ->orderBy('nombre')
                                ->pluck('nombre', 'id_area')
                                ->toArray();
                        }

                        return Area::orderBy('nombre')->pluck('nombre', 'id_area')->toArray();
                    })
                    ->required()
                    ->searchable()
                    ->preload()
                    ->prefixIcon('heroicon-m-building-office')
                    ->createOptionForm([
                        SubformSlideOverHelper::headerNav(),
                        Forms\Components\TextInput::make('nombre')
                            ->label('Nombre del área')
                            ->placeholder('Ej. Laboratorio de Cómputo')
                            ->required()
                            ->maxLength(100)
                            ->live(onBlur: true)
                            ->rule(CatalogoBaseFormSchema::reglaNombreUnico(Area::class, 'un área', 'nombre', false)),
                        CatalogoBaseFormSchema::alertaSimilitudCatalogo('nombre', Area::class, 'un área', 'nombre', false),
                        Forms\Components\Select::make('id_departamento')
                            ->placeholder('Selecciona...')
                            ->label('Departamento')
                            ->options(Departamento::pluck('nombre', 'id_departamento'))
                            ->searchable()
                            ->preload()
                            ->required(),
                        SubformSlideOverHelper::catalogTip('El área estará disponible inmediatamente en el catálogo.'),
                    ])
                    ->createOptionUsing(fn (array $data): int => Area::create($data)->id_area)
                    ->createOptionAction(fn (Action $action) => SubformSlideOverHelper::configureAction(
                        $action,
                        '✏️ Agregar área como nueva opción',
                        '+ Agregar',
                        'Área creada correctamente',
                    )),
            ], 2),
        ];
    }

    public static function getTableColumns(): array
    {
        return [
            CatalogoBaseTableSchema::columnaTexto('nombre', 'Nombre', individualSearch: true)
                ->description(fn (Receptor $record): ?string => $record->cargo),
            Tables\Columns\TextColumn::make('tipo')
                ->label('Tipo')
                ->badge()
                ->color(fn (?string $state): string => match ($state) {
                    'Docente' => 'info',
                    'Administrativo' => 'primary',
                    'Auxiliar' => 'warning',
                    'Alumno' => 'success',
                    'Externo' => 'gray',
                    default => 'gray',
                })
                ->placeholder('—')
                ->searchable()
                ->sortable(),
            Tables\Columns\TextColumn::make('num_control')
                ->label('No. Control')
                ->fontFamily('mono')
                ->placeholder('—')
                ->searchable()
                ->sortable(),
            CatalogoBaseTableSchema::columnaTexto('cargo', 'Cargo / Puesto', sortable: true),
            CatalogoBaseTableSchema::columnaTexto('area.departamento.nombre', 'Departamento', sortable: true)->placeholder('—'),
            CatalogoBaseTableSchema::columnaTexto('apellido_paterno', 'Apellido paterno', hiddenByDefault: true),
            CatalogoBaseTableSchema::columnaTexto('apellido_materno', 'Apellido materno', hiddenByDefault: true),
            CatalogoBaseTableSchema::columnaTexto('email', 'Email', hiddenByDefault: true),
            CatalogoBaseTableSchema::columnaTexto('telefono', 'Teléfono', hiddenByDefault: true),
            CatalogoBaseTableSchema::columnaTexto('area.nombre', 'Área', sortable: true),
            CatalogoBaseTableSchema::columnaFechaCreacion(),
        ];
    }

    public static function getTableActions(bool $soloLectura): array
    {
        return CatalogoBaseTableSchema::accionesConModalDetalle(
            viewHeading: fn (?Receptor $record) => trim(($record?->nombre ?? '') . ' ' . ($record?->apellido_paterno ?? '') . ' ' . ($record?->apellido_materno ?? '')) ?: 'Detalle de receptor',
            editHeading: fn (?Receptor $record) => 'Editar: ' . trim(($record?->nombre ?? '') . ' ' . ($record?->apellido_paterno ?? '')),
            description: fn (?Receptor $record) => new HtmlString(e($record?->cargo ?? '—') . ' · ID: #' . e($record?->getKey())),
            editSuccessMessage: 'Receptor actualizado correctamente',
            soloLectura: $soloLectura,
        );
    }

    public static function getInfolistSections(): array
    {
        return [
            Section::make('👤 Datos del receptor')
                ->schema([
                    TextEntry::make('nombre')->label('Nombre')->icon('heroicon-m-user')->placeholder('Por capturar'),
                    TextEntry::make('apellido_paterno')->label('Apellido paterno')->icon('heroicon-m-user')->placeholder('Por capturar'),
                    TextEntry::make('apellido_materno')->label('Apellido materno')->icon('heroicon-m-user')->placeholder('Por capturar'),
                    TextEntry::make('tipo')->label('Tipo')->badge()->color('info')->icon('heroicon-m-identification')->placeholder('—'),
                    TextEntry::make('num_control')->label('No. Control')->fontFamily('mono')->icon('heroicon-m-hashtag')->placeholder('—'),
                    TextEntry::make('cargo')->label('Cargo')->icon('heroicon-m-briefcase')->placeholder('Por capturar'),
                    TextEntry::make('telefono')->label('Teléfono')->icon('heroicon-m-phone')->placeholder('Por capturar'),
                    TextEntry::make('email')->label('Correo')->icon('heroicon-m-envelope')->placeholder('Por capturar'),
                ])
                ->columns(2),
            Section::make('Ubicación')
                ->schema([
                    TextEntry::make('area.departamento.nombre')->label('Departamento')->icon('heroicon-m-building-office')->placeholder('—'),
                    TextEntry::make('area.nombre')->label('Área')->icon('heroicon-m-map-pin')->placeholder('—'),
                ])
                ->columns(2),
        ];
    }
}
