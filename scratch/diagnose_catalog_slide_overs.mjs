import puppeteer from 'puppeteer-core';
import path from 'path';

const EDGE_PATH = 'C:\\Program Files (x86)\\Microsoft\\Edge\\Application\\msedge.exe';
const ARTIFACT_DIR = 'C:\\Users\\Rafael572\\.gemini\\antigravity\\brain\\8aa8cfbb-190a-4be8-a9af-9c36fb57a5a7';

(async () => {
  const browser = await puppeteer.launch({
    executablePath: EDGE_PATH,
    headless: true,
    args: ['--no-sandbox', '--disable-setuid-sandbox', '--disable-gpu'],
    defaultViewport: { width: 1400, height: 900 }
  });

  const fullReport = {
    admin: {},
    servicio_social: {},
    css_dom_findings: {}
  };

  async function inspectOpenModal(page, contextName, screenshotName) {
    await page.waitForSelector('.fi-modal-slide-over-window', { visible: true, timeout: 5000 });
    await new Promise(r => setTimeout(r, 800));

    const details = await page.evaluate(() => {
      const modal = document.querySelector('.fi-modal-slide-over-window');
      if (!modal) return null;
      const modalRect = modal.getBoundingClientRect();

      const header = modal.querySelector('.fi-modal-header');
      const headerRect = header ? header.getBoundingClientRect() : null;
      const heading = modal.querySelector('.fi-modal-heading')?.innerText.trim() || null;
      const description = modal.querySelector('.fi-modal-description')?.innerText.trim() || null;

      // Tabs
      const tabs = Array.from(modal.querySelectorAll('.fi-tabs-item, [role="tab"]')).map(t => t.innerText.trim());

      // Sections
      const sections = Array.from(modal.querySelectorAll('.fi-section, section')).map(s => {
        const sHeader = s.querySelector('.fi-section-header, header')?.innerText.trim();
        const computed = window.getComputedStyle(s);
        return {
          title: sHeader,
          border: computed.border,
          borderRadius: computed.borderRadius,
          background: computed.backgroundColor,
          boxShadow: computed.boxShadow,
          padding: computed.padding,
          margin: computed.margin
        };
      });

      // Form / Inputs
      const form = modal.querySelector('form');
      const inputs = Array.from(modal.querySelectorAll('.fi-fo-field-wrp, .fi-in-entry-wrp')).map(f => {
        const label = f.querySelector('label, dt, .fi-fo-field-label, .fi-in-entry-wrp-label')?.innerText.trim();
        const placeholder = f.querySelector('input, textarea, select, .choices__placeholder')?.getAttribute('placeholder') || 
                            f.querySelector('.choices__placeholder')?.innerText.trim() || null;
        const prefixIcon = !!f.querySelector('.fi-input-wrp-icon, svg, [class*="prefix"]');
        const val = f.querySelector('input, textarea, dd, .fi-in-entry-wrp-content')?.value || 
                    f.querySelector('dd, .fi-in-entry-wrp-content')?.innerText.trim() || null;
        return { label, placeholder, prefixIcon, val };
      });

      // Grid / Layout
      const grids = Array.from(modal.querySelectorAll('[class*="grid"], [style*="--cols"]')).map(g => {
        const computed = window.getComputedStyle(g);
        return {
          gridTemplateColumns: computed.gridTemplateColumns,
          columnGap: computed.columnGap,
          rowGap: computed.rowGap
        };
      });

      // Footer Actions
      const footer = modal.querySelector('.fi-modal-footer, footer');
      const footerButtons = Array.from(modal.querySelectorAll('.fi-modal-footer button, .fi-modal-footer a, footer button')).map(b => {
        const computed = window.getComputedStyle(b);
        return {
          text: b.innerText.trim(),
          classes: b.className,
          bg: computed.backgroundColor,
          color: computed.color,
          order: computed.order
        };
      });

      // Double card / Nested borders check
      const cardNesting = [];
      const allCards = modal.querySelectorAll('.fi-section, .fi-fieldset, [class*="card"]');
      for (const c of allCards) {
        if (c.parentElement && c.parentElement.closest('.fi-section, .fi-fieldset, [class*="card"]')) {
          cardNesting.push({
            child: c.className,
            parent: c.parentElement.closest('.fi-section, .fi-fieldset, [class*="card"]').className
          });
        }
      }

      // Exact measures
      const computedModal = window.getComputedStyle(modal);
      const computedContent = modal.querySelector('.fi-modal-content') ? window.getComputedStyle(modal.querySelector('.fi-modal-content')) : null;

      return {
        dimensions: {
          width: modalRect.width,
          height: modalRect.height,
          padding: computedModal.padding,
          maxWidth: computedModal.maxWidth,
          contentPadding: computedContent ? computedContent.padding : null,
          contentOverflowX: computedContent ? computedContent.overflowX : null,
          contentOverflowY: computedContent ? computedContent.overflowY : null
        },
        header: {
          heading,
          description,
          height: headerRect ? headerRect.height : null
        },
        tabs,
        sectionCount: sections.length,
        sections,
        inputCount: inputs.length,
        inputs: inputs.slice(0, 10),
        grids: grids.slice(0, 5),
        footerButtons,
        cardNesting
      };
    });

    console.log(`\nReport for ${contextName}:`);
    console.log(`  Heading: ${details?.header?.heading}`);
    console.log(`  Description: ${details?.header?.description}`);
    console.log(`  Tabs: ${JSON.stringify(details?.tabs)}`);
    console.log(`  Sections: ${details?.sectionCount}`);
    console.log(`  Footer Buttons: ${details?.footerButtons?.map(b => b.text).join(' | ')}`);
    console.log(`  Nested Card Issues: ${details?.cardNesting?.length}`);

    const shotPath = path.join(ARTIFACT_DIR, screenshotName);
    await page.screenshot({ path: shotPath });
    console.log(`  Saved screenshot: ${screenshotName}`);

    // Close modal
    await page.evaluate(() => {
      const closeBtn = document.querySelector('.fi-modal-slide-over-window .fi-modal-close-btn, .fi-modal-slide-over-window button[x-on\\:click*="close"]');
      if (closeBtn) closeBtn.click();
    });
    await new Promise(r => setTimeout(r, 600));

    return details;
  }

  // ================= ADMIN PANEL =================
  const adminPage = await browser.newPage();
  console.log('Logging in as Admin...');
  await adminPage.goto('http://127.0.0.1:8000/admin/login');
  await adminPage.type('input[type="email"]', 'admin@tecnm.edu.mx');
  await adminPage.type('input[type="password"]', 'admin123');
  await adminPage.click('button[type="submit"]');
  await adminPage.waitForFunction(() => !window.location.pathname.includes('/login'));

  await adminPage.goto('http://127.0.0.1:8000/admin/configuracion');
  await new Promise(r => setTimeout(r, 1200));

  // 1. Departamentos
  // Crear
  await adminPage.click('.sigem-rep-vh button.sigem-rep-btn-primary');
  fullReport.admin.departamento_crear = await inspectOpenModal(adminPage, 'Admin Departamentos - Crear', 'diag_cat_admin_dept_crear.png');

  // Ver
  await adminPage.evaluate(() => {
    const btns = Array.from(document.querySelectorAll('.sigem-cat-table button.sigem-cat-btn[title="Ver detalle"]'));
    if (btns[0]) btns[0].click();
  });
  fullReport.admin.departamento_ver = await inspectOpenModal(adminPage, 'Admin Departamentos - Ver Detalle', 'diag_cat_admin_dept_ver.png');

  // Editar
  await adminPage.evaluate(() => {
    const btns = Array.from(document.querySelectorAll('.sigem-cat-table button.sigem-cat-btn[title="Editar"]'));
    if (btns[0]) btns[0].click();
  });
  fullReport.admin.departamento_editar = await inspectOpenModal(adminPage, 'Admin Departamentos - Editar', 'diag_cat_admin_dept_editar.png');

  // 2. Áreas
  await adminPage.evaluate(() => {
    const tab = Array.from(document.querySelectorAll('.sigem-cat-tab')).find(t => t.innerText.includes('Áreas'));
    if (tab) tab.click();
  });
  await new Promise(r => setTimeout(r, 1000));

  // Crear Área
  await adminPage.click('.sigem-rep-vh button.sigem-rep-btn-primary');
  fullReport.admin.area_crear = await inspectOpenModal(adminPage, 'Admin Áreas - Crear', 'diag_cat_admin_area_crear.png');

  // Ver Área
  await adminPage.evaluate(() => {
    const btns = Array.from(document.querySelectorAll('.sigem-cat-table button.sigem-cat-btn[title="Ver detalle"]'));
    if (btns[0]) btns[0].click();
  });
  fullReport.admin.area_ver = await inspectOpenModal(adminPage, 'Admin Áreas - Ver Detalle', 'diag_cat_admin_area_ver.png');

  // 3. Materiales
  await adminPage.evaluate(() => {
    const tab = Array.from(document.querySelectorAll('.sigem-cat-tab')).find(t => t.innerText.includes('Materiales'));
    if (tab) tab.click();
  });
  await new Promise(r => setTimeout(r, 1000));

  // Crear Material
  await adminPage.click('.sigem-rep-vh button.sigem-rep-btn-primary');
  fullReport.admin.material_crear = await inspectOpenModal(adminPage, 'Admin Materiales - Crear', 'diag_cat_admin_mat_crear.png');

  // Ver Material
  await adminPage.evaluate(() => {
    const btns = Array.from(document.querySelectorAll('.sigem-cat-table button.sigem-cat-btn[title="Ver detalle"]'));
    if (btns[0]) btns[0].click();
  });
  fullReport.admin.material_ver = await inspectOpenModal(adminPage, 'Admin Materiales - Ver Detalle', 'diag_cat_admin_mat_ver.png');

  // 4. Proveedores
  await adminPage.evaluate(() => {
    const tab = Array.from(document.querySelectorAll('.sigem-cat-tab')).find(t => t.innerText.includes('Proveedores'));
    if (tab) tab.click();
  });
  await new Promise(r => setTimeout(r, 1000));

  // Ver Proveedor
  await adminPage.evaluate(() => {
    const btns = Array.from(document.querySelectorAll('.sigem-cat-table button.sigem-cat-btn[title="Ver detalle"]'));
    if (btns[0]) btns[0].click();
  });
  fullReport.admin.proveedor_ver = await inspectOpenModal(adminPage, 'Admin Proveedores - Ver Detalle', 'diag_cat_admin_prov_ver.png');

  // 5. Receptores
  await adminPage.evaluate(() => {
    const tab = Array.from(document.querySelectorAll('.sigem-cat-tab')).find(t => t.innerText.includes('Receptores'));
    if (tab) tab.click();
  });
  await new Promise(r => setTimeout(r, 1000));

  // Ver Receptor
  await adminPage.evaluate(() => {
    const btns = Array.from(document.querySelectorAll('.sigem-cat-table button.sigem-cat-btn[title="Ver detalle"]'));
    if (btns[0]) btns[0].click();
  });
  fullReport.admin.receptor_ver = await inspectOpenModal(adminPage, 'Admin Receptores - Ver Detalle', 'diag_cat_admin_rec_ver.png');

  await adminPage.close();

  // ================= SERVICIO SOCIAL PANEL =================
  const ssContext = await browser.createBrowserContext();
  const ssPage = await ssContext.newPage();
  console.log('\nLogging in as Servicio Social...');
  await ssPage.goto('http://127.0.0.1:8000/servicio-social/login');
  await ssPage.type('input[type="email"]', 'servicio@tecnm.edu.mx');
  await ssPage.type('input[type="password"]', 'servicio123');
  await ssPage.click('button[type="submit"]');
  await ssPage.waitForFunction(() => !window.location.pathname.includes('/login'));

  await ssPage.goto('http://127.0.0.1:8000/servicio-social/catalogos');
  await new Promise(r => setTimeout(r, 1200));

  // 1. Departamentos (Ver)
  await ssPage.evaluate(() => {
    const btns = Array.from(document.querySelectorAll('.sigem-cat-table button.sigem-cat-btn[title="Ver detalle"]'));
    if (btns[0]) btns[0].click();
  });
  fullReport.servicio_social.departamento_ver = await inspectOpenModal(ssPage, 'SS Departamentos - Ver Detalle', 'diag_cat_ss_dept_ver.png');

  // 2. Áreas (Ver)
  await ssPage.evaluate(() => {
    const tab = Array.from(document.querySelectorAll('.sigem-cat-tab')).find(t => t.innerText.includes('Áreas'));
    if (tab) tab.click();
  });
  await new Promise(r => setTimeout(r, 1000));
  await ssPage.evaluate(() => {
    const btns = Array.from(document.querySelectorAll('.sigem-cat-table button.sigem-cat-btn[title="Ver detalle"]'));
    if (btns[0]) btns[0].click();
  });
  fullReport.servicio_social.area_ver = await inspectOpenModal(ssPage, 'SS Áreas - Ver Detalle', 'diag_cat_ss_area_ver.png');

  // 3. Materiales (Ver)
  await ssPage.evaluate(() => {
    const tab = Array.from(document.querySelectorAll('.sigem-cat-tab')).find(t => t.innerText.includes('Materiales'));
    if (tab) tab.click();
  });
  await new Promise(r => setTimeout(r, 1000));
  await ssPage.evaluate(() => {
    const btns = Array.from(document.querySelectorAll('.sigem-cat-table button.sigem-cat-btn[title="Ver detalle"]'));
    if (btns[0]) btns[0].click();
  });
  fullReport.servicio_social.material_ver = await inspectOpenModal(ssPage, 'SS Materiales - Ver Detalle', 'diag_cat_ss_mat_ver.png');

  await ssContext.close();
  await browser.close();

  // Write full JSON report
  const jsonReportPath = path.join(ARTIFACT_DIR, 'catalogos_diagnostic_report.json');
  await (await import('fs')).promises.writeFile(jsonReportPath, JSON.stringify(fullReport, null, 2), 'utf8');
  console.log(`\nDiagnostic finished! Full JSON saved to ${jsonReportPath}`);
})();

