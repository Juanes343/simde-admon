import api from './api';

const notasCreditoService = {
  getConceptos: async () => {
    const response = await api.get('/notas-credito-conceptos');
    return response.data;
  },

  getNotas: async (page = 1, params = {}) => {
    const response = await api.get('/notas-credito', {
      params: { ...params, page }
    });
    return response.data;
  },

  crearNota: async (data) => {
    const response = await api.post('/notas-credito', data);
    return response.data;
  },

  obtenerNota: async (id) => {
    const response = await api.get(`/notas-credito/${id}`);
    return response.data;
  },

  enviarNota: async (id) => {
    const response = await api.post(`/notas-credito/${id}/enviar`);
    return response.data;
  },

  eliminarNota: async (id) => {
    const response = await api.delete(`/notas-credito/${id}`);
    return response.data;
  },

  estadisticas: async () => {
    const response = await api.get('/notas-credito/estadisticas');
    return response.data;
  }
};

export default notasCreditoService;
