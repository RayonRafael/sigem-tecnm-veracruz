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
        if (path.includes('/profile')) {
            return 'Mi Perfil';
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

    function applyLoginPlaceholders() {
        if (!window.location.pathname.toLowerCase().includes('/login')) return;
        const email = document.querySelector('.fi-simple-main input[type="email"], input[type="email"]');
        if (email && !email.getAttribute('placeholder')) {
            email.setAttribute('placeholder', 'usuario@veracruz.tecnm.mx');
        }
        const pass = document.querySelector('.fi-simple-main input[type="password"], input[type="password"]');
        if (pass && !pass.getAttribute('placeholder')) {
            pass.setAttribute('placeholder', '••••••••');
        }
    }

    function getChoiceLabelText(el) {
        if (!el) return '';
        const clone = el.cloneNode(true);
        clone.querySelectorAll('.choices__button, button, svg').forEach((node) => node.remove());
        return (clone.textContent || '').replace(/\s+/g, ' ').trim();
    }

    function applySelectTooltips(root) {
        const scope = root && root.querySelectorAll ? root : document;

        // 1. Opciones seleccionadas y opciones del dropdown en Choices.js (Filament Select)
        scope.querySelectorAll('.choices').forEach((wrapper) => {
            const selectedItem = wrapper.querySelector('.choices__list--single .choices__item');
            const inner = wrapper.querySelector('.choices__inner');
            if (selectedItem) {
                const label = getChoiceLabelText(selectedItem);
                if (label && !selectedItem.classList.contains('choices__placeholder')) {
                    if (selectedItem.getAttribute('title') !== label) {
                        selectedItem.setAttribute('title', label);
                    }
                    if (inner && inner.getAttribute('title') !== label) {
                        inner.setAttribute('title', label);
                    }
                } else {
                    selectedItem.removeAttribute('title');
                    if (inner) inner.removeAttribute('title');
                }
            }

            wrapper.querySelectorAll('.choices__list--dropdown .choices__item--choice, .choices__list[aria-expanded] .choices__item--choice').forEach((opt) => {
                const label = getChoiceLabelText(opt);
                if (label && opt.getAttribute('title') !== label) {
                    opt.setAttribute('title', label);
                }
            });
        });

        // 2. Selects nativos: tooltip con la opción seleccionada
        scope.querySelectorAll('select').forEach((sel) => {
            if (sel.closest('.choices')) return;
            const opt = sel.options && sel.selectedIndex >= 0 ? sel.options[sel.selectedIndex] : null;
            const label = opt ? (opt.textContent || '').replace(/\s+/g, ' ').trim() : '';
            if (label && sel.getAttribute('title') !== label) {
                sel.setAttribute('title', label);
            }
        });
    }

    let selectTooltipTimer = null;
    function scheduleSelectTooltips() {
        if (selectTooltipTimer) clearTimeout(selectTooltipTimer);
        selectTooltipTimer = setTimeout(() => applySelectTooltips(document), 60);
    }

    function init() {
        applyShortTitle();
        applyLoginPlaceholders();
        applySelectTooltips(document);
    }

    // Ejecución inicial y en eventos de navegación
    init();
    document.addEventListener('DOMContentLoaded', init);
    document.addEventListener('livewire:navigated', init);
    document.addEventListener('livewire:load', init);
    document.addEventListener('change', scheduleSelectTooltips, true);
    document.addEventListener('click', (e) => {
        if (e.target && e.target.closest && e.target.closest('.choices')) {
            scheduleSelectTooltips();
        }
    }, true);
    window.addEventListener('popstate', init);
    setTimeout(init, 100);
    setTimeout(init, 500);

    // Observador para cambios reactivos de Livewire (título + selects Choices.js en modales/slide-overs)
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

    const bodyObserver = new MutationObserver((mutations) => {
        for (const m of mutations) {
            if (m.target && m.target.closest && m.target.closest('.choices')) {
                scheduleSelectTooltips();
                return;
            }
            for (const node of m.addedNodes) {
                if (node.nodeType === 1 && (node.matches('.choices, select') || (node.querySelector && node.querySelector('.choices, select')))) {
                    scheduleSelectTooltips();
                    return;
                }
            }
        }
    });
    if (document.body) {
        bodyObserver.observe(document.body, { childList: true, subtree: true });
    } else {
        document.addEventListener('DOMContentLoaded', () => {
            if (document.body) {
                bodyObserver.observe(document.body, { childList: true, subtree: true });
            }
        });
    }
})();

