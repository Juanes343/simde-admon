import React, { useState, useEffect } from 'react';
import { Container, Row, Col, Card, Button, Navbar, Nav } from 'react-bootstrap';
import { useNavigate } from 'react-router-dom';
import { toast } from 'react-toastify';
import { authService } from '../../services/api';
import './Dashboard.css';

const Dashboard = () => {
  const navigate = useNavigate();
  const [user, setUser] = useState(null);

  useEffect(() => {
    const userData = localStorage.getItem('user');
    if (userData) {
      setUser(JSON.parse(userData));
    }
  }, []);

  const handleLogout = async () => {
    try {
      await authService.logout();
      localStorage.removeItem('token');
      localStorage.removeItem('user');
      toast.success('Sesión cerrada exitosamente');
      navigate('/login');
    } catch (error) {
      console.error('Error al cerrar sesión:', error);
      localStorage.removeItem('token');
      localStorage.removeItem('user');
      navigate('/login');
    }
  };

  return (
    <div className="dashboard-container">
      <Navbar bg="dark" variant="dark" expand="lg" className="mb-4">
        <Container fluid>
          <Navbar.Brand href="/dashboard">
            <strong>SIMDE ADMON</strong>
          </Navbar.Brand>
          <Navbar.Toggle aria-controls="basic-navbar-nav" />
          <Navbar.Collapse id="basic-navbar-nav">
            <Nav className="me-auto">
              <Nav.Link onClick={() => navigate('/dashboard')}>Dashboard</Nav.Link>
              <Nav.Link onClick={() => navigate('/terceros')}>Terceros</Nav.Link>
            </Nav>
            <Nav>
              <Navbar.Text className="me-3">
                Usuario: <strong>{user?.usuario}</strong>
              </Navbar.Text>
              <Button variant="outline-light" size="sm" onClick={handleLogout}>
                Cerrar Sesión
              </Button>
            </Nav>
          </Navbar.Collapse>
        </Container>
      </Navbar>

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
                <Button variant="primary">Ir a Terceros</Button>
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
                <Button variant="success">Crear Tercero</Button>
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
                <Button variant="warning">Subir PDF</Button>
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
    </div>
  );
};

export default Dashboard;
