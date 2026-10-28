import puppeteer from 'puppeteer-core';
import path from 'path';
import fs from 'fs';

const EDGE_PATH = 'C:\\Program Files (x86)\\Microsoft\\Edge\\Application\\msedge.exe';
const ARTIFACT_DIR = 'C:\\Users\\Rafael572\\.gemini\\antigravity\\brain\\8aa8cfbb-190a-4be8-a9af-9c36fb57a5a7';

(async () => {
  const browser = await puppeteer.launch({
    executablePath: EDGE_PATH,
    headless: true,
    userDataDir: path.join('scratch', 'diag-profile-rem-' + Date.now()),
    args: ['--no-sandbox', '--disable-setuid-sandbox', '--disable-gpu'],
    defaultViewport: { width: 1440, height: 960 }
  });

  const page = await browser.newPage();

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

  async function inspectModal(resourceKey, resourceName, screenshotFilename) {
    console.log(`\n========================================`);
    console.log(`Inspecting: ${resourceName} (${resourceKey})`);
    
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

    const analysis = await page.evaluate((name) => {
      const modal = document.querySelector('.fi-modal-slide-over-window');
      if (!modal) return { opensSlideOver: false };

      const modalRect = modal.getBoundingClientRect();
      const contentEl = modal.querySelector('.fi-modal-content') || modal.querySelector('.fi-modal-window') || modal;
      const contentComputed = window.getComputedStyle(contentEl);

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

      const tabElements = Array.from(modal.querySelectorAll('.fi-tabs-item, [role="tab"]'));
      const tabs = tabElements.map(t => t.innerText.trim());

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

      const gridElements = Array.from(modal.querySelectorAll('[class*="grid"], [style*="--cols"]'));
      const grids = gridElements.map(g => {
        const comp = window.getComputedStyle(g);
        return {
          columns: comp.gridTemplateColumns,
          colCount: comp.gridTemplateColumns ? comp.gridTemplateColumns.split(' ').length : 1,
          gap: comp.columnGap
        };
      });

      const contentScrollOverflow = (contentEl.scrollWidth > contentEl.clientWidth + 2);
      const modalScrollOverflow = (modal.scrollWidth > modal.clientWidth + 2);

      const overflowingElements = [];
      const allDescendants = Array.from(modal.querySelectorAll('*'));

      for (const el of allDescendants) {
        if (['SCRIPT', 'STYLE', 'PATH', 'SVG'].includes(el.tagName)) continue;

        const rect = el.getBoundingClientRect();
        if (rect.width === 0 || rect.height === 0) continue;

        let isOver = false;
        let reason = [];

        if (rect.right > modalRect.right - 2 && rect.width < modalRect.width + 10) {
          isOver = true;
          reason.push(`Excede ancho modal (${Math.round(rect.right - modalRect.right)}px afuera)`);
        }

        if (el.scrollWidth > el.clientWidth + 3) {
          const comp = window.getComputedStyle(el);
          if (comp.overflow !== 'visible' && !['HTML', 'BODY'].includes(el.tagName) && el !== contentEl) {
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

          el.style.outline = '2px dashed #ef4444';
          el.style.outlineOffset = '1px';
          el.style.backgroundColor = 'rgba(239, 68, 68, 0.08)';
          el.setAttribute('data-overflow-detected', reason.join('; '));
        }
      }

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
          elements: overflowingElements.slice(0, 10)
        },
        footer: {
          status: footerStatus,
          buttonCount: buttons.length,
          buttons: buttons.map(b => b.text)
        }
      };
    }, resourceName);

    const shotPath = path.join(ARTIFACT_DIR, screenshotFilename);
    await page.screenshot({ path: shotPath });
    console.log(`Saved screenshot: ${screenshotFilename}`);

    await page.evaluate(() => {
      const closeBtn = document.querySelector('.fi-modal-slide-over-window .fi-modal-close-btn, .fi-modal-slide-over-window button[x-on*="close"]');
      if (closeBtn) closeBtn.click();
    });
    await new Promise(r => setTimeout(r, 600));

    return analysis;
  }

  await login();

  const extraResults = {};

  // 1. SOLICITUDES
  try {
    console.log('\nNavigating to /admin/solicituds...');
    await page.goto('http://127.0.0.1:8000/admin/solicituds', { waitUntil: 'networkidle2' });
    await new Promise(r => setTimeout(r, 1200));

    const clicked = await page.evaluate(() => {
      const row = document.querySelector('.fi-ta-table tbody tr');
      if (!row) return false;
      const btn = row.querySelector('button[wire\\:click*="\'view\'"]') ||
                  row.querySelector('button[title*="Ver"]') ||
                  Array.from(row.querySelectorAll('button')).find(b => b.innerText.includes('Ver') || b.innerHTML.includes('Ver'));
      if (btn) { btn.click(); return true; }
      return false;
    });

    if (clicked) {
      extraResults['Solicitudes'] = await inspectModal('solicitudes', 'Solicitudes', 'diag_ver_solicituds.png');
    }
  } catch (e) {
    console.error('Error in Solicitudes:', e);
  }

  // 2. MANTENIMIENTOS
  try {
    console.log('\nNavigating to /admin/mantenimientos...');
    await page.goto('http://127.0.0.1:8000/admin/mantenimientos', { waitUntil: 'networkidle2' });
    await new Promise(r => setTimeout(r, 1200));

    const clicked = await page.evaluate(() => {
      const row = document.querySelector('.fi-ta-table tbody tr');
      if (!row) return false;
      const btn = row.querySelector('button[wire\\:click*="\'view\'"]') ||
                  row.querySelector('button[title*="Ver"]') ||
                  Array.from(row.querySelectorAll('button')).find(b => b.innerText.includes('Ver') || b.innerHTML.includes('Ver'));
      if (btn) { btn.click(); return true; }
      return false;
    });

    if (clicked) {
      extraResults['Mantenimientos'] = await inspectModal('mantenimientos', 'Mantenimientos', 'diag_ver_mantenimientos.png');
    }
  } catch (e) {
    console.error('Error in Mantenimientos:', e);
  }

  // 3. USUARIOS
  try {
    console.log('\nNavigating to /admin/users...');
    await page.goto('http://127.0.0.1:8000/admin/users', { waitUntil: 'networkidle2' });
    await new Promise(r => setTimeout(r, 1200));

    const clicked = await page.evaluate(() => {
      const row = document.querySelector('.fi-ta-table tbody tr');
      if (!row) return false;
      const btn = row.querySelector('button[wire\\:click*="\'view\'"]') ||
                  row.querySelector('button[title*="Ver"]') ||
                  Array.from(row.querySelectorAll('button')).find(b => b.innerText.includes('Ver') || b.innerHTML.includes('Ver'));
      if (btn) { btn.click(); return true; }
      return false;
    });

    if (clicked) {
      extraResults['Usuarios'] = await inspectModal('users', 'Usuarios', 'diag_ver_users.png');
    }
  } catch (e) {
    console.error('Error in Usuarios:', e);
  }

  // Merge into ver_slide_overs_diagnosis.json
  const jsonReportPath = path.join(ARTIFACT_DIR, 'ver_slide_overs_diagnosis.json');
  let currentList = [];
  if (fs.existsSync(jsonReportPath)) {
    currentList = JSON.parse(fs.readFileSync(jsonReportPath, 'utf8'));
  }

  // Replace or update entries
  for (const [resName, data] of Object.entries(extraResults)) {
    const idx = currentList.findIndex(item => item.resource === resName);
    if (idx >= 0) {
      currentList[idx] = data;
    } else {
      currentList.push(data);
    }
  }

  fs.writeFileSync(jsonReportPath, JSON.stringify(currentList, null, 2));
  console.log(`\nUpdated diagnosis written to: ${jsonReportPath}`);

  await browser.close();
})();

