import React, { useState } from 'react';
import { Card, Form, Button, Alert } from 'react-bootstrap';

const TerceroUploadPdfView = ({ onSubmit, onCancel, loading }) => {
  const [selectedFile, setSelectedFile] = useState(null);
  const [preview, setPreview] = useState(null);

  const handleFileChange = (e) => {
    const file = e.target.files[0];
    if (file && file.type === 'application/pdf') {
      setSelectedFile(file);
      setPreview(file.name);
    } else {
      e.target.value = null;
      alert('Por favor seleccione un archivo PDF válido');
    }
  };

  const handleSubmit = (e) => {
    e.preventDefault();
    if (selectedFile) {
      const formData = new FormData();
      formData.append('pdf', selectedFile);
      onSubmit(formData);
    }
  };

  return (
    <>
      <Card>
        <Card.Body>
          <Alert variant="info">
            <strong>Instrucciones:</strong>
            <ul className="mb-0 mt-2">
              <li>Seleccione un archivo PDF del RUT del tercero</li>
              <li>El sistema intentará extraer automáticamente la información</li>
              <li>
                Asegúrese de que el PDF contenga información clara del NIT, Razón
                Social y Dirección
              </li>
            </ul>
          </Alert>

          <Form onSubmit={handleSubmit}>
            <Form.Group className="mb-3">
              <Form.Label>
                <strong>Seleccionar archivo PDF *</strong>
              </Form.Label>
              <Form.Control
                type="file"
                accept=".pdf"
                onChange={handleFileChange}
                required
              />
              {preview && (
                <Form.Text className="text-muted">
                  Archivo seleccionado: <strong>{preview}</strong>
                </Form.Text>
              )}
            </Form.Group>

            <div className="d-flex justify-content-end">
              <Button variant="secondary" className="me-2" onClick={onCancel}>
                Cancelar
              </Button>
              <Button variant="warning" type="submit" disabled={loading}>
                {loading ? (
                  <>
                    <span
                      className="spinner-border spinner-border-sm me-2"
                      role="status"
                    ></span>
                    Procesando...
                  </>
                ) : (
                  <>
                    <i className="fas fa-upload me-2"></i>
                    Cargar y Crear Tercero
                  </>
                )}
              </Button>
            </div>
          </Form>
        </Card.Body>
      </Card>

      <Card className="mt-4">
        <Card.Header>
          <strong>Información Adicional</strong>
        </Card.Header>
        <Card.Body>
          <h6>Formato del RUT requerido:</h6>
          <p className="mb-2">
            El PDF debe contener al menos la siguiente información:
          </p>
          <ul>
            <li>
              <strong>NIT:</strong> Número de Identificación Tributaria
            </li>
            <li>
              <strong>Razón Social:</strong> Nombre completo o razón social del
              tercero
            </li>
            <li>
              <strong>Dirección:</strong> Dirección principal del tercero
            </li>
          </ul>
          <Alert variant="warning" className="mb-0">
            <small>
              <strong>Nota:</strong> Si el sistema no puede extraer la
              información automáticamente, deberá crear el tercero manualmente
              desde el formulario.
            </small>
          </Alert>
        </Card.Body>
      </Card>
    </>
  );
};

export default TerceroUploadPdfView;
