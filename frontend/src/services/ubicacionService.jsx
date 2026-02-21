import api from './api';

export const ubicacionService = {
  getPaises: async () => {
    try {
      const response = await api.get('/ubicacion/paises');
      return { success: true, data: response.data };
    } catch (error) {
      return { success: false, message: error.response?.data?.message || 'Error al cargar países' };
    }
  },
  getDepartamentos: async (paisId) => {
    try {
      const response = await api.get(`/ubicacion/departamentos/${paisId}`);
      return { success: true, data: response.data };
    } catch (error) {
      return { success: false, message: error.response?.data?.message || 'Error al cargar departamentos' };
    }
  },
  getMunicipios: async (paisId, dptoId) => {
    try {
      const response = await api.get(`/ubicacion/municipios/${paisId}/${dptoId}`);
      return { success: true, data: response.data };
    } catch (error) {
      return { success: false, message: error.response?.data?.message || 'Error al cargar municipios' };
    }
  }
};
