<x-filament-panels::page>
    <div style="display: flex; gap: 1.5rem; align-items: flex-start; flex-wrap: wrap;">
        <!-- Tabla a la izquierda -->
        <div style="flex: 1 1 70%; min-width: 0;">
            {{ $this->table }}
        </div>
        
        <!-- Panel de Infolist a la derecha -->
        <div style="flex: 1 1 28%; min-width: 320px; position: sticky; top: 1.5rem; max-height: 85vh; overflow-y: auto;" class="bg-white dark:bg-gray-900 rounded-xl shadow-sm ring-1 ring-gray-950/5 p-4">
            @if($activeRecord)
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-bold">Detalle del Equipo</h3>
                    <x-filament::button color="gray" size="sm" wire:click="$set('activeRecord', null)">Cerrar</x-filament::button>
                </div>
                {{ $this->infolist }}
            @else
                <div class="text-center text-gray-500 py-10">
                    <div style="max-width: 64px; margin: 0 auto;"><x-heroicon-o-cursor-arrow-rays style="color: #9ca3af; margin-bottom: 0.75rem;" /></div>
                    <p>Selecciona un registro en la tabla para ver su detalle aquí.</p>
                </div>
            @endif
        </div>
    </div>
</x-filament-panels::page>
