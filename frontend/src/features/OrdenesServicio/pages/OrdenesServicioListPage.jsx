import React, { useState } from 'react';
import { Container, Row, Col, Card, Button, Form, InputGroup, Modal } from 'react-bootstrap';
import { useNavigate } from 'react-router-dom';
import useOrdenesServicio from '../hooks/useOrdenesServicio';
import OrdenesServicioListView from '../views/OrdenesServicioListView';
import ConfirmModal from '../../../components/ConfirmModal/ConfirmModal';
import ordenServicioService from '../services/ordenServicioService';
import { terceroService } from '../../Terceros/services/terceroService';
import { toast } from 'react-toastify';
import MainLayout from '../../../components/Layout/MainLayout';
import BackToDashboard from '../../../components/BackToDashboard/BackToDashboard';

const OrdenesServicioListPage = () => {
  const navigate = useNavigate();
  const { ordenes, loading, pagination, setPage, updateFilters, refetch } = useOrdenesServicio();
  
  const [terceros, setTerceros] = useState([]);
  const [searchTerm, setSearchTerm] = useState('');
  const [estadoFilter, setEstadoFilter] = useState('');
  const [terceroFilter, setTerceroFilter] = useState('');
  
  // Estado para modal de firma
  const [showSignatureModal, setShowSignatureModal] = useState(false);
  const [ordenForSignature, setOrdenForSignature] = useState(null);
  const [signatureLoading, setSignatureLoading] = useState(false);

  const [showDeleteModal, setShowDeleteModal] = useState(false);
  const [ordenToToggle, setOrdenToToggle] = useState(null);

  // Cargar terceros para el filtro
  React.useEffect(() => {
    loadTerceros();
  }, []);

  const loadTerceros = async () => {
    try {
      const response = await terceroService.getAll({ per_page: 1000 });
      setTerceros(response.data || []);
    } catch (error) {
      console.error('Error al cargar terceros:', error);
    }
  };

  const handleSearch = (e) => {
    e.preventDefault();
    updateFilters({ search: searchTerm });
  };

  const handleEstadoChange = (e) => {
    const value = e.target.value;
    setEstadoFilter(value);
    updateFilters({ sw_estado: value });
  };

  const handleTerceroChange = (e) => {
    const value = e.target.value;
    setTerceroFilter(value);
    
    if (value) {
      const [tipo, id] = value.split('|');
      updateFilters({ tipo_id_tercero: tipo, tercero_id: id });
    } else {
      updateFilters({ tipo_id_tercero: '', tercero_id: '' });
    }
  };

  const handleNew = () => {
    navigate('/ordenes-servicio/new');
  };

  const handleEdit = (orden) => {
    navigate(`/ordenes-servicio/edit/${orden.orden_servicio_id}`);
  };

  const handleDeleteClick = (orden) => {
    setOrdenToToggle(orden);
    setShowDeleteModal(true);
  };

  const handleDeleteConfirm = async () => {
    try {
      await ordenServicioService.delete(ordenToToggle.orden_servicio_id);
      const action = ordenToToggle.sw_estado === '1' ? 'desactivada' : 'activada';
      toast.success(`Orden ${action} exitosamente`);
      refetch();
    } catch (error) {
      console.error('Error al cambiar estado:', error);
      toast.error('Error al cambiar estado de la orden');
    } finally {
      setShowDeleteModal(false);
      setOrdenToToggle(null);
    }
  };

  const handleView = (orden) => {
    navigate(`/ordenes-servicio/${orden.orden_servicio_id}`);
  };

  const handleRequestSignature = (orden) => {
    if (orden.sw_estado !== '1') {
      toast.warning('Solo se puede solicitar firma a órdenes activas.');
      return;
    }

    if (orden.fecha_firma) {
      toast.info('Esta orden ya está firmada.');
      return;
    }

    setOrdenForSignature(orden);
    setShowSignatureModal(true);
  };

  const confirmSignatureRequest = async () => {
    if (!ordenForSignature) return;

    setSignatureLoading(true);
    try {
      const response = await ordenServicioService.solicitarFirma(ordenForSignature.orden_servicio_id);
      
      // Si estamos en desarrollo, mostrar el link para probar
      if (response.link_debug) {
        console.log('LINK DE FIRMA (DEBUG):', response.link_debug);
        toast.info('Link generado (ver consola)');
        // Opción: abrir en nueva pestaña para probar
        // window.open(response.link_debug, '_blank');
      }
      
      toast.success(response.message || 'Solicitud enviada exitosamente');
      setShowSignatureModal(false);
      setOrdenForSignature(null);
    } catch (error) {
      console.error('Error al solicitar firma:', error);
      toast.error('Error al enviar la solicitud de firma.');
    } finally {
      setSignatureLoading(false);
    }
  };

  return (
    <MainLayout>
      <Container fluid className="py-4">
        <BackToDashboard />
        <Row className="mb-4">
          <Col>
            <h2>
              <i className="fas fa-file-contract me-2"></i>
              Órdenes de Servicio
            </h2>
          </Col>
        <Col xs="auto">
          <Button variant="primary" onClick={handleNew}>
            <i className="fas fa-plus me-2"></i>
            Nueva Orden
          </Button>
        </Col>
      </Row>

      {/* Filtros y búsqueda */}
      <Card className="mb-4">
        <Card.Body>
          <Row>
            <Col md={5}>
              <Form onSubmit={handleSearch}>
                <InputGroup>
                  <Form.Control
                    type="text"
                    placeholder="Buscar por número de orden..."
                    value={searchTerm}
                    onChange={(e) => setSearchTerm(e.target.value)}
                  />
                  <Button variant="outline-secondary" type="submit">
                    <i className="fas fa-search"></i>
                  </Button>
                </InputGroup>
              </Form>
            </Col>
            <Col md={4}>
              <Form.Select value={terceroFilter} onChange={handleTerceroChange}>
                <option value="">Todos los terceros</option>
                {terceros.map((tercero) => (
                  <option 
                    key={`${tercero.tipo_id_tercero}|${tercero.tercero_id}`}
                    value={`${tercero.tipo_id_tercero}|${tercero.tercero_id}`}
                  >
                    {tercero.nombre_tercero}
                  </option>
                ))}
              </Form.Select>
            </Col>
            <Col md={3}>
              <Form.Select value={estadoFilter} onChange={handleEstadoChange}>
                <option value="">Todos los estados</option>
                <option value="1">Activos</option>
                <option value="0">Inactivos</option>
              </Form.Select>
            </Col>
          </Row>
        </Card.Body>
      </Card>

      {/* Tabla */}
      <Card>
        <Card.Body>
          <OrdenesServicioListView
            ordenes={ordenes}
            loading={loading}
            onEdit={handleEdit}
            onDelete={handleDeleteClick}
            onView={handleView}
            onRequestSignature={handleRequestSignature}
          />
        </Card.Body>
      </Card>

      {/* Paginación */}
      {!loading && pagination.lastPage > 1 && (
        <div className="d-flex justify-content-center align-items-center mt-4">
          <Button
            variant="outline-primary"
            disabled={pagination.currentPage === 1}
            onClick={() => setPage(pagination.currentPage - 1)}
            className="me-2"
          >
            <i className="fas fa-chevron-left"></i> Anterior
          </Button>
          <span className="mx-3">
            Página {pagination.currentPage} de {pagination.lastPage}
            <span className="text-muted ms-2">({pagination.total} registros)</span>
          </span>
          <Button
            variant="outline-primary"
            disabled={pagination.currentPage === pagination.lastPage}
            onClick={() => setPage(pagination.currentPage + 1)}
            className="ms-2"
          >
            Siguiente <i className="fas fa-chevron-right"></i>
          </Button>
        </div>
      )}

      {/* Modal de confirmación para Firma */}
      <Modal show={showSignatureModal} onHide={() => !signatureLoading && setShowSignatureModal(false)}>
        <Modal.Header closeButton={!signatureLoading}>
          <Modal.Title>Solicitar Firma de Cliente</Modal.Title>
        </Modal.Header>
        <Modal.Body>
          <p>¿Enviar solicitud de firma al correo <strong>{ordenForSignature?.email_tercero || ordenForSignature?.tercero?.email || 'No disponible'}</strong> del tercero para la orden <strong>{ordenForSignature?.numero_orden}</strong>?</p>
          <p className="text-muted small">Asegúrese de que el correo es correcto antes de enviar.</p>
        </Modal.Body>
        <Modal.Footer>
          <Button variant="secondary" onClick={() => setShowSignatureModal(false)} disabled={signatureLoading}>
            Cancelar
          </Button>
          <Button variant="primary" onClick={confirmSignatureRequest} disabled={signatureLoading}>
            {signatureLoading ? 'Enviando...' : 'Enviar Solicitud'}
          </Button>
        </Modal.Footer>
      </Modal>

      {/* Modal de confirmación */}
      <ConfirmModal
        show={showDeleteModal}
        onHide={() => setShowDeleteModal(false)}
        onConfirm={handleDeleteConfirm}
        title={ordenToToggle?.sw_estado === '1' ? 'Desactivar Orden' : 'Activar Orden'}
        message={
          ordenToToggle?.sw_estado === '1'
            ? `¿Está seguro de desactivar la orden ${ordenToToggle?.numero_orden}?`
            : `¿Está seguro de activar la orden ${ordenToToggle?.numero_orden}?`
        }
        confirmText={ordenToToggle?.sw_estado === '1' ? 'Desactivar' : 'Activar'}
        confirmVariant={ordenToToggle?.sw_estado === '1' ? 'danger' : 'success'}
        icon={ordenToToggle?.sw_estado === '1' ? 'fa-exclamation-triangle' : 'fa-check-circle'}
      />
      </Container>
    </MainLayout>
  );
};

export default OrdenesServicioListPage;
