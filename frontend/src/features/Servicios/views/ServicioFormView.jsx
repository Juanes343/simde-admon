import React, { useState, useEffect } from 'react';
import { Form, Button, Row, Col, Card } from 'react-bootstrap';

const ServicioFormView = ({ servicio, onSubmit, onCancel, loading }) => {
  const isEditMode = !!servicio;

  const [formData, setFormData] = useState({
    nombre_servicio: '',
    descripcion: '',
    cantidad: '',
    tipo_unidad: 'UNIDAD',
    precio_unitario: '',
    sw_estado: '1',
  });

  useEffect(() => {
    if (servicio) {
      setFormData({
        nombre_servicio: servicio.nombre_servicio || '',
        descripcion: servicio.descripcion || '',
        cantidad: servicio.cantidad || '',
        tipo_unidad: servicio.tipo_unidad || 'UNIDAD',
        precio_unitario: servicio.precio_unitario || '',
        sw_estado: servicio.sw_estado || '1',
      });
    }
  }, [servicio]);

  const handleChange = (e) => {
    const { name, value, type, checked } = e.target;
    setFormData({
      ...formData,
      [name]: type === 'checkbox' ? (checked ? '1' : '0') : value,
    });
  };

  const handleSubmit = (e) => {
    e.preventDefault();
    onSubmit(formData);
  };

  return (
    <Card>
      <Card.Body>
        <Form onSubmit={handleSubmit}>
          <h5 className="mb-3">
            {isEditMode ? 'Editar Servicio' : 'Nuevo Servicio'}
          </h5>
          
          <Row>
            <Col md={12}>
              <Form.Group className="mb-3">
                <Form.Label>Nombre del Servicio *</Form.Label>
                <Form.Control
                  type="text"
                  name="nombre_servicio"
                  value={formData.nombre_servicio}
                  onChange={handleChange}
                  required
                  maxLength="100"
                  placeholder="Ej: Servicio en la nube"
                />
              </Form.Group>
            </Col>
          </Row>

          <Row>
            <Col md={12}>
              <Form.Group className="mb-3">
                <Form.Label>Descripción</Form.Label>
                <Form.Control
                  as="textarea"
                  rows={3}
                  name="descripcion"
                  value={formData.descripcion}
                  onChange={handleChange}
                  placeholder="Descripción detallada del servicio"
                />
              </Form.Group>
            </Col>
          </Row>

          <Row>
            <Col md={4}>
              <Form.Group className="mb-3">
                <Form.Label>Cantidad (Opcional)</Form.Label>
                <Form.Control
                  type="number"
                  step="0.01"
                  min="0"
                  name="cantidad"
                  value={formData.cantidad}
                  onChange={handleChange}
                  placeholder="Ej: 100"
                />
                <Form.Text className="text-muted">
                  Cantidad por defecto
                </Form.Text>
              </Form.Group>
            </Col>

            <Col md={4}>
              <Form.Group className="mb-3">
                <Form.Label>Tipo de Unidad *</Form.Label>
                <Form.Select
                  name="tipo_unidad"
                  value={formData.tipo_unidad}
                  onChange={handleChange}
                  required
                >
                  <option value="UNIDAD">UNIDAD</option>
                  <option value="HORAS">HORAS</option>
                </Form.Select>
              </Form.Group>
            </Col>

            <Col md={4}>
              <Form.Group className="mb-3">
                <Form.Label>Precio Unitario *</Form.Label>
                <Form.Control
                  type="number"
                  step="0.01"
                  min="0"
                  name="precio_unitario"
                  value={formData.precio_unitario}
                  onChange={handleChange}
                  required
                  placeholder="Ej: 150000"
                />
              </Form.Group>
            </Col>
          </Row>

          <Row>
            <Col md={12}>
              <Form.Group className="mb-3">
                <Form.Check
                  type="checkbox"
                  name="sw_estado"
                  label="Servicio activo"
                  checked={formData.sw_estado === '1'}
                  onChange={handleChange}
                />
              </Form.Group>
            </Col>
          </Row>

          <div className="d-flex justify-content-end">
            <Button variant="secondary" className="me-2" onClick={onCancel}>
              Cancelar
            </Button>
            <Button variant="primary" type="submit" disabled={loading}>
              {loading ? (
                <>
                  <span
                    className="spinner-border spinner-border-sm me-2"
                    role="status"
                  ></span>
                  Guardando...
                </>
              ) : (
                <>
                  <i className="fas fa-save me-2"></i>
                  {isEditMode ? 'Actualizar' : 'Guardar'}
                </>
              )}
            </Button>
          </div>
        </Form>
      </Card.Body>
    </Card>
  );
};

export default ServicioFormView;
