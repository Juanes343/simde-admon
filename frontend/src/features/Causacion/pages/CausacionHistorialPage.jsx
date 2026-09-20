import React, { useState, useEffect, useCallback } from 'react';
import {
  Card, Table, Button, Form, Row, Col, Spinner, Badge, InputGroup, Pagination, Modal,
} from 'react-bootstrap';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import {
  faHistory, faSearch, faArrowLeft, faFilter, faMoneyBillWave, faTimes, faEdit,
  faExclamationTriangle,
} from '@fortawesome/free-solid-svg-icons';
import { useNavigate } from 'react-router-dom';
import causacionService from '../services/causacionService';
import api from '../../../services/api';
import Swal from 'sweetalert2';

const fmt = (val) =>
  new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', minimumFractionDigits: 0 }).format(val || 0);

const BADGE_TIPO = {
  EFECTIVO: 'success',
  TRANSFERENCIA: 'primary',
  CHEQUE: 'warning',
  ANTICIPO: 'info',
  MIXTO: 'secondary',
};

const CausacionHistorialPage = () => {
  const navigate = useNavigate();
  const user = JSON.parse(localStorage.getItem('user') || '{}');
  const empresaId = user?.empresa_id || '01';

  // Filtros
  const [busquedaTercero, setBusquedaTercero] = useState('');
  const [resultadosTercero, setResultadosTercero] = useState([]);
  const [buscandoTercero, setBuscandoTercero] = useState(false);
  const [terceroFiltro, setTerceroFiltro] = useState(null);
  const [fechaDesde, setFechaDesde] = useState('');
  const [fechaHasta, setFechaHasta] = useState('');

  // Datos
  const [pagos, setPagos] = useState([]);
  const [meta, setMeta] = useState(null);
  const [paginaActual, setPaginaActual] = useState(1);
  const [loading, setLoading] = useState(false);

  const cargarHistorial = useCallback(async (pagina = 1) => {
    setLoading(true);
    try {
      const params = {
        empresa_id: empresaId,
        per_page: 20,
        page: pagina,
      };
      if (terceroFiltro) params.tercero_id = terceroFiltro.tercero_id;
      if (fechaDesde) params.desde = fechaDesde;
      if (fechaHasta) params.hasta = fechaHasta;

      const res = await causacionService.getPagos(params);
      const paginado = res.data?.data;
      setPagos(paginado?.data || []);
      setMeta(paginado);
      setPaginaActual(pagina);
    } catch {
      setPagos([]);
    } finally {
      setLoading(false);
    }
  }, [empresaId, terceroFiltro, fechaDesde, fechaHasta]);

  useEffect(() => {
    cargarHistorial(1);
  }, [cargarHistorial]);

  const buscarTercero = async () => {
    if (!busquedaTercero.trim()) return;
    setBuscandoTercero(true);
    try {
      const res = await api.get('/terceros', { params: { search: busquedaTercero, per_page: 8 } });
      setResultadosTercero(res.data?.data || []);
    } catch {
      setResultadosTercero([]);
    } finally {
      setBuscandoTercero(false);
    }
  };

  const seleccionarTercero = (t) => {
    setTerceroFiltro(t);
    setResultadosTercero([]);
    setBusquedaTercero('');
  };

  const limpiarFiltros = () => {
    setTerceroFiltro(null);
    setBusquedaTercero('');
    setFechaDesde('');
    setFechaHasta('');
    setResultadosTercero([]);
  };

  const totalPagos       = pagos.reduce((acc, p) => acc + parseFloat(p.valor_pago || 0), 0);
  const totalRetenciones = pagos.reduce((acc, p) => acc + parseFloat(p.valor_retencion || 0), 0);
  const totalIca         = pagos.reduce((acc, p) => acc + parseFloat(p.valor_ica || 0), 0);
  const totalReteiva     = pagos.reduce((acc, p) => acc + parseFloat(p.valor_reteiva || 0), 0);

  // ── Estado modal de edición ────────────────────────────────────────────────
  const TIPOS_PAGO = ['EFECTIVO', 'TRANSFERENCIA', 'CHEQUE', 'ANTICIPO', 'MIXTO'];

  const [pagoEditando, setPagoEditando]   = useState(null);
  const [editForm, setEditForm]           = useState({});
  const [loadingEdit, setLoadingEdit]     = useState(false);
  const [reasignar, setReasignar]         = useState(false);

  const abrirEdicion = (p) => {
    setPagoEditando(p);
    setReasignar(false);
    // Normalizar fecha: acepta '2026-06-09T05:00:00.000000Z', 'YYYY-MM-DD', etc.
    const fechaNorm = p.fecha_pago ? String(p.fecha_pago).slice(0, 10) : '';
    setEditForm({
      factura_fiscal_id: p.factura_fiscal_id,
      valor_pago:        String(p.valor_pago        ?? ''),
      valor_retencion:   String(p.valor_retencion   ?? ''),
      valor_ica:         String(p.valor_ica         ?? ''),
      valor_reteiva:     String(p.valor_reteiva      ?? ''),
      fecha_pago:        fechaNorm,
      tipo_pago:         p.tipo_pago  ?? 'EFECTIVO',
      observaciones:     p.observaciones ?? '',
    });
  };

  const cerrarEdicion = () => {
    if (loadingEdit) return;
    setPagoEditando(null);
  };

  const cambioEdit = (campo, valor) =>
    setEditForm((prev) => ({ ...prev, [campo]: valor }));

  const totalEditado = () => {
    const vp  = parseFloat(editForm.valor_pago)        || 0;
    const vr  = parseFloat(editForm.valor_retencion)   || 0;
    const vi  = parseFloat(editForm.valor_ica)         || 0;
    const vri = parseFloat(editForm.valor_reteiva)      || 0;
    return vp + vr + vi + vri;
  };

  const confirmarEdicion = () => {
    const vp = parseFloat(editForm.valor_pago) || 0;
    if (vp <= 0) {
      Swal.fire('Atención', 'El valor de pago debe ser mayor a 0.', 'warning');
      return;
    }
    if (!editForm.fecha_pago) {
      Swal.fire('Atención', 'La fecha de pago es requerida.', 'warning');
      return;
    }

    const factura = pagoEditando?.factura;
    const facturaLabel = factura
      ? `${factura.prefijo}${factura.factura_fiscal}`
      : `#${pagoEditando?.factura_fiscal_id}`;

    const reasignadoLabel = reasignar && editForm.factura_fiscal_id != pagoEditando?.factura_fiscal_id
      ? `<br/><b>Factura destino:</b> #${editForm.factura_fiscal_id}`
      : '';

    Swal.fire({
      title: 'Confirmar edición',
      icon: 'question',
      html:
        `<div style="text-align:left;font-size:0.95rem">`+
        `<b>Factura original:</b> ${facturaLabel}${reasignadoLabel}<br/>`+
        `<b>Valor pago:</b> ${fmt(parseFloat(editForm.valor_pago) || 0)}<br/>`+
        `<b>Retención:</b> ${fmt(parseFloat(editForm.valor_retencion) || 0)}<br/>`+
        `<b>ReteICA:</b> ${fmt(parseFloat(editForm.valor_ica) || 0)}<br/>`+
        `<b>ReteIVA:</b> ${fmt(parseFloat(editForm.valor_reteiva) || 0)}<br/>`+
        `<b>Total aplicado:</b> <strong>${fmt(totalEditado())}</strong><br/>`+
        `<b>Fecha:</b> ${editForm.fecha_pago}&nbsp;&nbsp;<b>Tipo:</b> ${editForm.tipo_pago}`+
        `</div>`,
      showCancelButton: true,
      confirmButtonText: 'Sí, actualizar',
      cancelButtonText: 'Cancelar',
      confirmButtonColor: '#198754',
    }).then(async (result) => {
      if (!result.isConfirmed) return;
      await guardarEdicion();
    });
  };

  const guardarEdicion = async () => {
    setLoadingEdit(true);
    try {
      const payload = {
        valor_pago:       parseFloat(editForm.valor_pago)       || 0,
        valor_retencion:  parseFloat(editForm.valor_retencion)  || 0,
        valor_ica:        parseFloat(editForm.valor_ica)        || 0,
        valor_reteiva:    parseFloat(editForm.valor_reteiva)     || 0,
        fecha_pago:       editForm.fecha_pago,
        tipo_pago:        editForm.tipo_pago,
        observaciones:    editForm.observaciones,
      };
      if (reasignar && editForm.factura_fiscal_id != pagoEditando?.factura_fiscal_id) {
        payload.factura_fiscal_id = parseInt(editForm.factura_fiscal_id);
      }
      await causacionService.actualizarPago(pagoEditando.id, payload);
      Swal.fire('¡Actualizado!', 'El pago fue editado correctamente.', 'success');
      setPagoEditando(null);
      cargarHistorial(paginaActual);
    } catch (err) {
      const msg = err?.response?.data?.message || 'Error al actualizar el pago.';
      Swal.fire('Error', msg, 'error');
    } finally {
      setLoadingEdit(false);
    }
  };

  return (
    <div className="p-3">
      {/* Header */}
      <div className="d-flex justify-content-between align-items-center mb-4">
        <h3 className="mb-0 text-primary">
          <FontAwesomeIcon icon={faHistory} className="me-2" />
          Causación — Historial de Pagos
        </h3>
        <Button variant="outline-secondary" size="sm" onClick={() => navigate('/causacion')}>
          <FontAwesomeIcon icon={faArrowLeft} className="me-2" />Volver
        </Button>
      </div>

      {/* Filtros */}
      <Card className="shadow-sm mb-4">
        <Card.Header className="bg-primary text-white">
          <FontAwesomeIcon icon={faFilter} className="me-2" />
          Filtros
        </Card.Header>
        <Card.Body>
          <Row className="g-3 align-items-end">
            {/* Búsqueda tercero */}
            <Col md={5}>
              <Form.Label className="fw-bold">Tercero</Form.Label>
              {terceroFiltro ? (
                <div className="d-flex align-items-center gap-2">
                  <div className="flex-grow-1 border rounded px-3 py-2 bg-light">
                    <strong>{terceroFiltro.nombre_tercero}</strong>
                    <small className="text-muted ms-2">{terceroFiltro.tipo_id_tercero} — {terceroFiltro.tercero_id}</small>
                  </div>
                  <Button variant="outline-danger" size="sm" onClick={() => setTerceroFiltro(null)}>
                    <FontAwesomeIcon icon={faTimes} />
                  </Button>
                </div>
              ) : (
                <InputGroup>
                  <Form.Control
                    placeholder="Buscar por nombre o ID..."
                    value={busquedaTercero}
                    onChange={(e) => setBusquedaTercero(e.target.value)}
                    onKeyDown={(e) => e.key === 'Enter' && buscarTercero()}
                  />
                  <Button variant="outline-primary" onClick={buscarTercero} disabled={buscandoTercero}>
                    {buscandoTercero ? <Spinner size="sm" animation="border" /> : <FontAwesomeIcon icon={faSearch} />}
                  </Button>
                </InputGroup>
              )}

              {resultadosTercero.length > 0 && (
                <div className="border rounded mt-1 bg-white shadow-sm" style={{ maxHeight: 200, overflowY: 'auto', position: 'absolute', zIndex: 999, width: '38%' }}>
                  {resultadosTercero.map((t) => (
                    <div
                      key={`${t.tipo_id_tercero}-${t.tercero_id}`}
                      className="px-3 py-2 border-bottom cursor-pointer hover-bg"
                      style={{ cursor: 'pointer' }}
                      onClick={() => seleccionarTercero(t)}
                      onMouseEnter={(e) => e.currentTarget.style.background = '#f0f4ff'}
                      onMouseLeave={(e) => e.currentTarget.style.background = ''}
                    >
                      <strong>{t.nombre_tercero}</strong>
                      <small className="text-muted ms-2">{t.tipo_id_tercero} {t.tercero_id}</small>
                    </div>
                  ))}
                </div>
              )}
            </Col>

            {/* Fechas */}
            <Col md={2}>
              <Form.Label className="fw-bold">Desde</Form.Label>
              <Form.Control type="date" value={fechaDesde} onChange={(e) => setFechaDesde(e.target.value)} />
            </Col>
            <Col md={2}>
              <Form.Label className="fw-bold">Hasta</Form.Label>
              <Form.Control type="date" value={fechaHasta} onChange={(e) => setFechaHasta(e.target.value)} />
            </Col>

            {/* Botones */}
            <Col md={3} className="d-flex gap-2">
              <Button variant="primary" onClick={() => cargarHistorial(1)} disabled={loading}>
                <FontAwesomeIcon icon={faSearch} className="me-1" />
                Buscar
              </Button>
              <Button variant="outline-secondary" onClick={limpiarFiltros}>
                <FontAwesomeIcon icon={faTimes} className="me-1" />
                Limpiar
              </Button>
            </Col>
          </Row>
        </Card.Body>
      </Card>

      {/* Tabla */}
      <Card className="shadow-sm">
        <Card.Header className="bg-success text-white d-flex justify-content-between align-items-center">
          <span>
            <FontAwesomeIcon icon={faMoneyBillWave} className="me-2" />
            Pagos Registrados
            {meta && <Badge bg="light" text="dark" className="ms-2">{meta.total} registros</Badge>}
          </span>
          {pagos.length > 0 && (
            <span className="small">
              Pagos: <strong>{fmt(totalPagos)}</strong>
              {totalRetenciones > 0 && <> | Rte.Fte: <strong>{fmt(totalRetenciones)}</strong></>}
              {totalIca > 0 && <> | ReteICA: <strong>{fmt(totalIca)}</strong></>}
              {totalReteiva > 0 && <> | ReteIVA: <strong>{fmt(totalReteiva)}</strong></>}
            </span>
          )}
        </Card.Header>

        {loading ? (
          <div className="text-center py-5">
            <Spinner animation="border" variant="primary" />
            <p className="mt-2 text-muted">Cargando historial...</p>
          </div>
        ) : pagos.length === 0 ? (
          <div className="text-center py-5 text-muted">
            <FontAwesomeIcon icon={faHistory} size="3x" className="mb-3 opacity-25" />
            <p>No se encontraron pagos con los filtros aplicados.</p>
          </div>
        ) : (
          <>
            <div className="table-responsive">
              <Table bordered hover size="sm" className="mb-0 align-middle">
                <thead className="table-dark">
                  <tr>
                    <th>#</th>
                    <th>Factura</th>
                    <th>Tercero</th>
                    <th>Fecha Pago</th>
                    <th className="text-end">Valor Pago</th>
                    <th className="text-end">Rte. Fte.</th>
                    <th className="text-end">ReteICA</th>
                    <th className="text-end">ReteIVA</th>
                    <th className="text-end">Total Aplicado</th>
                    <th>Tipo Pago</th>
                    <th>Observaciones</th>
                    <th></th>
                  </tr>
                </thead>
                <tbody>
                  {pagos.map((p) => {
                    const factura = p.factura;
                      const totalAplicado = parseFloat(p.valor_pago || 0) + parseFloat(p.valor_retencion || 0) + parseFloat(p.valor_ica || 0) + parseFloat(p.valor_reteiva || 0);
                    return (
                      <tr key={p.id}>
                        <td className="text-muted small">{p.id}</td>
                        <td>
                          {factura
                            ? <strong>{factura.prefijo}{factura.factura_fiscal}</strong>
                            : <span className="text-muted">—</span>}
                        </td>
                        <td>
                          <div>{p.tercero_id}</div>
                          <small className="text-muted">{p.tipo_id_tercero}</small>
                        </td>
                        <td>{String(p.fecha_pago).slice(0, 10)}</td>
                        <td className="text-end text-success fw-bold">{fmt(p.valor_pago)}</td>
                        <td className="text-end text-warning">{parseFloat(p.valor_retencion) > 0 ? fmt(p.valor_retencion) : '—'}</td>
                        <td className="text-end text-info">{parseFloat(p.valor_ica) > 0 ? fmt(p.valor_ica) : '—'}</td>
                        <td className="text-end" style={{ color: '#6f42c1' }}>{parseFloat(p.valor_reteiva) > 0 ? fmt(p.valor_reteiva) : '—'}</td>
                        <td className="text-end fw-bold">{fmt(totalAplicado)}</td>
                        <td>
                          <Badge bg={BADGE_TIPO[p.tipo_pago] || 'secondary'}>
                            {p.tipo_pago}
                          </Badge>
                        </td>
                        <td className="text-muted small">{p.observaciones || '—'}</td>
                        <td>
                          <Button
                            size="sm" variant="outline-warning"
                            title="Editar pago"
                            onClick={() => abrirEdicion(p)}
                          >
                            <FontAwesomeIcon icon={faEdit} />
                          </Button>
                        </td>
                      </tr>
                    );
                  })}
                </tbody>
                <tfoot className="table-light fw-bold">
                  <tr>
                    <td colSpan={4} className="text-end">Totales página:</td>
                    <td className="text-end text-success">{fmt(totalPagos)}</td>
                    <td className="text-end text-warning">{fmt(totalRetenciones)}</td>
                    <td className="text-end text-info">{fmt(totalIca)}</td>
                    <td className="text-end" style={{ color: '#6f42c1' }}>{fmt(totalReteiva)}</td>
                    <td className="text-end">{fmt(totalPagos + totalRetenciones + totalIca + totalReteiva)}</td>
                    <td colSpan={3}></td>
                  </tr>
                </tfoot>
              </Table>
            </div>

            {/* Paginación */}
            {meta && meta.last_page > 1 && (
              <div className="d-flex justify-content-center py-3">
                <Pagination size="sm">
                  <Pagination.Prev disabled={paginaActual === 1} onClick={() => cargarHistorial(paginaActual - 1)} />
                  {Array.from({ length: meta.last_page }, (_, i) => i + 1)
                    .filter((p) => p === 1 || p === meta.last_page || Math.abs(p - paginaActual) <= 2)
                    .reduce((acc, p, i, arr) => {
                      if (i > 0 && p - arr[i - 1] > 1) acc.push('...');
                      acc.push(p);
                      return acc;
                    }, [])
                    .map((p, i) =>
                      p === '...'
                        ? <Pagination.Ellipsis key={`e${i}`} disabled />
                        : <Pagination.Item key={p} active={p === paginaActual} onClick={() => cargarHistorial(p)}>{p}</Pagination.Item>
                    )}
                  <Pagination.Next disabled={paginaActual === meta.last_page} onClick={() => cargarHistorial(paginaActual + 1)} />
                </Pagination>
              </div>
            )}
          </>
        )}
      </Card>

      {/* ── Modal Edición de Pago ─────────────────────────────────────────── */}
      <Modal show={!!pagoEditando} onHide={cerrarEdicion} size="lg" backdrop="static">
        <Modal.Header closeButton className="bg-warning">
          <Modal.Title>
            <FontAwesomeIcon icon={faEdit} className="me-2" />
            Editar Pago #{pagoEditando?.id}
          </Modal.Title>
        </Modal.Header>
        <Modal.Body>
          {pagoEditando && (
            <>
              {/* Info de referencia */}
              <div className="border rounded p-3 mb-3 bg-light">
                <Row className="g-2">
                  <Col md={6}>
                    <small className="text-muted d-block">Factura original</small>
                    <strong>
                      {pagoEditando.factura
                        ? `${pagoEditando.factura.prefijo}${pagoEditando.factura.factura_fiscal}`
                        : `#${pagoEditando.factura_fiscal_id}`}
                    </strong>
                  </Col>
                  <Col md={6}>
                    <small className="text-muted d-block">Tercero</small>
                    <strong>{pagoEditando.tipo_id_tercero} — {pagoEditando.tercero_id}</strong>
                  </Col>
                </Row>

                {/* Reasignación opcional */}
                <div className="mt-2">
                  <Form.Check
                    type="switch"
                    label="Reasignar a otra factura"
                    checked={reasignar}
                    onChange={(e) => {
                      setReasignar(e.target.checked);
                      if (!e.target.checked) cambioEdit('factura_fiscal_id', pagoEditando.factura_fiscal_id);
                    }}
                  />
                </div>
                {reasignar && (
                  <div className="mt-2">
                    <Form.Label className="small fw-bold mb-1">
                      <FontAwesomeIcon icon={faExclamationTriangle} className="text-danger me-1" />
                      ID de factura destino (factura_fiscal_id)
                    </Form.Label>
                    <Form.Control
                      type="number" size="sm"
                      placeholder="Ej: 183"
                      value={editForm.factura_fiscal_id}
                      onChange={(e) => cambioEdit('factura_fiscal_id', e.target.value)}
                    />
                    <Form.Text className="text-muted">
                      La factura destino debe estar activa y tener saldo suficiente.
                    </Form.Text>
                  </div>
                )}
              </div>

              {/* Valores */}
              <Row className="g-3">
                <Col md={3}>
                  <Form.Label className="fw-bold">Valor Pago <span className="text-danger">*</span></Form.Label>
                  <Form.Control
                    type="number" min="0.01" step="0.01"
                    value={editForm.valor_pago}
                    onChange={(e) => cambioEdit('valor_pago', e.target.value)}
                  />
                </Col>
                <Col md={3}>
                  <Form.Label className="fw-bold">Rte. Fuente</Form.Label>
                  <Form.Control
                    type="number" min="0" step="0.01"
                    value={editForm.valor_retencion}
                    onChange={(e) => cambioEdit('valor_retencion', e.target.value)}
                  />
                </Col>
                <Col md={3}>
                  <Form.Label className="fw-bold">ReteICA</Form.Label>
                  <Form.Control
                    type="number" min="0" step="0.01"
                    value={editForm.valor_ica}
                    onChange={(e) => cambioEdit('valor_ica', e.target.value)}
                  />
                </Col>
                <Col md={3}>
                  <Form.Label className="fw-bold">ReteIVA</Form.Label>
                  <Form.Control
                    type="number" min="0" step="0.01"
                    value={editForm.valor_reteiva}
                    onChange={(e) => cambioEdit('valor_reteiva', e.target.value)}
                  />
                </Col>
              </Row>

              <Row className="g-3 mt-1">
                <Col md={3}>
                  <Form.Label className="fw-bold">Fecha Pago <span className="text-danger">*</span></Form.Label>
                  <Form.Control
                    type="date"
                    value={editForm.fecha_pago}
                    onChange={(e) => cambioEdit('fecha_pago', e.target.value)}
                  />
                </Col>
                <Col md={3}>
                  <Form.Label className="fw-bold">Tipo de Pago</Form.Label>
                  <Form.Select
                    value={editForm.tipo_pago}
                    onChange={(e) => cambioEdit('tipo_pago', e.target.value)}
                  >
                    {TIPOS_PAGO.map((t) => <option key={t}>{t}</option>)}
                  </Form.Select>
                </Col>
                <Col md={6}>
                  <Form.Label className="fw-bold">Observaciones</Form.Label>
                  <Form.Control
                    placeholder="Opcional..."
                    value={editForm.observaciones}
                    onChange={(e) => cambioEdit('observaciones', e.target.value)}
                  />
                </Col>
              </Row>

              {/* Resumen total */}
              <div className="mt-3 p-2 bg-light border rounded text-end">
                <span className="text-muted small me-2">Total que se aplicará a la factura:</span>
                <strong className={`fs-5 ${totalEditado() > 0 ? 'text-primary' : 'text-muted'}`}>
                  {fmt(totalEditado())}
                </strong>
              </div>
            </>
          )}
        </Modal.Body>
        <Modal.Footer>
          <Button variant="secondary" onClick={cerrarEdicion} disabled={loadingEdit}>
            Cancelar
          </Button>
          <Button variant="success" onClick={confirmarEdicion} disabled={loadingEdit}>
            {loadingEdit
              ? <><Spinner size="sm" animation="border" className="me-1" />Guardando...</>
              : <><FontAwesomeIcon icon={faEdit} className="me-1" />Confirmar cambios</>}
          </Button>
        </Modal.Footer>
      </Modal>
    </div>
  );
};

export default CausacionHistorialPage;
