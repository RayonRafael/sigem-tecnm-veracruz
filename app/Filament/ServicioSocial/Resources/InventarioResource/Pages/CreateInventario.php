<?php

namespace App\Filament\ServicioSocial\Resources\InventarioResource\Pages;

use App\Filament\ServicioSocial\Resources\InventarioResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateInventario extends CreateRecord
{
    protected static string $resource = InventarioResource::class;

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->title('Registro en espera de  aprobación')
            ->body('El registro ha sido creado y está pendiente de aprobación por el Administrador')
            ->info()
            ->send();
    }
}
