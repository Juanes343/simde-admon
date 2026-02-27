import React, { useEffect, useState } from 'react';
import { Card, Table, Button, Form, Row, Col, Badge, Spinner, Pagination, Modal } from 'react-bootstrap';
import { useNavigate } from 'react-router-dom';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faList, faTrash, faPaperPlane, faSync, faFilePdf, faFileCode, faFileArchive, faCheckCircle, faRedo } from '@fortawesome/free-solid-svg-icons';
import notaCreditoService from '../../../services/notaCreditoService';
import { formatCurrency } from '../../../utils/formatters';
import Swal from 'sweetalert2';

const NotasCreditoListView = () => {
  const navigate = useNavigate();
  const [notas, setNotas] = useState([]);
  const [loading, setLoading] = useState(false);
  const [pagination, setPagination] = useState({});
  const [showAuditModal, setShowAuditModal] = useState(false);
  const [auditDetail, setAuditDetail] = useState(null);
  const [filters, setFilters] = useState({
    fechaDesde: '',
    fechaHasta: '',
    estado: '',
    prefijo: '',
    empresa_id: '01'
  });

  useEffect(() => {
    loadNotas();
  }, [filters]);

  const loadNotas = async (page = 1) => {
    setLoading(true);
    try {
      const data = await notaCreditoService.getNotas({ page, ...filters });
      setNotas(data.data || []);
      setPagination({
        current_page: data.current_page,
        last_page: data.last_page,
        total: data.total
      });
    } catch (error) {
      console.error("Error al cargar notas:", error);
      Swal.fire('Error', 'No se pudieron cargar las notas credito', 'error');
    } finally {
      setLoading(false);
    }
  };

  const handleEnviarNota = async (nota) => {
    const result = await Swal.fire({
      title: '¿Enviar a DataIco?',
      text: `Se enviará la nota ${nota.prefijo} ${nota.nota_credito_id} a DataIco.`,
      icon: 'info',
      showCancelButton: true,
      confirmButtonText: 'Sí, Enviar',
      cancelButtonText: 'Cancelar'
    });

    if (result.isConfirmed) {
      Swal.fire({
        title: 'Enviando nota...',
        html: '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Cargando...</span></div><p class="mt-3">Por favor espera mientras se envía la nota a DataIco.</p>',
        icon: undefined,
        allowOutsideClick: false,
        allowEscapeKey: false,
        didOpen: async () => {
          try {
            const response = await notaCreditoService.enviarNota(nota.id);
            
            if (response.debug) {
                console.log("JSON PREVISUALIZACION:", response.payload);
                Swal.fire({
                    title: 'PREVISUALIZACIÓN JSON (DEBUG)',
                    html: `<div class="text-start">
                            <p><b>Endpoint:</b> /${response.endpoint_target}</p>
                            <pre style="background: #f4f4f4; padding: 10px; font-size: 11px; max-height: 400px; overflow-y: auto;">${JSON.stringify(response.payload, null, 2)}</pre>
                           </div>`,
                    icon: 'warning',
                    width: '800px'
                });
                return;
            }

            const cufe = response.data?.cufe || 'N/A';
            const dianStatus = response.data?.respuesta_dataico?.dian_status || 'PENDIENTE';
            
            Swal.fire({
              title: '✓ Enviado con éxito',
              html: `<p>Nota enviada a DataIco correctamente.</p>
                     <p><b>Estado DIAN:</b> ${dianStatus}</p>
                     <small class="text-muted">CUFE: ${cufe}</small>`,
              icon: 'success'
            });
            
            loadNotas(pagination.current_page);
          } catch (error) {
            const errorData = error.response?.data;
            const errorMessage = errorData?.message || errorData?.error || 'No se pudo procesar la nota electrónica';
            const payload = errorData?.payload;

            Swal.fire({
              title: '✗ Error de Validación',
              html: `<div class="text-start">
                      <p class="text-danger"><b>Mensaje:</b> ${errorMessage}</p>
                      ${payload ? `
                      <hr />
                      <p><b>JSON Enviado (Debug):</b></p>
                      <pre style="background: #f4f4f4; padding: 10px; font-size: 11px; max-height: 300px; overflow-y: auto;">${JSON.stringify(payload, null, 2)}</pre>
                      ` : ''}
                     </div>`,
              icon: 'error',
              width: '700px'
            });
          }
        }
      });
    }
  };

  const handleViewAudit = (nota) => {
    if (nota.respuesta_dataico) {
      setAuditDetail(nota.respuesta_dataico);
      setShowAuditModal(true);
    } else {
      Swal.fire('Información', 'No hay detalles de auditoría disponibles', 'info');
    }
  };

  const handleDownloadPdf = async (nota) => {
    if (!nota.uuid) {
      Swal.fire('Información', 'PDF no disponible para esta nota', 'info');
      return;
    }

    Swal.fire({
      title: 'Descargando PDF...',
      html: '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Cargando...</span></div><p class="mt-3">Por favor espera mientras se descarga el PDF.</p>',
      icon: undefined,
      allowOutsideClick: false,
      allowEscapeKey: false,
      didOpen: async () => {
        try {
          const response = await notaCreditoService.descargarPdf(nota.id);
          const blob = new Blob([response], { type: 'application/pdf' });
          const url = window.URL.createObjectURL(blob);
          const link = document.createElement('a');
          link.href = url;
          link.setAttribute('download', `nota_${nota.prefijo}_${nota.nota_credito_id}.pdf`);
          document.body.appendChild(link);
          link.click();
          link.parentNode.removeChild(link);
          window.URL.revokeObjectURL(url);
          Swal.close();
        } catch (error) {
          Swal.fire('Error', 'No se pudo descargar el PDF. Intenta nuevamente.', 'error');
        }
      }
    });
  };

  const handleDownloadXml = async (nota) => {
    if (!nota.uuid) {
      Swal.fire('Información', 'XML no disponible para esta nota', 'info');
      return;
    }

    Swal.fire({
      title: 'Descargando XML...',
      html: '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Cargando...</span></div><p class="mt-3">Por favor espera mientras se descarga el XML.</p>',
      icon: undefined,
      allowOutsideClick: false,
      allowEscapeKey: false,
      didOpen: async () => {
        try {
          const response = await notaCreditoService.descargarXml(nota.id);
          const blob = new Blob([response], { type: 'application/xml' });
          const url = window.URL.createObjectURL(blob);
          const link = document.createElement('a');
          link.href = url;
          link.setAttribute('download', `nota_${nota.prefijo}_${nota.nota_credito_id}.xml`);
          document.body.appendChild(link);
          link.click();
          link.parentNode.removeChild(link);
          window.URL.revokeObjectURL(url);
          Swal.close();
        } catch (error) {
          Swal.fire('Error', 'No se pudo descargar el XML. Intenta nuevamente.', 'error');
        }
      }
    });
  };

  const handleDownloadZip = async (nota) => {
    if (!nota.uuid) {
      Swal.fire('Información', 'ZIP no disponible para esta nota', 'info');
      return;
    }

    Swal.fire({
      title: 'Descargando ZIP...',
      html: '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Cargando...</span></div><p class="mt-3">Por favor espera mientras se descarga el ZIP.</p>',
      icon: undefined,
      allowOutsideClick: false,
      allowEscapeKey: false,
      didOpen: async () => {
        try {
          const response = await notaCreditoService.descargarZip(nota.id);
          const blob = new Blob([response], { type: 'application/zip' });
          const url = window.URL.createObjectURL(blob);
          const link = document.createElement('a');
          link.href = url;
          link.setAttribute('download', `${nota.prefijo}-${nota.nota_credito_id}.zip`);
          document.body.appendChild(link);
          link.click();
          link.parentNode.removeChild(link);
          window.URL.revokeObjectURL(url);
        
          Swal.fire({
            title: '✓ ZIP Descargado',
            text: `Archivo ${nota.prefijo}-${nota.nota_credito_id}.zip descargado correctamente.`,
            icon: 'success'
          });
        } catch (error) {
          Swal.fire('Error', 'No se pudo descargar el ZIP. Intenta nuevamente.', 'error');
        }
      }
    });
  };

  const getEstadoBadge = (nota) => {
    const dianStatus = nota.respuesta_dataico?.dian_status;
    
    if (!dianStatus) {
      if (nota.estado === 'PENDIENTE') {
        return <Badge bg="warning" text="dark">PENDIENTE ENVÍO</Badge>;
      }
      return <Badge bg="secondary">{nota.estado}</Badge>;
    }

    switch (dianStatus) {
      case 'DIAN_ACEPTADO':
        return <Badge bg="success">DIAN ACEPTADA</Badge>;
      case 'DIAN_RECHAZADO':
        return <Badge bg="danger">DIAN RECHAZADA</Badge>;
      case 'DIAN_EN_PROCESO':
        return <Badge bg="info">EN VALIDACIÓN</Badge>;
      case 'ERROR':
        return <Badge bg="danger">ERROR EN ENVÍO</Badge>;
      default:
        return <Badge bg="secondary">ENVIADA</Badge>;
    }
  };

  const getDianStatusBadge = (nota) => {
    const audit = nota.respuesta_dataico;
    if (!audit) return null;

    return (
      <div className="mt-1">
        <small className="d-block">
          <Badge bg="light" text="dark" className="me-1">
            DIAN: {audit.dian_status || 'N/A'}
          </Badge>
        </small>
        {audit.customer_status && (
          <small className="d-block">
            <Badge bg="light" text="dark" className="me-1">
              Cliente: {audit.customer_status}
            </Badge>
          </small>
        )}
        {audit.email_status && (
          <small className="d-block">
            <Badge bg="light" text="dark">
              Email: {audit.email_status}
            </Badge>
          </small>
        )}
      </div>
    );
  };

  return (
    <div className="container-fluid py-4">
      <Card className="shadow-sm">
        <Card.Header className="bg-primary text-white d-flex justify-content-between align-items-center">
          <h5 className="mb-0">Histórico de Notas Crédito / Débito</h5>
          <div>
            <Button variant="light" size="sm" className="me-2" onClick={() => navigate('/notas')}>
              <FontAwesomeIcon icon={faList} className="me-2" />Crear Nota
            </Button>
            <Button variant="light" size="sm" onClick={() => navigate('/dashboard')}>
              <i className="fas fa-arrow-left me-2"></i>Volver al Dashboard
            </Button>
          </div>
        </Card.Header>
        <Card.Body>
          <Form className="mb-4">
            <Row className="align-items-end">
              <Col md={3}>
                <Form.Group>
                  <Form.Label>Desde</Form.Label>
                  <Form.Control
                    type="date"
                    value={filters.fechaDesde}
                    onChange={(e) => setFilters({ ...filters, fechaDesde: e.target.value })}
                  />
                </Form.Group>
              </Col>
              <Col md={3}>
                <Form.Group>
                  <Form.Label>Hasta</Form.Label>
                  <Form.Control
                    type="date"
                    value={filters.fechaHasta}
                    onChange={(e) => setFilters({ ...filters, fechaHasta: e.target.value })}
                  />
                </Form.Group>
              </Col>
              <Col md={3}>
                <Form.Group>
                  <Form.Label>Estado</Form.Label>
                  <Form.Select value={filters.estado} onChange={(e) => setFilters({ ...filters, estado: e.target.value })}>
                    <option value="">Todos</option>
                    <option value="PENDIENTE">Pendiente</option>
                    <option value="ENVIADO">Enviado</option>
                    <option value="ACEPTADO">Aceptado</option>
                    <option value="RECHAZADO">Rechazado</option>
                  </Form.Select>
                </Form.Group>
              </Col>
              <Col md={3}>
                <Form.Group>
                  <Form.Label>Prefijo</Form.Label>
                  <Form.Control
                    type="text"
                    value={filters.prefijo}
                    onChange={(e) => setFilters({ ...filters, prefijo: e.target.value })}
                    placeholder="Ej: NC"
                  />
                </Form.Group>
              </Col>
            </Row>
          </Form>

          {loading ? (
            <div className="text-center py-5">
              <Spinner animation="border" variant="primary" />
            </div>
          ) : (
            <Table responsive striped hover className="mt-3">
              <thead className="table-dark">
                <tr>
                  <th>Nota</th>
                  <th>Factura Ref.</th>
                  <th>Fecha</th>
                  <th className="text-end">Valor</th>
                  <th className="text-center">Estado</th>
                  <th className="text-center">Estado DataIco</th>
                  <th className="text-center">Acciones</th>
                </tr>
              </thead>
              <tbody>
                {notas.length > 0 ? (
                  notas.map((n) => (
                    <tr key={n.id}>
                      <td>
                        <strong>{n.prefijo} {n.nota_credito_id}</strong>
                        <br/>
                        <small className="text-muted">{n.tipo_nota}</small>
                      </td>
                      <td>{n.prefijo_factura} {n.factura_fiscal}</td>
                      <td>{n.created_at ? new Date(n.created_at).toLocaleDateString('es-CO') : 'N/A'}</td>
                      <td className="text-end fw-bold">{formatCurrency(n.valor_nota || 0)}</td>
                      <td className="text-center">
                        {getEstadoBadge(n)}
                      </td>
                      <td className="text-center">
                        {getDianStatusBadge(n)}
                      </td>
                      <td className="text-center">
                        {n.estado === 'PENDIENTE' && !n.cufe && (
                          <Button 
                            size="sm" 
                            variant="success" 
                            title="Enviar a DataIco (DIAN)"
                            onClick={() => handleEnviarNota(n)}
                            className="me-1"
                          >
                            <FontAwesomeIcon icon={faPaperPlane} className="me-1" />Enviar
                          </Button>
                        )}

                        {n.respuesta_dataico?.dian_status === 'DIAN_ACEPTADO' && (
                          <>
                            <Button 
                              size="sm" 
                              variant="info" 
                              title="Descargar PDF"
                              onClick={() => handleDownloadPdf(n)}
                              className="me-1"
                            >
                              <FontAwesomeIcon icon={faFilePdf} />
                            </Button>
                            <Button 
                              size="sm" 
                              variant="info" 
                              title="Descargar XML"
                              onClick={() => handleDownloadXml(n)}
                              className="me-1"
                            >
                              <FontAwesomeIcon icon={faFileCode} />
                            </Button>
                            <Button 
                              size="sm" 
                              variant="info" 
                              title="Descargar ZIP (PDF + XML)"
                              onClick={() => handleDownloadZip(n)}
                              className="me-1"
                            >
                              <FontAwesomeIcon icon={faFileArchive} />
                            </Button>
                            <Button 
                              size="sm" 
                              variant="outline-info" 
                              title="Ver Auditoría"
                              onClick={() => handleViewAudit(n)}
                            >
                              <FontAwesomeIcon icon={faCheckCircle} />
                            </Button>
                          </>
                        )}

                        {n.respuesta_dataico?.dian_status === 'ERROR' && (
                          <Button 
                            size="sm" 
                            variant="warning" 
                            title="Reintentar Envío"
                            onClick={() => handleEnviarNota(n)}
                          >
                            <FontAwesomeIcon icon={faRedo} className="me-1" />Reintentar
                          </Button>
                        )}
                      </td>
                    </tr>
                  ))
                ) : (
                  <tr>
                    <td colSpan="7" className="text-center py-4">No se encontraron notas.</td>
                  </tr>
                )}
              </tbody>
            </Table>
          )}

          {pagination.last_page > 1 && (
            <div className="d-flex justify-content-center mt-4">
              <Pagination>
                <Pagination.First onClick={() => loadNotas(1)} disabled={pagination.current_page === 1} />
                <Pagination.Prev onClick={() => loadNotas(pagination.current_page - 1)} disabled={pagination.current_page === 1} />
                {[...Array(pagination.last_page).keys()].map((num) => (
                  <Pagination.Item
                    key={num + 1}
                    active={num + 1 === pagination.current_page}
                    onClick={() => loadNotas(num + 1)}
                  >
                    {num + 1}
                  </Pagination.Item>
                ))}
                <Pagination.Next onClick={() => loadNotas(pagination.current_page + 1)} disabled={pagination.current_page === pagination.last_page} />
                <Pagination.Last onClick={() => loadNotas(pagination.last_page)} disabled={pagination.current_page === pagination.last_page} />
              </Pagination>
            </div>
          )}
        </Card.Body>
      </Card>

      {/* Modal para detalles de auditoría */}
      <Modal show={showAuditModal} onHide={() => setShowAuditModal(false)} size="lg">
        <Modal.Header closeButton>
          <Modal.Title>Detalles de Auditoría DataIco</Modal.Title>
        </Modal.Header>
        <Modal.Body>
          {auditDetail ? (
            <div>
              <div className="row mb-3">
                <div className="col-md-6">
                  <h6 className="text-muted">Número de Nota</h6>
                  <p className="fw-bold">{auditDetail.number}</p>
                </div>
                <div className="col-md-6">
                  <h6 className="text-muted">UUID</h6>
                  <p className="fw-bold text-break">{auditDetail.uuid}</p>
                </div>
              </div>

              <div className="row mb-3">
                <div className="col-md-6">
                  <h6 className="text-muted">CUFE</h6>
                  <p className="fw-bold text-break" style={{ fontSize: '0.85rem' }}>{auditDetail.cufe}</p>
                </div>
                <div className="col-md-6">
                  <h6 className="text-muted">Fecha de Emisión</h6>
                  <p className="fw-bold">{auditDetail.issue_date}</p>
                </div>
              </div>

              <div className="row mb-3">
                <div className="col-md-4">
                  <h6 className="text-muted">Estado DIAN</h6>
                  <Badge bg={auditDetail.dian_status === 'DIAN_ACEPTADO' ? 'success' : 'danger'}>
                    {auditDetail.dian_status}
                  </Badge>
                </div>
                <div className="col-md-4">
                  <h6 className="text-muted">Estado Cliente</h6>
                  <Badge bg="info">{auditDetail.customer_status || 'N/A'}</Badge>
                </div>
                <div className="col-md-4">
                  <h6 className="text-muted">Estado Email</h6>
                  <Badge bg="info">{auditDetail.email_status || 'N/A'}</Badge>
                </div>
              </div>

              {auditDetail.qrcode && (
                <div className="mb-3">
                  <h6 className="text-muted">Código QR</h6>
                  <pre style={{ background: '#f4f4f4', padding: '10px', fontSize: '0.85rem', maxHeight: '150px', overflow: 'auto' }}>
                    {auditDetail.qrcode}
                  </pre>
                </div>
              )}

              <div className="alert alert-info">
                <strong>Enlaces de Descarga:</strong>
                <ul className="mb-0 mt-2">
                  {auditDetail.pdf && <li><a href={auditDetail.pdf} target="_blank" rel="noopener noreferrer">Descargar PDF</a></li>}
                  {auditDetail.xml && <li><a href={auditDetail.xml} target="_blank" rel="noopener noreferrer">Descargar XML</a></li>}
                </ul>
              </div>
            </div>
          ) : (
            <p>Cargando detalles...</p>
          )}
        </Modal.Body>
        <Modal.Footer>
          <Button variant="secondary" onClick={() => setShowAuditModal(false)}>
            Cerrar
          </Button>
        </Modal.Footer>
      </Modal>
    </div>
  );
};

export default NotasCreditoListView;
