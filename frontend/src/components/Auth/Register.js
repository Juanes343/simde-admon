import React, { useState } from 'react';
import { useNavigate, Link } from 'react-router-dom';
import { Form, Button, Container, Alert, Row, Col } from 'react-bootstrap';
import { toast } from 'react-toastify';
import { authService } from '../../services/api';
import './Auth.css';

const Register = () => {
  const navigate = useNavigate();
  const [formData, setFormData] = useState({
    usuario: '',
    email: '',
    passwd: '',
    confirmPasswd: '',
    nombre: '',
    primer_nombre: '',
    segundo_nombre: '',
    primer_apellido: '',
    segundo_apellido: '',
    telefono: '',
    tel_celular: '',
    descripcion: '',
  });
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');

  const handleChange = (e) => {
    setFormData({
      ...formData,
      [e.target.name]: e.target.value,
    });
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setError('');

    if (formData.passwd !== formData.confirmPasswd) {
      setError('Las contraseñas no coinciden');
      return;
    }

    if (formData.passwd.length < 8) {
      setError('La contraseña debe tener al menos 8 caracteres');
      return;
    }

    setLoading(true);

    try {
      const { confirmPasswd, ...dataToSend } = formData;
      const response = await authService.register(dataToSend);
      localStorage.setItem('token', response.data.access_token);
      localStorage.setItem('user', JSON.stringify(response.data.user));
      toast.success('Registro exitoso');
      navigate('/dashboard');
    } catch (err) {
      const errorMsg = err.response?.data?.message || 'Error al registrarse';
      setError(errorMsg);
      toast.error(errorMsg);
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="auth-container">
      <Container>
        <div className="auth-card" style={{ maxWidth: '700px' }}>
          <div className="auth-header">
            <h1>SIMDE ADMON</h1>
            <p>Registro de Nueva Cuenta</p>
          </div>

          {error && <Alert variant="danger">{error}</Alert>}

          <Form onSubmit={handleSubmit}>
            <Row>
              <Col md={6}>
                <Form.Group className="mb-3">
                  <Form.Label>Usuario *</Form.Label>
                  <Form.Control
                    type="text"
                    name="usuario"
                    value={formData.usuario}
                    onChange={handleChange}
                    placeholder="Usuario"
                    required
                    maxLength="25"
                  />
                </Form.Group>
              </Col>
              <Col md={6}>
                <Form.Group className="mb-3">
                  <Form.Label>Email *</Form.Label>
                  <Form.Control
                    type="email"
                    name="email"
                    value={formData.email}
                    onChange={handleChange}
                    placeholder="correo@ejemplo.com"
                    required
                  />
                </Form.Group>
              </Col>
            </Row>

            <Row>
              <Col md={6}>
                <Form.Group className="mb-3">
                  <Form.Label>Primer Nombre *</Form.Label>
                  <Form.Control
                    type="text"
                    name="primer_nombre"
                    value={formData.primer_nombre}
                    onChange={handleChange}
                    placeholder="Primer Nombre"
                    required
                    maxLength="20"
                  />
                </Form.Group>
              </Col>
              <Col md={6}>
                <Form.Group className="mb-3">
                  <Form.Label>Segundo Nombre</Form.Label>
                  <Form.Control
                    type="text"
                    name="segundo_nombre"
                    value={formData.segundo_nombre}
                    onChange={handleChange}
                    placeholder="Segundo Nombre"
                    maxLength="30"
                  />
                </Form.Group>
              </Col>
            </Row>

            <Row>
              <Col md={6}>
                <Form.Group className="mb-3">
                  <Form.Label>Primer Apellido *</Form.Label>
                  <Form.Control
                    type="text"
                    name="primer_apellido"
                    value={formData.primer_apellido}
                    onChange={handleChange}
                    placeholder="Primer Apellido"
                    required
                    maxLength="20"
                  />
                </Form.Group>
              </Col>
              <Col md={6}>
                <Form.Group className="mb-3">
                  <Form.Label>Segundo Apellido</Form.Label>
                  <Form.Control
                    type="text"
                    name="segundo_apellido"
                    value={formData.segundo_apellido}
                    onChange={handleChange}
                    placeholder="Segundo Apellido"
                    maxLength="30"
                  />
                </Form.Group>
              </Col>
            </Row>

            <Form.Group className="mb-3">
              <Form.Label>Nombre Completo *</Form.Label>
              <Form.Control
                type="text"
                name="nombre"
                value={formData.nombre}
                onChange={handleChange}
                placeholder="Nombre completo"
                required
                maxLength="60"
              />
            </Form.Group>

            <Row>
              <Col md={6}>
                <Form.Group className="mb-3">
                  <Form.Label>Teléfono</Form.Label>
                  <Form.Control
                    type="text"
                    name="telefono"
                    value={formData.telefono}
                    onChange={handleChange}
                    placeholder="Teléfono"
                    maxLength="30"
                  />
                </Form.Group>
              </Col>
              <Col md={6}>
                <Form.Group className="mb-3">
                  <Form.Label>Celular</Form.Label>
                  <Form.Control
                    type="text"
                    name="tel_celular"
                    value={formData.tel_celular}
                    onChange={handleChange}
                    placeholder="Celular"
                    maxLength="30"
                  />
                </Form.Group>
              </Col>
            </Row>

            <Form.Group className="mb-3">
              <Form.Label>Descripción</Form.Label>
              <Form.Control
                as="textarea"
                rows={2}
                name="descripcion"
                value={formData.descripcion}
                onChange={handleChange}
                placeholder="Descripción o cargo"
                maxLength="255"
              />
            </Form.Group>

            <Row>
              <Col md={6}>
                <Form.Group className="mb-3">
                  <Form.Label>Contraseña *</Form.Label>
                  <Form.Control
                    type="password"
                    name="passwd"
                    value={formData.passwd}
                    onChange={handleChange}
                    placeholder="Mínimo 8 caracteres"
                    required
                    minLength="8"
                  />
                </Form.Group>
              </Col>
              <Col md={6}>
                <Form.Group className="mb-3">
                  <Form.Label>Confirmar Contraseña *</Form.Label>
                  <Form.Control
                    type="password"
                    name="confirmPasswd"
                    value={formData.confirmPasswd}
                    onChange={handleChange}
                    placeholder="Confirmar contraseña"
                    required
                  />
                </Form.Group>
              </Col>
            </Row>

            <Button
              variant="primary"
              type="submit"
              className="w-100 mb-3"
              disabled={loading}
            >
              {loading ? 'Registrando...' : 'Registrarse'}
            </Button>

            <div className="text-center">
              <Link to="/login" className="text-decoration-none">
                ¿Ya tienes cuenta? Inicia sesión
              </Link>
            </div>
          </Form>
        </div>
      </Container>
    </div>
  );
};

export default Register;
