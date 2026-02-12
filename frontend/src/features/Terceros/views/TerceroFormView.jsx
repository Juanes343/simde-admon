import React, { useState, useEffect } from 'react';
import { Form, Button, Row, Col, Card } from 'react-bootstrap';

const TerceroFormView = ({ tercero, onSubmit, onCancel, loading }) => {
  const isEditMode = !!tercero;

  const [formData, setFormData] = useState({
    tipo_id_tercero: '',
    tercero_id: '',
    tipo_pais_id: '169',
    tipo_dpto_id: '',
    tipo_mpio_id: '',
    direccion: '',
    telefono: '',
    fax: '',
    email: '',
    celular: '',
    sw_persona_juridica: '0',
    cal_cli: '0',
    nombre_tercero: '',
    dv: '',
    sw_tipo_sociedad: '0',
    sw_reteica: '0',
    nombre_tercero_abreviado: '',
    sw_domiciliado: '0',
    apartado_aereo: '',
    direccion2: '',
    telefono2: '',
    sw_estado: '1',
    primer_nombre: '',
    segundo_nombre: '',
    primer_apellido: '',
    segundo_apellido: '',
    sw_responsable_iva: '0',
    sw_convenio: '0',
    dias_credito: '',
  });

  useEffect(() => {
    if (tercero) {
      setFormData(tercero);
    }
  }, [tercero]);

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
          <h5 className="mb-3">Identificación</h5>
          <Row>
            <Col md={3}>
              <Form.Group className="mb-3">
                <Form.Label>Tipo ID *</Form.Label>
                <Form.Select
                  name="tipo_id_tercero"
                  value={formData.tipo_id_tercero}
                  onChange={handleChange}
                  required
                  disabled={isEditMode}
                >
                  <option value="">Seleccione...</option>
                  <option value="13">CC - Cédula Ciudadanía</option>
                  <option value="31">NIT</option>
                  <option value="22">CE - Cédula Extranjería</option>
                  <option value="41">Pasaporte</option>
                </Form.Select>
              </Form.Group>
            </Col>
            <Col md={6}>
              <Form.Group className="mb-3">
                <Form.Label>Identificación *</Form.Label>
                <Form.Control
                  type="text"
                  name="tercero_id"
                  value={formData.tercero_id}
                  onChange={handleChange}
                  required
                  disabled={isEditMode}
                  maxLength="32"
                />
              </Form.Group>
            </Col>
            <Col md={3}>
              <Form.Group className="mb-3">
                <Form.Label>DV</Form.Label>
                <Form.Control
                  type="text"
                  name="dv"
                  value={formData.dv}
                  onChange={handleChange}
                  maxLength="1"
                />
              </Form.Group>
            </Col>
          </Row>

          <h5 className="mb-3 mt-4">Información Personal/Empresarial</h5>
          <Row>
            <Col md={12}>
              <Form.Group className="mb-3">
                <Form.Label>Nombre Completo / Razón Social *</Form.Label>
                <Form.Control
                  type="text"
                  name="nombre_tercero"
                  value={formData.nombre_tercero}
                  onChange={handleChange}
                  required
                  maxLength="100"
                />
              </Form.Group>
            </Col>
          </Row>

          <Row>
            <Col md={3}>
              <Form.Group className="mb-3">
                <Form.Label>Primer Nombre</Form.Label>
                <Form.Control
                  type="text"
                  name="primer_nombre"
                  value={formData.primer_nombre}
                  onChange={handleChange}
                  maxLength="32"
                />
              </Form.Group>
            </Col>
            <Col md={3}>
              <Form.Group className="mb-3">
                <Form.Label>Segundo Nombre</Form.Label>
                <Form.Control
                  type="text"
                  name="segundo_nombre"
                  value={formData.segundo_nombre}
                  onChange={handleChange}
                  maxLength="32"
                />
              </Form.Group>
            </Col>
            <Col md={3}>
              <Form.Group className="mb-3">
                <Form.Label>Primer Apellido</Form.Label>
                <Form.Control
                  type="text"
                  name="primer_apellido"
                  value={formData.primer_apellido}
                  onChange={handleChange}
                  maxLength="32"
                />
              </Form.Group>
            </Col>
            <Col md={3}>
              <Form.Group className="mb-3">
                <Form.Label>Segundo Apellido</Form.Label>
                <Form.Control
                  type="text"
                  name="segundo_apellido"
                  value={formData.segundo_apellido}
                  onChange={handleChange}
                  maxLength="32"
                />
              </Form.Group>
            </Col>
          </Row>

          <h5 className="mb-3 mt-4">Ubicación</h5>
          <Row>
            <Col md={4}>
              <Form.Group className="mb-3">
                <Form.Label>País *</Form.Label>
                <Form.Control
                  type="text"
                  name="tipo_pais_id"
                  value={formData.tipo_pais_id}
                  onChange={handleChange}
                  required
                  maxLength="4"
                />
              </Form.Group>
            </Col>
            <Col md={4}>
              <Form.Group className="mb-3">
                <Form.Label>Departamento *</Form.Label>
                <Form.Control
                  type="text"
                  name="tipo_dpto_id"
                  value={formData.tipo_dpto_id}
                  onChange={handleChange}
                  required
                  maxLength="4"
                />
              </Form.Group>
            </Col>
            <Col md={4}>
              <Form.Group className="mb-3">
                <Form.Label>Municipio *</Form.Label>
                <Form.Control
                  type="text"
                  name="tipo_mpio_id"
                  value={formData.tipo_mpio_id}
                  onChange={handleChange}
                  required
                  maxLength="4"
                />
              </Form.Group>
            </Col>
          </Row>

          <Row>
            <Col md={6}>
              <Form.Group className="mb-3">
                <Form.Label>Dirección Principal *</Form.Label>
                <Form.Control
                  type="text"
                  name="direccion"
                  value={formData.direccion}
                  onChange={handleChange}
                  required
                  maxLength="100"
                />
              </Form.Group>
            </Col>
            <Col md={6}>
              <Form.Group className="mb-3">
                <Form.Label>Dirección Secundaria</Form.Label>
                <Form.Control
                  type="text"
                  name="direccion2"
                  value={formData.direccion2}
                  onChange={handleChange}
                  maxLength="100"
                />
              </Form.Group>
            </Col>
          </Row>

          <h5 className="mb-3 mt-4">Contacto</h5>
          <Row>
            <Col md={3}>
              <Form.Group className="mb-3">
                <Form.Label>Teléfono</Form.Label>
                <Form.Control
                  type="text"
                  name="telefono"
                  value={formData.telefono}
                  onChange={handleChange}
                  maxLength="30"
                />
              </Form.Group>
            </Col>
            <Col md={3}>
              <Form.Group className="mb-3">
                <Form.Label>Teléfono 2</Form.Label>
                <Form.Control
                  type="text"
                  name="telefono2"
                  value={formData.telefono2}
                  onChange={handleChange}
                  maxLength="30"
                />
              </Form.Group>
            </Col>
            <Col md={3}>
              <Form.Group className="mb-3">
                <Form.Label>Celular</Form.Label>
                <Form.Control
                  type="text"
                  name="celular"
                  value={formData.celular}
                  onChange={handleChange}
                  maxLength="15"
                />
              </Form.Group>
            </Col>
            <Col md={3}>
              <Form.Group className="mb-3">
                <Form.Label>Fax</Form.Label>
                <Form.Control
                  type="text"
                  name="fax"
                  value={formData.fax}
                  onChange={handleChange}
                  maxLength="15"
                />
              </Form.Group>
            </Col>
          </Row>

          <Row>
            <Col md={6}>
              <Form.Group className="mb-3">
                <Form.Label>Email</Form.Label>
                <Form.Control
                  type="email"
                  name="email"
                  value={formData.email}
                  onChange={handleChange}
                  maxLength="60"
                />
              </Form.Group>
            </Col>
            <Col md={6}>
              <Form.Group className="mb-3">
                <Form.Label>Apartado Aéreo</Form.Label>
                <Form.Control
                  type="text"
                  name="apartado_aereo"
                  value={formData.apartado_aereo}
                  onChange={handleChange}
                  maxLength="100"
                />
              </Form.Group>
            </Col>
          </Row>

          <h5 className="mb-3 mt-4">Información Tributaria</h5>
          <Row>
            <Col md={3}>
              <Form.Check
                type="checkbox"
                label="Persona Jurídica"
                name="sw_persona_juridica"
                checked={formData.sw_persona_juridica === '1'}
                onChange={handleChange}
                className="mb-3"
              />
            </Col>
            <Col md={3}>
              <Form.Check
                type="checkbox"
                label="Responsable IVA"
                name="sw_responsable_iva"
                checked={formData.sw_responsable_iva === '1'}
                onChange={handleChange}
                className="mb-3"
              />
            </Col>
            <Col md={3}>
              <Form.Check
                type="checkbox"
                label="ReteICA"
                name="sw_reteica"
                checked={formData.sw_reteica === '1'}
                onChange={handleChange}
                className="mb-3"
              />
            </Col>
            <Col md={3}>
              <Form.Check
                type="checkbox"
                label="Domiciliado"
                name="sw_domiciliado"
                checked={formData.sw_domiciliado === '1'}
                onChange={handleChange}
                className="mb-3"
              />
            </Col>
          </Row>

          <Row>
            <Col md={6}>
              <Form.Group className="mb-3">
                <Form.Label>Días de Crédito</Form.Label>
                <Form.Control
                  type="number"
                  name="dias_credito"
                  value={formData.dias_credito}
                  onChange={handleChange}
                />
              </Form.Group>
            </Col>
          </Row>

          <div className="d-flex justify-content-end mt-4">
            <Button variant="secondary" className="me-2" onClick={onCancel}>
              Cancelar
            </Button>
            <Button variant="primary" type="submit" disabled={loading}>
              {loading ? 'Guardando...' : isEditMode ? 'Actualizar' : 'Crear'}
            </Button>
          </div>
        </Form>
      </Card.Body>
    </Card>
  );
};

export default TerceroFormView;
