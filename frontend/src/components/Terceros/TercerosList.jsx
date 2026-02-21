import React, { useState, useEffect } from 'react';
import { Container, Table, Button, Form, InputGroup, Badge, Navbar, Nav } from 'react-bootstrap';
import { useNavigate } from 'react-router-dom';
import { toast } from 'react-toastify';
import { terceroService, authService } from '../../services/api';
import './Terceros.css';

const TercerosList = () => {
  const navigate = useNavigate();
  const [terceros, setTerceros] = useState([]);
  const [loading, setLoading] = useState(false);
  const [search, setSearch] = useState('');
  const [currentPage, setCurrentPage] = useState(1);
  const [totalPages, setTotalPages] = useState(1);

  useEffect(() => {
    fetchTerceros();
  }, [search, currentPage]);

  const fetchTerceros = async () => {
    setLoading(true);
    try {
      const response = await terceroService.getAll({
        search,
        page: currentPage,
        per_page: 15,
      });
      setTerceros(response.data.data);
      setTotalPages(response.data.last_page);
    } catch (error) {
      toast.error('Error al cargar terceros');
      console.error(error);
    } finally {
      setLoading(false);
    }
  };

  const handleDelete = async (tipo_id_tercero, tercero_id) => {
    if (window.confirm('¿Está seguro de desactivar este tercero?')) {
      try {
        await terceroService.delete(tipo_id_tercero, tercero_id);
        toast.success('Tercero desactivado exitosamente');
        fetchTerceros();
      } catch (error) {
        toast.error('Error al desactivar tercero');
      }
    }
  };

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
    <div>
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
              <Button variant="outline-light" size="sm" onClick={handleLogout}>
                Cerrar Sesión
              </Button>
            </Nav>
          </Navbar.Collapse>
        </Container>
      </Navbar>

      <Container fluid>
        <div className="d-flex justify-content-between align-items-center mb-4">
          <h2>Gestión de Terceros</h2>
          <div>
            <Button
              variant="warning"
              className="me-2"
              onClick={() => navigate('/terceros/upload-pdf')}
            >
              <i className="fas fa-file-pdf"></i> Cargar RUT PDF
            </Button>
            <Button
              variant="primary"
              onClick={() => navigate('/terceros/new')}
            >
              <i className="fas fa-plus"></i> Nuevo Tercero
            </Button>
          </div>
        </div>

        <InputGroup className="mb-3">
          <Form.Control
            placeholder="Buscar por nombre, ID o email..."
            value={search}
            onChange={(e) => setSearch(e.target.value)}
          />
          <Button variant="outline-secondary" onClick={fetchTerceros}>
            <i className="fas fa-search"></i> Buscar
          </Button>
        </InputGroup>

        {loading ? (
          <div className="text-center">
            <div className="spinner-border" role="status">
              <span className="visually-hidden">Cargando...</span>
            </div>
          </div>
        ) : (
          <>
            <div className="table-responsive">
              <Table striped bordered hover>
                <thead>
                  <tr>
                    <th>Tipo ID</th>
                    <th>Identificación</th>
                    <th>Nombre</th>
                    <th>Email</th>
                    <th>Teléfono</th>
                    <th>Dirección</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                  </tr>
                </thead>
                <tbody>
                  {terceros.length === 0 ? (
                    <tr>
                      <td colSpan="8" className="text-center">
                        No se encontraron terceros
                      </td>
                    </tr>
                  ) : (
                    terceros.map((tercero) => (
                      <tr key={`${tercero.tipo_id_tercero}-${tercero.tercero_id}`}>
                        <td>{tercero.tipo_id_tercero}</td>
                        <td>{tercero.tercero_id}</td>
                        <td>{tercero.nombre_tercero}</td>
                        <td>{tercero.email || '-'}</td>
                        <td>{tercero.telefono || '-'}</td>
                        <td>{tercero.direccion}</td>
                        <td>
                          <Badge bg={tercero.sw_estado === '1' ? 'success' : 'danger'}>
                            {tercero.sw_estado === '1' ? 'Activo' : 'Inactivo'}
                          </Badge>
                        </td>
                        <td>
                          <Button
                            size="sm"
                            variant="info"
                            className="me-1"
                            onClick={() =>
                              navigate(
                                `/terceros/edit/${tercero.tipo_id_tercero}/${tercero.tercero_id}`
                              )
                            }
                          >
                            <i className="fas fa-edit"></i>
                          </Button>
                          <Button
                            size="sm"
                            variant="danger"
                            onClick={() =>
                              handleDelete(tercero.tipo_id_tercero, tercero.tercero_id)
                            }
                          >
                            <i className="fas fa-trash"></i>
                          </Button>
                        </td>
                      </tr>
                    ))
                  )}
                </tbody>
              </Table>
            </div>

            <div className="d-flex justify-content-center">
              <Button
                variant="secondary"
                disabled={currentPage === 1}
                onClick={() => setCurrentPage(currentPage - 1)}
                className="me-2"
              >
                Anterior
              </Button>
              <span className="align-self-center mx-3">
                Página {currentPage} de {totalPages}
              </span>
              <Button
                variant="secondary"
                disabled={currentPage === totalPages}
                onClick={() => setCurrentPage(currentPage + 1)}
              >
                Siguiente
              </Button>
            </div>
          </>
        )}
      </Container>
    </div>
  );
};

export default TercerosList;
