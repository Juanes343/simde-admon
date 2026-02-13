import React, { useState } from 'react';
import { Form, Button, Alert, InputGroup } from 'react-bootstrap';

const LoginView = ({ onSubmit, loading, error }) => {
  const [formData, setFormData] = useState({
    usuario: '',
    passwd: '',
  });

  const handleChange = (e) => {
    setFormData({
      ...formData,
      [e.target.name]: e.target.value,
    });
  };

  const handleSubmit = (e) => {
    e.preventDefault();
    onSubmit(formData);
  };

  return (
    <Form onSubmit={handleSubmit}>
      {error && <Alert variant="danger">{error}</Alert>}

      <Form.Group className="mb-3">
        <Form.Label>Usuario</Form.Label>
        <InputGroup>
          <InputGroup.Text style={{ background: 'white', border: '2px solid #e9ecef', borderRight: 'none' }}>
            <i className="fas fa-user" style={{ color: '#00b4d8' }}></i>
          </InputGroup.Text>
          <Form.Control
            type="text"
            name="usuario"
            value={formData.usuario}
            onChange={handleChange}
            placeholder="Ingrese su usuario"
            required
            style={{ borderLeft: 'none' }}
          />
        </InputGroup>
      </Form.Group>

      <Form.Group className="mb-3">
        <Form.Label>Contraseña</Form.Label>
        <InputGroup>
          <InputGroup.Text style={{ background: 'white', border: '2px solid #e9ecef', borderRight: 'none' }}>
            <i className="fas fa-lock" style={{ color: '#00b4d8' }}></i>
          </InputGroup.Text>
          <Form.Control
            type="password"
            name="passwd"
            value={formData.passwd}
            onChange={handleChange}
            placeholder="Ingrese su contraseña"
            required
            style={{ borderLeft: 'none' }}
          />
        </InputGroup>
      </Form.Group>

      <Button
        variant="primary"
        type="submit"
        className="w-100"
        disabled={loading}
      >
        {loading ? (
          <>
            <span className="spinner-border spinner-border-sm me-2" role="status"></span>
            Ingresando...
          </>
        ) : (
          <>
            <i className="fas fa-sign-in-alt me-2"></i>
            Iniciar Sesión
          </>
        )}
      </Button>
    </Form>
  );
};

export default LoginView;
