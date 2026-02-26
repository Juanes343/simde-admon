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
    try {
      const data = await facturacionService.getPendientes(filters.lapso_inicio, filters.lapso_fin, filters.tercero);
      setOrdenes(data);
      
      // Pre-seleccionar todos los items
      const todosLosItems = [];
      data.forEach(orden => {
        orden.items.forEach(item => {
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
    const itemIds = orden.items.map(i => i.item_id);
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

    const result = await Swal.fire({
      title: '¿Generar Factura?',
      text: `Se facturarán ${itemsOrden.length} ítems de la orden ${orden.numero_orden} para ${periodo.label}.`,
      icon: 'question',
      showCancelButton: true,
      confirmButtonText: 'Sí, Facturar',
      cancelButtonText: 'Cancelar'
    });

    if (result.isConfirmed) {
      try {
        const fechaPeriodoInicio = new Date(periodo.anio, periodo.mes - 1, 1);
        const fechaPeriodoFin = new Date(periodo.anio, periodo.mes, 0);
        
        const payload = {
          documento_id: prefijoSelected,
          tercero_id: orden.tercero_id,
          tipo_id_tercero: orden.tipo_id_tercero,
          items: itemsOrden.map(item => ({ item_id: item.item_id })),
          observacion: `Facturación ${periodo.label}`,
          fecha_periodo_inicio: fechaPeriodoInicio.toISOString().split('T')[0],
          fecha_periodo_fin: fechaPeriodoFin.toISOString().split('T')[0]
        };
        
        const response = await facturacionService.generarFactura(payload);
        
        Swal.fire({
          title: 'Éxito',
          html: `<p>Factura generada correctamente</p><p><strong>Número:</strong> ${response.factura.prefijo}-${response.factura.factura_fiscal}</p><p><strong>CUFE:</strong> ${response.factura.cufe || 'Pendiente'}</p>`,
          icon: 'success',
          confirmButtonText: 'Aceptar'
        });
        
        // Remover items facturados del listado
        const nuevosItems = selectedItems.filter(id => !itemsOrden.map(i => i.item_id).includes(id));
        setSelectedItems(nuevosItems);
        loadPendientes();
      } catch (error) {
        Swal.fire('Error', error.response?.data?.message || 'Error al generar la factura', 'error');
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
              {ordenes.map(orden => (
                <Card key={orden.orden_servicio_id} className="mb-4 border-primary">
                  <Card.Header className="bg-dark text-white d-flex justify-content-between align-items-center py-2">
                    <div>
                      <Form.Check 
                        type="checkbox"
                        inline
                        id={`check-orden-${orden.orden_servicio_id}`}
                        label={<span className="text-white fw-bold">Orden: {orden.numero_orden}</span>}
                        checked={orden.items.every(i => selectedItems.includes(i.item_id))}
                        onChange={() => handleSelectAll(orden)}
                      />
                      <span className="ms-3 text-warning fw-bold border-start ps-3">
                        TERCERO: {orden.tercero?.nombre_tercero}
                      </span>
                    </div>
                    {getPeriodoFacturable(orden) ? (
                      <Badge bg="success">Período: {getPeriodoFacturable(orden).label}</Badge>
                    ) : (
                      <Badge bg="danger">Fuera de período</Badge>
                    )}
                  </Card.Header>
                  <Table size="sm" responsive className="mb-0 bg-white">
                    <thead>
                      <tr>
                        <th width="40"></th>
                        <th>Servicio</th>
                        <th className="text-center">Cant.</th>
                        <th className="text-end">Precio Unit.</th>
                        <th className="text-end">Subtotal</th>
                      </tr>
                    </thead>
                    <tbody>
                      {orden.items.map(item => (
                        <tr key={item.item_id}>
                          <td className="text-center">
                            <Form.Check 
                              type="checkbox"
                              checked={selectedItems.includes(item.item_id)}
                              onChange={() => handleSelectItem(item.item_id)}
                            />
                          </td>
                          <td>{item.nombre_servicio}</td>
                          <td className="text-center">{item.cantidad}</td>
                          <td className="text-end text-success">{formatCurrency(item.precio_unitario)}</td>
                          <td className="text-end fw-bold">{formatCurrency(item.subtotal)}</td>
                        </tr>
                      ))}
                    </tbody>
                  </Table>
                  <Card.Footer className="bg-light d-flex justify-content-end p-3">
                    {orden.items.filter(i => selectedItems.includes(i.item_id)).length > 0 && (
                      <Button
                        variant="success"
                        size="sm"
                        onClick={() => handleFacturarOrden(orden)}
                      >
                        <i className="fas fa-file-invoice-dollar me-2"></i>
                        GENERAR FACTURA ({orden.items.filter(i => selectedItems.includes(i.item_id)).length} ítems)
                      </Button>
                    )}
                  </Card.Footer>
                </Card>
              ))}
            </>
          )}
        </Card.Body>
      </Card>
    </div>
  );
};

export default FacturacionView;
