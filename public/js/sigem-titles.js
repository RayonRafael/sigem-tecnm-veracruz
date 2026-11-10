/**
 * SIGEM - Control de títulos cortos en pestaña (document.title)
 * Sin prefijo "SIGEM ·" ya que el favicon ya identifica la app.
 */
(function () {
    function getShortTitle() {
        const path = window.location.pathname.toLowerCase();
        const search = window.location.search.toLowerCase();

        if (path.includes('/login')) {
            return 'Iniciar sesión';
        }
        if (path.includes('/solicituds')) {
            return 'Solicitudes';
        }
        if (path.includes('/mantenimientos')) {
            return 'Mantenimientos';
        }
        if (path.includes('/reportes')) {
            return 'Reportes';
        }
        if (path.includes('/configuracion')) {
            if (search.includes('tab=bitacora')) {
                return 'Bitácora';
            }
            return 'Configuración';
        }
        if (path.includes('/catalogos')) {
            return 'Catálogos';
        }
        if (path.includes('/inventarios')) {
            return 'Inventario';
        }
        if (path === '/admin' || path === '/admin/' || path === '/servicio-social' || path === '/servicio-social/') {
            return 'Panel principal';
        }

        // Título desde h1 si es otra página interna
        const h1 = document.querySelector('.fi-header-heading, .sigem-hero h1, h1');
        if (h1) {
            let text = h1.textContent
                .replace(/[\u{1F300}-\u{1FAFF}\u{2600}-\u{27BF}]/gu, '')
                .trim();
            if (text.toLowerCase().includes('buenos días') || text.toLowerCase().includes('buenas tardes') || text.toLowerCase().includes('buenas noches')) {
                return 'Panel principal';
            }
            if (text.length > 0) {
                return text;
            }
        }

        return document.title.replace(/^SIGEM\s*[-·|:]\s*/i, '').trim();
    }

    function applyShortTitle() {
        const targetTitle = getShortTitle();
        if (targetTitle && document.title !== targetTitle) {
            document.title = targetTitle;
        }
    }

    // Ejecución inicial y en eventos de navegación
    applyShortTitle();
    document.addEventListener('DOMContentLoaded', applyShortTitle);
    document.addEventListener('livewire:navigated', applyShortTitle);
    document.addEventListener('livewire:load', applyShortTitle);
    window.addEventListener('popstate', applyShortTitle);

    // Observador para cambios reactivos de Livewire
    let isUpdating = false;
    const titleEl = document.querySelector('title');
    if (titleEl) {
        const observer = new MutationObserver(() => {
            if (isUpdating) return;
            const targetTitle = getShortTitle();
            if (document.title !== targetTitle) {
                isUpdating = true;
                document.title = targetTitle;
                isUpdating = false;
            }
        });
        observer.observe(titleEl, { childList: true, characterData: true, subtree: true });
    }
})();

