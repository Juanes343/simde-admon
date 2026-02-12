import React, { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { Container, Row, Col, Card } from 'react-bootstrap';
import MainLayout from '../../../components/Layout/MainLayout';
import './Dashboard.css';

const DashboardPage = () => {
  const navigate = useNavigate();
  const [user, setUser] = useState(null);

  useEffect(() => {
    const userData = localStorage.getItem('user');
    if (userData) {
      setUser(JSON.parse(userData));
    }
  }, []);

  return (
    <MainLayout>
      <Container fluid>
        <Row className="mb-4">
          <Col>
            <h2>Bienvenido, {user?.primer_nombre} {user?.primer_apellido}</h2>
            <p className="text-muted">Sistema Integral de Gestión - SIMDE ADMON</p>
          </Col>
        </Row>

        <Row>
          <Col md={4} className="mb-3">
            <Card className="dashboard-card" onClick={() => navigate('/terceros')}>
              <Card.Body>
                <Card.Title>
                  <i className="fas fa-users"></i> Terceros
                </Card.Title>
                <Card.Text>
                  Gestiona la información de terceros, clientes y proveedores.
                </Card.Text>
                <div className="btn btn-primary">Ir a Terceros</div>
              </Card.Body>
            </Card>
          </Col>

          <Col md={4} className="mb-3">
            <Card className="dashboard-card" onClick={() => navigate('/terceros/new')}>
              <Card.Body>
                <Card.Title>
                  <i className="fas fa-plus-circle"></i> Nuevo Tercero
                </Card.Title>
                <Card.Text>
                  Crea un nuevo tercero manualmente.
                </Card.Text>
                <div className="btn btn-success">Crear Tercero</div>
              </Card.Body>
            </Card>
          </Col>

          <Col md={4} className="mb-3">
            <Card className="dashboard-card" onClick={() => navigate('/terceros/upload-pdf')}>
              <Card.Body>
                <Card.Title>
                  <i className="fas fa-file-pdf"></i> Cargar RUT
                </Card.Title>
                <Card.Text>
                  Carga un PDF del RUT para crear tercero automáticamente.
                </Card.Text>
                <div className="btn btn-warning">Subir PDF</div>
              </Card.Body>
            </Card>
          </Col>
        </Row>

        <Row className="mt-4">
          <Col>
            <Card>
              <Card.Header>
                <h5>Información del Sistema</h5>
              </Card.Header>
              <Card.Body>
                <Row>
                  <Col md={6}>
                    <p><strong>Fecha:</strong> {new Date().toLocaleDateString('es-CO')}</p>
                    <p><strong>Hora:</strong> {new Date().toLocaleTimeString('es-CO')}</p>
                  </Col>
                  <Col md={6}>
                    <p><strong>Usuario Activo:</strong> {user?.usuario}</p>
                    <p><strong>Email:</strong> {user?.email}</p>
                  </Col>
                </Row>
              </Card.Body>
            </Card>
          </Col>
        </Row>
      </Container>
    </MainLayout>
  );
};

export default DashboardPage;
