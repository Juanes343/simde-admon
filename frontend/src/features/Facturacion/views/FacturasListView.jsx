import React, { useState, useEffect } from 'react';
import { Card, Table, Button, Form, Row, Col, Badge, Spinner, Pagination, Modal } from 'react-bootstrap';
import { useNavigate } from 'react-router-dom';
import facturacionService from '../../../services/facturacionService';
import { formatCurrency } from '../../../utils/formatters';
import Swal from 'sweetalert2';

const FacturasListView = () => {
  const navigate = useNavigate();
  const [facturas, setFacturas] = useState([]);
  const [loading, setLoading] = useState(false);
  const [pagination, setPagination] = useState({});
  const [auditCache, setAuditCache] = useState({}); // Cache de auditorías
  const [showAuditModal, setShowAuditModal] = useState(false);
  const [auditDetail, setAuditDetail] = useState(null);
  const [filters, setFilters] = useState({
    lapso_inicio: '',
    lapso_fin: '',
    tercero: ''
  });

  useEffect(() => {
    loadFacturas();
  }, [filters]);

  const loadFacturas = async (page = 1) => {
    setLoading(true);
    try {
      const data = await facturacionService.getFacturas(page, filters);
      setFacturas(data.data);
      setPagination({
        current_page: data.current_page,
        last_page: data.last_page,
        total: data.total
      });

      // Cargar auditorías para cada factura que tenga CUFE
      const newAuditCache = { ...auditCache };
      for (const factura of data.data) {
        if (factura.cufe && !newAuditCache[factura.cufe]) {
          try {
            const auditResponse = await facturacionService.getAuditByCufe(factura.cufe);
            if (auditResponse.success) {
              newAuditCache[factura.cufe] = auditResponse.data;
            }
          } catch (error) {
            console.warn(`No se pudo cargar auditoría para CUFE ${factura.cufe}`);
          }
        }
      }
      setAuditCache(newAuditCache);
    } catch (error) {
      console.error("Error cargando facturas", error);
    } finally {
      setLoading(false);
    }
  };

  const getAuditInfo = (factura) => {
    if (!factura.cufe) return null;
    return auditCache[factura.cufe];
  };

  const handleEnviarDataIco = async (factura) => {
    const result = await Swal.fire({
      title: '¿Enviar a DataIco?',
      text: `Se enviará la factura ${factura.prefijo} ${factura.numero_factura} como facturación electrónica.`,
      icon: 'info',
      showCancelButton: true,
      confirmButtonText: 'Sí, Enviar',
      cancelButtonText: 'Cancelar'
    });

    if (result.isConfirmed) {
      setLoading(true);
      try {
        const response = await facturacionService.enviarDataIco(factura.factura_fiscal_id);
        
        // Si el backend retornó el modo debug/payload
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
            setLoading(false);
            return;
        }

        const cufe = response.data?.cufe || 'N/A';
        const dianStatus = response.data?.dian_status || 'PENDIENTE';
        
        Swal.fire({
          title: 'Enviado con éxito',
          html: `<p>Factura enviada a DataIco.</p>
                 <p><b>Estado DIAN:</b> ${dianStatus}</p>
                 <small class="text-muted">CUFE: ${cufe}</small>`,
          icon: 'success'
        });
        
        // Recargar facturas para actualizar estados
        loadFacturas(pagination.current_page);
      } catch (error) {
        const errorData = error.response?.data;
        const errorMessage = errorData?.message || errorData?.error || 'No se pudo procesar la factura electrónica';
        const payload = errorData?.payload;

        Swal.fire({
          title: 'Error de Validación',
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
      } finally {
        setLoading(false);
      }
    }
  };

  const handleViewAudit = async (factura) => {
    const audit = getAuditInfo(factura);
    if (!audit && factura.cufe) {
      try {
        const response = await facturacionService.getAuditByCufe(factura.cufe);
        if (response.success) {
          setAuditDetail(response.data);
          setShowAuditModal(true);
        }
      } catch (error) {
        Swal.fire('Error', 'No se pudo cargar los detalles de auditoría', 'error');
      }
    } else {
      setAuditDetail(audit);
      setShowAuditModal(true);
    }
  };

  const handleDownloadPdf = async (factura) => {
    const audit = getAuditInfo(factura);
    if (audit && audit.pdf_url) {
      window.open(audit.pdf_url, '_blank');
    } else {
      Swal.fire('Información', 'PDF no disponible para esta factura', 'info');
    }
  };

  const handleDownloadXml = async (factura) => {
    const audit = getAuditInfo(factura);
    if (audit && audit.xml_url) {
      window.open(audit.xml_url, '_blank');
    } else {
      Swal.fire('Información', 'XML no disponible para esta factura', 'info');
    }
  };

  const getEstadoBadge = (factura) => {
    const audit = getAuditInfo(factura);
    
    if (!audit) {
      if (factura.estado === '1') {
        return <Badge bg="warning" text="dark">PENDIENTE ENVÍO</Badge>;
      }
      return <Badge bg="danger">ANULADA</Badge>;
    }

    switch (audit.dian_status) {
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

  const getDianStatusBadge = (factura) => {
    const audit = getAuditInfo(factura);
    if (!audit) return null;

    return (
      <div className="mt-1">
        <small className="d-block">
          <Badge bg="light" text="dark" className="me-1">
            DIAN: {audit.dian_status}
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
          <h5 className="mb-0">Histórico de Facturas Generadas</h5>
          <Button variant="light" size="sm" onClick={() => navigate('/dashboard')}>
            <i className="fas fa-arrow-left me-2"></i>Volver al Dashboard
          </Button>
        </Card.Header>
        <Card.Body>
          <Form className="mb-4">
            <Row className="align-items-end">
              <Col md={3}>
                <Form.Group>
                  <Form.Label>Desde</Form.Label>
                  <Form.Control 
                    type="date" 
                    value={filters.lapso_inicio}
                    onChange={(e) => setFilters({...filters, lapso_inicio: e.target.value})}
                  />
                </Form.Group>
              </Col>
              <Col md={3}>
                <Form.Group>
                  <Form.Label>Hasta</Form.Label>
                  <Form.Control 
                    type="date"
                    value={filters.lapso_fin}
                    onChange={(e) => setFilters({...filters, lapso_fin: e.target.value})}
                  />
                </Form.Group>
              </Col>
              <Col md={4}>
                <Form.Group>
                  <Form.Label>Tercero (Nombre/NIT)</Form.Label>
                  <Form.Control 
                    type="text"
                    placeholder="Buscar cliente..."
                    value={filters.tercero}
                    onChange={(e) => setFilters({...filters, tercero: e.target.value})}
                  />
                </Form.Group>
              </Col>
              <Col md={2}>
                <Button variant="outline-primary" className="w-100" onClick={() => loadFacturas()}>
                  <i className="fas fa-search me-2"></i>Refrescar
                </Button>
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
                  <th>Factura</th>
                  <th>Fecha</th>
                  <th>Tercero</th>
                  <th className="text-end">Total</th>
                  <th className="text-center">Estado</th>
                  <th className="text-center">Estado DataIco</th>
                  <th className="text-center">Acciones</th>
                </tr>
              </thead>
              <tbody>
                {facturas.length > 0 ? (
                  facturas.map(f => (
                    <tr key={f.factura_fiscal_id}>
                      <td><strong>{f.prefijo} {f.numero_factura}</strong></td>
                      <td>{new Date(f.fecha_registro).toLocaleDateString('es-CO')}</td>
                      <td>
                        <div className="fw-bold">{f.tercero?.nombre_tercero || 'N/A'}</div>
                        <small className="text-muted">{f.tipo_id_tercero} {f.tercero_id}</small>
                      </td>
                      <td className="text-end fw-bold">{formatCurrency(f.total_factura)}</td>
                      <td className="text-center">
                        <Badge bg={f.estado === '1' ? 'success' : 'danger'}>
                          {f.estado === '1' ? 'Generada' : 'Anulada'}
                        </Badge>
                      </td>
                      <td className="text-center">
                        {getEstadoBadge(f)}
                        {getDianStatusBadge(f)}
                      </td>
                      <td className="text-center">
                        {/* Si NO tiene CUFE, mostrar botón para enviar */}
                        {f.estado === '1' && !f.cufe && (
                          <Button 
                            size="sm" 
                            variant="success" 
                            title="Enviar a DataIco (DIAN)"
                            onClick={() => handleEnviarDataIco(f)}
                            className="me-1"
                          >
                            <i className="fas fa-paper-plane me-1"></i>
                            Enviar
                          </Button>
                        )}

                        {/* Si tiene CUFE y está aceptada, mostrar descargas y auditoría */}
                        {getAuditInfo(f)?.dian_status === 'DIAN_ACEPTADO' && (
                          <>
                            <Button 
                              size="sm" 
                              variant="info" 
                              title="Descargar PDF"
                              onClick={() => handleDownloadPdf(f)}
                              className="me-1"
                            >
                              <i className="fas fa-file-pdf"></i>
                            </Button>
                            <Button 
                              size="sm" 
                              variant="info" 
                              title="Descargar XML"
                              onClick={() => handleDownloadXml(f)}
                              className="me-1"
                            >
                              <i className="fas fa-file-code"></i>
                            </Button>
                            <Button 
                              size="sm" 
                              variant="outline-info" 
                              title="Ver Auditoría"
                              onClick={() => handleViewAudit(f)}
                            >
                              <i className="fas fa-check-circle"></i>
                            </Button>
                          </>
                        )}

                        {/* Si está en error, permitir reintentar */}
                        {getAuditInfo(f)?.dian_status === 'ERROR' && (
                          <Button 
                            size="sm" 
                            variant="warning" 
                            title="Reintentar Envío"
                            onClick={() => handleEnviarDataIco(f)}
                          >
                            <i className="fas fa-redo me-1"></i>
                            Reintentar
                          </Button>
                        )}
                      </td>
                    </tr>
                  ))
                ) : (
                  <tr>
                    <td colSpan="7" className="text-center py-4">No se encontraron facturas.</td>
                  </tr>
                )}
              </tbody>
            </Table>
          )}

          {pagination.last_page > 1 && (
            <div className="d-flex justify-content-center mt-4">
              <Pagination>
                <Pagination.First onClick={() => loadFacturas(1)} disabled={pagination.current_page === 1} />
                <Pagination.Prev onClick={() => loadFacturas(pagination.current_page - 1)} disabled={pagination.current_page === 1} />
                {[...Array(pagination.last_page).keys()].map(num => (
                  <Pagination.Item 
                    key={num + 1} 
                    active={num + 1 === pagination.current_page}
                    onClick={() => loadFacturas(num + 1)}
                  >
                    {num + 1}
                  </Pagination.Item>
                ))}
                <Pagination.Next onClick={() => loadFacturas(pagination.current_page + 1)} disabled={pagination.current_page === pagination.last_page} />
                <Pagination.Last onClick={() => loadFacturas(pagination.last_page)} disabled={pagination.current_page === pagination.last_page} />
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
                  <h6 className="text-muted">Número de Factura</h6>
                  <p className="fw-bold">{auditDetail.numero}</p>
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
                  <h6 className="text-muted">Fecha de Registro</h6>
                  <p className="fw-bold">{new Date(auditDetail.fecha_registro).toLocaleString('es-CO')}</p>
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
                  <Badge bg="info">{auditDetail.customer_status}</Badge>
                </div>
                <div className="col-md-4">
                  <h6 className="text-muted">Estado Email</h6>
                  <Badge bg="info">{auditDetail.email_status}</Badge>
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
                  {auditDetail.pdf_url && <li><a href={auditDetail.pdf_url} target="_blank" rel="noopener noreferrer">Descargar PDF</a></li>}
                  {auditDetail.xml_url && <li><a href={auditDetail.xml_url} target="_blank" rel="noopener noreferrer">Descargar XML</a></li>}
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

export default FacturasListView;
