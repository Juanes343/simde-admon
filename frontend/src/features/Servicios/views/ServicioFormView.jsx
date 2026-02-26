import React, { useState, useEffect } from 'react';
import { Form, Button, Row, Col, Card } from 'react-bootstrap';
import { tipoUnidadServicioService } from '../../../services/tipoUnidadServicioService';
import impuestoService from '../services/impuestoService.jsx';
import { toast } from 'react-toastify';

const ServicioFormView = ({ servicio, onSubmit, onCancel, loading }) => {
  const isEditMode = !!servicio;
  const [tiposUnidad, setTiposUnidad] = useState([]);
  const [loadingTiposUnidad, setLoadingTiposUnidad] = useState(true);
  const [impuestos, setImpuestos] = useState([]);
  const [loadingImpuestos, setLoadingImpuestos] = useState(true);

  const [formData, setFormData] = useState({
    nombre_servicio: '',
    descripcion: '',
    cantidad: '',
    tipo_unidad: 'UNIDAD',
    precio_unitario: '',
    impuesto_id: '',
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
        impuesto_id: servicio.impuesto_id || '',
        sw_estado: servicio.sw_estado || '1',
      });
    }
  }, [servicio]);

  useEffect(() => {
    const fetchTiposUnidad = async () => {
      try {
        setLoadingTiposUnidad(true);
        const response = await tipoUnidadServicioService.getAll();
        if (response.success) {
          setTiposUnidad(response.data);
        }
      } catch (error) {
        console.error('Error al cargar tipos de unidad:', error);
        toast.error('Error al cargar tipos de unidad');
      } finally {
        setLoadingTiposUnidad(false);
      }
    };

    const fetchImpuestos = async () => {
      try {
        setLoadingImpuestos(true);
        const response = await impuestoService.getAll();
        if (response.success) {
          setImpuestos(response.data);
        }
      } catch (error) {
        console.error('Error al cargar impuestos:', error);
        // No mostrar error si la tabla no existe aún
        console.log('Tabla de impuestos no disponible aún. Ejecuta las migraciones.');
      } finally {
        setLoadingImpuestos(false);
      }
    };

    fetchTiposUnidad();
    fetchImpuestos();
  }, []);

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
                  disabled={loadingTiposUnidad}
                >
                  <option value="">Seleccione...</option>
                  {tiposUnidad.map((tipo) => (
                    <option key={tipo.codigo} value={tipo.codigo}>
                      {tipo.descripcion}
                    </option>
                  ))}
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
            <Col md={6}>
              <Form.Group className="mb-3">
                <Form.Label>Impuesto (IVA)</Form.Label>
                <Form.Select
                  name="impuesto_id"
                  value={formData.impuesto_id || ''}
                  onChange={handleChange}
                  disabled={loadingImpuestos}
                >
                  <option value="">Sin impuesto</option>
                  {impuestos.map((impuesto) => (
                    <option key={impuesto.impuesto_id} value={impuesto.impuesto_id}>
                      {impuesto.nombre} - {impuesto.porcentaje}%
                    </option>
                  ))}
                </Form.Select>
                <Form.Text className="text-muted">
                  Seleccione el tipo de IVA aplicable
                </Form.Text>
              </Form.Group>
            </Col>

            <Col md={6}>
              <Form.Group className="mb-3">
                <Form.Check
                  type="checkbox"
                  name="sw_estado"
                  label="Servicio activo"
                  checked={formData.sw_estado === '1'}
                  onChange={handleChange}
                  style={{ marginTop: '32px' }}
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
