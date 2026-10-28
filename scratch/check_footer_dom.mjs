import puppeteer from 'puppeteer-core';
import path from 'path';

const EDGE_PATH = 'C:\\Program Files (x86)\\Microsoft\\Edge\\Application\\msedge.exe';

(async () => {
  const browser = await puppeteer.launch({
    executablePath: EDGE_PATH,
    headless: true,
    userDataDir: path.join('scratch', 'diag-footer-test'),
    args: ['--no-sandbox', '--disable-setuid-sandbox', '--disable-gpu'],
    defaultViewport: { width: 1440, height: 960 }
  });

  const page = await browser.newPage();
  await page.goto('http://127.0.0.1:8000/admin/login');
  await page.type('input[type="email"]', 'admin@tecnm.edu.mx');
  await page.type('input[type="password"]', 'admin123');
  await page.click('button[type="submit"]');
  await page.waitForFunction(() => !window.location.pathname.includes('/login'));

  await page.goto('http://127.0.0.1:8000/admin/solicituds', { waitUntil: 'networkidle2' });
  await new Promise(r => setTimeout(r, 1200));

  await page.evaluate(() => {
    const btn = document.querySelector('.fi-ta-table tbody tr button[wire\\:click*="\'view\'"]');
    if (btn) btn.click();
  });

  await page.waitForSelector('.fi-modal-slide-over-window', { visible: true });
  await new Promise(r => setTimeout(r, 800));

  const footerDetails = await page.evaluate(() => {
    const modal = document.querySelector('.fi-modal-slide-over-window');
    const footer = modal.querySelector('.fi-modal-footer');
    const btn = footer?.querySelector('button');
    return {
      modalClasses: modal.className,
      hasForm: !!modal.querySelector('form'),
      hasFoCtn: !!modal.querySelector('.fi-fo-component-ctn'),
      footerHTML: footer?.outerHTML,
      footerStyle: footer ? {
        display: window.getComputedStyle(footer).display,
        justifyContent: window.getComputedStyle(footer).justifyContent,
        width: window.getComputedStyle(footer).width,
      } : null,
      btnStyle: btn ? {
        marginLeft: window.getComputedStyle(btn).marginLeft,
        marginRight: window.getComputedStyle(btn).marginRight,
        order: window.getComputedStyle(btn).order,
        classes: btn.className,
      } : null
    };
  });

  console.log(JSON.stringify(footerDetails, null, 2));

  await browser.close();
})();

