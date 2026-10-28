import puppeteer from 'puppeteer-core';
import path from 'path';

const EDGE_PATH = 'C:\\Program Files (x86)\\Microsoft\\Edge\\Application\\msedge.exe';
const ARTIFACT_DIR = 'C:\\Users\\Rafael572\\.gemini\\antigravity\\brain\\8aa8cfbb-190a-4be8-a9af-9c36fb57a5a7';

(async () => {
  const browser = await puppeteer.launch({
    executablePath: EDGE_PATH,
    headless: true,
    userDataDir: path.join('scratch', 'diag-profile-verify-' + Date.now()),
    args: ['--no-sandbox', '--disable-setuid-sandbox', '--disable-gpu'],
    defaultViewport: { width: 1440, height: 960 }
  });

  async function inspectAndShoot(page, name, filename) {
    await page.waitForSelector('.fi-modal-slide-over-window', { visible: true, timeout: 6000 });
    await new Promise(r => setTimeout(r, 800));

    const check = await page.evaluate(() => {
      const modal = document.querySelector('.fi-modal-slide-over-window');
      const sections = Array.from(modal.querySelectorAll('.fi-section, section')).map(s => {
        const comp = window.getComputedStyle(s);
        return {
          border: comp.border,
          boxShadow: comp.boxShadow,
          bgColor: comp.backgroundColor,
          padding: comp.padding
        };
      });

      const buttons = Array.from(modal.querySelectorAll('.fi-modal-footer button, footer button')).map(b => {
        const comp = window.getComputedStyle(b);
        const rect = b.getBoundingClientRect();
        return {
          text: b.innerText.trim(),
          marginLeft: comp.marginLeft,
          marginRight: comp.marginRight,
          order: comp.order,
          left: Math.round(rect.left),
          right: Math.round(rect.right)
        };
      });

      const modalRect = modal.getBoundingClientRect();

      return {
        sectionCount: sections.length,
        hasBorderOrShadow: sections.some(s => s.border !== '0px none rgb(17, 24, 39)' && s.boxShadow !== 'none'),
        sections: sections.slice(0, 3),
        modalWidth: Math.round(modalRect.width),
        buttonCount: buttons.length,
        buttons
      };
    });

    console.log(`\nCheck for ${name}:`);
    console.log(`  Sections count: ${check.sectionCount}, Double border/shadow: ${check.hasBorderOrShadow}`);
    console.log(`  Buttons: ${check.buttons.map(b => `${b.text} (order: ${b.order}, L: ${b.left}, R: ${b.right})`).join(' | ')}`);

    const shotPath = path.join(ARTIFACT_DIR, filename);
    await page.screenshot({ path: shotPath });
    console.log(`  Saved screenshot: ${filename}`);

    // Close modal
    await page.evaluate(() => {
      const closeBtn = document.querySelector('.fi-modal-slide-over-window .fi-modal-close-btn, .fi-modal-slide-over-window button[x-on*="close"]');
      if (closeBtn) closeBtn.click();
    });
    await new Promise(r => setTimeout(r, 600));
    return check;
  }

  // ================= ADMIN CONTEXT =================
  const adminPage = await browser.newPage();
  console.log('Logging in as Admin...');
  await adminPage.goto('http://127.0.0.1:8000/admin/login', { waitUntil: 'networkidle2' });
  await adminPage.type('input[type="email"]', 'admin@tecnm.edu.mx');
  await adminPage.type('input[type="password"]', 'admin123');
  await adminPage.click('button[type="submit"]');
  await adminPage.waitForFunction(() => !window.location.pathname.includes('/login'), { timeout: 10000 });
  console.log('Logged in as Admin successfully.');

  // 1. Solicitudes
  console.log('\nNavigating to /admin/solicituds...');
  await adminPage.goto('http://127.0.0.1:8000/admin/solicituds', { waitUntil: 'networkidle2' });
  await new Promise(r => setTimeout(r, 1200));
  await adminPage.evaluate(() => {
    const btn = document.querySelector('.fi-ta-table tbody tr button[wire\\:click*="\'view\'"]');
    if (btn) btn.click();
  });
  await inspectAndShoot(adminPage, 'Solicitudes Ver', 'fase1_admin_solicitudes_ver.png');

  // 2. Mantenimientos
  console.log('\nNavigating to /admin/mantenimientos...');
  await adminPage.goto('http://127.0.0.1:8000/admin/mantenimientos', { waitUntil: 'networkidle2' });
  await new Promise(r => setTimeout(r, 1200));
  await adminPage.evaluate(() => {
    const btn = document.querySelector('.fi-ta-table tbody tr button[wire\\:click*="\'view\'"]');
    if (btn) btn.click();
  });
  await inspectAndShoot(adminPage, 'Mantenimientos Ver', 'fase1_admin_mantenimientos_ver.png');

  // 3. Usuarios
  console.log('\nNavigating to /admin/users...');
  await adminPage.goto('http://127.0.0.1:8000/admin/users', { waitUntil: 'networkidle2' });
  await new Promise(r => setTimeout(r, 1200));
  await adminPage.evaluate(() => {
    const btn = document.querySelector('.fi-ta-table tbody tr button[wire\\:click*="\'view\'"]');
    if (btn) btn.click();
  });
  await inspectAndShoot(adminPage, 'Usuarios Ver', 'fase1_admin_users_ver.png');

  // 4. Catálogos (Departamentos)
  console.log('\nNavigating to /admin/configuracion (Departamentos)...');
  await adminPage.goto('http://127.0.0.1:8000/admin/configuracion', { waitUntil: 'networkidle2' });
  await new Promise(r => setTimeout(r, 1200));
  await adminPage.evaluate(() => {
    const btn = document.querySelector('.sigem-cat-table button.sigem-cat-btn[title="Ver detalle"]');
    if (btn) btn.click();
  });
  await inspectAndShoot(adminPage, 'Catálogos Admin (Departamentos) Ver', 'fase1_admin_catalogos_dept_ver.png');

  // 5. Inventario
  console.log('\nNavigating to /admin/inventarios...');
  await adminPage.goto('http://127.0.0.1:8000/admin/inventarios', { waitUntil: 'networkidle2' });
  await new Promise(r => setTimeout(r, 1200));
  await adminPage.evaluate(() => {
    const btn = document.querySelector('.fi-ta-table tbody tr button[title*="Ver"]');
    if (btn) btn.click();
  });
  await inspectAndShoot(adminPage, 'Inventarios Ver', 'fase1_admin_inventarios_ver.png');

  // ================= SERVICIO SOCIAL CONTEXT =================
  console.log('\nCreating isolated context for Servicio Social...');
  const ssContext = await browser.createBrowserContext();
  const ssPage = await ssContext.newPage();
  console.log('Logging in as Servicio Social...');
  await ssPage.goto('http://127.0.0.1:8000/servicio-social/login', { waitUntil: 'networkidle2' });
  await ssPage.type('input[type="email"]', 'servicio@tecnm.edu.mx');
  await ssPage.type('input[type="password"]', 'servicio123');
  await ssPage.click('button[type="submit"]');
  await ssPage.waitForFunction(() => !window.location.pathname.includes('/login'), { timeout: 10000 });
  console.log('Logged in as Servicio Social successfully.');

  console.log('Navigating to /servicio-social/catalogos...');
  await ssPage.goto('http://127.0.0.1:8000/servicio-social/catalogos', { waitUntil: 'networkidle2' });
  await new Promise(r => setTimeout(r, 1200));
  await ssPage.evaluate(() => {
    const btn = document.querySelector('.sigem-cat-table button.sigem-cat-btn[title="Ver detalle"]');
    if (btn) btn.click();
  });
  await inspectAndShoot(ssPage, 'Servicio Social Catálogos Ver', 'fase1_ss_catalogos_dept_ver.png');

  await browser.close();
  console.log('\nVerification complete.');
})();
