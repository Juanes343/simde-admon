import React, { useEffect, useState } from 'react';
import { Card, Table, Button, Form, Row, Col, Badge, Spinner, Pagination } from 'react-bootstrap';
import { useNavigate } from 'react-router-dom';
import notasCreditoService from '../../../services/notaCreditoService';
import { formatCurrency } from '../../../utils/formatters';
import Swal from 'sweetalert2';

const NotasCreditoListView = () => {
  const navigate = useNavigate();
  const [notas, setNotas] = useState([]);
  const [loading, setLoading] = useState(false);
  const [pagination, setPagination] = useState({});
  const [filters, setFilters] = useState({
    fecha_desde: '',
    fecha_hasta: '',
    estado: '',
    prefijo: ''
  });

  useEffect(() => {
    loadNotas();
  }, [filters]);

  const loadNotas = async (page = 1) => {
    setLoading(true);
    try {
      const data = await notasCreditoService.getNotas(page, filters);
      setNotas(data.data || []);
      setPagination({
        current_page: data.current_page,
        last_page: data.last_page,
        total: data.total
      });
    } catch (error) {
      Swal.fire('Error', 'No se pudieron cargar las notas credito', 'error');
    } finally {
      setLoading(false);
    }
  };

  const handleEnviarNota = async (nota) => {
    const result = await Swal.fire({
      title: 'Enviar Nota Credito?',
      text: `Se enviara la nota ${nota.prefijo} ${nota.nota_credito_id} a DataIco.`,
      icon: 'question',
      showCancelButton: true,
      confirmButtonText: 'Si, Enviar',
      cancelButtonText: 'Cancelar'
    });

    if (!result.isConfirmed) return;

    try {
      setLoading(true);
      const response = await notasCreditoService.enviarNota(nota.id || nota.nota_credito_id);
      Swal.fire('Enviado', response?.message || 'Nota enviada correctamente', 'success');
      loadNotas(pagination.current_page || 1);
    } catch (error) {
      Swal.fire('Error', error.response?.data?.message || 'No se pudo enviar la nota', 'error');
    } finally {
      setLoading(false);
    }
  };

  const handleEliminarNota = async (nota) => {
    const result = await Swal.fire({
      title: 'Eliminar Nota Credito?',
      text: `Se eliminara la nota ${nota.prefijo} ${nota.nota_credito_id}.`,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Si, Eliminar',
      cancelButtonText: 'Cancelar'
    });

    if (!result.isConfirmed) return;

    try {
      setLoading(true);
      await notasCreditoService.eliminarNota(nota.id || nota.nota_credito_id);
      Swal.fire('Eliminada', 'Nota eliminada correctamente', 'success');
      loadNotas(pagination.current_page || 1);
    } catch (error) {
      Swal.fire('Error', error.response?.data?.message || 'No se pudo eliminar la nota', 'error');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="container-fluid py-4">
      <Card className="shadow-sm">
        <Card.Header className="bg-primary text-white d-flex justify-content-between align-items-center">
          <h5 className="mb-0">Historico de Notas Credito</h5>
          <div>
            <Button variant="light" size="sm" className="me-2" onClick={() => navigate('/notas')}>
              <i className="fas fa-plus-circle me-2"></i>Crear Nota
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
                    value={filters.fecha_desde}
                    onChange={(e) => setFilters({ ...filters, fecha_desde: e.target.value })}
                  />
                </Form.Group>
              </Col>
              <Col md={3}>
                <Form.Group>
                  <Form.Label>Hasta</Form.Label>
                  <Form.Control
                    type="date"
                    value={filters.fecha_hasta}
                    onChange={(e) => setFilters({ ...filters, fecha_hasta: e.target.value })}
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
                  <th>Factura</th>
                  <th>Fecha</th>
                  <th className="text-end">Valor</th>
                  <th className="text-center">Estado</th>
                  <th className="text-center">Acciones</th>
                </tr>
              </thead>
              <tbody>
                {notas.length > 0 ? (
                  notas.map((n) => (
                    <tr key={n.id || n.nota_credito_id}>
                      <td><strong>{n.prefijo} {n.nota_credito_id}</strong></td>
                      <td>{n.prefijo_factura} {n.factura_fiscal}</td>
                      <td>{n.created_at ? new Date(n.created_at).toLocaleDateString('es-CO') : 'N/A'}</td>
                      <td className="text-end fw-bold">{formatCurrency(n.valor_nota || 0)}</td>
                      <td className="text-center">
                        <Badge bg={n.estado === 'ACEPTADO' ? 'success' : n.estado === 'RECHAZADO' ? 'danger' : 'warning'}>
                          {n.estado || 'PENDIENTE'}
                        </Badge>
                      </td>
                      <td className="text-center">
                        {n.estado === 'PENDIENTE' && (
                          <Button size="sm" variant="success" className="me-1" onClick={() => handleEnviarNota(n)}>
                            <i className="fas fa-paper-plane me-1"></i>Enviar
                          </Button>
                        )}
                        <Button size="sm" variant="outline-danger" onClick={() => handleEliminarNota(n)}>
                          <i className="fas fa-trash"></i>
                        </Button>
                      </td>
                    </tr>
                  ))
                ) : (
                  <tr>
                        <td colSpan="6" className="text-center py-4">No se encontraron notas credito.</td>
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
    </div>
  );
};

export default NotasCreditoListView;
