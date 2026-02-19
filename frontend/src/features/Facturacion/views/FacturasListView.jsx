import React, { useState, useEffect } from 'react';
import { Card, Table, Button, Form, Row, Col, Badge, Spinner, Pagination } from 'react-bootstrap';
import { useNavigate } from 'react-router-dom';
import facturacionService from '../../../services/facturacionService';
import { formatCurrency } from '../../../utils/formatters';

const FacturasListView = () => {
  const navigate = useNavigate();
  const [facturas, setFacturas] = useState([]);
  const [loading, setLoading] = useState(false);
  const [pagination, setPagination] = useState({});
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
    } catch (error) {
      console.error("Error cargando facturas", error);
    } finally {
      setLoading(false);
    }
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
                  <th className="text-center">Acciones</th>
                </tr>
              </thead>
              <tbody>
                {facturas.length > 0 ? (
                  facturas.map(f => (
                    <tr key={f.factura_fiscal_id}>
                      <td><strong>{f.prefijo} {f.factura_fiscal}</strong></td>
                      <td>{new Date(f.fecha_registro).toLocaleDateString()}</td>
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
                        <Button size="sm" variant="outline-primary" title="Ver Detalles">
                          <i className="fas fa-eye"></i>
                        </Button>
                      </td>
                    </tr>
                  ))
                ) : (
                  <tr>
                    <td colSpan="6" className="text-center py-4">No se encontraron facturas.</td>
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
    </div>
  );
};

export default FacturasListView;
