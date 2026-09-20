import React, { useState, useEffect, useRef } from 'react';
import {
  Card, Table, Button, Form, Row, Col, Badge,
  Spinner, Pagination, Alert, ProgressBar,
} from 'react-bootstrap';
import { useNavigate } from 'react-router-dom';
import facturaExternaService from '../../../services/facturaExternaService';
import { formatCurrency } from '../../../utils/formatters';
import Swal from 'sweetalert2';

const ESTADO_LABELS = {
  '1': { label: 'Activa',    variant: 'success' },
  '0': { label: 'Inactiva',  variant: 'secondary' },
  'A': { label: 'Activa',    variant: 'success' },
  'I': { label: 'Inactiva',  variant: 'secondary' },
  'P': { label: 'Pendiente', variant: 'warning' },
};

const FacturasExternasView = () => {
  const navigate = useNavigate();
  const fileInputRef = useRef(null);

  // ── Carga CSV ────────────────────────────────────────────────────────────
  const [archivoSeleccionado, setArchivoSeleccionado] = useState(null);
  const [subiendo, setSubiendo] = useState(false);
  const [progreso, setProgreso] = useState(0);
  const [resultado, setResultado] = useState(null); // { inserted, skipped, errors, message }

  // ── Tabla ────────────────────────────────────────────────────────────────
  const [facturas, setFacturas] = useState([]);
  const [loadingTabla, setLoadingTabla] = useState(false);
  const [errorTabla, setErrorTabla] = useState(null);
  const [paginacion, setPaginacion] = useState({ current_page: 1, last_page: 1, total: 0 });
  const [filtros, setFiltros] = useState({
    empresa_id: '',
    prefijo: '',
    tercero_id: '',
    estado: '',
    fecha_desde: '',
    fecha_hasta: '',
  });

  useEffect(() => {
    cargarFacturas(1);
  }, []);

  // ── Handlers CSV ─────────────────────────────────────────────────────────
  const handleFileChange = (e) => {
    const archivo = e.target.files[0] || null;
    setArchivoSeleccionado(archivo);
    setResultado(null);
  };

  const handleSubir = async () => {
    if (!archivoSeleccionado) return;

    const confirm = await Swal.fire({
      title: '¿Cargar facturas externas?',
      text: `Se procesará el archivo "${archivoSeleccionado.name}". Los registros duplicados (misma empresa + prefijo + número) serán omitidos.`,
      icon: 'question',
      showCancelButton: true,
      confirmButtonText: 'Sí, cargar',
      cancelButtonText: 'Cancelar',
      confirmButtonColor: '#0d6efd',
    });

    if (!confirm.isConfirmed) return;

    setSubiendo(true);
    setProgreso(0);
    setResultado(null);

    try {
      const data = await facturaExternaService.uploadCsv(archivoSeleccionado, setProgreso);
      setResultado(data);
      setArchivoSeleccionado(null);
      if (fileInputRef.current) fileInputRef.current.value = '';
      // Recargar tabla tras la subida
      cargarFacturas(1);
    } catch (err) {
      const msg = err?.response?.data?.message || err?.response?.data?.errors?.archivo?.[0] || 'Error al subir el archivo.';
      Swal.fire({ icon: 'error', title: 'Error', text: msg });
    } finally {
      setSubiendo(false);
    }
  };

  // ── Handlers tabla ────────────────────────────────────────────────────────
  const cargarFacturas = async (page = 1) => {
    setLoadingTabla(true);
    setErrorTabla(null);
    try {
      const data = await facturaExternaService.getAll({ ...filtros, page });
      setFacturas(data.data);
      setPaginacion({
        current_page: data.current_page,
        last_page: data.last_page,
        total: data.total,
      });
    } catch (err) {
      console.error('Error cargando facturas externas', err);
      const msg = err?.response?.data?.message || err?.message || 'Error desconocido al cargar facturas.';
      setErrorTabla(`HTTP ${err?.response?.status || '?'}: ${msg}`);
    } finally {
      setLoadingTabla(false);
    }
  };

  const handleBuscar = (e) => {
    e.preventDefault();
    cargarFacturas(1);
  };

  const handleEliminar = async (id, prefijo, numero) => {
    const confirm = await Swal.fire({
      title: '¿Eliminar factura?',
      text: `Factura ${prefijo}-${numero} será eliminada permanentemente.`,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Eliminar',
      cancelButtonText: 'Cancelar',
      confirmButtonColor: '#dc3545',
    });
    if (!confirm.isConfirmed) return;

    try {
      await facturaExternaService.deleteOne(id);
      Swal.fire({ icon: 'success', title: 'Eliminada', timer: 1500, showConfirmButton: false });
      cargarFacturas(paginacion.current_page);
    } catch (err) {
      Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo eliminar la factura.' });
    }
  };

  // ── Paginación ────────────────────────────────────────────────────────────
  const renderPaginacion = () => {
    const { current_page, last_page } = paginacion;
    if (last_page <= 1) return null;

    const items = [];
    items.push(
      <Pagination.Prev key="prev" disabled={current_page === 1} onClick={() => cargarFacturas(current_page - 1)} />
    );
    for (let p = Math.max(1, current_page - 2); p <= Math.min(last_page, current_page + 2); p++) {
      items.push(
        <Pagination.Item key={p} active={p === current_page} onClick={() => cargarFacturas(p)}>
          {p}
        </Pagination.Item>
      );
    }
    items.push(
      <Pagination.Next key="next" disabled={current_page === last_page} onClick={() => cargarFacturas(current_page + 1)} />
    );
    return <Pagination className="mb-0 justify-content-end">{items}</Pagination>;
  };

  // ── Render ────────────────────────────────────────────────────────────────
  return (
    <div className="container-fluid py-3">

      {/* Botón volver */}
      <Button variant="outline-secondary" size="sm" className="mb-3" onClick={() => navigate('/facturacion')}>
        ← Volver
      </Button>

      <h4 className="mb-3 fw-bold">Cargar Facturas Externas</h4>

      {/* ── Sección carga CSV ─────────────────────────────────────────── */}
      <Card className="mb-4 shadow-sm">
        <Card.Header className="bg-primary text-white fw-semibold">
          Cargar archivo CSV
        </Card.Header>
        <Card.Body>
          <Row className="align-items-end g-3">
            <Col xs={12} md={6}>
              <Form.Label className="fw-semibold mb-1">Archivo CSV (separado por punto y coma)</Form.Label>
              <Form.Control
                type="file"
                accept=".csv,.txt"
                ref={fileInputRef}
                onChange={handleFileChange}
                disabled={subiendo}
              />
              <Form.Text className="text-muted">
                Máximo 10 MB. Primera fila debe ser la cabecera.
              </Form.Text>
            </Col>
            <Col xs={12} md="auto">
              <Button
                variant="success"
                onClick={handleSubir}
                disabled={!archivoSeleccionado || subiendo}
              >
                {subiendo ? <><Spinner size="sm" className="me-1" />Subiendo...</> : '⬆ Cargar archivo'}
              </Button>
            </Col>
            <Col xs={12} md="auto">
              <Button
                variant="outline-secondary"
                onClick={facturaExternaService.descargarPlantilla}
              >
                ⬇ Descargar plantilla CSV
              </Button>
            </Col>
          </Row>

          {/* Barra de progreso */}
          {subiendo && (
            <ProgressBar animated now={progreso} label={`${progreso}%`} className="mt-3" />
          )}

          {/* Resultado de la carga */}
          {resultado && (
            <div className="mt-3">
              <Alert variant={resultado.skipped > 0 || resultado.errors?.length > 0 ? 'warning' : 'success'}>
                <strong>{resultado.message}</strong>
              </Alert>

              {resultado.errors?.length > 0 && (
                <div className="mt-2">
                  <p className="fw-semibold text-danger mb-1">Errores ({resultado.errors.length}):</p>
                  <ul className="small text-danger mb-0" style={{ maxHeight: 150, overflowY: 'auto' }}>
                    {resultado.errors.map((e, i) => <li key={i}>{e}</li>)}
                  </ul>
                </div>
              )}
            </div>
          )}
        </Card.Body>
      </Card>

      {/* ── Sección listado ───────────────────────────────────────────── */}
      <Card className="shadow-sm">
        <Card.Header className="bg-light fw-semibold d-flex justify-content-between align-items-center">
          <span>Facturas externas cargadas</span>
          {paginacion.total > 0 && (
            <Badge bg="secondary">{paginacion.total} registros</Badge>
          )}
        </Card.Header>
        <Card.Body>

          {/* Filtros */}
          <Form onSubmit={handleBuscar} className="mb-3">
            <Row className="g-2 align-items-end">
              <Col xs={6} md={2}>
                <Form.Label className="small mb-1">Empresa</Form.Label>
                <Form.Control size="sm" value={filtros.empresa_id}
                  onChange={e => setFiltros({ ...filtros, empresa_id: e.target.value })} placeholder="01" />
              </Col>
              <Col xs={6} md={2}>
                <Form.Label className="small mb-1">Prefijo</Form.Label>
                <Form.Control size="sm" value={filtros.prefijo}
                  onChange={e => setFiltros({ ...filtros, prefijo: e.target.value })} placeholder="FV" />
              </Col>
              <Col xs={12} md={3}>
                <Form.Label className="small mb-1">NIT / Tercero</Form.Label>
                <Form.Control size="sm" value={filtros.tercero_id}
                  onChange={e => setFiltros({ ...filtros, tercero_id: e.target.value })} placeholder="900..." />
              </Col>
              <Col xs={6} md={2}>
                <Form.Label className="small mb-1">Fecha desde</Form.Label>
                <Form.Control size="sm" type="date" value={filtros.fecha_desde}
                  onChange={e => setFiltros({ ...filtros, fecha_desde: e.target.value })} />
              </Col>
              <Col xs={6} md={2}>
                <Form.Label className="small mb-1">Fecha hasta</Form.Label>
                <Form.Control size="sm" type="date" value={filtros.fecha_hasta}
                  onChange={e => setFiltros({ ...filtros, fecha_hasta: e.target.value })} />
              </Col>
              <Col xs={12} md="auto">
                <Button size="sm" type="submit" variant="primary">Buscar</Button>
                <Button size="sm" variant="outline-secondary" className="ms-2" onClick={() => {
                  setFiltros({ empresa_id: '', prefijo: '', tercero_id: '', estado: '', fecha_desde: '', fecha_hasta: '' });
                  setTimeout(() => cargarFacturas(1), 0);
                }}>Limpiar</Button>
              </Col>
            </Row>
          </Form>

          {/* Tabla */}
          {loadingTabla ? (
            <div className="text-center py-4">
              <Spinner animation="border" variant="primary" />
            </div>
          ) : errorTabla ? (
            <Alert variant="danger" className="mb-0"><strong>Error:</strong> {errorTabla}</Alert>
          ) : facturas.length === 0 ? (
            <Alert variant="info" className="mb-0">No hay facturas externas que coincidan con los filtros.</Alert>
          ) : (
            <>
              <div className="table-responsive">
                <Table bordered hover size="sm" className="mb-2 align-middle">
                  <thead className="table-light">
                    <tr>
                      <th>Empresa</th>
                      <th>Prefijo</th>
                      <th>Número</th>
                      <th>Fecha</th>
                      <th>Tercero</th>
                      <th className="text-end">Total</th>
                      <th>Estado</th>
                      <th></th>
                    </tr>
                  </thead>
                  <tbody>
                    {facturas.map(f => {
                      const est = ESTADO_LABELS[f.estado] || { label: f.estado, variant: 'light' };
                      return (
                        <tr key={f.id}>
                          <td>{f.empresa_id}</td>
                          <td>{f.prefijo}</td>
                          <td>{f.factura_fiscal}</td>
                          <td>{f.fecha_registro ? new Date(f.fecha_registro).toLocaleDateString('es-CO') : '-'}</td>
                          <td className="small">{f.tercero_id}</td>
                          <td className="text-end">{formatCurrency(f.total_factura)}</td>
                          <td>
                            <Badge bg={est.variant}>{est.label}</Badge>
                          </td>
                          <td className="text-center">
                            <Button
                              variant="outline-danger"
                              size="sm"
                              onClick={() => handleEliminar(f.id, f.prefijo, f.factura_fiscal)}
                            >
                              ✕
                            </Button>
                          </td>
                        </tr>
                      );
                    })}
                  </tbody>
                </Table>
              </div>
              {renderPaginacion()}
            </>
          )}
        </Card.Body>
      </Card>
    </div>
  );
};

export default FacturasExternasView;
