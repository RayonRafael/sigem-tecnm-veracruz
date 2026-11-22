<?php

namespace App\Filament\ServicioSocial\Resources;

use App\Filament\Resources\MantenimientoResource as BaseMantenimientoResource;
use App\Filament\ServicioSocial\Resources\MantenimientoResource\Pages;
use Illuminate\Database\Eloquent\Builder;

class MantenimientoResource extends BaseMantenimientoResource
{
    protected static ?int $navigationSort = 2;

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
            ->where('id_usuario_solicita', auth()->id());
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMantenimientos::route('/'),
            'create' => Pages\CreateMantenimiento::route('/create'),
            'edit' => Pages\EditMantenimiento::route('/{record}/edit'),
        ];
    }
}
