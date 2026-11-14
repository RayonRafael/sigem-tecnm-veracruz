<?php

namespace Tests\Feature\Auth;

use App\Filament\Pages\EditProfile;
use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\ServicioSocialUserSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(AdminUserSeeder::class);
        $this->seed(ServicioSocialUserSeeder::class);
    }

    public function test_admin_can_render_profile_page_with_sigem_design(): void
    {
        $admin = User::where('email', 'admin@tecnm.edu.mx')->firstOrFail();

        $this->actingAs($admin)
            ->get('/admin/profile')
            ->assertOk()
            ->assertSee('Mi Perfil')
            ->assertSee('sigem-logo.svg')
            ->assertSee('Panel de Administración');
    }

    public function test_servicio_social_can_render_profile_page_with_sigem_design(): void
    {
        $ss = User::where('email', 'servicio@tecnm.edu.mx')->firstOrFail();

        $this->actingAs($ss)
            ->get('/servicio-social/profile')
            ->assertOk()
            ->assertSee('Mi Perfil')
            ->assertSee('sigem-logo.svg')
            ->assertSee('Panel de Servicio Social');
    }

    public function test_user_can_update_name_email_and_password(): void
    {
        $admin = User::where('email', 'admin@tecnm.edu.mx')->firstOrFail();
        $this->actingAs($admin);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        Livewire::test(EditProfile::class)
            ->fillForm([
                'name' => 'Administrador Actualizado',
                'email' => 'admin.nuevo@veracruz.tecnm.mx',
                'password' => 'NuevaClaveSegura2026!',
                'passwordConfirmation' => 'NuevaClaveSegura2026!',
            ])
            ->call('save')
            ->assertHasNoFormErrors()
            ->assertNotified();

        $admin->refresh();
        $this->assertSame('Administrador Actualizado', $admin->name);
        $this->assertSame('admin.nuevo@veracruz.tecnm.mx', $admin->email);
        $this->assertTrue(Hash::check('NuevaClaveSegura2026!', $admin->password));
    }
}

