import React, { useState, useRef, useEffect } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import axios from 'axios';
import { Container, Row, Col, Button, Card, Alert, Spinner } from 'react-bootstrap';
import SignatureCanvas from 'react-signature-canvas';
import { toast } from 'react-toastify';

const OrdenServicioSignaturePage = () => {
    const { id, token } = useParams();
    const navigate = useNavigate();
    const sigCanvas = useRef({});
    const [loading, setLoading] = useState(false);
    const [verifying, setVerifying] = useState(true);
    const [error, setError] = useState(null);
    const [ordenData, setOrdenData] = useState(null);
    const [success, setSuccess] = useState(false);

    // Verificar token al cargar
    useEffect(() => {
        const verifyToken = async () => {
            try {
                // Aquí deberíamos tener un endpoint para verificar el token y obtener datos básicos de la orden
                // Por ahora asumimos que si el token es válido, podemos mostrar la interfaz
                // Podríamos hacer una llamada GET para validar y obtener info:
                // const response = await axios.get(`${import.meta.env.VITE_API_URL}/api/ordenes-servicio/verificar-firma/${id}/${token}`);
                // setOrdenData(response.data);
                setVerifying(false);
            } catch (err) {
                console.error("Error verificando token:", err);
                setError("El enlace de firma es inválido o ha expirado.");
                setVerifying(false);
            }
        };

        verifyToken();
    }, [id, token]);

    const clearSignature = () => {
        sigCanvas.current.clear();
    };

    const saveSignature = async () => {
        if (sigCanvas.current.isEmpty()) {
            toast.warning("Por favor, firme en el recuadro antes de guardar.");
            return;
        }

        setLoading(true);
        setError(null);

        const signatureData = sigCanvas.current.getTrimmedCanvas().toDataURL('image/png');

        try {
            // URL base por defecto para producción si no hay variable de entorno
            const defaultUrl = 'https://devel82els.simde.com.co/simde-admon/backend/public';
            const apiUrl = import.meta.env.VITE_API_URL || defaultUrl; 
            
            // Asegurar que la URL no termine en slash para evitar doble slash
            const cleanUrl = apiUrl.replace(/\/$/, '');

            await axios.post(`${cleanUrl}/api/public/ordenes-servicio/firmar`, {
                id: id,
                token: token,
                firma: signatureData
            });

            setSuccess(true);
            toast.success("Firma guardada correctamente.");
            
            // Opcional: Redirigir o mostrar mensaje final
        } catch (err) {
            console.error("Error guardando firma:", err);
            setError(err.response?.data?.message || "Error al guardar la firma. Por favor intente nuevamente.");
            toast.error("Error al guardar la firma.");
        } finally {
            setLoading(false);
        }
    };

    if (verifying) {
        return (
            <Container className="d-flex justify-content-center align-items-center" style={{ minHeight: '100vh' }}>
                <Spinner animation="border" role="status">
                    <span className="visually-hidden">Verificando...</span>
                </Spinner>
            </Container>
        );
    }

    if (error) {
        return (
            <Container className="mt-5">
                <Alert variant="danger">
                    <Alert.Heading>Error</Alert.Heading>
                    <p>{error}</p>
                </Alert>
            </Container>
        );
    }

    if (success) {
        return (
            <Container className="mt-5 text-center">
                <Card className="p-5 shadow-sm">
                    <Card.Body>
                        <div className="mb-4 text-success">
                            <i className="fas fa-check-circle fa-5x"></i>
                        </div>
                        <Card.Title as="h2">¡Firma Recibida!</Card.Title>
                        <Card.Text className="mt-3">
                            La orden de servicio #{id} ha sido firmada exitosamente.
                            Se ha enviado un correo con el documento firmado.
                        </Card.Text>
                        <Button variant="primary" onClick={() => window.location.href = '/'}>
                            Volver al Inicio
                        </Button>
                    </Card.Body>
                </Card>
            </Container>
        );
    }

    return (
        <Container className="py-5">
            <Row className="justify-content-center">
                <Col md={8} lg={6}>
                    <Card className="shadow">
                        <Card.Header className="bg-primary text-white">
                            <h4 className="mb-0">Firmar Orden de Servicio #{id}</h4>
                        </Card.Header>
                        <Card.Body>
                            {ordenData && (
                                <div className="mb-4">
                                    <h5>Detalles:</h5>
                                    <p><strong>Cliente:</strong> {ordenData.cliente}</p>
                                    <p><strong>Fecha:</strong> {ordenData.fecha}</p>
                                </div>
                            )}

                            <div className="mb-3">
                                <label className="form-label">Por favor firme en el siguiente recuadro:</label>
                                <div style={{ border: '2px dashed #ccc', borderRadius: '5px', height: '200px', width: '100%', backgroundColor: '#f9f9f9' }}>
                                    <SignatureCanvas 
                                        ref={sigCanvas}
                                        penColor='black'
                                        canvasProps={{
                                            width: 500, 
                                            height: 200, 
                                            className: 'sigCanvas',
                                            style: { width: '100%', height: '100%' }
                                        }} 
                                    />
                                </div>
                                <div className="form-text text-muted">
                                    Utilice su mouse o dedo (en móvil) para firmar.
                                </div>
                            </div>

                            <div className="d-grid gap-2 d-md-flex justify-content-md-end">
                                <Button variant="secondary" onClick={clearSignature} disabled={loading}>
                                    Borrar
                                </Button>
                                <Button variant="success" onClick={saveSignature} disabled={loading}>
                                    {loading ? (
                                        <>
                                            <Spinner as="span" animation="border" size="sm" role="status" aria-hidden="true" /> Guardando...
                                        </>
                                    ) : 'Guardar Firma'}
                                </Button>
                            </div>
                        </Card.Body>
                    </Card>
                </Col>
            </Row>
        </Container>
    );
};

export default OrdenServicioSignaturePage;
