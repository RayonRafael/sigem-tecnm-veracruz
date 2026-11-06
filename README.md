# SIGEM — Sistema de Gestión de Equipos y Materiales

Sistema integral para el control de inventario, gestión de préstamos, rentas y seguimiento de mantenimiento para el equipamiento del **TecNM Campus Veracruz**. El sistema centraliza la operación de activos y proporciona interfaces distintas para administradores y prestadores de servicio social.

- **Producción:** [https://sigem-tecnm-veracruz-1.onrender.com](https://sigem-tecnm-veracruz-1.onrender.com)
- **Rama principal de desarrollo actual:** `diseno-v2`

---

## Stack Tecnológico

- **Framework:** Laravel 12 (PHP 8.2+)
- **Panel Administrativo:** Filament v3.3
- **Roles y Permisos:** Spatie Laravel Permission
- **Auditoría y Bitácora:** Spatie ActivityLog
- **Frontend y Diseño:** Blade + SCSS modular + Tailwind CSS
- **Bases de Datos:** MySQL 8 (Producción) / SQLite (Desarrollo y Testing automatizado)
- **Contenedor y Despliegue:** Docker en Render (Apache + PHP 8.4)

---

## Requisitos Previos

- PHP 8.2 o superior (con extensiones `pdo_mysql`, `pdo_sqlite`, `zip`, `intl`, `gd`)
- Composer 2+
- Node.js 18+ y npm
- MySQL 8 o SQLite

---

## Instalación y Configuración Local

1. **Clonar el repositorio:**
   ```bash
   git clone <url-del-repositorio>
   cd sigem-tecnm-veracruz
   ```

2. **Instalar dependencias de PHP:**
   ```bash
   composer install
   ```

3. **Configurar el entorno:**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Configurar la base de datos en `.env`:**
   Para SQLite:
   ```env
   DB_CONNECTION=sqlite
   ```
   O para MySQL:
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=sigem_db
   DB_USERNAME=tu_usuario
   DB_PASSWORD=tu_password
   ```

5. **Ejecutar migraciones y sembrar base de datos:**
   ```bash
   php artisan migrate --seed
   ```

6. **Instalar dependencias y compilar assets:**
   ```bash
   npm install
   npm run build:css
   ```

7. **Iniciar servidor local:**
   ```bash
   php artisan serve
   ```

---

## Suite de Pruebas Automatizadas

El proyecto cuenta con una cobertura integral de pruebas de integración y features:
- **130 tests automatizados (441 assertions)** pasando en verde.
- Cobertura de flujos críticos:
  - Autenticación y aislamiento de paneles (Admin vs Servicio Social).
  - Flujo de bajas y reactivación de inventario con historial atómico.
  - Flujo de cambios de área (solicitud por servicio social, aprobación/rechazo por administrador).
  - Registro masivo (`MultiRegistro`) con generación única de códigos e integridad transaccional.
  - Autorización y ciclo de vida de Solicitudes y Mantenimientos.
  - Reportes tabulares y exportación a Excel (.xlsx) y CSV.
  - Paleta de comandos con tolerancia a acentos y navegación rápida.
  - Catálogos en modo solo lectura para roles restringidos.

Para ejecutar toda la suite de pruebas:
```bash
php artisan test
```

---

## Usuarios de Prueba (Seeders)

El sistema cuenta con dos paneles aislados con autenticación nativa de Filament:

| Rol | URL de Acceso | Correo | Contraseña |
|---|---|---|---|
| **Administrador** | `/admin/login` | `admin@tecnm.edu.mx` | `admin123` |
| **Servicio Social** | `/servicio-social/login` | `servicio@tecnm.edu.mx` | `servicio123` |

---

## Estructura del Proyecto

| Directorio | Descripción |
|---|---|
| `app/Models/` | Modelos Eloquent con transacciones atómicas de base de datos (`DB::transaction`). |
| `app/Enums/` | `RoleEnum`, `EstadoInventario`, `EstadoSolicitud` para evitar valores mágicos. |
| `app/Policies/` | Políticas de acceso basadas en permisos granulares de Spatie. |
| `app/Filament/Resources/` | Resources y Schemas modulares del Panel Admin (acceso total). |
| `app/Filament/ServicioSocial/` | Resources y páginas del Panel Servicio Social (supervisión y lectura). |
| `app/Filament/Concerns/` | Traits compartidos (`ConSlideOver`, `ConModalesBajaCambio`, `ConCatalogoSoloLectura`). |
| `app/Observers/` | Observers de inventario y mantenimientos con control atómico de stock y estados. |
| `app/Support/` | Helpers desacoplados (`UbicacionHelper`, `EstadoBadgeHelper`, `SigemExcelExporter`). |
| `resources/views/` | Vistas Blade para Landing Page (optimizada WebP y Google Fonts asíncrono) y errores. |
| `public/css/` | SCSS modular (`sigem-theme.scss`) compilado a `sigem-theme.css`. |
| `database/migrations/` | Migraciones con índices de optimización (`tipo_propiedad`, `id_usuario_baja`, compuestos). |

---

## Roles y Autorización (Spatie)

- Verificaciones basadas en permisos específicos (`hasPermissionTo()`) en combinación con `RoleEnum`.
- **Administrador:** Cuenta con permisos totales y el permiso global de panel `access_admin_panel`. Autoriza solicitudes, aprueba bajas, traslados y administra usuarios.
- **Servicio Social:** Cuenta con `access_servicio_social_panel`. Permisos enfocados en registro rápido, visualización controlada y solicitud de bajas/traslados sujetas a aprobación.
- Toda la actividad administrativa y operativa se registra de forma transparente mediante **Spatie ActivityLog**.

---

## Despliegue en Producción (Render)

El despliegue está configurado mediante `Dockerfile` sobre Apache + PHP 8.4:
- Extensiones instaladas: `pdo_sqlite`, `pdo_mysql`, `zip`, `intl`.
- Cachés generadas en tiempo de arranque (runtime CMD):
  - `php artisan config:cache`
  - `php artisan route:cache`
  - `php artisan view:cache`
  - `php artisan icons:cache`
- Variables de entorno configuradas en Render Dashboard:
  - `APP_ENV=production`
  - `APP_KEY=base64:...`
  - `DB_CONNECTION=mysql` (o sqlite)
  - `PORT=10000` (Apache se ajusta automáticamente al puerto asignado)
