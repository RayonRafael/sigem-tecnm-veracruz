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
  await page.waitForSelector('.sigem-rep-vh button.sigem-rep-btn-primary');
  await page.click('.sigem-rep-vh button.sigem-rep-btn-primary');
  await page.waitForSelector('.fi-modal-slide-over-window', { visible: true });
  const buttonsInfo = await page.evaluate(() => {
    const footer = document.querySelector('.fi-modal-slide-over-window .fi-modal-footer');
    const actions = document.querySelector('.fi-modal-slide-over-window .fi-modal-footer-actions') || footer;
    return {
      footerClass: footer?.className,
      footerDisplay: window.getComputedStyle(footer).display,
      actionsClass: actions?.className,
      actionsDisplay: window.getComputedStyle(actions).display,
      actionsJustify: window.getComputedStyle(actions).justifyContent,
      buttons: Array.from(footer.querySelectorAll('button')).map(b => ({
        text: b.innerText.trim(),
        className: b.className,
        styleOrder: window.getComputedStyle(b).order,
        marginLeft: window.getComputedStyle(b).marginLeft,
        marginRight: window.getComputedStyle(b).marginRight,
        rect: b.getBoundingClientRect()
      }))
    };
  });
  console.log(JSON.stringify(buttonsInfo, null, 2));

  // Also check why form .fi-section has border
  const sectionCheck = await page.evaluate(() => {
    const s = document.querySelector('.fi-modal-slide-over-window form .fi-section, .fi-modal-slide-over-window .fi-section');
    return {
      classes: s?.className,
      tagName: s?.tagName,
      parentTagName: s?.parentElement?.tagName,
      parentClass: s?.parentElement?.className,
      border: window.getComputedStyle(s).border,
      boxShadow: window.getComputedStyle(s).boxShadow,
      bg: window.getComputedStyle(s).backgroundColor
    };
  });
  console.log('SECTION CHECK:', JSON.stringify(sectionCheck, null, 2));

  await browser.close();
})();

