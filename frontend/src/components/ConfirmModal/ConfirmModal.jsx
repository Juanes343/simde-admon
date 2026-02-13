import React from 'react';
import { Modal, Button } from 'react-bootstrap';
import './ConfirmModal.css';

const ConfirmModal = ({ 
  show, 
  onHide, 
  onConfirm, 
  title = 'Confirmar acción', 
  message = '¿Está seguro de realizar esta acción?',
  confirmText = 'Aceptar',
  cancelText = 'Cancelar',
  confirmVariant = 'primary',
  icon = 'fa-question-circle'
}) => {
  const handleConfirm = () => {
    onConfirm();
    onHide();
  };

  const getIconColor = () => {
    switch (confirmVariant) {
      case 'danger':
        return '#dc3545';
      case 'success':
        return '#28a745';
      case 'warning':
        return '#ffc107';
      default:
        return '#0096c7';
    }
  };

  return (
    <Modal 
      show={show} 
      onHide={onHide} 
      centered
      backdrop="static"
    >
      <Modal.Header closeButton className="border-0 pb-0">
        <Modal.Title className="w-100 text-center">
          <i className={`fas ${icon} fa-3x mb-3`} style={{ color: getIconColor() }}></i>
        </Modal.Title>
      </Modal.Header>
      <Modal.Body className="text-center px-4">
        <h5 className="mb-3">{title}</h5>
        <p className="text-muted mb-0">{message}</p>
      </Modal.Body>
      <Modal.Footer className="border-0 justify-content-center pb-4">
        <Button 
          variant="outline-secondary" 
          onClick={onHide}
          className="px-4"
        >
          <i className="fas fa-times me-2"></i>
          {cancelText}
        </Button>
        <Button 
          variant={confirmVariant} 
          onClick={handleConfirm}
          className="px-4"
        >
          <i className="fas fa-check me-2"></i>
          {confirmText}
        </Button>
      </Modal.Footer>
    </Modal>
  );
};

export default ConfirmModal;
