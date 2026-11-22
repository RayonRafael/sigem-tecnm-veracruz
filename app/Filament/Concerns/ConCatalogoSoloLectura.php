<?php

namespace App\Filament\Concerns;

use Illuminate\Database\Eloquent\Model;

trait ConCatalogoSoloLectura
{
    protected static function esSoloLectura(): bool
    {
        return true;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function hasRecordTitle(): bool
    {
        return false;
    }
}
