import puppeteer from 'puppeteer-core';

const EDGE_PATH = 'C:\\Program Files (x86)\\Microsoft\\Edge\\Application\\msedge.exe';

async function verify() {
  const browser = await puppeteer.launch({
    executablePath: EDGE_PATH,
    headless: 'new',
    args: ['--no-sandbox', '--disable-setuid-sandbox']
  });

  const page = await browser.newPage();
  await page.setViewport({ width: 1400, height: 900 });

  // 1. Login as Admin
  await page.goto('http://127.0.0.1:8000/admin/login', { waitUntil: 'networkidle2' });
  await page.type('input[type="email"], input[name="email"]', 'admin@sigem.test');
  await page.type('input[type="password"], input[name="password"]', 'password');
  await Promise.all([
    page.waitForNavigation({ waitUntil: 'networkidle2' }),
    page.click('button[type="submit"]')
  ]);

  const adminRoutes = [
    { name: 'Inventario', url: 'http://127.0.0.1:8000/admin/inventarios' },
    { name: 'Solicitudes', url: 'http://127.0.0.1:8000/admin/solicituds' },
    { name: 'Mantenimientos', url: 'http://127.0.0.1:8000/admin/mantenimientos' },
    { name: 'Usuarios', url: 'http://127.0.0.1:8000/admin/users' },
    { name: 'Materiales', url: 'http://127.0.0.1:8000/admin/materials' },
    { name: 'Proveedores', url: 'http://127.0.0.1:8000/admin/proveedors' },
    { name: 'Receptores', url: 'http://127.0.0.1:8000/admin/receptors' },
    { name: 'Departamentos', url: 'http://127.0.0.1:8000/admin/departamentos' },
    { name: 'Áreas', url: 'http://127.0.0.1:8000/admin/areas' },
    { name: 'Marcas', url: 'http://127.0.0.1:8000/admin/marca-materials' },
    { name: 'Tipos material', url: 'http://127.0.0.1:8000/admin/tipo-materials' },
    { name: 'Unidades medida', url: 'http://127.0.0.1:8000/admin/unidad-medidas' },
    { name: 'Configuración', url: 'http://127.0.0.1:8000/admin/configuracion' },
    { name: 'Reportes', url: 'http://127.0.0.1:8000/admin/reportes' }
  ];

  console.log('=== ADMIN PANEL ===');
  for (const r of adminRoutes) {
    await page.goto(r.url, { waitUntil: 'networkidle2' });
    const info = await page.evaluate(() => {
      // Filament header heading or custom blade header
      const headingEl = document.querySelector('.fi-header-heading, .sigem-rep-header h1, .sigem-hero h1');
      const subheadingEl = document.querySelector('.fi-header-subheading, .sigem-rep-header p, .sigem-hero p');
      return {
        heading: headingEl ? headingEl.textContent.trim().replace(/\s+/g, ' ') : null,
        subheading: subheadingEl ? subheadingEl.textContent.trim().replace(/\s+/g, ' ') : null
      };
    });
    console.log(`[${r.name}] -> Heading: "${info.heading}" | Sub: "${info.subheading}"`);
  }

  // 2. Login as Servicio Social
  await page.goto('http://127.0.0.1:8000/servicio-social/login', { waitUntil: 'networkidle2' });
  await page.type('input[type="email"], input[name="email"]', 'serviciosocial@sigem.test');
  await page.type('input[type="password"], input[name="password"]', 'password');
  await Promise.all([
    page.waitForNavigation({ waitUntil: 'networkidle2' }),
    page.click('button[type="submit"]')
  ]);

  const ssRoutes = [
    { name: 'Inventario SS', url: 'http://127.0.0.1:8000/servicio-social/inventarios' },
    { name: 'Solicitudes SS', url: 'http://127.0.0.1:8000/servicio-social/solicituds' },
    { name: 'Mantenimientos SS', url: 'http://127.0.0.1:8000/servicio-social/mantenimientos' },
    { name: 'Materiales SS', url: 'http://127.0.0.1:8000/servicio-social/materials' },
    { name: 'Receptores SS', url: 'http://127.0.0.1:8000/servicio-social/receptors' },
    { name: 'Áreas SS', url: 'http://127.0.0.1:8000/servicio-social/areas' },
    { name: 'Marcas SS', url: 'http://127.0.0.1:8000/servicio-social/marca-materials' },
    { name: 'Tipos material SS', url: 'http://127.0.0.1:8000/servicio-social/tipo-materials' },
    { name: 'Unidades medida SS', url: 'http://127.0.0.1:8000/servicio-social/unidad-medidas' },
    { name: 'Catálogos SS', url: 'http://127.0.0.1:8000/servicio-social/catalogos' },
    { name: 'Reportes SS', url: 'http://127.0.0.1:8000/servicio-social/reportes' }
  ];

  console.log('\n=== SERVICIO SOCIAL PANEL ===');
  for (const r of ssRoutes) {
    await page.goto(r.url, { waitUntil: 'networkidle2' });
    const info = await page.evaluate(() => {
      const headingEl = document.querySelector('.fi-header-heading, .sigem-rep-header h1, .sigem-hero h1');
      const subheadingEl = document.querySelector('.fi-header-subheading, .sigem-rep-header p, .sigem-hero p');
      return {
        heading: headingEl ? headingEl.textContent.trim().replace(/\s+/g, ' ') : null,
        subheading: subheadingEl ? subheadingEl.textContent.trim().replace(/\s+/g, ' ') : null
      };
    });
    console.log(`[${r.name}] -> Heading: "${info.heading}" | Sub: "${info.subheading}"`);
  }

  await browser.close();
}

verify().catch(console.error);
