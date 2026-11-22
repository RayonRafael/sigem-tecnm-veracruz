<?php

namespace App\Filament\Resources\SolicitudResource\Schemas;

use App\Models\Material;
use App\Models\MarcaMaterial;
use App\Models\Receptor;
use App\Models\TipoMaterial;
use App\Models\UnidadMedida;
use App\Support\SubformSlideOverHelper;
use Filament\Forms;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Form;
use Illuminate\Support\HtmlString;

class SolicitudFormSchema
{
    public static function getSchema(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('📝 Datos de la solicitud')
                    ->extraAttributes(['class' => 'form-card'])
                    ->schema([
                        Forms\Components\ViewField::make('tipo_solicitud')
                            ->label('¿Qué tipo de solicitud?')
                            ->view('filament.forms.components.tipo-solicitud-cards')
                            ->default('Salida')
                            ->required()
                            ->columnSpanFull(),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\DatePicker::make('fecha_solicitud')
                                    ->label('Fecha')
                                    ->default(now())
                                    ->required()
                                    ->helperText('Puedes programar la solicitud para otro día.')
                                    ->prefixIcon('heroicon-m-calendar'),

                                Forms\Components\Select::make('id_producto')
                                    ->label('Material')
                                    ->placeholder('Selecciona...')
                                    ->options(fn (): array => Material::pluck('nombre', 'id_producto')->toArray())
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->createOptionForm([
                                        SubformSlideOverHelper::headerNav(),
                                        Forms\Components\TextInput::make('nombre')
                                            ->label('Nombre del material')
                                            ->placeholder('Ej. Bobina cable UTP, Pasta térmica...')
                                            ->required()
                                            ->maxLength(100),
                                        SubformSlideOverHelper::catalogTip('El material estará disponible inmediatamente en el catálogo.'),
                                    ])
                                    ->createOptionUsing(function (array $data): int {
                                        $tipoModel = TipoMaterial::withTrashed()->first()
                                            ?? TipoMaterial::create(['nombre' => 'Equipo de Cómputo']);
                                        if ($tipoModel->trashed()) {
                                            $tipoModel->restore();
                                        }

                                        $unidadModel = UnidadMedida::withTrashed()->first()
                                            ?? UnidadMedida::create(['nombre' => 'Pieza']);
                                        if ($unidadModel->trashed()) {
                                            $unidadModel->restore();
                                        }

                                        $marcaModel = MarcaMaterial::withTrashed()->first()
                                            ?? MarcaMaterial::create(['nombre' => 'Genérico']);
                                        if ($marcaModel->trashed()) {
                                            $marcaModel->restore();
                                        }

                                        $mat = Material::create([
                                            'nombre' => trim($data['nombre']),
                                            'id_marca' => $marcaModel->id_marca,
                                            'id_tipodematerial' => $tipoModel->id_tipodematerial,
                                            'id_unidad' => $unidadModel->id_unidad,
                                            'stock_minimo' => 0,
                                            'stock_maximo' => 100,
                                        ]);

                                        return $mat->id_producto;
                                    })
                                    ->createOptionAction(function (Action $action) {
                                        return SubformSlideOverHelper::configureAction(
                                            $action,
                                            '✏️ Agregar material como nueva opción',
                                            '+ Agregar',
                                            'Material creado correctamente'
                                        );
                                    }),
                            ]),

                        Forms\Components\Textarea::make('observaciones')
                            ->label('Observaciones')
                            ->placeholder('Describe brevemente para qué necesitas el material...')
                            ->helperText('Esto ayuda al administrador a decidir más rápido.')
                            ->rows(3)
                            ->columnSpanFull(),

                        Forms\Components\Placeholder::make('solicitante_info')
                            ->label('')
                            ->content(new HtmlString(
                                '<div style="display:flex;align-items:center;gap:8px;padding:10px 14px;background:#f0f9ff;border:1px solid #bae6fd;border-radius:8px;font-size:13px;color:#0369a1;">'
                                . '<span>ℹ️</span> <span>Tu registro se enviará al administrador para su revisión</span>'
                                . '</div>'
                            ))
                            ->columnSpanFull(),

                        Forms\Components\Hidden::make('id_usuario')
                            ->default(fn () => auth()->id()),

                        Forms\Components\Hidden::make('estado')
                            ->default('Pendiente'),

                        Forms\Components\Hidden::make('id_receptor')
                            ->default(fn () => Receptor::first()?->id_receptor),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
