<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BitacoraSistemaResource\Pages;
use App\Models\BitacoraSistema;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class BitacoraSistemaResource extends Resource
{
    protected static ?string $model = BitacoraSistema::class;
    protected static ?string $navigationIcon = 'heroicon-o-clock';
    protected static ?string $navigationLabel = 'Bitácora';
    protected static ?string $modelLabel = 'Registro de bitácora';
    protected static ?string $pluralModelLabel = 'Bitácora del sistema';
    protected static ?string $navigationGroup = 'Administración';
    protected static ?int $navigationSort = 10;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información del Registro')
                    ->schema([
                        Forms\Components\TextInput::make('fecha_hora')
                            ->label('Fecha y Hora'),
                        Forms\Components\TextInput::make('usuario.name')
                            ->label('Usuario'),
                        Forms\Components\TextInput::make('accion')
                            ->label('Acción'),
                        Forms\Components\TextInput::make('tabla_afectada')
                            ->label('Tabla Afectada'),
                        Forms\Components\TextInput::make('registro_id')
                            ->label('ID Registro'),
                        Forms\Components\TextInput::make('ip_address')
                            ->label('Dirección IP'),
                        Forms\Components\TextInput::make('user_agent')
                            ->label('User Agent')
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('detalles')
                            ->label('Detalles')
                            ->columnSpanFull(),
                    ])->columns(3),
                Forms\Components\Section::make('Datos del Cambio')
                    ->schema([
                        Forms\Components\KeyValue::make('datos_anteriores')
                            ->label('Datos Anteriores'),
                        Forms\Components\KeyValue::make('datos_nuevos')
                            ->label('Datos Nuevos'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('fecha_hora')
                    ->label('Fecha y Hora')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('usuario.name')
                    ->label('Usuario')
                    ->searchable(),
                Tables\Columns\TextColumn::make('accion')
                    ->label('Acción')
                    ->badge()
                    ->colors([
                        'success' => 'crear',
                        'warning' => 'editar',
                        'danger' => 'eliminar',
                    ])
                    ->searchable(),
                Tables\Columns\TextColumn::make('tabla_afectada')
                    ->label('Tabla Afectada')
                    ->searchable(),
                Tables\Columns\TextColumn::make('registro_id')
                    ->label('ID Registro')
                    ->searchable(),
                Tables\Columns\TextColumn::make('detalles')
                    ->label('Detalles')
                    ->limit(50)
                    ->tooltip(fn ($record) => $record->detalles)
                    ->searchable(),
            ])
            ->defaultSort('fecha_hora', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('accion')
                    ->label('Acción')
                    ->options([
                        'crear' => 'Crear',
                        'editar' => 'Editar',
                        'eliminar' => 'Eliminar',
                    ]),
                Tables\Filters\SelectFilter::make('tabla_afectada')
                    ->label('Tabla Afectada')
                    ->options([
                        'inventario' => 'Inventario',
                        'material' => 'Material',
                        'mantenimiento' => 'Mantenimiento',
                        'solicitud' => 'Solicitud',
                        'proveedor' => 'Proveedor',
                    ]),
                Tables\Filters\SelectFilter::make('id_usuario')
                    ->label('Usuario')
                    ->relationship('usuario', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\Filter::make('rango_fechas')
                    ->form([
                        Forms\Components\DatePicker::make('fecha_desde')
                            ->label('Desde'),
                        Forms\Components\DatePicker::make('fecha_hasta')
                            ->label('Hasta'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['fecha_desde'], fn ($query, $date) => $query->whereDate('fecha_hora', '>=', $date))
                        ->when($data['fecha_hasta'], fn ($query, $date) => $query->whereDate('fecha_hora', '<=', $date))
                    ),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->iconButton(),
            ])
            ->bulkActions([
                // Sin bulk actions de delete
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBitacoraSistemas::route('/'),
            'view' => Pages\ViewBitacoraSistema::route('/{record}'),
        ];
    }
}
