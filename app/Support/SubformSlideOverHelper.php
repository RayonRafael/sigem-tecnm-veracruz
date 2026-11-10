<?php

namespace App\Support;

use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Placeholder;
use Filament\Notifications\Notification;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Support\HtmlString;

class SubformSlideOverHelper
{
    /**
     * Header de navegación dentro del sub-formulario slide-over con botón "← Volver".
     */
    public static function headerNav(): Placeholder
    {
        return Placeholder::make('subform_header_nav')
            ->hiddenLabel()
            ->content(new HtmlString(
                '<div class="sigem-subform-header flex items-center justify-between pb-3 mb-2 border-b border-gray-100 dark:border-gray-800">' .
                    '<button type="button" ' .
                        'x-on:click="$dispatch(\'close-modal\', { id: $el.closest(\'.fi-modal\')?.id }) || $el.closest(\'.fi-modal\')?.querySelector(\'.fi-modal-close-btn\')?.click()" ' .
                        'class="sigem-subform-back-btn inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg text-primary-600 bg-primary-50 hover:bg-primary-100 dark:text-primary-400 dark:bg-primary-950/50 dark:hover:bg-primary-900/50 transition cursor-pointer border border-primary-200/50">' .
                        '<span aria-hidden="true">&larr;</span>' .
                        '<span>Volver al formulario</span>' .
                    '</button>' .
                '</div>'
            ))
            ->columnSpanFull();
    }

    /**
     * Tarjeta de tip informativa: "💡 {Mensaje}"
     */
    public static function catalogTip(string $mensaje): Placeholder
    {
        return Placeholder::make('subform_catalog_tip')
            ->hiddenLabel()
            ->content(new HtmlString(
                '<div class="sigem-subform-tip flex items-start gap-2.5 p-3 rounded-lg bg-amber-50/80 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800/50 text-amber-900 dark:text-amber-200 text-xs my-2">' .
                    '<span class="text-base select-none leading-none">💡</span>' .
                    '<div class="leading-relaxed">' .
                        $mensaje .
                    '</div>' .
                '</div>'
            ))
            ->columnSpanFull();
    }

    /**
     * Configura la acción para abrirse como slide-over con estilos uniformes SIGEM.
     */
    public static function configureAction(
        Action $action,
        string $heading,
        string $btnLabel = '+ Agregar',
        ?string $successMessage = null
    ): Action {
        $action
            ->slideOver()
            ->modalHeading($heading)
            ->modalSubmitActionLabel($btnLabel)
            ->modalCancelActionLabel('Cancelar')
            ->modalWidth(MaxWidth::TwoExtraLarge);

        if ($successMessage) {
            $action->successNotification(
                Notification::make()
                    ->success()
                    ->title($successMessage)
            );
        }

        return $action;
    }
}

