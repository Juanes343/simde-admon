import React, { useState } from 'react';
import { useNavigate, Link } from 'react-router-dom';
import { Container } from 'react-bootstrap';
import { toast } from 'react-toastify';
import LoginView from '../views/LoginView';
import { authService } from '../services/authService';
import './Auth.css';

const LoginPage = () => {
  const navigate = useNavigate();
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');

  const handleSubmit = async (formData) => {
    setError('');
    setLoading(true);

    try {
      const response = await authService.login(formData);
      localStorage.setItem('token', response.access_token);
      localStorage.setItem('user', JSON.stringify(response.user));
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

          <LoginView onSubmit={handleSubmit} loading={loading} error={error} />

          <div className="text-center mt-3">
            <Link to="/register" className="text-decoration-none">
              ¿No tienes cuenta? Regístrate
            </Link>
          </div>
        </div>
      </Container>
    </div>
  );
};

export default LoginPage;
