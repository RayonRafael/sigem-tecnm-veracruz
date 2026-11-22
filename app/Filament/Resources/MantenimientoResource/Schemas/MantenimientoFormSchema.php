<?php

namespace App\Filament\Resources\MantenimientoResource\Schemas;

use App\Models\Inventario;
use App\Models\Mantenimiento;
use Filament\Forms;
use Filament\Forms\Form;

class MantenimientoFormSchema
{
    public static function getSchema(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('🔧 Datos del mantenimiento')
                    ->extraAttributes(['class' => 'form-card'])
                    ->schema([
                        Forms\Components\Select::make('id_inventario')
                            ->label('¿Qué equipo necesita servicio?')
                            ->placeholder('Selecciona...')
                            ->options(function (): array {
                                return Inventario::with('material')
                                    ->get()
                                    ->mapWithKeys(function ($inv) {
                                        $code = $inv->codigo ?: $inv->num_serie ?: $inv->numero_serie ?: 'S/N';
                                        $name = $inv->material?->nombre ?: $inv->subtipo ?: 'Equipo';

                                        return [$inv->id_inventario => "{$code} — {$name}"];
                                    })
                                    ->toArray();
                            })
                            ->searchable()
                            ->preload()
                            ->required()
                            ->columnSpanFull(),

                        Forms\Components\ViewField::make('tipo_mantenimiento')
                            ->label('Tipo de servicio')
                            ->view('filament.forms.components.tipo-mantenimiento-cards')
                            ->default('Correctivo')
                            ->required()
                            ->columnSpanFull(),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('nombre_tecnico')
                                    ->label('Nombre del técnico')
                                    ->placeholder('Ej. Juan Pérez')
                                    ->default(fn () => auth()->user() ? trim(auth()->user()->name . ' ' . (auth()->user()->apellido_paterno ?? '')) : '')
                                    ->maxLength(150)
                                    ->required(),

                                Forms\Components\TextInput::make('num_control_tecnico')
                                    ->label('No. de control del técnico')
                                    ->placeholder('Ej. 20240001')
                                    ->default(fn () => auth()->user()?->num_control ?? '')
                                    ->maxLength(10)
                                    ->required(),
                            ]),

                        Forms\Components\Textarea::make('descripcion_falla')
                            ->label('Describe la falla')
                            ->placeholder('Ej. El equipo no enciende. Se escucha un ruido extraño al arrancar...')
                            ->helperText('Entre más detalle des, más rápido se autoriza el servicio.')
                            ->required()
                            ->rows(3)
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('descripcion_trabajo')
                            ->label('Trabajo realizado / Hallazgo')
                            ->placeholder('Ej. Se reinstaló el sistema operativo y se aplicó limpieza interna...')
                            ->helperText('Se registra como hallazgo en la Lista de Verificación de Mantenimiento.')
                            ->rows(3)
                            ->visible(fn (?Mantenimiento $record): bool => $record !== null)
                            ->columnSpanFull(),

                        Forms\Components\Hidden::make('id_usuario_solicita')
                            ->default(fn () => auth()->id()),

                        Forms\Components\Hidden::make('tipo_servicio')
                            ->default('Servicio Social'),

                        Forms\Components\Hidden::make('estado')
                            ->default('Pendiente Revision Admin'),

                        Forms\Components\Hidden::make('fecha_solicitud')
                            ->default(now()),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public static function getCompletarFormSchema(): array
    {
        return [
            Forms\Components\Textarea::make('descripcion_trabajo')
                ->label('Trabajo realizado / Hallazgo')
                ->placeholder('Describe las acciones efectuadas o el hallazgo encontrado (opcional)...')
                ->default(fn (?Mantenimiento $record): ?string => $record?->descripcion_trabajo)
                ->rows(3),
        ];
    }
}
