import api from '../../../utils/api';

const cotizacionService = {
  getAll: async (params) => {
    const response = await api.get('/cotizaciones', { params });
    return response.data;
  },

  getOne: async (id) => {
    const response = await api.get(`/cotizaciones/${id}`);
    return response.data;
  },

  create: async (data) => {
    const response = await api.post('/cotizaciones', data);
    return response.data;
  },

  update: async (id, data) => {
    const response = await api.put(`/cotizaciones/${id}`, data);
    return response.data;
  },

  delete: async (id) => {
    const response = await api.delete(`/cotizaciones/${id}`);
    return response.data;
  },

  cambiarEstado: async (id, estado) => {
    const response = await api.patch(`/cotizaciones/${id}/estado`, { estado });
    return response.data;
  },

  convertirAOrden: async (id, data) => {
    const response = await api.post(`/cotizaciones/${id}/convertir`, data);
    return response.data;
  },

  descargarPdf: (id, numeroCotizacion) => {
    const token = localStorage.getItem('token');
    const url = `${api.defaults.baseURL}/cotizaciones/${id}/pdf`;
    const fileName = numeroCotizacion ? `${numeroCotizacion}.pdf` : `cotizacion-${id}.pdf`;
    fetch(url, { headers: { Authorization: `Bearer ${token}` } })
      .then((res) => res.blob())
      .then((blob) => {
        const blobUrl = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = blobUrl;
        a.download = fileName;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        window.URL.revokeObjectURL(blobUrl);
      });
  },

  enviarEmail: async (id, email) => {
    const response = await api.post(`/cotizaciones/${id}/enviar-email`, { email });
    return response.data;
  },
};

export default cotizacionService;
