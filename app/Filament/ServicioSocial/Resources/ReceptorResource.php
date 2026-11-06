<?php

namespace App\Filament\ServicioSocial\Resources;

use App\Filament\ServicioSocial\Resources\ReceptorResource\Pages;
use App\Models\Receptor;
use Filament\Forms\Form;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ReceptorResource extends Resource
{
    use \App\Filament\Concerns\ConCatalogoSoloLectura;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $model = Receptor::class;

    protected static ?string $recordTitleAttribute = 'nombre';

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'Catálogos';

    protected static ?string $navigationLabel = 'Receptores';

    protected static ?string $modelLabel = 'receptor';

    protected static ?string $pluralModelLabel = 'receptores';

    public static function getEloquentQuery(): Builder
    {
        return \App\Filament\Resources\ReceptorResource::getEloquentQuery();
    }

    public static function form(Form $form): Form
    {
        return \App\Filament\Resources\ReceptorResource::form($form);
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
                Tables\Columns\TextColumn::make('email')->wrap(false)->limit(30)->grow(false)->toggleable(isToggledHiddenByDefault: true)
                    ->label('Email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('telefono')->wrap(false)->limit(30)->grow(false)->toggleable(isToggledHiddenByDefault: true)
                    ->label('Teléfono')
                    ->searchable(),
                Tables\Columns\TextColumn::make('area.nombre')->wrap(false)->limit(30)->grow(false)
                    ->label('Área')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')->wrap(false)->limit(30)->grow(false)->toggleable(isToggledHiddenByDefault: true)
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
                    ->modalCancelActionLabel('Cerrar'),
                Tables\Actions\EditAction::make()
                    ->iconButton()
                    ->tooltip('Editar')
                    ->extraAttributes(['title' => 'Editar'])
                    ->slideOver()
                    ->modalHeading(fn (?Receptor $record) => 'Editar: ' . trim(($record?->nombre ?? '') . ' ' . ($record?->apellido_paterno ?? '')))
                    ->modalDescription(fn (?Receptor $record) => new \Illuminate\Support\HtmlString(
                        e($record?->cargo ?? '—') . ' · ID: #' . e($record?->getKey())
                    ))
                    ->successNotificationTitle('Receptor actualizado correctamente'),
                Tables\Actions\DeleteAction::make()->iconButton()->tooltip('Eliminar')->extraAttributes(['title' => 'Eliminar'])->successNotificationTitle('Registro eliminado correctamente'),
            ])
            ->bulkActions([]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return \App\Filament\Resources\ReceptorResource::infolist($infolist);
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
