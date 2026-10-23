<x-filament-panels::page>
    <div>
        {{-- Encabezado principal --}}
        <div class="sigem-rep-header">
            <h1>⚙️ Configuración y Catálogos</h1>
            <p>Gestiona los catálogos del sistema, consulta la bitácora de auditoría y ajusta las preferencias del panel.</p>
        </div>

        {{-- Barra de pestañas --}}
        <div class="sigem-tabs">
            <button type="button"
                    wire:click="setTab('catalogos')"
                    class="sigem-tab {{ $tabActiva === 'catalogos' ? 'active' : '' }}">
                📁 Catálogos
            </button>
            <button type="button"
                    wire:click="setTab('bitacora')"
                    class="sigem-tab {{ $tabActiva === 'bitacora' ? 'active' : '' }}">
                🕘 Bitácora
            </button>
            <button type="button"
                    wire:click="setTab('preferencias')"
                    class="sigem-tab {{ $tabActiva === 'preferencias' ? 'active' : '' }}">
                ⚙️ Preferencias
            </button>
        </div>

        {{-- CONTENIDO: PESTAÑA CATÁLOGOS --}}
        @if ($tabActiva === 'catalogos')
            @php
                $catalogos = $this->getCatalogosList();
                $catActivo = $this->getDatosCatalogoActivo();
            @endphp

            {{-- Franja informativa celeste --}}
            <div class="sigem-note-strip">
                <span style="font-size: 20px; line-height: 1;">💡</span>
                <div>
                    Los catálogos alimentan los formularios del sistema (altas de equipos, solicitudes, mantenimientos).
                    Cárgalos al inicio del ciclo; después solo necesitarás ediciones puntuales.
                </div>
            </div>

            {{-- Fila de chips de catálogo con conteos --}}
            <div class="sigem-cat-tabs">
                @foreach ($catalogos as $cat)
                    <button type="button"
                            wire:click="setCatalogo('{{ $cat['id'] }}')"
                            class="sigem-cat-tab {{ $catalogoActivo === $cat['id'] ? 'active' : '' }}"
                            title="Ver catálogo de {{ $cat['nombre'] }}">
                        {{ $cat['emoji'] }} {{ $cat['nombre'] }}
                        <b>{{ $cat['total'] }}</b>
                    </button>
                @endforeach
            </div>

            {{-- Tabla del catálogo seleccionado --}}
            <div class="sigem-rep-view">
                <div class="sigem-rep-vh">
                    <div>
                        <h3>{{ $catActivo['emoji'] }} Catálogo de {{ $catActivo['nombre'] }}</h3>
                        <div class="sigem-rep-meta">
                            {{ $catActivo['total'] }} registros registrados en la base de datos
                        </div>
                    </div>
                    <div style="display: inline-flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                        <button type="button"
                                wire:click="mountAction('{{ $catActivo['crearAccion'] }}')"
                                class="sigem-rep-btn-primary"
                                title="Crear nuevo registro en este catálogo">
                            ＋ Nuevo {{ $catActivo['singular'] }}
                        </button>
                        <a href="{{ $catActivo['indexUrl'] }}"
                           class="sigem-rep-btn-sec"
                           title="Abrir la gestión completa con filtros y paginado">
                            Gestionar en catálogo completo →
                        </a>
                    </div>
                </div>

                @if (count($catActivo['rows']) > 0)
                    <div class="sigem-rep-table-wrap">
                        <table class="fi-ta-table">
                            <thead>
                                <tr>
                                    @foreach ($catActivo['headers'] as $header)
                                        <th>{{ $header }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($catActivo['rows'] as $row)
                                    <tr>
                                        @foreach ($row as $cell)
                                            <td>{!! $cell !!}</td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="sigem-rep-empty">
                        Sin registros en este catálogo por ahora.
                    </div>
                @endif
            </div>
        @endif

        {{-- CONTENIDO: PESTAÑA BITÁCORA --}}
        @if ($tabActiva === 'bitacora')
            @php
                $bitacoraRows = $this->getBitacoraData();
            @endphp

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
                    @foreach ($this->getBitacoraUsuarios() as $uid => $uname)
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
                    <div class="sigem-rep-table-wrap">
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
        @endif

        {{-- CONTENIDO: PESTAÑA PREFERENCIAS --}}
        @if ($tabActiva === 'preferencias')
            <div class="sigem-pref-card">
                <div class="sigem-pref-row">
                    <div>
                        <div class="sigem-pref-t">Registros por hoja</div>
                        <div class="sigem-pref-d">
                            Define la cantidad de registros que se visualizan de forma predeterminada por página
                            en las tablas y catálogos del panel administrativo.
                        </div>
                    </div>

                    <div style="display: flex; flex-direction: column; gap: 12px; align-items: flex-end;">
                        {{-- Chips rápidos --}}
                        <div class="sigem-pref-chips">
                            @foreach ([10, 20, 50, 100, 200] as $opt)
                                <button type="button"
                                        wire:click="seleccionarRegistrosPorHoja({{ $opt }})"
                                        class="{{ $registrosPorHoja === $opt && empty($otroValor) ? 'on' : '' }}">
                                    {{ $opt }}
                                </button>
                            @endforeach
                        </div>

                        {{-- Input personalizado y botón guardar --}}
                        <div class="sigem-pref-in">
                            <span>Otro valor:</span>
                            <input type="number"
                                   wire:model.live="otroValor"
                                   min="5"
                                   max="500"
                                   placeholder="5 - 500">
                            <button type="button"
                                    wire:click="guardarPreferencias"
                                    class="sigem-rep-btn-primary">
                                Guardar
                            </button>
                        </div>

                        {{-- Mensaje de estado --}}
                        @if (! empty($mensajePreferencia))
                            <div class="sigem-pref-msg ok">
                                {{ $mensajePreferencia }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @endif
    </div>
</x-filament-panels::page>
