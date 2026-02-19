import React from 'react';
import { Table, Button, Badge, Spinner } from 'react-bootstrap';

const ServiciosListView = ({ servicios, loading, onEdit, onDelete }) => {
  if (loading) {
    return (
      <div className="text-center py-5">
        <Spinner animation="border" role="status">
          <span className="visually-hidden">Cargando...</span>
        </Spinner>
      </div>
    );
  }

  if (servicios.length === 0) {
    return (
      <div className="text-center py-5">
        <p className="text-muted">No se encontraron servicios</p>
      </div>
    );
  }

  const formatCurrency = (value) => {
    return new Intl.NumberFormat('es-CO', {
      style: 'currency',
      currency: 'COP',
      minimumFractionDigits: 0,
    }).format(value);
  };

  return (
    <div className="table-responsive">
      <Table striped bordered hover>
        <thead className="table-header-custom">
          <tr>
            <th>ID</th>
            <th>Nombre del Servicio</th>
            <th>Descripción</th>
            <th className="text-center">Cant.</th>
            <th className="text-center">Unidad</th>
            <th className="text-end">Precio Unit.</th>
            <th className="text-center">Estado</th>
            <th className="text-center">Acciones</th>
          </tr>
        </thead>
        <tbody>
          {servicios.map((servicio) => (
            <tr key={servicio.servicio_id}>
              <td>{servicio.servicio_id}</td>
              <td><strong>{servicio.nombre_servicio}</strong></td>
              <td>
                {servicio.descripcion ? (
                  servicio.descripcion.length > 50 
                    ? `${servicio.descripcion.substring(0, 50)}...`
                    : servicio.descripcion
                ) : '-'}
              </td>
              <td className="text-center">
                {servicio.cantidad || '-'}
              </td>
              <td className="text-center">
                <Badge bg={servicio.tipo_unidad === 'HORAS' ? 'info' : 'secondary'}>
                  {servicio.tipo_unidad}
                </Badge>
              </td>
              <td className="text-end">
                {formatCurrency(servicio.precio_unitario)}
              </td>
              <td className="text-center">
                <Badge bg={servicio.sw_estado === '1' ? 'success' : 'danger'}>
                  <i className={`fas fa-${servicio.sw_estado === '1' ? 'check' : 'times-circle'} me-1`}></i>
                  {servicio.sw_estado === '1' ? 'Activo' : 'Inactivo'}
                </Badge>
              </td>
              <td className="text-center">
                <Button
                  size="sm"
                  variant="outline-primary"
                  className="me-1"
                  onClick={() => onEdit(servicio)}
                  title="Editar"
                >
                  <i className="fas fa-edit"></i>
                </Button>
                <Button
                  size="sm"
                  variant={servicio.sw_estado === '1' ? 'outline-danger' : 'outline-success'}
                  onClick={() => onDelete(servicio)}
                  title={servicio.sw_estado === '1' ? 'Desactivar' : 'Activar'}
                >
                  <i className={`fas fa-${servicio.sw_estado === '1' ? 'ban' : 'check'}`}></i>
                </Button>
              </td>
            </tr>
          ))}
        </tbody>
      </Table>
    </div>
  );
};

export default ServiciosListView;
