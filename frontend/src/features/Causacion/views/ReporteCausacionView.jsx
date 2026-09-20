import React, { useState } from 'react';
import {
  Card, Row, Col, Form, Button, Table, Alert, Spinner, Badge,
} from 'react-bootstrap';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import {
  faFileExcel, faSearch, faArrowLeft, faFilter, faFileInvoiceDollar,
} from '@fortawesome/free-solid-svg-icons';
import Swal from 'sweetalert2';
import causacionService from '../services/causacionService';

const fmt = (val) =>
  new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', minimumFractionDigits: 0 }).format(val || 0);

const ReporteCausacionView = ({ onVolver }) => {
  const user = JSON.parse(localStorage.getItem('user') || '{}');
  const empresaId = user?.empresa_id || '01';

  const [filtros, setFiltros] = useState({
    fecha_factura_inicio: '',
    fecha_factura_fin: '',
    fecha_pago_inicio: '',
    fecha_pago_fin: '',
    tercero: '',
    incluir_externas: true,
  });

  const [rows, setRows] = useState([]);
  const [columnas, setColumnas] = useState([]);
  const [loading, setLoading] = useState(false);
  const [descargando, setDescargando] = useState(false);
  const [buscado, setBuscado] = useState(false);

  const handleChange = (campo, valor) => {
    setFiltros((prev) => ({ ...prev, [campo]: valor }));
  };

  const construirParams = () => {
    const params = { empresa_id: empresaId };
    if (filtros.fecha_factura_inicio) params.fecha_factura_desde = filtros.fecha_factura_inicio;
    if (filtros.fecha_factura_fin) params.fecha_factura_hasta = filtros.fecha_factura_fin;
    if (filtros.fecha_pago_inicio) params.fecha_pago_desde = filtros.fecha_pago_inicio;
    if (filtros.fecha_pago_fin) params.fecha_pago_hasta = filtros.fecha_pago_fin;
    if (filtros.tercero) params.tercero = filtros.tercero;
    params.incluir_externas = filtros.incluir_externas ? 1 : 0;
    return params;
  };

  const validarRangos = () => {
    if (
      !filtros.fecha_factura_inicio && !filtros.fecha_factura_fin &&
      !filtros.fecha_pago_inicio && !filtros.fecha_pago_fin
    ) {
      Swal.fire('Atención', 'Indique al menos un rango de fechas (factura o pago) para generar el reporte.', 'warning');
      return false;
    }
    if (filtros.fecha_factura_inicio && filtros.fecha_factura_fin && filtros.fecha_factura_inicio > filtros.fecha_factura_fin) {
      Swal.fire('Atención', 'La fecha inicial de factura no puede ser mayor a la final.', 'warning');
      return false;
    }
    if (filtros.fecha_pago_inicio && filtros.fecha_pago_fin && filtros.fecha_pago_inicio > filtros.fecha_pago_fin) {
      Swal.fire('Atención', 'La fecha inicial de pago no puede ser mayor a la final.', 'warning');
      return false;
    }
    return true;
  };

  const handleBuscar = async () => {
    if (!validarRangos()) return;
    try {
      setLoading(true);
      setBuscado(true);
      const res = await causacionService.getReporte(construirParams());
      const payload = res.data ?? {};
      // El backend devuelve { headers: [...], rows: [...] }.
      // Soporta también array de objetos como fallback.
      let headers = payload.headers ?? [];
      const filas = payload.rows ?? payload.data ?? (Array.isArray(payload) ? payload : []);
      if ((!headers || headers.length === 0) && filas.length > 0 && !Array.isArray(filas[0])) {
        headers = Object.keys(filas[0]);
      }
      setColumnas(headers);
      setRows(filas);
    } catch (err) {
      const msg = err?.response?.data?.message || 'No se pudo generar la previsualización del reporte';
      Swal.fire('Error', msg, 'error');
      setRows([]);
      setColumnas([]);
    } finally {
      setLoading(false);
    }
  };

  const handleDescargar = async () => {
    if (!validarRangos()) return;
    try {
      setDescargando(true);
      await causacionService.descargarReporte(construirParams());
    } catch (err) {
      const msg = err?.response?.data?.message || 'No se pudo descargar el reporte en Excel';
      Swal.fire('Error', msg, 'error');
    } finally {
      setDescargando(false);
    }
  };

  const limpiarFiltros = () => {
    setFiltros({
      fecha_factura_inicio: '',
      fecha_factura_fin: '',
      fecha_pago_inicio: '',
      fecha_pago_fin: '',
      tercero: '',
      incluir_externas: true,
    });
    setRows([]);
    setColumnas([]);
    setBuscado(false);
  };

  const formatearCelda = (valor) => {
    if (valor === null || valor === undefined || valor === '') return '—';
    if (typeof valor === 'number') return valor.toLocaleString('es-CO');
    return String(valor);
  };

  return (
    <div className="p-3">
      {/* Header */}
      <div className="d-flex justify-content-between align-items-center mb-4">
        <h3 className="mb-0 text-primary">
          <FontAwesomeIcon icon={faFileInvoiceDollar} className="me-2" />
          Reporte de Facturas y Pagos
        </h3>
        <Button variant="outline-secondary" onClick={onVolver}>
          <FontAwesomeIcon icon={faArrowLeft} className="me-2" />
          Volver
        </Button>
      </div>

      {/* Filtros */}
      <Card className="shadow-sm mb-4">
        <Card.Header className="bg-light fw-bold">
          <FontAwesomeIcon icon={faFilter} className="me-2 text-primary" />
          Filtros del Reporte
        </Card.Header>
        <Card.Body>
          <Row className="g-3">
            <Col md={3}>
              <Form.Group>
                <Form.Label className="small fw-bold">Fecha Factura — Desde</Form.Label>
                <Form.Control
                  type="date"
                  value={filtros.fecha_factura_inicio}
                  onChange={(e) => handleChange('fecha_factura_inicio', e.target.value)}
                />
              </Form.Group>
            </Col>
            <Col md={3}>
              <Form.Group>
                <Form.Label className="small fw-bold">Fecha Factura — Hasta</Form.Label>
                <Form.Control
                  type="date"
                  value={filtros.fecha_factura_fin}
                  onChange={(e) => handleChange('fecha_factura_fin', e.target.value)}
                />
              </Form.Group>
            </Col>
            <Col md={3}>
              <Form.Group>
                <Form.Label className="small fw-bold">Fecha Pago — Desde</Form.Label>
                <Form.Control
                  type="date"
                  value={filtros.fecha_pago_inicio}
                  onChange={(e) => handleChange('fecha_pago_inicio', e.target.value)}
                />
              </Form.Group>
            </Col>
            <Col md={3}>
              <Form.Group>
                <Form.Label className="small fw-bold">Fecha Pago — Hasta</Form.Label>
                <Form.Control
                  type="date"
                  value={filtros.fecha_pago_fin}
                  onChange={(e) => handleChange('fecha_pago_fin', e.target.value)}
                />
              </Form.Group>
            </Col>
            <Col md={6}>
              <Form.Group>
                <Form.Label className="small fw-bold">Tercero (Nombre o NIT) — opcional</Form.Label>
                <Form.Control
                  type="text"
                  placeholder="Escriba nombre o documento..."
                  value={filtros.tercero}
                  onChange={(e) => handleChange('tercero', e.target.value)}
                />
              </Form.Group>
            </Col>
            <Col md={6} className="d-flex align-items-end">
              <Form.Check
                type="checkbox"
                id="incluir-externas"
                label="Incluir facturas externas"
                checked={filtros.incluir_externas}
                onChange={(e) => handleChange('incluir_externas', e.target.checked)}
              />
            </Col>
          </Row>

          <div className="d-flex gap-2 mt-4 flex-wrap">
            <Button variant="primary" onClick={handleBuscar} disabled={loading}>
              {loading ? (
                <><Spinner animation="border" size="sm" className="me-2" />Generando...</>
              ) : (
                <><FontAwesomeIcon icon={faSearch} className="me-2" />Previsualizar</>
              )}
            </Button>
            <Button variant="success" onClick={handleDescargar} disabled={descargando}>
              {descargando ? (
                <><Spinner animation="border" size="sm" className="me-2" />Descargando...</>
              ) : (
                <><FontAwesomeIcon icon={faFileExcel} className="me-2" />Descargar Excel</>
              )}
            </Button>
            <Button variant="outline-secondary" onClick={limpiarFiltros}>
              Limpiar
            </Button>
          </div>
        </Card.Body>
      </Card>

      {/* Resultados */}
      <Card className="shadow-sm">
        <Card.Header className="bg-light fw-bold d-flex justify-content-between align-items-center">
          <span>
            <FontAwesomeIcon icon={faFileInvoiceDollar} className="me-2 text-success" />
            Previsualización
          </span>
          {rows.length > 0 && (
            <Badge bg="secondary">{rows.length} registro(s)</Badge>
          )}
        </Card.Header>
        <Card.Body>
          {loading ? (
            <div className="text-center py-5">
              <Spinner animation="border" variant="primary" />
              <p className="mt-2 text-muted">Generando previsualización...</p>
            </div>
          ) : !buscado ? (
            <Alert variant="info" className="mb-0">
              Configure los filtros y presione <strong>Previsualizar</strong> para ver los datos, o
              <strong> Descargar Excel</strong> para obtener el reporte completo.
            </Alert>
          ) : rows.length === 0 ? (
            <Alert variant="warning" className="mb-0">
              No se encontraron facturas ni pagos para los filtros seleccionados.
            </Alert>
          ) : (
            <div className="table-responsive">
              <Table striped bordered hover size="sm" className="align-middle">
                <thead className="table-dark">
                  <tr>
                    {columnas.map((col) => (
                      <th key={col} className="text-nowrap">{col}</th>
                    ))}
                  </tr>
                </thead>
                <tbody>
                  {rows.map((row, i) => (
                    <tr key={i}>
                      {columnas.map((col, ci) => (
                        <td key={ci} className="text-nowrap">
                          {formatearCelda(Array.isArray(row) ? row[ci] : row[col])}
                        </td>
                      ))}
                    </tr>
                  ))}
                </tbody>
              </Table>
              <p className="text-muted small mb-0">
                * La previsualización muestra los datos según el backend. El archivo Excel incluye la estructura completa.
              </p>
            </div>
          )}
        </Card.Body>
      </Card>
    </div>
  );
};

export default ReporteCausacionView;
