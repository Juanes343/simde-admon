import React, { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import {
  Card, Row, Col, Table, Badge, Button, Form, Modal, Alert,
} from 'react-bootstrap';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import {
  faUserShield, faArrowLeft, faSpinner, faSave, faEdit,
  faToggleOn, faToggleOff, faCrown,
} from '@fortawesome/free-solid-svg-icons';
import Swal from 'sweetalert2';
import api from '../../../services/api';

const UsuarioPermisosView = () => {
  const navigate = useNavigate();
  const [usuarios, setUsuarios] = useState([]);
  const [catalogo, setCatalogo] = useState([]);
  const [loading, setLoading] = useState(false);
  const [modalOpen, setModalOpen] = useState(false);
  const [usuarioEditar, setUsuarioEditar] = useState(null);
  const [modulosSeleccionados, setModulosSeleccionados] = useState([]);
  const [saving, setSaving] = useState(false);

  const currentUser = JSON.parse(localStorage.getItem('user') || '{}');

  useEffect(() => {
    cargar();
  }, []);

  const cargar = async () => {
    try {
      setLoading(true);
      const [resUsuarios, resCatalogo] = await Promise.all([
        api.get('/usuarios'),
        api.get('/modulos'),
      ]);
      setUsuarios(resUsuarios.data?.data || []);
      setCatalogo(resCatalogo.data?.data || []);
    } catch (err) {
      Swal.fire('Error', 'No se pudieron cargar los datos', 'error');
    } finally {
      setLoading(false);
    }
  };

  const handleEditar = (usuario) => {
    setUsuarioEditar(usuario);
    setModulosSeleccionados(usuario.modulos || []);
    setModalOpen(true);
  };

  const toggleModulo = (moduloId) => {
    setModulosSeleccionados((prev) =>
      prev.includes(moduloId) ? prev.filter((m) => m !== moduloId) : [...prev, moduloId]
    );
  };

  const handleGuardar = async () => {
    try {
      setSaving(true);
      await api.put(`/usuarios/${usuarioEditar.usuario_id}/modulos`, {
        modulos: modulosSeleccionados,
      });
      Swal.fire('Guardado', 'Permisos actualizados correctamente', 'success');
      setModalOpen(false);
      cargar();
    } catch (err) {
      Swal.fire('Error', err?.response?.data?.message || 'No se pudieron guardar los permisos', 'error');
    } finally {
      setSaving(false);
    }
  };

  const handleToggleActivo = async (usuario) => {
    const estaActivo = usuario.activo === '1' || usuario.activo === 1;
    const result = await Swal.fire({
      title: estaActivo ? '¿Inactivar usuario?' : '¿Activar usuario?',
      text: `${usuario.nombre} quedará ${estaActivo ? 'inactivo y no podrá iniciar sesión' : 'activo nuevamente'}.`,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: estaActivo ? '#d33' : '#28a745',
      cancelButtonColor: '#6c757d',
      confirmButtonText: estaActivo ? 'Sí, inactivar' : 'Sí, activar',
      cancelButtonText: 'Cancelar',
    });
    if (!result.isConfirmed) return;
    try {
      await api.patch(`/usuarios/${usuario.usuario_id}/activo`);
      cargar();
    } catch (err) {
      Swal.fire('Error', err?.response?.data?.message || 'No se pudo actualizar el estado', 'error');
    }
  };

  const handleToggleAdmin = async (usuario) => {
    const esAdmin = usuario.sw_admin === '1' || usuario.sw_admin === 1;
    const result = await Swal.fire({
      title: esAdmin ? '¿Quitar rol Admin?' : '¿Asignar rol Admin?',
      text: `${usuario.nombre} ${esAdmin ? 'perderá el acceso total y solo verá sus módulos asignados' : 'obtendrá acceso total al sistema'}.`,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#0066cc',
      cancelButtonColor: '#6c757d',
      confirmButtonText: esAdmin ? 'Sí, quitar Admin' : 'Sí, hacer Admin',
      cancelButtonText: 'Cancelar',
    });
    if (!result.isConfirmed) return;
    try {
      await api.patch(`/usuarios/${usuario.usuario_id}/admin`);
      cargar();
    } catch (err) {
      Swal.fire('Error', err?.response?.data?.message || 'No se pudo actualizar el rol Admin', 'error');
    }
  };

  return (
    <div className="p-4">
      <div className="d-flex justify-content-between align-items-center mb-4">
        <h2 className="mb-0 text-primary">
          <FontAwesomeIcon icon={faUserShield} className="me-2" />
          Gestión de Permisos por Módulo
        </h2>
        <Button variant="outline-secondary" onClick={() => navigate(-1)}>
          <FontAwesomeIcon icon={faArrowLeft} className="me-2" />
          Volver
        </Button>
      </div>

      <Alert variant="info" className="mb-4">
        Los usuarios con rol <strong>Admin</strong> tienen acceso automático a todos los módulos. Usa los botones de la tabla para cambiar el rol o el estado de cada usuario.
      </Alert>

      <Card className="shadow-sm">
        <Card.Header className="bg-primary text-white d-flex justify-content-between align-items-center">
          <h5 className="mb-0">
            <FontAwesomeIcon icon={faUserShield} className="me-2" />
            Usuarios del Sistema
          </h5>
          <Badge bg="light" text="dark">{usuarios.length} usuarios</Badge>
        </Card.Header>
        <Card.Body className="p-0">
          {loading ? (
            <div className="text-center py-5">
              <FontAwesomeIcon icon={faSpinner} spin size="2x" className="text-primary" />
              <p className="mt-2 text-muted">Cargando...</p>
            </div>
          ) : (
            <div className="table-responsive">
              <Table striped hover bordered size="sm" className="mb-0 align-middle">
                <thead className="table-light">
                  <tr>
                    <th>Usuario</th>
                    <th>Nombre</th>
                    <th className="text-center">Admin</th>
                    <th className="text-center">Estado</th>
                    <th>Módulos Asignados</th>
                    <th className="text-center" style={{ width: '100px' }}>Editar</th>
                  </tr>
                </thead>
                <tbody>
                  {usuarios.map((u) => {
                    const esAdmin = u.sw_admin === '1' || u.sw_admin === 1;
                    const estaActivo = u.activo === '1' || u.activo === 1;
                    const esMismoUsuario = u.usuario_id === currentUser.usuario_id;
                    return (
                      <tr key={u.usuario_id}>
                        <td className="fw-bold">{u.usuario}</td>
                        <td>{u.nombre}</td>
                        <td className="text-center">
                          <Button
                            variant={esAdmin ? 'danger' : 'outline-secondary'}
                            size="sm"
                            onClick={() => handleToggleAdmin(u)}
                            disabled={esMismoUsuario && esAdmin}
                            title={esMismoUsuario && esAdmin ? 'No puedes quitarte el Admin' : esAdmin ? 'Clic para quitar Admin' : 'Clic para hacer Admin'}
                            style={{ minWidth: '85px' }}
                          >
                            <FontAwesomeIcon icon={faCrown} className="me-1" />
                            {esAdmin ? 'Admin' : 'Normal'}
                          </Button>
                        </td>
                        <td className="text-center">
                          <Button
                            variant={estaActivo ? 'success' : 'secondary'}
                            size="sm"
                            onClick={() => handleToggleActivo(u)}
                            disabled={esMismoUsuario}
                            title={esMismoUsuario ? 'No puedes inactivarte a ti mismo' : estaActivo ? 'Clic para inactivar' : 'Clic para activar'}
                            style={{ minWidth: '90px' }}
                          >
                            <FontAwesomeIcon icon={estaActivo ? faToggleOn : faToggleOff} className="me-1" />
                            {estaActivo ? 'Activo' : 'Inactivo'}
                          </Button>
                        </td>
                        <td>
                          {esAdmin ? (
                            <Badge bg="danger">Todos (Admin)</Badge>
                          ) : u.modulos.length === 0 ? (
                            <span className="text-muted fst-italic">Sin módulos asignados</span>
                          ) : (
                            u.modulos.map((m) => (
                              <Badge key={m} bg="primary" className="me-1 mb-1">{m}</Badge>
                            ))
                          )}
                        </td>
                        <td className="text-center">
                          <Button
                            variant="outline-primary"
                            size="sm"
                            onClick={() => handleEditar(u)}
                            disabled={esAdmin}
                            title={esAdmin ? 'Admin tiene acceso total' : 'Editar módulos'}
                          >
                            <FontAwesomeIcon icon={faEdit} />
                          </Button>
                        </td>
                      </tr>
                    );
                  })}
                </tbody>
              </Table>
            </div>
          )}
        </Card.Body>
      </Card>

      {/* Modal de edición de permisos */}
      <Modal show={modalOpen} onHide={() => setModalOpen(false)} centered>
        <Modal.Header closeButton className="bg-primary text-white">
          <Modal.Title>
            <FontAwesomeIcon icon={faUserShield} className="me-2" />
            Módulos para: {usuarioEditar?.nombre}
          </Modal.Title>
        </Modal.Header>
        <Modal.Body>
          <p className="text-muted mb-3">Selecciona los módulos a los que tendrá acceso este usuario:</p>
          <Row>
            {catalogo.map((modulo) => (
              <Col xs={12} key={modulo.id} className="mb-2">
                <Form.Check
                  type="checkbox"
                  id={`modulo-${modulo.id}`}
                  label={
                    <span>
                      <i className={`fas ${modulo.icono} me-2 text-primary`}></i>
                      <strong>{modulo.titulo}</strong>
                    </span>
                  }
                  checked={modulosSeleccionados.includes(modulo.id)}
                  onChange={() => toggleModulo(modulo.id)}
                />
              </Col>
            ))}
          </Row>
        </Modal.Body>
        <Modal.Footer>
          <Button variant="secondary" onClick={() => setModalOpen(false)} disabled={saving}>
            Cancelar
          </Button>
          <Button variant="primary" onClick={handleGuardar} disabled={saving}>
            {saving ? (
              <><FontAwesomeIcon icon={faSpinner} spin className="me-1" /> Guardando...</>
            ) : (
              <><FontAwesomeIcon icon={faSave} className="me-1" /> Guardar Permisos</>
            )}
          </Button>
        </Modal.Footer>
      </Modal>
    </div>
  );
};

export default UsuarioPermisosView;
