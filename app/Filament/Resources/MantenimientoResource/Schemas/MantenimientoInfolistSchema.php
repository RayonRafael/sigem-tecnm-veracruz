<?php

namespace App\Filament\Resources\MantenimientoResource\Schemas;

use App\Models\Mantenimiento;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\Tabs;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Infolists\Infolist;
use Filament\Support\Enums\FontWeight;

class MantenimientoInfolistSchema
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
                                Section::make('💻 Equipo')
                                    ->schema([
                                        TextEntry::make('inventario.num_serie')
                                            ->label('Equipo')
                                            ->getStateUsing(fn (Mantenimiento $record): string => $record->inventario?->codigo ?? $record->inventario?->num_serie ?? '—')
                                            ->fontFamily('mono')
                                            ->weight(FontWeight::Bold)
                                            ->default('—'),
                                        TextEntry::make('inventario.material.nombre')
                                            ->label('Material')
                                            ->getStateUsing(fn (Mantenimiento $record): string => $record->inventario?->material?->nombre ?? $record->inventario?->subtipo ?? '—')
                                            ->default('—'),
                                        TextEntry::make('nombre_tecnico')
                                            ->label('Técnico asignado')
                                            ->default('—'),
                                        TextEntry::make('num_control_tecnico')
                                            ->label('No. de control')
                                            ->default('—'),
                                    ])
                                    ->columns(2),

                                Section::make('🧰 Detalle del servicio')
                                    ->schema([
                                        TextEntry::make('tipo_mantenimiento')
                                            ->label('Tipo de servicio')
                                            ->badge()
                                            ->formatStateUsing(fn (?string $state): string => $state ? '● ' . $state : '—')
                                            ->colors([
                                                'success' => 'Preventivo',
                                                'danger' => 'Correctivo',
                                                'primary' => 'Mejora',
                                            ]),
                                        TextEntry::make('estado')
                                            ->label('Estado actual')
                                            ->badge()
                                            ->formatStateUsing(fn ($state) => match ($state) {
                                                'Pendiente Revision Admin' => '● En revisión',
                                                'Completado' => '● Terminado',
                                                'Cancelado' => '● Rechazado',
                                                'Solicitado' => '● En revisión',
                                                default => '● ' . $state,
                                            })
                                            ->colors([
                                                'info' => ['En revisión', 'Pendiente Revision Admin', 'Solicitado'],
                                                'warning' => 'En proceso',
                                                'success' => ['Terminado', 'Completado'],
                                                'danger' => ['Rechazado', 'Cancelado'],
                                                'gray' => 'otros',
                                            ]),
                                        TextEntry::make('descripcion_falla')
                                            ->label('Descripción de la falla')
                                            ->default('Sin descripción registrada.')
                                            ->columnSpanFull(),
                                        TextEntry::make('descripcion_trabajo')
                                            ->label('Trabajo realizado / Hallazgo')
                                            ->default('Pendiente de registrar.')
                                            ->columnSpanFull(),
                                    ])
                                    ->columns(2),

                                Section::make('🗓 Fechas y estado')
                                    ->schema([
                                        TextEntry::make('fecha_solicitud')
                                            ->label('Fecha de solicitud')
                                            ->date('d/m/Y')
                                            ->default('—'),
                                        TextEntry::make('fecha_inicio')
                                            ->label('Fecha de inicio')
                                            ->date('d/m/Y')
                                            ->placeholder('— pendiente de autorizar'),
                                        TextEntry::make('fecha_fin')
                                            ->label('Fecha de fin')
                                            ->date('d/m/Y')
                                            ->placeholder('— pendiente de completar'),
                                    ])
                                    ->columns(3),
                            ]),

                        Tabs\Tab::make('historial')
                            ->label('🕘 Historial')
                            ->schema([
                                ViewEntry::make('historial')
                                    ->label('')
                                    ->view('filament.infolists.components.mantenimiento-historial')
                                    ->columnSpanFull(),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
