import React, { useState, useEffect, useRef } from 'react';
import {
  Card, Row, Col, Form, Button, Table, InputGroup,
  Modal, Badge,
} from 'react-bootstrap';
import api from '../../../utils/api';

const METODOS_PAGO = [
  'Consignación bancaria',
  'Transferencia electrónica',
  'Cheque',
  'Efectivo',
  'Otro',
];

const TIPOS_PAGO = ['Contado', 'Crédito 15 días', 'Crédito 30 días', 'Crédito 60 días', 'Crédito 90 días'];

const CotizacionFormView = ({ cotizacion, onSubmit, onCancel, loading }) => {
  const isEditMode = !!cotizacion;

  // ── Catálogos ──────────────────────────────────────────────────────────────
  const [terceros, setTerceros]     = useState([]);
  const [servicios, setServicios]   = useState([]);
  const [impuestos, setImpuestos]   = useState([]);
  const [retenciones, setRetenciones] = useState([]);
  const [loadingData, setLoadingData] = useState(true);

  // ── Cabecera ───────────────────────────────────────────────────────────────
  const [formData, setFormData] = useState({
    tipo_id_tercero: '',
    tercero_id:      '',
    fecha_emision:   new Date().toISOString().split('T')[0],
    fecha_vencimiento: '',
    metodo_pago:     'Consignación bancaria',
    tipo_pago:       'Contado',
    orden_compra:    '',
    notas:           '',
  });

  // ── Buscador tercero ───────────────────────────────────────────────────────
  const [terceroSearch, setTerceroSearch]           = useState('');
  const [showTerceroDropdown, setShowTerceroDropdown] = useState(false);
  const [filteredTerceros, setFilteredTerceros]     = useState([]);
  const [terceroDropUp, setTerceroDropUp]           = useState(false);
  const terceroRef = useRef(null);

  // ── Buscador servicio ──────────────────────────────────────────────────────
  const [servicioSearch, setServicioSearch]           = useState('');
  const [showServicioDropdown, setShowServicioDropdown] = useState(false);
  const [filteredServicios, setFilteredServicios]     = useState([]);
  const [servicioDropUp, setServicioDropUp]           = useState(false);
  const servicioRef = useRef(null);

  // Refs de las secciones que un desplegable no debe tapar
  const agregarItemCardRef = useRef(null);
  const notasRowRef        = useRef(null);

  // Decide si el desplegable debe abrirse hacia arriba: mide el espacio real
  // hasta el siguiente elemento de la página que quedaría tapado (ej. la
  // tarjeta "Notas"), no solo hasta el borde de la ventana — si hay scroll
  // debajo pero esa tarjeta está pegada, igual debe abrir hacia arriba.
  const computeDropUp = (fieldRef, boundaryRef, neededHeight) => {
    if (!fieldRef.current) return false;
    const fieldBottom = fieldRef.current.getBoundingClientRect().bottom;
    let limit = window.innerHeight;
    if (boundaryRef?.current) {
      limit = Math.min(limit, boundaryRef.current.getBoundingClientRect().top);
    }
    return (limit - fieldBottom) < neededHeight;
  };

  // ── Nuevo ítem (formulario inline) ────────────────────────────────────────
  const [nuevoItem, setNuevoItem] = useState({
    servicio_id:           '',
    referencia:            '',
    descripcion:           '',
    cantidad:              '1',
    tipo_unidad:           'UNIDAD',
    precio_unitario:       '',
    porcentaje_descuento:  '0',
    porcentaje_ret_fuente: '',
    impuesto_id:           '',
    observaciones:         '',
  });

  // ── Lista de ítems ─────────────────────────────────────────────────────────
  const [items, setItems] = useState([]);

  // ── Modal confirmar eliminar ítem ──────────────────────────────────────────
  const [showDeleteModal, setShowDeleteModal]   = useState(false);
  const [indexToDelete, setIndexToDelete]       = useState(null);

  // ─────────────────────────────────────────────────────────────────────────
  useEffect(() => { loadData(); }, []);

  // Filtrar terceros al escribir
  useEffect(() => {
    if (terceroSearch.length < 2) { setFilteredTerceros([]); return; }
    const term = terceroSearch.toLowerCase();
    setFilteredTerceros(
      terceros.filter(
        (t) =>
          (t.nombre_tercero || '').toLowerCase().includes(term) ||
          t.tercero_id.toLowerCase().includes(term)
      ).slice(0, 10)
    );
  }, [terceroSearch, terceros]);

  // Filtrar servicios al escribir
  useEffect(() => {
    if (servicioSearch.length < 1) { setFilteredServicios([]); return; }
    const term = servicioSearch.toLowerCase();
    setFilteredServicios(
      servicios.filter(
        (s) =>
          s.nombre_servicio.toLowerCase().includes(term) ||
          (s.descripcion || '').toLowerCase().includes(term)
      ).slice(0, 10)
    );
  }, [servicioSearch, servicios]);

  // Cerrar dropdowns al hacer click fuera
  useEffect(() => {
    const handleClick = (e) => {
      if (terceroRef.current && !terceroRef.current.contains(e.target)) {
        setShowTerceroDropdown(false);
      }
      if (servicioRef.current && !servicioRef.current.contains(e.target)) {
        setShowServicioDropdown(false);
      }
    };
    document.addEventListener('mousedown', handleClick);
    return () => document.removeEventListener('mousedown', handleClick);
  }, []);

  // Cargar datos de cotización al editar
  useEffect(() => {
    if (cotizacion && !loadingData) {
      setFormData({
        tipo_id_tercero:   cotizacion.tipo_id_tercero || '',
        tercero_id:        cotizacion.tercero_id      || '',
        fecha_emision:     cotizacion.fecha_emision     ? cotizacion.fecha_emision.substring(0, 10)     : '',
        fecha_vencimiento: cotizacion.fecha_vencimiento ? cotizacion.fecha_vencimiento.substring(0, 10) : '',
        metodo_pago:       cotizacion.metodo_pago     || 'Consignación bancaria',
        tipo_pago:         cotizacion.tipo_pago       || 'Contado',
        orden_compra:      cotizacion.orden_compra    || '',
        notas:             cotizacion.notas           || '',
      });

      if (cotizacion.tercero) {
        setTerceroSearch(
          `${cotizacion.tercero.nombre_tercero || cotizacion.tercero_id} (${cotizacion.tercero_id})`
        );
      }

      if (cotizacion.items) {
        setItems(
          cotizacion.items.map((item) => ({
            item_id:               item.item_id,
            servicio_id:           item.servicio_id   || '',
            referencia:            item.referencia    || '',
            descripcion:           item.descripcion,
            cantidad:              String(item.cantidad),
            tipo_unidad:           item.tipo_unidad   || 'UNIDAD',
            precio_unitario:       String(item.precio_unitario),
            porcentaje_descuento:  String(item.porcentaje_descuento  || '0'),
            porcentaje_ret_fuente: item.porcentaje_ret_fuente != null ? String(item.porcentaje_ret_fuente) : '',
            impuesto_id:           item.impuesto_id   ? String(item.impuesto_id) : '',
            observaciones:         item.observaciones || '',
          }))
        );
      }
    }
  }, [cotizacion, loadingData]);

  const loadData = async () => {
    try {
      setLoadingData(true);
      const [tercerosRes, serviciosRes, impuestosRes, retencionesRes] = await Promise.all([
        api.get('/terceros', { params: { per_page: 500 } }),
        api.get('/servicios/activos'),
        api.get('/impuestos'),
        api.get('/retencion-fuente'),
      ]);
      setTerceros(tercerosRes.data?.data || []);
      setServicios(serviciosRes.data || []);
      setImpuestos(impuestosRes.data?.data || []);
      setRetenciones(retencionesRes.data?.data || []);
    } catch (err) {
      console.error('Error cargando datos:', err);
    } finally {
      setLoadingData(false);
    }
  };

  // ── Cabecera handlers ──────────────────────────────────────────────────────
  const handleChange = (e) => {
    const { name, value } = e.target;
    setFormData((prev) => ({ ...prev, [name]: value }));
  };

  const handleSelectTercero = (t) => {
    setFormData((prev) => ({
      ...prev,
      tipo_id_tercero: t.tipo_id_tercero,
      tercero_id:      t.tercero_id,
    }));
    setTerceroSearch(`${t.nombre_tercero || t.tercero_id} (${t.tercero_id})`);
    setShowTerceroDropdown(false);
    setFilteredTerceros([]);
  };

  // ── Servicio handlers ──────────────────────────────────────────────────────
  const handleSelectServicio = (s) => {
    setNuevoItem((prev) => ({
      ...prev,
      servicio_id:      s.servicio_id,
      referencia:       String(s.servicio_id),
      descripcion:      s.nombre_servicio,
      tipo_unidad:      s.tipo_unidad || 'UNIDAD',
      precio_unitario:  String(s.precio_unitario),
      impuesto_id:      s.impuesto_id ? String(s.impuesto_id) : '',
    }));
    setServicioSearch(s.nombre_servicio);
    setShowServicioDropdown(false);
    setFilteredServicios([]);
  };

  const handleNuevoItemChange = (field, value) => {
    setNuevoItem((prev) => ({ ...prev, [field]: value }));
  };

  // ── Agregar ítem a la lista ────────────────────────────────────────────────
  const handleAgregarItem = () => {
    if (!nuevoItem.descripcion.trim()) return;
    if (!nuevoItem.cantidad || parseFloat(nuevoItem.cantidad) <= 0) return;
    if (nuevoItem.precio_unitario === '' || parseFloat(nuevoItem.precio_unitario) < 0) return;

    setItems((prev) => [...prev, { ...nuevoItem }]);
    resetNuevoItem();
  };

  const handleAgregarAIU = () => {
    setNuevoItem({
      servicio_id:           '',
      referencia:            'AIU',
      descripcion:           'Administración, Imprevistos y Utilidades (AIU)',
      cantidad:              '1',
      tipo_unidad:           'UNIDAD',
      precio_unitario:       '',
      porcentaje_descuento:  '0',
      porcentaje_ret_fuente: '',
      impuesto_id:           '',
      observaciones:         '',
    });
    setServicioSearch('AIU');
  };

  const handleAgregarBolsaPlastica = () => {
    setNuevoItem({
      servicio_id:           '',
      referencia:            'IMP-BP',
      descripcion:           'Impuesto bolsa plástica',
      cantidad:              '1',
      tipo_unidad:           'UNIDAD',
      precio_unitario:       '66',
      porcentaje_descuento:  '0',
      porcentaje_ret_fuente: '',
      impuesto_id:           '',
      observaciones:         '',
    });
    setServicioSearch('Bolsa plástica');
  };

  const resetNuevoItem = () => {
    setNuevoItem({
      servicio_id:           '',
      referencia:            '',
      descripcion:           '',
      cantidad:              '1',
      tipo_unidad:           'UNIDAD',
      precio_unitario:       '',
      porcentaje_descuento:  '0',
      porcentaje_ret_fuente: '',
      impuesto_id:           '',
      observaciones:         '',
    });
    setServicioSearch('');
  };

  const handleEliminarItem = (index) => {
    setIndexToDelete(index);
    setShowDeleteModal(true);
  };

  const confirmEliminar = () => {
    setItems((prev) => prev.filter((_, i) => i !== indexToDelete));
    setShowDeleteModal(false);
    setIndexToDelete(null);
  };

  // ── Cálculos ───────────────────────────────────────────────────────────────
  const calcularItemSubtotal = (item) => {
    const cant  = parseFloat(item.cantidad)        || 0;
    const precio = parseFloat(item.precio_unitario) || 0;
    return cant * precio;
  };

  const calcularItemTotal = (item) => {
    const subtotal = calcularItemSubtotal(item);
    const descPct  = parseFloat(item.porcentaje_descuento)  || 0;
    const retPct   = parseFloat(item.porcentaje_ret_fuente) || 0;
    const baseNeta = subtotal * (1 - descPct / 100);

    let impPct = 0;
    if (item.impuesto_id) {
      const imp = impuestos.find((i) => String(i.impuesto_id) === String(item.impuesto_id));
      if (imp) impPct = parseFloat(imp.porcentaje) || 0;
    }

    return baseNeta * (1 + impPct / 100) - baseNeta * (retPct / 100);
  };

  const calcularTotalesGlobales = () => {
    let subtotal   = 0;
    let descuento  = 0;
    let impuestoT  = 0;
    let retencionT = 0;

    items.forEach((item) => {
      const cant    = parseFloat(item.cantidad)        || 0;
      const precio  = parseFloat(item.precio_unitario) || 0;
      const descPct = parseFloat(item.porcentaje_descuento)  || 0;
      const retPct  = parseFloat(item.porcentaje_ret_fuente) || 0;
      const sub      = cant * precio;
      const desc     = sub * descPct / 100;
      const baseNeta = sub - desc;

      let impPct = 0;
      if (item.impuesto_id) {
        const imp = impuestos.find((i) => String(i.impuesto_id) === String(item.impuesto_id));
        if (imp) impPct = parseFloat(imp.porcentaje) || 0;
      }

      subtotal   += sub;
      descuento  += desc;
      impuestoT  += baseNeta * impPct / 100;
      retencionT += baseNeta * retPct / 100;
    });

    return { subtotal, descuento, impuestoT, retencionT, total: subtotal - descuento + impuestoT - retencionT };
  };

  const formatCurrency = (v) =>
    new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', minimumFractionDigits: 0 }).format(v || 0);

  // ── Submit ─────────────────────────────────────────────────────────────────
  const handleSubmit = (e) => {
    e.preventDefault();
    if (!formData.tipo_id_tercero || !formData.tercero_id) {
      alert('Debe seleccionar un cliente.');
      return;
    }
    if (items.length === 0) {
      alert('Debe agregar al menos un ítem.');
      return;
    }

    const payload = {
      ...formData,
      items: items.map((item, i) => ({
        ...item,
        orden:             i,
        cantidad:          parseFloat(item.cantidad)        || 1,
        precio_unitario:   parseFloat(item.precio_unitario) || 0,
        porcentaje_descuento:  parseFloat(item.porcentaje_descuento)  || 0,
        porcentaje_ret_fuente: item.porcentaje_ret_fuente !== '' ? parseFloat(item.porcentaje_ret_fuente) : null,
        impuesto_id:       item.impuesto_id || null,
        servicio_id:       item.servicio_id || null,
      })),
    };

    onSubmit(payload);
  };

  // ─────────────────────────────────────────────────────────────────────────
  const { subtotal, descuento, impuestoT, retencionT, total } = calcularTotalesGlobales();

  if (loadingData) {
    return <div className="text-center py-4"><div className="spinner-border text-primary" /></div>;
  }

  return (
    <Form onSubmit={handleSubmit}>
      {/* ── Cabecera ── */}
      <Card className="mb-3 shadow-sm">
        <Card.Body>
          <Row className="g-3">
            {/* CLIENTE */}
            <Col md={6}>
              <Form.Label className="fw-bold text-uppercase small">Cliente</Form.Label>
              <div ref={terceroRef} className="position-relative">
                <InputGroup>
                  <InputGroup.Text><i className="fas fa-user"></i></InputGroup.Text>
                  <Form.Control
                    type="text"
                    placeholder="Buscar cliente por nombre o NIT..."
                    value={terceroSearch}
                    onChange={(e) => {
                      setTerceroSearch(e.target.value);
                      setShowTerceroDropdown(true);
                      setTerceroDropUp(computeDropUp(terceroRef, agregarItemCardRef, 270));
                      if (!e.target.value) {
                        setFormData((prev) => ({ ...prev, tipo_id_tercero: '', tercero_id: '' }));
                      }
                    }}
                    onFocus={() => {
                      setShowTerceroDropdown(true);
                      setTerceroDropUp(computeDropUp(terceroRef, agregarItemCardRef, 270));
                    }}
                  />
                </InputGroup>
                {showTerceroDropdown && filteredTerceros.length > 0 && (
                  <div
                    className="position-absolute w-100 bg-white border rounded shadow"
                    style={{
                      zIndex: 1050,
                      maxHeight: 250,
                      overflowY: 'auto',
                      ...(terceroDropUp
                        ? { bottom: '100%', marginBottom: 4 }
                        : { top: '100%', marginTop: 4 }),
                    }}
                  >
                    {filteredTerceros.map((t) => (
                      <div
                        key={`${t.tipo_id_tercero}-${t.tercero_id}`}
                        className="px-3 py-2 cursor-pointer"
                        style={{ cursor: 'pointer' }}
                        onMouseDown={() => handleSelectTercero(t)}
                        onMouseEnter={(e) => (e.currentTarget.style.background = '#f0f0f0')}
                        onMouseLeave={(e) => (e.currentTarget.style.background = 'white')}
                      >
                        <strong>{t.nombre_tercero || t.tercero_id}</strong>
                        <span className="text-muted ms-2 small">({t.tercero_id})</span>
                      </div>
                    ))}
                  </div>
                )}
              </div>
              {formData.tercero_id && (
                <Form.Text className="text-success">
                  <i className="fas fa-check-circle me-1"></i>
                  {formData.tipo_id_tercero} — {formData.tercero_id}
                </Form.Text>
              )}
            </Col>

            {/* FECHA EMISIÓN */}
            <Col md={3}>
              <Form.Label className="fw-bold text-uppercase small">Fecha Emisión</Form.Label>
              <Form.Control
                type="date"
                name="fecha_emision"
                value={formData.fecha_emision}
                onChange={handleChange}
                required
              />
            </Col>

            {/* FECHA VENCIMIENTO */}
            <Col md={3}>
              <Form.Label className="fw-bold text-uppercase small">Fecha Vencimiento</Form.Label>
              <Form.Control
                type="date"
                name="fecha_vencimiento"
                value={formData.fecha_vencimiento}
                onChange={handleChange}
              />
            </Col>

            {/* MÉTODO DE PAGO */}
            <Col md={4}>
              <Form.Label className="fw-bold text-uppercase small">Método de Pago</Form.Label>
              <Form.Select name="metodo_pago" value={formData.metodo_pago} onChange={handleChange}>
                {METODOS_PAGO.map((m) => <option key={m}>{m}</option>)}
              </Form.Select>
            </Col>

            {/* TIPO DE PAGO */}
            <Col md={4}>
              <Form.Label className="fw-bold text-uppercase small">Tipo de Pago</Form.Label>
              <Form.Select name="tipo_pago" value={formData.tipo_pago} onChange={handleChange}>
                {TIPOS_PAGO.map((t) => <option key={t}>{t}</option>)}
              </Form.Select>
            </Col>

            {/* ORDEN DE COMPRA */}
            <Col md={4}>
              <Form.Label className="fw-bold text-uppercase small">Orden de Compra</Form.Label>
              <Form.Control
                type="text"
                name="orden_compra"
                placeholder="N° de orden de compra"
                value={formData.orden_compra}
                onChange={handleChange}
              />
            </Col>
          </Row>
        </Card.Body>
      </Card>

      {/* ── Nuevo ítem ── */}
      <Card ref={agregarItemCardRef} className="mb-3 shadow-sm">
        <Card.Header className="fw-bold">
          <i className="fas fa-plus-circle me-2 text-primary"></i>Agregar Ítem
        </Card.Header>
        <Card.Body>
          <Row className="g-2 align-items-end">
            {/* Buscador de servicio */}
            <Col md={4}>
              <Form.Label className="small fw-bold text-uppercase">Servicio / Descripción</Form.Label>
              <div ref={servicioRef} className="position-relative">
                <Form.Control
                  type="text"
                  placeholder="Buscar servicio o escribir descripción..."
                  value={servicioSearch}
                  onChange={(e) => {
                    setServicioSearch(e.target.value);
                    setShowServicioDropdown(true);
                    setServicioDropUp(computeDropUp(servicioRef, notasRowRef, 220));
                    handleNuevoItemChange('descripcion', e.target.value);
                  }}
                  onFocus={() => {
                    setShowServicioDropdown(true);
                    setServicioDropUp(computeDropUp(servicioRef, notasRowRef, 220));
                  }}
                />
                {showServicioDropdown && filteredServicios.length > 0 && (
                  <div
                    className="position-absolute w-100 bg-white border rounded shadow"
                    style={{
                      zIndex: 1050,
                      maxHeight: 200,
                      overflowY: 'auto',
                      ...(servicioDropUp
                        ? { bottom: '100%', marginBottom: 4 }
                        : { top: '100%', marginTop: 4 }),
                    }}
                  >
                    {filteredServicios.map((s) => (
                      <div
                        key={s.servicio_id}
                        className="px-3 py-2"
                        style={{ cursor: 'pointer' }}
                        onMouseDown={() => handleSelectServicio(s)}
                        onMouseEnter={(e) => (e.currentTarget.style.background = '#f0f0f0')}
                        onMouseLeave={(e) => (e.currentTarget.style.background = 'white')}
                      >
                        <strong>{s.nombre_servicio}</strong>
                        <span className="text-muted ms-2 small">{formatCurrency(s.precio_unitario)}</span>
                      </div>
                    ))}
                  </div>
                )}
              </div>
            </Col>

            {/* REF */}
            <Col md={1}>
              <Form.Label className="small fw-bold text-uppercase">REF</Form.Label>
              <Form.Control
                type="text"
                placeholder="REF"
                value={nuevoItem.referencia}
                onChange={(e) => handleNuevoItemChange('referencia', e.target.value)}
              />
            </Col>

            {/* CANTIDAD */}
            <Col md={1}>
              <Form.Label className="small fw-bold text-uppercase">Cantidad</Form.Label>
              <Form.Control
                type="number"
                min="0.01"
                step="0.01"
                value={nuevoItem.cantidad}
                onChange={(e) => handleNuevoItemChange('cantidad', e.target.value)}
              />
            </Col>

            {/* PRECIO */}
            <Col md={2}>
              <Form.Label className="small fw-bold text-uppercase">Precio</Form.Label>
              <Form.Control
                type="number"
                min="0"
                step="0.01"
                placeholder="0.00"
                value={nuevoItem.precio_unitario}
                onChange={(e) => handleNuevoItemChange('precio_unitario', e.target.value)}
              />
            </Col>

            {/* DESCUENTO */}
            <Col md={1}>
              <Form.Label className="small fw-bold text-uppercase">Desc %</Form.Label>
              <Form.Control
                type="number"
                min="0"
                max="100"
                step="0.01"
                value={nuevoItem.porcentaje_descuento}
                onChange={(e) => handleNuevoItemChange('porcentaje_descuento', e.target.value)}
              />
            </Col>

            {/* RET. FUENTE */}
            <Col md={1}>
              <Form.Label className="small fw-bold text-uppercase">Ret.F %</Form.Label>
              <Form.Select
                value={nuevoItem.porcentaje_ret_fuente}
                onChange={(e) => handleNuevoItemChange('porcentaje_ret_fuente', e.target.value)}
              >
                <option value="">—</option>
                {retenciones.map((r) => (
                  <option key={r.retencion_id} value={r.porcentaje}>
                    {r.porcentaje}%
                  </option>
                ))}
              </Form.Select>
            </Col>

            {/* IMPUESTO */}
            <Col md={1}>
              <Form.Label className="small fw-bold text-uppercase">Impuesto</Form.Label>
              <Form.Select
                value={nuevoItem.impuesto_id}
                onChange={(e) => handleNuevoItemChange('impuesto_id', e.target.value)}
              >
                <option value="">Excluido</option>
                {impuestos.map((i) => (
                  <option key={i.impuesto_id} value={i.impuesto_id}>
                    {i.nombre} {i.porcentaje}%
                  </option>
                ))}
              </Form.Select>
            </Col>

            {/* BOTÓN AGREGAR */}
            <Col md={1}>
              <Button variant="primary" className="w-100" onClick={handleAgregarItem}>
                <i className="fas fa-plus"></i>
              </Button>
            </Col>
          </Row>


        </Card.Body>
      </Card>

      {/* ── Tabla de ítems ── */}
      {items.length > 0 && (
        <Card className="mb-3 shadow-sm">
          <Card.Header className="fw-bold">
            <i className="fas fa-list me-2 text-success"></i>Ítems de la Cotización
          </Card.Header>
          <Card.Body className="p-0">
            <div className="table-responsive">
              <Table size="sm" className="align-middle mb-0">
                <thead className="table-light">
                  <tr>
                    <th>#</th>
                    <th>REF</th>
                    <th>Descripción</th>
                    <th className="text-end">Cantidad</th>
                    <th className="text-end">Precio</th>
                    <th className="text-end">Desc %</th>
                    <th className="text-end">Ret.F %</th>
                    <th>Impuesto</th>
                    <th className="text-end">Subtotal</th>
                    <th className="text-end">Total</th>
                    <th></th>
                  </tr>
                </thead>
                <tbody>
                  {items.map((item, index) => {
                    const impObj = impuestos.find((i) => String(i.impuesto_id) === String(item.impuesto_id));
                    return (
                      <tr key={index}>
                        <td>{index + 1}</td>
                        <td><small className="text-muted">{item.referencia || '—'}</small></td>
                        <td>{item.descripcion}</td>
                        <td className="text-end">{parseFloat(item.cantidad || 0).toLocaleString('es-CO')}</td>
                        <td className="text-end">{formatCurrency(item.precio_unitario)}</td>
                        <td className="text-end">{item.porcentaje_descuento || 0}%</td>
                        <td className="text-end">{item.porcentaje_ret_fuente || '—'}</td>
                        <td>
                          {impObj
                            ? <Badge bg="info">{impObj.nombre} {impObj.porcentaje}%</Badge>
                            : <span className="text-muted small">Excluido</span>
                          }
                        </td>
                        <td className="text-end">{formatCurrency(calcularItemSubtotal(item))}</td>
                        <td className="text-end fw-bold">{formatCurrency(calcularItemTotal(item))}</td>
                        <td>
                          <Button
                            variant="outline-danger"
                            size="sm"
                            onClick={() => handleEliminarItem(index)}
                          >
                            <i className="fas fa-trash"></i>
                          </Button>
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

      {/* ── Totales ── */}
      <Row ref={notasRowRef}>
        <Col md={6}>
          {/* NOTAS */}
          <Card className="mb-3 shadow-sm">
            <Card.Header className="fw-bold">
              <i className="fas fa-sticky-note me-2 text-warning"></i>Notas
            </Card.Header>
            <Card.Body>
              <Form.Control
                as="textarea"
                rows={3}
                name="notas"
                placeholder="Observaciones, condiciones, etc."
                value={formData.notas}
                onChange={handleChange}
              />
            </Card.Body>
          </Card>
        </Col>

        <Col md={6}>
          <Card className="mb-3 shadow-sm">
            <Card.Body>
              <div className="d-flex justify-content-between mb-1">
                <span className="text-muted">Subtotal</span>
                <span>{formatCurrency(subtotal)}</span>
              </div>
              {descuento > 0 && (
                <div className="d-flex justify-content-between mb-1 text-danger">
                  <span>— Descuento</span>
                  <span>- {formatCurrency(descuento)}</span>
                </div>
              )}
              {impuestoT > 0 && (
                <div className="d-flex justify-content-between mb-1 text-info">
                  <span>+ Impuestos</span>
                  <span>{formatCurrency(impuestoT)}</span>
                </div>
              )}
              {retencionT > 0 && (
                <div className="d-flex justify-content-between mb-1 text-warning">
                  <span>— Retención en fuente</span>
                  <span>- {formatCurrency(retencionT)}</span>
                </div>
              )}
              <hr />
              <div className="d-flex justify-content-between fw-bold fs-5">
                <span>Total</span>
                <span className="text-primary">{formatCurrency(total)}</span>
              </div>
            </Card.Body>
          </Card>
        </Col>
      </Row>

      {/* ── Botones ── */}
      <div className="d-flex justify-content-end gap-2 mb-4">
        <Button variant="outline-secondary" onClick={onCancel} disabled={loading}>
          <i className="fas fa-times me-1"></i>Cancelar
        </Button>
        <Button type="submit" variant="primary" disabled={loading}>
          {loading
            ? <><span className="spinner-border spinner-border-sm me-1" />Guardando...</>
            : <><i className="fas fa-save me-1"></i>{isEditMode ? 'Actualizar' : 'Guardar'} Cotización</>
          }
        </Button>
      </div>

      {/* Modal eliminar ítem */}
      <Modal show={showDeleteModal} onHide={() => setShowDeleteModal(false)} centered>
        <Modal.Header closeButton>
          <Modal.Title>Eliminar ítem</Modal.Title>
        </Modal.Header>
        <Modal.Body>¿Está seguro de eliminar este ítem de la cotización?</Modal.Body>
        <Modal.Footer>
          <Button variant="secondary" onClick={() => setShowDeleteModal(false)}>Cancelar</Button>
          <Button variant="danger" onClick={confirmEliminar}>Eliminar</Button>
        </Modal.Footer>
      </Modal>
    </Form>
  );
};

export default CotizacionFormView;
