import api from './api';

const facturacionService = {
  getPrefijos: async () => {
    const response = await api.get('/facturacion/prefijos');
    return response.data;
  },

  getPendientes: async (lapsoInicio = null, lapsoFin = null, tercero = null) => {
    const params = {};
    if (lapsoInicio) params.lapso_inicio = lapsoInicio;
    if (lapsoFin) params.lapso_fin = lapsoFin;
    if (tercero) params.tercero = tercero;
    const response = await api.get('/facturacion/pendientes', { params });
    return response.data;
  },

  generarFactura: async (data) => {
    const response = await api.post('/facturacion/facturar', data);
    return response.data;
  },

  getFacturas: async (page = 1, params = {}) => {
    const response = await api.get(`/facturacion/facturas`, { 
      params: { ...params, page } 
    });
    return response.data;
  },

  enviarDataIco: async (facturaId) => {
    const response = await api.post('/electronic-invoicing/send', { factura_fiscal_id: facturaId });
    return response.data;
  },

  // Obtener detalles de auditoría por CUFE
  getAuditByCufe: async (cufe) => {
    const response = await api.get(`/electronic-invoicing/audit/${cufe}`);
    return response.data;
  },

  // Obtener historial completo de auditorías para una factura
  getAuditHistory: async (facturaFiscalId) => {
    const response = await api.get(`/electronic-invoicing/history/${facturaFiscalId}`);
    return response.data;
  },

  // Descargar PDF desde DataIco
  downloadPdf: async (cufe) => {
    const response = await api.get(`/electronic-invoicing/pdf/${cufe}`);
    return response.data;
  },

  // Descargar XML desde DataIco
  downloadXml: async (cufe) => {
    const response = await api.get(`/electronic-invoicing/xml/${cufe}`);
    return response.data;
  }
};

export default facturacionService;
