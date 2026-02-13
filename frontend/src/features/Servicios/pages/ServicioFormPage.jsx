import React, { useState, useEffect } from 'react';
import { Container, Button } from 'react-bootstrap';
import { useNavigate, useParams } from 'react-router-dom';
import { toast } from 'react-toastify';
import ServicioFormView from '../views/ServicioFormView';
import servicioService from '../services/servicioService';
import MainLayout from '../../../components/Layout/MainLayout';

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
    <MainLayout>
      <Container>
        <div className="d-flex justify-content-between align-items-center mb-4">
          <h2>{isEditMode ? 'Editar Servicio' : 'Nuevo Servicio'}</h2>
          <Button variant="secondary" onClick={handleCancel}>
            <i className="fas fa-arrow-left me-2"></i> Volver
          </Button>
        </div>

        <ServicioFormView
          servicio={servicio}
          onSubmit={handleSubmit}
          onCancel={handleCancel}
          loading={loading}
        />
      </Container>
    </MainLayout>
  );
};

export default ServicioFormPage;
