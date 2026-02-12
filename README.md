# SIMDE ADMON - Sistema Integral de Gestión

Sistema profesional de gestión administrativa con módulo de terceros y lectura automática de RUT desde PDF.

## 🚀 Tecnologías

### Backend
- Laravel 11
- MySQL (simde_admon)
- Autenticación personalizada con tokens
- PDF Parser (Lectura de RUT DIAN)

### Frontend
- React 18
- React Router DOM
- Bootstrap 5
- React Bootstrap
- Axios
- React Toastify

## 📋 Requisitos Previos

- PHP >= 8.2
- Composer
- Node.js >= 16
- MySQL >= 8.0
- npm o yarn

## 🔧 Instalación

### 1. Configurar Backend (Laravel)

```powershell
# Navegar a la carpeta backend
cd backend

# Instalar dependencias
composer install

# Copiar archivo de configuración
copy .env.example .env

# Generar key de la aplicación
php artisan key:generate

# Configurar la base de datos en el archivo .env
# DB_CONNECTION=mysql
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=simde_admon
# DB_USERNAME=root
# DB_PASSWORD=
```

### 2. Crear la Base de Datos

```sql
-- En MySQL, crear la base de datos
CREATE DATABASE simde_admon;
```

### 3. Ejecutar Migraciones

```powershell
# Ejecutar migraciones
php artisan migrate

# (Opcional) Si deseas crear datos de prueba
php artisan db:seed
```

### 4. Iniciar el Servidor Backend

```powershell
php artisan serve
# El backend estará disponible en http://localhost:8000
```

### 5. Configurar Frontend (React)

```powershell
# En una nueva terminal, navegar a la carpeta frontend
cd ..\frontend

# Instalar dependencias
npm install

# Copiar archivo de configuración
copy .env.example .env

# El archivo .env debe contener:
# REACT_APP_API_URL=http://localhost:8000/api
```

### 6. Iniciar el Servidor Frontend

```powershell
npm start
# El frontend estará disponible en http://localhost:3000
```

## 📁 Estructura del Proyecto

```
programa_simde/
├── backend/                    # Laravel API
│   ├── app/
│   │   ├── Http/
│   │   │   └── Controllers/
│   │   │       ├── AuthController.php
│   │   │       └── TerceroController.php
│   │   └── Models/
│   │       ├── SystemUsuario.php
│   │       └── Tercero.php
│   ├── database/
│   │   └── migrations/
│   ├── routes/
│   │   └── api.php
│   └── .env
│
└── frontend/                   # React App
    ├── src/
    │   ├── components/
    │   │   ├── Auth/
    │   │   │   ├── Login.js
    │   │   │   └── Register.js
    │   │   ├── Dashboard/
    │   │   │   └── Dashboard.js
    │   │   ├── Terceros/
    │   │   │   ├── TercerosList.js
    │   │   │   ├── TerceroForm.js
    │   │   │   └── TerceroUploadPdf.js
    │   │   └── Common/
    │   │       └── PrivateRoute.js
    │   ├── services/
    │   │   └── api.js
    │   ├── App.js
    │   └── App.css
    └── .env
```

## 🔐 Características de Seguridad

- Autenticación basada en tokens (Laravel Sanctum)
- Protección de rutas en frontend
- Validación de datos en backend y frontend
- Hashing de contraseñas
- CORS configurado

## 📝 Funcionalidades

### Autenticación
- ✅ Registro de usuarios
- ✅ Inicio de sesión
- ✅ Cierre de sesión
- ✅ Protección de rutas

### Módulo de Terceros
- ✅ Listar terceros con paginación
- ✅ Crear tercero manual
- ✅ Editar tercero
- ✅ Desactivar tercero
- ✅ Búsqueda de terceros
- ✅ Carga de RUT desde PDF (extracción automática)

## 🎨 Características del Frontend

- Diseño responsive con Bootstrap
- Notificaciones toast
- Validación de formularios
- Manejo de errores
- Carga de archivos PDF
- Interfaz profesional

## 🗄️ Base de Datos

### Tabla: system_usuarios
Gestiona los usuarios del sistema con información completa y control de acceso.

### Tabla: terceros
Gestiona terceros (clientes, proveedores) con información completa incluyendo:
- Identificación (NIT, CC, etc.)
- Información personal/empresarial
- Ubicación geográfica
- Contacto
- Información tributaria

## 🔄 API Endpoints

### Autenticación
```
POST /api/register      - Registro de usuario
POST /api/login         - Inicio de sesión
POST /api/logout        - Cierre de sesión
GET  /api/me            - Obtener usuario autenticado
```

### Terceros
```
GET    /api/terceros                                    - Listar terceros
POST   /api/terceros                                    - Crear tercero
GET    /api/terceros/{tipo_id}/{tercero_id}           - Ver tercero
PUT    /api/terceros/{tipo_id}/{tercero_id}           - Actualizar tercero
DELETE /api/terceros/{tipo_id}/{tercero_id}           - Desactivar tercero
POST   /api/terceros/upload-pdf                        - Crear desde PDF
```

## 🐛 Solución de Problemas

### Error de CORS
Asegúrese de que en el archivo `.env` del backend esté configurado:
```
SANCTUM_STATEFUL_DOMAINS=localhost:3000
CORS_ALLOWED_ORIGINS=http://localhost:3000
```

### Error de Conexión a Base de Datos
Verificar credenciales en `.env` del backend y que PostgreSQL esté ejecutándose.

### Error al cargar PDF
Asegúrese de que el paquete `smalot/pdfparser` esté instalado correctamente.

## 📧 Soporte

Para soporte o consultas sobre el sistema, contacte al equipo de desarrollo.

## 📄 Licencia

Este proyecto es privado y confidencial.
