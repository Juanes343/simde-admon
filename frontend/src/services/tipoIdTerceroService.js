import api from '../utils/api';

export const tipoIdTerceroService = {
  getAll: async () => {
    const response = await api.get('/tipos-id-tercero');
    return response.data;
  },
};
