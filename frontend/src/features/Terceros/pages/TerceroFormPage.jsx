import React, { useState, useEffect } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import { Container, Button } from 'react-bootstrap';
import { toast } from 'react-toastify';
import TerceroFormView from '../views/TerceroFormView';
import { terceroService } from '../services/terceroService';
import MainLayout from '../../../components/Layout/MainLayout';

const TerceroFormPage = () => {
  const navigate = useNavigate();
  const { tipo_id_tercero, tercero_id } = useParams();
  const isEditMode = !!tipo_id_tercero && !!tercero_id;

  const [tercero, setTercero] = useState(null);
  const [loading, setLoading] = useState(false);

  useEffect(() => {
    if (isEditMode) {
      fetchTercero();
    }
  }, [tipo_id_tercero, tercero_id]);

  const fetchTercero = async () => {
    try {
      const data = await terceroService.getOne(tipo_id_tercero, tercero_id);
      setTercero(data);
    } catch (error) {
      toast.error('Error al cargar el tercero');
      navigate('/terceros');
    }
  };

  const handleSubmit = async (formData) => {
    setLoading(true);

    try {
      if (isEditMode) {
        await terceroService.update(tipo_id_tercero, tercero_id, formData);
        toast.success('Tercero actualizado exitosamente');
      } else {
        await terceroService.create(formData);
        toast.success('Tercero creado exitosamente');
      }
      navigate('/terceros');
    } catch (error) {
      const errorMsg = error.response?.data?.message || 'Error al guardar el tercero';
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
          <h2>{isEditMode ? 'Editar Tercero' : 'Nuevo Tercero'}</h2>
          <Button variant="secondary" onClick={handleCancel}>
            <i className="fas fa-arrow-left"></i> Volver
          </Button>
        </div>

        <TerceroFormView
          tercero={tercero}
          onSubmit={handleSubmit}
          onCancel={handleCancel}
          loading={loading}
        />
      </Container>
    </MainLayout>
  );
};

export default TerceroFormPage;
