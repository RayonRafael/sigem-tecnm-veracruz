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
  await page.waitForNavigation();
  await page.goto('http://127.0.0.1:8000/admin/configuracion');
  await page.waitForSelector('.sigem-rep-vh button.sigem-rep-btn-primary');
  await page.click('.sigem-rep-vh button.sigem-rep-btn-primary');
  await page.waitForSelector('.fi-modal-slide-over-window', { visible: true });
  const footerHtml = await page.evaluate(() => document.querySelector('.fi-modal-footer')?.outerHTML);
  console.log('FOOTER HTML:\n', footerHtml);

  // Also check Ver modal footer
  await page.evaluate(() => {
    document.querySelector('.fi-modal-slide-over-window .fi-modal-close-btn')?.click();
  });
  await new Promise(r => setTimeout(r, 600));

  await page.evaluate(() => {
    document.querySelector('.sigem-cat-table button.sigem-cat-btn[title="Ver detalle"]')?.click();
  });
  await page.waitForSelector('.fi-modal-slide-over-window', { visible: true });
  const footerVerHtml = await page.evaluate(() => document.querySelector('.fi-modal-footer')?.outerHTML);
  console.log('FOOTER VER HTML:\n', footerVerHtml);

  await browser.close();
})();

