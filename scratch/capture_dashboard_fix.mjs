import puppeteer from 'puppeteer-core';

(async () => {
    try {
        console.log('Launching Edge...');
        const browser = await puppeteer.launch({
            executablePath: 'C:\\Program Files (x86)\\Microsoft\\Edge\\Application\\msedge.exe',
            headless: true,
            args: ['--no-sandbox', '--disable-setuid-sandbox', '--disable-gpu'],
            defaultViewport: { width: 1440, height: 900 }
        });

        const page = await browser.newPage();
        await page.emulateMediaFeatures([{ name: 'prefers-color-scheme', value: 'light' }]);

        console.log('Navigating to login...');
        await page.goto('http://127.0.0.1:8000/admin/login', { waitUntil: 'networkidle2' });

        console.log('Filling login credentials...');
        await page.type('input[type="email"]', 'admin@tecnm.edu.mx');
        await page.type('input[type="password"]', 'admin123');
        await Promise.all([
            page.waitForNavigation({ waitUntil: 'networkidle2' }),
            page.click('button[type="submit"]')
        ]);

        console.log('Current URL after login:', page.url());

        console.log('Ensuring light mode theme...');
        await page.evaluate(() => {
            localStorage.setItem('theme', 'light');
            document.documentElement.classList.remove('dark');
        });

        console.log('Waiting for dashboard content...');
        await page.waitForSelector('.sigem-hero', { timeout: 10000 });

        // Small wait for all styles and elements to settle
        await new Promise(r => setTimeout(r, 1500));

        const outputPath = 'C:\\Users\\Rafael572\\.gemini\\antigravity-ide\\brain\\8aa8cfbb-190a-4be8-a9af-9c36fb57a5a7\\dashboard_fix_densidad.png';
        console.log('Capturing screenshot to:', outputPath);
        await page.screenshot({ path: outputPath, fullPage: false });

        console.log('SUCCESS! Screenshot captured.');
        await browser.close();
        process.exit(0);
    } catch (err) {
        console.error('Error during capture:', err);
        process.exit(1);
    }
})();
