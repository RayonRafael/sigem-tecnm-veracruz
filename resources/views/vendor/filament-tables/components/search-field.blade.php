@php
    use Illuminate\View\ComponentAttributeBag;
    use App\Support\IndividualSearchHelper;
@endphp

@props([
    'debounce' => '500ms',
    'onBlur' => false,
    'placeholder' => __('filament-tables::table.fields.search.placeholder'),
    'wireModel' => 'tableSearch',
])

@php
    $wireModelAttribute = $onBlur ? 'wire:model.blur' : "wire:model.live.debounce.{$debounce}";
    $isIndividual = str_starts_with($wireModel, 'tableColumnSearches.');
    $columnName = $isIndividual ? str_replace('tableColumnSearches.', '', $wireModel) : null;
    $model = null;
    if (method_exists($this, 'getModel')) {
        $model = $this->getModel();
    } elseif (method_exists($this, 'getTable')) {
        $model = $this->getTable()->getModel();
    }

    $options = $isIndividual ? IndividualSearchHelper::getOptionsForColumn($columnName, $model) : null;

    if ($isIndividual && ! $options) {
        $placeholder = IndividualSearchHelper::getPlaceholderForColumn($columnName, $model);
    }

    $hasDateFilter = (! $isIndividual) && (
        in_array($model, [
            \App\Models\Inventario::class,
            'App\\Models\\Inventario',
            \App\Models\Solicitud::class,
            'App\\Models\\Solicitud',
            \App\Models\Mantenimiento::class,
            'App\\Models\\Mantenimiento',
        ]) || (is_string($model) && (str_ends_with($model, 'Inventario') || str_ends_with($model, 'Solicitud') || str_ends_with($model, 'Mantenimiento')))
    );
@endphp

@if ($isIndividual && is_array($options))
    <div
        x-id="['select']"
        {{ $attributes->class(['fi-ta-search-field', 'fi-table-individual-search-select-wrp']) }}
    >
        <label x-bind:for="$id('select')" class="sr-only">
            {{ $columnName }}
        </label>

        <select
            x-bind:id="$id('select')"
            wire:model.live="{{ $wireModel }}"
            wire:key="{{ $this->getId() . '.table.' . $wireModel . '.field.select' }}"
            class="fi-table-individual-search-select"
        >
            @foreach ($options as $optValue => $optLabel)
                <option value="{{ $optValue }}">{{ $optLabel }}</option>
            @endforeach
        </select>
    </div>
@elseif ($isIndividual)
    <div
        x-id="['input']"
        {{ $attributes->class(['fi-ta-search-field']) }}
    >
        <label x-bind:for="$id('input')" class="sr-only">
            {{ __('filament-tables::table.fields.search.label') }}
        </label>

        <x-filament::input.wrapper
            inline-prefix
            prefix-icon="heroicon-m-magnifying-glass"
            prefix-icon-alias="tables::search-field"
            :wire:target="$wireModel"
        >
            <x-filament::input
                :attributes="
                    (new ComponentAttributeBag)->merge([
                        'autocomplete' => 'off',
                        'inlinePrefix' => true,
                        'maxlength' => 1000,
                        'placeholder' => $placeholder,
                        'type' => 'search',
                        'wire:key' => $this->getId() . '.table.' . $wireModel . '.field.input',
                        $wireModelAttribute => $wireModel,
                        'x-bind:id' => '$id(\'input\')',
                        'x-on:keyup' => 'if ($event.key === \'Enter\') { $wire.$refresh() }',
                    ])
                "
            />
        </x-filament::input.wrapper>
    </div>
@else
    {{-- TOOLBAR PRINCIPAL: [Buscador] + [Desde 📅] + [Hasta 📅] + [✕ Quitar filtros] --}}
    <div class="sigem-table-toolbar-group">
        <div
            x-id="['input']"
            {{ $attributes->class(['fi-ta-search-field', 'sigem-toolbar-search']) }}
        >
            <label x-bind:for="$id('input')" class="sr-only">
                {{ __('filament-tables::table.fields.search.label') }}
            </label>

            <x-filament::input.wrapper
                inline-prefix
                prefix-icon="heroicon-m-magnifying-glass"
                prefix-icon-alias="tables::search-field"
                :wire:target="$wireModel"
            >
                <x-filament::input
                    :attributes="
                        (new ComponentAttributeBag)->merge([
                            'autocomplete' => 'off',
                            'inlinePrefix' => true,
                            'maxlength' => 1000,
                            'placeholder' => $placeholder,
                            'type' => 'search',
                            'wire:key' => $this->getId() . '.table.' . $wireModel . '.field.input',
                            $wireModelAttribute => $wireModel,
                            'x-bind:id' => '$id(\'input\')',
                            'x-on:keyup' => 'if ($event.key === \'Enter\') { $wire.$refresh() }',
                        ])
                    "
                />
            </x-filament::input.wrapper>
        </div>

        @if ($hasDateFilter)
            <div class="sigem-toolbar-date">
                <span class="sigem-toolbar-date-label">Desde</span>
                <input
                    type="date"
                    wire:model.live="tableColumnSearches.fecha_desde"
                    wire:key="{{ $this->getId() . '.table.fecha_desde' }}"
                    class="sigem-toolbar-date-input"
                    title="Filtrar desde fecha"
                />
            </div>

            <div class="sigem-toolbar-date">
                <span class="sigem-toolbar-date-label">Hasta</span>
                <input
                    type="date"
                    wire:model.live="tableColumnSearches.fecha_hasta"
                    wire:key="{{ $this->getId() . '.table.fecha_hasta' }}"
                    class="sigem-toolbar-date-input"
                    title="Filtrar hasta fecha"
                />
            </div>
        @endif

        <button
            type="button"
            x-data
            x-show="Boolean($wire.tableSearch) || Object.values($wire.tableColumnSearches || {}).some(v => Boolean(v)) || Boolean($wire.get('tableFilters.estado.value'))"
            x-cloak
            x-on:click="$wire.set('tableSearch', ''); if ($wire.tableFilters && $wire.tableFilters.estado) { $wire.set('tableFilters.estado.value', null); } $wire.resetTableColumnSearches();"
            class="sigem-toolbar-reset-btn"
            title="Quitar todos los filtros"
        >
            ✕ Quitar filtros
        </button>
    </div>
@endif
