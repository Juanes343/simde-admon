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
};

export default ordenServicioService;
