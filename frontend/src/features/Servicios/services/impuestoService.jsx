import api from '../../../utils/api';

const impuestoService = {
  // Obtener todos los impuestos activos
  getAll: async () => {
    const response = await api.get('/impuestos');
    return response.data;
  },
};

export default impuestoService;