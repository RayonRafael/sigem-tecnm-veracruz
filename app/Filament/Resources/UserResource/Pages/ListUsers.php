<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Concerns\ConDashboardBreadcrumb;
use App\Filament\Concerns\ConSlideOver;
use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListUsers extends ListRecords
{
    use ConDashboardBreadcrumb;
    use ConSlideOver;

    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    protected function mutateSlideOverData(array $data): array
    {
        if (isset($data['roles'])) {
            $data['tipo_usuario'] = UserResource::mapRoleToTipoUsuario($data['roles']);
        }

        return $data;
    }
}
