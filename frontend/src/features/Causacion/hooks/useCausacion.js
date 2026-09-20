import { useState, useCallback } from 'react';
import causacionService from '../services/causacionService';
import Swal from 'sweetalert2';

const useCausacion = () => {
  const [terceroSeleccionado, setTerceroSeleccionado] = useState(null);
  const [anticipo, setAnticipo] = useState(null);
  const [facturas, setFacturas] = useState([]);
  const [pagos, setPagos] = useState({}); // { factura_fiscal_id: { valor_pago, valor_retencion } }
  const [usarAnticipo, setUsarAnticipo] = useState(false);
  const [loading, setLoading] = useState(false);
  const [loadingPago, setLoadingPago] = useState(false);

  const buscarFacturas = useCallback(async (empresaId, tipoIdTercero, terceroId) => {
    try {
      setLoading(true);
      const [resAnticipo, resFacturas] = await Promise.all([
        causacionService.getAnticipo(empresaId, tipoIdTercero, terceroId),
        causacionService.getFacturasPendientes(empresaId, tipoIdTercero, terceroId),
      ]);
      setAnticipo(resAnticipo.data.anticipo);
      setUsarAnticipo((resAnticipo.data.anticipo?.saldo || 0) > 0);
      setFacturas(resFacturas.data.data || []);
      // Inicializar pagos con 0
      const initPagos = {};
      (resFacturas.data.data || []).forEach((f) => {
        initPagos[f.factura_fiscal_id] = { valor_pago: '', valor_retencion: '', valor_ica: '', valor_reteiva: '' };
      });
      setPagos(initPagos);
    } catch (err) {
      Swal.fire('Error', 'No se pudieron cargar las facturas pendientes', 'error');
    } finally {
      setLoading(false);
    }
  }, []);

  const actualizarPago = (facturaId, campo, valor) => {
    setPagos((prev) => ({
      ...prev,
      [facturaId]: { ...prev[facturaId], [campo]: valor },
    }));
  };

  const registrarPagos = async (empresaId, tipoIdTercero, terceroId, fechaPago, tipoPago, observaciones) => {
    const pagosValidos = facturas
      .filter((f) => parseFloat(pagos[f.factura_fiscal_id]?.valor_pago) > 0)
      .map((f) => ({
        factura_fiscal_id: f.factura_fiscal_id,
        valor_pago: parseFloat(pagos[f.factura_fiscal_id].valor_pago) || 0,
        valor_retencion: parseFloat(pagos[f.factura_fiscal_id].valor_retencion) || 0,
        valor_ica: parseFloat(pagos[f.factura_fiscal_id].valor_ica) || 0,
        valor_reteiva: parseFloat(pagos[f.factura_fiscal_id].valor_reteiva) || 0,
      }));

    if (pagosValidos.length === 0) {
      Swal.fire('Atención', 'Ingresa al menos un valor de pago mayor a 0', 'warning');
      return false;
    }

    try {
      setLoadingPago(true);
      await causacionService.registrarPagos({
        empresa_id: empresaId,
        tipo_id_tercero: tipoIdTercero,
        tercero_id: terceroId,
        fecha_pago: fechaPago,
        tipo_pago: tipoPago,
        observaciones,
        pagos: pagosValidos,
        usar_anticipo: usarAnticipo,
      });
      Swal.fire('Éxito', 'Pagos registrados correctamente', 'success');
      return true;
    } catch (err) {
      Swal.fire('Error', err?.response?.data?.message || 'Error al registrar pagos', 'error');
      return false;
    } finally {
      setLoadingPago(false);
    }
  };

  const resetear = () => {
    setTerceroSeleccionado(null);
    setAnticipo(null);
    setFacturas([]);
    setPagos({});
    setUsarAnticipo(false);
  };

  return {
    terceroSeleccionado, setTerceroSeleccionado,
    anticipo,
    facturas,
    pagos,
    usarAnticipo, setUsarAnticipo,
    loading,
    loadingPago,
    buscarFacturas,
    actualizarPago,
    registrarPagos,
    resetear,
  };
};

export default useCausacion;
