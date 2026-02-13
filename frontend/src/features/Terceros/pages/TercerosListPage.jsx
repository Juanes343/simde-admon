import React from 'react';
import { useNavigate } from 'react-router-dom';
import { Container, Button, Form, InputGroup } from 'react-bootstrap';
import { toast } from 'react-toastify';
import { useTerceros } from '../hooks/useTerceros';
import { terceroService } from '../services/terceroService';
import TercerosListView from '../views/TercerosListView';
import MainLayout from '../../../components/Layout/MainLayout';
import BackToDashboard from '../../../components/BackToDashboard/BackToDashboard';
import ConfirmModal from '../../../components/ConfirmModal';

const TercerosListPage = () => {
  const navigate = useNavigate();
  const { terceros, loading, pagination, setPage, updateFilters, refetch } = useTerceros();
  const [search, setSearch] = React.useState('');
  const [showConfirmModal, setShowConfirmModal] = React.useState(false);
  const [terceroToDelete, setTerceroToDelete] = React.useState(null);

  const handleSearch = () => {
    updateFilters({ search });
  };

  const handleEdit = (tercero) => {
    navigate(`/terceros/edit/${tercero.tipo_id_tercero}/${tercero.tercero_id}`);
  };

  const handleDelete = (tercero) => {
    setTerceroToDelete(tercero);
    setShowConfirmModal(true);
  };

  const confirmDelete = async () => {
    if (!terceroToDelete) return;
    
    const isActive = terceroToDelete.sw_estado === '1';
    const action = isActive ? 'desactivado' : 'activado';
    
    try {
      await terceroService.delete(terceroToDelete.tipo_id_tercero, terceroToDelete.tercero_id);
      toast.success(`Tercero ${action} exitosamente`);
      refetch();
    } catch (error) {
      toast.error(`Error al ${isActive ? 'desactivar' : 'activar'} tercero`);
    } finally {
      setTerceroToDelete(null);
    }
  };

  return (
    <MainLayout>
      <Container fluid>
        <BackToDashboard />
        <div className="d-flex justify-content-between align-items-center mb-4">
          <h2>Gestión de Terceros</h2>
          <div>
            <Button
              variant="warning"
              className="me-2"
              onClick={() => navigate('/terceros/upload-pdf')}
            >
              <i className="fas fa-file-pdf"></i> Cargar RUT PDF
            </Button>
            <Button variant="primary" onClick={() => navigate('/terceros/new')}>
              <i className="fas fa-plus"></i> Nuevo Tercero
            </Button>
          </div>
        </div>

        <InputGroup className="mb-3">
          <Form.Control
            placeholder="Buscar por nombre, ID o email..."
            value={search}
            onChange={(e) => setSearch(e.target.value)}
            onKeyPress={(e) => e.key === 'Enter' && handleSearch()}
          />
          <Button variant="outline-secondary" onClick={handleSearch}>
            <i className="fas fa-search"></i> Buscar
          </Button>
        </InputGroup>

        <TercerosListView
          terceros={terceros}
          loading={loading}
          onEdit={handleEdit}
          onDelete={handleDelete}
        />

        <div className="d-flex justify-content-center mt-3">
          <Button
            variant="secondary"
            disabled={pagination.currentPage === 1}
            onClick={() => setPage(pagination.currentPage - 1)}
            className="me-2"
          >
            Anterior
          </Button>
          <span className="align-self-center mx-3">
            Página {pagination.currentPage} de {pagination.lastPage}
          </span>
          <Button
            variant="secondary"
            disabled={pagination.currentPage === pagination.lastPage}
            onClick={() => setPage(pagination.currentPage + 1)}
          >
            Siguiente
          </Button>
        </div>

        <ConfirmModal
          show={showConfirmModal}
          onHide={() => {
            setShowConfirmModal(false);
            setTerceroToDelete(null);
          }}
          onConfirm={confirmDelete}
          title={terceroToDelete?.sw_estado === '1' ? 'Desactivar Tercero' : 'Activar Tercero'}
          message={
            terceroToDelete 
              ? terceroToDelete.sw_estado === '1'
                ? `¿Está seguro de desactivar el tercero "${terceroToDelete.nombre_tercero}"?`
                : `¿Está seguro de activar el tercero "${terceroToDelete.nombre_tercero}"?`
              : '¿Está seguro de realizar esta acción?'
          }
          confirmText={terceroToDelete?.sw_estado === '1' ? 'Desactivar' : 'Activar'}
          cancelText="Cancelar"
          confirmVariant={terceroToDelete?.sw_estado === '1' ? 'danger' : 'success'}
          icon={terceroToDelete?.sw_estado === '1' ? 'fa-exclamation-triangle' : 'fa-check-circle'}
        />
      </Container>
    </MainLayout>
  );
};

export default TercerosListPage;
