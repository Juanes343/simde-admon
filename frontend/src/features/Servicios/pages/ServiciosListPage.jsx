import React, { useState, useEffect } from 'react';
import { Container, Row, Col, Button, Form, InputGroup } from 'react-bootstrap';
import { useNavigate } from 'react-router-dom';
import { toast } from 'react-toastify';
import ServiciosListView from '../views/ServiciosListView';
import servicioService from '../services/servicioService';
import MainLayout from '../../../components/Layout/MainLayout';
import BackToDashboard from '../../../components/BackToDashboard/BackToDashboard';
import ConfirmModal from '../../../components/ConfirmModal';

const ServiciosListPage = () => {
  const navigate = useNavigate();
  const [servicios, setServicios] = useState([]);
  const [loading, setLoading] = useState(true);
  const [buscar, setBuscar] = useState('');
  const [filtroEstado, setFiltroEstado] = useState('');
  const [filtroTipoUnidad, setFiltroTipoUnidad] = useState('');
  const [showConfirmModal, setShowConfirmModal] = useState(false);
  const [servicioToDelete, setServicioToDelete] = useState(null);

  useEffect(() => {
    loadServicios();
  }, [filtroEstado, filtroTipoUnidad]);

  const loadServicios = async () => {
    try {
      setLoading(true);
      const params = {};
      
      // Solo agregar parámetros si tienen valor
      if (buscar && buscar.trim()) {
        params.buscar = buscar;
      }
      if (filtroEstado) {
        params.estado = filtroEstado;
      }
      if (filtroTipoUnidad) {
        params.tipo_unidad = filtroTipoUnidad;
      }
      
      console.log('Params enviados a API:', params);
      const response = await servicioService.getAll(params);
      console.log('Response from API:', response);
      
      // La respuesta paginada tiene la estructura: {data: [...], current_page, total, etc}
      if (response.data && Array.isArray(response.data)) {
        console.log('Servicios encontrados:', response.data.length);
        setServicios(response.data);
      } else if (Array.isArray(response)) {
        console.log('Response es array:', response.length);
        setServicios(response);
      } else {
        console.log('No se encontraron servicios en la respuesta');
        setServicios([]);
      }
    } catch (error) {
      toast.error('Error al cargar los servicios');
      console.error('Error al cargar servicios:', error);
    } finally {
      setLoading(false);
    }
  };

  const handleBuscar = (e) => {
    e.preventDefault();
    loadServicios();
  };

  const handleEdit = (servicio) => {
    navigate(`/servicios/edit/${servicio.servicio_id}`);
  };

  const handleDelete = (servicio) => {
    setServicioToDelete(servicio);
    setShowConfirmModal(true);
  };

  const confirmDelete = async () => {
    if (!servicioToDelete) return;
    
    const isActive = servicioToDelete.sw_estado === '1';
    const action = isActive ? 'desactivado' : 'activado';
    
    try {
      await servicioService.delete(servicioToDelete.servicio_id);
      toast.success(`Servicio ${action} exitosamente`);
      loadServicios();
    } catch (error) {
      toast.error(`Error al ${isActive ? 'desactivar' : 'activar'} el servicio`);
      console.error('Error:', error);
    } finally {
      setServicioToDelete(null);
    }
  };

  return (
    <MainLayout>
      <Container fluid className="py-4">
        <BackToDashboard />
        <Row className="mb-4">
          <Col>
            <h2>Gestión de Servicios</h2>
          </Col>
          <Col className="text-end">
            <Button
              variant="primary"
              onClick={() => navigate('/servicios/new')}
            >
              <i className="fas fa-plus me-2"></i>
              Nuevo Servicio
            </Button>
          </Col>
        </Row>

      <Row className="mb-3">
        <Col md={5}>
          <Form onSubmit={handleBuscar}>
            <InputGroup>
              <Form.Control
                type="text"
                placeholder="Buscar por nombre o descripción..."
                value={buscar}
                onChange={(e) => setBuscar(e.target.value)}
              />
              <Button variant="primary" type="submit">
                <i className="fas fa-search"></i> Buscar
              </Button>
            </InputGroup>
          </Form>
        </Col>
        <Col md={3}>
          <Form.Select
            value={filtroTipoUnidad}
            onChange={(e) => setFiltroTipoUnidad(e.target.value)}
          >
            <option value="">Todos los tipos</option>
            <option value="UNIDAD">UNIDAD</option>
            <option value="HORAS">HORAS</option>
          </Form.Select>
        </Col>
        <Col md={3}>
          <Form.Select
            value={filtroEstado}
            onChange={(e) => setFiltroEstado(e.target.value)}
          >
            <option value="">Todos los estados</option>
            <option value="1">Activos</option>
            <option value="0">Inactivos</option>
          </Form.Select>
        </Col>
        <Col md={1}>
          <Button
            variant="secondary"
            onClick={() => {
              setBuscar('');
              setFiltroEstado('');
              setFiltroTipoUnidad('');
              loadServicios();
            }}
          >
            <i className="fas fa-redo"></i>
          </Button>
        </Col>
      </Row>

      <Row>
        <Col>
          <ServiciosListView
            servicios={servicios}
            loading={loading}
            onEdit={handleEdit}
            onDelete={handleDelete}
          />
        </Col>
      </Row>

      <ConfirmModal
        show={showConfirmModal}
        onHide={() => {
          setShowConfirmModal(false);
          setServicioToDelete(null);
        }}
        onConfirm={confirmDelete}
        title={servicioToDelete?.sw_estado === '1' ? 'Desactivar Servicio' : 'Activar Servicio'}
        message={
          servicioToDelete 
            ? servicioToDelete.sw_estado === '1'
              ? `¿Está seguro de desactivar el servicio "${servicioToDelete.nombre_servicio}"?`
              : `¿Está seguro de activar el servicio "${servicioToDelete.nombre_servicio}"?`
            : '¿Está seguro de realizar esta acción?'
        }
        confirmText={servicioToDelete?.sw_estado === '1' ? 'Desactivar' : 'Activar'}
        cancelText="Cancelar"
        confirmVariant={servicioToDelete?.sw_estado === '1' ? 'danger' : 'success'}
        icon={servicioToDelete?.sw_estado === '1' ? 'fa-exclamation-triangle' : 'fa-check-circle'}
      />
    </Container>
    </MainLayout>
  );
};

export default ServiciosListPage;
