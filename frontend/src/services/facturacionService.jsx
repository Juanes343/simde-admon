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
    const response = await api.post('/electronic-invoicing/send', { id: facturaId });
    return response.data;
  }
};

export default facturacionService;
