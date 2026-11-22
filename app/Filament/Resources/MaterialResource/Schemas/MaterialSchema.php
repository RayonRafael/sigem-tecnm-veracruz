<?php

namespace App\Filament\Resources\MaterialResource\Schemas;

use App\Filament\Schemas\CatalogoBaseFormSchema;
use App\Filament\Schemas\CatalogoBaseTableSchema;
use App\Models\Material;
use Filament\Forms;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\Tabs\Tab;
use Filament\Infolists\Components\TextEntry;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;

class MaterialSchema
{
    public static function getFormSchema(): array
    {
        return [
            Forms\Components\Wizard::make([
                Forms\Components\Wizard\Step::make('🔧 Datos del material')
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
                                CatalogoBaseFormSchema::selectRelacionConCreacion(
                                    name: 'id_marca',
                                    label: 'Marca',
                                    relationship: 'marca',
                                    icon: 'heroicon-m-star',
                                    modalHeading: '✏️ Agregar marca como nueva opción',
                                    createFieldLabel: 'Nombre de la marca',
                                    createFieldPlaceholder: 'Ej. Dell',
                                    tip: 'La marca estará disponible inmediatamente en el catálogo.',
                                    successMessage: 'Marca creada correctamente',
                                ),
                                CatalogoBaseFormSchema::selectRelacionConCreacion(
                                    name: 'id_tipodematerial',
                                    label: 'Tipo de material',
                                    relationship: 'tipo',
                                    icon: 'heroicon-m-square-3-stack-3d',
                                    modalHeading: '✏️ Agregar tipo de material como nueva opción',
                                    createFieldLabel: 'Nombre del tipo de material',
                                    createFieldPlaceholder: 'Ej. Equipo de Cómputo',
                                    tip: 'El tipo de material estará disponible inmediatamente en el catálogo.',
                                    successMessage: 'Tipo de material creado correctamente',
                                ),
                                CatalogoBaseFormSchema::selectRelacionConCreacion(
                                    name: 'id_unidad',
                                    label: 'Unidad de medida',
                                    relationship: 'unidad',
                                    icon: 'heroicon-m-scale',
                                    modalHeading: '✏️ Agregar unidad de medida como nueva opción',
                                    createFieldLabel: 'Nombre de la unidad de medida',
                                    createFieldPlaceholder: 'Ej. Pieza',
                                    tip: 'La unidad de medida estará disponible inmediatamente en el catálogo.',
                                    successMessage: 'Unidad de medida creada correctamente',
                                    columnSpanFull: true,
                                ),
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
                        Forms\Components\TextInput::make('stock_minimo')
                            ->label('Stock mínimo')
                            ->placeholder('Ej. 5')
                            ->numeric()
                            ->minValue(0)
                            ->default(0)
                            ->prefixIcon('heroicon-m-bell-alert')
                            ->required(),
                        Forms\Components\ToggleButtons::make('requiere_control_individual')
                            ->label('Requiere control individual')
                            ->boolean(trueLabel: 'Sí', falseLabel: 'No')
                            ->inline()
                            ->default(true)
                            ->columnSpanFull(),
                    ])->columns(2),
            ])->columnSpanFull(),
        ];
    }

    public static function getTableColumns(): array
    {
        return [
            CatalogoBaseTableSchema::columnaTexto('nombre', 'Nombre', individualSearch: true, sortable: true),
            CatalogoBaseTableSchema::columnaTexto('modelo', 'Modelo', hiddenByDefault: true),
            CatalogoBaseTableSchema::columnaTexto('marca.nombre', 'Marca', sortable: true),
            CatalogoBaseTableSchema::columnaTexto('tipo.nombre', 'Tipo', sortable: true),
            Tables\Columns\TextColumn::make('stock_actual')
                ->wrap(false)->limit(30)->grow(false)
                ->label('Stock actual')
                ->numeric()
                ->sortable(query: function (Builder $query, string $direction) {
                    $dir = strtolower($direction) === 'desc' ? 'DESC' : 'ASC';

                    return $query->orderByRaw(Material::stockDerivadoSql() . ' ' . $dir);
                }),
            Tables\Columns\TextColumn::make('stock_minimo')
                ->wrap(false)->limit(30)->grow(false)
                ->toggleable(isToggledHiddenByDefault: true)
                ->label('Stock mín.')
                ->numeric()
                ->sortable(),
            Tables\Columns\TextColumn::make('stock_status')
                ->wrap(false)->limit(30)->grow(false)
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
                ])
                ->sortable(query: function (Builder $query, string $direction) {
                    $dir = strtolower($direction) === 'desc' ? 'DESC' : 'ASC';

                    return $query->orderByRaw(Material::stockDerivadoSql() . ' - material.stock_minimo ' . $dir);
                }),
        ];
    }

    public static function getTableActions(bool $soloLectura): array
    {
        $edit = Tables\Actions\EditAction::make()
            ->iconButton()
            ->tooltip('Editar')
            ->extraAttributes(['title' => 'Editar'])
            ->modalHeading(fn (?Material $record) => 'Editar: ' . ($record?->nombre ?? 'material'))
            ->modalDescription(fn (?Material $record) => new HtmlString(
                e($record?->tipo?->nombre ?? '—') . ' · ID: #' . e($record?->getKey())
            ))
            ->successNotificationTitle('Material actualizado correctamente');

        if ($soloLectura) {
            $edit->slideOver();
        }

        return [
            Tables\Actions\ViewAction::make()->iconButton()->tooltip('Ver detalle')->slideOver(),
            $edit,
            Tables\Actions\DeleteAction::make()
                ->iconButton()
                ->tooltip('Eliminar')
                ->extraAttributes(['title' => 'Eliminar'])
                ->successNotificationTitle('Registro eliminado correctamente'),
        ];
    }

    public static function getInfolistTabs(): array
    {
        return [
            Tab::make('Información')
                ->icon('heroicon-m-tag')
                ->schema([
                    Section::make('🔧 Datos del material')
                        ->schema([
                            TextEntry::make('nombre')->label('Nombre')->icon('heroicon-m-tag'),
                            TextEntry::make('modelo')->label('Modelo')->icon('heroicon-m-qr-code'),
                            TextEntry::make('descripcion')->label('Descripción')->columnSpanFull(),
                        ])->columns(2),
                    Section::make('Clasificación')
                        ->schema([
                            TextEntry::make('marca.nombre')->label('Marca')->icon('heroicon-m-star'),
                            TextEntry::make('tipo.nombre')->label('Tipo')->icon('heroicon-m-square-3-stack-3d'),
                            TextEntry::make('unidad.nombre')->label('Unidad de medida')->icon('heroicon-m-scale'),
                        ])->columns(3),
                ]),
            Tab::make('Control de stock')
                ->icon('heroicon-m-chart-bar')
                ->schema([
                    Section::make('Inventario y Existencias')
                        ->schema([
                            TextEntry::make('stock_actual')->label('Stock actual')->icon('heroicon-m-archive-box'),
                            TextEntry::make('stock_minimo')->label('Stock mínimo')->icon('heroicon-m-bell-alert'),
                            TextEntry::make('estado_stock')
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
                            IconEntry::make('requiere_control_individual')
                                ->label('Control individual')
                                ->boolean(),
                        ])->columns(4),
                ]),
        ];
    }
}
