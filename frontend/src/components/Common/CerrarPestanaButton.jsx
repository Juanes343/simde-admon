import React, { useState } from 'react';
import { Button } from 'react-bootstrap';

/**
 * Botón para terminar un flujo público (aprobación de cotización, firma de orden) cerrando la pestaña.
 * Los navegadores solo permiten cerrar por script las pestañas que el propio script abrió; como estas
 * páginas se abren desde el enlace de un correo, `window.close()` normalmente no tiene efecto. Por eso,
 * si tras el intento la pestaña sigue abierta, se reemplaza el botón por un aviso para cerrarla a mano.
 */
const CerrarPestanaButton = ({ label = 'Finalizar' }) => {
  const [noSePudoCerrar, setNoSePudoCerrar] = useState(false);

  const handleClick = () => {
    window.close();
    // Si el navegador bloqueó el cierre, seguimos aquí tras el intento
    setTimeout(() => setNoSePudoCerrar(true), 300);
  };

  if (noSePudoCerrar) {
    return <p className="text-muted small mb-0">Ya puede cerrar esta pestaña.</p>;
  }

  return (
    <Button variant="outline-secondary" onClick={handleClick}>
      <i className="fas fa-check me-1"></i>{label}
    </Button>
  );
};

export default CerrarPestanaButton;
