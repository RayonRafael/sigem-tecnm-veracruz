/**
 * SIGEM - Manejo de Red Lenta, Spinners en Botones y Toasts de Red
 * FASE 1 RED LENTA - BATCH 2 & FIX SPINNERS DUPLICADOS
 */
(function () {
  'use strict';

  // -------------------------------------------------------------
  // 1. BATCH 2A: Mapeo de textos y helpers de spinners en botones
  // -------------------------------------------------------------
  const LOADING_TEXT_MAP = {
    'iniciar sesión': 'Iniciando sesión...',
    'entrar': 'Iniciando sesión...',
    'guardar cambios': 'Guardando...',
    'guardar': 'Guardando...',
    'guardar preferencias': 'Guardando...',
    'crear': 'Creando...',
    'crear y crear otro': 'Creando...',
    'exportar': 'Exportando...',
    'exportar csv': 'Exportando...',
    '⬇ exportar': 'Exportando...',
    'eliminar': 'Eliminando...',
    'confirmar': 'Eliminando...'
  };

  const SPINNER_SVG = `<svg class="sigem-btn-spinner" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" opacity="0.25"></circle><path d="M4 12a8 8 0 018-8" stroke="currentColor" stroke-width="3" stroke-linecap="round"></path></svg>`;

  function getButtonLabelEl(button) {
    return button.querySelector('.fi-btn-label') || button;
  }

  /**
   * REGLA 4: Comprueba si el botón ya cuenta con un spinner nativo de Filament
   * o un indicador de carga embebido en Blade (wire:loading).
   */
  function hasNativeSpinner(button) {
    // 1. Si el botón tiene SVGs de carga nativos de Filament
    if (button.querySelector('svg.animate-spin, svg[wire\\:loading], .fi-btn-icon')) {
      return true;
    }
    // 2. Si el botón ya tiene bloque wire:loading con spinner sigem embebido en Blade
    if (button.querySelector('.sigem-btn-loading-content, svg.sigem-btn-spinner')) {
      return true;
    }
    // 3. Si es un botón nativo de Filament (.fi-btn o .fi-ac-action)
    if (button.classList.contains('fi-btn') || button.classList.contains('fi-ac-action')) {
      return true;
    }
    return false;
  }

  /**
   * REGLA 3: Garantiza que el botón tenga SOLO 1 spinner visible en el DOM.
   * Si Filament renderiza múltiples svgs hermanos (ej. wire:loading + isProcessing en Login),
   * oculta y marca los duplicados para que solo se muestre el primero.
   */
  function deduplicateButtonSpinners(button) {
    if (!button) return;
    const spinners = button.querySelectorAll('svg.animate-spin, svg.sigem-btn-spinner');
    if (spinners.length > 1) {
      for (let i = 1; i < spinners.length; i++) {
        if (!spinners[i].classList.contains('sigem-spinner-duplicate')) {
          spinners[i].classList.add('sigem-spinner-duplicate');
          spinners[i].style.setProperty('display', 'none', 'important');
        }
      }
    }
  }

  function applyButtonLoading(button, forceText = null) {
    if (button.dataset.sigemLoading === 'true') return;

    const labelEl = getButtonLabelEl(button);
    const text = (labelEl.innerText || labelEl.textContent || '').trim().toLowerCase();

    let targetLoadingText = forceText;
    if (!targetLoadingText) {
      for (const [key, val] of Object.entries(LOADING_TEXT_MAP)) {
        if (text === key || text.startsWith(key)) {
          targetLoadingText = val;
          break;
        }
      }
    }

    if (!targetLoadingText && !button.classList.contains('fi-btn') && button.type !== 'submit') {
      return;
    }

    button.dataset.sigemLoading = 'true';
    button.dataset.originalHtml = labelEl.innerHTML;
    button.classList.add('is-loading');

    // Desduplicar cualquier spinner nativo redundante antes de proceder
    deduplicateButtonSpinners(button);

    // REGLA 4: Si el botón ya tiene spinner nativo → NO agregar otro
    if (hasNativeSpinner(button)) {
      // Si el botón tiene bloque blade de wire:loading (como en configuracion o reportes)
      const loadingContent = button.querySelector('.sigem-btn-loading-content');
      const staticLabel = button.querySelector('[wire\\:loading\\.remove]');
      if (loadingContent && staticLabel) {
        staticLabel.style.display = 'none';
        loadingContent.style.display = 'inline-flex';
      } else if (targetLoadingText) {
        // En botones de Filament, solo actualizar el texto del label si existe sin inyectar nuevo SVG
        const fiLabel = button.querySelector('.fi-btn-label') || labelEl;
        fiLabel.textContent = targetLoadingText;
      }

      // Si es un botón con spinner nativo en preview, asegurar que el primer spinner sea visible
      const firstSpinner = button.querySelector('svg.animate-spin, svg.sigem-btn-spinner');
      if (firstSpinner) {
        firstSpinner.style.removeProperty('display');
      }
    } else {
      // REGLA 5: Si el botón NO tiene spinner → agregar el nuestro
      labelEl.innerHTML = SPINNER_SVG + '<span>' + (targetLoadingText || text) + '</span>';
    }
  }

  function resetButtonLoading(button) {
    if (button.dataset.sigemLoading !== 'true') return;
    const labelEl = getButtonLabelEl(button);
    if (button.dataset.originalHtml) {
      labelEl.innerHTML = button.dataset.originalHtml;
    }

    const loadingContent = button.querySelector('.sigem-btn-loading-content');
    const staticLabel = button.querySelector('[wire\\:loading\\.remove]');
    if (loadingContent && staticLabel) {
      staticLabel.style.removeProperty('display');
      loadingContent.style.display = 'none';
    }

    button.querySelectorAll('.sigem-spinner-duplicate').forEach(el => {
      el.classList.remove('sigem-spinner-duplicate');
      el.style.removeProperty('display');
    });

    delete button.dataset.sigemLoading;
    delete button.dataset.originalHtml;
    button.classList.remove('is-loading');
  }

  // Interceptar eventos de clic en botones de submit y acciones
  document.addEventListener('click', (e) => {
    const button = e.target.closest('button, [role="button"]');
    if (!button) return;

    if (button.disabled || button.getAttribute('aria-disabled') === 'true') return;

    if (
      button.type === 'submit' ||
      button.classList.contains('fi-btn') ||
      button.classList.contains('sigem-rep-btn-primary') ||
      button.classList.contains('sigem-rep-btn-sec') ||
      button.classList.contains('sigem-btn-primary') ||
      button.getAttribute('wire:click')
    ) {
      applyButtonLoading(button);
    }
  }, true);

  // -------------------------------------------------------------
  // 2. BATCH 2B: Toasts de error de red y timeout
  // -------------------------------------------------------------
  let isOffline = !navigator.onLine;
  let activeRequests = 0;
  let timeoutTimer = null;

  function showNetworkToast(type) {
    let title = '';
    let status = 'warning';
    let duration = 6000;

    if (type === 'offline') {
      title = '⚠️ Sin conexión — reintentando...';
      status = 'warning';
    } else if (type === 'timeout') {
      title = '⏱️ Está tardando más de lo normal';
      status = 'warning';
    } else if (type === 'online') {
      title = '✅ Conexión restaurada';
      status = 'success';
      duration = 4000;
    } else {
      return;
    }

    if (window.FilamentNotification) {
      new window.FilamentNotification()
        .title(title)
        .status(status)
        .duration(duration)
        .send();
    } else {
      window.dispatchEvent(
        new CustomEvent('notificationSent', {
          detail: {
            notification: {
              id: 'sigem-net-' + Date.now(),
              title: title,
              status: status,
              duration: duration
            }
          }
        })
      );
    }
  }

  // Eventos de estado de red del navegador
  window.addEventListener('offline', () => {
    isOffline = true;
    showNetworkToast('offline');
  });

  window.addEventListener('online', () => {
    if (isOffline) {
      isOffline = false;
      showNetworkToast('online');
    }
  });

  // Hook de Livewire para timeout (>5s) y fallos de solicitud
  function registerLivewireHooks() {
    if (!window.Livewire) return;

    window.Livewire.hook('commit', ({ commit, respond, succeed, fail }) => {
      activeRequests++;

      if (!timeoutTimer) {
        timeoutTimer = setTimeout(() => {
          if (activeRequests > 0) {
            showNetworkToast('timeout');
          }
        }, 5000);
      }

      respond(() => {
        activeRequests = Math.max(0, activeRequests - 1);
        if (activeRequests === 0 && timeoutTimer) {
          clearTimeout(timeoutTimer);
          timeoutTimer = null;
        }

        // Restaurar estado de botones al responder la solicitud
        document.querySelectorAll('[data-sigem-loading="true"]').forEach((btn) => {
          resetButtonLoading(btn);
        });
      });

      fail((response) => {
        const status = response?.status;
        if (status === 0 || !navigator.onLine) {
          isOffline = true;
          showNetworkToast('offline');
        }
      });
    });
  }

  if (window.Livewire) {
    registerLivewireHooks();
  } else {
    document.addEventListener('livewire:init', registerLivewireHooks);
  }

  // -------------------------------------------------------------
  // 3. Previews para verificación visual y pruebas automáticas
  // -------------------------------------------------------------
  const urlParams = new URLSearchParams(window.location.search);
  const toastPreview = urlParams.get('toast_preview');
  if (toastPreview) {
    window.addEventListener('load', () => {
      setTimeout(() => showNetworkToast(toastPreview), 600);
    });
  }

  const spinnerPreview = urlParams.get('spinner_preview');
  if (spinnerPreview) {
    window.addEventListener('load', () => {
      setTimeout(() => {
        if (spinnerPreview === 'login') {
          const loginBtn = document.querySelector('button[type="submit"]');
          if (loginBtn) {
            applyButtonLoading(loginBtn, 'Iniciando sesión...');
            // Simular el primer spinner nativo como visible
            const firstSpinner = loginBtn.querySelector('svg.animate-spin');
            if (firstSpinner) {
              firstSpinner.style.setProperty('display', 'inline-block', 'important');
            }
          }
        } else {
          document.querySelectorAll('button, .fi-btn, .sigem-rep-btn-primary, .sigem-rep-btn-sec').forEach((btn) => {
            const t = (btn.innerText || '').toLowerCase();
            if (t.includes(spinnerPreview) || (spinnerPreview === 'guardar' && t.includes('guardar'))) {
              applyButtonLoading(btn);
            }
          });
        }
      }, 500);
    });
  }

  window.sigemShowNetworkToast = showNetworkToast;
  window.sigemApplyButtonLoading = applyButtonLoading;
  window.sigemResetButtonLoading = resetButtonLoading;
  window.sigemDeduplicateButtonSpinners = deduplicateButtonSpinners;
})();
