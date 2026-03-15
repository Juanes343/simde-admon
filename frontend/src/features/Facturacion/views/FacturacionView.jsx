import React, { useState, useEffect } from 'react';
import { Card, Table, Button, Form, Row, Col, Badge, Spinner, Alert } from 'react-bootstrap';
import { useNavigate } from 'react-router-dom';
import facturacionService from '../../../services/facturacionService';
import { formatCurrency } from '../../../utils/formatters';
import Swal from 'sweetalert2';

const FacturacionView = () => {
  const navigate = useNavigate();
  const [prefijos, setPrefijos] = useState([]);
  const [ordenes, setOrdenes] = useState([]);
  const [loading, setLoading] = useState(false);
  const [prefijoSelected, setPrefijoSelected] = useState('');
  const [selectedItems, setSelectedItems] = useState([]);
  const [editedItems, setEditedItems] = useState({}); // { [item_id]: { cantidad, precio_unitario, observacion } }
  const [expandedOrdenes, setExpandedOrdenes] = useState({}); // { [orden_servicio_id]: bool }
  const [generando, setGenerando] = useState(false);
  const [filters, setFilters] = useState({
    lapso_inicio: '',
    lapso_fin: '',
    tercero: ''
  });

  useEffect(() => {
    loadPrefijos();
  }, []);

  // Buscador dinámico con debounce
  useEffect(() => {
    const delayDebounceFn = setTimeout(() => {
      loadPendientes();
    }, 500);

    return () => clearTimeout(delayDebounceFn);
  }, [filters]);

  // --- Helpers para valores editados ---
  const getItemCantidad = (item) => editedItems[item.item_id]?.cantidad ?? item.cantidad;
  const getItemPrecio = (item) => editedItems[item.item_id]?.precio_unitario ?? item.precio_unitario;
  const getItemSubtotal = (item) => {
    const c = parseFloat(getItemCantidad(item)) || 0;
    const p = parseFloat(getItemPrecio(item)) || 0;
    return c * p;
  };
  const getItemObservacion = (item) => editedItems[item.item_id]?.observacion ?? (item.observaciones || '');
  const getItemIvaPorcentaje = (item) => {
    if (editedItems[item.item_id]?.impuesto_porcentaje !== undefined) {
      return parseFloat(editedItems[item.item_id].impuesto_porcentaje) || 0;
    }
    return parseFloat(item.servicio?.impuesto?.porcentaje || 0);
  };
  const getItemTotalConIva = (item) => {
    const subtotal = getItemSubtotal(item);
    const iva = getItemIvaPorcentaje(item);
    return subtotal + (subtotal * iva / 100);
  };
  const handleItemEdit = (itemId, field, value) => {
    setEditedItems(prev => ({
      ...prev,
      [itemId]: { ...prev[itemId], [field]: value }
    }));
  };

  const loadPrefijos = async () => {
    try {
      const data = await facturacionService.getPrefijos();
      setPrefijos(data);
      if (data.length > 0) setPrefijoSelected(data[0].documento_id);
    } catch (error) {
      console.error("Error cargando prefijos", error);
    }
  };

  const loadPendientes = async () => {
    setLoading(true);
    setEditedItems({}); // Limpiar overrides al recargar
    try {
      const data = await facturacionService.getPendientes(filters.lapso_inicio, filters.lapso_fin, filters.tercero);
      setOrdenes(data);
      setExpandedOrdenes({}); // Colapsar todas al recargar
      
      // Pre-seleccionar todos los items activos (el usuario puede editar el precio de los que valen $0)
      const todosLosItems = [];
      data.forEach(orden => {
        orden.items.filter(i => i.estado === '1' || i.estado === 1).forEach(item => {
          todosLosItems.push(item.item_id);
        });
      });
      setSelectedItems(todosLosItems);
    } catch (error) {
      Swal.fire('Error', 'No se pudieron cargar las órdenes pendientes', 'error');
    } finally {
      setLoading(false);
    }
  };

  const handleSelectAll = (orden) => {
    const itemIds = orden.items.filter(i => i.estado === '1' || i.estado === 1).map(i => i.item_id);
    const allSelected = itemIds.every(id => selectedItems.includes(id));

    if (allSelected) {
      setSelectedItems(selectedItems.filter(id => !itemIds.includes(id)));
    } else {
      setSelectedItems([...new Set([...selectedItems, ...itemIds])]);
    }
  };

  const handleSelectItem = (itemId) => {
    if (selectedItems.includes(itemId)) {
      setSelectedItems(selectedItems.filter(id => id !== itemId));
    } else {
      setSelectedItems([...selectedItems, itemId]);
    }
  };

  const getPeriodoFacturable = (orden) => {
    const hoy = new Date();
    const fechaInicio = new Date(orden.fecha_inicio);
    const fechaFin = new Date(orden.fecha_fin);

    // Si hoy está fuera del rango, return null
    if (hoy < fechaInicio || hoy > fechaFin) {
      return null;
    }

    // El período es el mes actual
    const meses = [
      'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
      'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'
    ];
    const mesActual = hoy.getMonth();
    const anioActual = hoy.getFullYear();
    
    return {
      mes: mesActual + 1,
      nombre: meses[mesActual],
      anio: anioActual,
      label: `${meses[mesActual]} ${anioActual}`
    };
  };

  const handleFacturarOrden = async (orden) => {
    if (!prefijoSelected) return Swal.fire('Atención', 'Seleccione un prefijo de facturación', 'warning');
    
    const periodo = getPeriodoFacturable(orden);
    if (!periodo) return Swal.fire('Atención', 'La orden no está en período de facturación', 'warning');
    
    const itemsOrden = orden.items.filter(i => selectedItems.includes(i.item_id));
    
    if (itemsOrden.length === 0) return Swal.fire('Atención', 'Seleccione al menos un ítem para facturar', 'warning');

    // Validar que todos los ítems seleccionados tengan precio > 0
    const sinPrecio = itemsOrden.filter(i => getItemSubtotal(i) <= 0.01);
    if (sinPrecio.length > 0) {
      return Swal.fire('Atención', `${sinPrecio.length} ítem(s) seleccionado(s) tienen precio $0. Ingrese un precio antes de facturar.`, 'warning');
    }

    const result = await Swal.fire({
      title: '¿Generar Factura?',
      text: `Se facturarán ${itemsOrden.length} ítems de la orden ${orden.numero_orden} para ${periodo.label}.`,
      icon: 'question',
      showCancelButton: true,
      confirmButtonText: 'Sí, Facturar',
      cancelButtonText: 'Cancelar'
    });

    if (result.isConfirmed) {
      setGenerando(true);
      Swal.fire({
        title: 'Generando factura...',
        html: '<p>Creando factura y enviando a DataIco / DIAN.<br>Por favor espere.</p>',
        allowOutsideClick: false,
        allowEscapeKey: false,
        showConfirmButton: false,
        didOpen: () => Swal.showLoading()
      });
      try {
        const fechaPeriodoInicio = new Date(periodo.anio, periodo.mes - 1, 1);
        const fechaPeriodoFin = new Date(periodo.anio, periodo.mes, 0);
        
        const payload = {
          documento_id: prefijoSelected,
          tercero_id: orden.tercero_id,
          tipo_id_tercero: orden.tipo_id_tercero,
          items: itemsOrden.map(item => ({
            item_id: item.item_id,
            cantidad: parseFloat(getItemCantidad(item)),
            precio_unitario: parseFloat(getItemPrecio(item)),
            observacion: getItemObservacion(item),
          })),
          observacion: `Facturación ${periodo.label}`,
          fecha_periodo_inicio: fechaPeriodoInicio.toISOString().split('T')[0],
          fecha_periodo_fin: fechaPeriodoFin.toISOString().split('T')[0]
        };
        
        const response = await facturacionService.generarFactura(payload);
        
        Swal.fire({
          title: '¡Factura generada!',
          html: `<p>Factura generada y enviada correctamente.</p><p><strong>Número:</strong> ${response.factura.prefijo}-${response.factura.factura_fiscal}</p><p><strong>CUFE:</strong> ${response.factura.cufe || 'Pendiente'}</p>`,
          icon: 'success',
          confirmButtonText: 'Aceptar'
        });
        
        // Remover items facturados del listado
        const nuevosItems = selectedItems.filter(id => !itemsOrden.map(i => i.item_id).includes(id));
        setSelectedItems(nuevosItems);
        loadPendientes();
      } catch (error) {
        Swal.fire('Error', error.response?.data?.message || 'Error al generar la factura', 'error');
      } finally {
        setGenerando(false);
      }
    }
  };

  const handleFacturar = async () => {
    if (!prefijoSelected) return Swal.fire('Atención', 'Seleccione un prefijo de facturación', 'warning');
    if (selectedItems.length === 0) return Swal.fire('Atención', 'Seleccione al menos un ítem para facturar', 'warning');

    const result = await Swal.fire({
      title: '¿Generar Factura?',
      text: `Se facturarán ${selectedItems.length} ítems.`,
      icon: 'question',
      showCancelButton: true,
      confirmButtonText: 'Sí, Facturar',
      cancelButtonText: 'Cancelar'
    });

    if (result.isConfirmed) {
      try {
        // Para este ejemplo tomamos el tercero de la primera orden seleccionada
        // En un caso real, la lógica podría agrupar por tercero automáticamente
        const firstItemId = selectedItems[0];
        const targetOrden = ordenes.find(o => o.items.some(i => i.item_id === firstItemId));
        
        const payload = {
          documento_id: prefijoSelected,
          tercero_id: targetOrden.tercero_id,
          tipo_id_tercero: targetOrden.tipo_id_tercero,
          items: selectedItems.map(id => ({ item_id: id })),
          observacion: "Facturación automática desde módulo de facturas."
        };

        const response = await facturacionService.generarFactura(payload);
        const dataIco = response.dataIco;

        if (dataIco?.debug) {
          Swal.fire({
            title: 'PREVISUALIZACION JSON (DEBUG)',
            html: `<div class="text-start">
                    <p><b>Endpoint:</b> /${dataIco.endpoint_target || 'N/A'}</p>
                    <pre style="background: #f4f4f4; padding: 10px; font-size: 11px; max-height: 400px; overflow-y: auto;">${JSON.stringify(dataIco.payload || {}, null, 2)}</pre>
                   </div>`,
            icon: 'warning',
            width: '800px'
          });
          return;
        }

        if (dataIco?.success) {
          const dianStatus = dataIco.data?.dian_status || 'PENDIENTE';
          const cufe = dataIco.data?.cufe || 'N/A';
          const customerStatus = dataIco.data?.customer_status || 'N/A';
          const emailStatus = dataIco.data?.email_status || 'N/A';

          Swal.fire({
            title: 'Factura creada y enviada',
            html: `<div class="text-start">
                    <p><b>Estado DIAN:</b> ${dianStatus}</p>
                    <p><b>Estado Cliente:</b> ${customerStatus}</p>
                    <p><b>Estado Email:</b> ${emailStatus}</p>
                    <small class="text-muted">CUFE: ${cufe}</small>
                   </div>`,
            icon: 'success'
          });
        } else {
          Swal.fire({
            title: 'Factura creada, envio fallido',
            html: `<div class="text-start">
                    <p>${dataIco?.message || 'No se pudo enviar a DataIco'}</p>
                   </div>`,
            icon: 'warning'
          });
        }

        setSelectedItems([]);
        loadPendientes();
      } catch (error) {
        Swal.fire('Error', error.response?.data?.message || 'Error al generar la factura', 'error');
      }
    }
  };

  return (
    <div className="container-fluid py-4">
      <Card className="shadow-sm mb-4">
        <Card.Header className="bg-primary text-white d-flex justify-content-between align-items-center">
          <h5 className="mb-0">Facturación de Órdenes Pendientes</h5>
          <div>
            <Button variant="light" size="sm" className="me-2" onClick={() => navigate('/facturas')}>
              <i className="fas fa-list me-2"></i>Ver Histórico
            </Button>
            <Button variant="light" size="sm" onClick={() => navigate('/dashboard')}>
              <i className="fas fa-arrow-left me-2"></i>Volver al Dashboard
            </Button>
          </div>
        </Card.Header>
        <Card.Body>
          <Form className="mb-4">
            <Row className="align-items-end">
              <Col md={3}>
                <Form.Group>
                  <Form.Label>Lapso Inicio</Form.Label>
                  <Form.Control 
                    type="date" 
                    value={filters.lapso_inicio}
                    onChange={(e) => setFilters({...filters, lapso_inicio: e.target.value})}
                  />
                </Form.Group>
              </Col>
              <Col md={3}>
                <Form.Group>
                  <Form.Label>Lapso Fin</Form.Label>
                  <Form.Control 
                    type="date"
                    value={filters.lapso_fin}
                    onChange={(e) => setFilters({...filters, lapso_fin: e.target.value})}
                  />
                </Form.Group>
              </Col>
              <Col md={3}>
                <Form.Group>
                  <Form.Label>Filtrar por Tercero (Nombre o NIT)</Form.Label>
                  <Form.Control 
                    type="text"
                    placeholder="Escriba nombre o documento..."
                    value={filters.tercero}
                    onChange={(e) => setFilters({...filters, tercero: e.target.value})}
                  />
                </Form.Group>
              </Col>
              <Col md={3}>
                <Button variant="outline-primary" className="w-100" onClick={loadPendientes}>
                  Filtrar Pendientes
                </Button>
              </Col>
            </Row>
            <hr />
            <Row>
              <Col md={12}>
                <Form.Group>
                  <Form.Label className="fw-bold text-primary">Prefijo Facturación Habilitado</Form.Label>
                  <Form.Select 
                    value={prefijoSelected} 
                    onChange={(e) => setPrefijoSelected(e.target.value)}
                  >
                    <option value="">Seleccione prefijo...</option>
                    {prefijos.map(p => (
                      <option key={p.documento_id} value={p.documento_id}>
                        {p.prefijo} - {p.descripcion} (Sig: {p.numeracion + 1})
                      </option>
                    ))}
                  </Form.Select>
                </Form.Group>
              </Col>
            </Row>
          </Form>

          {loading ? (
            <div className="text-center py-5">
              <Spinner animation="border" variant="primary" />
              <p className="mt-2">Buscando órdenes pendientes...</p>
            </div>
          ) : ordenes.length === 0 ? (
            <Alert variant="info" className="text-center">No hay órdenes pendientes por facturar para este criterio.</Alert>
          ) : (
            <>
              {ordenes.map(orden => {
                    const itemsOrden = orden.items.filter(i => i.estado === '1' || i.estado === 1);
                    const itemsSeleccionados = itemsOrden.filter(i => selectedItems.includes(i.item_id));
                    const totalSeleccionado = itemsSeleccionados.reduce((acc, item) => acc + getItemSubtotal(item), 0);
                    const haySinPrecio = itemsSeleccionados.some(i => getItemSubtotal(i) <= 0.01);
                    const periodo = getPeriodoFacturable(orden);
                    const isExpanded = !!expandedOrdenes[orden.orden_servicio_id];
                    return (
                      <Card key={orden.orden_servicio_id} className="mb-3 border-primary">
                        {/* ── ENCABEZADO SIEMPRE VISIBLE ── */}
                        <Card.Header
                          className="bg-dark text-white py-2"
                          style={{ cursor: 'pointer' }}
                          onClick={() => setExpandedOrdenes(prev => ({ ...prev, [orden.orden_servicio_id]: !prev[orden.orden_servicio_id] }))}
                        >
                          <div className="d-flex justify-content-between align-items-center">
                            <div className="d-flex align-items-center gap-3">
                              {/* Checkbox seleccionar todos - detener propagación para no abrir/cerrar */}
                              <span onClick={e => e.stopPropagation()}>
                                <Form.Check
                                  type="checkbox"
                                  inline
                                  id={`check-orden-${orden.orden_servicio_id}`}
                                  label={<span className="text-white fw-bold">Orden: {orden.numero_orden}</span>}
                                  checked={itemsOrden.length > 0 && itemsOrden.every(i => selectedItems.includes(i.item_id))}
                                  onChange={() => handleSelectAll(orden)}
                                />
                              </span>
                              <span className="text-warning fw-bold border-start ps-3">
                                TERCERO: {orden.tercero?.nombre_tercero}
                              </span>
                            </div>
                            <div className="d-flex align-items-center gap-2">
                              {periodo ? (
                                <Badge bg="success">Período: {periodo.label}</Badge>
                              ) : (
                                <Badge bg="danger">Fuera de período</Badge>
                              )}
                              <i className={`fas fa-chevron-${isExpanded ? 'up' : 'down'} text-white ms-2`}></i>
                            </div>
                          </div>
                          {orden.observaciones && (
                            <div className="mt-2 border-top border-secondary pt-2">
                              <small className="text-white-50 text-uppercase fw-bold me-2" style={{ letterSpacing: '0.05em' }}>
                                <i className="fas fa-sticky-note me-1"></i>Observaciones:
                              </small>
                              <span className="text-white fst-italic small">{orden.observaciones}</span>
                            </div>
                          )}
                        </Card.Header>

                        {/* ── ITEMS: solo visibles si está expandido ── */}
                        {isExpanded && (
                          <>
                            <Table size="sm" responsive className="mb-0 bg-white">
                              <thead>
                                <tr>
                                  <th width="40"></th>
                                  <th>Servicio</th>
                                  <th className="text-center" style={{ width: '100px' }}>Cant.</th>
                                  <th className="text-end" style={{ width: '140px' }}>Precio Unit.</th>
                                  <th className="text-end" style={{ width: '110px' }}>Subtotal</th>
                                  <th className="text-center" style={{ width: '80px' }}>% IVA</th>
                                  <th className="text-end" style={{ width: '130px' }}>Total c/IVA</th>
                                </tr>
                              </thead>
                              <tbody>
                                {itemsOrden.map(item => (
                                  <tr key={item.item_id}>
                                    <td className="text-center">
                                      <Form.Check
                                        type="checkbox"
                                        checked={selectedItems.includes(item.item_id)}
                                        onChange={() => handleSelectItem(item.item_id)}
                                      />
                                    </td>
                                    <td>
                                      {item.nombre_servicio}
                                      {getItemSubtotal(item) <= 0.01 && (
                                        <Badge bg="warning" text="dark" className="ms-2">Sin precio</Badge>
                                      )}
                                      <Form.Control
                                        type="text"
                                        size="sm"
                                        className="mt-1"
                                        placeholder="Observaciones..."
                                        value={getItemObservacion(item)}
                                        onChange={(e) => handleItemEdit(item.item_id, 'observacion', e.target.value)}
                                      />
                                    </td>
                                    <td className="text-center">
                                      <Form.Control
                                        type="number"
                                        size="sm"
                                        step="0.01"
                                        min="0.01"
                                        value={getItemCantidad(item)}
                                        onChange={(e) => handleItemEdit(item.item_id, 'cantidad', e.target.value)}
                                        style={{ width: '80px', display: 'inline-block' }}
                                      />
                                    </td>
                                    <td className="text-end">
                                      <Form.Control
                                        type="number"
                                        size="sm"
                                        step="0.01"
                                        min="0"
                                        value={getItemPrecio(item)}
                                        onChange={(e) => handleItemEdit(item.item_id, 'precio_unitario', e.target.value)}
                                        style={{ width: '120px', display: 'inline-block', textAlign: 'right' }}
                                      />
                                    </td>
                                    <td className="text-end fw-bold text-success">{formatCurrency(getItemSubtotal(item))}</td>
                                    <td className="text-center">
                                      <Form.Control
                                        type="number"
                                        size="sm"
                                        step="0.5"
                                        min="0"
                                        max="100"
                                        value={getItemIvaPorcentaje(item)}
                                        onChange={(e) => handleItemEdit(item.item_id, 'impuesto_porcentaje', e.target.value)}
                                        style={{ width: '70px', display: 'inline-block', textAlign: 'right' }}
                                      />
                                    </td>
                                    <td className="text-end fw-bold text-primary">{formatCurrency(getItemTotalConIva(item))}</td>
                                  </tr>
                                ))}
                              </tbody>
                            </Table>
                            <Card.Footer className="bg-light d-flex justify-content-end align-items-center gap-3 p-3">
                              {itemsSeleccionados.length > 0 && (
                                <>
                                  <span className="fw-bold text-dark">
                                    Total a facturar: {formatCurrency(totalSeleccionado)}
                                  </span>
                                  {haySinPrecio ? (
                                    <small className="text-warning">
                                      <i className="fas fa-exclamation-triangle me-1"></i>
                                      Hay ítems sin precio
                                    </small>
                                  ) : (
                                    <Button
                                      variant="success"
                                      size="sm"
                                      onClick={() => handleFacturarOrden(orden)}
                                      disabled={generando}
                                    >
                                      {generando ? (
                                        <><Spinner animation="border" size="sm" className="me-2" />Generando...</>
                                      ) : (
                                        <><i className="fas fa-file-invoice-dollar me-2"></i>GENERAR FACTURA ({itemsSeleccionados.length} ítems)</>
                                      )}
                                    </Button>
                                  )}
                                </>
                              )}
                            </Card.Footer>
                          </>
                        )}
                      </Card>
                    );
                  })}
            </>
          )}
        </Card.Body>
      </Card>
    </div>
  );
};

export default FacturacionView;
