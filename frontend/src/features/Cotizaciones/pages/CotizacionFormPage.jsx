import React, { useState, useEffect } from 'react';
import { Container, Row, Col } from 'react-bootstrap';
import { useNavigate, useParams } from 'react-router-dom';
import { toast } from 'react-toastify';
import MainLayout from '../../../components/Layout/MainLayout';
import BackToDashboard from '../../../components/BackToDashboard/BackToDashboard';
import CotizacionFormView from '../views/CotizacionFormView';
import cotizacionService from '../services/cotizacionService';

const CotizacionFormPage = () => {
  const navigate    = useNavigate();
  const { id }      = useParams();
  const isEditMode  = !!id;

  const [cotizacion,    setCotizacion]    = useState(null);
  const [loading,       setLoading]       = useState(false);
  const [loadingData,   setLoadingData]   = useState(isEditMode);

  useEffect(() => {
    if (isEditMode) loadCotizacion();
  }, [id]);

  const loadCotizacion = async () => {
    try {
      setLoadingData(true);
      const data = await cotizacionService.getOne(id);
      setCotizacion(data);
    } catch (err) {
      console.error(err);
      toast.error('Error al cargar la cotización');
      navigate('/cotizaciones');
    } finally {
      setLoadingData(false);
    }
  };

  const handleSubmit = async (data) => {
    try {
      setLoading(true);
      if (isEditMode) {
        await cotizacionService.update(id, data);
        toast.success('Cotización actualizada exitosamente');
      } else {
        await cotizacionService.create(data);
        toast.success('Cotización creada exitosamente');
      }
      navigate('/cotizaciones');
    } catch (err) {
      const msg = err.response?.data?.message || 'Error al guardar la cotización';
      toast.error(msg);
    } finally {
      setLoading(false);
    }
  };

  const handleCancel = () => navigate('/cotizaciones');

  if (loadingData) {
    return (
      <MainLayout>
        <Container fluid className="py-4">
          <BackToDashboard />
          <div className="text-center py-5">
            <div className="spinner-border text-primary" role="status" />
            <p className="mt-2">Cargando cotización...</p>
          </div>
        </Container>
      </MainLayout>
    );
  }

  return (
    <MainLayout>
      <Container fluid className="py-4">
        <BackToDashboard />
        <Row className="mb-4">
          <Col>
            <h2>
              <i className="fas fa-file-alt me-2"></i>
              {isEditMode ? 'Editar Cotización' : 'Nueva Cotización'}
            </h2>
          </Col>
        </Row>

        <Row>
          <Col>
            <CotizacionFormView
              cotizacion={cotizacion}
              onSubmit={handleSubmit}
              onCancel={handleCancel}
              loading={loading}
            />
          </Col>
        </Row>
      </Container>
    </MainLayout>
  );
};

export default CotizacionFormPage;
