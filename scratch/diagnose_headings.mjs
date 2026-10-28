import fs from 'fs';
import path from 'path';

const pages = [
    // Admin
    { section: 'Admin', name: 'Dashboard', file: 'app/Filament/Pages/Dashboard.php' },
    { section: 'Admin', name: 'Inventario', file: 'app/Filament/Resources/InventarioResource/Pages/ListInventarios.php' },
    { section: 'Admin', name: 'Solicitudes', file: 'app/Filament/Resources/SolicitudResource/Pages/ListSolicituds.php' },
    { section: 'Admin', name: 'Mantenimientos', file: 'app/Filament/Resources/MantenimientoResource/Pages/ListMantenimientos.php' },
    { section: 'Admin', name: 'Usuarios', file: 'app/Filament/Resources/UserResource/Pages/ListUsers.php' },
    { section: 'Admin', name: 'Materiales', file: 'app/Filament/Resources/MaterialResource/Pages/ListMaterials.php' },
    { section: 'Admin', name: 'Proveedores', file: 'app/Filament/Resources/ProveedorResource/Pages/ListProveedors.php' },
    { section: 'Admin', name: 'Receptores', file: 'app/Filament/Resources/ReceptorResource/Pages/ListReceptors.php' },
    { section: 'Admin', name: 'Departamentos', file: 'app/Filament/Resources/DepartamentoResource/Pages/ListDepartamentos.php' },
    { section: 'Admin', name: 'Áreas', file: 'app/Filament/Resources/AreaResource/Pages/ListAreas.php' },
    { section: 'Admin', name: 'Marcas', file: 'app/Filament/Resources/MarcaMaterialResource/Pages/ListMarcaMaterials.php' },
    { section: 'Admin', name: 'Tipos material', file: 'app/Filament/Resources/TipoMaterialResource/Pages/ListTipoMaterials.php' },
    { section: 'Admin', name: 'Unidades medida', file: 'app/Filament/Resources/UnidadMedidaResource/Pages/ListUnidadMedidas.php' },
    { section: 'Admin', name: 'Reportes', file: 'app/Filament/Pages/ReportesPage.php' },
    { section: 'Admin', name: 'Configuración', file: 'app/Filament/Pages/ConfiguracionPage.php' },

    // Servicio Social
    { section: 'SS', name: 'Dashboard', file: 'app/Filament/ServicioSocial/Pages/Dashboard.php' },
    { section: 'SS', name: 'Inventario', file: 'app/Filament/ServicioSocial/Resources/InventarioResource/Pages/ListInventarios.php' },
    { section: 'SS', name: 'Solicitudes', file: 'app/Filament/ServicioSocial/Resources/SolicitudResource/Pages/ListSolicituds.php' },
    { section: 'SS', name: 'Mantenimientos', file: 'app/Filament/ServicioSocial/Resources/MantenimientoResource/Pages/ListMantenimientos.php' },
    { section: 'SS', name: 'Materiales', file: 'app/Filament/ServicioSocial/Resources/MaterialResource/Pages/ListMaterials.php' },
    { section: 'SS', name: 'Receptores', file: 'app/Filament/ServicioSocial/Resources/ReceptorResource/Pages/ListReceptors.php' },
    { section: 'SS', name: 'Áreas', file: 'app/Filament/ServicioSocial/Resources/AreaResource/Pages/ListAreas.php' },
    { section: 'SS', name: 'Marcas', file: 'app/Filament/ServicioSocial/Resources/MarcaMaterialResource/Pages/ListMarcaMaterials.php' },
    { section: 'SS', name: 'Tipos material', file: 'app/Filament/ServicioSocial/Resources/TipoMaterialResource/Pages/ListTipoMaterials.php' },
    { section: 'SS', name: 'Unidades medida', file: 'app/Filament/ServicioSocial/Resources/UnidadMedidaResource/Pages/ListUnidadMedidas.php' },
    { section: 'SS', name: 'Catálogos (SS)', file: 'app/Filament/ServicioSocial/Pages/CatalogosPage.php' },
    { section: 'SS', name: 'Reportes', file: 'app/Filament/ServicioSocial/Pages/ReportesPage.php' },
];

function hasEmoji(str) {
    if (!str) return false;
    const emojiRegex = /[\u{1F300}-\u{1F9FF}\u{2600}-\u{26FF}\u{2700}-\u{27BF}\u{1F1E6}-\u{1F1FF}\u{1F600}-\u{1F64F}\u{1F680}-\u{1F6FF}]/u;
    return emojiRegex.test(str);
}

const results = [];

for (const p of pages) {
    const fullPath = path.resolve(p.file);
    if (!fs.existsSync(fullPath)) {
        results.push({ ...p, exists: false });
        continue;
    }
    const content = fs.readFileSync(fullPath, 'utf8');

    // Also check resource file if it's a List page
    let resourceContent = '';
    const resourceFile = p.file.replace(/\/Pages\/List.*\.php$/, '.php');
    if (fs.existsSync(path.resolve(resourceFile))) {
        resourceContent = fs.readFileSync(path.resolve(resourceFile), 'utf8');
    }

    // Match getTitle or getHeading
    const getTitleMatch = content.match(/function\s+getTitle\s*\(\)\s*:\s*string\s*\{[^}]*return\s+['"]([^'"]+)['"]/);
    const getHeadingMatch = content.match(/function\s+getHeading\s*\(\)\s*:\s*string\s*\{[^}]*return\s+['"]([^'"]+)['"]/);
    const staticTitleMatch = content.match(/protected\s+static\s+\?string\s+\$title\s*=\s*['"]([^'"]+)['"]/);
    const resourceLabelMatch = resourceContent.match(/protected\s+static\s+\?string\s+\$navigationLabel\s*=\s*['"]([^'"]+)['"]/);
    const pluralLabelMatch = resourceContent.match(/protected\s+static\s+\?string\s+\$pluralModelLabel\s*=\s*['"]([^'"]+)['"]/);

    const title = getTitleMatch ? getTitleMatch[1] : (getHeadingMatch ? getHeadingMatch[1] : (staticTitleMatch ? staticTitleMatch[1] : (pluralLabelMatch ? pluralLabelMatch[1] : (resourceLabelMatch ? resourceLabelMatch[1] : null))));

    // Match getSubheading
    const getSubheadingMatch = content.match(/function\s+getSubheading\s*\(\)\s*:\s*\?string\s*\{[^}]*return\s+['"]([^'"]+)['"]/);
    const subheading = getSubheadingMatch ? getSubheadingMatch[1] : null;

    results.push({
        section: p.section,
        name: p.name,
        file: p.file,
        title,
        hasEmoji: hasEmoji(title),
        subheading,
        hasSubheading: !!subheading
    });
}

console.log(JSON.stringify(results, null, 2));
