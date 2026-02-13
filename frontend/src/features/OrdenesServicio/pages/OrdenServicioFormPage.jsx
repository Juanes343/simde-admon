import React, { useState, useEffect } from 'react';
import { Container, Row, Col, Card } from 'react-bootstrap';
import { useNavigate, useParams } from 'react-router-dom';
import OrdenServicioFormView from '../views/OrdenServicioFormView';
import ordenServicioService from '../services/ordenServicioService';
import { toast } from 'react-toastify';
import MainLayout from '../../../components/Layout/MainLayout';
import BackToDashboard from '../../../components/BackToDashboard/BackToDashboard';

const OrdenServicioFormPage = () => {
  const navigate = useNavigate();
  const { id } = useParams();
  const isEditMode = !!id;

  const [orden, setOrden] = useState(null);
  const [loading, setLoading] = useState(false);
  const [loadingData, setLoadingData] = useState(isEditMode);

  useEffect(() => {
    if (isEditMode) {
      loadOrden();
    }
  }, [id]);

  const loadOrden = async () => {
    try {
      setLoadingData(true);
      const response = await ordenServicioService.getOne(id);
      setOrden(response);
    } catch (error) {
      console.error('Error al cargar orden:', error);
      toast.error('Error al cargar la orden');
      navigate('/ordenes-servicio');
    } finally {
      setLoadingData(false);
    }
  };

  const handleSubmit = async (data) => {
    try {
      setLoading(true);
      
      if (isEditMode) {
        await ordenServicioService.update(id, data);
        toast.success('Orden actualizada exitosamente');
      } else {
        await ordenServicioService.create(data);
        toast.success('Orden creada exitosamente');
      }
      
      navigate('/ordenes-servicio');
    } catch (error) {
      console.error('Error al guardar orden:', error);
      const message = error.response?.data?.message || 'Error al guardar la orden';
      toast.error(message);
    } finally {
      setLoading(false);
    }
  };

  const handleCancel = () => {
    navigate('/ordenes-servicio');
  };

  if (loadingData) {
    return (
      <MainLayout>
        <Container fluid className="py-4">
          <BackToDashboard />
          <div className="text-center">
            <div className="spinner-border text-primary" role="status">
              <span className="visually-hidden">Cargando...</span>
            </div>
            <p className="mt-2">Cargando orden...</p>
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
              <i className="fas fa-file-contract me-2"></i>
              {isEditMode ? 'Editar Orden de Servicio' : 'Nueva Orden de Servicio'}
            </h2>
          </Col>
        </Row>

      <Row>
        <Col>
          <OrdenServicioFormView
            orden={orden}
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

export default OrdenServicioFormPage;
