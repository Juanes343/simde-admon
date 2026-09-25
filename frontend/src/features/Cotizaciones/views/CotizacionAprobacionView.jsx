import React, { useState, useRef, useEffect } from 'react';
import { Container, Row, Col, Card, Table, Button, Form, Modal, Spinner } from 'react-bootstrap';
import SignatureCanvas from 'react-signature-canvas';
import { toast } from 'react-toastify';

const ALTO_LIENZO = 200;

const formatCurrency = (v) =>
  new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', minimumFractionDigits: 0, maximumFractionDigits: 2 }).format(v ?? 0);

const formatDate = (d) => {
  if (!d) return '—';
  const [year, month, day] = String(d).split('T')[0].split('-');
  return `${day}/${month}/${year}`;
};

/**
 * Vista pública (sin sesión) para que el cliente revise un documento (cotización u orden de servicio),
 * lo apruebe/firme con su nombre, documento y firma, y —en cotizaciones— lo rechace.
 * Recibe los datos ya cargados; las acciones las resuelve el contenedor.
 *
 * Props opcionales para reutilizarla con órdenes de servicio:
 *  - tipoDocumento: 'Cotización' (por defecto) | 'Orden de servicio'
 *  - textoAprobar: texto del botón principal
 *  - textoAceptacion: aviso legal bajo la firma (nodo React)
 *  - permitirRechazo: muestra el botón "Rechazar" (por defecto true)
 */
const CotizacionAprobacionView = ({
  data,
  enviando,
  onAprobar,
  onRechazar,
  tipoDocumento = 'Cotización',
  textoAprobar = 'Aprobar y firmar',
  textoAceptacion = null,
  permitirRechazo = true,
}) => {
  const [nombre, setNombre] = useState('');
  const [documento, setDocumento] = useState('');
  const [telefono, setTelefono] = useState('');
  const [showRechazo, setShowRechazo] = useState(false);
  const [motivo, setMotivo] = useState('');

  // El lienzo se dimensiona al ancho real del contenedor para que el trazo coincida con el cursor (móvil incluido)
  const sigCanvas = useRef(null);
  const wrapperRef = useRef(null);
  const [anchoLienzo, setAnchoLienzo] = useState(500);

  useEffect(() => {
    if (wrapperRef.current) {
      setAnchoLienzo(Math.max(wrapperRef.current.clientWidth, 200));
    }
  }, []);

  const limpiarFirma = () => sigCanvas.current?.clear();

  // Datos del encabezado: una orden de servicio trae su vigencia; una cotización, su fecha de emisión
  const esOrden = data.tipo === 'orden';
  const campos = [
    ...(esOrden
      ? [
          { label: 'Fecha Inicio', value: data.fecha_inicio ? formatDate(data.fecha_inicio) : 'Por definir' },
          { label: 'Fecha Fin', value: data.fecha_fin ? formatDate(data.fecha_fin) : 'Por definir' },
          { label: 'Período de Facturación', value: `Cada ${data.periodo_dias} días` },
          { label: 'Prórroga Automática', value: data.prorroga ? 'Sí' : 'No' },
        ]
      : [{ label: 'Fecha Emisión', value: formatDate(data.fecha_emision) }]),
    ...(esOrden && !data.orden_compra ? [] : [{ label: 'Orden de Compra', value: data.orden_compra || '—' }]),
    ...(esOrden && !data.metodo_pago ? [] : [{ label: 'Método de Pago', value: data.metodo_pago || '—' }]),
    ...(esOrden && !data.tipo_pago ? [] : [{ label: 'Tipo de Pago', value: data.tipo_pago || '—' }]),
  ];

  const handleAprobar = () => {
    if (!nombre.trim()) { toast.warning('Ingrese su nombre completo.'); return; }
    if (!documento.trim()) { toast.warning('Ingrese su número de documento.'); return; }
    if (!/^[0-9+()\-\s]{7,30}$/.test(telefono.trim())) {
      toast.warning('Ingrese un celular o teléfono válido.');
      return;
    }
    if (!sigCanvas.current || sigCanvas.current.isEmpty()) {
      toast.warning('Por favor firme en el recuadro antes de aprobar.');
      return;
    }

    onAprobar({
      nombre: nombre.trim(),
      documento: documento.trim(),
      telefono: telefono.trim(),
      firma: sigCanvas.current.getTrimmedCanvas().toDataURL('image/png'),
    });
  };

  const handleRechazar = () => {
    onRechazar(motivo.trim());
    setShowRechazo(false);
  };

  return (
    <Container className="py-4">
      <Row className="justify-content-center">
        <Col lg={10} xl={9}>
          {/* ── Cabecera ── */}
          <div className="text-center mb-3">
            <h4 className="mb-0 fw-bold">SIMDE SAS</h4>
            <small className="text-muted">Soporte Implementación y Desarrollo</small>
          </div>

          {/* ── Datos de la cotización ── */}
          <Card className="mb-3 shadow-sm">
            <Card.Header className="d-flex flex-wrap justify-content-between align-items-center gap-2">
              <h5 className="mb-0">
                <i className="fas fa-file-alt me-2 text-primary"></i>
                {tipoDocumento} {data.numero_documento || data.numero_cotizacion}
              </h5>
              {data.fecha_vencimiento && (
                <span className="text-muted small">Válida hasta {formatDate(data.fecha_vencimiento)}</span>
              )}
            </Card.Header>
            <Card.Body>
              <Row>
                <Col md={6}>
                  <p className="mb-1 text-muted small text-uppercase fw-bold">Cliente</p>
                  <p className="mb-3 fs-5">{data.cliente}</p>
                </Col>
                {campos.map((campo) => (
                  <Col md={3} xs={6} key={campo.label}>
                    <p className="mb-1 text-muted small text-uppercase fw-bold">{campo.label}</p>
                    <p className="mb-3">{campo.value}</p>
                  </Col>
                ))}
              </Row>
            </Card.Body>
          </Card>

          {/* ── Ítems ── */}
          <Card className="mb-3 shadow-sm">
            <Card.Header className="fw-bold">
              <i className="fas fa-list me-2 text-success"></i>Detalle de Servicios / Productos
            </Card.Header>
            <Card.Body className="p-0">
              <div className="table-responsive">
                <Table size="sm" className="align-middle mb-0">
                  <thead className="table-light">
                    <tr>
                      <th>#</th>
                      <th>Descripción</th>
                      <th className="text-end">Cant.</th>
                      <th className="text-end">Precio</th>
                      <th className="text-end">Desc %</th>
                      <th>Impuesto</th>
                      <th className="text-end">Total</th>
                    </tr>
                  </thead>
                  <tbody>
                    {data.items.map((item, i) => (
                      <tr key={i}>
                        <td>{i + 1}</td>
                        <td>
                          {item.descripcion}
                          {item.observaciones && <div className="text-muted small">{item.observaciones}</div>}
                        </td>
                        <td className="text-end">{item.cantidad} {item.tipo_unidad || ''}</td>
                        <td className="text-end">{formatCurrency(item.precio_unitario)}</td>
                        <td className="text-end">{item.porcentaje_descuento ? `${item.porcentaje_descuento}%` : '—'}</td>
                        <td>
                          {item.impuesto_porcentaje !== null ? `IVA ${item.impuesto_porcentaje}%` : 'Excluido'}
                          {item.porcentaje_ret_fuente > 0 && (
                            <div className="text-danger small">Ret. {item.porcentaje_ret_fuente}%</div>
                          )}
                        </td>
                        <td className="text-end fw-bold">{formatCurrency(item.total)}</td>
                      </tr>
                    ))}
                  </tbody>
                </Table>
              </div>
            </Card.Body>
            <Card.Footer>
              <div className="ms-auto" style={{ maxWidth: 320 }}>
                <div className="d-flex justify-content-between"><span className="text-muted">Subtotal</span><span>{formatCurrency(data.subtotal)}</span></div>
                {data.descuento_total > 0 && (
                  <div className="d-flex justify-content-between text-danger"><span>Descuento</span><span>- {formatCurrency(data.descuento_total)}</span></div>
                )}
                {data.impuestos_total > 0 && (
                  <div className="d-flex justify-content-between text-success"><span>Impuestos</span><span>{formatCurrency(data.impuestos_total)}</span></div>
                )}
                {data.retencion_total > 0 && (
                  <div className="d-flex justify-content-between text-warning"><span>Retención en fuente</span><span>- {formatCurrency(data.retencion_total)}</span></div>
                )}
                <div className="d-flex justify-content-between fs-5 fw-bold border-top mt-1 pt-1">
                  <span>TOTAL</span><span>{formatCurrency(data.total)}</span>
                </div>
              </div>
            </Card.Footer>
          </Card>

          {data.notas && (
            <Card className="mb-3 shadow-sm">
              <Card.Body>
                <p className="mb-1 text-muted small text-uppercase fw-bold">Notas y Observaciones</p>
                <p className="mb-0" style={{ whiteSpace: 'pre-line' }}>{data.notas}</p>
              </Card.Body>
            </Card>
          )}

          {/* ── Aprobación y firma ── */}
          <Card className="mb-4 shadow border-success">
            <Card.Header className="bg-success text-white">
              <i className="fas fa-file-signature me-2"></i>{textoAprobar}
            </Card.Header>
            <Card.Body>
              <Row className="g-3 mb-3">
                <Col md={6}>
                  <Form.Label className="fw-bold">Nombre completo <span className="text-danger">*</span></Form.Label>
                  <Form.Control
                    type="text"
                    maxLength={150}
                    value={nombre}
                    onChange={(e) => setNombre(e.target.value)}
                    disabled={enviando}
                  />
                </Col>
                <Col md={3}>
                  <Form.Label className="fw-bold">Documento de identidad <span className="text-danger">*</span></Form.Label>
                  <Form.Control
                    type="text"
                    maxLength={50}
                    value={documento}
                    onChange={(e) => setDocumento(e.target.value)}
                    disabled={enviando}
                  />
                </Col>
                <Col md={3}>
                  <Form.Label className="fw-bold">Celular o teléfono <span className="text-danger">*</span></Form.Label>
                  <Form.Control
                    type="tel"
                    inputMode="tel"
                    maxLength={30}
                    placeholder="Ej: 3001234567"
                    value={telefono}
                    onChange={(e) => setTelefono(e.target.value)}
                    disabled={enviando}
                  />
                </Col>
              </Row>

              <Form.Label className="fw-bold">Firma <span className="text-danger">*</span></Form.Label>
              <div
                ref={wrapperRef}
                style={{ border: '2px dashed #ccc', borderRadius: 5, backgroundColor: '#f9f9f9', height: ALTO_LIENZO, overflow: 'hidden' }}
              >
                <SignatureCanvas
                  ref={sigCanvas}
                  penColor="black"
                  canvasProps={{ width: anchoLienzo, height: ALTO_LIENZO, className: 'sigCanvas' }}
                />
              </div>
              <div className="form-text text-muted">Utilice su mouse o dedo (en móvil) para firmar.</div>

              <p className="text-muted small mt-3 mb-0">
                {textoAceptacion || (
                  <>
                    Al pulsar <strong>{textoAprobar}</strong> usted acepta las condiciones de esta cotización y se generará
                    la orden de servicio correspondiente, cuya copia recibirá por correo.
                  </>
                )}
              </p>
            </Card.Body>
            <Card.Footer className="d-flex flex-wrap justify-content-end gap-2">
              <Button variant="outline-secondary" onClick={limpiarFirma} disabled={enviando}>Borrar firma</Button>
              {permitirRechazo && (
                <Button variant="outline-danger" onClick={() => setShowRechazo(true)} disabled={enviando}>
                  <i className="fas fa-times me-1"></i>Rechazar
                </Button>
              )}
              <Button variant="success" onClick={handleAprobar} disabled={enviando}>
                {enviando
                  ? <><Spinner as="span" animation="border" size="sm" className="me-1" />Procesando...</>
                  : <><i className="fas fa-check me-1"></i>{textoAprobar}</>
                }
              </Button>
            </Card.Footer>
          </Card>
        </Col>
      </Row>

      {/* ── Modal rechazo ── */}
      <Modal show={showRechazo} onHide={() => setShowRechazo(false)} centered>
        <Modal.Header closeButton>
          <Modal.Title>Rechazar cotización</Modal.Title>
        </Modal.Header>
        <Modal.Body>
          <Form.Label className="fw-bold">Motivo <span className="text-muted fw-normal">(opcional)</span></Form.Label>
          <Form.Control
            as="textarea"
            rows={3}
            maxLength={1000}
            value={motivo}
            onChange={(e) => setMotivo(e.target.value)}
            placeholder="Cuéntenos por qué no aprueba la cotización"
          />
        </Modal.Body>
        <Modal.Footer>
          <Button variant="secondary" onClick={() => setShowRechazo(false)}>Volver</Button>
          <Button variant="danger" onClick={handleRechazar}>Confirmar rechazo</Button>
        </Modal.Footer>
      </Modal>
    </Container>
  );
};

export default CotizacionAprobacionView;
