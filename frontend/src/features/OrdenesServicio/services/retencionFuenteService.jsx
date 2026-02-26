import api from '../../../utils/api';

const retencionFuenteService = {
  // Obtener todas las retenciones activas
  getAll: async () => {
    const response = await api.get('/retencion-fuente');
    return response.data;
  },
};

export default retencionFuenteService;
