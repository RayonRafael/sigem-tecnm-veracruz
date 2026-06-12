<?php

namespace App\Filament\Widgets;

use App\Models\Area;
use App\Models\Material;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Widgets\Widget;

class RegistroRapidoWidget extends Widget implements HasForms
{
    use InteractsWithForms;

    protected static ?int $sort = 2;
    protected int | string | array $columnSpan = 4;
    protected static string $view = 'filament.widgets.registro-rapido-widget';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    protected function getFormSchema(): array
    {
        return [
            Forms\Components\Select::make('tipo')
                ->label('Tipo')
                ->options([
                    'Equipo de cómputo' => 'Equipo de cómputo',
                    'Mobiliario' => 'Mobiliario',
                    'Material de oficina' => 'Material de oficina',
                    'Otro' => 'Otro',
                ])
                ->required(),
            Forms\Components\Select::make('id_producto')
                ->label('Material')
                ->options(Material::pluck('nombre', 'id_producto'))
                ->searchable()
                ->required(),
            Forms\Components\TextInput::make('num_serie')
                ->label('No. Serie')
                ->required(),
            Forms\Components\Select::make('id_area')
                ->label('Área')
                ->options(Area::pluck('nombre', 'id_area'))
                ->searchable()
                ->required(),
        ];
    }

    public function form(Forms\Form $form): Forms\Form
    {
        return $form
            ->schema($this->getFormSchema())
            ->statePath('data');
    }

    public function crear()
    {
        $data = $this->form->getState();
        
        session()->flash('success', 'Registro rápido completado. Redirigiendo...');
        
        return redirect()->route('filament.admin.resources.inventarios.create', [
            'tipo' => $data['tipo'],
            'id_producto' => $data['id_producto'],
            'num_serie' => $data['num_serie'],
            'id_area' => $data['id_area'],
        ]);
    }
}