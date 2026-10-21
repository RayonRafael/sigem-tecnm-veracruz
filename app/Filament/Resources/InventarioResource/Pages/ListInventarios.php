<?php

namespace App\Filament\Resources\InventarioResource\Pages;

use App\Filament\Concerns\ConDashboardBreadcrumb;
use App\Filament\Resources\InventarioResource;
use App\Models\Inventario;
use Filament\Actions;
use Filament\Infolists\Concerns\InteractsWithInfolists;
use Filament\Infolists\Contracts\HasInfolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Table;

class ListInventarios extends ListRecords implements HasInfolists
{
    use ConDashboardBreadcrumb;
    use InteractsWithInfolists;

    protected static string $resource = InventarioResource::class;

    protected static string $view = 'filament.resources.inventario.pages.list-inventarios-split';

    public ?Inventario $activeRecord = null;

    public function getHeading(): string
    {
        return 'Registros de Inventario';
    }

    public function getSubheading(): ?string
    {
        return 'Busca, filtra y gestiona los equipos registrados en el sistema.';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('toggleGrafica')
                ->label('📊 Gráfica')
                ->color('gray')
                ->extraAttributes([
                    'class' => 'sigem-rep-btn-sec',
                    'style' => 'cursor: pointer;',
                    'title' => 'Alternar panel de resumen del inventario',
                ])
                ->alpineClickHandler("window.dispatchEvent(new CustomEvent('toggle-inventario-grafica'))"),
            Actions\CreateAction::make()
                ->label('＋ Registrar equipo')
                ->extraAttributes([
                    'class' => 'sigem-btn-primary',
                    'title' => 'Registrar un nuevo equipo en el inventario',
                ]),
        ];
    }

    public function table(Table $table): Table
    {
        return parent::table($table)
            ->recordAction('ver_detalle')
            ->actions([
                Action::make('aprobar')
                    ->label('Aprobar')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (Inventario $record): bool => ! $record->aprobado && auth()->user()->can('aprobaciones.aprobar'))
                    ->action(function (Inventario $record) {
                        $record->update(['aprobado' => true, 'estado_registro' => 'Aprobado', 'aprobado_por' => auth()->id(), 'fecha_aprobacion' => now()]);
                        Notification::make()->title('Registro aprobado exitosamente')->success()->send();
                    })
                    ->requiresConfirmation(),
                Action::make('rechazar')
                    ->label('Rechazar')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (Inventario $record): bool => ! $record->aprobado && auth()->user()->can('aprobaciones.rechazar'))
                    ->action(function (Inventario $record) {
                        $record->update([
                            'estado_registro' => 'Rechazado',
                            'aprobado' => false,
                        ]);
                        Notification::make()->title('Registro rechazado exitosamente')->success()->send();
                    })
                    ->requiresConfirmation(),
                Action::make('ver_detalle')
                    ->label('Detalle')
                    ->icon('heroicon-m-eye')
                    ->iconButton()
                    ->tooltip('Ver detalle en panel lateral')
                    ->action(fn (Inventario $record) => $this->activeRecord = $record),
                EditAction::make()->iconButton()->slideOver()->tooltip('Editar')->successNotificationTitle('Registro de inventario actualizado correctamente'),
            ]);
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return InventarioResource::infolist($infolist)
            ->record($this->activeRecord);
    }

    public function filtrarPorEstado(?string $estado = null): void
    {
        $actual = $this->tableFilters['estado']['value'] ?? null;
        if ($actual === $estado || empty($estado)) {
            $this->tableFilters['estado']['value'] = null;
        } else {
            $this->tableFilters['estado']['value'] = $estado;
        }
    }

    public function filtrarPorCategoria(?string $categoria = null): void
    {
        if ($this->tableSearch === $categoria || empty($categoria)) {
            $this->tableSearch = '';
        } else {
            $this->tableSearch = $categoria;
        }
    }
}
