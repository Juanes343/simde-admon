# Arquitectura del Frontend - SIMDE ADMON

## 📁 Estructura por Features

El proyecto está organizado siguiendo el patrón **Feature-Sliced Design**, donde cada funcionalidad (feature) es independiente y contiene toda su lógica.

```
src/
├── features/                 # Módulos principales
│   ├── Auth/                # Autenticación
│   │   ├── services/        # Lógica de peticiones HTTP
│   │   │   └── authService.js
│   │   ├── views/           # Componentes de UI/Formularios
│   │   │   ├── LoginView.jsx
│   │   │   └── RegisterView.jsx
│   │   └── pages/           # Páginas con layout completo
│   │       ├── LoginPage.jsx
│   │       ├── RegisterPage.jsx
│   │       └── Auth.css
│   │
│   ├── Terceros/            # Gestión de Terceros
│   │   ├── services/
│   │   │   └── terceroService.js
│   │   ├── hooks/           # Custom hooks de React
│   │   │   └── useTerceros.js
│   │   ├── views/
│   │   │   ├── TercerosListView.jsx
│   │   │   ├── TerceroFormView.jsx
│   │   │   └── TerceroUploadPdfView.jsx
│   │   └── pages/
│   │       ├── TercerosListPage.jsx
│   │       ├── TerceroFormPage.jsx
│   │       └── TerceroUploadPdfPage.jsx
│   │
│   └── Dashboard/           # Dashboard principal
│       └── pages/
│           ├── DashboardPage.jsx
│           └── Dashboard.css
│
├── components/              # Componentes compartidos
│   ├── Layout/
│   │   └── MainLayout.jsx
│   ├── Navbar/
│   │   └── AppNavbar.jsx
│   └── PrivateRoute/
│       └── PrivateRoute.jsx
│
├── utils/                   # Utilidades globales
│   └── api.js              # Configuración de Axios
│
├── App.js                   # Configuración de rutas
├── App.css                  # Estilos globales
└── index.js                 # Punto de entrada
```

## 🎯 Responsabilidades

### **Services** (Capa de Datos)
- Maneja todas las peticiones HTTP
- Se comunica con el backend
- Retorna datos o lanza errores
- **NO** contiene lógica de UI

**Ejemplo:**
```javascript
// features/Auth/services/authService.js
export const authService = {
  login: async (credentials) => {
    const response = await api.post('/login', credentials);
    return response.data;
  }
};
```

### **Views** (Componentes de Presentación)
- Componentes "tontos" de UI
- Reciben datos via props
- Emiten eventos via callbacks
- **NO** hacen peticiones HTTP directamente
- Reusables y testables

**Ejemplo:**
```javascript
// features/Auth/views/LoginView.jsx
const LoginView = ({ onSubmit, loading, error }) => {
  // Solo UI, no lógica de negocio
  return <Form onSubmit={handleSubmit}>...</Form>
};
```

### **Pages** (Contenedores)
- Conectan services con views
- Manejan el estado local
- Contienen la lógica de negocio
- Usan el layout principal

**Ejemplo:**
```javascript
// features/Auth/pages/LoginPage.jsx
const LoginPage = () => {
  const [loading, setLoading] = useState(false);
  
  const handleSubmit = async (formData) => {
    const response = await authService.login(formData);
    // Lógica de redirección, etc.
  };

  return (
    <div className="auth-container">
      <LoginView onSubmit={handleSubmit} loading={loading} />
    </div>
  );
};
```

### **Hooks** (Lógica Reutilizable)
- Custom hooks de React
- Encapsulan lógica compleja
- Reutilizables entre componentes

**Ejemplo:**
```javascript
// features/Terceros/hooks/useTerceros.js
export const useTerceros = () => {
  const [terceros, setTerceros] = useState([]);
  const [loading, setLoading] = useState(false);
  
  const fetchTerceros = async () => {
    // Lógica de carga
  };
  
  return { terceros, loading, fetchTerceros };
};
```

## 🔄 Flujo de Datos

```
User Action → Page → Service → API → Backend
                ↓
              View ← State Update
```

1. **Usuario** interactúa con un **View**
2. **View** emite evento al **Page**
3. **Page** llama al **Service**
4. **Service** hace petición HTTP
5. **Service** retorna datos al **Page**
6. **Page** actualiza estado
7. Estado se pasa al **View** via props

## 🎨 Ventajas de esta Arquitectura

### ✅ **Separación de Responsabilidades**
Cada capa tiene un propósito claro y único

### ✅ **Testeable**
- Services se testean independientemente
- Views se testean con datos mockeados
- Pages se testean de forma integrada

### ✅ **Escalable**
Fácil agregar nuevas features sin afectar las existentes

### ✅ **Mantenible**
Código organizado y predecible

### ✅ **Reutilizable**
Views y Services pueden compartirse entre features

### ✅ **Trabajo en Equipo**
Múltiples desarrolladores pueden trabajar en diferentes features sin conflictos

## 📝 Convenciones de Nombres

### Archivos
- **Services:** `nombreService.js`
- **Hooks:** `useNombre.js`
- **Views:** `NombreView.jsx`
- **Pages:** `NombrePage.jsx`

### Funciones
- **Services:** camelCase (`getUser`, `updateTercero`)
- **Hooks:** camelCase con prefijo `use` (`useTerceros`, `useAuth`)
- **Components:** PascalCase (`LoginView`, `TerceroFormPage`)

## 🚀 Agregar Nueva Feature

### Paso 1: Crear estructura
```bash
src/features/NuevaFeature/
├── services/
│   └── nuevaFeatureService.js
├── hooks/
│   └── useNuevaFeature.js
├── views/
│   └── NuevaFeatureView.jsx
└── pages/
    └── NuevaFeaturePage.jsx
```

### Paso 2: Crear Service
```javascript
// services/nuevaFeatureService.js
import api from '../../../utils/api';

export const nuevaFeatureService = {
  getAll: async () => {
    const response = await api.get('/nueva-feature');
    return response.data;
  }
};
```

### Paso 3: Crear View
```javascript
// views/NuevaFeatureView.jsx
const NuevaFeatureView = ({ data, onAction }) => {
  return <div>{/* UI */}</div>;
};
```

### Paso 4: Crear Page
```javascript
// pages/NuevaFeaturePage.jsx
import MainLayout from '../../../components/Layout/MainLayout';

const NuevaFeaturePage = () => {
  // Lógica
  return (
    <MainLayout>
      <NuevaFeatureView />
    </MainLayout>
  );
};
```

### Paso 5: Agregar Ruta
```javascript
// App.js
<Route path="/nueva-feature" element={
  <PrivateRoute>
    <NuevaFeaturePage />
  </PrivateRoute>
} />
```

## 🔐 Componentes Compartidos

### MainLayout
Envuelve todas las páginas protegidas, incluye navbar

### AppNavbar
Barra de navegación con menú y logout

### PrivateRoute
Protege rutas que requieren autenticación

## 🛠️ Herramientas y Librerías

- **React Router DOM:** Navegación
- **Axios:** Peticiones HTTP
- **React Bootstrap:** Componentes UI
- **React Toastify:** Notificaciones
- **Formik & Yup:** (opcional) Validación de formularios

## 📚 Recursos

- [Feature-Sliced Design](https://feature-sliced.design/)
- [React Hooks](https://react.dev/reference/react)
- [Axios](https://axios-http.com/)
- [React Router](https://reactrouter.com/)
