import puppeteer from 'puppeteer-core';
import path from 'path';
import fs from 'fs';

const EDGE_PATH = 'C:\\Program Files (x86)\\Microsoft\\Edge\\Application\\msedge.exe';
const ARTIFACT_DIR = 'C:\\Users\\Rafael572\\.gemini\\antigravity\\brain\\8aa8cfbb-190a-4be8-a9af-9c36fb57a5a7';

(async () => {
  console.log('Launching browser...');
  const profileDir = path.join('scratch', 'diag-profile-' + Date.now());
  const browser = await puppeteer.launch({
    executablePath: EDGE_PATH,
    headless: true,
    userDataDir: profileDir,
    args: ['--no-sandbox', '--disable-setuid-sandbox', '--disable-gpu'],
    defaultViewport: { width: 1440, height: 960 }
  });

  const page = await browser.newPage();

  // Helper to login
  async function login() {
    console.log('Logging in as Admin...');
    await page.goto('http://127.0.0.1:8000/admin/login', { waitUntil: 'networkidle2' });
    const isLogin = await page.$('input[type="email"]');
    if (isLogin) {
      await page.type('input[type="email"]', 'admin@tecnm.edu.mx');
      await page.type('input[type="password"]', 'admin123');
      await page.click('button[type="submit"]');
      await page.waitForFunction(() => !window.location.pathname.includes('/login'), { timeout: 10000 });
      console.log('Logged in successfully.');
    }
  }

  // Inspection function
  async function inspectModal(resourceKey, resourceName, screenshotFilename) {
    console.log(`\n========================================`);
    console.log(`Inspecting: ${resourceName} (${resourceKey})`);
    
    // Wait for slide-over window
    try {
      await page.waitForSelector('.fi-modal-slide-over-window', { visible: true, timeout: 6000 });
    } catch (e) {
      console.error(`Slide-over did NOT open for ${resourceName}: ${e.message}`);
      return {
        resource: resourceName,
        opensSlideOver: false,
        error: 'Slide-over did not open or selector not found'
      };
    }

    await new Promise(r => setTimeout(r, 700));

    // Deep analysis inside page DOM
    const analysis = await page.evaluate((name) => {
      const modal = document.querySelector('.fi-modal-slide-over-window');
      if (!modal) return { opensSlideOver: false };

      const modalRect = modal.getBoundingClientRect();
      const contentEl = modal.querySelector('.fi-modal-content') || modal.querySelector('.fi-modal-window') || modal;
      const contentComputed = window.getComputedStyle(contentEl);

      // Check header
      const header = modal.querySelector('.fi-modal-header');
      const headerRect = header ? header.getBoundingClientRect() : null;
      const headingEl = modal.querySelector('.fi-modal-heading');
      const headingText = headingEl ? headingEl.innerText.trim() : '(Sin título)';
      const headingRect = headingEl ? headingEl.getBoundingClientRect() : null;
      const descEl = modal.querySelector('.fi-modal-description');
      const descText = descEl ? descEl.innerText.trim() : '(Sin descripción)';
      
      const closeBtn = modal.querySelector('.fi-modal-close-btn, button[x-on*="close"]');
      const closeRect = closeBtn ? closeBtn.getBoundingClientRect() : null;

      let closeCollision = false;
      let closeDistance = null;
      if (headingRect && closeRect) {
        closeDistance = closeRect.left - headingRect.right;
        if (closeDistance < 10) {
          closeCollision = true;
        }
      }

      // Check tabs
      const tabElements = Array.from(modal.querySelectorAll('.fi-tabs-item, [role="tab"]'));
      const tabs = tabElements.map(t => t.innerText.trim());

      // Check sections and double borders
      const sectionElements = Array.from(modal.querySelectorAll('.fi-section, section'));
      const sections = sectionElements.map(s => {
        const title = s.querySelector('.fi-section-header-heading, .fi-section-header, header')?.innerText.trim() || '(Sin título)';
        const comp = window.getComputedStyle(s);
        const hasBorder = comp.borderStyle !== 'none' && comp.borderWidth !== '0px';
        const hasShadow = comp.boxShadow && comp.boxShadow !== 'none';
        const hasBg = comp.backgroundColor && comp.backgroundColor !== 'rgba(0, 0, 0, 0)' && comp.backgroundColor !== 'transparent';
        return {
          title,
          hasBorder,
          border: comp.border,
          hasShadow,
          boxShadow: comp.boxShadow,
          hasBg,
          bgColor: comp.backgroundColor,
          padding: comp.padding
        };
      });

      const doubleRecuadro = sections.some(s => s.hasBorder && s.hasShadow);

      // Check grid layout
      const gridElements = Array.from(modal.querySelectorAll('[class*="grid"], [style*="--cols"]'));
      const grids = gridElements.map(g => {
        const comp = window.getComputedStyle(g);
        return {
          columns: comp.gridTemplateColumns,
          colCount: comp.gridTemplateColumns ? comp.gridTemplateColumns.split(' ').length : 1,
          gap: comp.columnGap
        };
      });

      // Check overflows
      const contentScrollOverflow = (contentEl.scrollWidth > contentEl.clientWidth + 2);
      const modalScrollOverflow = (modal.scrollWidth > modal.clientWidth + 2);

      // Scan all descendant elements for visual overflow
      const overflowingElements = [];
      const allDescendants = Array.from(modal.querySelectorAll('*'));

      for (const el of allDescendants) {
        // Skip script, style, svg paths
        if (['SCRIPT', 'STYLE', 'PATH', 'SVG'].includes(el.tagName)) continue;

        const rect = el.getBoundingClientRect();
        if (rect.width === 0 || rect.height === 0) continue;

        let isOver = false;
        let reason = [];

        // Condition A: Extends past modal right boundary
        if (rect.right > modalRect.right - 2 && rect.width < modalRect.width + 10) {
          isOver = true;
          reason.push(`Excede ancho modal (${Math.round(rect.right - modalRect.right)}px afuera)`);
        }

        // Condition B: Text/content clipped with scrollWidth > clientWidth
        if (el.scrollWidth > el.clientWidth + 3) {
          const comp = window.getComputedStyle(el);
          if (comp.overflow !== 'visible' && !['HTML', 'BODY'].includes(el.tagName) && el !== contentEl) {
            // Text truncation or container clipping
            isOver = true;
            reason.push(`Contenido recortado (scrollW: ${el.scrollWidth} > clientW: ${el.clientWidth})`);
          }
        }

        if (isOver) {
          const textPreview = (el.innerText || el.textContent || '').trim().slice(0, 40);
          overflowingElements.push({
            tag: el.tagName,
            classes: el.className || '',
            text: textPreview,
            reasons: reason
          });

          // Highlight visually with red outline and marker
          el.style.outline = '2px dashed #ef4444';
          el.style.outlineOffset = '1px';
          el.style.backgroundColor = 'rgba(239, 68, 68, 0.08)';
          el.setAttribute('data-overflow-detected', reason.join('; '));
        }
      }

      // Check footer
      const footer = modal.querySelector('.fi-modal-footer, footer');
      const footerComp = footer ? window.getComputedStyle(footer) : null;
      const footerActionsCtn = modal.querySelector('.fi-modal-footer-actions, .fi-modal-footer');
      const footerActionsComp = footerActionsCtn ? window.getComputedStyle(footerActionsCtn) : null;
      const buttons = Array.from(modal.querySelectorAll('.fi-modal-footer button, footer button')).map(b => {
        const bComp = window.getComputedStyle(b);
        const bRect = b.getBoundingClientRect();
        return {
          text: b.innerText.trim(),
          color: bComp.backgroundColor,
          textColor: bComp.color,
          left: Math.round(bRect.left),
          right: Math.round(bRect.right)
        };
      });

      // Check if footer alignment is bipolar or clean
      let footerStatus = 'Correcto';
      if (buttons.length === 0) {
        footerStatus = 'Sin botones';
      } else if (buttons.length === 1) {
        footerStatus = 'Solo Cerrar (alineado a derecha)';
      } else {
        const leftmost = buttons[0];
        const rightmost = buttons[buttons.length - 1];
        const isBipolar = (rightmost.left - leftmost.right) > 100;
        if (!isBipolar && footerComp && footerComp.justifyContent !== 'space-between' && footerActionsComp && footerActionsComp.justifyContent !== 'space-between') {
          footerStatus = 'Agrupados (sin distribución bipolar)';
        } else {
          footerStatus = 'Bipolar / Distribuido';
        }
      }

      return {
        resource: name,
        opensSlideOver: true,
        modalWidth: Math.round(modalRect.width),
        header: {
          heading: headingText,
          description: descText,
          closeCollision,
          closeDistance: closeDistance !== null ? Math.round(closeDistance) : null
        },
        tabs: {
          present: tabs.length > 0,
          tabCount: tabs.length,
          names: tabs
        },
        sections: {
          count: sections.length,
          doubleRecuadro,
          list: sections
        },
        grids: {
          maxColumns: Math.max(1, ...grids.map(g => g.colCount)),
          details: grids
        },
        overflow: {
          hasHorizontalScroll: contentScrollOverflow || modalScrollOverflow,
          overflowingElementCount: overflowingElements.length,
          elements: overflowingElements.slice(0, 10) // top 10
        },
        footer: {
          status: footerStatus,
          buttonCount: buttons.length,
          buttons: buttons.map(b => b.text)
        }
      };
    }, resourceName);

    // Save screenshot
    const shotPath = path.join(ARTIFACT_DIR, screenshotFilename);
    await page.screenshot({ path: shotPath });
    console.log(`Saved screenshot: ${screenshotFilename}`);

    // Close modal
    await page.evaluate(() => {
      const closeBtn = document.querySelector('.fi-modal-slide-over-window .fi-modal-close-btn, .fi-modal-slide-over-window button[x-on*="close"]');
      if (closeBtn) closeBtn.click();
    });
    await new Promise(r => setTimeout(r, 600));

    return analysis;
  }

  await login();

  const results = [];

  // ================= 1. INVENTARIO =================
  try {
    console.log('\nNavigating to /admin/inventarios...');
    await page.goto('http://127.0.0.1:8000/admin/inventarios', { waitUntil: 'networkidle2' });
    await new Promise(r => setTimeout(r, 1200));

    // Click 👁 Ver on first row
    const clicked = await page.evaluate(() => {
      const row = document.querySelector('.fi-ta-table tbody tr');
      if (!row) return false;
      const btn = row.querySelector('button[title*="Ver"], button[tooltip*="Ver"]') ||
                  Array.from(row.querySelectorAll('button')).find(b => b.innerHTML.includes('heroicon-m-eye') || b.innerHTML.includes('heroicon-o-eye'));
      if (btn) { btn.click(); return true; }
      return false;
    });

    if (clicked) {
      results.push(await inspectModal('inventario', 'Inventario', 'diag_ver_inventarios.png'));
    } else {
      console.error('Could not find Ver button in Inventarios');
      results.push({ resource: 'Inventario', opensSlideOver: false, error: 'No se encontró botón Ver' });
    }
  } catch (e) {
    console.error('Error in Inventario:', e);
    results.push({ resource: 'Inventario', opensSlideOver: false, error: e.message });
  }

  // ================= 2. SOLICITUDES =================
  try {
    console.log('\nNavigating to /admin/solicituds...');
    await page.goto('http://127.0.0.1:8000/admin/solicituds', { waitUntil: 'networkidle2' });
    await new Promise(r => setTimeout(r, 1200));

    const clicked = await page.evaluate(() => {
      const row = document.querySelector('.fi-ta-table tbody tr');
      if (!row) return false;
      const btn = row.querySelector('button[title*="Ver"], button[tooltip*="Ver"]') ||
                  Array.from(row.querySelectorAll('button')).find(b => b.innerHTML.includes('heroicon-m-eye') || b.innerHTML.includes('heroicon-o-eye'));
      if (btn) { btn.click(); return true; }
      return false;
    });

    if (clicked) {
      results.push(await inspectModal('solicitudes', 'Solicitudes', 'diag_ver_solicituds.png'));
    } else {
      console.error('Could not find Ver button in Solicitudes');
      results.push({ resource: 'Solicitudes', opensSlideOver: false, error: 'No se encontró botón Ver' });
    }
  } catch (e) {
    console.error('Error in Solicitudes:', e);
    results.push({ resource: 'Solicitudes', opensSlideOver: false, error: e.message });
  }

  // ================= 3. MANTENIMIENTOS =================
  try {
    console.log('\nNavigating to /admin/mantenimientos...');
    await page.goto('http://127.0.0.1:8000/admin/mantenimientos', { waitUntil: 'networkidle2' });
    await new Promise(r => setTimeout(r, 1200));

    const clicked = await page.evaluate(() => {
      const row = document.querySelector('.fi-ta-table tbody tr');
      if (!row) return false;
      const btn = row.querySelector('button[title*="Ver"], button[tooltip*="Ver"]') ||
                  Array.from(row.querySelectorAll('button')).find(b => b.innerHTML.includes('heroicon-m-eye') || b.innerHTML.includes('heroicon-o-eye'));
      if (btn) { btn.click(); return true; }
      return false;
    });

    if (clicked) {
      results.push(await inspectModal('mantenimientos', 'Mantenimientos', 'diag_ver_mantenimientos.png'));
    } else {
      console.error('Could not find Ver button in Mantenimientos');
      results.push({ resource: 'Mantenimientos', opensSlideOver: false, error: 'No se encontró botón Ver' });
    }
  } catch (e) {
    console.error('Error in Mantenimientos:', e);
    results.push({ resource: 'Mantenimientos', opensSlideOver: false, error: e.message });
  }

  // ================= 4 to 11. CATALOGOS EN /admin/configuracion =================
  const catalogList = [
    { id: 'departamentos', name: 'Departamentos', file: 'diag_ver_departamentos.png' },
    { id: 'areas', name: 'Áreas', file: 'diag_ver_areas.png' },
    { id: 'materiales', name: 'Materiales', file: 'diag_ver_materiales.png' },
    { id: 'marcas', name: 'Marcas', file: 'diag_ver_marcas.png' },
    { id: 'tipos', name: 'Tipos material', file: 'diag_ver_tipos.png' },
    { id: 'unidades', name: 'Unidades medida', file: 'diag_ver_unidades.png' },
    { id: 'proveedores', name: 'Proveedores', file: 'diag_ver_proveedores.png' },
    { id: 'receptores', name: 'Receptores', file: 'diag_ver_receptores.png' },
  ];

  for (const cat of catalogList) {
    try {
      console.log(`\nNavigating to /admin/configuracion (cat: ${cat.id})...`);
      await page.goto('http://127.0.0.1:8000/admin/configuracion', { waitUntil: 'networkidle2' });
      await new Promise(r => setTimeout(r, 1200));

      // Click catalog tab
      await page.evaluate((id) => {
        const btn = Array.from(document.querySelectorAll('.sigem-cat-tab')).find(b => {
          const clickAttr = b.getAttribute('wire:click') || '';
          return clickAttr.includes(id);
        });
        if (btn) btn.click();
      }, cat.id);

      await new Promise(r => setTimeout(r, 1000));

      // Click Ver Detalle in table
      const clicked = await page.evaluate(() => {
        const btn = document.querySelector('.sigem-cat-table button.sigem-cat-btn[title="Ver detalle"], .sigem-cat-table button[title*="Ver"]');
        if (btn) { btn.click(); return true; }
        return false;
      });

      if (clicked) {
        results.push(await inspectModal(cat.id, cat.name, cat.file));
      } else {
        console.error(`Could not find Ver button for catalog: ${cat.name}`);
        results.push({ resource: cat.name, opensSlideOver: false, error: 'No se encontró botón Ver' });
      }
    } catch (e) {
      console.error(`Error in catalog ${cat.name}:`, e);
      results.push({ resource: cat.name, opensSlideOver: false, error: e.message });
    }
  }

  // ================= 12. USUARIOS (/admin/users) =================
  try {
    console.log('\nNavigating to /admin/users...');
    await page.goto('http://127.0.0.1:8000/admin/users', { waitUntil: 'networkidle2' });
    await new Promise(r => setTimeout(r, 1200));

    const clicked = await page.evaluate(() => {
      const row = document.querySelector('.fi-ta-table tbody tr');
      if (!row) return false;
      const btn = row.querySelector('button[title*="Ver"], button[tooltip*="Ver"]') ||
                  Array.from(row.querySelectorAll('button')).find(b => b.innerHTML.includes('heroicon-m-eye') || b.innerHTML.includes('heroicon-o-eye'));
      if (btn) { btn.click(); return true; }
      return false;
    });

    if (clicked) {
      results.push(await inspectModal('users', 'Usuarios', 'diag_ver_users.png'));
    } else {
      console.error('Could not find Ver button in Users');
      results.push({ resource: 'Usuarios', opensSlideOver: false, error: 'No se encontró botón Ver' });
    }
  } catch (e) {
    console.error('Error in Users:', e);
    results.push({ resource: 'Usuarios', opensSlideOver: false, error: e.message });
  }

  // Write final JSON report
  const jsonReportPath = path.join(ARTIFACT_DIR, 'ver_slide_overs_diagnosis.json');
  fs.writeFileSync(jsonReportPath, JSON.stringify(results, null, 2));
  console.log(`\nComplete diagnosis written to: ${jsonReportPath}`);

  await browser.close();
  console.log('Browser closed.');
})();
