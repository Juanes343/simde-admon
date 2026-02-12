import React from 'react';
import { Table, Button, Badge, Spinner } from 'react-bootstrap';

const TercerosListView = ({ terceros, loading, onEdit, onDelete }) => {
  if (loading) {
    return (
      <div className="text-center py-5">
        <Spinner animation="border" role="status">
          <span className="visually-hidden">Cargando...</span>
        </Spinner>
      </div>
    );
  }

  if (terceros.length === 0) {
    return (
      <div className="text-center py-5">
        <p className="text-muted">No se encontraron terceros</p>
      </div>
    );
  }

  return (
    <div className="table-responsive">
      <Table striped bordered hover>
        <thead>
          <tr>
            <th>Tipo ID</th>
            <th>Identificación</th>
            <th>Nombre</th>
            <th>Email</th>
            <th>Teléfono</th>
            <th>Dirección</th>
            <th>Estado</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>
          {terceros.map((tercero) => (
            <tr key={`${tercero.tipo_id_tercero}-${tercero.tercero_id}`}>
              <td>{tercero.tipo_id_tercero}</td>
              <td>{tercero.tercero_id}</td>
              <td>{tercero.nombre_tercero}</td>
              <td>{tercero.email || '-'}</td>
              <td>{tercero.telefono || '-'}</td>
              <td>{tercero.direccion}</td>
              <td>
                <Badge bg={tercero.sw_estado === '1' ? 'success' : 'danger'}>
                  {tercero.sw_estado === '1' ? 'Activo' : 'Inactivo'}
                </Badge>
              </td>
              <td>
                <Button
                  size="sm"
                  variant="info"
                  className="me-1"
                  onClick={() => onEdit(tercero)}
                >
                  <i className="fas fa-edit"></i>
                </Button>
                <Button
                  size="sm"
                  variant="danger"
                  onClick={() => onDelete(tercero)}
                >
                  <i className="fas fa-trash"></i>
                </Button>
              </td>
            </tr>
          ))}
        </tbody>
      </Table>
    </div>
  );
};

export default TercerosListView;
