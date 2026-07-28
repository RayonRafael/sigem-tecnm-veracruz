<?php

namespace App\Filament\Resources\AreaResource\Pages;

use App\Filament\Concerns\ConDashboardBreadcrumb;

use App\Filament\Resources\AreaResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditArea extends EditRecord
{
    use ConDashboardBreadcrumb;
    protected static string $resource = AreaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return url('/admin');
    }
}
