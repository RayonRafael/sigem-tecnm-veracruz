<?php

namespace App\Filament\ServicioSocial\Resources;

use App\Filament\ServicioSocial\Resources\MaterialResource\Pages;
use App\Models\Material;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class MaterialResource extends Resource
{
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $model = Material::class;

    protected static ?string $recordTitleAttribute = 'nombre';

    protected static ?string $navigationIcon = 'heroicon-o-globe-alt';

    protected static ?string $navigationLabel = 'Materiales';

    protected static ?string $modelLabel = 'material';

    protected static ?string $pluralModelLabel = 'materiales';

    protected static ?string $navigationGroup = 'Catálogos';

    protected static ?int $navigationSort = 1;

    public static function hasRecordTitle(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Wizard::make([
                    Forms\Components\Wizard\Step::make('Datos y clasificación')
                        ->icon('heroicon-m-cube')
                        ->schema([
                            Forms\Components\Grid::make(2)
                                ->schema([
                                    Forms\Components\TextInput::make('nombre')
                                        ->placeholder('Ej. Computadora de Escritorio OptiPlex')
                                        ->required()
                                        ->prefixIcon('heroicon-m-tag')
                                        ->maxLength(150)
                                        ->columnSpanFull(),
                                    Forms\Components\TextInput::make('modelo')
                                        ->placeholder('Ej. OptiPlex 7090')
                                        ->prefixIcon('heroicon-m-qr-code')
                                        ->maxLength(100),
                                    Forms\Components\Textarea::make('descripcion')
                                        ->label('Descripción')
                                        ->placeholder('Ej. Especificaciones y características generales del material...')
                                        ->rows(3)
                                        ->columnSpanFull(),
                                ]),
                            Forms\Components\Grid::make(2)
                                ->schema([
                                    Forms\Components\Select::make('id_marca')
                                        ->label('Marca')
                                        ->placeholder('Selecciona...')
                                        ->relationship('marca', 'nombre')
                                        ->required()
                                        ->searchable()
                                        ->preload()
                                        ->prefixIcon('heroicon-m-star'),
                                    Forms\Components\Select::make('id_tipodematerial')
                                        ->label('Tipo de material')
                                        ->placeholder('Selecciona...')
                                        ->relationship('tipo', 'nombre')
                                        ->required()
                                        ->searchable()
                                        ->preload()
                                        ->prefixIcon('heroicon-m-square-3-stack-3d'),
                                    Forms\Components\Select::make('id_unidad')
                                        ->label('Unidad de medida')
                                        ->placeholder('Selecciona...')
                                        ->relationship('unidad', 'nombre')
                                        ->required()
                                        ->searchable()
                                        ->preload()
                                        ->prefixIcon('heroicon-m-scale')
                                        ->columnSpanFull(),
                                ]),
                        ]),

                    Forms\Components\Wizard\Step::make('Control de stock')
                        ->icon('heroicon-m-chart-bar')
                        ->schema([
                            Forms\Components\TextInput::make('stock_actual')
                                ->label('Stock actual')
                                ->numeric()
                                ->prefixIcon('heroicon-m-archive-box')
                                ->disabled()
                                ->dehydrated(false)
                                ->helperText('Calculado automáticamente'),
                            Forms\Components\Hidden::make('stock_minimo')
                                ->default(0),
                            Forms\Components\Hidden::make('requiere_control_individual')
                                ->default(true),
                        ]),
                ])->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nombre')->wrap(false)->limit(30)->grow(false)
                    ->label('Nombre')
                    ->searchable(isIndividual: true)
                    ->sortable(),
                Tables\Columns\TextColumn::make('modelo')->wrap(false)->limit(30)->grow(false)->toggleable(isToggledHiddenByDefault: true)
                    ->label('Modelo')
                    ->searchable(),
                Tables\Columns\TextColumn::make('marca.nombre')->wrap(false)->limit(30)->grow(false)
                    ->label('Marca')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('tipo.nombre')->wrap(false)->limit(30)->grow(false)
                    ->label('Tipo')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('stock_actual')->wrap(false)->limit(30)->grow(false)
                    ->label('Stock actual')
                    ->numeric()
                    // Se ordena por el stock derivado, no por la columna fisica obsoleta.
                    ->sortable(query: fn (Builder $query, string $direction) => $query->orderByRaw(\App\Models\Material::stockDerivadoSql().' '.$direction)),
                Tables\Columns\TextColumn::make('stock_minimo')->wrap(false)->limit(30)->grow(false)->toggleable(isToggledHiddenByDefault: true)
                    ->label('Stock mín.')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('stock_status')->wrap(false)->limit(30)->grow(false)
                    ->label('Estado de stock')
                    ->getStateUsing(fn ($record) => $record->stock_actual < $record->stock_minimo ? 'Stock bajo' : 'Normal')
                    ->badge()
                    ->colors([
                        'danger' => 'Stock bajo',
                        'success' => 'Normal',
                    ])
                    ->icons([
                        'heroicon-m-exclamation-triangle' => 'Stock bajo',
                        'heroicon-m-check-circle' => 'Normal',
                    ]),
            ])
            ->defaultSort('nombre', 'asc')
            ->striped()
            ->searchPlaceholder('Buscar por nombre, modelo, marca o tipo...')
            ->defaultPaginationPageOption(10)
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordUrl(null)
            ->recordAction(null)
            ->actions([
                Tables\Actions\ViewAction::make()->iconButton()->tooltip('Ver detalle')->slideOver(),
                Tables\Actions\EditAction::make()
                    ->iconButton()
                    ->tooltip('Editar')
                    ->extraAttributes(['title' => 'Editar'])
                    ->slideOver()
                    ->modalHeading(fn (?Material $record) => 'Editar: ' . ($record?->nombre ?? 'material'))
                    ->modalDescription(fn (?Material $record) => new \Illuminate\Support\HtmlString(
                        e($record?->tipo?->nombre ?? '—') . ' · ID: #' . e($record?->getKey())
                    ))
                    ->successNotificationTitle('Material actualizado correctamente'),
                Tables\Actions\DeleteAction::make()->iconButton()->tooltip('Eliminar')->extraAttributes(['title' => 'Eliminar'])->successNotificationTitle('Registro eliminado correctamente'),
            ])
            ->bulkActions([]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return \App\Filament\Resources\MaterialResource::infolist($infolist);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMaterials::route('/'),
            'create' => Pages\CreateMaterial::route('/create'),
            'edit' => Pages\EditMaterial::route('/{record}/edit'),
        ];
    }
}
