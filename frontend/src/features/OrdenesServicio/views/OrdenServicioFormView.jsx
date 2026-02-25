import React, { useState, useEffect } from 'react';
import { Form, Button, Row, Col, Card, Table, InputGroup } from 'react-bootstrap';
import { terceroService } from '../../Terceros/services/terceroService';
import servicioService from '../../Servicios/services/servicioService';
import { toast } from 'react-toastify';

const OrdenServicioFormView = ({ orden, onSubmit, onCancel, loading }) => {
  const isEditMode = !!orden;

  const [terceros, setTerceros] = useState([]);
  const [servicios, setServicios] = useState([]);
  const [loadingData, setLoadingData] = useState(true);

  const [formData, setFormData] = useState({
    tipo_id_tercero: '',
    tercero_id: '',
    fecha_inicio: '',
    fecha_fin: '',
    sw_prorroga_automatica: '0',
    periodo_facturacion_dias: '30',
    porcentaje_soltec: '0',
    observaciones: '',
    sw_estado: '1',
  });

  const [items, setItems] = useState([]);
  const [selectedServicio, setSelectedServicio] = useState('');
  const [cantidad, setCantidad] = useState('1');
  const [editingIndex, setEditingIndex] = useState(null);
  const [editData, setEditData] = useState({});

  useEffect(() => {
    loadData();
  }, []);

  useEffect(() => {
    if (orden) {
      // Formatear fechas para input type="date" (YYYY-MM-DD)
      const formatDateForInput = (dateString) => {
        if (!dateString) return '';
        const date = new Date(dateString);
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
      };

      setFormData({
        tipo_id_tercero: orden.tipo_id_tercero || '',
        tercero_id: orden.tercero_id || '',
        fecha_inicio: formatDateForInput(orden.fecha_inicio),
        fecha_fin: formatDateForInput(orden.fecha_fin),
        sw_prorroga_automatica: orden.sw_prorroga_automatica || '0',
        periodo_facturacion_dias: orden.periodo_facturacion_dias || '30',
        porcentaje_soltec: orden.porcentaje_soltec || '0',
        observaciones: orden.observaciones || '',
        sw_estado: orden.sw_estado || '1',
      });
      
      if (orden.items) {
        setItems(orden.items.map(item => ({
          servicio_id: item.servicio_id,
          nombre_servicio: item.nombre_servicio,
          cantidad: parseFloat(item.cantidad) || 0,
          precio_unitario: parseFloat(item.precio_unitario) || 0,
          tipo_unidad: item.tipo_unidad,
          subtotal: parseFloat(item.subtotal) || parseFloat(item.cantidad) * parseFloat(item.precio_unitario),
        })));
      }
    }
  }, [orden]);

  const loadData = async () => {
    try {
      setLoadingData(true);
      const [tercerosRes, serviciosRes] = await Promise.all([
        terceroService.getAll({ sw_estado: '1', per_page: 1000 }),
        servicioService.getAll({ estado: '1' })
      ]);
      
      setTerceros(tercerosRes.data || []);
      setServicios(serviciosRes.data || []);
    } catch (error) {
      console.error('Error al cargar datos:', error);
      toast.error('Error al cargar datos');
    } finally {
      setLoadingData(false);
    }
  };

  const handleChange = (e) => {
    const { name, value, type, checked } = e.target;
    setFormData({
      ...formData,
      [name]: type === 'checkbox' ? (checked ? '1' : '0') : value,
    });
  };

  const handleTerceroChange = (e) => {
    const value = e.target.value;
    if (value) {
      const [tipo, id] = value.split('|');
      setFormData({
        ...formData,
        tipo_id_tercero: tipo,
        tercero_id: id,
      });
    } else {
      setFormData({
        ...formData,
        tipo_id_tercero: '',
        tercero_id: '',
      });
    }
  };

  const handleAgregarServicio = () => {
    if (!selectedServicio || !cantidad || cantidad <= 0) {
      toast.warning('Seleccione un servicio y cantidad válida');
      return;
    }

    const servicio = servicios.find(s => s.servicio_id === parseInt(selectedServicio));
    if (!servicio) return;

    // Verificar si ya existe
    if (items.find(item => item.servicio_id === servicio.servicio_id)) {
      toast.warning('El servicio ya fue agregado');
      return;
    }

    const cantidadNum = parseFloat(cantidad);
    const subtotal = cantidadNum * parseFloat(servicio.precio_unitario);

    setItems([
      ...items,
      {
        servicio_id: servicio.servicio_id,
        nombre_servicio: servicio.nombre_servicio,
        cantidad: cantidadNum,
        precio_unitario: parseFloat(servicio.precio_unitario),
        tipo_unidad: servicio.tipo_unidad,
        subtotal: subtotal,
      }
    ]);

    setSelectedServicio('');
    setCantidad('1');
  };

  const handleEliminarServicio = (servicioId) => {
    setItems(items.filter(item => item.servicio_id !== servicioId));
  };

  const handleEditClick = (index, item) => {
    setEditingIndex(index);
    setEditData({
      cantidad: item.cantidad,
      precio_unitario: item.precio_unitario
    });
  };

  const handleCancelEdit = () => {
    setEditingIndex(null);
    setEditData({});
  };

  const handleInputChange = (field, value) => {
    setEditData({
      ...editData,
      [field]: value === '' ? 0 : parseFloat(value)
    });
  };

  const handleSaveEdit = (index) => {
    if (editData.cantidad <= 0) {
      toast.warning('La cantidad debe ser mayor a 0');
      return;
    }
    if (editData.precio_unitario < 0) {
      toast.warning('El precio no puede ser negativo');
      return;
    }

    const updatedItems = [...items];
    updatedItems[index] = {
      ...updatedItems[index],
      cantidad: parseFloat(editData.cantidad),
      precio_unitario: parseFloat(editData.precio_unitario),
      subtotal: parseFloat(editData.cantidad) * parseFloat(editData.precio_unitario)
    };
    
    setItems(updatedItems);
    setEditingIndex(null);
    setEditData({});
  };

  const calcularTotal = () => {
    return items.reduce((total, item) => {
      const subtotal = parseFloat(item.subtotal) || (parseFloat(item.cantidad) * parseFloat(item.precio_unitario)) || 0;
      return total + subtotal;
    }, 0);
  };

  const formatCurrency = (value) => {
    return new Intl.NumberFormat('es-CO', {
      style: 'currency',
      currency: 'COP',
      minimumFractionDigits: 0
    }).format(value);
  };

  const handleSubmit = (e) => {
    e.preventDefault();
    
    if (items.length === 0) {
      toast.warning('Debe agregar al menos un servicio');
      return;
    }

    const data = {
      ...formData,
      items: items.map(item => ({
        servicio_id: item.servicio_id,
        cantidad: item.cantidad,
      })),
    };
    
    onSubmit(data);
  };

  const terceroValue = formData.tipo_id_tercero && formData.tercero_id 
    ? `${formData.tipo_id_tercero}|${formData.tercero_id}` 
    : '';

  return (
    <Card>
      <Card.Body>
        <Form onSubmit={handleSubmit}>
          <h5 className="mb-3">
            {isEditMode ? 'Editar Orden de Servicio' : 'Nueva Orden de Servicio'}
          </h5>

          {/* Tercero */}
          <Row>
            <Col md={12}>
              <Form.Group className="mb-3">
                <Form.Label>Tercero *</Form.Label>
                <Form.Select
                  value={terceroValue}
                  onChange={handleTerceroChange}
                  required
                  disabled={isEditMode || loadingData}
                >
                  <option value="">Seleccione un tercero...</option>
                  {terceros.map((tercero) => (
                    <option 
                      key={`${tercero.tipo_id_tercero}|${tercero.tercero_id}`}
                      value={`${tercero.tipo_id_tercero}|${tercero.tercero_id}`}
                    >
                      {tercero.nombre_tercero} - {tercero.tipo_id_tercero} {tercero.tercero_id}
                    </option>
                  ))}
                </Form.Select>
              </Form.Group>
            </Col>
          </Row>

          {/* Fechas */}
          <Row>
            <Col md={3}>
              <Form.Group className="mb-3">
                <Form.Label>Fecha Inicio *</Form.Label>
                <Form.Control
                  type="date"
                  name="fecha_inicio"
                  value={formData.fecha_inicio}
                  onChange={handleChange}
                  required
                />
              </Form.Group>
            </Col>
            <Col md={3}>
              <Form.Group className="mb-3">
                <Form.Label>Fecha Fin *</Form.Label>
                <Form.Control
                  type="date"
                  name="fecha_fin"
                  value={formData.fecha_fin}
                  onChange={handleChange}
                  required
                  min={formData.fecha_inicio}
                />
              </Form.Group>
            </Col>
            <Col md={3}>
              <Form.Group className="mb-3">
                <Form.Label>Periodo Facturación (días) *</Form.Label>
                <Form.Control
                  type="number"
                  name="periodo_facturacion_dias"
                  value={formData.periodo_facturacion_dias}
                  onChange={handleChange}
                  required
                  min="1"
                />
              </Form.Group>
            </Col>
            <Col md={3}>
              <Form.Group className="mb-3">
                <Form.Label>Porcentaje Soltec (%)</Form.Label>
                <Form.Control
                  type="number"
                  step="0.01"
                  name="porcentaje_soltec"
                  value={formData.porcentaje_soltec}
                  onChange={handleChange}
                  min="0"
                  max="100"
                />
              </Form.Group>
            </Col>
          </Row>

          {/* Prórroga y Estado */}
          <Row>
            <Col md={6}>
              <Form.Group className="mb-3">
                <Form.Check
                  type="checkbox"
                  name="sw_prorroga_automatica"
                  label="Prórroga Automática (Permite facturar después de la fecha fin)"
                  checked={formData.sw_prorroga_automatica === '1'}
                  onChange={handleChange}
                />
              </Form.Group>
            </Col>
            <Col md={6}>
              <Form.Group className="mb-3">
                <Form.Check
                  type="checkbox"
                  name="sw_estado"
                  label="Orden Activa"
                  checked={formData.sw_estado === '1'}
                  onChange={handleChange}
                />
              </Form.Group>
            </Col>
          </Row>

          {/* Servicios */}
          <hr />
          <h6 className="mb-3">Servicios</h6>
          <Row className="mb-3">
            <Col md={7}>
              <Form.Group>
                <Form.Label>Servicio</Form.Label>
                <Form.Select
                  value={selectedServicio}
                  onChange={(e) => setSelectedServicio(e.target.value)}
                  disabled={loadingData}
                >
                  <option value="">Seleccione un servicio...</option>
                  {servicios.map((servicio) => (
                    <option key={servicio.servicio_id} value={servicio.servicio_id}>
                      {servicio.nombre_servicio} - {formatCurrency(servicio.precio_unitario)} / {servicio.tipo_unidad}
                    </option>
                  ))}
                </Form.Select>
              </Form.Group>
            </Col>
            <Col md={3}>
              <Form.Group>
                <Form.Label>Cantidad</Form.Label>
                <Form.Control
                  type="number"
                  step="0.01"
                  min="0.01"
                  value={cantidad}
                  onChange={(e) => setCantidad(e.target.value)}
                />
              </Form.Group>
            </Col>
            <Col md={2} className="d-flex align-items-end">
              <Button
                variant="success"
                onClick={handleAgregarServicio}
                className="w-100"
              >
                <i className="fas fa-plus"></i> Agregar
              </Button>
            </Col>
          </Row>

          {/* Tabla de servicios agregados */}
          {items.length > 0 && (
            <div className="table-responsive mb-3">
              <Table bordered hover size="sm">
                <thead className="table-light">
                  <tr>
                    <th>Servicio</th>
                    <th style={{ width: '120px' }} className="text-center">Cantidad</th>
                    <th style={{ width: '140px' }} className="text-end">Precio Unit.</th>
                    <th style={{ width: '130px' }} className="text-end">Subtotal</th>
                    <th style={{ width: '100px' }} className="text-center">Acción</th>
                  </tr>
                </thead>
                <tbody>
                  {items.map((item, index) => (
                    <tr key={index}>
                      <td>
                        {item.nombre_servicio}
                        <br />
                        <small className="text-muted">{item.tipo_unidad}</small>
                      </td>
                      <td className="text-center">
                        {editingIndex === index ? (
                          <Form.Control
                            type="number"
                            step="0.01"
                            min="0"
                            value={editData.cantidad}
                            onChange={(e) => handleInputChange('cantidad', e.target.value)}
                            autoFocus
                            size="sm"
                            onKeyPress={(e) => {
                              if (e.key === 'Enter') handleSaveEdit(index);
                            }}
                          />
                        ) : (
                          <span>{item.cantidad}</span>
                        )}
                      </td>
                      <td className="text-end">
                        {editingIndex === index ? (
                          <Form.Control
                            type="number"
                            step="0.01"
                            min="0"
                            value={editData.precio_unitario}
                            onChange={(e) => handleInputChange('precio_unitario', e.target.value)}
                            size="sm"
                            onKeyPress={(e) => {
                              if (e.key === 'Enter') handleSaveEdit(index);
                            }}
                          />
                        ) : (
                          <span>{formatCurrency(item.precio_unitario)}</span>
                        )}
                      </td>
                      <td className="text-end">
                        <strong>
                          {editingIndex === index
                            ? formatCurrency(editData.cantidad * editData.precio_unitario)
                            : formatCurrency(item.subtotal)
                          }
                        </strong>
                      </td>
                      <td className="text-center">
                        {editingIndex === index ? (
                          <div className="d-flex gap-1 justify-content-center">
                            <Button
                              size="sm"
                              variant="success"
                              onClick={() => handleSaveEdit(index)}
                              title="Guardar"
                            >
                              <i className="fas fa-check"></i>
                            </Button>
                            <Button
                              size="sm"
                              variant="secondary"
                              onClick={handleCancelEdit}
                              title="Cancelar"
                            >
                              <i className="fas fa-times"></i>
                            </Button>
                          </div>
                        ) : (
                          <div className="d-flex gap-1 justify-content-center">
                            <Button
                              size="sm"
                              variant="outline-primary"
                              onClick={() => handleEditClick(index, item)}
                              title="Editar"
                            >
                              <i className="fas fa-edit"></i>
                            </Button>
                            <Button
                              size="sm"
                              variant="outline-danger"
                              onClick={() => handleEliminarServicio(item.servicio_id)}
                              title="Eliminar"
                            >
                              <i className="fas fa-trash"></i>
                            </Button>
                          </div>
                        )}
                      </td>
                    </tr>
                  ))}
                  <tr className="table-secondary">
                    <td colSpan="3" className="text-end">
                      <strong>TOTAL:</strong>
                    </td>
                    <td className="text-end">
                      <strong className="fs-5">{formatCurrency(calcularTotal())}</strong>
                    </td>
                    <td></td>
                  </tr>
                </tbody>
              </Table>
            </div>
          )}

          {/* Observaciones */}
          <Row>
            <Col md={12}>
              <Form.Group className="mb-3">
                <Form.Label>Observaciones</Form.Label>
                <Form.Control
                  as="textarea"
                  rows={3}
                  name="observaciones"
                  value={formData.observaciones}
                  onChange={handleChange}
                  placeholder="Observaciones adicionales..."
                />
              </Form.Group>
            </Col>
          </Row>

          {/* Botones */}
          <div className="d-flex justify-content-end">
            <Button variant="secondary" className="me-2" onClick={onCancel}>
              Cancelar
            </Button>
            <Button variant="primary" type="submit" disabled={loading || items.length === 0}>
              {loading ? (
                <>
                  <span className="spinner-border spinner-border-sm me-2"></span>
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

export default OrdenServicioFormView;
