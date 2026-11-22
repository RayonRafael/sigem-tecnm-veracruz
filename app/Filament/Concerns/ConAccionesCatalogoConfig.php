<?php

namespace App\Filament\Concerns;

use App\Filament\Resources\AreaResource;
use App\Filament\Resources\DepartamentoResource;
use App\Filament\Resources\MarcaMaterialResource;
use App\Filament\Resources\MaterialResource;
use App\Filament\Resources\ProveedorResource;
use App\Filament\Resources\ReceptorResource;
use App\Filament\Resources\TipoMaterialResource;
use App\Filament\Resources\UnidadMedidaResource;
use App\Filament\Resources\UserResource;
use App\Models\Proveedor;
use App\Models\Receptor;
use App\Models\User;
use Filament\Actions;
use Filament\Forms\Form;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;

trait ConAccionesCatalogoConfig
{
    public function crearDepartamentoAction(): Actions\CreateAction { return $this->accionCrear('crearDepartamento', DepartamentoResource::class, 'Departamento creado', 'departamento'); }
    public function editarDepartamentoAction(): Actions\EditAction { return $this->accionEditar('editarDepartamento', DepartamentoResource::class, 'Departamento actualizado', 'departamento'); }
    public function verDepartamentoAction(): Actions\ViewAction { return $this->accionVer('verDepartamento', DepartamentoResource::class, 'departamento'); }
    public function eliminarDepartamentoAction(): Actions\DeleteAction { return $this->accionEliminar('eliminarDepartamento', DepartamentoResource::class, 'Departamento eliminado', 'departamento'); }

    public function crearAreaAction(): Actions\CreateAction { return $this->accionCrear('crearArea', AreaResource::class, 'Área creada', 'área'); }
    public function editarAreaAction(): Actions\EditAction { return $this->accionEditar('editarArea', AreaResource::class, 'Área actualizada', 'área'); }
    public function verAreaAction(): Actions\ViewAction { return $this->accionVer('verArea', AreaResource::class, 'área'); }
    public function eliminarAreaAction(): Actions\DeleteAction { return $this->accionEliminar('eliminarArea', AreaResource::class, 'Área eliminada', 'área'); }

    public function crearMaterialAction(): Actions\CreateAction { return $this->accionCrear('crearMaterial', MaterialResource::class, 'Material creado', 'material'); }
    public function editarMaterialAction(): Actions\EditAction { return $this->accionEditar('editarMaterial', MaterialResource::class, 'Material actualizado', 'material'); }
    public function verMaterialAction(): Actions\ViewAction { return $this->accionVer('verMaterial', MaterialResource::class, 'material'); }
    public function eliminarMaterialAction(): Actions\DeleteAction { return $this->accionEliminar('eliminarMaterial', MaterialResource::class, 'Material eliminado', 'material'); }

    public function crearMarcaAction(): Actions\CreateAction { return $this->accionCrear('crearMarca', MarcaMaterialResource::class, 'Marca creada', 'marca'); }
    public function editarMarcaAction(): Actions\EditAction { return $this->accionEditar('editarMarca', MarcaMaterialResource::class, 'Marca actualizada', 'marca'); }
    public function verMarcaAction(): Actions\ViewAction { return $this->accionVer('verMarca', MarcaMaterialResource::class, 'marca'); }
    public function eliminarMarcaAction(): Actions\DeleteAction { return $this->accionEliminar('eliminarMarca', MarcaMaterialResource::class, 'Marca eliminada', 'marca'); }

    public function crearTipoAction(): Actions\CreateAction { return $this->accionCrear('crearTipo', TipoMaterialResource::class, 'Tipo de material creado', 'tipo de material'); }
    public function editarTipoAction(): Actions\EditAction { return $this->accionEditar('editarTipo', TipoMaterialResource::class, 'Tipo de material actualizado', 'tipo de material'); }
    public function verTipoAction(): Actions\ViewAction { return $this->accionVer('verTipo', TipoMaterialResource::class, 'tipo de material'); }
    public function eliminarTipoAction(): Actions\DeleteAction { return $this->accionEliminar('eliminarTipo', TipoMaterialResource::class, 'Tipo de material eliminado', 'tipo de material'); }

    public function crearUnidadAction(): Actions\CreateAction { return $this->accionCrear('crearUnidad', UnidadMedidaResource::class, 'Unidad de medida creada', 'unidad de medida'); }
    public function editarUnidadAction(): Actions\EditAction { return $this->accionEditar('editarUnidad', UnidadMedidaResource::class, 'Unidad de medida actualizada', 'unidad de medida'); }
    public function verUnidadAction(): Actions\ViewAction { return $this->accionVer('verUnidad', UnidadMedidaResource::class, 'unidad de medida'); }
    public function eliminarUnidadAction(): Actions\DeleteAction { return $this->accionEliminar('eliminarUnidad', UnidadMedidaResource::class, 'Unidad de medida eliminada', 'unidad de medida'); }

    public function crearProveedorAction(): Actions\CreateAction { return $this->accionCrear('crearProveedor', ProveedorResource::class, 'Proveedor creado', 'proveedor'); }
    public function editarProveedorAction(): Actions\EditAction { return $this->accionEditar('editarProveedor', ProveedorResource::class, 'Proveedor actualizado', 'proveedor'); }
    public function verProveedorAction(): Actions\ViewAction { return $this->accionVer('verProveedor', ProveedorResource::class, 'proveedor'); }
    public function eliminarProveedorAction(): Actions\DeleteAction { return $this->accionEliminar('eliminarProveedor', ProveedorResource::class, 'Proveedor eliminado', 'proveedor'); }

    public function crearReceptorAction(): Actions\CreateAction { return $this->accionCrear('crearReceptor', ReceptorResource::class, 'Receptor creado', 'receptor'); }
    public function editarReceptorAction(): Actions\EditAction { return $this->accionEditar('editarReceptor', ReceptorResource::class, 'Receptor actualizado', 'receptor'); }
    public function verReceptorAction(): Actions\ViewAction { return $this->accionVer('verReceptor', ReceptorResource::class, 'receptor'); }
    public function eliminarReceptorAction(): Actions\DeleteAction { return $this->accionEliminar('eliminarReceptor', ReceptorResource::class, 'Receptor eliminado', 'receptor'); }

    public function crearUsuarioAction(): Actions\CreateAction { return $this->accionCrear('crearUsuario', UserResource::class, 'Usuario creado exitosamente', 'usuario'); }
    public function editarUsuarioAction(): Actions\EditAction { return $this->accionEditar('editarUsuario', UserResource::class, 'Usuario actualizado exitosamente', 'usuario'); }
    public function verUsuarioAction(): Actions\ViewAction { return $this->accionVer('verUsuario', UserResource::class, 'usuario'); }
    public function eliminarUsuarioAction(): Actions\DeleteAction { return $this->accionEliminar('eliminarUsuario', UserResource::class, 'Usuario eliminado exitosamente', 'usuario'); }

    protected function accionCrear(string $nombre, string $resource, string $mensaje, string $singular): Actions\CreateAction
    {
        return Actions\CreateAction::make($nombre)
            ->model($resource::getModel())
            ->modalHeading(fn () => '+ Nuevo ' . $singular)
            ->modalDescription(fn () => 'Da de alta un registro en el catálogo institucional.')
            ->form(fn (Form $form): Form => $resource::form($form))
            ->mutateFormDataUsing(function (array $data) use ($resource): array {
                if ($resource === UserResource::class && isset($data['roles'])) {
                    $data['tipo_usuario'] = UserResource::mapRoleToTipoUsuario($data['roles']);
                }

                return $data;
            })
            ->slideOver()
            ->createAnother(false)
            ->successNotificationTitle($mensaje)
            ->after(fn () => $this->refreshCatalogoData());
    }

    protected function accionEditar(string $nombre, string $resource, string $mensaje, string $singular): Actions\EditAction
    {
        $model = $resource::getModel();

        return Actions\EditAction::make($nombre)
            ->model($model)
            ->modalHeading(function (?Model $record, array $arguments = []) use ($model) {
                $rec = $record ?? $model::find($arguments['record'] ?? null);
                if ($rec instanceof User) {
                    return 'Editar: ' . ($rec->name ?? 'usuario');
                }
                if ($rec instanceof Receptor) {
                    return 'Editar: ' . (trim(($rec->nombre ?? '') . ' ' . ($rec->apellido_paterno ?? '')) ?: 'receptor');
                }

                return 'Editar: ' . trim($rec?->nombre ?? $rec?->nombre_empresa ?? 'registro');
            })
            ->modalDescription(fn (?Model $record, array $arguments = []) => $this->construirDescripcionModal($record ?? $model::find($arguments['record'] ?? null)))
            ->record(fn (array $arguments) => $model::find($arguments['record'] ?? null))
            ->form(fn (Form $form): Form => $resource::form($form))
            ->mutateFormDataUsing(function (array $data) use ($resource): array {
                if ($resource === UserResource::class && isset($data['roles'])) {
                    $data['tipo_usuario'] = UserResource::mapRoleToTipoUsuario($data['roles']);
                }

                return $data;
            })
            ->slideOver()
            ->successNotificationTitle($mensaje)
            ->after(fn () => $this->refreshCatalogoData());
    }

    protected function accionVer(string $nombre, string $resource, string $singular): Actions\ViewAction
    {
        $model = $resource::getModel();
        $entidad = str_replace('ver', '', $nombre);

        $action = Actions\ViewAction::make($nombre)
            ->model($model)
            ->modalHeading(function (?Model $record, array $arguments = []) use ($model) {
                $rec = $record ?? $model::find($arguments['record'] ?? null);
                if ($rec instanceof User) {
                    return trim(($rec->name ?? '') . ' ' . ($rec->apellido_paterno ?? '')) ?: 'Detalle de usuario';
                }
                if ($rec instanceof Receptor) {
                    return trim(($rec->nombre ?? '') . ' ' . ($rec->apellido_paterno ?? '') . ' ' . ($rec->apellido_materno ?? '')) ?: 'Detalle de receptor';
                }

                return trim($rec?->nombre ?? $rec?->nombre_empresa ?? 'Detalle');
            })
            ->modalDescription(fn (?Model $record, array $arguments = []) => $this->construirDescripcionModal($record ?? $model::find($arguments['record'] ?? null), $singular))
            ->modalCancelActionLabel('Cerrar')
            ->record(fn (array $arguments) => $model::find($arguments['record'] ?? null))
            ->slideOver();

        if (method_exists($resource, 'infolist')) {
            $action->infolist(fn (Infolist $infolist): Infolist => $resource::infolist($infolist));
        }

        $action->form(fn (Form $form): Form => $resource::form($form));

        $action->extraModalFooterActions([
            Actions\Action::make('editar_desde_detalle')
                ->label('Editar')
                ->color('warning')
                ->icon('heroicon-m-pencil-square')
                ->action(fn ($record, $livewire) => $livewire->mountAction(
                    'editar' . $entidad, ['record' => $record->getKey()]
                )),
            Actions\Action::make('eliminar_desde_detalle')
                ->label('Eliminar')
                ->color('danger')
                ->icon('heroicon-m-trash')
                ->hidden(fn (?Model $record): bool => $record instanceof User && $record->email === 'admin@tecnm.edu.mx')
                ->action(fn ($record, $livewire) => $livewire->mountAction(
                    'eliminar' . $entidad, ['record' => $record->getKey()]
                )),
        ]);

        return $action;
    }

    protected function accionEliminar(string $nombre, string $resource, string $mensaje, string $singular): Actions\DeleteAction
    {
        $model = $resource::getModel();

        return Actions\DeleteAction::make($nombre)
            ->model($model)
            ->modalHeading(function (?Model $record, array $arguments = []) use ($model) {
                $rec = $record ?? $model::find($arguments['record'] ?? null);
                if ($rec instanceof User) {
                    return '¿Eliminar usuario ' . ($rec->name ?? 'registro') . '?';
                }

                return '¿Eliminar ' . trim($rec?->nombre ?? $rec?->nombre_empresa ?? 'registro') . '?';
            })
            ->modalDescription(function (?Model $record, array $arguments = []) use ($model) {
                $rec = $record ?? $model::find($arguments['record'] ?? null);
                if ($rec instanceof User) {
                    return new HtmlString(e($rec->email ?? '—') . ' · ID: #' . e($rec->getKey()) . ' · Esta acción no se puede deshacer');
                }

                return new HtmlString('ID: #' . e($rec?->getKey()) . ' · Esta acción no se puede deshacer');
            })
            ->record(fn (array $arguments) => $model::find($arguments['record'] ?? null))
            ->hidden(static function (?Model $record = null): bool {
                if (! $record) {
                    return false;
                }
                if ($record instanceof User && $record->email === 'admin@tecnm.edu.mx') {
                    return true;
                }

                return method_exists($record, 'trashed') ? $record->trashed() : false;
            })
            ->using(function (?Model $record, Actions\DeleteAction $action, array $arguments = []) use ($model) {
                $record ??= $model::find($arguments['record'] ?? null);
                if (! $record) {
                    return false;
                }

                try {
                    return $record->delete();
                } catch (\Throwable $e) {
                    Notification::make()
                        ->title('No se puede eliminar el registro')
                        ->body('El registro está en uso por otros elementos del sistema.')
                        ->danger()
                        ->send();

                    $action->halt();

                    return false;
                }
            })
            ->successNotificationTitle($mensaje)
            ->after(fn () => $this->refreshCatalogoData());
    }

    protected function construirDescripcionModal(?Model $rec, ?string $singular = null): HtmlString
    {
        if ($rec instanceof User) {
            $rol = $rec->getRoleNames()->first() ?? $rec->tipo_usuario ?? 'Sin rol';

            return new HtmlString(e($rec->email ?? '—') . ' · ' . e($rol) . ' · ID: #' . e($rec->getKey()));
        }
        if ($rec instanceof Proveedor) {
            return new HtmlString(e($rec->contacto_email ?? '—') . ' · ID: #' . e($rec->getKey()));
        }
        if ($rec instanceof Receptor) {
            return new HtmlString(e($rec->cargo ?? '—') . ' · ID: #' . e($rec->getKey()));
        }
        if ($singular !== null) {
            return new HtmlString('🏢 ' . e($singular) . ' · ID: #' . e($rec?->getKey()));
        }

        return new HtmlString('ID: #' . e($rec?->getKey()));
    }
}
