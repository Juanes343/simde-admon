import api from '../utils/api';

export const tipoUnidadServicioService = {
  getAll: async () => {
    const response = await api.get('/tipos-unidad-servicio');
    return response.data;
  },
};
