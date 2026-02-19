import React from 'react';
import { Table, Button, Badge, Spinner } from 'react-bootstrap';

// Mapeo de tipos de identificación
const getTipoIdentificacion = (codigo) => {
  const tipos = {
    '11': 'RC',           // Registro Civil
    '12': 'TI',           // Tarjeta de Identidad
    '13': 'CC',           // Cédula de Ciudadanía
    '21': 'TE',           // Tarjeta de Extranjería
    '22': 'CE',           // Cédula de Extranjería
    '31': 'NIT',          // NIT
    '41': 'Pasaporte',
    '42': 'Documento Extranjero',
    '43': 'PEP',          // Permiso Especial de Permanencia
  };
  return tipos[codigo] || codigo;
};

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
        <thead className="table-header-custom">
          <tr>
            <th>Tipo ID</th>
            <th>Identificación</th>
            <th>Nombre</th>
            <th>Email</th>
            <th>Teléfono</th>
            <th>Dirección</th>
            <th className="text-center">Estado</th>
            <th className="text-center">Acciones</th>
          </tr>
        </thead>
        <tbody>
          {terceros.map((tercero) => (
            <tr key={`${tercero.tipo_id_tercero}-${tercero.tercero_id}`}>
              <td>{getTipoIdentificacion(tercero.tipo_id_tercero)}</td>
              <td>{tercero.tercero_id}</td>
              <td>{tercero.nombre_tercero}</td>
              <td>{tercero.email || '-'}</td>
              <td>{tercero.telefono || '-'}</td>
              <td>{tercero.direccion}</td>
              <td className="text-center">
                <Badge bg={tercero.sw_estado === '1' ? 'success' : 'danger'}>
                  <i className={`fas fa-${tercero.sw_estado === '1' ? 'check' : 'times-circle'} me-1`}></i>
                  {tercero.sw_estado === '1' ? 'Activo' : 'Inactivo'}
                </Badge>
              </td>
              <td className="text-center">
                <Button
                  size="sm"
                  variant="outline-primary"
                  className="me-1"
                  onClick={() => onEdit(tercero)}
                  title="Editar"
                >
                  <i className="fas fa-edit"></i>
                </Button>
                <Button
                  size="sm"
                  variant={tercero.sw_estado === '1' ? 'outline-danger' : 'outline-success'}
                  onClick={() => onDelete(tercero)}
                  title={tercero.sw_estado === '1' ? 'Desactivar' : 'Activar'}
                >
                  <i className={`fas fa-${tercero.sw_estado === '1' ? 'ban' : 'check'}`}></i>
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
