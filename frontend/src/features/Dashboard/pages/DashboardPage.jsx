import React, { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { Container, Row, Col, Card, Button } from 'react-bootstrap';
import MainLayout from '../../../components/Layout/MainLayout';
import './Dashboard.css';

const DashboardPage = () => {
  const navigate = useNavigate();
  const [user, setUser] = useState(null);
  const [selectedModule, setSelectedModule] = useState(null);
  const [modulosPermitidos, setModulosPermitidos] = useState([]);

  useEffect(() => {
    const userData = localStorage.getItem('user');
    if (userData) {
      const parsed = JSON.parse(userData);
      setUser(parsed);
      setModulosPermitidos(parsed.modulos || []);
    }
  }, []);

  const modules = [
    {
      id: 'terceros',
      title: 'TERCEROS',
      icon: 'fa-users',
      description: 'Gestiona la información de terceros, clientes y proveedores.',
      color: '#1e3a5f',
      subModules: [
        {
          id: 'list',
          title: 'Consultar Terceros',
          icon: 'fa-list',
          description: 'Consulta y gestiona el listado completo de terceros.',
          action: () => navigate('/terceros'),
          buttonText: 'Ver Listado',
          buttonColor: 'primary'
        },
        {
          id: 'new',
          title: 'Nuevo Tercero',
          icon: 'fa-plus-circle',
          description: 'Crea un nuevo tercero manualmente.',
          action: () => navigate('/terceros/new'),
          buttonText: 'Crear Tercero',
          buttonColor: 'success'
        },
        {
          id: 'upload',
          title: 'Cargar RUT',
          icon: 'fa-file-pdf',
          description: 'Carga un PDF del RUT para crear tercero automáticamente.',
          action: () => navigate('/terceros/upload-pdf'),
          buttonText: 'Subir PDF',
          buttonColor: 'warning'
        }
      ]
    },
    {
      id: 'servicios',
      title: 'SERVICIOS',
      icon: 'fa-cogs',
      description: 'Parametriza y gestiona los servicios que ofrece tu empresa.',
      color: '#2c5f2d',
      subModules: [
        {
          id: 'list',
          title: 'Consultar Servicios',
          icon: 'fa-list',
          description: 'Consulta y gestiona el listado de servicios.',
          action: () => navigate('/servicios'),
          buttonText: 'Ver Servicios',
          buttonColor: 'primary'
        },
        {
          id: 'new',
          title: 'Nuevo Servicio',
          icon: 'fa-plus-circle',
          description: 'Crea un nuevo servicio con precio y tipo de unidad.',
          action: () => navigate('/servicios/new'),
          buttonText: 'Crear Servicio',
          buttonColor: 'success'
        }
      ]
    },
    {
      id: 'ordenes',
      title: 'ÓRDENES DE SERVICIO',
      icon: 'fa-file-contract',
      description: 'Gestiona las órdenes de servicio asociadas a terceros.',
      color: '#8b4513',
      subModules: [
        {
          id: 'list',
          title: 'Consultar Órdenes',
          icon: 'fa-list',
          description: 'Consulta y gestiona el listado de órdenes de servicio.',
          action: () => navigate('/ordenes-servicio'),
          buttonText: 'Ver Órdenes',
          buttonColor: 'primary'
        },
        {
          id: 'new',
          title: 'Nueva Orden',
          icon: 'fa-plus-circle',
          description: 'Crea una nueva orden de servicio con tercero y servicios.',
          action: () => navigate('/ordenes-servicio/new'),
          buttonText: 'Crear Orden',
          buttonColor: 'success'
        }
      ]    },
    {
      id: 'facturacion',
      title: 'FACTURACIÓN',
      icon: 'fa-file-invoice-dollar',
      description: 'Genera facturas a partir de órdenes de servicio pendientes.',
      color: '#d35400',
      subModules: [
        {
          id: 'generar',
          title: 'Generar Factura',
          icon: 'fa-plus-circle',
          description: 'Selecciona órdenes pendientes y genera facturas.',
          action: () => navigate('/facturacion'),
          buttonText: 'Ir a Facturación',
          buttonColor: 'success'
        },
        {
          id: 'list',
          title: 'Consultar Facturas',
          icon: 'fa-list',
          description: 'Consulta el historial de facturas generadas.',
          action: () => navigate('/facturas'),
          buttonText: 'Ver Histórico',
          buttonColor: 'primary'
        },
        {
          id: 'externas',
          title: 'Cargar Facturas Externas',
          icon: 'fa-file-upload',
          description: 'Carga masiva de facturas externas mediante archivo CSV.',
          action: () => navigate('/facturas-externas'),
          buttonText: 'Cargar CSV',
          buttonColor: 'warning'
        }
      ]
    },
    {
      id: 'notas',
      title: 'NOTAS CREDITO/DEBITO',
      icon: 'fa-file-signature',
      description: 'Gestiona notas credito y debito asociadas a facturas.',
      color: '#6c4a3a',
      subModules: [
        {
          id: 'generar',
          title: 'Crear Nota',
          icon: 'fa-plus-circle',
          description: 'Selecciona factura e items para crear una nota.',
          action: () => navigate('/notas'),
          buttonText: 'Crear Nota',
          buttonColor: 'success'
        },
        {
          id: 'list',
          title: 'Consultar Notas',
          icon: 'fa-list',
          description: 'Consulta el historial de notas credito.',
          action: () => navigate('/notas-historico'),
          buttonText: 'Ver Histórico',
          buttonColor: 'primary'
        },
        {
          id: 'conceptos',
          title: 'Parametrizar Conceptos',
          icon: 'fa-tags',
          description: 'Crea y gestiona los conceptos disponibles para notas crédito.',
          action: () => navigate('/notas-conceptos'),
          buttonText: 'Gestionar Conceptos',
          buttonColor: 'warning'
        }
      ]
    },
    {
      id: 'usuarios',
      title: 'GESTIÓN DE USUARIOS',
      icon: 'fa-user-shield',
      description: 'Administra usuarios y permisos de acceso a módulos.',
      color: '#4a235a',
      subModules: [
        {
          id: 'permisos',
          title: 'Permisos por Módulo',
          icon: 'fa-lock',
          description: 'Asigna o revoca acceso a módulos por usuario.',
          action: () => navigate('/usuarios/permisos'),
          buttonText: 'Gestionar Permisos',
          buttonColor: 'primary'
        }
      ]
    },
    {
      id: 'causacion',
      title: 'CAUSACIÓN',
      icon: 'fa-money-bill-wave',
      description: 'Registra pagos, aplica anticipos y cruza saldos de facturas.',
      color: '#1a5276',
      subModules: [
        {
          id: 'pagos',
          title: 'Registrar Pagos',
          icon: 'fa-money-check-alt',
          description: 'Selecciona un tercero y aplica pagos a sus facturas pendientes.',
          action: () => navigate('/causacion'),
          buttonText: 'Ir a Causación',
          buttonColor: 'primary'
        },
        {
          id: 'reporte',
          title: 'Reporte de Facturas y Pagos',
          icon: 'fa-file-excel',
          description: 'Genera y descarga en Excel el reporte de facturas (internas y externas) y pagos por fecha.',
          action: () => navigate('/causacion/reporte'),
          buttonText: 'Generar Reporte',
          buttonColor: 'success'
        }
      ]
    },
    {
      id: 'cotizaciones',
      title: 'COTIZACIONES',
      icon: 'fa-file-alt',
      description: 'Crea y gestiona cotizaciones. Conviértelas en órdenes de servicio al ser aprobadas.',
      color: '#117a65',
      subModules: [
        {
          id: 'list',
          title: 'Consultar Cotizaciones',
          icon: 'fa-list',
          description: 'Consulta y gestiona el listado de cotizaciones.',
          action: () => navigate('/cotizaciones'),
          buttonText: 'Ver Cotizaciones',
          buttonColor: 'primary'
        },
        {
          id: 'new',
          title: 'Nueva Cotización',
          icon: 'fa-plus-circle',
          description: 'Crea una nueva cotización para un cliente.',
          action: () => navigate('/cotizaciones/new'),
          buttonText: 'Crear Cotización',
          buttonColor: 'success'
        }
      ]
    }
  ];

  const renderModules = () => (
    <>
      <Row className="mb-4">
        <Col>
          <h2>Bienvenido, {user?.primer_nombre || 'SIMDE'} {user?.primer_apellido || 'SIIS'}</h2>
          <p className="text-muted">Sistema Integral de Gestión - SIMDE ADMON</p>
          {modulosPermitidos.length === 0 && (
            <div className="alert alert-warning mt-3" role="alert">
              <i className="fas fa-exclamation-triangle me-2"></i>
              No tienes módulos asignados. Contacta al administrador del sistema.
            </div>
          )}
        </Col>
      </Row>

      <Row className="g-4">
        {modules
          .filter((m) => modulosPermitidos.includes(m.id))
          .map((module) => (
          <Col key={module.id} lg={6} xl={4}>
            <Card 
              className="module-card h-100" 
              onClick={() => setSelectedModule(module)}
              style={{ 
                cursor: 'pointer',
                background: `linear-gradient(135deg, ${module.color} 0%, ${module.color}dd 100%)`,
                color: 'white',
                border: 'none',
                boxShadow: '0 4px 12px rgba(0,0,0,0.15)',
                transition: 'transform 0.2s, box-shadow 0.2s'
              }}
            >
              <Card.Body className="d-flex flex-column p-4">
                <div className="mb-4">
                  <i className={`fas ${module.icon} fa-4x opacity-75`}></i>
                </div>
                <Card.Title className="h3 mb-3">{module.title}</Card.Title>
                <Card.Text className="flex-grow-1" style={{ fontSize: '1.05rem', opacity: 0.95 }}>
                  {module.description}
                </Card.Text>
                <div className="mt-3">
                  <span className="badge bg-white text-dark px-3 py-2">
                    {module.subModules.length} opciones disponibles
                  </span>
                </div>
              </Card.Body>
            </Card>
          </Col>
        ))}
      </Row>
    </>
  );

  const renderSubModules = () => (
    <>
      <Row className="mb-4">
        <Col>
          <Button 
            variant="outline-secondary" 
            onClick={() => setSelectedModule(null)}
            className="mb-2"
          >
            <i className="fas fa-arrow-left me-2"></i>
            Volver al inicio
          </Button>
          <h2 className="mt-3">{selectedModule.title}</h2>
          <p className="text-muted">{selectedModule.description}</p>
        </Col>
      </Row>

      <Row className="g-4">
        {selectedModule.subModules.map((subModule) => (
          <Col key={subModule.id} lg={6} xl={4}>
            <Card 
              className="submodule-card h-100"
              style={{
                border: '2px solid #e0e0e0',
                transition: 'all 0.3s ease',
                cursor: 'pointer'
              }}
              onClick={subModule.action}
            >
              <Card.Body className="d-flex flex-column p-4">
                <div className="mb-3">
                  <div 
                    className="icon-wrapper d-flex align-items-center justify-content-center"
                    style={{
                      width: '70px',
                      height: '70px',
                      borderRadius: '12px',
                      background: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
                      color: 'white'
                    }}
                  >
                    <i className={`fas ${subModule.icon} fa-2x`}></i>
                  </div>
                </div>
                <Card.Title className="h4 mb-3">{subModule.title}</Card.Title>
                <Card.Text className="flex-grow-1 text-muted">
                  {subModule.description}
                </Card.Text>
                <div className="mt-3">
                  <Button 
                    variant={subModule.buttonColor} 
                    className="w-100"
                    size="lg"
                  >
                    {subModule.buttonText}
                  </Button>
                </div>
              </Card.Body>
            </Card>
          </Col>
        ))}
      </Row>
    </>
  );

  return (
    <MainLayout>
      <Container fluid className="py-4">
        {selectedModule ? renderSubModules() : renderModules()}
      </Container>
    </MainLayout>
  );
};

export default DashboardPage;
