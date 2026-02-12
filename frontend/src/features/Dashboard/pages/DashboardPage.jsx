import React, { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { Container, Row, Col, Card, Button } from 'react-bootstrap';
import MainLayout from '../../../components/Layout/MainLayout';
import './Dashboard.css';

const DashboardPage = () => {
  const navigate = useNavigate();
  const [user, setUser] = useState(null);
  const [selectedModule, setSelectedModule] = useState(null);

  useEffect(() => {
    const userData = localStorage.getItem('user');
    if (userData) {
      setUser(JSON.parse(userData));
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
    }
  ];

  const renderModules = () => (
    <>
      <Row className="mb-4">
        <Col>
          <h2>Bienvenido, {user?.primer_nombre || 'SIMDE'} {user?.primer_apellido || 'SIIS'}</h2>
          <p className="text-muted">Sistema Integral de Gestión - SIMDE ADMON</p>
        </Col>
      </Row>

      <Row className="g-4">
        {modules.map((module) => (
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

      <Row className="mt-5">
        <Col>
          <Card className="info-card">
            <Card.Header className="bg-dark text-white">
              <h5 className="mb-0">Información del Sistema</h5>
            </Card.Header>
            <Card.Body>
              <Row>
                <Col md={6}>
                  <p className="mb-2"><strong>Fecha:</strong> {new Date().toLocaleDateString('es-CO')}</p>
                  <p className="mb-2"><strong>Hora:</strong> {new Date().toLocaleTimeString('es-CO')}</p>
                </Col>
                <Col md={6}>
                  <p className="mb-2"><strong>Usuario Activo:</strong> {user?.usuario}</p>
                  <p className="mb-2"><strong>Email:</strong> {user?.email}</p>
                </Col>
              </Row>
            </Card.Body>
          </Card>
        </Col>
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
