<?php

namespace App\Filament\Concerns;

/**
 * Facade que agrupa los traits de acciones masivas, selección por página,
 * slide-over contextual, filtros encadenados y exportación del inventario.
 */
trait ConAccionesMasivasInventario
{
    use ConSeleccionFilas;
    use ConSlideOverCambio;
    use ConAccionesMasivas;
    use ConExportacionMasiva;
    use ConFiltrosEncadenados;
}
