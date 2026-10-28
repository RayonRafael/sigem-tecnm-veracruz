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

  async function takeModalShot(page, label, shotName) {
    await page.waitForSelector('.fi-modal-slide-over-window', { visible: true, timeout: 5000 });
    await new Promise(r => setTimeout(r, 800));

    const info = await page.evaluate(() => {
      const modal = document.querySelector('.fi-modal-slide-over-window');
      const heading = modal?.querySelector('.fi-modal-heading')?.innerText.trim();
      const description = modal?.querySelector('.fi-modal-description')?.innerText.trim();
      const tabs = Array.from(modal?.querySelectorAll('.fi-tabs-item, [role="tab"]') || []).map(t => t.innerText.trim());
      const buttons = Array.from(modal?.querySelectorAll('.fi-modal-footer button, .fi-modal-footer a') || []).map(b => b.innerText.trim());
      const hasCardBorders = Array.from(modal?.querySelectorAll('form .fi-section') || []).some(s => {
        return window.getComputedStyle(s).borderWidth !== '0px' && window.getComputedStyle(s).borderStyle !== 'none';
      });
      return { heading, description, tabs, buttons, hasCardBorders };
    });

    console.log(`\n[${label}]`);
    console.log(`  Heading: ${info.heading}`);
    console.log(`  Description: ${info.description}`);
    console.log(`  Tabs: ${JSON.stringify(info.tabs)}`);
    console.log(`  Footer Buttons: ${info.buttons.join(' | ')}`);
    console.log(`  Form has card borders: ${info.hasCardBorders}`);

    const shotPath = path.join(ARTIFACT_DIR, shotName);
    await page.screenshot({ path: shotPath });
    console.log(`  Saved screenshot: ${shotName}`);

    // Close modal
    await page.evaluate(() => {
      const closeBtn = document.querySelector('.fi-modal-slide-over-window .fi-modal-close-btn, .fi-modal-slide-over-window button[x-on\\:click*="close"]');
      if (closeBtn) closeBtn.click();
    });
    await new Promise(r => setTimeout(r, 600));
  }

  // ================= ADMIN =================
  const adminPage = await browser.newPage();
  console.log('Logging in as Admin...');
  await adminPage.goto('http://127.0.0.1:8000/admin/login');
  await adminPage.type('input[type="email"]', 'admin@tecnm.edu.mx');
  await adminPage.type('input[type="password"]', 'admin123');
  await adminPage.click('button[type="submit"]');
  await adminPage.waitForFunction(() => !window.location.pathname.includes('/login'));

  await adminPage.goto('http://127.0.0.1:8000/admin/configuracion');
  await new Promise(r => setTimeout(r, 1200));

  // 1. Admin - Crear Departamento
  await adminPage.click('.sigem-rep-vh button.sigem-rep-btn-primary');
  await takeModalShot(adminPage, 'Admin Departamentos - Crear', 'after_cat_admin_dept_crear.png');

  // 2. Admin - Ver Detalle Departamento
  await adminPage.evaluate(() => {
    const btns = Array.from(document.querySelectorAll('.sigem-cat-table button.sigem-cat-btn[title="Ver detalle"]'));
    if (btns[0]) btns[0].click();
  });
  await takeModalShot(adminPage, 'Admin Departamentos - Ver Detalle', 'after_cat_admin_dept_ver.png');

  // 3. Admin - Editar Departamento
  await adminPage.evaluate(() => {
    const btns = Array.from(document.querySelectorAll('.sigem-cat-table button.sigem-cat-btn[title="Editar"]'));
    if (btns[0]) btns[0].click();
  });
  await takeModalShot(adminPage, 'Admin Departamentos - Editar', 'after_cat_admin_dept_editar.png');

  // 4. Admin - Ver Detalle Material (Tabs: Información + Control de stock)
  await adminPage.evaluate(() => {
    const tab = Array.from(document.querySelectorAll('.sigem-cat-tab')).find(t => t.innerText.includes('Materiales'));
    if (tab) tab.click();
  });
  await new Promise(r => setTimeout(r, 1000));
  await adminPage.evaluate(() => {
    const btns = Array.from(document.querySelectorAll('.sigem-cat-table button.sigem-cat-btn[title="Ver detalle"]'));
    if (btns[0]) btns[0].click();
  });
  await takeModalShot(adminPage, 'Admin Materiales - Ver Detalle', 'after_cat_admin_mat_ver.png');

  // 5. Admin - Crear Material (Wizard sin bordes dobles)
  await adminPage.click('.sigem-rep-vh button.sigem-rep-btn-primary');
  await takeModalShot(adminPage, 'Admin Materiales - Crear', 'after_cat_admin_mat_crear.png');

  await adminPage.close();

  // ================= SERVICIO SOCIAL =================
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

  // 6. SS - Ver Detalle Departamento
  await ssPage.evaluate(() => {
    const btns = Array.from(document.querySelectorAll('.sigem-cat-table button.sigem-cat-btn[title="Ver detalle"]'));
    if (btns[0]) btns[0].click();
  });
  await takeModalShot(ssPage, 'SS Departamentos - Ver Detalle', 'after_cat_ss_dept_ver.png');

  // 7. SS - Ver Detalle Material
  await ssPage.evaluate(() => {
    const tab = Array.from(document.querySelectorAll('.sigem-cat-tab')).find(t => t.innerText.includes('Materiales'));
    if (tab) tab.click();
  });
  await new Promise(r => setTimeout(r, 1000));
  await ssPage.evaluate(() => {
    const btns = Array.from(document.querySelectorAll('.sigem-cat-table button.sigem-cat-btn[title="Ver detalle"]'));
    if (btns[0]) btns[0].click();
  });
  await takeModalShot(ssPage, 'SS Materiales - Ver Detalle', 'after_cat_ss_mat_ver.png');

  await ssContext.close();
  await browser.close();

  console.log('\nVerification complete!');
})();

