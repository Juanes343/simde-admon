import React, { useState } from 'react';
import { Form, Button, Alert } from 'react-bootstrap';

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
        className="w-100"
        disabled={loading}
      >
        {loading ? 'Ingresando...' : 'Iniciar Sesión'}
      </Button>
    </Form>
  );
};

export default LoginView;
