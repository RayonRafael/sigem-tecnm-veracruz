<?php

namespace App\Filament\Schemas;

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

    public static function campoNombre(
        string $label,
        string $placeholder,
        string $icon = 'heroicon-m-tag',
        int $maxLength = 100,
    ): Forms\Components\TextInput {
        return Forms\Components\TextInput::make('nombre')
            ->label($label)
            ->placeholder($placeholder)
            ->required()
            ->prefixIcon($icon)
            ->maxLength($maxLength);
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
                Forms\Components\TextInput::make('nombre')
                    ->label($createFieldLabel)
                    ->placeholder($createFieldPlaceholder)
                    ->required()
                    ->maxLength(100),
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
