import api from '../../../utils/api';

const servicioService = {
  // Obtener todos los servicios con filtros
  getAll: async (params = {}) => {
    const response = await api.get('/servicios', { params });
    return response.data;
  },

  // Obtener solo servicios activos
  getActivos: async () => {
    const response = await api.get('/servicios/activos');
    return response.data;
  },

  // Obtener un servicio por ID
  getOne: async (id) => {
    const response = await api.get(`/servicios/${id}`);
    return response.data;
  },

  // Crear nuevo servicio
  create: async (data) => {
    const response = await api.post('/servicios', data);
    return response.data;
  },

  // Actualizar servicio
  update: async (id, data) => {
    const response = await api.put(`/servicios/${id}`, data);
    return response.data;
  },

  // Eliminar servicio (soft delete)
  delete: async (id) => {
    const response = await api.delete(`/servicios/${id}`);
    return response.data;
  },
};

export default servicioService;
