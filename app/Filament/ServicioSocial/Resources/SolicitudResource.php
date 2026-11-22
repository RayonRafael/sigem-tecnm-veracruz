<?php

namespace App\Filament\ServicioSocial\Resources;

use App\Filament\Resources\SolicitudResource as BaseSolicitudResource;
use App\Filament\ServicioSocial\Resources\SolicitudResource\Pages;
use Illuminate\Database\Eloquent\Builder;

class SolicitudResource extends BaseSolicitudResource
{
    protected static ?int $navigationSort = 1;

    protected static function esPanelServicioSocial(): bool
    {
        return true;
    }

    public static function hasRecordTitle(): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('id_usuario', auth()->id());
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSolicituds::route('/'),
            'create' => Pages\CreateSolicitud::route('/create'),
            'edit' => Pages\EditSolicitud::route('/{record}/edit'),
        ];
    }
}
