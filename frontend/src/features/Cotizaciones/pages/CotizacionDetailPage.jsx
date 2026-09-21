import React, { useState, useEffect } from 'react';
import { Container, Row, Col, Button, Badge, Modal, Form } from 'react-bootstrap';
import { useNavigate, useParams } from 'react-router-dom';
import { toast } from 'react-toastify';
import MainLayout from '../../../components/Layout/MainLayout';
import BackToDashboard from '../../../components/BackToDashboard/BackToDashboard';
import CotizacionDetailView from '../views/CotizacionDetailView';
import EnviarEmailModal from '../views/EnviarEmailModal';
import cotizacionService from '../services/cotizacionService';

const ESTADOS_POSIBLES = [
  { value: 'enviada',   label: 'Marcar como Enviada',   variant: 'info'    },
  { value: 'aprobada',  label: 'Marcar como Aprobada',  variant: 'success' },
  { value: 'rechazada', label: 'Marcar como Rechazada', variant: 'danger'  },
  { value: 'vencida',   label: 'Marcar como Vencida',   variant: 'warning' },
];

const CotizacionDetailPage = () => {
  const navigate = useNavigate();
  const { id }   = useParams();

  const [cotizacion,  setCotizacion]  = useState(null);
  const [loading,     setLoading]     = useState(true);

  // ── Modal cambiar estado ───────────────────────────────────────────────────
  const [showEstadoModal, setShowEstadoModal] = useState(false);
  const [nuevoEstado,     setNuevoEstado]     = useState('');
  const [loadingEstado,   setLoadingEstado]   = useState(false);

  // ── Modal convertir ────────────────────────────────────────────────────────
  const [showConvertirModal, setShowConvertirModal] = useState(false);
  const [loadingConvertir,   setLoadingConvertir]   = useState(false);
  const [convertirData, setConvertirData] = useState({
    fecha_inicio:             '',
    fecha_fin:                '',
    periodo_facturacion_dias: '30',
    sw_prorroga_automatica:   '0',
    porcentaje_soltec:        '0',
    porcentaje_ret_fuente:    '0',
  });

  // ── Modal email ────────────────────────────────────────────────────────────
  const [showEmailModal, setShowEmailModal] = useState(false);
  const [loadingEmail,   setLoadingEmail]   = useState(false);

  const handleDescargarPdf = () => {
    cotizacionService.descargarPdf(id, cotizacion?.numero_cotizacion);
  };

  const handleEnviarEmail = () => setShowEmailModal(true);

  const handleEmailConfirm = async (email, adjuntos, datosOrden) => {
    try {
      setLoadingEmail(true);
      const res = await cotizacionService.enviarEmail(id, email, adjuntos, datosOrden);
      toast.success(res.message);
      setShowEmailModal(false);
      loadCotizacion();
    } catch (err) {
      toast.error(err.response?.data?.message || 'Error al enviar el correo');
    } finally {
      setLoadingEmail(false);
    }
  };

  useEffect(() => { loadCotizacion(); }, [id]);

  const loadCotizacion = async () => {
    try {
      setLoading(true);
      const data = await cotizacionService.getOne(id);
      setCotizacion(data);
    } catch (err) {
      toast.error('Error al cargar la cotización');
      navigate('/cotizaciones');
    } finally {
      setLoading(false);
    }
  };

  const handleCambiarEstado = async () => {
    try {
      setLoadingEstado(true);
      await cotizacionService.cambiarEstado(id, nuevoEstado);
      toast.success('Estado actualizado');
      setShowEstadoModal(false);
      loadCotizacion();
    } catch (err) {
      toast.error(err.response?.data?.message || 'Error al cambiar estado');
    } finally {
      setLoadingEstado(false);
    }
  };

  const handleConvertir = async () => {
    if (!convertirData.fecha_inicio || !convertirData.fecha_fin) {
      toast.warning('Debe indicar fecha inicio y fecha fin');
      return;
    }
    try {
      setLoadingConvertir(true);
      const res = await cotizacionService.convertirAOrden(id, convertirData);
      toast.success(`Convertida a Orden: ${res.numero_orden}`);
      setShowConvertirModal(false);
      loadCotizacion();
    } catch (err) {
      toast.error(err.response?.data?.message || 'Error al convertir');
    } finally {
      setLoadingConvertir(false);
    }
  };

  if (loading) {
    return (
      <MainLayout>
        <Container fluid className="py-4">
          <BackToDashboard />
          <div className="text-center py-5">
            <div className="spinner-border text-primary" role="status" />
            <p className="mt-2">Cargando cotización...</p>
          </div>
        </Container>
      </MainLayout>
    );
  }

  return (
    <MainLayout>
      <Container fluid className="py-4">
        <BackToDashboard />

        <Row className="mb-4 align-items-center">
          <Col>
            <h2>
              <i className="fas fa-file-alt me-2"></i>
              Detalle de Cotización
            </h2>
          </Col>
          <Col xs="auto" className="d-flex gap-2 flex-wrap">
            <Button variant="outline-secondary" onClick={() => navigate('/cotizaciones')}>
              <i className="fas fa-arrow-left me-1"></i>Volver
            </Button>

            {cotizacion?.sw_estado === 'borrador' && (
              <Button variant="warning" onClick={() => navigate(`/cotizaciones/edit/${id}`)}>
                <i className="fas fa-edit me-1"></i>Editar
              </Button>
            )}

            {cotizacion && !['convertida'].includes(cotizacion.sw_estado) && (
              <Button
                variant="outline-info"
                onClick={() => {
                  const siguiente = ESTADOS_POSIBLES.find((e) => e.value !== cotizacion.sw_estado);
                  setNuevoEstado(siguiente?.value || 'enviada');
                  setShowEstadoModal(true);
                }}
              >
                <i className="fas fa-exchange-alt me-1"></i>Cambiar Estado
              </Button>
            )}

            {cotizacion?.sw_estado === 'aprobada' && !cotizacion.orden_servicio_id && (
              <Button
                variant="success"
                onClick={() => {
                  const items = cotizacion?.items || [];
                  const maxRet = items.reduce((max, item) => {
                    const ret = parseFloat(item.porcentaje_ret_fuente) || 0;
                    return ret > max ? ret : max;
                  }, 0);
                  const maxSoltec = items.reduce((max, item) => {
                    const s = parseFloat(item.porcentaje_soltec) || 0;
                    return s > max ? s : max;
                  }, 0);
                  setConvertirData({
                    fecha_inicio:             '',
                    fecha_fin:                '',
                    periodo_facturacion_dias: '30',
                    sw_prorroga_automatica:   '0',
                    porcentaje_soltec:        String(maxSoltec),
                    porcentaje_ret_fuente:    String(maxRet),
                  });
                  setShowConvertirModal(true);
                }}
              >
                <i className="fas fa-file-contract me-1"></i>Crear Orden de Servicio
              </Button>
            )}
          </Col>
        </Row>

        <CotizacionDetailView
          cotizacion={cotizacion}
          onDescargarPdf={handleDescargarPdf}
          onEnviarEmail={handleEnviarEmail}
        />
      </Container>

      {/* Modal: Cambiar Estado */}
      <Modal show={showEstadoModal} onHide={() => setShowEstadoModal(false)} centered>
        <Modal.Header closeButton>
          <Modal.Title>Cambiar Estado</Modal.Title>
        </Modal.Header>
        <Modal.Body>
          <Form.Label className="fw-bold">Nuevo estado</Form.Label>
          <Form.Select value={nuevoEstado} onChange={(e) => setNuevoEstado(e.target.value)}>
            {ESTADOS_POSIBLES.filter((e) => e.value !== cotizacion?.sw_estado).map((e) => (
              <option key={e.value} value={e.value}>{e.label}</option>
            ))}
          </Form.Select>
        </Modal.Body>
        <Modal.Footer>
          <Button variant="secondary" onClick={() => setShowEstadoModal(false)}>Cancelar</Button>
          <Button variant="primary" onClick={handleCambiarEstado} disabled={loadingEstado}>
            {loadingEstado ? 'Guardando...' : 'Confirmar'}
          </Button>
        </Modal.Footer>
      </Modal>

      {/* Modal: Convertir */}
      <Modal show={showConvertirModal} onHide={() => setShowConvertirModal(false)} centered size="lg">
        <Modal.Header closeButton>
          <Modal.Title>
            <i className="fas fa-file-contract me-2 text-success"></i>
            Crear Orden de Servicio
          </Modal.Title>
        </Modal.Header>
        <Modal.Body>
          <p className="text-muted mb-3">
            A partir de: <strong>{cotizacion?.numero_cotizacion}</strong>
          </p>
          <Row className="g-3">
            <Col md={6}>
              <Form.Label className="fw-bold">Fecha Inicio <span className="text-danger">*</span></Form.Label>
              <Form.Control
                type="date"
                value={convertirData.fecha_inicio}
                onChange={(e) => setConvertirData((p) => ({ ...p, fecha_inicio: e.target.value }))}
              />
            </Col>
            <Col md={6}>
              <Form.Label className="fw-bold">Fecha Fin <span className="text-danger">*</span></Form.Label>
              <Form.Control
                type="date"
                value={convertirData.fecha_fin}
                onChange={(e) => setConvertirData((p) => ({ ...p, fecha_fin: e.target.value }))}
              />
            </Col>
            <Col md={4}>
              <Form.Label className="fw-bold">Período Facturación (días)</Form.Label>
              <Form.Control
                type="number" min="1"
                value={convertirData.periodo_facturacion_dias}
                onChange={(e) => setConvertirData((p) => ({ ...p, periodo_facturacion_dias: e.target.value }))}
              />
            </Col>
            <Col md={4}>
              <Form.Label className="fw-bold">% Soltec</Form.Label>
              <Form.Control
                type="number" min="0" max="100" step="0.01"
                value={convertirData.porcentaje_soltec}
                onChange={(e) => setConvertirData((p) => ({ ...p, porcentaje_soltec: e.target.value }))}
              />
            </Col>
            <Col md={4}>
              <Form.Label className="fw-bold">% Ret. Fuente</Form.Label>
              <Form.Control
                type="number" min="0" max="100" step="0.01"
                value={convertirData.porcentaje_ret_fuente}
                onChange={(e) => setConvertirData((p) => ({ ...p, porcentaje_ret_fuente: e.target.value }))}
              />
            </Col>
            <Col md={4}>
              <Form.Label className="fw-bold">Prórroga Automática</Form.Label>
              <Form.Select
                value={convertirData.sw_prorroga_automatica}
                onChange={(e) => setConvertirData((p) => ({ ...p, sw_prorroga_automatica: e.target.value }))}
              >
                <option value="0">No</option>
                <option value="1">Sí</option>
              </Form.Select>
            </Col>
          </Row>
        </Modal.Body>
        <Modal.Footer>
          <Button variant="secondary" onClick={() => setShowConvertirModal(false)}>Cancelar</Button>
          <Button variant="success" onClick={handleConvertir} disabled={loadingConvertir}>
            {loadingConvertir
              ? <><span className="spinner-border spinner-border-sm me-1" />Creando...</>
              : <><i className="fas fa-file-contract me-1"></i>Crear Orden de Servicio</>
            }
          </Button>
        </Modal.Footer>
      </Modal>

      {/* Modal: Enviar por correo */}
      <EnviarEmailModal
        show={showEmailModal}
        onHide={() => setShowEmailModal(false)}
        numeroCotizacion={cotizacion?.numero_cotizacion}
        emailInicial={cotizacion?.tercero?.email}
        conAprobacion={['borrador', 'enviada'].includes(cotizacion?.sw_estado)}
        datosOrdenInicial={cotizacion?.datos_orden}
        loading={loadingEmail}
        onConfirm={handleEmailConfirm}
      />
    </MainLayout>
  );
};

export default CotizacionDetailPage;
