<?php

namespace App\Filament\ServicioSocial\Resources\SolicitudResource\Pages;

use App\Filament\ServicioSocial\Resources\SolicitudResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSolicitud extends EditRecord
{
    use EditRecord\Concerns\HasWizard;
    protected static string $resource = SolicitudResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function getRedirectUrl(): string
    {
        return url('/servicio-social');
    }
}
