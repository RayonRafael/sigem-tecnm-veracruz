import puppeteer from 'puppeteer-core';

(async () => {
  const browser = await puppeteer.launch({
    executablePath: 'C:\\Program Files (x86)\\Microsoft\\Edge\\Application\\msedge.exe',
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
  await new Promise(r => setTimeout(r, 1500));

  await page.click('.sigem-rep-vh button.sigem-rep-btn-primary');
  await page.waitForSelector('.fi-modal-slide-over-window', { visible: true });
  await new Promise(r => setTimeout(r, 800));

  const debug = await page.evaluate(() => {
    const modal = document.querySelector('.fi-modal-slide-over-window');
    const sections = Array.from(modal.querySelectorAll('.fi-section, section')).map(s => ({
      tagName: s.tagName,
      className: s.className,
      parentHierarchy: (() => {
        const p = [];
        let cur = s.parentElement;
        while (cur && cur !== modal) {
          p.push(`${cur.tagName}.${cur.className.split(' ').slice(0, 2).join('.')}`);
          cur = cur.parentElement;
        }
        return p;
      })(),
      border: window.getComputedStyle(s).border,
      boxShadow: window.getComputedStyle(s).boxShadow,
      bg: window.getComputedStyle(s).backgroundColor
    }));

    const footer = modal.querySelector('.fi-modal-footer');
    const buttons = Array.from(footer.querySelectorAll('button')).map(b => ({
      text: b.innerText.trim(),
      className: b.className,
      styleOrder: window.getComputedStyle(b).order,
      rect: b.getBoundingClientRect()
    }));

    return { sections, buttons };
  });

  console.log('DEBUG INFO:', JSON.stringify(debug, null, 2));

  await browser.close();
})();

