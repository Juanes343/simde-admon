import React, { useState, useEffect } from 'react';
import { Container, Row, Col, Button, Form, InputGroup } from 'react-bootstrap';
import { useNavigate } from 'react-router-dom';
import { toast } from 'react-toastify';
import ServiciosListView from '../views/ServiciosListView';
import servicioService from '../services/servicioService';
import MainLayout from '../../../components/Layout/MainLayout';

const ServiciosListPage = () => {
  const navigate = useNavigate();
  const [servicios, setServicios] = useState([]);
  const [loading, setLoading] = useState(true);
  const [buscar, setBuscar] = useState('');
  const [filtroEstado, setFiltroEstado] = useState('');
  const [filtroTipoUnidad, setFiltroTipoUnidad] = useState('');

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

  const handleDelete = async (servicio) => {
    if (window.confirm(`¿Está seguro de desactivar el servicio "${servicio.nombre_servicio}"?`)) {
      try {
        await servicioService.delete(servicio.servicio_id);
        toast.success('Servicio desactivado exitosamente');
        loadServicios();
      } catch (error) {
        toast.error('Error al desactivar el servicio');
        console.error('Error:', error);
      }
    }
  };

  return (
    <MainLayout>
      <Container fluid className="py-4">
        <Row className="mb-4">
          <Col>
            <Button variant="link" onClick={() => navigate('/dashboard')} className="p-0 mb-2">
              <i className="fas fa-arrow-left me-2"></i>
              Volver al Dashboard
            </Button>
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
    </Container>
    </MainLayout>
  );
};

export default ServiciosListPage;
