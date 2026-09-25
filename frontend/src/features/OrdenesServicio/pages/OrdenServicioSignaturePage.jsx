import React, { useState, useEffect } from 'react';
import { useParams } from 'react-router-dom';
import { Container, Card, Alert, Spinner } from 'react-bootstrap';
import { toast } from 'react-toastify';
import CotizacionAprobacionView from '../../Cotizaciones/views/CotizacionAprobacionView';
import CerrarPestanaButton from '../../../components/Common/CerrarPestanaButton';
import ordenServicioService from '../services/ordenServicioService';

/**
 * Página PÚBLICA a la que llega el cliente desde el botón del correo "Solicitud de firma".
 * No usa MainLayout ni PrivateRoute: la autorización es el token del enlace.
 * Reutiliza la vista de aprobación de cotizaciones (resumen del documento + nombre, documento y firma).
 */
const OrdenServicioSignaturePage = () => {
  const { id, token } = useParams();

  // cargando | listo | error | info | firmada
  const [fase, setFase] = useState('cargando');
  const [data, setData] = useState(null);
  const [mensaje, setMensaje] = useState('');
  const [numeroOrden, setNumeroOrden] = useState(null);
  const [enviando, setEnviando] = useState(false);

  // 409 = ya firmada (informativo); el resto son errores del enlace
  const mostrarError = (err, mensajePorDefecto) => {
    setMensaje(err.response?.data?.message || mensajePorDefecto);
    setFase(err.response?.status === 409 ? 'info' : 'error');
  };

  useEffect(() => {
    let cancelado = false;

    ordenServicioService.verificarTokenFirma(id, token)
      .then((res) => {
        if (cancelado) return;
        setData(res);
        setFase('listo');
      })
      .catch((err) => {
        if (cancelado) return;
        mostrarError(err, 'No se pudo cargar la orden de servicio.');
      });

    return () => { cancelado = true; };
  }, [id, token]);

  const handleFirmar = async (payload) => {
    try {
      setEnviando(true);
      const res = await ordenServicioService.guardarFirma(id, token, payload);
      setNumeroOrden(res.numero_orden);
      setFase('firmada');
    } catch (err) {
      if (err.response?.status === 422 || !err.response) {
        // Error de datos (o de red): el cliente puede corregir y reintentar
        toast.error(err.response?.data?.message || 'No se pudo conectar con el servidor. Intente nuevamente.');
      } else {
        mostrarError(err, 'No se pudo guardar la firma.');
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
          <Alert.Heading>{fase === 'info' ? 'Orden ya firmada' : 'No es posible continuar'}</Alert.Heading>
          <p className="mb-0">{mensaje}</p>
        </Alert>
      </Container>
    );
  }

  if (fase === 'firmada') {
    return (
      <Container className="mt-5" style={{ maxWidth: 640 }}>
        <Card className="p-4 shadow-sm text-center">
          <Card.Body>
            <div className="mb-3 text-success"><i className="fas fa-check-circle fa-5x"></i></div>
            <Card.Title as="h2">¡Orden de servicio firmada!</Card.Title>
            <Card.Text className="mt-3">
              Registramos su firma en la orden de servicio {numeroOrden && <strong>{numeroOrden}</strong>} y le enviamos
              una copia firmada en PDF a su correo.
            </Card.Text>
            <div className="mt-4">
              <CerrarPestanaButton />
            </div>
          </Card.Body>
        </Card>
      </Container>
    );
  }

  return (
    <CotizacionAprobacionView
      data={data}
      enviando={enviando}
      onAprobar={handleFirmar}
      onRechazar={() => {}}
      tipoDocumento="Orden de servicio"
      textoAprobar="Firmar orden de servicio"
      permitirRechazo={false}
      textoAceptacion={(
        <>
          Al pulsar <strong>Firmar orden de servicio</strong> usted acepta las condiciones de esta orden y recibirá
          una copia firmada en PDF por correo.
        </>
      )}
    />
  );
};

export default OrdenServicioSignaturePage;
