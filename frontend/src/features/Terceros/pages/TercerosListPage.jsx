import React from 'react';
import { useNavigate } from 'react-router-dom';
import { Container, Button, Form, InputGroup } from 'react-bootstrap';
import { toast } from 'react-toastify';
import { useTerceros } from '../hooks/useTerceros';
import { terceroService } from '../services/terceroService';
import TercerosListView from '../views/TercerosListView';
import MainLayout from '../../../components/Layout/MainLayout';
import BackToDashboard from '../../../components/BackToDashboard/BackToDashboard';

const TercerosListPage = () => {
  const navigate = useNavigate();
  const { terceros, loading, pagination, setPage, updateFilters, refetch } = useTerceros();
  const [search, setSearch] = React.useState('');

  const handleSearch = () => {
    updateFilters({ search });
  };

  const handleEdit = (tercero) => {
    navigate(`/terceros/edit/${tercero.tipo_id_tercero}/${tercero.tercero_id}`);
  };

  const handleDelete = async (tercero) => {
    if (window.confirm('¿Está seguro de desactivar este tercero?')) {
      try {
        await terceroService.delete(tercero.tipo_id_tercero, tercero.tercero_id);
        toast.success('Tercero desactivado exitosamente');
        refetch();
      } catch (error) {
        toast.error('Error al desactivar tercero');
      }
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
      </Container>
    </MainLayout>
  );
};

export default TercerosListPage;
