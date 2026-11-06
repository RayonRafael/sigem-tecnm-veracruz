<?php

namespace App\Filament\Resources\InventarioResource\Schemas;

use App\Models\HistorialEstado;
use App\Models\Inventario;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Support\Enums\FontWeight;

class InfolistSchema
{
    public static function make(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Tabs::make('Detalle')
                    ->extraAttributes(['style' => 'display: flex; flex-direction: column;'])
                    ->tabs([
                        Infolists\Components\Tabs\Tab::make('Información')
                            ->icon('heroicon-m-tag')
                            ->schema([
                                Infolists\Components\Section::make('⚠️ Información de baja')
                                    ->icon('heroicon-m-exclamation-triangle')
                                    ->visible(fn (?Inventario $record): bool => in_array($record?->estado, ['Dado de baja', 'Solicitud de baja', 'Baja'], true))
                                    ->schema([
                                        Infolists\Components\TextEntry::make('motivo_baja')
                                            ->label('Motivo de baja')
                                            ->badge()
                                            ->color('danger')
                                            ->placeholder('Sin motivo registrado'),
                                        Infolists\Components\TextEntry::make('fecha_baja')
                                            ->label('Fecha de baja')
                                            ->dateTime('d/m/Y H:i')
                                            ->placeholder('Pendiente de procesar'),
                                        Infolists\Components\TextEntry::make('usuarioBaja.name')
                                            ->label('Dado de baja / Solicitado por')
                                            ->icon('heroicon-m-user')
                                            ->placeholder('Sistema'),
                                        Infolists\Components\TextEntry::make('observaciones_baja')
                                            ->label('Observaciones de baja')
                                            ->columnSpanFull()
                                            ->placeholder('Sin observaciones registradas'),
                                    ])
                                    ->columns(3),

                                Infolists\Components\Section::make('Identificación del Equipo')
                                    ->collapsible()
                                    ->schema([
                                        Infolists\Components\TextEntry::make('codigo')
                                            ->label('Código')
                                            ->fontFamily('mono')
                                            ->weight(FontWeight::Bold)
                                            ->getStateUsing(fn (Inventario $record): string => $record->codigo ?: ($record->num_serie ?: ('#' . $record->id_inventario)))
                                            ->placeholder('Por capturar'),
                                        Infolists\Components\TextEntry::make('material.nombre')
                                            ->label('Nombre del equipo')
                                            ->icon('heroicon-m-cube')
                                            ->getStateUsing(fn (Inventario $record): string => $record->material?->nombre ?? $record->subtipo ?? 'Por capturar')
                                            ->placeholder('Por capturar'),
                                        Infolists\Components\TextEntry::make('marca_display')
                                            ->label('Marca')
                                            ->getStateUsing(fn (Inventario $record): string => $record->material?->marca?->nombre ?? ($record->especificaciones['marca'] ?? null) ?? ($record->especificaciones['cpu_marca'] ?? null) ?? 'Por capturar')
                                            ->placeholder('Por capturar'),
                                        Infolists\Components\TextEntry::make('modelo_display')
                                            ->label('Modelo')
                                            ->getStateUsing(fn (Inventario $record): string => $record->modelo ?? $record->material?->modelo ?? ($record->especificaciones['modelo'] ?? null) ?? 'Por capturar')
                                            ->placeholder('Por capturar'),
                                        Infolists\Components\TextEntry::make('num_serie')
                                            ->label('No. de serie')
                                            ->fontFamily('mono')
                                            ->icon('heroicon-m-qr-code')
                                            ->placeholder('Por capturar'),
                                        Infolists\Components\TextEntry::make('especificacion_texto_display')
                                            ->label('Especificaciones')
                                            ->getStateUsing(fn (Inventario $record): string => $record->especificacion_texto ?? ($record->especificaciones['especificaciones'] ?? null) ?? 'Por capturar')
                                            ->placeholder('Por capturar'),
                                        Infolists\Components\TextEntry::make('subtipo')
                                            ->label('Tipo de equipo')
                                            ->badge()
                                            ->color('primary')
                                            ->getStateUsing(fn (Inventario $record): string => $record->subtipo ?? $record->tipo_equipo ?? 'Por capturar')
                                            ->placeholder('Por capturar'),
                                        Infolists\Components\TextEntry::make('categoria_display')
                                            ->label('Categoría')
                                            ->badge()
                                            ->color('gray')
                                            ->getStateUsing(fn (Inventario $record): string => $record->material?->tipo?->nombre ?? $record->subtipo ?? 'Por capturar')
                                            ->placeholder('Por capturar'),
                                        Infolists\Components\TextEntry::make('estado')
                                            ->label('Estado')
                                            ->badge()
                                            ->formatStateUsing(fn (?string $state): string => $state ? '● ' . $state : 'Por capturar')
                                            ->color(fn (?string $state): string => match ($state) {
                                                'Disponible' => 'success',
                                                'Asignado' => 'info',
                                                'Reasignado' => 'reassign',
                                                'En Mantenimiento', 'En mantenimiento' => 'warning',
                                                'Solicitud de baja' => 'warning',
                                                'Dado de baja', 'Dañado', 'Baja' => 'danger',
                                                default => 'gray',
                                            }),
                                        Infolists\Components\TextEntry::make('sistema_operativo_display')
                                            ->label('Sistema operativo')
                                            ->getStateUsing(fn (Inventario $record): string => $record->sistema_operativo ?? ($record->especificaciones['sistema_operativo'] ?? null) ?? 'No aplica / Sin SO')
                                            ->placeholder('Por capturar'),
                                        Infolists\Components\TextEntry::make('tipo_propiedad')
                                            ->label('Propiedad')
                                            ->badge()
                                            ->formatStateUsing(fn (?string $state): string => $state ? '● ' . $state : 'Por capturar')
                                            ->color(fn (?string $state): string => match ($state) {
                                                'Propio' => 'primary',
                                                'Rentado' => 'gray',
                                                default => 'gray',
                                            }),
                                        Infolists\Components\TextEntry::make('usuario.name')
                                            ->label('Registrado por')
                                            ->icon('heroicon-m-user')
                                            ->placeholder('Por capturar'),
                                    ])->columns(2),

                                Infolists\Components\Section::make('Ubicación y Asignación')
                                    ->collapsible()
                                    ->schema([
                                        Infolists\Components\TextEntry::make('departamento_display')
                                            ->label('Departamento')
                                            ->icon('heroicon-m-building-office-2')
                                            ->getStateUsing(function (Inventario $record): string {
                                                $depto = $record->departamento ?? $record->area?->departamento;
                                                if ($depto) {
                                                    return $depto->encargado
                                                        ? "{$depto->nombre} (Encargado: {$depto->encargado})"
                                                        : $depto->nombre;
                                                }
                                                if ($record->ubicacion_fisica) {
                                                    return explode(' > ', $record->ubicacion_fisica)[0] ?? 'Por capturar';
                                                }
                                                return 'Por capturar';
                                            }),
                                        Infolists\Components\TextEntry::make('area_display')
                                            ->label('Área')
                                            ->icon('heroicon-m-building-office')
                                            ->getStateUsing(function (Inventario $record): string {
                                                if ($record->area?->nombre) {
                                                    return $record->area->nombre;
                                                }
                                                if ($record->ubicacion_fisica) {
                                                    $parts = explode(' > ', $record->ubicacion_fisica);
                                                    return $parts[1] ?? $parts[0] ?? 'Por capturar';
                                                }
                                                return 'Por capturar';
                                            }),
                                        Infolists\Components\TextEntry::make('aula_display')
                                            ->label('Aula')
                                            ->icon('heroicon-m-academic-cap')
                                            ->getStateUsing(function (Inventario $record): string {
                                                return $record->salon?->nombre
                                                    ?? ($record->especificaciones['aula'] ?? null)
                                                    ?? (preg_match('/Aula\s+([A-Za-z0-9_-]+)/i', $record->ubicacion_fisica ?? '', $m) ? $m[1] : null)
                                                    ?? '—';
                                            }),
                                        Infolists\Components\TextEntry::make('estacion')
                                            ->label('Estación')
                                            ->fontFamily('mono')
                                            ->badge()
                                            ->color('info')
                                            ->placeholder('Individual (sin estación)'),
                                        Infolists\Components\TextEntry::make('ubicacion_fisica')
                                            ->label('Ubicación física descriptiva')
                                            ->icon('heroicon-m-map-pin')
                                            ->placeholder('Por capturar')
                                            ->columnSpanFull(),
                                        Infolists\Components\TextEntry::make('fecha_asignacion')
                                            ->label('Fecha de asignación')
                                            ->date('d/m/Y')
                                            ->icon('heroicon-m-calendar')
                                            ->placeholder('Por capturar'),
                                        Infolists\Components\TextEntry::make('fecha_cambio_area')
                                            ->label('Fecha de cambio de área')
                                            ->date('d/m/Y')
                                            ->icon('heroicon-m-calendar-days')
                                            ->placeholder('Por capturar'),
                                    ])->columns(2),

                                Infolists\Components\Section::make('Red y Conectividad')
                                    ->collapsible()
                                    ->schema([
                                        Infolists\Components\TextEntry::make('ip_address')
                                            ->label('Dirección IP')
                                            ->fontFamily('mono')
                                            ->icon('heroicon-m-globe-alt')
                                            ->getStateUsing(fn (Inventario $record): ?string => $record->ip_address ?? ($record->especificaciones['ip_address'] ?? null))
                                            ->placeholder('Por capturar'),
                                        Infolists\Components\TextEntry::make('mac_address')
                                            ->label('Dirección MAC')
                                            ->fontFamily('mono')
                                            ->icon('heroicon-m-signal')
                                            ->getStateUsing(fn (Inventario $record): ?string => $record->mac_address ?? ($record->especificaciones['mac_address'] ?? null))
                                            ->placeholder('Por capturar'),
                                        Infolists\Components\TextEntry::make('procesador')
                                            ->label('Procesador')
                                            ->getStateUsing(fn (Inventario $record): ?string => $record->procesador ?? ($record->especificaciones['procesador'] ?? null))
                                            ->placeholder('Por capturar'),
                                        Infolists\Components\TextEntry::make('ram')
                                            ->label('Memoria RAM')
                                            ->getStateUsing(fn (Inventario $record): ?string => $record->ram ?? ($record->especificaciones['ram'] ?? null))
                                            ->placeholder('Por capturar'),
                                        Infolists\Components\TextEntry::make('disco')
                                            ->label('Disco duro')
                                            ->getStateUsing(fn (Inventario $record): ?string => $record->disco ?? ($record->especificaciones['disco'] ?? null))
                                            ->placeholder('Por capturar')
                                            ->columnSpanFull(),
                                    ])->columns(2),

                                Infolists\Components\Section::make('Componentes de la Estación')
                                    ->collapsible()
                                    ->visible(fn (?Inventario $record): bool => filled($record?->estacion))
                                    ->schema([
                                        Infolists\Components\RepeatableEntry::make('componentes_estacion')
                                            ->label('')
                                            ->getStateUsing(fn (?Inventario $record) => filled($record?->estacion)
                                                ? Inventario::with(['material.marca'])->where('estacion', $record->estacion)->orderBy('id_inventario')->get()
                                                : [])
                                            ->schema([
                                                Infolists\Components\TextEntry::make('nombre_componente')
                                                    ->label('Componente')
                                                    ->weight(FontWeight::Bold)
                                                    ->getStateUsing(fn (Inventario $comp): string => $comp->material?->nombre ?? $comp->subtipo ?? 'Componente'),
                                                Infolists\Components\TextEntry::make('codigo')
                                                    ->label('Código')
                                                    ->fontFamily('mono')
                                                    ->getStateUsing(fn (Inventario $comp): string => $comp->codigo ?: ('#' . $comp->id_inventario)),
                                                Infolists\Components\TextEntry::make('num_serie')
                                                    ->label('No. de serie (S/N)')
                                                    ->fontFamily('mono')
                                                    ->getStateUsing(fn (Inventario $comp): string => $comp->num_serie ?: '—'),
                                                Infolists\Components\TextEntry::make('marca_comp')
                                                    ->label('Marca')
                                                    ->getStateUsing(fn (Inventario $comp): string => $comp->material?->marca?->nombre ?? ($comp->especificaciones['marca'] ?? '—')),
                                                Infolists\Components\TextEntry::make('modelo_comp')
                                                    ->label('Modelo')
                                                    ->getStateUsing(fn (Inventario $comp): string => $comp->modelo ?? $comp->material?->modelo ?? ($comp->especificaciones['modelo'] ?? '—')),
                                            ])
                                            ->columns(2)
                                            ->columnSpanFull(),
                                    ]),

                                Infolists\Components\Section::make('Observaciones')
                                    ->collapsible()
                                    ->schema([
                                        Infolists\Components\TextEntry::make('observaciones_generales')
                                            ->label('Observaciones generales')
                                            ->placeholder('Por capturar')
                                            ->columnSpanFull(),
                                    ]),
                            ]),

                        Infolists\Components\Tabs\Tab::make('Historial')
                            ->icon('heroicon-m-clock')
                            ->schema([
                                Infolists\Components\RepeatableEntry::make('historial')
                                    ->label('')
                                    ->getStateUsing(fn (?Inventario $record) => $record
                                        ? HistorialEstado::with('usuario')
                                            ->where('id_inventario', $record->id_inventario)
                                            ->orderByDesc('fecha_cambio')
                                            ->orderByDesc('id_historial')
                                            ->get()
                                        : [])
                                    ->schema([
                                        Infolists\Components\TextEntry::make('fecha_cambio')
                                            ->label('Fecha')
                                            ->icon('heroicon-m-calendar')
                                            ->dateTime('d/m/Y H:i'),
                                        Infolists\Components\TextEntry::make('usuario.name')
                                            ->label('Usuario')
                                            ->icon('heroicon-m-user')
                                            ->default('Administrador')
                                            ->getStateUsing(fn (HistorialEstado $entry): string => $entry->usuario?->name ?? 'Administrador'),
                                        Infolists\Components\TextEntry::make('motivo_cambio')
                                            ->label('Movimiento')
                                            ->icon('heroicon-m-arrow-path')
                                            ->columnSpanFull()
                                            ->placeholder('Sin motivo registrado'),
                                        Infolists\Components\TextEntry::make('ubicacion_fisica')
                                            ->label('Ubicación registrada')
                                            ->icon('heroicon-m-map-pin')
                                            ->columnSpanFull()
                                            ->placeholder('Sin ubicación registrada')
                                            ->visible(fn (?string $state): bool => filled($state)),
                                        Infolists\Components\TextEntry::make('estado_anterior')
                                            ->label('Estado anterior')
                                            ->badge()
                                            ->placeholder('—')
                                            ->visible(fn (HistorialEstado $entry): bool => filled($entry->estado_anterior)),
                                        Infolists\Components\TextEntry::make('estado_nuevo')
                                            ->label('Estado nuevo')
                                            ->badge()
                                            ->visible(fn (HistorialEstado $entry): bool => filled($entry->estado_nuevo)),
                                    ])
                                    ->columns(2)
                                    ->columnSpanFull()
                                    ->placeholder('Sin historial de cambios registrado'),
                            ]),

                        Infolists\Components\Tabs\Tab::make('Documentación')
                            ->icon('heroicon-m-document-text')
                            ->schema([
                                Infolists\Components\Section::make('Factura y proveedor')
                                    ->collapsible()
                                    ->schema([
                                        Infolists\Components\TextEntry::make('proveedor.nombre_empresa')
                                            ->label('Proveedor')
                                            ->placeholder('Por capturar'),
                                        Infolists\Components\TextEntry::make('num_factura')
                                            ->label('Factura')
                                            ->placeholder('Por capturar'),
                                        Infolists\Components\TextEntry::make('fecha_factura')
                                            ->label('Fecha de factura')
                                            ->date('d/m/Y')
                                            ->placeholder('Por capturar'),
                                        Infolists\Components\TextEntry::make('tipo_impresora')
                                            ->label('Tipo de impresora')
                                            ->badge()
                                            ->color('info')
                                            ->getStateUsing(fn (Inventario $record): ?string => $record->tipo_impresora ?? ($record->especificaciones['tipo_impresora'] ?? null))
                                            ->placeholder('No especificado')
                                            ->visible(fn (?Inventario $record): bool => filled($record?->tipo_impresora)
                                                || filled($record?->especificaciones['tipo_impresora'] ?? null)
                                                || ($record?->especificaciones['tipo_equipo'] ?? '') === 'imp'
                                                || str_contains(strtolower(($record?->subtipo ?? '') . ' ' . ($record?->tipo_equipo ?? '') . ' ' . ($record?->material?->nombre ?? '')), 'impresora')),
                                    ])
                                    ->columns(2),

                                Infolists\Components\Section::make('Garantía')
                                    ->collapsible()
                                    ->schema([
                                        Infolists\Components\TextEntry::make('garantia_estado')
                                            ->label('Estado de garantía')
                                            ->badge()
                                            ->formatStateUsing(fn (?string $state): string => $state ? '● ' . ucfirst(str_replace('_', ' ', $state)) : 'Por capturar')
                                            ->color(fn (?string $state): string => match ($state) {
                                                'vigente' => 'success',
                                                'por_vencer' => 'warning',
                                                'vencida' => 'danger',
                                                default => 'gray',
                                            }),
                                        Infolists\Components\TextEntry::make('garantia_fecha_fin')
                                            ->label('Fin de garantía')
                                            ->date('d/m/Y')
                                            ->placeholder('Por capturar'),
                                    ])
                                    ->columns(2),

                                Infolists\Components\Section::make('Archivos adjuntos')
                                    ->collapsible()
                                    ->schema([
                                        Infolists\Components\TextEntry::make('archivos')
                                            ->label('')
                                            ->default('Sin archivos digitales adjuntos')
                                            ->getStateUsing(fn () => 'Sin archivos digitales adjuntos')
                                            ->color('gray'),
                                    ]),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
