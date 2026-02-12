import React, { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { Container, Button } from 'react-bootstrap';
import { toast } from 'react-toastify';
import TerceroUploadPdfView from '../views/TerceroUploadPdfView';
import { terceroService } from '../services/terceroService';
import MainLayout from '../../../components/Layout/MainLayout';

const TerceroUploadPdfPage = () => {
  const navigate = useNavigate();
  const [loading, setLoading] = useState(false);

  const handleSubmit = async (formData) => {
    setLoading(true);

    try {
      await terceroService.uploadPdf(formData);
      toast.success('Tercero creado exitosamente desde PDF');
      navigate('/terceros');
    } catch (error) {
      const errorMsg =
        error.response?.data?.message || 'Error al procesar el PDF';
      toast.error(errorMsg);
    } finally {
      setLoading(false);
    }
  };

  const handleCancel = () => {
    navigate('/terceros');
  };

  return (
    <MainLayout>
      <Container>
        <div className="d-flex justify-content-between align-items-center mb-4">
          <h2>Cargar RUT desde PDF</h2>
          <Button variant="secondary" onClick={handleCancel}>
            <i className="fas fa-arrow-left"></i> Volver
          </Button>
        </div>

        <TerceroUploadPdfView
          onSubmit={handleSubmit}
          onCancel={handleCancel}
          loading={loading}
        />
      </Container>
    </MainLayout>
  );
};

export default TerceroUploadPdfPage;
