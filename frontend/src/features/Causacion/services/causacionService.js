import api from '../../../services/api';

const causacionService = {
  getAnticipo: (empresaId, tipoIdTercero, terceroId) =>
    api.get('/causacion/anticipos', {
      params: { empresa_id: empresaId, tipo_id_tercero: tipoIdTercero, tercero_id: terceroId },
    }),

  upsertAnticipo: (data) => api.post('/causacion/anticipos', data),

  getFacturasPendientes: (empresaId, tipoIdTercero, terceroId) =>
    api.get('/causacion/facturas-pendientes', {
      params: { empresa_id: empresaId, tipo_id_tercero: tipoIdTercero, tercero_id: terceroId },
    }),

  registrarPagos: (data) => api.post('/causacion/registrar-pagos', data),

  getPagos: (params) => api.get('/causacion/pagos', { params }),

  actualizarPago: (id, data) => api.put(`/causacion/pagos/${id}`, data),

  // Reporte de facturas y pagos (previsualización en JSON)
  getReporte: (params) => api.get('/causacion/reporte', { params }),

  // Descarga del reporte en Excel (.xls generado por el backend)
  descargarReporte: async (params) => {
    const response = await api.get('/causacion/reporte/excel', {
      params,
      responseType: 'blob',
    });

    const blob = new Blob([response.data], {
      type: response.data?.type || 'application/vnd.ms-excel',
    });
    const blobUrl = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = blobUrl;
    a.download = `reporte-facturas-pagos-${new Date().toISOString().split('T')[0]}.xls`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    window.URL.revokeObjectURL(blobUrl);
  },
};

export default causacionService;
