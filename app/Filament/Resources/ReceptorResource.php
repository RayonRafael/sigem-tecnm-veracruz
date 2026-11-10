<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReceptorResource\Pages;
use App\Models\Receptor;
use App\Support\SubformSlideOverHelper;
use Filament\Forms;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Form;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\Tabs;
use Filament\Infolists\Components\Tabs\Tab;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ReceptorResource extends Resource
{
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $model = Receptor::class;

    protected static ?string $recordTitleAttribute = 'nombre';

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'Receptores';

    protected static ?string $modelLabel = 'receptor';

    protected static ?string $pluralModelLabel = 'receptores';

    protected static ?string $navigationGroup = 'Catálogos';

    protected static ?int $navigationSort = 7;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['area.departamento']);
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['nombre', 'apellido_paterno', 'apellido_materno', 'tipo', 'num_control', 'cargo', 'email'];
    }

    public static function getTiposOptions(): array
    {
        return [
            'Docente' => 'Docente',
            'Administrativo' => 'Administrativo',
            'Auxiliar' => 'Auxiliar',
            'Alumno' => 'Alumno',
            'Externo' => 'Externo',
        ];
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('👤 Datos del receptor')
                    ->icon('heroicon-m-user-circle')
                    ->schema([
                        Forms\Components\TextInput::make('nombre')
                            ->label('Nombre')
                            ->placeholder('Ej. Roberto')
                            ->required()
                            ->prefixIcon('heroicon-m-user')
                            ->maxLength(100),
                        Forms\Components\TextInput::make('apellido_paterno')
                            ->label('Apellido paterno')
                            ->placeholder('Ej. Hernández')
                            ->required()
                            ->prefixIcon('heroicon-m-user')
                            ->maxLength(100),
                        Forms\Components\TextInput::make('apellido_materno')
                            ->label('Apellido materno')
                            ->placeholder('Ej. García')
                            ->required()
                            ->prefixIcon('heroicon-m-user')
                            ->maxLength(100),
                        Forms\Components\Select::make('tipo')
                            ->label('Tipo')
                            ->placeholder('Selecciona el tipo...')
                            ->options(self::getTiposOptions())
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
                            ->options(fn (): array => \App\Models\Departamento::orderBy('nombre')->pluck('nombre', 'id_departamento')->toArray())
                            ->searchable()
                            ->preload()
                            ->live()
                            ->dehydrated(false)
                            ->prefixIcon('heroicon-m-building-office-2')
                            ->afterStateHydrated(function (Forms\Components\Select $component, ?Receptor $record): void {
                                if ($record && $record->id_area) {
                                    $deptId = $record->area?->id_departamento
                                        ?? \App\Models\Area::where('id_area', $record->id_area)->value('id_departamento');
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
                                    return \App\Models\Area::where('id_departamento', $deptoId)
                                        ->orderBy('nombre')
                                        ->pluck('nombre', 'id_area')
                                        ->toArray();
                                }
                                return \App\Models\Area::orderBy('nombre')->pluck('nombre', 'id_area')->toArray();
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
                                    ->maxLength(100),
                                Forms\Components\Select::make('id_departamento')->placeholder('Selecciona...')
                                    ->label('Departamento')
                                    ->options(\App\Models\Departamento::pluck('nombre', 'id_departamento'))
                                    ->searchable()
                                    ->preload()
                                    ->required(),
                                SubformSlideOverHelper::catalogTip('El área estará disponible inmediatamente en el catálogo.'),
                            ])
                            ->createOptionUsing(function (array $data): int {
                                $area = \App\Models\Area::create($data);
                                return $area->id_area;
                            })
                            ->createOptionAction(function (Action $action) {
                                return SubformSlideOverHelper::configureAction(
                                    $action,
                                    '✏️ Agregar área como nueva opción',
                                    '+ Agregar',
                                    'Área creada correctamente'
                                );
                            }),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nombre')->wrap(false)->limit(30)->grow(false)
                    ->label('Nombre')
                    ->description(fn (Receptor $record): ?string => $record->cargo)
                    ->searchable(isIndividual: true),
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
                Tables\Columns\TextColumn::make('cargo')->wrap(false)->limit(30)->grow(false)
                    ->label('Cargo / Puesto')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('area.departamento.nombre')->wrap(false)->limit(30)->grow(false)
                    ->label('Departamento')
                    ->placeholder('—')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('apellido_paterno')->wrap(false)->limit(30)->grow(false)->toggleable(isToggledHiddenByDefault: true)
                    ->label('Apellido paterno')
                    ->searchable(),
                Tables\Columns\TextColumn::make('apellido_materno')->wrap(false)->limit(30)->grow(false)->toggleable(isToggledHiddenByDefault: true)
                    ->label('Apellido materno')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')->wrap(false)->limit(30)->grow(false)->toggleable(isToggledHiddenByDefault: true)->label('Correo electrónico')
                    ->label('Email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('telefono')->wrap(false)->limit(30)->grow(false)->toggleable(isToggledHiddenByDefault: true)
                    ->label('Teléfono')
                    ->searchable(),
                Tables\Columns\TextColumn::make('area.nombre')->wrap(false)->limit(30)->grow(false)
                    ->label('Área')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')->wrap(false)->limit(30)->grow(false)->toggleable(isToggledHiddenByDefault: true)->label('Fecha de creación')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('nombre', 'asc')
            ->striped()
            ->searchPlaceholder('Buscar por nombre, cargo, área o correo...')
            ->defaultPaginationPageOption(10)
            ->filters([
                TrashedFilter::make(),
                //
            ])
            ->recordUrl(null)
            ->recordAction(null)
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->iconButton()
                    ->tooltip('Ver detalle')
                    ->extraAttributes(['title' => 'Ver detalle'])
                    ->slideOver()
                    ->modalHeading(fn (?Receptor $record) => trim(($record?->nombre ?? '') . ' ' . ($record?->apellido_paterno ?? '') . ' ' . ($record?->apellido_materno ?? '')) ?: 'Detalle de receptor')
                    ->modalDescription(fn (?Receptor $record) => new \Illuminate\Support\HtmlString(
                        e($record?->cargo ?? '—') . ' · ID: #' . e($record?->getKey())
                    ))
                    ->modalCancelActionLabel('Cerrar')
                    ->extraModalFooterActions([
                        Tables\Actions\Action::make('desde_detalle_editar')
                            ->label('Editar')
                            ->color('warning')
                            ->icon('heroicon-m-pencil-square')
                            ->alpineClickHandler(fn (?Receptor $record) => "\$wire.replaceMountedTableAction('edit', '{$record?->getKey()}')"),
                        Tables\Actions\Action::make('desde_detalle_eliminar')
                            ->label('Eliminar')
                            ->color('danger')
                            ->icon('heroicon-m-trash')
                            ->alpineClickHandler(fn (?Receptor $record) => "\$wire.replaceMountedTableAction('delete', '{$record?->getKey()}')"),
                    ]),
                Tables\Actions\EditAction::make()
                    ->iconButton()
                    ->tooltip('Editar')
                    ->extraAttributes(['title' => 'Editar'])
                    ->modalHeading(fn (?Receptor $record) => 'Editar: ' . trim(($record?->nombre ?? '') . ' ' . ($record?->apellido_paterno ?? '')))
                    ->modalDescription(fn (?Receptor $record) => new \Illuminate\Support\HtmlString(
                        e($record?->cargo ?? '—') . ' · ID: #' . e($record?->getKey())
                    ))
                    ->successNotificationTitle('Receptor actualizado correctamente'),
                Tables\Actions\DeleteAction::make()->iconButton()->tooltip('Eliminar')->extraAttributes(['title' => 'Eliminar'])->successNotificationTitle('Registro eliminado correctamente'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()->successNotificationTitle('Receptor eliminado'),
                ]),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Tabs::make('Detalle')
                    ->tabs([
                        Tab::make('Información')
                            ->icon('heroicon-m-tag')
                            ->schema([
                                Section::make('👤 Datos del receptor')
                                    ->schema([
                                        TextEntry::make('nombre')
                                            ->label('Nombre')
                                            ->icon('heroicon-m-user')
                                            ->placeholder('Por capturar'),
                                        TextEntry::make('apellido_paterno')
                                            ->label('Apellido paterno')
                                            ->icon('heroicon-m-user')
                                            ->placeholder('Por capturar'),
                                        TextEntry::make('apellido_materno')
                                            ->label('Apellido materno')
                                            ->icon('heroicon-m-user')
                                            ->placeholder('Por capturar'),
                                        TextEntry::make('tipo')
                                            ->label('Tipo')
                                            ->badge()
                                            ->color('info')
                                            ->icon('heroicon-m-identification')
                                            ->placeholder('—'),
                                        TextEntry::make('num_control')
                                            ->label('No. Control')
                                            ->fontFamily('mono')
                                            ->icon('heroicon-m-hashtag')
                                            ->placeholder('—'),
                                        TextEntry::make('cargo')
                                            ->label('Cargo')
                                            ->icon('heroicon-m-briefcase')
                                            ->placeholder('Por capturar'),
                                        TextEntry::make('telefono')
                                            ->label('Teléfono')
                                            ->icon('heroicon-m-phone')
                                            ->placeholder('Por capturar'),
                                        TextEntry::make('email')
                                            ->label('Correo')
                                            ->icon('heroicon-m-envelope')
                                            ->placeholder('Por capturar'),
                                    ])
                                    ->columns(2),

                                Section::make('Ubicación')
                                    ->schema([
                                        TextEntry::make('area.departamento.nombre')
                                            ->label('Departamento')
                                            ->icon('heroicon-m-building-office')
                                            ->placeholder('—'),
                                        TextEntry::make('area.nombre')
                                            ->label('Área')
                                            ->icon('heroicon-m-map-pin')
                                            ->placeholder('—'),
                                    ])
                                    ->columns(2),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListReceptors::route('/'),
            'create' => Pages\CreateReceptor::route('/create'),
            'edit' => Pages\EditReceptor::route('/{record}/edit'),
        ];
    }
}
