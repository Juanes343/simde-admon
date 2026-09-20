import React from 'react';
import { Table, Badge, Button, Spinner } from 'react-bootstrap';

const ESTADO_CONFIG = {
  borrador:   { label: 'Borrador',   variant: 'secondary' },
  enviada:    { label: 'Enviada',    variant: 'info'      },
  aprobada:   { label: 'Aprobada',   variant: 'success'   },
  rechazada:  { label: 'Rechazada',  variant: 'danger'    },
  vencida:    { label: 'Vencida',    variant: 'warning'   },
  convertida: { label: 'Convertida', variant: 'primary'   },
};

const CotizacionesListView = ({
  cotizaciones,
  loading,
  onEdit,
  onDelete,
  onView,
  onCambiarEstado,
  onConvertir,
  onDescargarPdf,
  onEnviarEmail,
}) => {
  if (loading) {
    return (
      <div className="text-center py-5">
        <Spinner animation="border" variant="primary" />
        <p className="mt-2">Cargando cotizaciones...</p>
      </div>
    );
  }

  if (!cotizaciones || cotizaciones.length === 0) {
    return (
      <div className="text-center py-5 text-muted">
        <i className="fas fa-file-alt fa-3x mb-3"></i>
        <p>No se encontraron cotizaciones.</p>
      </div>
    );
  }

  const formatCurrency = (v) =>
    new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', minimumFractionDigits: 0 }).format(v ?? 0);

  const formatDate = (d) => {
    if (!d) return '—';
    const [year, month, day] = String(d).split('T')[0].split('-');
    return `${day}/${month}/${year}`;
  };

  const getNombreTercero = (c) => {
    if (c.tercero) return c.tercero.nombre_tercero || c.tercero.tercero_id;
    return c.tercero_id;
  };

  return (
    <div className="table-responsive">
      <Table hover size="sm" className="align-middle">
        <thead className="table-dark">
          <tr>
            <th>N° Cotización</th>
            <th>Cliente</th>
            <th>Fecha Emisión</th>
            <th>Vencimiento</th>
            <th>Total</th>
            <th>Estado</th>
            <th className="text-center">Acciones</th>
          </tr>
        </thead>
        <tbody>
          {cotizaciones.map((c) => {
            const estadoCfg = ESTADO_CONFIG[c.sw_estado] || { label: c.sw_estado, variant: 'secondary' };
            return (
              <tr key={c.cotizacion_id}>
                <td>
                  <strong>{c.numero_cotizacion}</strong>
                </td>
                <td>{getNombreTercero(c)}</td>
                <td>{formatDate(c.fecha_emision)}</td>
                <td>{formatDate(c.fecha_vencimiento)}</td>
                <td className="text-end">{formatCurrency(c.total)}</td>
                <td>
                  <Badge bg={estadoCfg.variant}>{estadoCfg.label}</Badge>
                </td>
                <td className="text-center">
                  <Button
                    variant="outline-primary"
                    size="sm"
                    className="me-1"
                    title="Ver detalle"
                    onClick={() => onView(c)}
                  >
                    <i className="fas fa-eye"></i>
                  </Button>

                  {c.sw_estado === 'borrador' && (
                    <Button
                      variant="outline-warning"
                      size="sm"
                      className="me-1"
                      title="Editar"
                      onClick={() => onEdit(c)}
                    >
                      <i className="fas fa-edit"></i>
                    </Button>
                  )}

                  {['borrador', 'enviada'].includes(c.sw_estado) && (
                    <Button
                      variant="outline-success"
                      size="sm"
                      className="me-1"
                      title="Aprobar"
                      onClick={() => onCambiarEstado(c, 'aprobada')}
                    >
                      <i className="fas fa-check"></i>
                    </Button>
                  )}

                  {c.sw_estado === 'aprobada' && !c.orden_servicio_id && (
                    <Button
                      variant="outline-info"
                      size="sm"
                      className="me-1"
                      title="Convertir a Orden de Servicio"
                      onClick={() => onConvertir(c)}
                    >
                      <i className="fas fa-exchange-alt"></i>
                    </Button>
                  )}

                  <Button
                    variant="outline-secondary"
                    size="sm"
                    className="me-1"
                    title="Descargar PDF"
                    onClick={() => onDescargarPdf(c)}
                  >
                    <i className="fas fa-file-pdf"></i>
                  </Button>

                  <Button
                    variant="outline-dark"
                    size="sm"
                    className="me-1"
                    title="Enviar por correo"
                    onClick={() => onEnviarEmail(c)}
                  >
                    <i className="fas fa-envelope"></i>
                  </Button>

                  {!['convertida'].includes(c.sw_estado) && (
                    <Button
                      variant="outline-danger"
                      size="sm"
                      title="Eliminar"
                      onClick={() => onDelete(c)}
                    >
                      <i className="fas fa-trash"></i>
                    </Button>
                  )}
                </td>
              </tr>
            );
          })}
        </tbody>
      </Table>
    </div>
  );
};

export default CotizacionesListView;
