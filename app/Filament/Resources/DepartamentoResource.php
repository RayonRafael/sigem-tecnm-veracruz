<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DepartamentoResource\Pages;
use App\Models\Departamento;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\Tabs;
use Filament\Infolists\Components\Tabs\Tab;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class DepartamentoResource extends Resource
{
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $model = Departamento::class;

    protected static ?string $recordTitleAttribute = 'nombre';

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?string $navigationLabel = 'Departamentos';

    protected static ?string $modelLabel = 'departamento';

    protected static ?string $pluralModelLabel = 'departamentos';

    protected static ?string $navigationGroup = 'Catálogos';

    protected static ?int $navigationSort = 1;

    public static function getGloballySearchableAttributes(): array
    {
        return ['nombre'];
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Datos del departamento')
                    ->icon('heroicon-m-building-office-2')
                    ->schema([
                        Forms\Components\TextInput::make('nombre')
                            ->label('Nombre')
                            ->placeholder('Ej. Departamento de Ciencias Básicas')
                            ->required()
                            ->prefixIcon('heroicon-m-tag')
                            ->maxLength(100),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nombre')->wrap(false)->limit(30)->grow(false)->label('Nombre')->searchable(isIndividual: true)->sortable(),
            ])
            ->defaultSort('nombre', 'asc')
            ->searchPlaceholder('Buscar por nombre del departamento...')
            ->recordUrl(null)
            ->recordAction(null)
            ->actions([
                Tables\Actions\ViewAction::make()->iconButton()->tooltip('Ver detalle')->slideOver(),
                Tables\Actions\EditAction::make()->iconButton()->tooltip('Editar')->successNotificationTitle('Departamento actualizado correctamente'),
                Tables\Actions\DeleteAction::make()->iconButton()->tooltip('Eliminar')->extraAttributes(['title' => 'Eliminar'])->successNotificationTitle('Registro eliminado correctamente'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()->successNotificationTitle('Departamento eliminado'),
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
                                Section::make('Datos del departamento')
                                    ->schema([
                                        TextEntry::make('nombre')->label('Nombre del departamento')->icon('heroicon-m-building-office-2'),
                                        TextEntry::make('areas_count')
                                            ->label('Áreas adscritas')
                                            ->state(fn (Departamento $record): string => $record->areas()->count() . ' registradas')
                                            ->icon('heroicon-m-map-pin'),
                                    ])->columns(2),
                            ]),
                    ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDepartamentos::route('/'),
            'create' => Pages\CreateDepartamento::route('/create'),
            'edit' => Pages\EditDepartamento::route('/{record}/edit'),
        ];
    }
}
