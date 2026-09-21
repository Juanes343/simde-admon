import React from 'react';
import { Card, Row, Col, Badge, Table, Button } from 'react-bootstrap';

const ESTADO_CONFIG = {
  borrador:   { label: 'Borrador',   variant: 'secondary' },
  enviada:    { label: 'Enviada',    variant: 'info'      },
  aprobada:   { label: 'Aprobada',   variant: 'success'   },
  rechazada:  { label: 'Rechazada',  variant: 'danger'    },
  vencida:    { label: 'Vencida',    variant: 'warning'   },
  convertida: { label: 'Convertida', variant: 'primary'   },
};

const CotizacionDetailView = ({ cotizacion, onDescargarPdf, onEnviarEmail }) => {
  if (!cotizacion) return null;

  const formatCurrency = (v) =>
    new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', minimumFractionDigits: 0 }).format(v ?? 0);

  const formatDate = (d) => {
    if (!d) return '—';
    const [year, month, day] = String(d).split('T')[0].split('-');
    return `${day}/${month}/${year}`;
  };

  const formatDateTime = (d) => {
    if (!d) return '';
    const date = new Date(d);
    return Number.isNaN(date.getTime())
      ? ''
      : date.toLocaleString('es-CO', { dateStyle: 'short', timeStyle: 'short' });
  };

  const estadoCfg = ESTADO_CONFIG[cotizacion.sw_estado] || { label: cotizacion.sw_estado, variant: 'secondary' };
  const tercero   = cotizacion.tercero;

  return (
    <>
      {/* ── Cabecera ── */}
      <Card className="mb-3 shadow-sm">
        <Card.Header className="d-flex justify-content-between align-items-center">
          <h5 className="mb-0">
            <i className="fas fa-file-alt me-2 text-primary"></i>
            {cotizacion.numero_cotizacion}
          </h5>
          <div className="d-flex align-items-center gap-2">
            <Badge bg={estadoCfg.variant} className="fs-6">{estadoCfg.label}</Badge>
            {onDescargarPdf && (
              <Button variant="outline-secondary" size="sm" title="Descargar PDF" onClick={onDescargarPdf}>
                <i className="fas fa-file-pdf me-1"></i>PDF
              </Button>
            )}
            {onEnviarEmail && (
              <Button variant="outline-dark" size="sm" title="Enviar por correo" onClick={onEnviarEmail}>
                <i className="fas fa-envelope me-1"></i>Enviar
              </Button>
            )}
          </div>
        </Card.Header>
        <Card.Body>
          <Row>
            <Col md={6}>
              <p className="mb-1 text-muted small text-uppercase fw-bold">Cliente</p>
              <p className="mb-3 fs-5">
                {tercero?.nombre_tercero || cotizacion.tercero_id}
                <span className="text-muted ms-2 small">({cotizacion.tipo_id_tercero} {cotizacion.tercero_id})</span>
              </p>
            </Col>
            <Col md={3}>
              <p className="mb-1 text-muted small text-uppercase fw-bold">Fecha Emisión</p>
              <p className="mb-3">{formatDate(cotizacion.fecha_emision)}</p>
            </Col>
            <Col md={3}>
              <p className="mb-1 text-muted small text-uppercase fw-bold">Vencimiento</p>
              <p className="mb-3">{formatDate(cotizacion.fecha_vencimiento)}</p>
            </Col>
            <Col md={3}>
              <p className="mb-1 text-muted small text-uppercase fw-bold">Método de Pago</p>
              <p className="mb-3">{cotizacion.metodo_pago || '—'}</p>
            </Col>
            <Col md={3}>
              <p className="mb-1 text-muted small text-uppercase fw-bold">Tipo de Pago</p>
              <p className="mb-3">{cotizacion.tipo_pago || '—'}</p>
            </Col>
            <Col md={3}>
              <p className="mb-1 text-muted small text-uppercase fw-bold">Orden de Compra</p>
              <p className="mb-3">{cotizacion.orden_compra || '—'}</p>
            </Col>
            {cotizacion.orden_servicio_id && (
              <Col md={3}>
                <p className="mb-1 text-muted small text-uppercase fw-bold">Orden de Servicio</p>
                <p className="mb-3">
                  <Badge bg="primary">ID #{cotizacion.orden_servicio_id}</Badge>
                </p>
              </Col>
            )}
            {cotizacion.fecha_firma && (
              <Col md={6}>
                <p className="mb-1 text-muted small text-uppercase fw-bold">Aprobada y firmada por el cliente</p>
                <p className="mb-3">
                  <i className="fas fa-file-signature text-success me-1"></i>
                  {cotizacion.firmante_nombre}
                  {cotizacion.firmante_documento && <span className="text-muted"> (Doc. {cotizacion.firmante_documento})</span>}
                  <span className="text-muted small ms-2">{formatDateTime(cotizacion.fecha_firma)}</span>
                </p>
              </Col>
            )}
            {cotizacion.fecha_rechazo && (
              <Col md={6}>
                <p className="mb-1 text-muted small text-uppercase fw-bold">Rechazada por el cliente</p>
                <p className="mb-3">
                  <i className="fas fa-times-circle text-danger me-1"></i>
                  {cotizacion.motivo_rechazo || 'Sin motivo indicado'}
                  <span className="text-muted small ms-2">{formatDateTime(cotizacion.fecha_rechazo)}</span>
                </p>
              </Col>
            )}
          </Row>
        </Card.Body>
      </Card>

      {/* ── Ítems ── */}
      <Card className="mb-3 shadow-sm">
        <Card.Header className="fw-bold">
          <i className="fas fa-list me-2 text-success"></i>Ítems
        </Card.Header>
        <Card.Body className="p-0">
          <div className="table-responsive">
            <Table size="sm" className="align-middle mb-0">
              <thead className="table-light">
                <tr>
                  <th>#</th>
                  <th>REF</th>
                  <th>Descripción</th>
                  <th className="text-end">Cantidad</th>
                  <th className="text-end">Precio</th>
                  <th className="text-end">Desc %</th>
                  <th className="text-end">Ret.F %</th>
                  <th>Impuesto</th>
                  <th className="text-end">Subtotal</th>
                  <th className="text-end">Total</th>
                </tr>
              </thead>
              <tbody>
                {(cotizacion.items || []).map((item, i) => {
                  const imp = item.impuesto;
                  return (
                    <tr key={item.item_id || i}>
                      <td>{i + 1}</td>
                      <td><small className="text-muted">{item.referencia || '—'}</small></td>
                      <td>{item.descripcion}</td>
                      <td className="text-end">{parseFloat(item.cantidad).toLocaleString('es-CO')}</td>
                      <td className="text-end">{formatCurrency(item.precio_unitario)}</td>
                      <td className="text-end">{item.porcentaje_descuento || 0}%</td>
                      <td className="text-end">{item.porcentaje_ret_fuente ?? '—'}</td>
                      <td>
                        {imp
                          ? <Badge bg="info">{imp.nombre} {imp.porcentaje}%</Badge>
                          : <span className="text-muted small">Excluido</span>
                        }
                      </td>
                      <td className="text-end">{formatCurrency(item.subtotal)}</td>
                      <td className="text-end fw-bold">{formatCurrency(item.total)}</td>
                    </tr>
                  );
                })}
              </tbody>
            </Table>
          </div>
        </Card.Body>
      </Card>

      {/* ── Totales y Notas ── */}
      <Row>
        {cotizacion.notas && (
          <Col md={6}>
            <Card className="mb-3 shadow-sm">
              <Card.Header className="fw-bold">
                <i className="fas fa-sticky-note me-2 text-warning"></i>Notas
              </Card.Header>
              <Card.Body>
                <p className="mb-0" style={{ whiteSpace: 'pre-wrap' }}>{cotizacion.notas}</p>
              </Card.Body>
            </Card>
          </Col>
        )}

        <Col md={cotizacion.notas ? 6 : 12}>
          <Card className="mb-3 shadow-sm">
            <Card.Body>
              <div className="d-flex justify-content-between mb-1">
                <span className="text-muted">Subtotal</span>
                <span>{formatCurrency(cotizacion.subtotal)}</span>
              </div>
              {parseFloat(cotizacion.descuento_total) > 0 && (
                <div className="d-flex justify-content-between mb-1 text-danger">
                  <span>— Descuento</span>
                  <span>- {formatCurrency(cotizacion.descuento_total)}</span>
                </div>
              )}
              {parseFloat(cotizacion.impuestos_total) > 0 && (
                <div className="d-flex justify-content-between mb-1 text-info">
                  <span>+ Impuestos</span>
                  <span>{formatCurrency(cotizacion.impuestos_total)}</span>
                </div>
              )}
              <hr />
              <div className="d-flex justify-content-between fw-bold fs-5">
                <span>Total</span>
                <span className="text-primary">{formatCurrency(cotizacion.total)}</span>
              </div>
            </Card.Body>
          </Card>
        </Col>
      </Row>
    </>
  );
};

export default CotizacionDetailView;
