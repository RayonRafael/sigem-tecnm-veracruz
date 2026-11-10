<?php

namespace App\Filament\Resources\SolicitudResource\Pages;

use App\Filament\Concerns\ConDashboardBreadcrumb;
use App\Filament\Concerns\ConSlideOver;
use App\Filament\Resources\SolicitudResource;
use App\Models\DetalleSolicitud;
use App\Models\Receptor;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSolicituds extends ListRecords
{
    use ConDashboardBreadcrumb;
    use ConSlideOver;

    protected static string $resource = SolicitudResource::class;

    protected ?int $productoIdParaDetalle = null;

    public function getTitle(): string
    {
        return '📋 Solicitud de Material/Equipo';
    }

    public function getSubheading(): ?string
    {
        return 'Peticiones de salida de material. Clic en el ojo 👁 o en la fila para desplegar el detalle en el panel lateral.';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('＋ Nueva solicitud')
                ->modalHeading('＋ Nueva Solicitud')
                ->successNotificationTitle('Solicitud enviada exitosamente'),
        ];
    }

    protected function mutateSlideOverCreateData(array $data): array
    {
        $tipo = $data['tipo_solicitud'] ?? 'Salida';
        $data['tipo_movimiento'] = match ($tipo) {
            'Salida' => 'Asignacion Permanente',
            'Entrada' => 'Asignacion Temporal',
            'Traslado' => 'Renta Externa',
            default => 'Asignacion Permanente',
        };
        unset($data['tipo_solicitud']);

        if (isset($data['id_producto'])) {
            $this->productoIdParaDetalle = (int) $data['id_producto'];
            unset($data['id_producto']);
        }

        $data['id_usuario'] = auth()->id();
        $data['estado'] = 'Pendiente';
        $data['id_receptor'] = $data['id_receptor'] ?? (Receptor::value('id_receptor') ?? 1);

        return $data;
    }

    protected function afterSlideOverCreate($record, array $data): void
    {
        if ($this->productoIdParaDetalle) {
            DetalleSolicitud::create([
                'id_solicitud' => $record->id_solicitud,
                'id_producto' => $this->productoIdParaDetalle,
                'cantidad' => 1,
            ]);
            $this->productoIdParaDetalle = null;
        }
    }

    protected function mutateSlideOverFillData(array $data, $record): array
    {
        $tipo = $data['tipo_movimiento'] ?? '';
        $data['tipo_solicitud'] = match ($tipo) {
            'Asignacion Permanente', 'Salida' => 'Salida',
            'Asignacion Temporal', 'Entrada' => 'Entrada',
            'Renta Externa', 'Traslado' => 'Traslado',
            default => 'Salida',
        };

        $detalle = $record->detalles()->first();
        if ($detalle) {
            $data['id_producto'] = $detalle->id_producto;
        }

        return $data;
    }

    protected function mutateSlideOverSaveData(array $data, $record): array
    {
        if (isset($data['tipo_solicitud'])) {
            $data['tipo_movimiento'] = match ($data['tipo_solicitud']) {
                'Salida' => 'Asignacion Permanente',
                'Entrada' => 'Asignacion Temporal',
                'Traslado' => 'Renta Externa',
                default => 'Asignacion Permanente',
            };
            unset($data['tipo_solicitud']);
        }

        if (isset($data['id_producto'])) {
            $detalle = $record->detalles()->first();
            if ($detalle) {
                $detalle->update(['id_producto' => $data['id_producto']]);
            } else {
                DetalleSolicitud::create([
                    'id_solicitud' => $record->id_solicitud,
                    'id_producto' => $data['id_producto'],
                    'cantidad' => 1,
                ]);
            }
            unset($data['id_producto']);
        }

        return $data;
    }
}
