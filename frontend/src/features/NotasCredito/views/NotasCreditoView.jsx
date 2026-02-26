import React, { useEffect, useMemo, useState } from 'react';
import { Card, Table, Button, Form, Row, Col, Badge, Spinner, Alert } from 'react-bootstrap';
import { useNavigate } from 'react-router-dom';
import facturacionService from '../../../services/facturacionService';
import notasCreditoService from '../../../services/notaCreditoService';
import { formatCurrency } from '../../../utils/formatters';
import Swal from 'sweetalert2';

const NotasCreditoView = () => {
  const navigate = useNavigate();
  const [loading, setLoading] = useState(false);
  const [loadingFacturas, setLoadingFacturas] = useState(false);
  const [facturas, setFacturas] = useState([]);
  const [pagination, setPagination] = useState({});
  const [selectedFactura, setSelectedFactura] = useState(null);
  const [selectedItems, setSelectedItems] = useState([]);
  const [itemAmounts, setItemAmounts] = useState({});
  const [conceptos, setConceptos] = useState([]);
  const [conceptoId, setConceptoId] = useState('');
  const [prefijo, setPrefijo] = useState('');
  const [observacion, setObservacion] = useState('');
  const [tipoNota, setTipoNota] = useState('CREDITO');
  const [alcance, setAlcance] = useState('TOTAL');
  const [filters, setFilters] = useState({
    lapso_inicio: '',
    lapso_fin: '',
    tercero: ''
  });

  useEffect(() => {
    loadConceptos();
  }, []);

  useEffect(() => {
    const delayDebounceFn = setTimeout(() => {
      loadFacturas(1);
    }, 400);

    return () => clearTimeout(delayDebounceFn);
  }, [filters]);

  useEffect(() => {
    if (!selectedFactura) return;

    const items = getFacturaItems(selectedFactura);
    if (alcance === 'TOTAL') {
      const itemIds = items.map((i) => i.item_id);
      setSelectedItems(itemIds);

      const amounts = {};
      items.forEach((item) => {
        const osItem = getOrdenServicioItem(item);
        amounts[item.item_id] = getItemSubtotal(osItem);
      });
      setItemAmounts(amounts);
    }
  }, [alcance, selectedFactura]);

  const loadConceptos = async () => {
    try {
      const data = await notasCreditoService.getConceptos();
      setConceptos(data);
      if (data.length > 0) {
        setConceptoId(data[0].concepto_id || data[0].id || '');
      }
    } catch (error) {
      Swal.fire('Error', 'No se pudieron cargar los conceptos de nota credito', 'error');
    }
  };

  const loadFacturas = async (page = 1) => {
    setLoadingFacturas(true);
    try {
      const data = await facturacionService.getFacturas(page, filters);
      setFacturas(data.data || []);
      setPagination({
        current_page: data.current_page,
        last_page: data.last_page,
        total: data.total
      });
    } catch (error) {
      Swal.fire('Error', 'No se pudieron cargar las facturas', 'error');
    } finally {
      setLoadingFacturas(false);
    }
  };

  const getFacturaItems = (factura) => factura?.items || [];

  const getOrdenServicioItem = (item) => item?.ordenServicioItem || item?.orden_servicio_item || null;

  const getItemSubtotal = (osItem) => {
    if (!osItem) return 0;
    const subtotal = osItem.subtotal ?? (osItem.precio_unitario || 0) * (osItem.cantidad || 1);
    return Number(subtotal) || 0;
  };

  const handleSelectFactura = (factura) => {
    setSelectedFactura(factura);
    setSelectedItems([]);
    setItemAmounts({});
  };

  const handleSelectItem = (itemId) => {
    if (alcance === 'TOTAL') return;

    if (selectedItems.includes(itemId)) {
      setSelectedItems(selectedItems.filter((id) => id !== itemId));
      const newAmounts = { ...itemAmounts };
      delete newAmounts[itemId];
      setItemAmounts(newAmounts);
    } else {
      setSelectedItems([...selectedItems, itemId]);

      const item = getFacturaItems(selectedFactura).find((i) => i.item_id === itemId);
      const osItem = getOrdenServicioItem(item);
      const subtotal = getItemSubtotal(osItem);
      setItemAmounts({
        ...itemAmounts,
        [itemId]: subtotal
      });
    }
  };

  const handleAmountChange = (itemId, value) => {
    setItemAmounts({
      ...itemAmounts,
      [itemId]: Number(value) || 0
    });
  };

  const totalNota = useMemo(() => {
    return selectedItems.reduce((sum, itemId) => {
      return sum + (Number(itemAmounts[itemId]) || 0);
    }, 0);
  }, [selectedItems, itemAmounts]);

  const handleCrearNota = async () => {
    if (tipoNota === 'DEBITO') {
      return Swal.fire('En desarrollo', 'El envio de notas debito aun no esta habilitado en el backend.', 'info');
    }

    if (!selectedFactura) return Swal.fire('Atencion', 'Seleccione una factura', 'warning');
    if (!prefijo) return Swal.fire('Atencion', 'Ingrese el prefijo de la nota', 'warning');
    if (!conceptoId) return Swal.fire('Atencion', 'Seleccione un concepto', 'warning');
    if (selectedItems.length === 0) return Swal.fire('Atencion', 'Seleccione al menos un item', 'warning');
    if (totalNota <= 0) return Swal.fire('Atencion', 'El valor de la nota debe ser mayor a 0', 'warning');

    const result = await Swal.fire({
      title: 'Crear Nota Credito?',
      text: `Se creara una nota credito ${alcance.toLowerCase()} por ${formatCurrency(totalNota)}.`,
      icon: 'question',
      showCancelButton: true,
      confirmButtonText: 'Si, Crear',
      cancelButtonText: 'Cancelar'
    });

    if (!result.isConfirmed) return;

    try {
      setLoading(true);
      const userData = JSON.parse(localStorage.getItem('user') || '{}');

      const payload = {
        empresa_id: userData.empresa_id || userData.id_empresa || 1,
        prefijo,
        prefijo_factura: selectedFactura.prefijo,
        factura_fiscal: selectedFactura.factura_fiscal,
        concepto_id: conceptoId,
        valor_nota: totalNota,
        observacion,
        tipo_nota: tipoNota,
        alcance,
        items: selectedItems.map((id) => ({
          item_id: id,
          valor: Number(itemAmounts[id]) || 0
        }))
      };

      const response = await notasCreditoService.crearNota(payload);

      Swal.fire({
        title: 'Nota creada',
        text: response?.message || 'Nota credito creada correctamente.',
        icon: 'success'
      });

      setSelectedFactura(null);
      setSelectedItems([]);
      setItemAmounts({});
      setObservacion('');
    } catch (error) {
      Swal.fire('Error', error.response?.data?.message || 'No se pudo crear la nota', 'error');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="container-fluid py-4">
      <Card className="shadow-sm mb-4">
        <Card.Header className="bg-primary text-white d-flex justify-content-between align-items-center">
          <h5 className="mb-0">Notas Credito / Debito</h5>
          <div>
            <Button variant="light" size="sm" className="me-2" onClick={() => navigate('/notas-historico')}>
              <i className="fas fa-list me-2"></i>Ver Historico
            </Button>
            <Button variant="light" size="sm" onClick={() => navigate('/dashboard')}>
              <i className="fas fa-arrow-left me-2"></i>Volver al Dashboard
            </Button>
          </div>
        </Card.Header>
        <Card.Body>
          <Form className="mb-3">
            <Row className="align-items-end">
              <Col md={3}>
                <Form.Group>
                  <Form.Label>Tipo de Nota</Form.Label>
                  <Form.Select value={tipoNota} onChange={(e) => setTipoNota(e.target.value)}>
                    <option value="CREDITO">Credito</option>
                    <option value="DEBITO">Debito</option>
                  </Form.Select>
                </Form.Group>
              </Col>
              <Col md={3}>
                <Form.Group>
                  <Form.Label>Alcance</Form.Label>
                  <Form.Select value={alcance} onChange={(e) => setAlcance(e.target.value)}>
                    <option value="TOTAL">Total</option>
                    <option value="PARCIAL">Parcial</option>
                  </Form.Select>
                </Form.Group>
              </Col>
              <Col md={3}>
                <Form.Group>
                  <Form.Label>Prefijo Nota</Form.Label>
                  <Form.Control
                    type="text"
                    value={prefijo}
                    onChange={(e) => setPrefijo(e.target.value)}
                    placeholder="Ej: NC"
                  />
                </Form.Group>
              </Col>
              <Col md={3}>
                <Form.Group>
                  <Form.Label>Concepto</Form.Label>
                  <Form.Select value={conceptoId} onChange={(e) => setConceptoId(e.target.value)}>
                    <option value="">Seleccione...</option>
                    {conceptos.map((c) => (
                      <option key={c.concepto_id || c.id} value={c.concepto_id || c.id}>
                        {c.descripcion || c.nombre}
                      </option>
                    ))}
                  </Form.Select>
                </Form.Group>
              </Col>
            </Row>
            <Row className="mt-3">
              <Col md={12}>
                <Form.Group>
                  <Form.Label>Observacion</Form.Label>
                  <Form.Control
                    as="textarea"
                    rows={2}
                    value={observacion}
                    onChange={(e) => setObservacion(e.target.value)}
                    placeholder="Detalle u observaciones de la nota"
                  />
                </Form.Group>
              </Col>
            </Row>
          </Form>

          {tipoNota === 'DEBITO' && (
            <Alert variant="warning">
              El envio de notas debito aun no esta habilitado en el backend. Puedes continuar con notas credito.
            </Alert>
          )}

          <Card className="mb-4">
            <Card.Header className="bg-light">
              <strong>Seleccionar Factura</strong>
            </Card.Header>
            <Card.Body>
              <Form className="mb-3">
                <Row className="align-items-end">
                  <Col md={3}>
                    <Form.Group>
                      <Form.Label>Desde</Form.Label>
                      <Form.Control
                        type="date"
                        value={filters.lapso_inicio}
                        onChange={(e) => setFilters({ ...filters, lapso_inicio: e.target.value })}
                      />
                    </Form.Group>
                  </Col>
                  <Col md={3}>
                    <Form.Group>
                      <Form.Label>Hasta</Form.Label>
                      <Form.Control
                        type="date"
                        value={filters.lapso_fin}
                        onChange={(e) => setFilters({ ...filters, lapso_fin: e.target.value })}
                      />
                    </Form.Group>
                  </Col>
                  <Col md={4}>
                    <Form.Group>
                      <Form.Label>Tercero (Nombre/NIT)</Form.Label>
                      <Form.Control
                        type="text"
                        placeholder="Buscar cliente..."
                        value={filters.tercero}
                        onChange={(e) => setFilters({ ...filters, tercero: e.target.value })}
                      />
                    </Form.Group>
                  </Col>
                  <Col md={2}>
                    <Button variant="outline-primary" className="w-100" onClick={() => loadFacturas(1)}>
                      <i className="fas fa-search me-2"></i>Buscar
                    </Button>
                  </Col>
                </Row>
              </Form>

              {loadingFacturas ? (
                <div className="text-center py-4">
                  <Spinner animation="border" variant="primary" />
                </div>
              ) : (
                <Table responsive striped hover size="sm">
                  <thead className="table-dark">
                    <tr>
                      <th>Factura</th>
                      <th>Fecha</th>
                      <th>Tercero</th>
                      <th className="text-end">Total</th>
                      <th className="text-center">Accion</th>
                    </tr>
                  </thead>
                  <tbody>
                    {facturas.length > 0 ? (
                      facturas.map((f) => (
                        <tr key={f.factura_fiscal_id} className={selectedFactura?.factura_fiscal_id === f.factura_fiscal_id ? 'table-primary' : ''}>
                          <td><strong>{f.prefijo} {f.factura_fiscal}</strong></td>
                          <td>{new Date(f.fecha_registro).toLocaleDateString('es-CO')}</td>
                          <td>
                            <div className="fw-bold">{f.tercero?.nombre_tercero || 'N/A'}</div>
                            <small className="text-muted">{f.tipo_id_tercero} {f.tercero_id}</small>
                          </td>
                          <td className="text-end fw-bold">{formatCurrency(f.total_factura)}</td>
                          <td className="text-center">
                            <Button
                              size="sm"
                              variant={selectedFactura?.factura_fiscal_id === f.factura_fiscal_id ? 'secondary' : 'primary'}
                              onClick={() => handleSelectFactura(f)}
                            >
                              {selectedFactura?.factura_fiscal_id === f.factura_fiscal_id ? 'Seleccionada' : 'Seleccionar'}
                            </Button>
                          </td>
                        </tr>
                      ))
                    ) : (
                      <tr>
                        <td colSpan="5" className="text-center py-3">No se encontraron facturas.</td>
                      </tr>
                    )}
                  </tbody>
                </Table>
              )}
            </Card.Body>
          </Card>

          {selectedFactura && (
            <Card className="mb-4">
              <Card.Header className="bg-light d-flex justify-content-between align-items-center">
                <strong>Items de la Factura</strong>
                <Badge bg={alcance === 'TOTAL' ? 'success' : 'info'}>{alcance}</Badge>
              </Card.Header>
              <Card.Body>
                <Table responsive striped hover size="sm">
                  <thead className="table-dark">
                    <tr>
                      <th>Sel</th>
                      <th>Descripcion</th>
                      <th>Cantidad</th>
                      <th className="text-end">Subtotal</th>
                      <th className="text-end">Valor Nota</th>
                    </tr>
                  </thead>
                  <tbody>
                    {getFacturaItems(selectedFactura).map((item) => {
                      const osItem = getOrdenServicioItem(item);
                      const subtotal = getItemSubtotal(osItem);
                      const checked = selectedItems.includes(item.item_id);
                      return (
                        <tr key={item.item_id}>
                          <td>
                            <Form.Check
                              type="checkbox"
                              checked={checked}
                              disabled={alcance === 'TOTAL'}
                              onChange={() => handleSelectItem(item.item_id)}
                            />
                          </td>
                          <td>{osItem?.nombre_servicio || osItem?.descripcion || 'Servicio'}</td>
                          <td>{osItem?.cantidad || 1}</td>
                          <td className="text-end">{formatCurrency(subtotal)}</td>
                          <td className="text-end">
                            <Form.Control
                              type="number"
                              min="0"
                              step="0.01"
                              value={itemAmounts[item.item_id] ?? (alcance === 'TOTAL' ? subtotal : 0)}
                              disabled={!checked}
                              onChange={(e) => handleAmountChange(item.item_id, e.target.value)}
                            />
                          </td>
                        </tr>
                      );
                    })}
                  </tbody>
                </Table>
              </Card.Body>
            </Card>
          )}

          <div className="d-flex justify-content-between align-items-center">
            <div>
              <strong>Total Nota:</strong> {formatCurrency(totalNota)}
            </div>
            <Button variant="success" onClick={handleCrearNota} disabled={loading}>
              {loading ? 'Creando...' : 'Crear Nota'}
            </Button>
          </div>
        </Card.Body>
      </Card>
    </div>
  );
};

export default NotasCreditoView;
