import React, { useState, useEffect, useMemo } from 'react';
import { useNavigate } from 'react-router-dom';
import {
  Card,
  Row,
  Col,
  Form,
  Button,
  Table,
  Badge,
  Alert,
  InputGroup,
} from 'react-bootstrap';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import {
  faPlus,
  faCheckCircle,
  faTimesCircle,
  faSpinner,
  faArrowLeft,
  faSearch,
  faFileInvoice,
  faList,
  faCog,
  faMoneyBillWave
} from '@fortawesome/free-solid-svg-icons';
import Swal from 'sweetalert2';
import notaCreditoService from '../../../services/notaCreditoService';
import facturacionService from '../../../services/facturacionService';
import { formatCurrency } from '../../../utils/formatters';

const NotasCreditoView = () => {
  const navigate = useNavigate();
  
  // Estados principales
  const [prefijos, setPrefijos] = useState([]);
  const [conceptos, setConceptos] = useState([]);
  const [facturas, setFacturas] = useState([]);
  const [loading, setLoading] = useState(false);
  const [empresaId, setEmpresaId] = useState('01');

  // Estados del formulario
  const [tipoNota, setTipoNota] = useState('CREDITO');
  const [prefijoSeleccionado, setPrefijoSeleccionado] = useState('');
  const [alcance, setAlcance] = useState('TOTAL');
  const [conceptoId, setConceptoId] = useState('');
  const [observacion, setObservacion] = useState('');

  // Estados de selección
  const [facturaFilters, setFacturaFilters] = useState({
    fechaDesde: '',
    fechaHasta: '',
    tercero: '',
  });
  const [selectedFactura, setSelectedFactura] = useState(null);
  const [selectedItems, setSelectedItems] = useState([]);
  const [itemAmounts, setItemAmounts] = useState({});

  // Cargar catálogos al montar
  useEffect(() => {
    cargarCatalogos();
  }, []);

  // Recargar prefijos cuando cambia el tipo de nota
  useEffect(() => {
    if (tipoNota) {
      cargarPrefijos();
    }
  }, [tipoNota]);

  // Auto-seleccionar items cuando cambia alcance
  useEffect(() => {
    if (!selectedFactura) return;

    if (alcance === 'TOTAL') {
      const items = selectedFactura.items || [];
      const itemIds = items.map((item) => item.item_id || item.id);
      setSelectedItems(itemIds);

      const amounts = {};
      items.forEach((item) => {
        const itemId = item.item_id || item.id;
        const subtotal = item.orden_servicio_item?.subtotal 
                         || item.subtotal 
                         || (item.valor_unitario * item.cantidad)
                         || (item.precio_unitario * item.cantidad)
                         || item.total
                         || 0;
        amounts[itemId] = parseFloat(subtotal) || 0;
      });
      setItemAmounts(amounts);
    } else {
      setSelectedItems([]);
      setItemAmounts({});
    }
  }, [alcance, selectedFactura]);

  const cargarCatalogos = async () => {
    try {
      setLoading(true);
      const [conceptosRes, prefijoRes] = await Promise.all([
        notaCreditoService.getConceptos(empresaId),
        notaCreditoService.getPrefijos(empresaId, 'C'),
      ]);

      if (conceptosRes.success || Array.isArray(conceptosRes)) {
        setConceptos(conceptosRes.data || conceptosRes);
      }
      if (prefijoRes.success || Array.isArray(prefijoRes)) {
        const data = prefijoRes.data || prefijoRes;
        setPrefijos(data);
        if (data.length > 0) {
          setPrefijoSeleccionado(data[0].prefijo);
        }
      }
    } catch (error) {
      console.error('Error cargando catálogos:', error);
      Swal.fire('Error', 'No se pudieron cargar los catálogos', 'error');
    } finally {
      setLoading(false);
    }
  };

  const cargarPrefijos = async () => {
    try {
      const tipo = tipoNota === 'DEBITO' ? 'D' : 'C';
      const res = await notaCreditoService.getPrefijos(empresaId, tipo);
      if (res.success || Array.isArray(res)) {
        const data = res.data || res;
        setPrefijos(data);
        if (data.length > 0 && !prefijoSeleccionado) {
          setPrefijoSeleccionado(data[0].prefijo);
        }
      }
    } catch (error) {
      console.error('Error cargando prefijos:', error);
    }
  };

  const cargarFacturas = async () => {
    try {
      setLoading(true);
      const filtros = {
        empresa_id: empresaId,
        ...facturaFilters,
      };
      const res = await facturacionService.getFacturas(filtros);
      
      let facturasData = [];
      if (res.data && Array.isArray(res.data.data)) {
        facturasData = res.data.data;
      } else if (res.data && Array.isArray(res.data)) {
        facturasData = res.data;
      } else if (Array.isArray(res)) {
        facturasData = res;
      }
      
      setFacturas(facturasData);
      
      if (facturasData.length === 0) {
        Swal.fire('Información', 'No se encontraron facturas con los filtros aplicados', 'info');
      }
    } catch (error) {
      console.error('Error cargando facturas:', error);
      Swal.fire('Error', 'No se pudieron cargar las facturas', 'error');
    } finally {
      setLoading(false);
    }
  };

  const handleSelectFactura = (factura) => {
    setSelectedFactura({
      ...factura,
      numero: factura.factura_fiscal || factura.numero,
      tercero_nombre: factura.tercero?.nombre_tercero || factura.tercero_nombre,
      valor_total: parseFloat(factura.total_factura || factura.valor_total || 0),
      fecha: factura.fecha_registro ? factura.fecha_registro.split('T')[0] : factura.fecha,
      items: factura.items || factura.items_lista || [],
    });
  };

  const handleSelectItem = (itemId) => {
    if (alcance === 'TOTAL') return;

    setSelectedItems((prev) => {
      if (prev.includes(itemId)) {
        return prev.filter((id) => id !== itemId);
      } else {
        return [...prev, itemId];
      }
    });
  };

  const handleChangeItemAmount = (itemId, value) => {
    setItemAmounts((prev) => ({
      ...prev,
      [itemId]: parseFloat(value) || 0,
    }));
  };

  const totalNota = useMemo(() => {
    if (!selectedFactura) return 0;
    if (alcance === 'TOTAL') {
      return selectedFactura.valor_total || 0;
    }
    return Object.values(itemAmounts).reduce((sum, val) => sum + val, 0);
  }, [selectedFactura, alcance, itemAmounts]);

  const handleCrearNota = async () => {
    if (!prefijoSeleccionado) {
      Swal.fire('Validación', 'Debe seleccionar un prefijo', 'warning');
      return;
    }
    if (!selectedFactura) {
      Swal.fire('Validación', 'Debe seleccionar una factura', 'warning');
      return;
    }
    if (selectedItems.length === 0) {
      Swal.fire('Validación', 'Debe seleccionar al menos un item', 'warning');
      return;
    }
    if (totalNota <= 0) {
      Swal.fire('Validación', 'El valor de la nota debe ser mayor a 0', 'warning');
      return;
    }

    const result = await Swal.fire({
      title: '¿Crear Nota Crédito?',
      html: `<div style="text-align: left;">
        <p><strong>Factura:</strong> ${selectedFactura.prefijo}-${selectedFactura.numero}</p>
        <p><strong>Tercero:</strong> ${selectedFactura.tercero_nombre}</p>
        <p><strong>Items:</strong> ${selectedItems.length}</p>
        <p><strong>Valor Total:</strong> ${formatCurrency(totalNota)}</p>
      </div>`,
      icon: 'question',
      showCancelButton: true,
      confirmButtonText: 'Crear',
      cancelButtonText: 'Cancelar',
      confirmButtonColor: '#0d6efd',
    });

    if (result.isConfirmed) {
      await enviarNota();
    }
  };

  const enviarNota = async () => {
    try {
      const items = selectedItems.map((itemId) => ({
        item_id: itemId,
        valor: itemAmounts[itemId] || 0,
      }));

      const payload = {
        empresa_id: empresaId,
        prefijo: prefijoSeleccionado,
        prefijo_factura: selectedFactura.prefijo,
        factura_fiscal: selectedFactura.numero,
        concepto_id: conceptoId || 1, // Enviamos 1 por defecto si no hay concepto seleccionado
        valor_nota: totalNota,
        observacion: observacion,
        tipo_nota: tipoNota,
        alcance: alcance,
        items: items,
      };

      Swal.fire({
        title: 'Creando nota crédito...',
        html: '<p>Por favor espere...</p>',
        icon: 'info',
        allowOutsideClick: false,
        allowEscapeKey: false,
        didOpen: async () => {
          Swal.showLoading();
          try {
            const res = await notaCreditoService.crearNota(payload);
            if (res.success) {
              Swal.fire('Exitoso', 'Nota crédito creada correctamente', 'success').then(() => {
                resetForm();
              });
            } else {
              Swal.fire('Error', res.message || 'Error al crear la nota', 'error');
            }
          } catch (error) {
            Swal.fire('Error', error.message, 'error');
          }
        },
      });
    } catch (error) {
      Swal.fire('Error', error.message, 'error');
    }
  };

  const resetForm = () => {
    setTipoNota('CREDITO');
    setAlcance('TOTAL');
    setConceptoId('');
    setObservacion('');
    setSelectedFactura(null);
    setSelectedItems([]);
    setItemAmounts({});
    setFacturaFilters({ fechaDesde: '', fechaHasta: '', tercero: '' });
    setFacturas([]);
    cargarPrefijos();
  };

  return (
    <div className="p-4">
      <div className="d-flex justify-content-between align-items-center mb-4">
        <h2 className="mb-0 text-primary">
          <FontAwesomeIcon icon={faPlus} className="me-2" />
          Crear Nota Crédito/Débito
        </h2>
        <Button variant="outline-secondary" onClick={() => navigate(-1)}>
          <FontAwesomeIcon icon={faArrowLeft} className="me-2" />
          Volver
        </Button>
      </div>

      {tipoNota === 'DEBITO' && (
        <Alert variant="warning" dismissible>
          <strong>Advertencia:</strong> El envío de notas débito aún no está habilitado
        </Alert>
      )}

      <Row>
        <Col lg={8}>
          <Card className="mb-4 shadow-sm">
            <Card.Header className="bg-primary text-white">
              <h5 className="mb-0"><FontAwesomeIcon icon={faCog} className="me-2" />Configuración</h5>
            </Card.Header>
            <Card.Body>
              <Row>
                <Col md={6}>
                  <Form.Group className="mb-3">
                    <Form.Label className="fw-bold">Tipo de Nota</Form.Label>
                    <Form.Select
                      value={tipoNota}
                      onChange={(e) => setTipoNota(e.target.value)}
                      className="bg-light"
                    >
                      <option value="CREDITO">Nota Crédito</option>
                      <option value="DEBITO">Nota Débito</option>
                    </Form.Select>
                  </Form.Group>
                </Col>
                <Col md={6}>
                  <Form.Group className="mb-3">
                    <Form.Label className="fw-bold">Prefijo</Form.Label>
                    <Form.Select
                      value={prefijoSeleccionado}
                      onChange={(e) => setPrefijoSeleccionado(e.target.value)}
                      disabled={prefijos.length === 0}
                    >
                      <option value="">-- Seleccionar --</option>
                      {prefijos.map((p) => (
                        <option key={p.documento_id || p.prefijo} value={p.prefijo}>
                          {p.prefijo} - {p.descripcion}
                        </option>
                      ))}
                    </Form.Select>
                  </Form.Group>
                </Col>
              </Row>

              <Row>
                <Col md={6}>
                  <Form.Group className="mb-3">
                    <Form.Label className="fw-bold">Alcance</Form.Label>
                    <Form.Select
                      value={alcance}
                      onChange={(e) => setAlcance(e.target.value)}
                    >
                      <option value="TOTAL">Total</option>
                      <option value="PARCIAL">Parcial</option>
                    </Form.Select>
                  </Form.Group>
                </Col>
                <Col md={6}>
                  <Form.Group className="mb-3">
                    <Form.Label className="fw-bold">Concepto</Form.Label>
                    <Form.Select
                      value={conceptoId}
                      onChange={(e) => setConceptoId(e.target.value)}
                    >
                      <option value="">-- Seleccionar --</option>
                      {conceptos.map((c) => (
                        <option key={c.id} value={c.id}>
                          {c.descripcion}
                        </option>
                      ))}
                    </Form.Select>
                  </Form.Group>
                </Col>
              </Row>

              <Form.Group className="mb-3">
                <Form.Label className="fw-bold">Observación</Form.Label>
                <Form.Control
                  as="textarea"
                  rows={2}
                  value={observacion}
                  onChange={(e) => setObservacion(e.target.value)}
                  placeholder="Notas adicionales..."
                />
              </Form.Group>
            </Card.Body>
          </Card>

          {!selectedFactura ? (
            <Card className="mb-4 shadow-sm border-info">
              <Card.Header className="bg-info text-white">
                <h5 className="mb-0"><FontAwesomeIcon icon={faSearch} className="me-2" />Buscar Factura</h5>
              </Card.Header>
              <Card.Body>
                <Row className="mb-3">
                  <Col md={4}>
                    <Form.Group>
                      <Form.Label>Desde</Form.Label>
                      <Form.Control
                        type="date"
                        value={facturaFilters.fechaDesde}
                        onChange={(e) => setFacturaFilters({ ...facturaFilters, fechaDesde: e.target.value })}
                      />
                    </Form.Group>
                  </Col>
                  <Col md={4}>
                    <Form.Group>
                      <Form.Label>Hasta</Form.Label>
                      <Form.Control
                        type="date"
                        value={facturaFilters.fechaHasta}
                        onChange={(e) => setFacturaFilters({ ...facturaFilters, fechaHasta: e.target.value })}
                      />
                    </Form.Group>
                  </Col>
                  <Col md={4}>
                    <Form.Group>
                      <Form.Label>Tercero / Factura</Form.Label>
                      <Form.Control
                        type="text"
                        placeholder="Buscar..."
                        value={facturaFilters.tercero}
                        onChange={(e) => setFacturaFilters({ ...facturaFilters, tercero: e.target.value })}
                        onKeyDown={(e) => e.key === 'Enter' && cargarFacturas()}
                      />
                    </Form.Group>
                  </Col>
                </Row>

                <div className="d-flex justify-content-end mb-3">
                  <Button variant="primary" onClick={cargarFacturas} disabled={loading}>
                    {loading ? (
                      <><FontAwesomeIcon icon={faSpinner} spin className="me-2" /> Buscando...</>
                    ) : (
                      <><FontAwesomeIcon icon={faSearch} className="me-2" /> Buscar</>
                    )}
                  </Button>
                </div>

                {facturas.length > 0 && (
                  <div className="table-responsive">
                    <Table striped bordered hover size="sm" className="align-middle">
                      <thead className="table-light">
                        <tr>
                          <th>Factura</th>
                          <th>Tercero</th>
                          <th>Fecha</th>
                          <th className="text-end">Valor</th>
                          <th className="text-center">Acción</th>
                        </tr>
                      </thead>
                      <tbody>
                        {facturas.map((factura) => {
                          const numFactura = factura.factura_fiscal || factura.numero;
                          const nombreTercero = factura.tercero?.nombre_tercero || factura.tercero_nombre;
                          const valorTotal = factura.total_factura || factura.valor_total;
                          const fecha = factura.fecha_registro ? factura.fecha_registro.split('T')[0] : factura.fecha;
                          
                          return (
                            <tr key={factura.factura_fiscal_id || factura.id}>
                              <td className="fw-bold text-primary">{factura.prefijo}-{numFactura}</td>
                              <td>{nombreTercero}</td>
                              <td>{fecha}</td>
                              <td className="text-end">{formatCurrency(valorTotal)}</td>
                              <td className="text-center">
                                <Button variant="success" size="sm" onClick={() => handleSelectFactura(factura)} title="Seleccionar Factura">
                                  <FontAwesomeIcon icon={faCheckCircle} />
                                </Button>
                              </td>
                            </tr>
                          );
                        })}
                      </tbody>
                    </Table>
                  </div>
                )}
              </Card.Body>
            </Card>
          ) : (
            <Card className="mb-4 shadow-sm border-success">
              <Card.Header className="bg-success text-white d-flex justify-content-between align-items-center">
                <h5 className="mb-0"><FontAwesomeIcon icon={faFileInvoice} className="me-2" />Factura Seleccionada</h5>
                <Badge bg="light" text="dark" className="fs-6">
                  {selectedFactura.prefijo}-{selectedFactura.numero}
                </Badge>
              </Card.Header>
              <Card.Body>
                <Row className="mb-3">
                  <Col md={4}>
                    <p className="mb-1 text-muted">Tercero</p>
                    <p className="fw-bold mb-0">{selectedFactura.tercero_nombre}</p>
                  </Col>
                  <Col md={4}>
                    <p className="mb-1 text-muted">Fecha</p>
                    <p className="fw-bold mb-0">{selectedFactura.fecha}</p>
                  </Col>
                  <Col md={4}>
                    <p className="mb-1 text-muted">Valor Total</p>
                    <p className="fw-bold mb-0 text-success">{formatCurrency(selectedFactura.valor_total)}</p>
                  </Col>
                </Row>
                <div className="d-flex justify-content-end">
                  <Button variant="outline-danger" size="sm" onClick={() => {
                    setSelectedFactura(null);
                    setSelectedItems([]);
                    setItemAmounts({});
                  }}>
                    <FontAwesomeIcon icon={faTimesCircle} className="me-1" /> Cambiar Factura
                  </Button>
                </div>
              </Card.Body>
            </Card>
          )}

          {selectedFactura && selectedFactura.items && selectedFactura.items.length > 0 && (
            <Card className="mb-4 shadow-sm">
              <Card.Header className="bg-secondary text-white d-flex justify-content-between align-items-center">
                <h5 className="mb-0"><FontAwesomeIcon icon={faList} className="me-2" />Detalle de Items</h5>
                <Badge bg="light" text="dark">
                  Seleccionados: {selectedItems.length} de {selectedFactura.items.length}
                </Badge>
              </Card.Header>
              <Card.Body className="p-0">
                <div className="table-responsive">
                  <Table striped hover size="sm" className="mb-0 align-middle">
                    <thead className="table-light">
                      <tr>
                        <th className="text-center" style={{ width: '50px' }}>
                          {alcance === 'PARCIAL' ? 'Sel' : ''}
                        </th>
                        <th>Descripción</th>
                        <th className="text-center" style={{ width: '80px' }}>Cant.</th>
                        <th className="text-end" style={{ width: '120px' }}>V. Unitario</th>
                        <th className="text-end" style={{ width: '150px' }}>V. Nota</th>
                      </tr>
                    </thead>
                    <tbody>
                      {selectedFactura.items.map((item) => {
                        const itemId = item.item_id || item.id;
                        const descripcion = item.orden_servicio_item?.nombre_servicio 
                                            || item.orden_servicio_item?.descripcion
                                            || item.nombre_servicio
                                            || item.descripcion 
                                            || 'Item';
                        const cantidad = item.orden_servicio_item?.cantidad || item.cantidad || 1;
                        const valorUnitario = item.orden_servicio_item?.precio_unitario || item.valor_unitario || item.precio_unitario || 0;
                        
                        return (
                          <tr key={itemId}>
                            <td className="text-center">
                              {alcance === 'PARCIAL' && (
                                <Form.Check
                                  type="checkbox"
                                  checked={selectedItems.includes(itemId)}
                                  onChange={() => handleSelectItem(itemId)}
                                />
                              )}
                              {alcance === 'TOTAL' && (
                                <FontAwesomeIcon icon={faCheckCircle} className="text-success" />
                              )}
                            </td>
                            <td>{descripcion}</td>
                            <td className="text-center">{parseFloat(cantidad).toString()}</td>
                            <td className="text-end">{formatCurrency(valorUnitario)}</td>
                            <td>
                              <InputGroup size="sm">
                                <InputGroup.Text>$</InputGroup.Text>
                                <Form.Control
                                  type="number"
                                  className="text-end"
                                  value={itemAmounts[itemId] || 0}
                                  onChange={(e) => handleChangeItemAmount(itemId, e.target.value)}
                                  readOnly={alcance === 'TOTAL' || !selectedItems.includes(itemId)}
                                />
                              </InputGroup>
                            </td>
                          </tr>
                        );
                      })}
                    </tbody>
                  </Table>
                </div>
              </Card.Body>
            </Card>
          )}
        </Col>

        <Col lg={4}>
          <Card className="shadow-sm sticky-top" style={{ top: '20px' }}>
            <Card.Header className="bg-dark text-white">
              <h5 className="mb-0"><FontAwesomeIcon icon={faMoneyBillWave} className="me-2" />Resumen</h5>
            </Card.Header>
            <Card.Body>
              <div className="d-flex justify-content-between mb-2">
                <span className="text-muted">Tipo de Nota:</span>
                <Badge bg={tipoNota === 'CREDITO' ? 'success' : 'danger'} className="fs-6">
                  {tipoNota}
                </Badge>
              </div>
              <div className="d-flex justify-content-between mb-2">
                <span className="text-muted">Alcance:</span>
                <span className="fw-bold">{alcance}</span>
              </div>
              <div className="d-flex justify-content-between mb-3">
                <span className="text-muted">Items Seleccionados:</span>
                <span className="fw-bold">{selectedItems.length}</span>
              </div>

              <hr />

              <div className="text-center mb-4">
                <h6 className="text-muted mb-1">Valor Total de Nota</h6>
                <h3 className="text-primary mb-0">
                  {formatCurrency(totalNota)}
                </h3>
              </div>

              <Button
                variant="primary"
                size="lg"
                className="w-100 fw-bold"
                onClick={handleCrearNota}
                disabled={!selectedFactura || selectedItems.length === 0 || loading}
              >
                {loading ? (
                  <><FontAwesomeIcon icon={faSpinner} spin className="me-2" /> Procesando...</>
                ) : (
                  <><FontAwesomeIcon icon={faCheckCircle} className="me-2" /> Crear Nota</>
                )}
              </Button>
            </Card.Body>
          </Card>
        </Col>
      </Row>
    </div>
  );
};

export default NotasCreditoView;
