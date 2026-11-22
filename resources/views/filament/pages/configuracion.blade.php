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

        {{-- CONTENIDO POR PESTAÑA --}}
        @if ($tabActiva === 'catalogos')
            <x-configuracion.config-catalogos
                :catalogos="$this->getCatalogosList()"
                :catalogo-activo="$catalogoActivo"
                :cat-activo="$this->getDatosCatalogoActivo()"
                :placeholder="$this->getPlaceholderCatalogo()"
                :tiene-filtros-activos="$this->tieneFiltrosCatalogoActivos()"
                :opciones-filtros="$this->getOpcionesFiltrosCatalogoActivo()"
            />
        @elseif ($tabActiva === 'bitacora')
            <x-configuracion.config-bitacora
                :bitacora-rows="$this->getBitacoraData()"
                :usuarios="$this->getBitacoraUsuarios()"
            />
        @elseif ($tabActiva === 'preferencias')
            <x-configuracion.config-preferencias
                :registros-por-hoja="$registrosPorHoja"
                :otro-valor="$otroValor"
                :mensaje-preferencia="$mensajePreferencia"
            />
        @endif
    </div>
</x-filament-panels::page>
