<?php

namespace App\Filament\Schemas;

use App\Models\Area;
use App\Models\Departamento;
use App\Models\MarcaMaterial;
use App\Models\Material;
use App\Models\TipoMaterial;
use App\Models\UnidadMedida;
use App\Support\SigemUniqueValidator;
use App\Support\SubformSlideOverHelper;
use Filament\Forms;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Form;

class CatalogoBaseFormSchema
{
    /**
     * @param  array<int, \Filament\Forms\Components\Component>  $schema
     */
    public static function build(Form $form, array $schema): Form
    {
        return $form->schema($schema);
    }

    /**
     * Regla genérica de unicidad para catálogos (case-insensitive, incluye soft-deleted,
     * ignora el propio registro al editar).
     *
     * @param  class-string<\Illuminate\Database\Eloquent\Model>  $modelClass
     */
    public static function reglaNombreUnico(
        string $modelClass,
        string $etiquetaArticulo,
        string $column = 'nombre',
        bool $ignorarRecord = true,
    ): \Closure {
        return SigemUniqueValidator::reglaCatalogoUnico($modelClass, $etiquetaArticulo, $column, $ignorarRecord);
    }

    /**
     * @param  array<int, \Filament\Forms\Components\Component>  $fields
     */
    public static function section(string $heading, string $icon, array $fields, ?int $columns = null): Forms\Components\Section
    {
        $section = Forms\Components\Section::make($heading)
            ->icon($icon)
            ->schema($fields);

        if ($columns !== null) {
            $section->columns($columns);
        }

        return $section;
    }

    /**
     * @param  ?class-string<\Illuminate\Database\Eloquent\Model>  $uniqueModel
     */
    public static function campoNombre(
        string $label,
        string $placeholder,
        string $icon = 'heroicon-m-tag',
        int $maxLength = 100,
        ?string $uniqueModel = null,
        ?string $uniqueLabel = null,
    ): Forms\Components\TextInput {
        $field = Forms\Components\TextInput::make('nombre')
            ->label($label)
            ->placeholder($placeholder)
            ->required()
            ->prefixIcon($icon)
            ->maxLength($maxLength);

        if ($uniqueModel !== null && $uniqueLabel !== null) {
            $field->rule(static::reglaNombreUnico($uniqueModel, $uniqueLabel));
        }

        return $field;
    }

    public static function selectRelacionConCreacion(
        string $name,
        string $label,
        string $relationship,
        string $icon,
        string $modalHeading,
        string $createFieldLabel,
        string $createFieldPlaceholder,
        string $tip,
        string $successMessage,
        bool $columnSpanFull = false,
    ): Forms\Components\Select {
        $createNombreField = Forms\Components\TextInput::make('nombre')
            ->label($createFieldLabel)
            ->placeholder($createFieldPlaceholder)
            ->required()
            ->maxLength(100);

        $relacionUniqueMap = [
            'departamento' => [Departamento::class, 'un departamento'],
            'area' => [Area::class, 'un área'],
            'material' => [Material::class, 'un material'],
            'marca' => [MarcaMaterial::class, 'una marca'],
            'tipo' => [TipoMaterial::class, 'un tipo de material'],
            'unidad' => [UnidadMedida::class, 'una unidad de medida'],
        ];

        if (isset($relacionUniqueMap[$relationship])) {
            [$relModel, $relLabel] = $relacionUniqueMap[$relationship];
            $createNombreField->rule(static::reglaNombreUnico($relModel, $relLabel, 'nombre', false));
        }

        $select = Forms\Components\Select::make($name)
            ->placeholder('Selecciona...')
            ->label($label)
            ->relationship($relationship, 'nombre')
            ->required()
            ->searchable()
            ->preload()
            ->prefixIcon($icon)
            ->createOptionForm([
                SubformSlideOverHelper::headerNav(),
                $createNombreField,
                SubformSlideOverHelper::catalogTip($tip),
            ])
            ->createOptionAction(fn (Action $action) => SubformSlideOverHelper::configureAction(
                $action,
                $modalHeading,
                '+ Agregar',
                $successMessage,
            ));

        if ($columnSpanFull) {
            $select->columnSpanFull();
        }

        return $select;
    }
}
