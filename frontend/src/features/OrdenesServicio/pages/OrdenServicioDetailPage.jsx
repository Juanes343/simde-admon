import React, { useState, useEffect } from 'react';
import { Container, Row, Col, Button } from 'react-bootstrap';
import { useNavigate, useParams } from 'react-router-dom';
import OrdenServicioDetailView from '../views/OrdenServicioDetailView';
import ordenServicioService from '../services/ordenServicioService';
import { toast } from 'react-toastify';
import MainLayout from '../../../components/Layout/MainLayout';
import BackToDashboard from '../../../components/BackToDashboard/BackToDashboard';

const OrdenServicioDetailPage = () => {
  const navigate = useNavigate();
  const { id } = useParams();
  const [orden, setOrden] = useState(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    loadOrden();
  }, [id]);

  const loadOrden = async () => {
    try {
      setLoading(true);
      const response = await ordenServicioService.getOne(id);
      setOrden(response);
    } catch (error) {
      console.error('Error al cargar orden:', error);
      toast.error('Error al cargar la orden');
      navigate('/ordenes-servicio');
    } finally {
      setLoading(false);
    }
  };

  const handleEdit = () => {
    navigate(`/ordenes-servicio/edit/${id}`);
  };

  const handleBack = () => {
    navigate('/ordenes-servicio');
  };

  if (loading) {
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
              Detalle de Orden de Servicio
            </h2>
          </Col>
          <Col xs="auto">
            <Button variant="outline-secondary" onClick={handleBack} className="me-2">
              <i className="fas fa-arrow-left me-2"></i>
              Volver
            </Button>
            <Button variant="primary" onClick={handleEdit}>
              <i className="fas fa-edit me-2"></i>
              Editar
            </Button>
          </Col>
        </Row>

        <Row>
          <Col>
            <OrdenServicioDetailView orden={orden} />
          </Col>
        </Row>
      </Container>
    </MainLayout>
  );
};

export default OrdenServicioDetailPage;
