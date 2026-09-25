import api from '../../../utils/api';

const ordenServicioService = {
  getAll: async (params) => {
    const response = await api.get('/ordenes-servicio', { params });
    return response.data;
  },

  getOne: async (id) => {
    const response = await api.get(`/ordenes-servicio/${id}`);
    return response.data;
  },

  create: async (data) => {
    const response = await api.post('/ordenes-servicio', data);
    return response.data;
  },

  update: async (id, data) => {
    const response = await api.put(`/ordenes-servicio/${id}`, data);
    return response.data;
  },

  delete: async (id) => {
    const response = await api.delete(`/ordenes-servicio/${id}`);
    return response.data;
  },

  solicitarFirma: async (id) => {
    const response = await api.post(`/ordenes-servicio/${id}/solicitar-firma`);
    return response.data;
  },

  // Descarga el PDF de la orden (con la firma del cliente si ya la firmó)
  descargarPdf: async (id, numeroOrden) => {
    const response = await api.get(`/ordenes-servicio/${id}/pdf`, { responseType: 'blob' });
    const blobUrl = window.URL.createObjectURL(new Blob([response.data], { type: 'application/pdf' }));
    const a = document.createElement('a');
    a.href = blobUrl;
    a.download = `${numeroOrden || `orden-${id}`}.pdf`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    window.URL.revokeObjectURL(blobUrl);
  },

  enviarEmail: async (id, email) => {
    const response = await api.post(`/ordenes-servicio/${id}/enviar-email`, { email });
    return response.data;
  },

  verificarTokenFirma: async (id, token) => {
    const response = await api.get(`/public/ordenes-servicio/${id}/firmar/${token}`);
    return response.data;
  },

  // payload: { nombre, documento, firma (PNG en base64) }
  guardarFirma: async (id, token, payload) => {
    const response = await api.post(`/public/ordenes-servicio/${id}/firmar/${token}`, payload);
    return response.data;
  },
};

export default ordenServicioService;
