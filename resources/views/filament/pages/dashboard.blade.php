<x-filament-panels::page>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <style>
        .custom-bento-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1.5rem;
            width: 100%;
        }
        
        .custom-bento-col-left {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }
        
        .custom-bento-col-right {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }
        
        .custom-bento-inner-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1.5rem;
        }
        
        @media (min-width: 1024px) {
            .custom-bento-grid {
                grid-template-columns: repeat(12, minmax(0, 1fr));
            }
            
            .custom-bento-col-left {
                grid-column: span 4 / span 4;
            }
            
            .custom-bento-col-right {
                grid-column: span 8 / span 8;
            }
            
            .custom-bento-inner-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
            
            .custom-bento-inner-chart {
                grid-column: span 2 / span 2;
            }
            
            .custom-bento-inner-alerts {
                grid-column: span 1 / span 1;
            }
        }
    </style>

    <div class="custom-bento-grid">
        
        {{-- SIDEBAR IZQUIERDO (4 columnas de 12) --}}
        <div class="custom-bento-col-left">
            @livewire(\App\Filament\Widgets\RegistroRapidoWidget::class)
            @livewire(\App\Filament\Widgets\AccesosRapidosWidget::class)
        </div>
        
        {{-- ÁREA PRINCIPAL (8 columnas de 12) --}}
        <div class="custom-bento-col-right">
            @livewire(\App\Filament\Widgets\StatsOverview::class)
            
            <div class="custom-bento-inner-grid">
                <div class="custom-bento-inner-chart">
                    @livewire(\App\Filament\Widgets\ActivosPorEstadoWidget::class)
                </div>
                <div class="custom-bento-inner-alerts">
                    @livewire(\App\Filament\Widgets\AlertasWidget::class)
                </div>
            </div>
            
            @livewire(\App\Filament\Widgets\UltimasActividadesWidget::class)
        </div>
        
    </div>
</x-filament-panels::page>