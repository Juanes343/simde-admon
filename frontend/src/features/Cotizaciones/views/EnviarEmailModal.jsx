import React, { useState, useEffect, useRef } from 'react';
import { Modal, Form, Button, ListGroup } from 'react-bootstrap';
import { toast } from 'react-toastify';

export const MAX_ADJUNTOS = 5;
export const MAX_MB_ADJUNTO = 10;
const EXTENSIONES = '.pdf';
const TIPO_PDF = 'application/pdf';

const formatearTamano = (bytes) =>
  bytes >= 1024 * 1024
    ? `${(bytes / (1024 * 1024)).toFixed(1)} MB`
    : `${Math.max(1, Math.round(bytes / 1024))} KB`;

/**
 * Modal para enviar una cotización por correo (PDF + documentos adicionales opcionales).
 * Cuando `conAprobacion` es true, el correo lleva un botón para que el cliente apruebe y firme en línea.
 * Maneja su propio estado de formulario; el envío lo resuelve el padre en `onConfirm(email, archivos)`.
 */
const EnviarEmailModal = ({
  show,
  onHide,
  numeroCotizacion,
  emailInicial,
  conAprobacion = false,
  loading,
  onConfirm,
}) => {
  const [email, setEmail] = useState('');
  const [archivos, setArchivos] = useState([]);
  const inputRef = useRef(null);

  // Cada vez que se abre, se reinicia el formulario
  useEffect(() => {
    if (show) {
      setEmail(emailInicial || '');
      setArchivos([]);
    }
  }, [show, emailInicial]);

  const handleSeleccionar = (e) => {
    const nuevos = Array.from(e.target.files || []);
    e.target.value = ''; // permite volver a elegir el mismo archivo

    const validos = [];
    for (const archivo of nuevos) {
      const esPdf = archivo.type === TIPO_PDF || archivo.name.toLowerCase().endsWith('.pdf');
      if (!esPdf) {
        toast.error(`"${archivo.name}" no es un PDF. Solo se admiten documentos PDF.`);
        continue;
      }
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
    onConfirm(email, archivos);
  };

  return (
    <Modal show={show} onHide={onHide} centered>
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
            <p className="text-muted small mb-0">
              El correo incluirá un botón para que el cliente apruebe y firme la cotización. Al aprobar, se crea
              la orden de servicio; sus fechas y condiciones se completan después, al editarla.
            </p>
          </div>
        )}

        <Form.Group>
          <Form.Label className="fw-bold">Documentos adicionales <span className="text-muted fw-normal">(opcional, solo PDF)</span></Form.Label>
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
