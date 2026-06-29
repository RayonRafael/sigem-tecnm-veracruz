<?php

namespace App\Filament\Widgets;

use App\Models\Solicitud;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class SolicitudesPendientesWidget extends BaseWidget
{
    protected static ?string $heading = 'Solicitudes Pendientes de Autorización';
    protected static ?int $sort = 4;
    protected int | string | array $columnSpan = 8;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Solicitud::where('estado', 'Pendiente')->latest('fecha_solicitud')
            )
            ->columns([
                Tables\Columns\TextColumn::make('id_solicitud')->label('Folio')->sortable(),
                Tables\Columns\TextColumn::make('tipo_movimiento')->label('Tipo')->badge(),
                Tables\Columns\TextColumn::make('usuario.name')->label('Solicitante'),
                Tables\Columns\TextColumn::make('fecha_solicitud')->label('Fecha')->date('d/m/Y'),
                Tables\Columns\TextColumn::make('estado')->badge()->color('warning'),
            ])
            ->actions([
                Tables\Actions\Action::make('Ver')
                    ->url(fn (Solicitud $record): string => route('filament.admin.resources.solicituds.edit', $record))
                    ->icon('heroicon-o-eye'),
            ]);
    }
}
