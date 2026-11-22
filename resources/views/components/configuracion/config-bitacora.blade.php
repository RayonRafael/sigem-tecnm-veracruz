@props([
    'bitacoraRows' => [],
    'usuarios' => [],
])

<div style="margin-bottom: 14px;">
    <h3 style="font-size: 19px; font-weight: 700; margin: 0 0 4px; color: var(--sig-text);">
        🕘 Bitácora de actividades
    </h3>
    <p style="font-size: 15.5px; color: var(--sig-muted); margin: 0;">
        Registro cronológico de operaciones, accesos y modificaciones auditables en el sistema.
    </p>
</div>

{{-- Barra de filtros --}}
<div class="sigem-bitacora-filters">
    <input type="text"
           wire:model.live.debounce.300ms="bitacoraSearch"
           placeholder="🔍 Buscar usuario, evento o detalle..."
           style="min-width: 220px; flex: 1;">

    <select wire:model.live="bitacoraUsuario" style="min-width: 170px;">
        <option value="">Todos los usuarios</option>
        @foreach ($usuarios as $uid => $uname)
            <option value="{{ $uid }}">{{ $uname }}</option>
        @endforeach
    </select>

    <select wire:model.live="bitacoraRol" style="min-width: 150px;">
        <option value="">Todos los roles</option>
        <option value="Administrador">Administrador</option>
        <option value="Servicio Social">Servicio Social</option>
    </select>

    <select wire:model.live="bitacoraAccion" style="min-width: 150px;">
        <option value="">Todas las acciones</option>
        <option value="created">Creación</option>
        <option value="updated">Actualización</option>
        <option value="deleted">Eliminación</option>
    </select>

    <button type="button"
            wire:click="limpiarFiltrosBitacora"
            class="sigem-rep-btn-sec"
            title="Restablecer filtros">
        ✕ Quitar filtros
    </button>
</div>

{{-- Tabla de bitácora --}}
<div class="sigem-rep-view">
    <div class="sigem-rep-vh">
        <div class="sigem-rep-meta">
            Mostrando {{ count($bitacoraRows) }} eventos recientes
        </div>
    </div>

    @if (count($bitacoraRows) > 0)
        <div class="sigem-rep-table-wrap sigem-scroll-top">
            <table class="fi-ta-table">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Usuario</th>
                        <th>Rol</th>
                        <th>Acción</th>
                        <th>Detalle</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($bitacoraRows as $brow)
                        <tr>
                            <td><span class="sigem-td-folio">{{ $brow['fecha'] }}</span></td>
                            <td>{!! $brow['usuario'] !!}</td>
                            <td>{!! $brow['rol'] !!}</td>
                            <td>{!! $brow['accion'] !!}</td>
                            <td>{{ $brow['detalle'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="sigem-rep-empty">
            Sin registros de bitácora coincidentes con los filtros.
        </div>
    @endif
</div>
