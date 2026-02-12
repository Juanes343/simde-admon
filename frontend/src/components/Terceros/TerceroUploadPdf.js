import React, { useState } from 'react';
import { Container, Card, Form, Button, Alert, Navbar, Nav } from 'react-bootstrap';
import { useNavigate } from 'react-router-dom';
import { toast } from 'react-toastify';
import { terceroService, authService } from '../../services/api';

const TerceroUploadPdf = () => {
  const navigate = useNavigate();
  const [selectedFile, setSelectedFile] = useState(null);
  const [loading, setLoading] = useState(false);
  const [preview, setPreview] = useState(null);

  const handleFileChange = (e) => {
    const file = e.target.files[0];
    if (file && file.type === 'application/pdf') {
      setSelectedFile(file);
      setPreview(file.name);
    } else {
      toast.error('Por favor seleccione un archivo PDF válido');
      e.target.value = null;
    }
  };

  const handleSubmit = async (e) => {
    e.preventDefault();

    if (!selectedFile) {
      toast.error('Por favor seleccione un archivo PDF');
      return;
    }

    const formData = new FormData();
    formData.append('pdf', selectedFile);

    setLoading(true);

    try {
      const response = await terceroService.uploadPdf(formData);
      toast.success('Tercero creado exitosamente desde PDF');
      navigate('/terceros');
    } catch (error) {
      const errorMsg =
        error.response?.data?.message || 'Error al procesar el PDF';
      toast.error(errorMsg);
    } finally {
      setLoading(false);
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

      <Container>
        <div className="d-flex justify-content-between align-items-center mb-4">
          <h2>Cargar RUT desde PDF</h2>
          <Button variant="secondary" onClick={() => navigate('/terceros')}>
            <i className="fas fa-arrow-left"></i> Volver
          </Button>
        </div>

        <Card>
          <Card.Body>
            <Alert variant="info">
              <strong>Instrucciones:</strong>
              <ul className="mb-0 mt-2">
                <li>Seleccione un archivo PDF del RUT del tercero</li>
                <li>El sistema intentará extraer automáticamente la información</li>
                <li>
                  Asegúrese de que el PDF contenga información clara del NIT, Razón
                  Social y Dirección
                </li>
              </ul>
            </Alert>

            <Form onSubmit={handleSubmit}>
              <Form.Group className="mb-3">
                <Form.Label>
                  <strong>Seleccionar archivo PDF *</strong>
                </Form.Label>
                <Form.Control
                  type="file"
                  accept=".pdf"
                  onChange={handleFileChange}
                  required
                />
                {preview && (
                  <Form.Text className="text-muted">
                    Archivo seleccionado: <strong>{preview}</strong>
                  </Form.Text>
                )}
              </Form.Group>

              <div className="d-flex justify-content-end">
                <Button
                  variant="secondary"
                  className="me-2"
                  onClick={() => navigate('/terceros')}
                >
                  Cancelar
                </Button>
                <Button variant="warning" type="submit" disabled={loading}>
                  {loading ? (
                    <>
                      <span
                        className="spinner-border spinner-border-sm me-2"
                        role="status"
                      ></span>
                      Procesando...
                    </>
                  ) : (
                    <>
                      <i className="fas fa-upload me-2"></i>
                      Cargar y Crear Tercero
                    </>
                  )}
                </Button>
              </div>
            </Form>
          </Card.Body>
        </Card>

        <Card className="mt-4">
          <Card.Header>
            <strong>Información Adicional</strong>
          </Card.Header>
          <Card.Body>
            <h6>Formato del RUT requerido:</h6>
            <p className="mb-2">
              El PDF debe contener al menos la siguiente información:
            </p>
            <ul>
              <li>
                <strong>NIT:</strong> Número de Identificación Tributaria
              </li>
              <li>
                <strong>Razón Social:</strong> Nombre completo o razón social del
                tercero
              </li>
              <li>
                <strong>Dirección:</strong> Dirección principal del tercero
              </li>
            </ul>
            <Alert variant="warning" className="mb-0">
              <small>
                <strong>Nota:</strong> Si el sistema no puede extraer la
                información automáticamente, deberá crear el tercero manualmente
                desde el formulario.
              </small>
            </Alert>
          </Card.Body>
        </Card>
      </Container>
    </div>
  );
};

export default TerceroUploadPdf;
