import React from 'react';
import { Card, Row, Col, Table, Badge } from 'react-bootstrap';
import { format } from 'date-fns';

const OrdenServicioDetailView = ({ orden }) => {
  if (!orden) return null;

  const formatCurrency = (value) => {
    return new Intl.NumberFormat('es-CO', {
      style: 'currency',
      currency: 'COP',
      minimumFractionDigits: 0
    }).format(value);
  };

  const formatDate = (dateString) => {
    if (!dateString) return '-';
    return format(new Date(dateString), 'dd/MM/yyyy');
  };

  // Calcular total desde items si no viene del backend
  const calcularTotal = () => {
    if (orden.total) return orden.total;
    if (!orden.items || orden.items.length === 0) return 0;
    return orden.items.reduce((sum, item) => sum + (parseFloat(item.subtotal) || 0), 0);
  };

  return (
    <div>
      {/* Información General */}
      <Card className="mb-4">
        <Card.Header>
          <h5 className="mb-0">
            <i className="fas fa-info-circle me-2"></i>
            Información General
          </h5>
        </Card.Header>
        <Card.Body>
          <Row>
            <Col md={6}>
              <p><strong>Número de Orden:</strong> {orden.numero_orden}</p>
              <p><strong>Estado:</strong>{' '}
                <Badge bg={orden.sw_estado === '1' ? 'success' : 'danger'}>
                  {orden.sw_estado === '1' ? 'Activo' : 'Inactivo'}
                </Badge>
              </p>
              <p><strong>Tercero:</strong></p>
              {orden.tercero && (
                <div className="ms-3">
                  <p className="mb-1">{orden.tercero.nombre_tercero}</p>
                  <small className="text-muted">
                    {orden.tercero.tipo_id_tercero} {orden.tercero.tercero_id}
                  </small>
                </div>
              )}
            </Col>
            <Col md={6}>
              <p><strong>Fecha Inicio:</strong> {formatDate(orden.fecha_inicio)}</p>
              <p><strong>Fecha Fin:</strong> {formatDate(orden.fecha_fin)}</p>
              <p><strong>Prórroga Automática:</strong>{' '}
                <Badge bg={orden.sw_prorroga_automatica === '1' ? 'success' : 'secondary'}>
                  {orden.sw_prorroga_automatica === '1' ? (
                    <>
                      <i className="fas fa-check me-1"></i>Sí
                    </>
                  ) : (
                    <>
                      <i className="fas fa-times me-1"></i>No
                    </>
                  )}
                </Badge>
              </p>
              <p><strong>Periodo Facturación:</strong> {orden.periodo_facturacion_dias} días</p>
              {parseFloat(orden.porcentaje_soltec) > 0 && (
                <p><strong>Porcentaje Soltec:</strong> {orden.porcentaje_soltec}%</p>
              )}
            </Col>
          </Row>
          
          {orden.observaciones && (
            <Row className="mt-3">
              <Col>
                <p className="mb-1"><strong>Observaciones:</strong></p>
                <p className="text-muted">{orden.observaciones}</p>
              </Col>
            </Row>
          )}
        </Card.Body>
      </Card>

      {/* Servicios */}
      <Card className="mb-4">
        <Card.Header>
          <h5 className="mb-0">
            <i className="fas fa-list me-2"></i>
            Servicios Contratados
          </h5>
        </Card.Header>
        <Card.Body>
          <Table bordered hover responsive>
            <thead className="table-light">
              <tr>
                <th>Servicio</th>
                <th style={{ width: '120px' }} className="text-end">Cantidad</th>
                <th style={{ width: '150px' }} className="text-end">Precio Unitario</th>
                <th style={{ width: '150px' }} className="text-end">Subtotal</th>
              </tr>
            </thead>
            <tbody>
              {orden.items && orden.items.map((item, index) => (
                <tr key={index}>
                  <td>
                    <strong>{item.nombre_servicio}</strong>
                    <br />
                    <small className="text-muted">
                      {item.descripcion || 'Sin descripción'}
                    </small>
                    <br />
                    <Badge bg="secondary" className="mt-1">
                      {item.tipo_unidad}
                    </Badge>
                  </td>
                  <td className="text-end align-middle">
                    {item.cantidad}
                  </td>
                  <td className="text-end align-middle">
                    {formatCurrency(item.precio_unitario)}
                  </td>
                  <td className="text-end align-middle">
                    <strong>{formatCurrency(item.subtotal)}</strong>
                  </td>
                </tr>
              ))}
            </tbody>
            <tfoot className="table-secondary">
              <tr>
                <td colSpan="3" className="text-end">
                  <strong>TOTAL:</strong>
                </td>
                <td className="text-end">
                  <strong className="fs-5">
                    {formatCurrency(calcularTotal())}
                  </strong>
                </td>
              </tr>
            </tfoot>
          </Table>
        </Card.Body>
      </Card>

      {/* Información Adicional */}
      <Card>
        <Card.Header>
          <h5 className="mb-0">
            <i className="fas fa-calendar-check me-2"></i>
            Estado de Facturación
          </h5>
        </Card.Header>
        <Card.Body>
          <Row>
            <Col md={6}>
              <p><strong>Permite Facturar Hoy:</strong>{' '}
                <Badge bg={orden.permite_facturar_hoy ? 'info' : 'warning'}>
                  {orden.permite_facturar_hoy ? (
                    <>
                      <i className="fas fa-file-invoice me-1"></i>Sí
                    </>
                  ) : (
                    <>
                      <i className="fas fa-ban me-1"></i>No
                    </>
                  )}
                </Badge>
              </p>
            </Col>
            <Col md={6}>
              {orden.usuario && (
                <p><strong>Creado por:</strong> {orden.usuario.usuario}</p>
              )}
              {orden.created_at && (
                <p><strong>Fecha Creación:</strong> {formatDate(orden.created_at)}</p>
              )}
            </Col>
          </Row>
        </Card.Body>
      </Card>
    </div>
  );
};

export default OrdenServicioDetailView;
