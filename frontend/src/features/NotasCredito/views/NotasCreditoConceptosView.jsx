import React, { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import {
  Card,
  Row,
  Col,
  Form,
  Button,
  Table,
  Badge,
  Modal,
  InputGroup,
} from 'react-bootstrap';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import {
  faTags,
  faPlus,
  faEdit,
  faTrash,  
  faArrowLeft,
  faToggleOn,
  faToggleOff,
  faSearch,
  faSpinner,
} from '@fortawesome/free-solid-svg-icons';
import Swal from 'sweetalert2';
import notaCreditoService from '../../../services/notaCreditoService';

const EMPRESA_ID = '01';

const ConceptoModal = ({ show, concepto, onHide, onSave }) => {
  const [form, setForm] = useState({ descripcion: '', sw_naturaleza: 'C', sw_activo: true });
  const [saving, setSaving] = useState(false);

  useEffect(() => {
    if (concepto) {
      setForm({
        descripcion: concepto.descripcion || '',
        sw_naturaleza: concepto.sw_naturaleza || 'C',
        sw_activo: concepto.sw_activo !== false,
      });
    } else {
      setForm({ descripcion: '', sw_naturaleza: 'C', sw_activo: true });
    }
  }, [concepto, show]);

  const handleSubmit = async (e) => {
    e.preventDefault();
    if (!form.descripcion.trim()) {
      Swal.fire('Validación', 'La descripción es obligatoria', 'warning');
      return;
    }
    try {
      setSaving(true);
      await onSave(form);
      onHide();
    } catch (err) {
      Swal.fire('Error', err?.message || 'No se pudo guardar el concepto', 'error');
    } finally {
      setSaving(false);
    }
  };

  return (
    <Modal show={show} onHide={onHide} centered>
      <Modal.Header closeButton className="bg-primary text-white">
        <Modal.Title>
          <FontAwesomeIcon icon={faTags} className="me-2" />
          {concepto ? 'Editar Concepto' : 'Nuevo Concepto'}
        </Modal.Title>
      </Modal.Header>
      <Form onSubmit={handleSubmit}>
        <Modal.Body>
          <Form.Group className="mb-3">
            <Form.Label className="fw-bold">Descripción <span className="text-danger">*</span></Form.Label>
            <Form.Control
              type="text"
              maxLength={255}
              value={form.descripcion}
              onChange={(e) => setForm({ ...form, descripcion: e.target.value })}
              placeholder="Ej: Devolución de mercancía"
              autoFocus
            />
          </Form.Group>

          <Form.Group className="mb-3">
            <Form.Label className="fw-bold">Naturaleza</Form.Label>
            <Form.Select
              value={form.sw_naturaleza}
              onChange={(e) => setForm({ ...form, sw_naturaleza: e.target.value })}
            >
              <option value="C">Crédito</option>
              <option value="D">Débito</option>
            </Form.Select>
          </Form.Group>

          <Form.Group>
            <Form.Check
              type="switch"
              id="sw_activo"
              label={form.sw_activo ? 'Activo' : 'Inactivo'}
              checked={form.sw_activo}
              onChange={(e) => setForm({ ...form, sw_activo: e.target.checked })}
            />
          </Form.Group>
        </Modal.Body>
        <Modal.Footer>
          <Button variant="secondary" onClick={onHide} disabled={saving}>
            Cancelar
          </Button>
          <Button variant="primary" type="submit" disabled={saving}>
            {saving ? (
              <><FontAwesomeIcon icon={faSpinner} spin className="me-1" /> Guardando...</>
            ) : (
              'Guardar'
            )}
          </Button>
        </Modal.Footer>
      </Form>
    </Modal>
  );
};

const NotasCreditoConceptosView = () => {
  const navigate = useNavigate();
  const [conceptos, setConceptos] = useState([]);
  const [loading, setLoading] = useState(false);
  const [busqueda, setBusqueda] = useState('');
  const [modalOpen, setModalOpen] = useState(false);
  const [conceptoEditar, setConceptoEditar] = useState(null);

  useEffect(() => {
    cargar();
  }, []);

  const cargar = async () => {
    try {
      setLoading(true);
      const res = await notaCreditoService.getConceptosAll(EMPRESA_ID);
      setConceptos(res.data || res || []);
    } catch (err) {
      Swal.fire('Error', 'No se pudieron cargar los conceptos', 'error');
    } finally {
      setLoading(false);
    }
  };

  const handleNuevo = () => {
    setConceptoEditar(null);
    setModalOpen(true);
  };

  const handleEditar = (c) => {
    setConceptoEditar(c);
    setModalOpen(true);
  };

  const handleEliminar = async (c) => {
    const result = await Swal.fire({
      title: '¿Eliminar concepto?',
      html: `<p>Se eliminará <strong>${c.descripcion}</strong>.<br>Esta acción no se puede deshacer.</p>`,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Eliminar',
      cancelButtonText: 'Cancelar',
      confirmButtonColor: '#dc3545',
    });
    if (!result.isConfirmed) return;
    try {
      await notaCreditoService.eliminarConcepto(c.id);
      Swal.fire('Eliminado', 'Concepto eliminado correctamente', 'success');
      cargar();
    } catch (err) {
      Swal.fire('Error', err?.message || 'No se pudo eliminar el concepto', 'error');
    }
  };

  const handleGuardar = async (form) => {
    if (conceptoEditar) {
      await notaCreditoService.actualizarConcepto(conceptoEditar.id, form);
      Swal.fire('Actualizado', 'Concepto actualizado correctamente', 'success');
    } else {
      await notaCreditoService.crearConcepto({ ...form, empresa_id: EMPRESA_ID });
      Swal.fire('Creado', 'Concepto creado correctamente', 'success');
    }
    cargar();
  };

  const conceptosFiltrados = conceptos.filter((c) =>
    c.descripcion?.toLowerCase().includes(busqueda.toLowerCase())
  );

  return (
    <div className="p-4">
      <div className="d-flex justify-content-between align-items-center mb-4">
        <h2 className="mb-0 text-primary">
          <FontAwesomeIcon icon={faTags} className="me-2" />
          Conceptos de Notas Crédito/Débito
        </h2>
        <Button variant="outline-secondary" onClick={() => navigate(-1)}>
          <FontAwesomeIcon icon={faArrowLeft} className="me-2" />
          Volver
        </Button>
      </div>

      <Row className="mb-3 align-items-end">
        <Col md={6}>
          <InputGroup>
            <InputGroup.Text>
              <FontAwesomeIcon icon={faSearch} />
            </InputGroup.Text>
            <Form.Control
              placeholder="Buscar concepto..."
              value={busqueda}
              onChange={(e) => setBusqueda(e.target.value)}
            />
          </InputGroup>
        </Col>
        <Col md={6} className="text-end">
          <Button variant="success" onClick={handleNuevo}>
            <FontAwesomeIcon icon={faPlus} className="me-2" />
            Nuevo Concepto
          </Button>
        </Col>
      </Row>

      <Card className="shadow-sm">
        <Card.Header className="bg-primary text-white d-flex justify-content-between align-items-center">
          <h5 className="mb-0">
            <FontAwesomeIcon icon={faTags} className="me-2" />
            Listado de Conceptos
          </h5>
          <Badge bg="light" text="dark">{conceptosFiltrados.length} registros</Badge>
        </Card.Header>
        <Card.Body className="p-0">
          {loading ? (
            <div className="text-center py-5">
              <FontAwesomeIcon icon={faSpinner} spin size="2x" className="text-primary" />
              <p className="mt-2 text-muted">Cargando conceptos...</p>
            </div>
          ) : conceptosFiltrados.length === 0 ? (
            <div className="text-center py-5 text-muted">
              <FontAwesomeIcon icon={faTags} size="3x" className="mb-3 opacity-25" />
              <p className="mb-0">No hay conceptos registrados.</p>
              <Button variant="link" onClick={handleNuevo}>Crear el primero</Button>
            </div>
          ) : (
            <div className="table-responsive">
              <Table striped hover bordered size="sm" className="mb-0 align-middle">
                <thead className="table-light">
                  <tr>
                    <th style={{ width: '60px' }}>ID</th>
                    <th>Descripción</th>
                    <th className="text-center" style={{ width: '110px' }}>Naturaleza</th>
                    <th className="text-center" style={{ width: '100px' }}>Estado</th>
                    <th className="text-center" style={{ width: '110px' }}>Acciones</th>
                  </tr>
                </thead>
                <tbody>
                  {conceptosFiltrados.map((c) => (
                    <tr key={c.id}>
                      <td className="text-muted">{c.id}</td>
                      <td className="fw-semibold">{c.descripcion}</td>
                      <td className="text-center">
                        <Badge bg={c.sw_naturaleza === 'C' ? 'success' : 'danger'}>
                          {c.sw_naturaleza === 'C' ? 'Crédito' : 'Débito'}
                        </Badge>
                      </td>
                      <td className="text-center">
                        {c.sw_activo ? (
                          <Badge bg="success">
                            <FontAwesomeIcon icon={faToggleOn} className="me-1" />Activo
                          </Badge>
                        ) : (
                          <Badge bg="secondary">
                            <FontAwesomeIcon icon={faToggleOff} className="me-1" />Inactivo
                          </Badge>
                        )}
                      </td>
                      <td className="text-center">
                        <Button
                          variant="outline-primary"
                          size="sm"
                          className="me-1"
                          onClick={() => handleEditar(c)}
                          title="Editar"
                        >
                          <FontAwesomeIcon icon={faEdit} />
                        </Button>
                        <Button
                          variant="outline-danger"
                          size="sm"
                          onClick={() => handleEliminar(c)}
                          title="Eliminar"
                        >
                          <FontAwesomeIcon icon={faTrash} />
                        </Button>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </Table>
            </div>
          )}
        </Card.Body>
      </Card>

      <ConceptoModal
        show={modalOpen}
        concepto={conceptoEditar}
        onHide={() => setModalOpen(false)}
        onSave={handleGuardar}
      />
    </div>
  );
};

export default NotasCreditoConceptosView;
