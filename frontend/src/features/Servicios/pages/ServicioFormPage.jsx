import React, { useState, useEffect } from 'react';
import { Container, Row, Col, Button } from 'react-bootstrap';
import { useNavigate, useParams } from 'react-router-dom';
import { toast } from 'react-toastify';
import ServicioFormView from '../views/ServicioFormView';
import servicioService from '../services/servicioService';

const ServicioFormPage = () => {
  const navigate = useNavigate();
  const { id } = useParams();
  const isEditMode = !!id;

  const [servicio, setServicio] = useState(null);
  const [loading, setLoading] = useState(false);

  useEffect(() => {
    if (isEditMode) {
      loadServicio();
    }
  }, [id]);

  const loadServicio = async () => {
    try {
      setLoading(true);
      const data = await servicioService.getOne(id);
      setServicio(data);
    } catch (error) {
      toast.error('Error al cargar el servicio');
      console.error('Error:', error);
      navigate('/servicios');
    } finally {
      setLoading(false);
    }
  };

  const handleSubmit = async (formData) => {
    try {
      setLoading(true);
      if (isEditMode) {
        await servicioService.update(id, formData);
        toast.success('Servicio actualizado exitosamente');
      } else {
        await servicioService.create(formData);
        toast.success('Servicio creado exitosamente');
      }
      navigate('/servicios');
    } catch (error) {
      const errorMsg = error.response?.data?.message || 'Error al guardar el servicio';
      toast.error(errorMsg);
      console.error('Error:', error);
    } finally {
      setLoading(false);
    }
  };

  const handleCancel = () => {
    navigate('/servicios');
  };

  return (
    <Container fluid className="py-4">
      <Row className="mb-4">
        <Col>
          <Button variant="link" onClick={() => navigate('/servicios')} className="p-0">
            <i className="fas fa-arrow-left me-2"></i>
            Volver a Servicios
          </Button>
        </Col>
      </Row>

      <Row>
        <Col lg={8} className="mx-auto">
          <ServicioFormView
            servicio={servicio}
            onSubmit={handleSubmit}
            onCancel={handleCancel}
            loading={loading}
          />
        </Col>
      </Row>
    </Container>
  );
};

export default ServicioFormPage;
