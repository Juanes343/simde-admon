import React from 'react';
import { Navbar, Nav, Container, Button } from 'react-bootstrap';
import { useNavigate } from 'react-router-dom';
import { toast } from 'react-toastify';
import { authService } from '../../features/Auth/services/authService';

const AppNavbar = () => {
  const navigate = useNavigate();
  const user = JSON.parse(localStorage.getItem('user') || '{}');

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
    <Navbar bg="dark" variant="dark" expand="lg" className="mb-4">
      <Container fluid>
        <Navbar.Brand onClick={() => navigate('/dashboard')} style={{ cursor: 'pointer' }}>
          <strong>SIMDE ADMON</strong>
        </Navbar.Brand>
        <Navbar.Toggle aria-controls="basic-navbar-nav" />
        <Navbar.Collapse id="basic-navbar-nav">
          <Nav className="me-auto">
            <Nav.Link onClick={() => navigate('/dashboard')}>Dashboard</Nav.Link>
            <Nav.Link onClick={() => navigate('/terceros')}>Terceros</Nav.Link>
            <Nav.Link onClick={() => navigate('/servicios')}>Servicios</Nav.Link>
            <Nav.Link onClick={() => navigate('/ordenes-servicio')}>Órdenes de Servicio</Nav.Link>
            <Nav.Link onClick={() => navigate('/facturacion')}>Facturación</Nav.Link>
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
  );
};

export default AppNavbar;
