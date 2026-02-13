import React, { useState } from 'react';
import { useNavigate, Link } from 'react-router-dom';
import { toast } from 'react-toastify';
import RegisterView from '../views/RegisterView';
import { authService } from '../services/authService';
import './Auth.css';

const RegisterPage = () => {
  const navigate = useNavigate();
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');

  const handleSubmit = async (formData) => {
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
      localStorage.setItem('token', response.access_token);
      localStorage.setItem('user', JSON.stringify(response.user));
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
      <div className="auth-card" style={{ maxWidth: '700px' }}>
        <div className="auth-header">
          <h1>SIMDE ADMON</h1>
          <p>Registro de Nueva Cuenta</p>
        </div>

        <RegisterView onSubmit={handleSubmit} loading={loading} error={error} />

        <div className="text-center mt-3">
          <Link to="/login" className="text-decoration-none">
            ¿Ya tienes cuenta? Inicia sesión
          </Link>
        </div>
      </div>
    </div>
  );
};

export default RegisterPage;
