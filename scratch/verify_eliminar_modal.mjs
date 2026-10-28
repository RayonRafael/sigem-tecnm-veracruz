import puppeteer from 'puppeteer-core';
import path from 'path';

const EDGE_PATH = 'C:\\Program Files (x86)\\Microsoft\\Edge\\Application\\msedge.exe';
const ARTIFACT_DIR = 'C:\\Users\\Rafael572\\.gemini\\antigravity\\brain\\8aa8cfbb-190a-4be8-a9af-9c36fb57a5a7';

(async () => {
  const browser = await puppeteer.launch({
    executablePath: EDGE_PATH,
    headless: true,
    args: ['--no-sandbox', '--disable-setuid-sandbox']
  });
  const page = await browser.newPage();
  await page.goto('http://127.0.0.1:8000/admin/login');
  await page.type('input[type="email"]', 'admin@tecnm.edu.mx');
  await page.type('input[type="password"]', 'admin123');
  await page.click('button[type="submit"]');
  await page.waitForFunction(() => !window.location.pathname.includes('/login'));

  await page.goto('http://127.0.0.1:8000/admin/configuracion');
  await new Promise(r => setTimeout(r, 1200));

  // Click delete on first department row
  await page.evaluate(() => {
    const btns = Array.from(document.querySelectorAll('.sigem-cat-table button.sigem-cat-btn[title="Eliminar"]'));
    if (btns[0]) btns[0].click();
  });
  await page.waitForSelector('.fi-modal-window', { visible: true, timeout: 5000 });
  await new Promise(r => setTimeout(r, 800));

  const info = await page.evaluate(() => {
    const modal = document.querySelector('.fi-modal-window');
    return {
      heading: modal?.querySelector('.fi-modal-heading')?.innerText.trim(),
      description: modal?.querySelector('.fi-modal-description')?.innerText.trim(),
      buttons: Array.from(modal?.querySelectorAll('.fi-modal-footer button') || []).map(b => b.innerText.trim())
    };
  });
  console.log('Eliminar Modal Info:', JSON.stringify(info, null, 2));

  await page.screenshot({ path: path.join(ARTIFACT_DIR, 'after_cat_admin_dept_eliminar.png') });
  console.log('Saved after_cat_admin_dept_eliminar.png');

  await browser.close();
})();

