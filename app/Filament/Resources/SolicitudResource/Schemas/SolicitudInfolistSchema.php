<?php

namespace App\Filament\Resources\SolicitudResource\Schemas;

use App\Models\Solicitud;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\Tabs;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Infolists\Infolist;
use Filament\Support\Enums\FontWeight;

class SolicitudInfolistSchema
{
    public static function getInfolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Tabs::make('Detalle')
                    ->extraAttributes(['style' => 'display: flex; flex-direction: column;'])
                    ->tabs([
                        Tabs\Tab::make('informacion')
                            ->label('🔖 Información')
                            ->schema([
                                Section::make('ℹ️ Información de la solicitud')
                                    ->schema([
                                        TextEntry::make('id_solicitud')
                                            ->label('Folio')
                                            ->formatStateUsing(fn ($state) => $state ? 'FOLIO-' . str_pad($state, 5, '0', STR_PAD_LEFT) : '—')
                                            ->fontFamily('mono')
                                            ->weight(FontWeight::Bold),
                                        TextEntry::make('tipo_movimiento')
                                            ->label('Tipo')
                                            ->badge()
                                            ->formatStateUsing(fn (?string $state): string => match ($state) {
                                                'Asignacion Permanente', 'Salida' => 'Salida',
                                                'Asignacion Temporal', 'Entrada' => 'Entrada',
                                                'Renta Externa', 'Traslado' => 'Traslado',
                                                default => $state ?? '—',
                                            })
                                            ->color('info'),
                                        TextEntry::make('estado')
                                            ->label('Estado')
                                            ->badge()
                                            ->formatStateUsing(fn (?string $state): string => $state ? '● ' . $state : '—')
                                            ->color(fn (?string $state): string => match ($state) {
                                                'Pendiente' => 'warning',
                                                'Autorizado' => 'success',
                                                'Completado', 'Entregado' => 'info',
                                                'Rechazado' => 'danger',
                                                default => 'gray',
                                            }),
                                        TextEntry::make('fecha_solicitud')
                                            ->label('Fecha')
                                            ->date('d/m/Y'),
                                        TextEntry::make('material_solicitado')
                                            ->label('Material solicitado')
                                            ->icon('heroicon-m-cube')
                                            ->getStateUsing(function (?Solicitud $record): string {
                                                if (! $record) {
                                                    return '—';
                                                }
                                                $nombres = $record->detalles()
                                                    ->with('material')
                                                    ->get()
                                                    ->map(fn ($detalle) => $detalle->material?->nombre)
                                                    ->filter()
                                                    ->unique()
                                                    ->values();

                                                return $nombres->isNotEmpty() ? $nombres->implode(', ') : '—';
                                            })
                                            ->columnSpanFull(),
                                    ])
                                    ->columns(2),

                                Section::make('👥 Participantes')
                                    ->schema([
                                        TextEntry::make('usuario.name')
                                            ->label('Solicitante')
                                            ->default('—'),
                                        TextEntry::make('autorizadoPor.name')
                                            ->label('Autorizado por')
                                            ->default('— Sin autorizar —'),
                                    ])
                                    ->columns(2),

                                Section::make('')
                                    ->schema([
                                        TextEntry::make('observaciones')
                                            ->label('Detalle (observaciones)')
                                            ->default('Sin observaciones registradas.')
                                            ->columnSpanFull(),
                                    ]),
                            ]),

                        Tabs\Tab::make('historial')
                            ->label('🕘 Historial')
                            ->schema([
                                ViewEntry::make('historial')
                                    ->label('')
                                    ->view('filament.infolists.components.solicitud-historial')
                                    ->columnSpanFull(),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
