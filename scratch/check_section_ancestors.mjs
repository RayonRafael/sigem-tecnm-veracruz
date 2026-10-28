import puppeteer from 'puppeteer-core';
import path from 'path';

const EDGE_PATH = 'C:\\Program Files (x86)\\Microsoft\\Edge\\Application\\msedge.exe';

(async () => {
  const browser = await puppeteer.launch({
    executablePath: EDGE_PATH,
    headless: true,
    userDataDir: path.join('scratch', 'diag-dom-sol'),
    args: ['--no-sandbox', '--disable-setuid-sandbox', '--disable-gpu'],
    defaultViewport: { width: 1440, height: 960 }
  });

  const page = await browser.newPage();
  await page.goto('http://127.0.0.1:8000/admin/login');
  await page.type('input[type="email"]', 'admin@tecnm.edu.mx');
  await page.type('input[type="password"]', 'admin123');
  await page.click('button[type="submit"]');
  await page.waitForFunction(() => !window.location.pathname.includes('/login'));

  await page.goto('http://127.0.0.1:8000/admin/solicituds');
  await new Promise(r => setTimeout(r, 1200));
  await page.evaluate(() => {
    document.querySelector('.fi-ta-table tbody tr button[wire\\:click*="\'view\'"]').click();
  });
  await page.waitForSelector('.fi-modal-slide-over-window', { visible: true });
  await new Promise(r => setTimeout(r, 600));

  const info = await page.evaluate(() => {
    const modal = document.querySelector('.fi-modal-slide-over-window');
    const firstSection = modal.querySelector('.fi-section, section');
    if (!firstSection) return null;

    // Get all ancestors
    const ancestors = [];
    let curr = firstSection.parentElement;
    while (curr && curr !== document.body) {
      ancestors.push({ tag: curr.tagName, className: curr.className });
      curr = curr.parentElement;
    }

    const comp = window.getComputedStyle(firstSection);
    return {
      sectionTag: firstSection.tagName,
      sectionClasses: firstSection.className,
      ancestors,
      border: comp.border,
      boxShadow: comp.boxShadow,
      matchingRules: Array.from(document.styleSheets).flatMap(sheet => {
        try {
          return Array.from(sheet.cssRules || []).filter(rule => rule.selectorText && firstSection.matches(rule.selectorText)).map(r => r.cssText);
        } catch (e) {
          return [];
        }
      }).slice(-5)
    };
  });

  console.log(JSON.stringify(info, null, 2));

  await browser.close();
})();
