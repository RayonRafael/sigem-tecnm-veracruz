# SIGEM — Sistema de Gestión de Equipos y Materiales

Sistema integral para el control de inventario, gestión de préstamos, rentas y seguimiento de mantenimiento para el equipamiento del TecNM Campus Veracruz. El sistema centraliza la operación de activos y proporciona interfaces distintas para administradores y prestadores de servicio social.

## Stack Tecnológico

- **Framework:** Laravel 12
- **Admin Panel:** Filament 3.3
- **Roles & Permisos:** Spatie Laravel Permission
- **Estilos:** Tailwind CSS 4
- **Base de Datos:** MySQL 8

## Requisitos Previos

- PHP 8.2+
- Composer
- Node.js & npm
- MySQL 8

## Instalación

1. Clona el repositorio:
   ```bash
   git clone [repo]
   ```
2. Instala las dependencias de PHP:
   ```bash
   composer install
   ```
3. Copia el archivo de entorno y configura tu base de datos:
   ```bash
   cp .env.example .env
   ```
4. Genera la clave de la aplicación:
   ```bash
   php artisan key:generate
   ```
5. Ejecuta las migraciones:
   ```bash
   php artisan migrate
   ```
6. Siembra la base de datos (Roles, Permisos y Usuarios):
   ```bash
   php artisan db:seed
   ```
7. Instala y compila los assets del frontend:
   ```bash
   npm install
   npm run build
   ```
8. Inicia el servidor de desarrollo:
   ```bash
   php artisan serve
   ```

## Usuarios de Prueba

Para ingresar al sistema, dirígete a `http://localhost:8000/login` e inicia sesión con las siguientes credenciales:

- **Administrador:** 
  - Correo: `admin@tecnm.edu.mx` 
  - Contraseña: `admin123` 
  - Redirección: `/admin`
- **Servicio Social:** 
  - Correo: `servicio@tecnm.edu.mx` 
  - Contraseña: `servicio123` 
  - Redirección: `/servicio-social`

## Estructura del Proyecto

| Directorio | Descripción |
|---|---|
| `app/Models/` | Modelos Eloquent (15 modelos) |
| `app/Filament/Resources/` | Resources del panel Admin (11 módulos) |
| `app/Filament/ServicioSocial/Resources/` | Resources del panel Servicio Social (9 módulos) |
| `app/Filament/Widgets/` | Widgets del dashboard (eliminados tras refactoring a favor de vistas HTML directas) |
| `app/Observers/` | Observers para lógica de negocio y bitácora automatizada |
| `app/Http/Controllers/Auth/` | `LoginController` para autenticación unificada con pestañas dinámicas |
| `resources/views/` | Vistas Blade (landing, login unificado con pestañas, dashboards de Filament) |
| `database/seeders/` | Seeders responsables de cargar roles, permisos iniciales y usuarios de prueba |

## Módulos del Sistema

### Panel Admin (Acceso Total)
1. Inventario
2. Solicitudes (Préstamos/Rentas)
3. Mantenimiento
4. Departamentos
5. Áreas
6. Materiales
7. Marcas de Material
8. Tipos de Material
9. Unidades de Medida
10. Proveedores
11. Receptores
12. Usuarios
13. Bitácora de Sistema

### Panel Servicio Social (Operaciones con Supervisión)
1. Inventario
2. Solicitudes
3. Mantenimiento
4. Materiales
5. Receptores
6. Marcas de Material
7. Tipos de Material
8. Unidades de Medida
9. Áreas

## Roles y Permisos (Spatie)
- **Administrador:** Cuenta con 24 permisos, acceso total a todos los catálogos, configuraciones y paneles administrativos. Autoriza solicitudes y edita históricos.
- **Servicio Social:** Cuenta con 10 permisos, destinado a registro rápido, lectura de inventario y levantamiento de solicitudes. Su actividad queda sujeta a revisión administrativa.
