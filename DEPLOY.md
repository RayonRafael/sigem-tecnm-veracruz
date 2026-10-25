# Guía de Deploy en Render - SIGEM TecNM Veracruz

Este documento describe el procedimiento para desplegar el sistema SIGEM en la plataforma **Render** utilizando una base de datos **SQLite embebida y precargada con datos reales**.

---

## 1. Pasos para hacer deploy en Render

El proyecto incluye un archivo [`render.yaml`](file:///render.yaml) configurado para despliegue automático mediante **Render Blueprints**.

### Opción A: Despliegue con Blueprint (Recomendado)
1. Sube los cambios de la rama `deploy-render` a tu repositorio en GitHub:
   ```bash
   git add .
   git commit -m "feat: preparar despliegue en Render con SQLite precargado"
   git push origin deploy-render
   ```
2. Inicia sesión en [Render](https://dashboard.render.com/).
3. Haz clic en **New +** y selecciona **Blueprint**.
4. Conecta tu repositorio de GitHub (`sigem-tecnm-veracruz`) y selecciona la rama `deploy-render`.
5. Render detectará automáticamente el archivo [`render.yaml`](file:///render.yaml).
6. Haz clic en **Apply**. Render aprovisionará el servicio web PHP, instalará dependencias, publicará los assets de Filament, compilará las cachés y levantará el servidor con `php artisan serve`.

### Opción B: Despliegue Manual como Web Service
Si prefieres crearlo manualmente en Render:
- **Service Type**: Web Service
- **Runtime**: `Docker`
- **Region**: Oregon (USA) o Frankfurt (EU)
- **Branch**: `deploy-render`
- **Plan**: `Free`
- **Dockerfile Path**: `Dockerfile` (Render lo detecta automáticamente en la raíz)
- **Environment Variables**:
  | Variable | Valor |
  |---|---|
  | `PHP_VERSION` | `8.4` |
  | `APP_ENV` | `production` |
  | `APP_DEBUG` | `false` |
  | `APP_KEY` | *(Generar valor automáticamente en Render)* |
  | `APP_URL` | `https://sigem-tecnm-veracruz.onrender.com` |
  | `DB_CONNECTION` | `sqlite` |
  | `DB_DATABASE` | `database/database.sqlite` |
  | `SESSION_DRIVER` | `file` |
  | `CACHE_STORE` | `file` |
  | `LOG_CHANNEL` | `stderr` |
  | `APP_LOCALE` | `es` |

> [!IMPORTANT]
> **NO** incluyas `php artisan migrate` en el Build Command. La base de datos SQLite ya está precargada con todas las tablas y datos reales en el repositorio (`database/database.sqlite`).

---

## 2. Credenciales de Prueba

La base de datos SQLite incluye los usuarios precargados para explorar todas las funcionalidades y paneles del sistema:

### Panel de Administración (Admin)
- **URL**: `/admin` o `/admin/login`
- **Usuario**: `admin@tecnm.edu.mx`
- **Contraseña**: `admin123`
- **Rol**: `Administrador`
- **Acceso**: Control total de Inventario, Solicitudes, Mantenimientos, Catálogos (Departamentos, Áreas, Proveedores, Receptores, Materiales, etc.), Reportes, Usuarios y Configuración.

### Panel de Servicio Social
- **URL**: `/servicio-social` o `/servicio-social/login`
- **Usuario**: `servicio@tecnm.edu.mx`
- **Contraseña**: `servicio123`
- **Rol**: `Servicio Social`
- **Acceso**: Consulta y registro de Inventario, Solicitudes, Mantenimientos, Materiales y Reportes con permisos restringidos acordes al rol.

---

## 3. Limitaciones del Entorno

> [!WARNING]
> En el plan gratuito de Render, el sistema de archivos es **efímero (non-persistent disk)**.

- **Persistencia**: Cualquier registro que se cree, modifique o elimine durante la navegación permanecerá activo mientras la instancia esté en ejecución, pero **se reiniciará** a los datos precargados originales si el contenedor se apaga por inactividad o ante un nuevo despliegue.
- **Propósito**: Esta configuración está pensada exclusivamente para demostraciones funcionales, navegación de pantallas, validación de diseño y pruebas del sistema sin necesidad de una base de datos externa gestionada.
- **Inactividad**: En el plan gratuito de Render, el servidor puede entrar en suspensión tras 15 minutos sin peticiones. La primera petición tras la suspensión puede demorar aproximadamente 30-50 segundos en despertar.

---

## 4. Comando para Regenerar SQLite desde MySQL (Futuro)

Si en el futuro se capturan nuevos datos en la base de datos MySQL local (`sigem_tecnm`) y se desea actualizar el archivo SQLite precargado para una nueva demo:

1. **Asegurarse de tener el servicio MySQL local activo** con la base de datos `sigem_tecnm`.
2. **Recrear el archivo SQLite vacío**:
   - En Windows (PowerShell):
     ```powershell
     Remove-Item database/database.sqlite -Force -ErrorAction SilentlyContinue
     New-Item database/database.sqlite -ItemType File
     ```
   - En Linux / macOS / Git Bash:
     ```bash
     rm -f database/database.sqlite && touch database/database.sqlite
     ```
3. **Ejecutar migraciones contra SQLite**:
   ```bash
   php artisan migrate --force --database=sqlite
   ```
4. **Ejecutar el comando de exportación**:
   ```bash
   php artisan sigem:export-sqlite
   ```
   *Este comando exporta en el orden estricto de claves foráneas respetando las columnas existentes e informa el conteo de cada tabla.*
5. **Verificar los datos exportados**:
   ```bash
   php artisan tinker --execute="echo DB::connection('sqlite')->table('inventario')->count();"
   ```
6. **Guardar y subir los cambios**:
   ```bash
   git add database/database.sqlite
   git commit -m "chore: actualizar datos precargados de SQLite"
   git push origin deploy-render
   ```
