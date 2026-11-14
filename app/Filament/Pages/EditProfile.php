<?php

namespace App\Filament\Pages;

use Filament\Actions\Action;
use Filament\Forms\Components\Component;
use Filament\Pages\Auth\EditProfile as BaseEditProfile;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Js;

class EditProfile extends BaseEditProfile
{
    protected static string $view = 'filament.pages.edit-profile';

    public static function getLabel(): string
    {
        return 'Mi Perfil';
    }

    public function getTitle(): string | Htmlable
    {
        return 'Mi Perfil';
    }

    public function getHeading(): string | Htmlable
    {
        return 'Mi Perfil';
    }

    public function getSubheading(): string | Htmlable | null
    {
        return 'Actualiza tu información personal y tus credenciales de acceso al sistema.';
    }

    protected function getForms(): array
    {
        return [
            'form' => $this->form(
                $this->makeForm()
                    ->schema([
                        $this->getNameFormComponent(),
                        $this->getEmailFormComponent(),
                        $this->getPasswordFormComponent(),
                        $this->getPasswordConfirmationFormComponent(),
                    ])
                    ->operation('edit')
                    ->model($this->getUser())
                    ->statePath('data')
                    ->inlineLabel(false),
            ),
        ];
    }

    protected function getNameFormComponent(): Component
    {
        return parent::getNameFormComponent()
            ->placeholder('Nombre completo');
    }

    protected function getEmailFormComponent(): Component
    {
        return parent::getEmailFormComponent()
            ->placeholder('usuario@veracruz.tecnm.mx');
    }

    protected function getPasswordFormComponent(): Component
    {
        return parent::getPasswordFormComponent()
            ->placeholder('••••••••')
            ->helperText('Deja este campo vacío si deseas conservar tu contraseña actual.');
    }

    protected function getPasswordConfirmationFormComponent(): Component
    {
        return parent::getPasswordConfirmationFormComponent()
            ->placeholder('••••••••');
    }

    public function backAction(): Action
    {
        $panelUrl = filament()->getUrl();

        return Action::make('back')
            ->label(__('filament-panels::pages/auth/edit-profile.actions.cancel.label'))
            ->alpineClickHandler('document.referrer && !document.referrer.includes(\'/profile\') ? window.history.back() : (window.location.href = ' . Js::from($panelUrl) . ')')
            ->color('gray');
    }
}

