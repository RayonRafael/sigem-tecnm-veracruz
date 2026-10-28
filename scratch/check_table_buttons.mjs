import puppeteer from 'puppeteer-core';
import path from 'path';

const EDGE_PATH = 'C:\\Program Files (x86)\\Microsoft\\Edge\\Application\\msedge.exe';

(async () => {
  const browser = await puppeteer.launch({
    executablePath: EDGE_PATH,
    headless: true,
    userDataDir: path.join('scratch', 'diag-profile-check'),
    args: ['--no-sandbox', '--disable-setuid-sandbox', '--disable-gpu'],
    defaultViewport: { width: 1440, height: 960 }
  });

  const page = await browser.newPage();
  await page.goto('http://127.0.0.1:8000/admin/login');
  await page.type('input[type="email"]', 'admin@tecnm.edu.mx');
  await page.type('input[type="password"]', 'admin123');
  await page.click('button[type="submit"]');
  await page.waitForFunction(() => !window.location.pathname.includes('/login'));

  for (const url of ['/admin/solicituds', '/admin/mantenimientos', '/admin/users']) {
    console.log(`\nChecking URL: ${url}`);
    await page.goto(`http://127.0.0.1:8000${url}`, { waitUntil: 'networkidle2' });
    await new Promise(r => setTimeout(r, 1200));

    const info = await page.evaluate(() => {
      const rows = Array.from(document.querySelectorAll('.fi-ta-table tbody tr'));
      if (rows.length === 0) return { rowCount: 0, html: document.querySelector('.fi-ta-table')?.outerHTML || 'no table' };
      
      const firstRow = rows[0];
      const buttons = Array.from(firstRow.querySelectorAll('button, a')).map(b => ({
        tag: b.tagName,
        title: b.getAttribute('title'),
        ariaLabel: b.getAttribute('aria-label'),
        xTooltip: b.getAttribute('x-tooltip'),
        wireClick: b.getAttribute('wire:click'),
        classes: b.className,
        innerHTML: b.innerHTML.slice(0, 100)
      }));

      return {
        rowCount: rows.length,
        buttons
      };
    });

    console.log(JSON.stringify(info, null, 2));
  }

  await browser.close();
})();

