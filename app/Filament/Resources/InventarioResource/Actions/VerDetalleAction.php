<?php

namespace App\Filament\Resources\InventarioResource\Actions;

use App\Filament\Concerns\ConModalesBajaCambio;
use App\Filament\Resources\InventarioResource;
use App\Models\Inventario;
use App\Support\SigemPdfGenerator;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Tables;
use Illuminate\Support\HtmlString;

class VerDetalleAction
{
    public static function make(): Tables\Actions\ViewAction
    {
        return Tables\Actions\ViewAction::make()
            ->iconButton()
            ->tooltip('Ver detalle')
            ->extraAttributes(['title' => 'Ver detalle', 'class' => 'icon-btn'])
            ->slideOver()
            ->modalHeading(fn (?Inventario $record) => $record?->material?->nombre ?? $record?->subtipo ?? 'Detalle de equipo')
            ->modalDescription(fn (?Inventario $record) => new HtmlString(
                'ID: ' . e($record?->codigo ?? $record?->num_serie ?? '—') . ' · ' . e($record?->material?->tipo?->nombre ?? $record?->subtipo ?? 'Sin categoría')
            ))
            ->modalCancelActionLabel('Cerrar')
            ->extraModalFooterActions([
                Tables\Actions\Action::make('desde_detalle_editar')
                    ->label('Modificar')
                    ->color('warning')
                    ->icon('heroicon-m-pencil-square')
                    ->visible(fn (?Inventario $record) => $record && ! in_array($record->estado, ['Dado de baja', 'Cambio de área'], true) && auth()->user()?->can('update', $record))
                    ->alpineClickHandler(fn (?Inventario $record) => "\$wire.replaceMountedTableAction('edit', '{$record?->getKey()}')"),

                Tables\Actions\Action::make('desde_detalle_cambio')
                    ->label('Cambio')
                    ->color('info')
                    ->icon('heroicon-m-arrows-right-left')
                    ->slideOver()
                    ->visible(fn (?Inventario $record) => $record && ! in_array($record->estado, ['Dado de baja', 'Cambio de área', 'Solicitud de baja'], true))
                    ->modalHeading(fn () => InventarioResource::esAdmin() ? 'Cambiar área de equipo' : 'Solicitar cambio de área')
                    ->modalDescription(fn () => InventarioResource::esAdmin() ? 'Se actualizará la ubicación física del equipo de forma inmediata.' : 'La solicitud quedará registrada en espera de revisión y aprobación por el Administrador.')
                    ->form(ConModalesBajaCambio::getModalCambioAreaFormSchema(InventarioResource::esAdmin()))
                    ->modalSubmitActionLabel(fn () => InventarioResource::esAdmin() ? 'Confirmar cambio' : 'Solicitar cambio')
                    ->modalCancelActionLabel('Cancelar')
                    ->action(function (Inventario $record, array $data, $livewire = null) {
                        $origenDeptoObj = $record->departamento ?? $record->area?->departamento;
                        $origenAreaObj = $record->area;

                        if (InventarioResource::esAdmin()) {
                            $record->cambiarAreaDirecto(
                                $data['departamento_id'],
                                $data['area_id'],
                                $data['aula'] ?? null,
                                $data['estacion'] ?? null,
                                $data['motivo'] ?? null,
                                auth()->id()
                            );
                            ConModalesBajaCambio::guardarMetadatosCambioArea($record, $data, $origenDeptoObj, $origenAreaObj);
                            Notification::make()->title('Cambio registrado')->body('Área del equipo actualizada correctamente')->success()->send();
                        } else {
                            $record->solicitarCambioArea(
                                $data['departamento_id'],
                                $data['area_id'],
                                $data['aula'] ?? null,
                                $data['estacion'] ?? null,
                                $data['motivo'] ?? null,
                                auth()->id()
                            );
                            ConModalesBajaCambio::guardarMetadatosCambioArea($record, $data, $origenDeptoObj, $origenAreaObj);
                            Notification::make()->title('Cambio registrado')->body('Solicitud de cambio de área enviada a revisión')->warning()->send();
                        }

                        if ($livewire && method_exists($livewire, 'replaceMountedTableAction')) {
                            $livewire->replaceMountedTableAction('checklist_cambio_area', (string) $record->getKey());
                        }
                    }),

                Tables\Actions\Action::make('aprobar_cambio')
                    ->label('✓ Aprobar cambio')
                    ->color('success')
                    ->visible(fn (?Inventario $record) => $record && $record->estado === 'Cambio de área' && InventarioResource::esAdmin())
                    ->requiresConfirmation()
                    ->modalHeading('Aprobar cambio de área')
                    ->modalDescription('¿Deseas autorizar el cambio de área solicitado? Se actualizará la ubicación del equipo.')
                    ->modalSubmitActionLabel('Sí, autorizar cambio')
                    ->modalCancelActionLabel('Cancelar')
                    ->action(function (Inventario $record) {
                        $record->aprobarCambioArea(auth()->id());
                        Notification::make()->title('Cambio de área aprobado exitosamente')->success()->send();
                    }),

                Tables\Actions\Action::make('rechazar_cambio')
                    ->label('✕ Rechazar cambio')
                    ->color('danger')
                    ->visible(fn (?Inventario $record) => $record && $record->estado === 'Cambio de área' && InventarioResource::esAdmin())
                    ->form([
                        Forms\Components\Textarea::make('motivo_rechazo')
                            ->label('Motivo del rechazo')
                            ->placeholder('Explica por qué se rechaza el cambio de área...')
                            ->required(),
                    ])
                    ->modalHeading('Rechazar cambio de área')
                    ->modalDescription('El equipo regresará al estado previo y la solicitud quedará cancelada.')
                    ->modalSubmitActionLabel('Confirmar rechazo')
                    ->modalCancelActionLabel('Cancelar')
                    ->action(function (Inventario $record, array $data) {
                        $record->rechazarCambioArea($data['motivo_rechazo'] ?? null, auth()->id());
                        Notification::make()->title('Solicitud de cambio de área rechazada')->warning()->send();
                    }),

                Tables\Actions\Action::make('Cambio de Área')
                    ->label('📄 Formato Cambio de Área')
                    ->color('info')
                    ->icon('heroicon-o-arrow-path')
                    ->url(fn (Inventario $record) => route('pdf.cambio.area', $record))
                    ->openUrlInNewTab()
                    ->visible(fn (Inventario $record) => (bool) $record->aprobado),

                Tables\Actions\Action::make('desde_detalle_baja')
                    ->label('Baja')
                    ->color('danger')
                    ->icon('heroicon-m-arrow-down-circle')
                    ->slideOver()
                    ->visible(fn (?Inventario $record) => $record && ! in_array($record->estado, ['Dado de baja', 'Solicitud de baja', 'Cambio de área'], true))
                    ->modalHeading(fn () => InventarioResource::esAdmin() ? 'Confirmar baja de equipo' : 'Solicitar baja de equipo')
                    ->modalDescription(fn () => InventarioResource::esAdmin() ? 'El equipo pasará a estado Dado de baja de manera inmediata.' : 'La solicitud quedará registrada en espera de revisión y aprobación por el Administrador.')
                    ->form(ConModalesBajaCambio::getModalBajaFormSchema())
                    ->modalSubmitActionLabel(fn () => InventarioResource::esAdmin() ? 'Confirmar baja' : 'Solicitar baja')
                    ->modalCancelActionLabel('Cancelar')
                    ->action(function (Inventario $record, array $data) {
                        $datosAdicionales = ConModalesBajaCambio::extraerDatosAdicionalesBaja($data);
                        if (InventarioResource::esAdmin()) {
                            $record->darBajaDirecta($data['motivo_baja'], $data['observaciones_baja'], auth()->id(), $datosAdicionales);
                            Notification::make()->title('Equipo dado de baja correctamente')->danger()->send();
                        } else {
                            $record->solicitarBaja($data['motivo_baja'], $data['observaciones_baja'], auth()->id(), $datosAdicionales);
                            Notification::make()->title('Solicitud de baja enviada a revisión')->warning()->send();
                        }
                    }),

                Tables\Actions\Action::make('aprobar_baja')
                    ->label('✓ Aprobar baja')
                    ->color('success')
                    ->visible(fn (?Inventario $record) => $record && $record->estado === 'Solicitud de baja' && InventarioResource::esAdmin())
                    ->requiresConfirmation()
                    ->modalHeading('Aprobar baja del equipo')
                    ->modalDescription('¿Deseas autorizar la baja definitiva de este equipo? El estado pasará a Dado de baja.')
                    ->modalSubmitActionLabel('Sí, autorizar baja')
                    ->modalCancelActionLabel('Cancelar')
                    ->action(function (Inventario $record) {
                        $record->aprobarBaja(auth()->id());
                        Notification::make()->title('Baja aprobada exitosamente')->success()->send();
                    }),

                Tables\Actions\Action::make('rechazar_baja')
                    ->label('✕ Rechazar baja')
                    ->color('danger')
                    ->visible(fn (?Inventario $record) => $record && $record->estado === 'Solicitud de baja' && InventarioResource::esAdmin())
                    ->form([
                        Forms\Components\Textarea::make('motivo_rechazo')
                            ->label('Motivo del rechazo')
                            ->placeholder('Explica por qué se rechaza la solicitud de baja...')
                            ->required(),
                    ])
                    ->modalHeading('Rechazar solicitud de baja')
                    ->modalDescription('El equipo regresará al estado previo y la solicitud quedará cancelada.')
                    ->modalSubmitActionLabel('Confirmar rechazo')
                    ->modalCancelActionLabel('Cancelar')
                    ->action(function (Inventario $record, array $data) {
                        $record->rechazarBaja($data['motivo_rechazo'] ?? null, auth()->id());
                        Notification::make()->title('Solicitud de baja rechazada')->warning()->send();
                    }),

                Tables\Actions\Action::make('reactivar_equipo')
                    ->label('Reactivar')
                    ->color('success')
                    ->icon('heroicon-m-arrow-path')
                    ->visible(fn (?Inventario $record) => $record && $record->estado === 'Dado de baja' && InventarioResource::esAdmin())
                    ->requiresConfirmation()
                    ->modalHeading('Reactivar equipo')
                    ->modalDescription('El equipo volverá al estado Disponible y se limpiará la información de baja.')
                    ->modalSubmitActionLabel('Sí, reactivar')
                    ->modalCancelActionLabel('Cancelar')
                    ->action(function (Inventario $record) {
                        $record->reactivar(auth()->id());
                        Notification::make()->title('Equipo reactivado y disponible')->success()->send();
                    }),
            ]);
    }

    public static function checklistCambioAreaAction(): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('checklist_cambio_area')
            ->label('Generar Formato PDF')
            ->color('info')
            ->icon('heroicon-o-document-arrow-down')
            ->extraAttributes(['style' => 'display: none;'])
            ->slideOver()
            ->modalHeading('Cambio registrado')
            ->modalDescription('Selecciona qué datos mostrar en el formato de Cambio de Área.')
            ->form(ConModalesBajaCambio::getChecklistCambioAreaSchema())
            ->modalSubmitActionLabel('Generar Formato PDF')
            ->modalCancelActionLabel('Cerrar')
            ->action(function (Inventario $record, array $data) {
                $campos = $data['campos_pdf'] ?? [];

                return SigemPdfGenerator::descargarCambioArea(
                    $record,
                    $record->especificaciones['cambio_motivo'] ?? null,
                    $record->especificaciones['jefe_destino'] ?? null,
                    null,
                    $campos
                );
            });
    }
}
