import React, { useState } from 'react';
import { Form, Button, Alert, Row, Col } from 'react-bootstrap';

const RegisterView = ({ onSubmit, loading, error }) => {
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

  const handleChange = (e) => {
    setFormData({
      ...formData,
      [e.target.name]: e.target.value,
    });
  };

  const handleSubmit = (e) => {
    e.preventDefault();
    if (formData.passwd !== formData.confirmPasswd) {
      return;
    }
    onSubmit(formData);
  };

  return (
    <Form onSubmit={handleSubmit}>
      {error && <Alert variant="danger">{error}</Alert>}

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
        className="w-100"
        disabled={loading}
      >
        {loading ? 'Registrando...' : 'Registrarse'}
      </Button>
    </Form>
  );
};

export default RegisterView;
