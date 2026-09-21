import React, { useState } from 'react';
import { Container, Row, Col, Button, Form, InputGroup, Modal } from 'react-bootstrap';
import { useNavigate } from 'react-router-dom';
import { toast } from 'react-toastify';
import MainLayout from '../../../components/Layout/MainLayout';
import BackToDashboard from '../../../components/BackToDashboard/BackToDashboard';
import CotizacionesListView from '../views/CotizacionesListView';
import EnviarEmailModal from '../views/EnviarEmailModal';
import useCotizaciones from '../hooks/useCotizaciones';
import cotizacionService from '../services/cotizacionService';

const ESTADOS = [
  { value: '',           label: 'Todos los estados' },
  { value: 'borrador',   label: 'Borrador'   },
  { value: 'enviada',    label: 'Enviada'    },
  { value: 'aprobada',   label: 'Aprobada'   },
  { value: 'rechazada',  label: 'Rechazada'  },
  { value: 'vencida',    label: 'Vencida'    },
  { value: 'convertida', label: 'Convertida' },
];

const CotizacionesListPage = () => {
  const navigate = useNavigate();
  const { cotizaciones, loading, pagination, setPage, updateFilters, refetch } = useCotizaciones();

  const [searchTerm,   setSearchTerm]   = useState('');
  const [estadoFilter, setEstadoFilter] = useState('');

  // ── Modal eliminar ─────────────────────────────────────────────────────────
  const [showDeleteModal,   setShowDeleteModal]   = useState(false);
  const [cotizacionToDelete, setCotizacionToDelete] = useState(null);

  // ── Modal cambiar estado ───────────────────────────────────────────────────
  const [showEstadoModal, setShowEstadoModal]   = useState(false);
  const [cotizacionEstado, setCotizacionEstado] = useState(null);
  const [nuevoEstado,      setNuevoEstado]      = useState('');
  const [loadingEstado,    setLoadingEstado]    = useState(false);

  // ── Modal convertir a orden ────────────────────────────────────────────────
  const [showConvertirModal, setShowConvertirModal] = useState(false);
  const [cotizacionConvertir, setCotizacionConvertir] = useState(null);
  const [loadingConvertir, setLoadingConvertir] = useState(false);
  const [convertirData, setConvertirData] = useState({
    fecha_inicio:             '',
    fecha_fin:                '',
    periodo_facturacion_dias: '30',
    sw_prorroga_automatica:   '0',
    porcentaje_soltec:        '0',
    porcentaje_ret_fuente:    '0',
  });

  // ── Handlers de búsqueda ───────────────────────────────────────────────────
  const handleSearch = (e) => {
    setSearchTerm(e.target.value);
    updateFilters({ search: e.target.value });
  };

  const handleEstadoFilter = (e) => {
    setEstadoFilter(e.target.value);
    updateFilters({ sw_estado: e.target.value });
  };

  // ── Navegación ─────────────────────────────────────────────────────────────
  const handleNew  = () => navigate('/cotizaciones/new');
  const handleEdit = (c) => navigate(`/cotizaciones/edit/${c.cotizacion_id}`);
  const handleView = (c) => navigate(`/cotizaciones/${c.cotizacion_id}`);

  // ── Eliminar ───────────────────────────────────────────────────────────────
  const handleDeleteClick = (c) => {
    setCotizacionToDelete(c);
    setShowDeleteModal(true);
  };

  const handleDeleteConfirm = async () => {
    try {
      await cotizacionService.delete(cotizacionToDelete.cotizacion_id);
      toast.success('Cotización eliminada');
      setShowDeleteModal(false);
      refetch();
    } catch (err) {
      toast.error(err.response?.data?.message || 'Error al eliminar');
    }
  };

  // ── Cambiar estado ─────────────────────────────────────────────────────────
  const handleCambiarEstado = (c, estado) => {
    setCotizacionEstado(c);
    setNuevoEstado(estado);
    setShowEstadoModal(true);
  };

  const handleEstadoConfirm = async () => {
    try {
      setLoadingEstado(true);
      await cotizacionService.cambiarEstado(cotizacionEstado.cotizacion_id, nuevoEstado);
      toast.success('Estado actualizado');
      setShowEstadoModal(false);
      refetch();
    } catch (err) {
      toast.error(err.response?.data?.message || 'Error al cambiar estado');
    } finally {
      setLoadingEstado(false);
    }
  };

  // ── Convertir a Orden ──────────────────────────────────────────────────────
  const handleConvertir = (c) => {
    setCotizacionConvertir(c);
    const items = c.items || [];
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
  };

  // ── Email ──────────────────────────────────────────────────────────────────
  const [showEmailModal,    setShowEmailModal]    = useState(false);
  const [cotizacionEmail,   setCotizacionEmail]   = useState(null);
  const [loadingEmail,      setLoadingEmail]      = useState(false);

  const handleEnviarEmail = (c) => {
    setCotizacionEmail(c);
    setShowEmailModal(true);
  };

  const handleEmailConfirm = async (email, adjuntos, datosOrden) => {
    try {
      setLoadingEmail(true);
      const res = await cotizacionService.enviarEmail(cotizacionEmail.cotizacion_id, email, adjuntos, datosOrden);
      toast.success(res.message);
      setShowEmailModal(false);
      refetch();
    } catch (err) {
      toast.error(err.response?.data?.message || 'Error al enviar el correo');
    } finally {
      setLoadingEmail(false);
    }
  };

  const handleDescargarPdf = (c) => {
    cotizacionService.descargarPdf(c.cotizacion_id, c.numero_cotizacion);
  };

  const handleConvertirConfirm = async () => {
    if (!convertirData.fecha_inicio || !convertirData.fecha_fin) {
      toast.warning('Debe indicar fecha inicio y fecha fin');
      return;
    }
    try {
      setLoadingConvertir(true);
      const res = await cotizacionService.convertirAOrden(cotizacionConvertir.cotizacion_id, convertirData);
      toast.success(`Convertida a Orden de Servicio: ${res.numero_orden}`);
      setShowConvertirModal(false);
      refetch();
    } catch (err) {
      toast.error(err.response?.data?.message || 'Error al convertir');
    } finally {
      setLoadingConvertir(false);
    }
  };

  return (
    <MainLayout>
      <Container fluid className="py-4">
        <BackToDashboard />

        {/* Cabecera */}
        <Row className="mb-3 align-items-center">
          <Col>
            <h2><i className="fas fa-file-alt me-2"></i>Cotizaciones</h2>
          </Col>
          <Col xs="auto">
            <Button variant="primary" onClick={handleNew}>
              <i className="fas fa-plus me-2"></i>Nueva Cotización
            </Button>
          </Col>
        </Row>

        {/* Filtros */}
        <Row className="mb-3 g-2">
          <Col md={6}>
            <InputGroup>
              <InputGroup.Text><i className="fas fa-search"></i></InputGroup.Text>
              <Form.Control
                placeholder="Buscar por N° cotización, cliente u orden de compra..."
                value={searchTerm}
                onChange={handleSearch}
              />
            </InputGroup>
          </Col>
          <Col md={3}>
            <Form.Select value={estadoFilter} onChange={handleEstadoFilter}>
              {ESTADOS.map((e) => <option key={e.value} value={e.value}>{e.label}</option>)}
            </Form.Select>
          </Col>
        </Row>

        {/* Lista */}
        <CotizacionesListView
          cotizaciones={cotizaciones}
          loading={loading}
          onEdit={handleEdit}
          onDelete={handleDeleteClick}
          onView={handleView}
          onCambiarEstado={handleCambiarEstado}
          onConvertir={handleConvertir}
          onDescargarPdf={handleDescargarPdf}
          onEnviarEmail={handleEnviarEmail}
        />

        {/* Paginación */}
        {pagination.lastPage > 1 && (
          <div className="d-flex justify-content-center mt-3 gap-2">
            <Button
              variant="outline-secondary"
              size="sm"
              disabled={pagination.currentPage === 1}
              onClick={() => setPage(pagination.currentPage - 1)}
            >
              ‹ Anterior
            </Button>
            <span className="align-self-center text-muted small">
              Página {pagination.currentPage} de {pagination.lastPage} — {pagination.total} registros
            </span>
            <Button
              variant="outline-secondary"
              size="sm"
              disabled={pagination.currentPage === pagination.lastPage}
              onClick={() => setPage(pagination.currentPage + 1)}
            >
              Siguiente ›
            </Button>
          </div>
        )}
      </Container>

      {/* Modal: Eliminar */}
      <Modal show={showDeleteModal} onHide={() => setShowDeleteModal(false)} centered>
        <Modal.Header closeButton>
          <Modal.Title>Eliminar Cotización</Modal.Title>
        </Modal.Header>
        <Modal.Body>
          ¿Está seguro de eliminar la cotización{' '}
          <strong>{cotizacionToDelete?.numero_cotizacion}</strong>? Esta acción no se puede deshacer.
        </Modal.Body>
        <Modal.Footer>
          <Button variant="secondary" onClick={() => setShowDeleteModal(false)}>Cancelar</Button>
          <Button variant="danger" onClick={handleDeleteConfirm}>Eliminar</Button>
        </Modal.Footer>
      </Modal>

      {/* Modal: Cambiar estado */}
      <Modal show={showEstadoModal} onHide={() => setShowEstadoModal(false)} centered>
        <Modal.Header closeButton>
          <Modal.Title>Cambiar Estado</Modal.Title>
        </Modal.Header>
        <Modal.Body>
          ¿Desea cambiar el estado de <strong>{cotizacionEstado?.numero_cotizacion}</strong> a{' '}
          <strong>{nuevoEstado}</strong>?
        </Modal.Body>
        <Modal.Footer>
          <Button variant="secondary" onClick={() => setShowEstadoModal(false)}>Cancelar</Button>
          <Button variant="success" onClick={handleEstadoConfirm} disabled={loadingEstado}>
            {loadingEstado ? 'Guardando...' : 'Confirmar'}
          </Button>
        </Modal.Footer>
      </Modal>

      {/* Modal: Convertir a Orden de Servicio */}
      <Modal show={showConvertirModal} onHide={() => setShowConvertirModal(false)} centered size="lg">
        <Modal.Header closeButton>
          <Modal.Title>
            <i className="fas fa-exchange-alt me-2 text-info"></i>
            Convertir a Orden de Servicio
          </Modal.Title>
        </Modal.Header>
        <Modal.Body>
          <p className="text-muted mb-3">
            Cotización: <strong>{cotizacionConvertir?.numero_cotizacion}</strong>
          </p>
          <Row className="g-3">
            <Col md={6}>
              <Form.Label className="fw-bold">Fecha Inicio <span className="text-danger">*</span></Form.Label>
              <Form.Control
                type="date"
                value={convertirData.fecha_inicio}
                onChange={(e) => setConvertirData((p) => ({ ...p, fecha_inicio: e.target.value }))}
                required
              />
            </Col>
            <Col md={6}>
              <Form.Label className="fw-bold">Fecha Fin <span className="text-danger">*</span></Form.Label>
              <Form.Control
                type="date"
                value={convertirData.fecha_fin}
                onChange={(e) => setConvertirData((p) => ({ ...p, fecha_fin: e.target.value }))}
                required
              />
            </Col>
            <Col md={4}>
              <Form.Label className="fw-bold">Período Facturación (días)</Form.Label>
              <Form.Control
                type="number"
                min="1"
                value={convertirData.periodo_facturacion_dias}
                onChange={(e) => setConvertirData((p) => ({ ...p, periodo_facturacion_dias: e.target.value }))}
              />
            </Col>
            <Col md={4}>
              <Form.Label className="fw-bold">% Soltec</Form.Label>
              <Form.Control
                type="number"
                min="0"
                max="100"
                step="0.01"
                value={convertirData.porcentaje_soltec}
                onChange={(e) => setConvertirData((p) => ({ ...p, porcentaje_soltec: e.target.value }))}
              />
            </Col>
            <Col md={4}>
              <Form.Label className="fw-bold">% Ret. Fuente</Form.Label>
              <Form.Control
                type="number"
                min="0"
                max="100"
                step="0.01"
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
          <Button variant="info" onClick={handleConvertirConfirm} disabled={loadingConvertir}>
            {loadingConvertir
              ? <><span className="spinner-border spinner-border-sm me-1" />Convirtiendo...</>
              : <><i className="fas fa-exchange-alt me-1"></i>Convertir a Orden</>
            }
          </Button>
        </Modal.Footer>
      </Modal>

      {/* Modal: Enviar por correo */}
      <EnviarEmailModal
        show={showEmailModal}
        onHide={() => setShowEmailModal(false)}
        numeroCotizacion={cotizacionEmail?.numero_cotizacion}
        emailInicial={cotizacionEmail?.tercero?.email}
        conAprobacion={['borrador', 'enviada'].includes(cotizacionEmail?.sw_estado)}
        datosOrdenInicial={cotizacionEmail?.datos_orden}
        loading={loadingEmail}
        onConfirm={handleEmailConfirm}
      />
    </MainLayout>
  );
};

export default CotizacionesListPage;
