import React, { useState, useEffect } from 'react';
import { Modal, Form, Button } from 'react-bootstrap';
import { toast } from 'react-toastify';

/**
 * Modal para enviar por correo el PDF de una orden de servicio (incluye la firma del cliente si ya la firmó).
 * Maneja su propio formulario; el envío lo resuelve el padre en `onConfirm(email)`.
 */
const EnviarOrdenEmailModal = ({ show, onHide, numeroOrden, emailInicial, loading, onConfirm }) => {
  const [email, setEmail] = useState('');

  // Cada vez que se abre, se precarga el correo del tercero
  useEffect(() => {
    if (show) setEmail(emailInicial || '');
  }, [show, emailInicial]);

  const handleEnviar = () => {
    if (!email) { toast.error('Ingrese un correo destinatario'); return; }
    onConfirm(email);
  };

  return (
    <Modal show={show} onHide={onHide} centered>
      <Modal.Header closeButton={!loading}>
        <Modal.Title>
          <i className="fas fa-envelope me-2 text-dark"></i>Enviar Orden de Servicio por Correo
        </Modal.Title>
      </Modal.Header>
      <Modal.Body>
        <p className="text-muted mb-3">
          Se enviará la orden <strong>{numeroOrden}</strong> con el PDF adjunto.
        </p>
        <Form.Group>
          <Form.Label className="fw-bold">Correo destinatario <span className="text-danger">*</span></Form.Label>
          <Form.Control
            type="email"
            placeholder="correo@ejemplo.com"
            value={email}
            onChange={(e) => setEmail(e.target.value)}
            autoFocus
          />
        </Form.Group>
      </Modal.Body>
      <Modal.Footer>
        <Button variant="secondary" onClick={onHide} disabled={loading}>Cancelar</Button>
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

export default EnviarOrdenEmailModal;
