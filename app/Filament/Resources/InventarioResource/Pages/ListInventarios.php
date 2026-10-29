<?php

namespace App\Filament\Resources\InventarioResource\Pages;

use App\Filament\Concerns\ConDashboardBreadcrumb;
use App\Filament\Concerns\ConSlideOver;
use App\Filament\Resources\InventarioResource;
use App\Models\Inventario;
use Filament\Actions;
use Filament\Infolists\Concerns\InteractsWithInfolists;
use Filament\Infolists\Contracts\HasInfolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ListRecords;

class ListInventarios extends ListRecords implements HasInfolists
{
    use ConDashboardBreadcrumb;
    use ConSlideOver;
    use InteractsWithInfolists;

    protected static string $resource = InventarioResource::class;

    protected static string $view = 'filament.resources.inventario.pages.list-inventarios-split';

    public ?Inventario $activeRecord = null;

    public function getTitle(): string
    {
        return '💻 Inventario general';
    }

    public function getSubheading(): ?string
    {
        return 'Busca, filtra y gestiona los equipos registrados en el sistema.';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('toggleGrafica')
                ->label("\u{1F4CA} Gráfica")
                ->color('gray')
                ->extraAttributes([
                    'class' => 'sigem-rep-btn-sec',
                    'style' => 'cursor: pointer;',
                    'title' => 'Alternar panel de resumen del inventario',
                ])
                ->alpineClickHandler("window.dispatchEvent(new CustomEvent('toggle-inventario-grafica'))"),
            Actions\CreateAction::make()
                ->label("\u{FF0B} Registrar equipo")
                ->slideOver()
                ->steps(InventarioResource::getFormSteps())
                ->extraAttributes([
                    'class' => 'sigem-btn-primary',
                    'title' => 'Registrar un nuevo equipo en el inventario',
                ])
                ->successNotificationTitle('Registro creado exitosamente'),
        ];
    }

    protected function configureCreateAction(Actions\CreateAction | \Filament\Tables\Actions\CreateAction $action): void
    {
        parent::configureCreateAction($action);

        $action->url(null);
        $action->slideOver();
        $action->steps(InventarioResource::getFormSteps());
    }

    public function infolist(Infolist $infolist): Infolist
    {
        $infolist = InventarioResource::infolist($infolist);

        if (! $infolist->getRecord() && $this->activeRecord) {
            $infolist->record($this->activeRecord);
        }

        return $infolist;
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
