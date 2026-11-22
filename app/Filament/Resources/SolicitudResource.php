<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SolicitudResource\Actions\SolicitudAcciones;
use App\Filament\Resources\SolicitudResource\Pages;
use App\Filament\Resources\SolicitudResource\Schemas\SolicitudFormSchema;
use App\Filament\Resources\SolicitudResource\Schemas\SolicitudInfolistSchema;
use App\Filament\Resources\SolicitudResource\Schemas\SolicitudTableSchema;
use App\Models\Solicitud;
use Filament\Forms\Form;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SolicitudResource extends Resource
{
    protected static ?string $model = Solicitud::class;

    protected static ?string $recordTitleAttribute = 'observaciones';

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Solicitudes';

    protected static ?string $modelLabel = 'solicitud';

    protected static ?string $pluralModelLabel = 'solicitudes';

    protected static ?string $navigationGroup = 'Gestión de inventario';

    protected static ?int $navigationSort = 2;

    protected static function esPanelServicioSocial(): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['usuario', 'receptor.area.departamento', 'autorizadoPor']);
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['observaciones'];
    }

    public static function form(Form $form): Form
    {
        return SolicitudFormSchema::getSchema($form);
    }

    public static function table(Table $table): Table
    {
        return SolicitudTableSchema::getTable($table, ! static::esPanelServicioSocial());
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return SolicitudInfolistSchema::getInfolist($infolist);
    }

    public static function puedeDescargarActaEntrega(?Solicitud $record): bool
    {
        return SolicitudAcciones::puedeDescargarActaEntrega($record);
    }

    /**
     * Construye las acciones de descarga de Acta de Entrega (Formato 1) para fila o slide-over.
     *
     * @return array<int, \Filament\Tables\Actions\Action>
     */
    public static function getAccionesPdfSolicitud(bool $enSlideOver = false): array
    {
        return SolicitudAcciones::getAccionesPdfSolicitud($enSlideOver);
    }

    public static function getRelations(): array
    {
        return [];
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
