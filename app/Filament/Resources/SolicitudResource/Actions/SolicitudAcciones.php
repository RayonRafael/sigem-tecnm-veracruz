<?php

namespace App\Filament\Resources\SolicitudResource\Actions;

use App\Models\Solicitud;
use App\Support\SigemPdfGenerator;
use Filament\Notifications\Notification;
use Filament\Tables;
use Illuminate\Support\HtmlString;

class SolicitudAcciones
{
    public static function getTableActions(bool $esAdmin = true): array
    {
        $actions = [
            static::viewAction($esAdmin),
            static::editAction(),
            ...static::getAccionesPdfSolicitud(false),
        ];

        if ($esAdmin) {
            $actions[] = static::autorizarAction();
            $actions[] = static::rechazarAction();
        }

        return $actions;
    }

    public static function getBulkActions(bool $esAdmin = true): array
    {
        if (! $esAdmin) {
            return [];
        }

        return [
            Tables\Actions\BulkActionGroup::make([
                Tables\Actions\DeleteBulkAction::make()->successNotificationTitle('Solicitud eliminada'),
            ]),
        ];
    }

    public static function viewAction(bool $esAdmin = true): Tables\Actions\ViewAction
    {
        $footerActions = $esAdmin
            ? [
                Tables\Actions\Action::make('autorizar_slideover')
                    ->label('✓ Autorizar')
                    ->color('success')
                    ->visible(fn (?Solicitud $record): bool => $record?->estado === 'Pendiente')
                    ->alpineClickHandler(fn (?Solicitud $record) => "\$wire.replaceMountedTableAction('autorizar', '{$record?->getKey()}')"),
                Tables\Actions\Action::make('rechazar_slideover')
                    ->label('✕ Rechazar')
                    ->color('danger')
                    ->visible(fn (?Solicitud $record): bool => $record?->estado === 'Pendiente')
                    ->alpineClickHandler(fn (?Solicitud $record) => "\$wire.replaceMountedTableAction('rechazar', '{$record?->getKey()}')"),
                ...static::getAccionesPdfSolicitud(true),
            ]
            : static::getAccionesPdfSolicitud(true);

        return Tables\Actions\ViewAction::make()
            ->iconButton()
            ->tooltip('Ver detalle')
            ->extraAttributes(['title' => 'Ver detalle'])
            ->slideOver()
            ->modalHeading(fn (?Solicitud $record) => $record?->id_solicitud ? 'FOLIO-' . str_pad($record->id_solicitud, 5, '0', STR_PAD_LEFT) : 'Detalle de solicitud')
            ->modalDescription(fn (?Solicitud $record) => new HtmlString(
                'Solicitud de <b>' . e(match ($record?->tipo_movimiento) {
                    'Asignacion Permanente' => 'Asignación Permanente',
                    'Asignacion Temporal' => 'Asignación Temporal',
                    'Renta Externa' => 'Renta Externa',
                    default => $record?->tipo_movimiento ?? 'Solicitud',
                }) . '</b> · ' . e($record?->fecha_solicitud?->format('d/m/Y') ?? '—')
            ))
            ->modalCancelActionLabel('Cerrar')
            ->extraModalFooterActions($footerActions);
    }

    public static function editAction(): Tables\Actions\EditAction
    {
        return Tables\Actions\EditAction::make()
            ->iconButton()
            ->tooltip('Editar')
            ->extraAttributes(['title' => 'Editar'])
            ->modalHeading(fn (?Solicitud $record) => 'Editar Solicitud ' . ($record?->id_solicitud ? 'FOLIO-' . str_pad($record->id_solicitud, 5, '0', STR_PAD_LEFT) : ''))
            ->successNotificationTitle('Solicitud actualizada exitosamente');
    }

    public static function autorizarAction(): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('autorizar')
            ->label('✓ Autorizar')
            ->iconButton()
            ->color('success')
            ->icon('heroicon-m-check')
            ->tooltip('Autorizar')
            ->extraAttributes(['title' => 'Autorizar'])
            ->visible(fn (Solicitud $record): bool => $record->estado === 'Pendiente')
            ->requiresConfirmation()
            ->modalHeading(fn (Solicitud $record) => 'Autorizar FOLIO-' . str_pad($record->id_solicitud, 5, '0', STR_PAD_LEFT))
            ->modalDescription('¿Deseas autorizar esta solicitud de material?')
            ->modalSubmitActionLabel('Sí, autorizar')
            ->action(function (Solicitud $record) {
                $record->update([
                    'estado' => 'Autorizado',
                    'autorizado_por' => auth()->id(),
                    'fecha_autorizacion' => now(),
                ]);
                Notification::make()->title('Solicitud autorizada correctamente')->success()->send();
            });
    }

    public static function rechazarAction(): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('rechazar')
            ->label('✕ Rechazar')
            ->iconButton()
            ->color('danger')
            ->icon('heroicon-m-x-mark')
            ->tooltip('Rechazar')
            ->extraAttributes(['title' => 'Rechazar'])
            ->visible(fn (Solicitud $record): bool => $record->estado === 'Pendiente')
            ->requiresConfirmation()
            ->modalHeading(fn (Solicitud $record) => 'Rechazar FOLIO-' . str_pad($record->id_solicitud, 5, '0', STR_PAD_LEFT))
            ->modalDescription('¿Estás seguro de rechazar esta solicitud?')
            ->modalSubmitActionLabel('Sí, rechazar')
            ->action(function (Solicitud $record) {
                $record->update([
                    'estado' => 'Rechazado',
                    'autorizado_por' => auth()->id(),
                    'fecha_autorizacion' => now(),
                ]);
                Notification::make()->title('Solicitud rechazada')->danger()->send();
            });
    }

    public static function puedeDescargarActaEntrega(?Solicitud $record): bool
    {
        if (! $record || $record->estado === 'Rechazado') {
            return false;
        }

        return in_array($record->tipo_movimiento, [
            'Asignacion Permanente',
            'Asignacion Temporal',
            'Renta Externa',
            'Salida',
            'Traslado',
            'Préstamo',
            'Adaptación',
            'Asignación',
        ], true);
    }

    /**
     * Construye las acciones de descarga de Acta de Entrega (Formato 1) para fila o slide-over.
     *
     * @return array<int, Tables\Actions\Action>
     */
    public static function getAccionesPdfSolicitud(bool $enSlideOver = false): array
    {
        $sufijo = $enSlideOver ? 'Detalle' : '';

        $acta = Tables\Actions\Action::make('actaEntrega' . $sufijo)
            ->label('📄 Acta de Entrega')
            ->color('primary')
            ->tooltip('📄 Acta de Entrega')
            ->visible(fn (?Solicitud $record): bool => static::puedeDescargarActaEntrega($record))
            ->action(fn (Solicitud $record) => SigemPdfGenerator::descargarActaEntrega($record));

        if ($enSlideOver) {
            $acta->extraAttributes(['class' => 'sigem-pdf-btn']);
        } else {
            $acta->icon('heroicon-o-document-text')->iconButton()->extraAttributes([
                'class' => 'sigem-pdf-row-btn',
                'title' => '📄 Acta de Entrega',
            ]);
        }

        return [$acta];
    }
}
