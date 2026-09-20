import React from 'react';
import { HashRouter as Router, Routes, Route, Navigate } from 'react-router-dom';
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
import OrdenServicioSignaturePage from './features/OrdenesServicio/pages/OrdenServicioSignaturePage';

// Facturacion
import FacturacionView from './features/Facturacion/views/FacturacionView';
import FacturasListView from './features/Facturacion/views/FacturasListView';
import FacturasExternasView from './features/Facturacion/views/FacturasExternasView';

// Notas Crédito/Débito
import NotasCreditoView from './features/NotasCredito/views/NotasCreditoView';
import NotasCreditoListView from './features/NotasCredito/views/NotasCreditoListView';
import NotasCreditoConceptosView from './features/NotasCredito/views/NotasCreditoConceptosView';

// Causacion
import CausacionPage from './features/Causacion/pages/CausacionPage';
import CausacionHistorialPage from './features/Causacion/pages/CausacionHistorialPage';
import ReporteCausacionPage from './features/Causacion/pages/ReporteCausacionPage';

// Cotizaciones
import CotizacionesListPage from './features/Cotizaciones/pages/CotizacionesListPage';
import CotizacionFormPage from './features/Cotizaciones/pages/CotizacionFormPage';
import CotizacionDetailPage from './features/Cotizaciones/pages/CotizacionDetailPage';

// Usuarios
import UsuarioPermisosView from './features/Usuarios/views/UsuarioPermisosView';

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
          <Route path="/firmar-orden/:id/:token" element={<OrdenServicioSignaturePage />} />
          
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
            path="/facturacion"
            element={
              <PrivateRoute>
                <FacturacionView />
              </PrivateRoute>
            }
          />

          <Route
            path="/facturas"
            element={
              <PrivateRoute>
                <FacturasListView />
              </PrivateRoute>
            }
          />

          <Route
            path="/facturas-externas"
            element={
              <PrivateRoute>
                <FacturasExternasView />
              </PrivateRoute>
            }
          />

          <Route
            path="/notas"
            element={
              <PrivateRoute>
                <NotasCreditoView />
              </PrivateRoute>
            }
          />

          <Route
            path="/notas-historico"
            element={
              <PrivateRoute>
                <NotasCreditoListView />
              </PrivateRoute>
            }
          />

          <Route
            path="/notas-conceptos"
            element={
              <PrivateRoute>
                <NotasCreditoConceptosView />
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

          <Route
            path="/usuarios/permisos"
            element={
              <PrivateRoute>
                <UsuarioPermisosView />
              </PrivateRoute>
            }
          />

          <Route
            path="/causacion"
            element={
              <PrivateRoute>
                <CausacionPage />
              </PrivateRoute>
            }
          />

          <Route
            path="/causacion/historial"
            element={
              <PrivateRoute>
                <CausacionHistorialPage />
              </PrivateRoute>
            }
          />

          <Route
            path="/causacion/reporte"
            element={
              <PrivateRoute>
                <ReporteCausacionPage />
              </PrivateRoute>
            }
          />

          {/* Rutas de Cotizaciones */}
          <Route
            path="/cotizaciones"
            element={
              <PrivateRoute>
                <CotizacionesListPage />
              </PrivateRoute>
            }
          />
          <Route
            path="/cotizaciones/new"
            element={
              <PrivateRoute>
                <CotizacionFormPage />
              </PrivateRoute>
            }
          />
          <Route
            path="/cotizaciones/edit/:id"
            element={
              <PrivateRoute>
                <CotizacionFormPage />
              </PrivateRoute>
            }
          />
          <Route
            path="/cotizaciones/:id"
            element={
              <PrivateRoute>
                <CotizacionDetailPage />
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
