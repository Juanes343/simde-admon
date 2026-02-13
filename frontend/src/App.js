import React from 'react';
import { BrowserRouter as Router, Routes, Route, Navigate } from 'react-router-dom';
import { ToastContainer } from 'react-toastify';
import 'react-toastify/dist/ReactToastify.css';
import 'bootstrap/dist/css/bootstrap.min.css';
import '@fortawesome/fontawesome-free/css/all.min.css';
import './App.css';

// Auth
import LoginPage from './features/Auth/pages/LoginPage';
import RegisterPage from './features/Auth/pages/RegisterPage';

// Dashboard
import DashboardPage from './features/Dashboard/pages/DashboardPage';

// Terceros
import TercerosListPage from './features/Terceros/pages/TercerosListPage';
import TerceroFormPage from './features/Terceros/pages/TerceroFormPage';
import TerceroUploadPdfPage from './features/Terceros/pages/TerceroUploadPdfPage';

// Servicios
import ServiciosListPage from './features/Servicios/pages/ServiciosListPage';
import ServicioFormPage from './features/Servicios/pages/ServicioFormPage';

// Órdenes de Servicio
import OrdenesServicioListPage from './features/OrdenesServicio/pages/OrdenesServicioListPage';
import OrdenServicioFormPage from './features/OrdenesServicio/pages/OrdenServicioFormPage';
import OrdenServicioDetailPage from './features/OrdenesServicio/pages/OrdenServicioDetailPage';

// Components
import PrivateRoute from './components/PrivateRoute/PrivateRoute';

function App() {
  return (
    <Router>
      <div className="App">
        <ToastContainer
          position="top-right"
          autoClose={3000}
          hideProgressBar={false}
          newestOnTop={false}
          closeOnClick
          rtl={false}
          pauseOnFocusLoss
          draggable
          pauseOnHover
        />
        
        <Routes>
          {/* Rutas públicas */}
          <Route path="/login" element={<LoginPage />} />
          <Route path="/register" element={<RegisterPage />} />
          
          {/* Rutas protegidas */}
          <Route
            path="/dashboard"
            element={
              <PrivateRoute>
                <DashboardPage />
              </PrivateRoute>
            }
          />
          
          <Route
            path="/terceros"
            element={
              <PrivateRoute>
                <TercerosListPage />
              </PrivateRoute>
            }
          />
          
          <Route
            path="/terceros/new"
            element={
              <PrivateRoute>
                <TerceroFormPage />
              </PrivateRoute>
            }
          />
          
          <Route
            path="/terceros/edit/:tipo_id_tercero/:tercero_id"
            element={
              <PrivateRoute>
                <TerceroFormPage />
              </PrivateRoute>
            }
          />
          
          <Route
            path="/terceros/upload-pdf"
            element={
              <PrivateRoute>
                <TerceroUploadPdfPage />
              </PrivateRoute>
            }
          />

          {/* Rutas de Servicios */}
          <Route
            path="/servicios"
            element={
              <PrivateRoute>
                <ServiciosListPage />
              </PrivateRoute>
            }
          />
          
          <Route
            path="/servicios/new"
            element={
              <PrivateRoute>
                <ServicioFormPage />
              </PrivateRoute>
            }
          />
          
          <Route
            path="/servicios/edit/:id"
            element={
              <PrivateRoute>
                <ServicioFormPage />
              </PrivateRoute>
            }
          />

          {/* Rutas de Órdenes de Servicio */}
          <Route
            path="/ordenes-servicio"
            element={
              <PrivateRoute>
                <OrdenesServicioListPage />
              </PrivateRoute>
            }
          />
          
          <Route
            path="/ordenes-servicio/new"
            element={
              <PrivateRoute>
                <OrdenServicioFormPage />
              </PrivateRoute>
            }
          />
          
          <Route
            path="/ordenes-servicio/edit/:id"
            element={
              <PrivateRoute>
                <OrdenServicioFormPage />
              </PrivateRoute>
            }
          />
          
          <Route
            path="/ordenes-servicio/:id"
            element={
              <PrivateRoute>
                <OrdenServicioDetailPage />
              </PrivateRoute>
            }
          />
          
          {/* Ruta por defecto */}
          <Route path="/" element={<Navigate to="/dashboard" replace />} />
        </Routes>
      </div>
    </Router>
  );
}

export default App;
