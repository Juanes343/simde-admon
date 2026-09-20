import React, { useState, useEffect, useRef } from 'react';
import {
  Card, Row, Col, Form, Button, Table, Badge, Alert, InputGroup, Spinner, ListGroup,
} from 'react-bootstrap';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import {
  faMoneyBillWave, faSearch, faCheckCircle, faSpinner,
  faArrowLeft, faInfoCircle, faPiggyBank, faPlus, faHistory,
} from '@fortawesome/free-solid-svg-icons';
import useCausacion from '../hooks/useCausacion';
import causacionService from '../services/causacionService';
import api from '../../../services/api';
import Swal from 'sweetalert2';

const fmt = (val) =>
  new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', minimumFractionDigits: 0 }).format(val || 0);

const TIPOS_PAGO = ['EFECTIVO', 'TRANSFERENCIA', 'CHEQUE', 'ANTICIPO', 'MIXTO'];

const CausacionView = ({ onVolver, onVerHistorial }) => {
  const user = JSON.parse(localStorage.getItem('user') || '{}');
  const empresaId = user?.empresa_id || '01';

  const {
    terceroSeleccionado, setTerceroSeleccionado,
    anticipo, facturas, pagos,
    usarAnticipo, setUsarAnticipo,
    loading, loadingPago,
    buscarFacturas, actualizarPago, registrarPagos, resetear,
  } = useCausacion();

  // Búsqueda de tercero
  const [busquedaTercero, setBusquedaTercero] = useState('');
  const [resultadosTercero, setResultadosTercero] = useState([]);
  const [buscandoTercero, setBuscandoTercero] = useState(false);
  const [mostrarLista, setMostrarLista] = useState(false);
  const [busquedaHecha, setBusquedaHecha] = useState(false);
  const [indiceActivo, setIndiceActivo] = useState(-1);
  const reqIdRef = useRef(0); // descarta respuestas viejas si el usuario sigue escribiendo

  // Formulario de pago
  const [fechaPago, setFechaPago] = useState(new Date().toISOString().split('T')[0]);
  const [tipoPago, setTipoPago] = useState('EFECTIVO');
  const [observaciones, setObservaciones] = useState('');

  // Formulario registro de anticipo
  const [mostrarFormAnticipo, setMostrarFormAnticipo] = useState(false);
  const [montoAnticipo, setMontoAnticipo] = useState('');
  const [savingAnticipo, setSavingAnticipo] = useState(false);

  const buscarTercero = async (texto = busquedaTercero) => {
    const q = texto.trim();
    if (q.length < 2) {
      reqIdRef.current++;
      setResultadosTercero([]);
      setBusquedaHecha(false);
      setBuscandoTercero(false);
      return;
    }
    const reqId = ++reqIdRef.current;
    try {
      setBuscandoTercero(true);
      const res = await api.get('/terceros', { params: { search: q, per_page: 10 } });
      if (reqId !== reqIdRef.current) return;
      const lista = res.data?.data ?? res.data;
      setResultadosTercero(Array.isArray(lista) ? lista : []);
      setBusquedaHecha(true);
      setIndiceActivo(-1);
    } catch {
      if (reqId === reqIdRef.current) Swal.fire('Error', 'No se pudo buscar el tercero', 'error');
    } finally {
      if (reqId === reqIdRef.current) setBuscandoTercero(false);
    }
  };

  // Autocompletado: busca mientras se escribe (con pausa de 300 ms)
  useEffect(() => {
    const timer = setTimeout(() => buscarTercero(busquedaTercero), 300);
    return () => clearTimeout(timer);
  }, [busquedaTercero]);

  const seleccionarTercero = async (t) => {
    setTerceroSeleccionado(t);
    setResultadosTercero([]);
    setMostrarLista(false);
    setBusquedaTercero('');
    await buscarFacturas(empresaId, t.tipo_id_tercero, t.tercero_id);
  };

  const handleKeyDownTercero = (e) => {
    if (e.key === 'ArrowDown') {
      e.preventDefault();
      setMostrarLista(true);
      setIndiceActivo((i) => Math.min(i + 1, resultadosTercero.length - 1));
    } else if (e.key === 'ArrowUp') {
      e.preventDefault();
      setIndiceActivo((i) => Math.max(i - 1, 0));
    } else if (e.key === 'Escape') {
      setMostrarLista(false);
    } else if (e.key === 'Enter') {
      e.preventDefault();
      if (indiceActivo >= 0 && resultadosTercero[indiceActivo]) {
        seleccionarTercero(resultadosTercero[indiceActivo]);
      } else if (resultadosTercero.length === 1) {
        seleccionarTercero(resultadosTercero[0]);
      } else {
        buscarTercero();
      }
    }
  };

  const handleRegistrarAnticipo = async () => {
    const monto = parseFloat(montoAnticipo);
    if (!monto || monto <= 0) return;
    try {
      setSavingAnticipo(true);
      await causacionService.upsertAnticipo({
        empresa_id: empresaId,
        tipo_id_tercero: terceroSeleccionado.tipo_id_tercero,
        tercero_id: terceroSeleccionado.tercero_id,
        monto,
      });
      Swal.fire('Éxito', `Anticipo de ${fmt(monto)} registrado correctamente`, 'success');
      setMontoAnticipo('');
      setMostrarFormAnticipo(false);
      // Recargar anticipo y facturas
      await buscarFacturas(empresaId, terceroSeleccionado.tipo_id_tercero, terceroSeleccionado.tercero_id);
    } catch (err) {
      const msg = err?.response?.data?.message || err?.message || 'No se pudo registrar el anticipo';
      Swal.fire('Error', msg, 'error');
    } finally {
      setSavingAnticipo(false);
    }
  };

  const totalPagos = facturas.reduce((acc, f) => {
    const vp  = parseFloat(pagos[f.factura_fiscal_id]?.valor_pago) || 0;
    const vr  = parseFloat(pagos[f.factura_fiscal_id]?.valor_retencion) || 0;
    const vi  = parseFloat(pagos[f.factura_fiscal_id]?.valor_ica) || 0;
    const vri = parseFloat(pagos[f.factura_fiscal_id]?.valor_reteiva) || 0;
    return acc + vp + vr + vi + vri;
  }, 0);

  const handleRegistrar = async () => {
    const ok = await registrarPagos(
      empresaId,
      terceroSeleccionado.tipo_id_tercero,
      terceroSeleccionado.tercero_id,
      fechaPago, tipoPago, observaciones,
    );
    if (ok) {
      await buscarFacturas(empresaId, terceroSeleccionado.tipo_id_tercero, terceroSeleccionado.tercero_id);
      setObservaciones('');
    }
  };

  return (
    <div className="p-3">
      {/* Header */}
      <div className="d-flex justify-content-between align-items-center mb-4">
        <h3 className="mb-0 text-primary">
          <FontAwesomeIcon icon={faMoneyBillWave} className="me-2" />
          Causación — Registro de Pagos
        </h3>
        <div className="d-flex gap-2">
          {onVerHistorial && (
            <Button variant="outline-primary" size="sm" onClick={onVerHistorial}>
              <FontAwesomeIcon icon={faHistory} className="me-2" />Ver Historial
            </Button>
          )}
          <Button variant="outline-secondary" size="sm" onClick={onVolver}>
            <FontAwesomeIcon icon={faArrowLeft} className="me-2" />Volver
          </Button>
        </div>
      </div>

      {/* Búsqueda de tercero */}
      {!terceroSeleccionado ? (
        <Card className="shadow-sm mb-4">
          <Card.Header className="bg-primary text-white">
            <FontAwesomeIcon icon={faSearch} className="me-2" />
            Buscar Tercero
          </Card.Header>
          <Card.Body>
            <Row className="align-items-end g-2">
              <Col md={8}>
                <Form.Label>Nombre o ID del tercero</Form.Label>
                <div className="position-relative">
                  <InputGroup>
                    <Form.Control
                      placeholder="Escribe el nombre o la identificación..."
                      value={busquedaTercero}
                      onChange={(e) => { setBusquedaTercero(e.target.value); setMostrarLista(true); }}
                      onFocus={() => setMostrarLista(true)}
                      onBlur={() => setMostrarLista(false)}
                      onKeyDown={handleKeyDownTercero}
                      autoComplete="off"
                      autoFocus
                    />
                    <Button variant="primary" onClick={() => buscarTercero()} disabled={buscandoTercero}>
                      {buscandoTercero
                        ? <Spinner size="sm" animation="border" />
                        : <FontAwesomeIcon icon={faSearch} />}
                    </Button>
                  </InputGroup>

                  {mostrarLista && busquedaTercero.trim().length >= 2 && (
                    <ListGroup
                      className="position-absolute w-100 shadow"
                      style={{ top: '100%', zIndex: 1000, maxHeight: 320, overflowY: 'auto' }}
                      onMouseDown={(e) => e.preventDefault()} // evita que el input pierda el foco antes del click
                    >
                      {resultadosTercero.length > 0 ? (
                        resultadosTercero.map((t, i) => (
                          <ListGroup.Item
                            action
                            key={`${t.tipo_id_tercero}-${t.tercero_id}`}
                            active={i === indiceActivo}
                            onClick={() => seleccionarTercero(t)}
                          >
                            <div className="fw-semibold">{t.nombre_tercero}</div>
                            <small className={i === indiceActivo ? '' : 'text-muted'}>
                              {t.tipo_id_tercero} — {t.tercero_id}
                            </small>
                          </ListGroup.Item>
                        ))
                      ) : buscandoTercero ? (
                        <ListGroup.Item className="text-muted small">
                          <Spinner size="sm" animation="border" className="me-2" />Buscando...
                        </ListGroup.Item>
                      ) : busquedaHecha ? (
                        <ListGroup.Item className="text-muted small">Sin resultados</ListGroup.Item>
                      ) : null}
                    </ListGroup>
                  )}
                </div>
              </Col>
            </Row>
          </Card.Body>
        </Card>
      ) : (
        <>
          {/* Info tercero + anticipo */}
          <Row className="mb-3 g-3">
            <Col md={7}>
              <Card className="border-primary h-100">
                <Card.Body className="d-flex align-items-center justify-content-between">
                  <div>
                    <small className="text-muted">Tercero seleccionado</small>
                    <h5 className="mb-0">{terceroSeleccionado.nombre_tercero}</h5>
                    <small className="text-muted">{terceroSeleccionado.tipo_id_tercero} — {terceroSeleccionado.tercero_id}</small>
                  </div>
                  <Button variant="outline-secondary" size="sm" onClick={resetear}>
                    Cambiar
                  </Button>
                </Card.Body>
              </Card>
            </Col>
            <Col md={5}>
              <Card className="border-warning h-100">
                <Card.Body>
                  <div className="d-flex align-items-center justify-content-between mb-2">
                    <div>
                      <small className="text-muted">
                        <FontAwesomeIcon icon={faPiggyBank} className="me-1 text-warning" />
                        Saldo Anticipo
                      </small>
                      <h4 className="mb-0 text-warning">{fmt(anticipo?.saldo || 0)}</h4>
                    </div>
                    <div className="d-flex flex-column gap-1 align-items-end">
                      <Button
                        variant="outline-warning"
                        size="sm"
                        onClick={() => setMostrarFormAnticipo((v) => !v)}
                      >
                        <FontAwesomeIcon icon={faPlus} className="me-1" />
                        Registrar anticipo
                      </Button>
                      {(anticipo?.saldo || 0) > 0 && (
                        <Form.Check
                          type="switch"
                          label="Usar anticipo"
                          checked={usarAnticipo}
                          onChange={(e) => setUsarAnticipo(e.target.checked)}
                        />
                      )}
                    </div>
                  </div>

                  {mostrarFormAnticipo && (
                    <div className="border-top pt-2">
                      <Row className="g-2 align-items-end">
                        <Col xs={7}>
                          <Form.Label className="small mb-1 fw-bold">Monto a registrar</Form.Label>
                          <Form.Control
                            type="number" size="sm" min="0.01" step="0.01"
                            placeholder="0"
                            value={montoAnticipo}
                            onChange={(e) => setMontoAnticipo(e.target.value)}
                            onKeyDown={(e) => e.key === 'Enter' && handleRegistrarAnticipo()}
                            autoFocus
                          />
                        </Col>
                        <Col xs={5}>
                          <Button
                            variant="warning"
                            size="sm"
                            className="w-100"
                            onClick={handleRegistrarAnticipo}
                            disabled={savingAnticipo || !montoAnticipo || parseFloat(montoAnticipo) <= 0}
                          >
                            {savingAnticipo
                              ? <FontAwesomeIcon icon={faSpinner} spin />
                              : <><FontAwesomeIcon icon={faCheckCircle} className="me-1" />Guardar</>}
                          </Button>
                        </Col>
                      </Row>
                    </div>
                  )}
                </Card.Body>
              </Card>
            </Col>
          </Row>

          {/* Facturas pendientes */}
          {loading ? (
            <div className="text-center py-4">
              <Spinner animation="border" variant="primary" />
              <p className="mt-2 text-muted">Cargando facturas pendientes...</p>
            </div>
          ) : facturas.length === 0 ? (
            <Alert variant="info">
              <FontAwesomeIcon icon={faInfoCircle} className="me-2" />
              Este tercero no tiene facturas pendientes de pago.
            </Alert>
          ) : (
            <Card className="shadow-sm mb-4">
              <Card.Header className="bg-success text-white">
                <FontAwesomeIcon icon={faMoneyBillWave} className="me-2" />
                Facturas Pendientes — ingresa los valores a aplicar
              </Card.Header>
              <div className="table-responsive">
                <Table bordered hover size="sm" className="mb-0 align-middle">
                  <thead className="table-light">
                    <tr>
                      <th>Factura</th>
                      <th>Fecha</th>
                      <th className="text-end">Total Factura</th>
                      <th className="text-end">IVA</th>
                      <th className="text-end">Ret. Fte.</th>
                      <th className="text-end">Saldo</th>
                      <th style={{ minWidth: 140 }}>Valor Pago</th>
                      <th style={{ minWidth: 130 }}>Retención Fte.</th>
                      <th style={{ minWidth: 120 }}>ReteICA</th>
                      <th style={{ minWidth: 120 }}>ReteIVA</th>
                      <th className="text-end">Total a Aplicar</th>
                    </tr>
                  </thead>
                  <tbody>
                    {facturas.map((f) => {
                      const vp  = parseFloat(pagos[f.factura_fiscal_id]?.valor_pago) || 0;
                      const vr  = parseFloat(pagos[f.factura_fiscal_id]?.valor_retencion) || 0;
                      const vi  = parseFloat(pagos[f.factura_fiscal_id]?.valor_ica) || 0;
                      const vri = parseFloat(pagos[f.factura_fiscal_id]?.valor_reteiva) || 0;
                      const total = vp + vr + vi + vri;
                      const excede = total > parseFloat(f.saldo) + 0.01;
                      return (
                        <tr key={f.factura_fiscal_id} className={excede ? 'table-danger' : ''}>
                          <td className="fw-bold">
                            {f.prefijo}{f.factura_fiscal}
                            {f.concepto && <div><small className="text-muted">{f.concepto}</small></div>}
                          </td>
                          <td>{f.fecha_registro?.split('T')[0] || f.fecha_registro}</td>
                          <td className="text-end">{fmt(f.total_factura)}</td>
                          <td className="text-end">
                            {parseFloat(f.gravamen) > 0
                              ? <span className="text-secondary small fw-semibold">{fmt(f.gravamen)}</span>
                              : <span className="text-muted small">—</span>}
                          </td>
                          <td className="text-end">
                            {parseFloat(f.valor_ret_fuente) > 0 ? (
                              <span className="text-danger small fw-semibold">
                                − {fmt(f.valor_ret_fuente)}
                                <div className="text-muted">({parseFloat(f.porcentaje_ret_fuente).toLocaleString('es-CO')}%)</div>
                              </span>
                            ) : <span className="text-muted small">—</span>}
                          </td>
                          <td className="text-end">
                            <Badge bg="warning" text="dark">{fmt(f.saldo)}</Badge>
                          </td>
                          <td>
                            <Form.Control
                              type="number" size="sm" min="0" step="0.01"
                              placeholder="0"
                              value={pagos[f.factura_fiscal_id]?.valor_pago}
                              onChange={(e) => actualizarPago(f.factura_fiscal_id, 'valor_pago', e.target.value)}
                              className={excede ? 'border-danger' : ''}
                            />
                          </td>
                          <td>
                            <Form.Control
                              type="number" size="sm" min="0" step="0.01"
                              placeholder="0"
                              value={pagos[f.factura_fiscal_id]?.valor_retencion}
                              onChange={(e) => actualizarPago(f.factura_fiscal_id, 'valor_retencion', e.target.value)}
                            />
                          </td>
                          <td>
                            <Form.Control
                              type="number" size="sm" min="0" step="0.01"
                              placeholder="0"
                              value={pagos[f.factura_fiscal_id]?.valor_ica}
                              onChange={(e) => actualizarPago(f.factura_fiscal_id, 'valor_ica', e.target.value)}
                            />
                          </td>
                          <td>
                            <Form.Control
                              type="number" size="sm" min="0" step="0.01"
                              placeholder="0"
                              value={pagos[f.factura_fiscal_id]?.valor_reteiva}
                              onChange={(e) => actualizarPago(f.factura_fiscal_id, 'valor_reteiva', e.target.value)}
                            />
                          </td>
                          <td className="text-end fw-bold">
                            {total > 0 ? (
                              <span className={excede ? 'text-danger' : 'text-success'}>{fmt(total)}</span>
                            ) : '—'}
                          </td>
                        </tr>
                      );
                    })}
                  </tbody>
                  <tfoot className="table-light fw-bold">
                    <tr>
                      <td colSpan={9} className="text-end">Total a registrar:</td>
                      <td colSpan={2} className="text-end text-primary fs-5">{fmt(totalPagos)}</td>
                    </tr>
                  </tfoot>
                </Table>
              </div>
            </Card>
          )}

          {/* Formulario de pago */}
          {facturas.length > 0 && (
            <Card className="shadow-sm">
              <Card.Header className="bg-dark text-white">Datos del Pago</Card.Header>
              <Card.Body>
                <Row className="g-3">
                  <Col md={3}>
                    <Form.Label>Fecha de Pago</Form.Label>
                    <Form.Control
                      type="date"
                      value={fechaPago}
                      onChange={(e) => setFechaPago(e.target.value)}
                    />
                  </Col>
                  <Col md={3}>
                    <Form.Label>Tipo de Pago</Form.Label>
                    <Form.Select value={tipoPago} onChange={(e) => setTipoPago(e.target.value)}>
                      {TIPOS_PAGO.map((t) => <option key={t}>{t}</option>)}
                    </Form.Select>
                  </Col>
                  <Col md={6}>
                    <Form.Label>Observaciones</Form.Label>
                    <Form.Control
                      placeholder="Opcional..."
                      value={observaciones}
                      onChange={(e) => setObservaciones(e.target.value)}
                    />
                  </Col>
                </Row>
                <div className="mt-3 text-end">
                  <Button
                    variant="success"
                    size="lg"
                    onClick={handleRegistrar}
                    disabled={loadingPago || totalPagos === 0}
                  >
                    {loadingPago
                      ? <><FontAwesomeIcon icon={faSpinner} spin className="me-2" />Registrando...</>
                      : <><FontAwesomeIcon icon={faCheckCircle} className="me-2" />Registrar Pagos ({fmt(totalPagos)})</>}
                  </Button>
                </div>
              </Card.Body>
            </Card>
          )}
        </>
      )}
    </div>
  );
};

export default CausacionView;
