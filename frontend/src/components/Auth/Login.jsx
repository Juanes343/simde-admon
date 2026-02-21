import React, { useState } from 'react';
import { useNavigate, Link } from 'react-router-dom';
import { Form, Button, Container, Alert } from 'react-bootstrap';
import { toast } from 'react-toastify';
import { authService } from '../../services/api';
import './Auth.css';

const Login = () => {
  const navigate = useNavigate();
  const [formData, setFormData] = useState({
    usuario: '',
    passwd: '',
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
    setLoading(true);

    try {
      const response = await authService.login(formData);
      localStorage.setItem('token', response.data.access_token);
      localStorage.setItem('user', JSON.stringify(response.data.user));
      toast.success('Inicio de sesión exitoso');
      navigate('/dashboard');
    } catch (err) {
      const errorMsg = err.response?.data?.message || 'Error al iniciar sesión';
      setError(errorMsg);
      toast.error(errorMsg);
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="auth-container">
      <Container>
        <div className="auth-card">
          <div className="auth-header">
            <h1>SIMDE ADMON</h1>
            <p>Sistema Integral de Gestión</p>
          </div>

          {error && <Alert variant="danger">{error}</Alert>}

          <Form onSubmit={handleSubmit}>
            <Form.Group className="mb-3">
              <Form.Label>Usuario</Form.Label>
              <Form.Control
                type="text"
                name="usuario"
                value={formData.usuario}
                onChange={handleChange}
                placeholder="Ingrese su usuario"
                required
              />
            </Form.Group>

            <Form.Group className="mb-3">
              <Form.Label>Contraseña</Form.Label>
              <Form.Control
                type="password"
                name="passwd"
                value={formData.passwd}
                onChange={handleChange}
                placeholder="Ingrese su contraseña"
                required
              />
            </Form.Group>

            <Button
              variant="primary"
              type="submit"
              className="w-100 mb-3"
              disabled={loading}
            >
              {loading ? 'Ingresando...' : 'Iniciar Sesión'}
            </Button>

            <div className="text-center">
              <Link to="/register" className="text-decoration-none">
                ¿No tienes cuenta? Regístrate
              </Link>
            </div>
          </Form>
        </div>
      </Container>
    </div>
  );
};

export default Login;
