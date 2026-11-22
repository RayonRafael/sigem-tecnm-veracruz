<?php

namespace App\Filament\Resources\MantenimientoResource\Actions;

use App\Enums\EstadoInventario;
use App\Filament\Resources\MantenimientoResource\Schemas\MantenimientoFormSchema;
use App\Models\HistorialEstado;
use App\Models\Mantenimiento;
use App\Models\User;
use App\Support\SigemPdfGenerator;
use Filament\Notifications\Notification;
use Filament\Tables;
use Illuminate\Support\HtmlString;

class MantenimientoAcciones
{
    public static function getTableActions(bool $esAdmin = true): array
    {
        $actions = [
            static::viewAction($esAdmin),
            static::editAction(),
            ...static::getAccionesPdfMantenimiento(false),
        ];

        if ($esAdmin) {
            $actions[] = static::autorizarAction();
            $actions[] = static::completarAction();
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
                Tables\Actions\DeleteBulkAction::make()->successNotificationTitle('Mantenimiento eliminado'),
            ]),
        ];
    }

    public static function viewAction(bool $esAdmin = true): Tables\Actions\ViewAction
    {
        $footerActions = $esAdmin
            ? [
                Tables\Actions\Action::make('autorizar_slideover')
                    ->label('✓ Autorizar servicio')
                    ->color('primary')
                    ->visible(fn (?Mantenimiento $record): bool => in_array($record?->estado, ['Pendiente Revision Admin', 'En revisión', 'Solicitado']))
                    ->requiresConfirmation()
                    ->modalHeading('Autorizar mantenimiento')
                    ->modalDescription('El reporte pasará a En proceso y el equipo pasará a estado En mantenimiento.')
                    ->modalSubmitActionLabel('Sí, autorizar')
                    ->action(fn (Mantenimiento $record) => static::autorizarMantenimiento($record)),
                Tables\Actions\Action::make('rechazar_slideover')
                    ->label('✕ Rechazar')
                    ->color('danger')
                    ->visible(fn (?Mantenimiento $record): bool => in_array($record?->estado, ['Pendiente Revision Admin', 'En revisión', 'Solicitado']))
                    ->requiresConfirmation()
                    ->modalHeading('Rechazar mantenimiento')
                    ->modalDescription('El reporte quedará marcado como rechazado y el estado del equipo no cambiará.')
                    ->modalSubmitActionLabel('Sí, rechazar')
                    ->action(fn (Mantenimiento $record) => static::rechazarMantenimiento($record)),
                Tables\Actions\Action::make('completar_slideover')
                    ->label('Completar')
                    ->color('success')
                    ->visible(fn (?Mantenimiento $record): bool => $record?->estado === 'En proceso')
                    ->modalHeading('Completar mantenimiento')
                    ->modalDescription('Se marcará el servicio como finalizado y el equipo volverá a Disponible.')
                    ->form(MantenimientoFormSchema::getCompletarFormSchema())
                    ->modalSubmitActionLabel('Completar')
                    ->action(fn (Mantenimiento $record, array $data) => static::completarMantenimiento($record, $data)),
                ...static::getAccionesPdfMantenimiento(true),
            ]
            : static::getAccionesPdfMantenimiento(true);

        return Tables\Actions\ViewAction::make()
            ->iconButton()
            ->tooltip('Ver detalle')
            ->extraAttributes(['title' => 'Ver detalle'])
            ->slideOver()
            ->modalHeading(fn (?Mantenimiento $record) => 'Mantenimiento #' . ($record?->id_mantenimiento ? str_pad($record->id_mantenimiento, 4, '0', STR_PAD_LEFT) : '—'))
            ->modalDescription(fn (?Mantenimiento $record) => new HtmlString(
                'Equipo: <b>' . e($record?->inventario?->codigo ?? $record?->inventario?->num_serie ?? '—') . '</b> · ' . e($record?->fecha_solicitud?->format('d/m/Y') ?? '—')
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
            ->modalHeading(fn (?Mantenimiento $record) => 'Editar Mantenimiento #' . ($record?->id_mantenimiento ? str_pad($record->id_mantenimiento, 4, '0', STR_PAD_LEFT) : '—'))
            ->successNotificationTitle('Mantenimiento actualizado exitosamente');
    }

    public static function autorizarAction(): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('autorizar')
            ->iconButton()
            ->color('success')
            ->icon('heroicon-m-check')
            ->tooltip('Autorizar')
            ->extraAttributes(['title' => 'Autorizar'])
            ->visible(fn (Mantenimiento $record): bool => in_array($record->estado, ['Pendiente Revision Admin', 'En revisión', 'Solicitado']))
            ->requiresConfirmation()
            ->modalHeading(fn (Mantenimiento $record) => 'Autorizar mantenimiento #' . str_pad($record->id_mantenimiento, 4, '0', STR_PAD_LEFT))
            ->modalDescription('El reporte pasará a En proceso y el equipo pasará a estado En mantenimiento.')
            ->modalSubmitActionLabel('Sí, autorizar')
            ->action(fn (Mantenimiento $record) => static::autorizarMantenimiento($record));
    }

    public static function completarAction(): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('completar')
            ->iconButton()
            ->color('info')
            ->icon('heroicon-m-check-badge')
            ->tooltip('Completar')
            ->extraAttributes(['title' => 'Completar'])
            ->visible(fn (Mantenimiento $record): bool => $record->estado === 'En proceso')
            ->modalHeading(fn (Mantenimiento $record) => 'Completar mantenimiento #' . str_pad($record->id_mantenimiento, 4, '0', STR_PAD_LEFT))
            ->modalDescription('Se marcará el servicio como finalizado y el equipo volverá a Disponible.')
            ->form(MantenimientoFormSchema::getCompletarFormSchema())
            ->modalSubmitActionLabel('Completar')
            ->action(fn (Mantenimiento $record, array $data) => static::completarMantenimiento($record, $data));
    }

    public static function autorizarMantenimiento(Mantenimiento $record): void
    {
        $record->update(['estado' => 'En proceso', 'fecha_inicio' => now()]);
        $record->inventario?->update(['estado' => EstadoInventario::EnMantenimiento->value]);
        Notification::make()->title('Mantenimiento autorizado correctamente')->success()->send();
    }

    public static function rechazarMantenimiento(Mantenimiento $record): void
    {
        $record->update(['estado' => 'Cancelado']);
        $inventario = $record->inventario?->fresh();

        if ($inventario && in_array($inventario->estado, [EstadoInventario::EnMantenimiento->value, 'En Mantenimiento'], true)) {
            $estadoAnt = $inventario->estado;
            $inventario->update(['estado' => EstadoInventario::Disponible->value]);

            $userId = auth()->id()
                ?? $record->id_usuario_solicita
                ?? $inventario->id_usuario
                ?? User::where('email', 'admin@tecnm.edu.mx')->value('id')
                ?? User::first()?->id;

            if ($userId) {
                HistorialEstado::create([
                    'id_inventario' => $inventario->id_inventario,
                    'id_usuario' => $userId,
                    'estado_anterior' => $estadoAnt,
                    'estado_nuevo' => EstadoInventario::Disponible->value,
                    'motivo_cambio' => 'Mantenimiento rechazado: equipo restaurado a Disponible',
                    'ubicacion_fisica' => $inventario->ubicacion_fisica,
                    'fecha_cambio' => now(),
                ]);
            }
        }

        Notification::make()->title('Mantenimiento rechazado')->danger()->send();
    }

    public static function completarMantenimiento(Mantenimiento $record, array $data = []): void
    {
        $update = [
            'estado' => 'Completado',
            'fecha_fin' => now(),
        ];

        if (filled($data['descripcion_trabajo'] ?? null)) {
            $update['descripcion_trabajo'] = trim((string) $data['descripcion_trabajo']);
        }

        $record->update($update);
        $record->inventario?->update(['estado' => 'Disponible']);

        Notification::make()->title('Mantenimiento completado')->success()->send();
    }

    public static function puedeDescargarOrdenTrabajo(?Mantenimiento $record): bool
    {
        return $record !== null && in_array($record->estado, [
            'Pendiente Revision Admin',
            'Pendiente',
            'Solicitado',
            'En revisión',
            'En proceso',
            'Completado',
            'Terminado',
        ], true);
    }

    public static function puedeDescargarListaVerificacion(?Mantenimiento $record): bool
    {
        return $record !== null && in_array($record->estado, ['Completado', 'Terminado'], true);
    }

    /**
     * Construye las acciones de descarga PDF (Formato 2 y Formato 3) para filas o slide-over.
     *
     * @return array<int, Tables\Actions\Action>
     */
    public static function getAccionesPdfMantenimiento(bool $enSlideOver = false): array
    {
        $sufijo = $enSlideOver ? 'Detalle' : '';

        $ordenTrabajo = Tables\Actions\Action::make('ordenTrabajo' . $sufijo)
            ->label('📄 Orden de Trabajo')
            ->color('primary')
            ->tooltip('📄 Orden de Trabajo')
            ->visible(fn (?Mantenimiento $record): bool => static::puedeDescargarOrdenTrabajo($record))
            ->action(fn (Mantenimiento $record) => SigemPdfGenerator::descargarOrdenTrabajo($record));

        $listaVerificacion = Tables\Actions\Action::make('listaVerificacion' . $sufijo)
            ->label('📄 Lista Verificación')
            ->color('primary')
            ->tooltip('📄 Lista Verificación')
            ->visible(fn (?Mantenimiento $record): bool => static::puedeDescargarListaVerificacion($record))
            ->action(fn (Mantenimiento $record) => SigemPdfGenerator::descargarListaVerificacion($record));

        if ($enSlideOver) {
            $ordenTrabajo->extraAttributes(['class' => 'sigem-pdf-btn']);
            $listaVerificacion->extraAttributes(['class' => 'sigem-pdf-btn']);
        } else {
            $ordenTrabajo->icon('heroicon-o-document-text')->iconButton()->extraAttributes([
                'class' => 'sigem-pdf-row-btn',
                'title' => '📄 Orden de Trabajo',
            ]);
            $listaVerificacion->icon('heroicon-o-clipboard-document-check')->iconButton()->extraAttributes([
                'class' => 'sigem-pdf-row-btn',
                'title' => '📄 Lista Verificación',
            ]);
        }

        return [$ordenTrabajo, $listaVerificacion];
    }
}
