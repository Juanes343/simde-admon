import React, { useState } from 'react';
import { Table, Badge, Button, Spinner } from 'react-bootstrap';
import { format } from 'date-fns';

const OrdenesServicioListView = ({ ordenes, loading, onEdit, onDelete, onView }) => {
  if (loading) {
    return (
      <div className="text-center py-5">
        <Spinner animation="border" role="status">
          <span className="visually-hidden">Cargando...</span>
        </Spinner>
      </div>
    );
  }

  if (!ordenes || ordenes.length === 0) {
    return (
      <div className="text-center py-5">
        <i className="fas fa-folder-open fa-3x text-muted mb-3"></i>
        <p className="text-muted">No hay órdenes de servicio registradas</p>
      </div>
    );
  }

  const formatCurrency = (value) => {
    return new Intl.NumberFormat('es-CO', {
      style: 'currency',
      currency: 'COP',
      minimumFractionDigits: 0
    }).format(value);
  };

  const formatDate = (date) => {
    if (!date) return '-';
    return format(new Date(date), 'dd/MM/yyyy');
  };

  return (
    <div className="table-responsive">
      <Table hover className="align-middle">
        <thead className="table-header-custom">
          <tr>
            <th>Número Orden</th>
            <th>Tercero</th>
            <th>Fecha Inicio</th>
            <th>Fecha Fin</th>
            <th>Periodo Fact.</th>
            <th>Prórroga Auto</th>
            <th>Total</th>
            <th>Estado</th>
            <th className="text-center">Acciones</th>
          </tr>
        </thead>
        <tbody>
          {ordenes.map((orden) => (
            <tr key={orden.orden_servicio_id}>
              <td>
                <strong>{orden.numero_orden}</strong>
              </td>
              <td>
                <div>
                  <div className="fw-bold">{orden.tercero?.nombre_tercero || 'N/A'}</div>
                  <small className="text-muted">
                    {orden.tipo_id_tercero} {orden.tercero_id}
                  </small>
                </div>
              </td>
              <td>{formatDate(orden.fecha_inicio)}</td>
              <td>{formatDate(orden.fecha_fin)}</td>
              <td>{orden.periodo_facturacion_dias} días</td>
              <td>
                {orden.sw_prorroga_automatica === '1' ? (
                  <Badge bg="success">
                    <i className="fas fa-check me-1"></i>Sí
                  </Badge>
                ) : (
                  <Badge bg="secondary">
                    <i className="fas fa-times me-1"></i>No
                  </Badge>
                )}
              </td>
              <td>
                <strong>{formatCurrency(orden.total || 0)}</strong>
                {orden.porcentaje_soltec > 0 && (
                  <div>
                    <small className="text-muted">
                      Soltec: {orden.porcentaje_soltec}%
                    </small>
                  </div>
                )}
              </td>
              <td>
                {orden.sw_estado === '1' ? (
                  <Badge bg="success">Activo</Badge>
                ) : (
                  <Badge bg="danger">Inactivo</Badge>
                )}
                {orden.permite_facturar_hoy && (
                  <div>
                    <Badge bg="info" className="mt-1">
                      <i className="fas fa-file-invoice me-1"></i>Facturable
                    </Badge>
                  </div>
                )}
              </td>
              <td className="text-center">
                <Button
                  variant="outline-info"
                  size="sm"
                  className="me-1"
                  onClick={() => onView(orden)}
                  title="Ver detalles"
                >
                  <i className="fas fa-eye"></i>
                </Button>
                <Button
                  variant="outline-primary"
                  size="sm"
                  className="me-1"
                  onClick={() => onEdit(orden)}
                  title="Editar"
                >
                  <i className="fas fa-edit"></i>
                </Button>
                <Button
                  variant={orden.sw_estado === '1' ? 'outline-danger' : 'outline-success'}
                  size="sm"
                  onClick={() => onDelete(orden)}
                  title={orden.sw_estado === '1' ? 'Desactivar' : 'Activar'}
                >
                  <i className={`fas fa-${orden.sw_estado === '1' ? 'ban' : 'check'}`}></i>
                </Button>
              </td>
            </tr>
          ))}
        </tbody>
      </Table>
    </div>
  );
};

export default OrdenesServicioListView;
