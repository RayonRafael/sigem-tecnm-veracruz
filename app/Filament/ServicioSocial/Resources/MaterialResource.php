<?php

namespace App\Filament\ServicioSocial\Resources;

use App\Filament\ServicioSocial\Resources\MaterialResource\Pages;
use App\Models\Material;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class MaterialResource extends Resource
{
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $model = Material::class;
    protected static ?string $navigationIcon = 'heroicon-o-globe-alt';
    protected static ?string $navigationLabel = 'Materiales';
    protected static ?string $modelLabel = 'Material';
    protected static ?string $pluralModelLabel = 'Materiales';
    protected static ?string $navigationGroup = 'Catálogos';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Datos del material')
                    ->icon('heroicon-m-cube')
                    ->schema([
                        Forms\Components\TextInput::make('nombre')
                            ->required()
                            ->prefixIcon('heroicon-m-tag')
                            ->maxLength(150),
                        Forms\Components\TextInput::make('modelo')
                            ->prefixIcon('heroicon-m-qr-code')
                            ->maxLength(100),
                        Forms\Components\Textarea::make('descripcion')
                            ->label('Descripción')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])->columns(2),

                Forms\Components\Section::make('Clasificación')
                    ->icon('heroicon-m-rectangle-stack')
                    ->schema([
                        Forms\Components\Select::make('id_marca')
                            ->label('Marca')
                            ->relationship('marca', 'nombre')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->prefixIcon('heroicon-m-star'),
                        Forms\Components\Select::make('id_tipodematerial')
                            ->label('Tipo de Material')
                            ->relationship('tipo', 'nombre')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->prefixIcon('heroicon-m-square-3-stack-3d'),
                        Forms\Components\Select::make('id_unidad')
                            ->label('Unidad de Medida')
                            ->relationship('unidad', 'nombre')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->prefixIcon('heroicon-m-scale'),
                    ])->columns(3),

                        Forms\Components\Hidden::make('stock_actual')
                            ->default(0),
                        Forms\Components\Hidden::make('stock_minimo')
                            ->default(0),
                        Forms\Components\Hidden::make('requiere_control_individual')
                            ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nombre')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('modelo')
                    ->label('Modelo')
                    ->searchable(),
                Tables\Columns\TextColumn::make('marca.nombre')
                    ->label('Marca')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('tipo.nombre')
                    ->label('Tipo')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('stock_actual')
                    ->label('Stock Actual')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('stock_minimo')
                    ->label('Stock Mín.')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('stock_status')
                    ->label('Estado de Stock')
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
            ->filters([])
            ->actions([
                Tables\Actions\ViewAction::make()->iconButton()->slideOver(),
                Tables\Actions\EditAction::make()->iconButton(),
            ])
            ->bulkActions([]);
    }

    public static function infolist(\Filament\Infolists\Infolist $infolist): \Filament\Infolists\Infolist
    {
        return $infolist
            ->schema([
                \Filament\Infolists\Components\Section::make('Datos del Material')
                    ->schema([
                        \Filament\Infolists\Components\TextEntry::make('nombre')->label('Nombre')->icon('heroicon-m-tag'),
                        \Filament\Infolists\Components\TextEntry::make('modelo')->label('Modelo')->icon('heroicon-m-qr-code'),
                        \Filament\Infolists\Components\TextEntry::make('descripcion')->label('Descripción')->columnSpanFull(),
                    ])->columns(2),
                \Filament\Infolists\Components\Section::make('Clasificación')
                    ->schema([
                        \Filament\Infolists\Components\TextEntry::make('marca.nombre')->label('Marca')->icon('heroicon-m-star'),
                        \Filament\Infolists\Components\TextEntry::make('tipo.nombre')->label('Tipo')->icon('heroicon-m-square-3-stack-3d'),
                        \Filament\Infolists\Components\TextEntry::make('unidad.nombre')->label('Unidad de Medida')->icon('heroicon-m-scale'),
                    ])->columns(3),
                \Filament\Infolists\Components\Section::make('Control de Stock')
                    ->schema([
                        \Filament\Infolists\Components\TextEntry::make('stock_actual')->label('Stock Actual')->icon('heroicon-m-archive-box'),
                        \Filament\Infolists\Components\TextEntry::make('stock_minimo')->label('Stock Mínimo')->icon('heroicon-m-bell-alert'),
                        \Filament\Infolists\Components\TextEntry::make('estado_stock')
                            ->label('Estado')
                            ->getStateUsing(fn ($record) => $record->stock_actual < $record->stock_minimo ? 'Stock bajo' : 'Normal')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'Stock bajo' => 'danger',
                                'Normal' => 'success',
                                default => 'gray',
                            })
                            ->icon(fn (string $state): string => match ($state) {
                                'Stock bajo' => 'heroicon-m-exclamation-triangle',
                                'Normal' => 'heroicon-m-check-circle',
                                default => 'heroicon-m-minus',
                            }),
                        \Filament\Infolists\Components\IconEntry::make('requiere_control_individual')
                            ->label('Control Individual')
                            ->boolean(),
                    ])->columns(4),
            ]);
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
