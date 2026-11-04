/**
 * SIGEM - Script de Capturas Automatizadas Responsivas Fase 0a
 * 9 pantallas × 4 tamaños = 36 capturas PNG
 */

const fs = require('fs');
const path = require('path');
const http = require('http');
const { spawn } = require('child_process');
const puppeteer = require('puppeteer');

// Configuración de tamaños
const VIEWPORTS = {
    desktop: { width: 1920, height: 1080, label: 'Monitor grande' },
    laptop:  { width: 1366, height: 768,  label: 'Laptop típica' },
    tablet:  { width: 768,  height: 1024, label: 'iPad' },
    movil:   { width: 375,  height: 667,  label: 'iPhone/Galaxy' }
};

// Directorios de salida
const OUTPUT_DIR = path.resolve(__dirname, 'screenshots');
const BRAIN_DIR = 'C:\\Users\\Rafael572\\.gemini\\antigravity\\brain\\54e356bd-87b7-471c-8e27-2c2e9bbc955c\\screenshots';

if (!fs.existsSync(OUTPUT_DIR)) {
    fs.mkdirSync(OUTPUT_DIR, { recursive: true });
}
if (!fs.existsSync(BRAIN_DIR)) {
    fs.mkdirSync(BRAIN_DIR, { recursive: true });
}

function getPhpBinary() {
    const defaultHerd = 'C:\\Users\\Rafael572\\.config\\herd\\bin\\php84\\php.exe';
    if (fs.existsSync(defaultHerd)) {
        return defaultHerd;
    }
    return process.env.PHP_BINARY || 'php';
}

function wait(ms) {
    return new Promise(resolve => setTimeout(resolve, ms));
}

function probeServer(url, retries = 20) {
    return new Promise((resolve, reject) => {
        const req = http.get(url, (res) => {
            resolve(res.statusCode);
        });
        req.on('error', (err) => {
            if (retries > 0) {
                setTimeout(() => {
                    probeServer(url, retries - 1).then(resolve).catch(reject);
                }, 500);
            } else {
                reject(err);
            }
        });
    });
}

async function startServer() {
    console.log('[Server] Verificando si el servidor PHP ya está respondiendo...');
    try {
        await probeServer('http://127.0.0.1:8000/admin/login', 1);
        console.log('[Server] Servidor existente detectado en puerto 8000.');
        return null;
    } catch {
        // Iniciar nuevo servidor
    }

    const phpBin = getPhpBinary();
    const routerScript = path.resolve(__dirname, 'vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php');
    console.log(`[Server] Iniciando PHP server con 4 workers (${phpBin})...`);

    const serverProcess = spawn(phpBin, ['-S', '127.0.0.1:8000', routerScript], {
        cwd: path.resolve(__dirname, 'public'),
        env: { ...process.env, PHP_CLI_SERVER_WORKERS: '4' },
        stdio: ['ignore', 'pipe', 'pipe']
    });

    serverProcess.on('error', (err) => {
        console.error('[Server Error]', err);
    });

    console.log('[Server] Esperando disponibilidad en http://127.0.0.1:8000...');
    await probeServer('http://127.0.0.1:8000/admin/login', 25);
    console.log('[Server] Servidor listo.');
    return serverProcess;
}

async function run() {
    const results = [];
    let serverProcess = null;
    let browser = null;

    try {
        serverProcess = await startServer();

        console.log('[Puppeteer] Lanzando navegador Chromium...');
        browser = await puppeteer.launch({
            headless: 'new',
            args: [
                '--no-sandbox',
                '--disable-setuid-sandbox',
                '--disable-dev-shm-usage',
                '--disable-gpu'
            ]
        });

        const page = await browser.newPage();

        // -------------------------------------------------------------
        // 1. PANTALLA 9: Login (sin sesión previa)
        // -------------------------------------------------------------
        console.log('\n========================================');
        console.log('Pantalla 9/9: Login (/admin/login)');
        console.log('========================================');
        await page.setViewport(VIEWPORTS.desktop);
        await page.goto('http://127.0.0.1:8000/admin/login', { waitUntil: 'domcontentloaded' });
        await wait(2500);

        for (const [sizeName, vp] of Object.entries(VIEWPORTS)) {
            const filename = `login_${sizeName}.png`;
            const destPath = path.join(OUTPUT_DIR, filename);
            const brainPath = path.join(BRAIN_DIR, filename);

            try {
                console.log(`  -> Capturando [login] en ${sizeName} (${vp.width}x${vp.height})...`);
                await page.setViewport({ width: vp.width, height: vp.height });
                await wait(600);
                await page.screenshot({ path: destPath });
                fs.copyFileSync(destPath, brainPath);
                const stat = fs.statSync(destPath);
                results.push({ screen: 'Login', size: sizeName, file: filename, sizeBytes: stat.size, status: 'OK' });
            } catch (err) {
                console.error(`  Error en login (${sizeName}):`, err.message);
                results.push({ screen: 'Login', size: sizeName, file: filename, sizeBytes: 0, status: `ERROR: ${err.message}` });
            }
        }

        // -------------------------------------------------------------
        // 2. Autenticación como Administrador (una sola vez)
        // -------------------------------------------------------------
        console.log('\n[Auth] Autenticando con admin@tecnm.edu.mx...');
        await page.setViewport(VIEWPORTS.desktop);
        await page.goto('http://127.0.0.1:8000/admin/login', { waitUntil: 'domcontentloaded' });
        await page.waitForSelector('input[type="email"], input[name*="email"]', { timeout: 10000 });
        await page.type('input[type="email"], input[name*="email"]', 'admin@tecnm.edu.mx');
        await page.type('input[type="password"]', 'admin123');

        await Promise.all([
            page.waitForNavigation({ waitUntil: 'domcontentloaded' }),
            page.click('button[type="submit"]')
        ]);
        console.log(`[Auth] Autenticado correctamente. URL actual: ${page.url()}`);
        await wait(2000);

        // -------------------------------------------------------------
        // Definición de las pantallas principales autenticadas
        // -------------------------------------------------------------
        const screens = [
            { id: 'dashboard', name: 'Dashboard Admin', url: 'http://127.0.0.1:8000/admin', action: null },
            { id: 'inventario', name: 'Inventario', url: 'http://127.0.0.1:8000/admin/inventarios', action: null },
            { id: 'solicitudes', name: 'Solicitudes', url: 'http://127.0.0.1:8000/admin/solicituds', action: null },
            { id: 'mantenimientos', name: 'Mantenimientos', url: 'http://127.0.0.1:8000/admin/mantenimientos', action: null },
            { id: 'slideover_ver_inventario', name: 'Slide-over Ver Inventario', url: 'http://127.0.0.1:8000/admin/inventarios', action: 'view' },
            { id: 'slideover_editar_inventario', name: 'Slide-over Editar Inventario', url: 'http://127.0.0.1:8000/admin/inventarios', action: 'edit' },
            { id: 'reportes', name: 'Reportes', url: 'http://127.0.0.1:8000/admin/reportes', action: null },
            { id: 'configuracion', name: 'Configuración', url: 'http://127.0.0.1:8000/admin/configuracion', action: null }
        ];

        let screenIndex = 1;
        for (const scr of screens) {
            console.log(`\n========================================`);
            console.log(`Pantalla ${screenIndex++}/9: ${scr.name} (${scr.url})`);
            console.log(`========================================`);

            // a. Navegar a la URL con viewport base desktop
            await page.setViewport(VIEWPORTS.desktop);
            await page.goto(scr.url, { waitUntil: 'domcontentloaded' });
            await wait(2500);

            // b. Si es slide-over, hacer clic en el botón correspondiente
            if (scr.action) {
                const btnSelector = scr.action === 'view'
                    ? 'button[wire\\:click*="\'view\'"], button[title="Ver detalle"]'
                    : 'button[wire\\:click*="\'edit\'"], button[title="Editar"]';

                console.log(`  -> Abriendo slide-over (${scr.action})...`);
                await page.waitForSelector(btnSelector, { timeout: 10000 });
                const btn = await page.$(btnSelector);
                await btn.click();
                // c. Esperar a que cargue el slide-over
                await wait(2500);
            }

            // d. Para cada tamaño: setViewport + screenshot
            for (const [sizeName, vp] of Object.entries(VIEWPORTS)) {
                const filename = `${scr.id}_${sizeName}.png`;
                const destPath = path.join(OUTPUT_DIR, filename);
                const brainPath = path.join(BRAIN_DIR, filename);

                try {
                    console.log(`  -> Capturando [${scr.id}] en ${sizeName} (${vp.width}x${vp.height})...`);
                    await page.setViewport({ width: vp.width, height: vp.height });
                    await wait(600);

                    await page.screenshot({ path: destPath });
                    fs.copyFileSync(destPath, brainPath);
                    const stat = fs.statSync(destPath);
                    results.push({ screen: scr.name, size: sizeName, file: filename, sizeBytes: stat.size, status: 'OK' });
                } catch (err) {
                    console.error(`  Error capturando ${scr.id} en ${sizeName}:`, err.message);
                    results.push({ screen: scr.name, size: sizeName, file: filename, sizeBytes: 0, status: `ERROR: ${err.message}` });
                }
            }
        }

    } catch (globalErr) {
        console.error('[Error Crítico]', globalErr);
    } finally {
        if (browser) {
            console.log('\n[Puppeteer] Cerrando navegador...');
            try {
                await browser.close();
            } catch (e) {
                // Ignore harmless cleanup errors on Windows
            }
        }
        if (serverProcess) {
            console.log('[Server] Finalizando proceso servidor PHP...');
            serverProcess.kill();
        }
    }

    console.log('\n======================================================');
    console.log('RESUMEN DE CAPTURAS GENERADAS');
    console.log('======================================================');
    console.table(results.map(r => ({
        'Pantalla': r.screen,
        'Tamaño': r.size,
        'Archivo': r.file,
        'Tamaño (KB)': (r.sizeBytes / 1024).toFixed(1),
        'Estado': r.status
    })));

    const successCount = results.filter(r => r.status === 'OK').length;
    console.log(`\nTotal capturas exitosas: ${successCount} de ${results.length}`);
}

run();
