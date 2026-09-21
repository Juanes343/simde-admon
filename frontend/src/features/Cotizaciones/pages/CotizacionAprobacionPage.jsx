import React, { useState, useEffect } from 'react';
import { useParams } from 'react-router-dom';
import { Container, Card, Alert, Spinner } from 'react-bootstrap';
import { toast } from 'react-toastify';
import CotizacionAprobacionView from '../views/CotizacionAprobacionView';
import cotizacionService from '../services/cotizacionService';

/**
 * Página PÚBLICA a la que llega el cliente desde el botón del correo de la cotización.
 * No usa MainLayout ni PrivateRoute: la autorización es el token del enlace.
 */
const CotizacionAprobacionPage = () => {
  const { id, token } = useParams();

  // cargando | listo | error | info | aprobada | rechazada
  const [fase, setFase] = useState('cargando');
  const [data, setData] = useState(null);
  const [mensaje, setMensaje] = useState('');
  const [numeroOrden, setNumeroOrden] = useState(null);
  const [enviando, setEnviando] = useState(false);

  useEffect(() => {
    let cancelado = false;

    cotizacionService.getAprobacion(id, token)
      .then((res) => {
        if (cancelado) return;
        setData(res);
        setFase('listo');
      })
      .catch((err) => {
        if (cancelado) return;
        mostrarError(err, 'No se pudo cargar la cotización.');
      });

    return () => { cancelado = true; };
  }, [id, token]);

  // 409 = ya respondida (informativo); el resto son errores del enlace
  const mostrarError = (err, mensajePorDefecto) => {
    setMensaje(err.response?.data?.message || mensajePorDefecto);
    setFase(err.response?.status === 409 ? 'info' : 'error');
  };

  const handleAprobar = async (payload) => {
    try {
      setEnviando(true);
      const res = await cotizacionService.aprobarPublica(id, token, payload);
      setNumeroOrden(res.numero_orden);
      setFase('aprobada');
    } catch (err) {
      if (err.response?.status === 422 || !err.response) {
        // Error de datos (o de red): el cliente puede corregir y reintentar
        toast.error(err.response?.data?.message || 'No se pudo conectar con el servidor. Intente nuevamente.');
      } else {
        mostrarError(err, 'No se pudo registrar la aprobación.');
      }
    } finally {
      setEnviando(false);
    }
  };

  const handleRechazar = async (motivo) => {
    try {
      setEnviando(true);
      await cotizacionService.rechazarPublica(id, token, motivo);
      setFase('rechazada');
    } catch (err) {
      if (!err.response) {
        toast.error('No se pudo conectar con el servidor. Intente nuevamente.');
      } else {
        mostrarError(err, 'No se pudo registrar la respuesta.');
      }
    } finally {
      setEnviando(false);
    }
  };

  if (fase === 'cargando') {
    return (
      <Container className="d-flex justify-content-center align-items-center" style={{ minHeight: '100vh' }}>
        <Spinner animation="border" role="status">
          <span className="visually-hidden">Cargando...</span>
        </Spinner>
      </Container>
    );
  }

  if (fase === 'error' || fase === 'info') {
    return (
      <Container className="mt-5" style={{ maxWidth: 640 }}>
        <Alert variant={fase === 'info' ? 'info' : 'danger'}>
          <Alert.Heading>{fase === 'info' ? 'Cotización ya respondida' : 'No es posible continuar'}</Alert.Heading>
          <p className="mb-0">{mensaje}</p>
        </Alert>
      </Container>
    );
  }

  if (fase === 'aprobada') {
    return (
      <Container className="mt-5" style={{ maxWidth: 640 }}>
        <Card className="p-4 shadow-sm text-center">
          <Card.Body>
            <div className="mb-3 text-success"><i className="fas fa-check-circle fa-5x"></i></div>
            <Card.Title as="h2">¡Cotización aprobada!</Card.Title>
            <Card.Text className="mt-3">
              Registramos su aprobación y firma. Se generó la orden de servicio
              {numeroOrden && <> <strong>{numeroOrden}</strong></>} y le enviamos una copia en PDF a su correo.
            </Card.Text>
          </Card.Body>
        </Card>
      </Container>
    );
  }

  if (fase === 'rechazada') {
    return (
      <Container className="mt-5" style={{ maxWidth: 640 }}>
        <Card className="p-4 shadow-sm text-center">
          <Card.Body>
            <div className="mb-3 text-secondary"><i className="fas fa-times-circle fa-5x"></i></div>
            <Card.Title as="h2">Respuesta registrada</Card.Title>
            <Card.Text className="mt-3">
              Hemos registrado que la cotización no fue aprobada. Gracias por su respuesta.
            </Card.Text>
          </Card.Body>
        </Card>
      </Container>
    );
  }

  return (
    <CotizacionAprobacionView
      data={data}
      enviando={enviando}
      onAprobar={handleAprobar}
      onRechazar={handleRechazar}
    />
  );
};

export default CotizacionAprobacionPage;
