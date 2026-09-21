import React, { useState, useEffect, useRef } from 'react';
import { Modal, Form, Button, ListGroup, Row, Col } from 'react-bootstrap';
import { toast } from 'react-toastify';

export const MAX_ADJUNTOS = 5;
export const MAX_MB_ADJUNTO = 10;
const EXTENSIONES = '.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.csv,.txt,.jpg,.jpeg,.png,.zip';

const DATOS_ORDEN_VACIOS = {
  fecha_inicio: '',
  fecha_fin: '',
  periodo_facturacion_dias: '30',
  sw_prorroga_automatica: '0',
  porcentaje_soltec: '0',
  porcentaje_ret_fuente: '0',
};

const formatearTamano = (bytes) =>
  bytes >= 1024 * 1024
    ? `${(bytes / (1024 * 1024)).toFixed(1)} MB`
    : `${Math.max(1, Math.round(bytes / 1024))} KB`;

/**
 * Modal para enviar una cotización por correo (PDF + documentos adicionales opcionales).
 * Cuando `conAprobacion` es true, el correo lleva un botón para que el cliente apruebe y firme,
 * y aquí se piden los datos de la orden de servicio que se creará al aprobar.
 * Maneja su propio estado de formulario; el envío lo resuelve el padre en `onConfirm(email, archivos, datosOrden)`.
 */
const EnviarEmailModal = ({
  show,
  onHide,
  numeroCotizacion,
  emailInicial,
  conAprobacion = false,
  datosOrdenInicial = null,
  loading,
  onConfirm,
}) => {
  const [email, setEmail] = useState('');
  const [archivos, setArchivos] = useState([]);
  const [datosOrden, setDatosOrden] = useState(DATOS_ORDEN_VACIOS);
  const inputRef = useRef(null);

  // Cada vez que se abre, se reinicia el formulario
  useEffect(() => {
    if (show) {
      setEmail(emailInicial || '');
      setArchivos([]);
      setDatosOrden({ ...DATOS_ORDEN_VACIOS, ...(datosOrdenInicial || {}) });
    }
  }, [show, emailInicial, datosOrdenInicial]);

  const handleDatosOrden = (campo) => (e) =>
    setDatosOrden((prev) => ({ ...prev, [campo]: e.target.value }));

  const handleSeleccionar = (e) => {
    const nuevos = Array.from(e.target.files || []);
    e.target.value = ''; // permite volver a elegir el mismo archivo

    const validos = [];
    for (const archivo of nuevos) {
      if (archivo.size > MAX_MB_ADJUNTO * 1024 * 1024) {
        toast.error(`"${archivo.name}" supera los ${MAX_MB_ADJUNTO} MB`);
        continue;
      }
      validos.push(archivo);
    }

    const combinados = [...archivos, ...validos];
    if (combinados.length > MAX_ADJUNTOS) {
      toast.error(`Puede adjuntar máximo ${MAX_ADJUNTOS} documentos`);
    }
    setArchivos(combinados.slice(0, MAX_ADJUNTOS));
  };

  const quitarArchivo = (index) => setArchivos((prev) => prev.filter((_, i) => i !== index));

  const handleEnviar = () => {
    if (!email) { toast.error('Ingrese un correo destinatario'); return; }

    if (conAprobacion) {
      if (!datosOrden.fecha_inicio || !datosOrden.fecha_fin) {
        toast.error('Indique la fecha de inicio y fin de la orden de servicio');
        return;
      }
      if (datosOrden.fecha_fin < datosOrden.fecha_inicio) {
        toast.error('La fecha de fin de la orden no puede ser anterior a la de inicio');
        return;
      }
    }

    onConfirm(email, archivos, conAprobacion ? datosOrden : null);
  };

  return (
    <Modal show={show} onHide={onHide} centered size={conAprobacion ? 'lg' : undefined}>
      <Modal.Header closeButton>
        <Modal.Title>
          <i className="fas fa-envelope me-2 text-dark"></i>Enviar Cotización por Correo
        </Modal.Title>
      </Modal.Header>
      <Modal.Body>
        <p className="text-muted mb-3">
          Se enviará la cotización <strong>{numeroCotizacion}</strong> con el PDF adjunto.
        </p>

        <Form.Group className="mb-3">
          <Form.Label className="fw-bold">Correo destinatario <span className="text-danger">*</span></Form.Label>
          <Form.Control
            type="email"
            placeholder="correo@ejemplo.com"
            value={email}
            onChange={(e) => setEmail(e.target.value)}
            autoFocus
          />
        </Form.Group>

        {conAprobacion && (
          <div className="border rounded p-3 mb-3 bg-light">
            <p className="fw-bold mb-1">
              <i className="fas fa-file-signature me-2 text-success"></i>Aprobación en línea
            </p>
            <p className="text-muted small mb-3">
              El correo incluirá un botón para que el cliente apruebe y firme la cotización. Al aprobar,
              se crea automáticamente la orden de servicio con estos datos.
            </p>
            <Row className="g-3">
              <Col md={6}>
                <Form.Label className="fw-bold">Fecha Inicio <span className="text-danger">*</span></Form.Label>
                <Form.Control type="date" value={datosOrden.fecha_inicio} onChange={handleDatosOrden('fecha_inicio')} />
              </Col>
              <Col md={6}>
                <Form.Label className="fw-bold">Fecha Fin <span className="text-danger">*</span></Form.Label>
                <Form.Control type="date" value={datosOrden.fecha_fin} onChange={handleDatosOrden('fecha_fin')} />
              </Col>
              <Col md={4}>
                <Form.Label className="fw-bold">Período Facturación (días)</Form.Label>
                <Form.Control
                  type="number"
                  min="1"
                  value={datosOrden.periodo_facturacion_dias}
                  onChange={handleDatosOrden('periodo_facturacion_dias')}
                />
              </Col>
              <Col md={4}>
                <Form.Label className="fw-bold">% Soltec</Form.Label>
                <Form.Control
                  type="number"
                  min="0"
                  max="100"
                  step="0.01"
                  value={datosOrden.porcentaje_soltec}
                  onChange={handleDatosOrden('porcentaje_soltec')}
                />
              </Col>
              <Col md={4}>
                <Form.Label className="fw-bold">% Ret. Fuente</Form.Label>
                <Form.Control
                  type="number"
                  min="0"
                  max="100"
                  step="0.01"
                  value={datosOrden.porcentaje_ret_fuente}
                  onChange={handleDatosOrden('porcentaje_ret_fuente')}
                />
              </Col>
              <Col md={4}>
                <Form.Label className="fw-bold">Prórroga Automática</Form.Label>
                <Form.Select value={datosOrden.sw_prorroga_automatica} onChange={handleDatosOrden('sw_prorroga_automatica')}>
                  <option value="0">No</option>
                  <option value="1">Sí</option>
                </Form.Select>
              </Col>
            </Row>
          </div>
        )}

        <Form.Group>
          <Form.Label className="fw-bold">Documentos adicionales <span className="text-muted fw-normal">(opcional)</span></Form.Label>
          <div>
            <input
              ref={inputRef}
              type="file"
              multiple
              accept={EXTENSIONES}
              onChange={handleSeleccionar}
              style={{ display: 'none' }}
            />
            <Button
              variant="outline-secondary"
              size="sm"
              onClick={() => inputRef.current?.click()}
              disabled={loading || archivos.length >= MAX_ADJUNTOS}
            >
              <i className="fas fa-paperclip me-1"></i>Adjuntar documento
            </Button>
            <Form.Text className="text-muted ms-2">
              Máx. {MAX_ADJUNTOS} archivos de {MAX_MB_ADJUNTO} MB
            </Form.Text>
          </div>

          {archivos.length > 0 && (
            <ListGroup className="mt-2">
              {archivos.map((archivo, i) => (
                <ListGroup.Item
                  key={`${archivo.name}-${i}`}
                  className="d-flex justify-content-between align-items-center py-1"
                >
                  <span className="text-truncate me-2">
                    <i className="fas fa-file me-2 text-muted"></i>
                    {archivo.name} <small className="text-muted">({formatearTamano(archivo.size)})</small>
                  </span>
                  <Button
                    variant="link"
                    size="sm"
                    className="text-danger p-0"
                    title="Quitar"
                    onClick={() => quitarArchivo(i)}
                    disabled={loading}
                  >
                    <i className="fas fa-times"></i>
                  </Button>
                </ListGroup.Item>
              ))}
            </ListGroup>
          )}
        </Form.Group>
      </Modal.Body>
      <Modal.Footer>
        <Button variant="secondary" onClick={onHide}>Cancelar</Button>
        <Button variant="dark" onClick={handleEnviar} disabled={loading}>
          {loading
            ? <><span className="spinner-border spinner-border-sm me-1" />Enviando...</>
            : <><i className="fas fa-paper-plane me-1"></i>Enviar</>
          }
        </Button>
      </Modal.Footer>
    </Modal>
  );
};

export default EnviarEmailModal;
