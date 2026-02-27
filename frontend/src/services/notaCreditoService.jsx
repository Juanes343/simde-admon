import api from './api';

const notaCreditoService = {
  // Obtener conceptos de notas crédito
  getConceptos: async (empresaId) => {
    try {
      const response = await api.get('/notas-credito-conceptos', {
        params: {
          empresa_id: empresaId,
        },
      });
      return response.data;
    } catch (error) {
      console.error('Error getting conceptos:', error);
      throw error;
    }
  },

  // Obtener prefijos disponibles
  getPrefijos: async (empresaId, tipo = 'C') => {
    try {
      const response = await api.get('/notas-credito/prefijos', {
        params: {
          empresa_id: empresaId,
          tipo: tipo,
        },
      });
      return response.data;
    } catch (error) {
      console.error('Error getting prefijos:', error);
      throw error;
    }
  },

  // Obtener lista de notas crédito
  getNotas: async (filtros = {}) => {
    try {
      const response = await api.get('/notas-credito', {
        params: filtros,
      });
      return response.data;
    } catch (error) {
      console.error('Error getting notas:', error);
      throw error;
    }
  },

  // Obtener detalle de una nota crédito
  obtenerNota: async (notaId) => {
    try {
      const response = await api.get(`/notas-credito/${notaId}`);
      return response.data;
    } catch (error) {
      console.error('Error getting nota:', error);
      throw error;
    }
  },

  // Crear nueva nota crédito
  crearNota: async (payload) => {
    try {
      const response = await api.post('/notas-credito', payload);
      return response.data;
    } catch (error) {
      console.error('Error creating nota:', error);
      throw error.response?.data || error;
    }
  },

  // Enviar nota crédito
  enviarNota: async (notaId) => {
    try {
      const response = await api.post(`/notas-credito/${notaId}/enviar`);
      return response.data;
    } catch (error) {
      console.error('Error sending nota:', error);
      throw error.response?.data || error;
    }
  },

  // Eliminar nota crédito
  eliminarNota: async (notaId) => {
    try {
      const response = await api.delete(`/notas-credito/${notaId}`);
      return response.data;
    } catch (error) {
      console.error('Error deleting nota:', error);
      throw error.response?.data || error;
    }
  },

  // Descargar ZIP (PDF + XML) de DataIco
  descargarZip: async (notaId) => {
    try {
      const response = await api.get(`/notas-credito/${notaId}/descargar-zip`, {
        responseType: 'blob', // Importante para recibir archivos binarios
      });
      return response.data;
    } catch (error) {
      console.error('Error downloading ZIP:', error);
      throw error;
    }
  },

  // Descargar PDF de DataIco
  descargarPdf: async (notaId) => {
    try {
      const response = await api.get(`/notas-credito/${notaId}/descargar-pdf`, {
        responseType: 'blob',
      });
      return response.data;
    } catch (error) {
      console.error('Error downloading PDF:', error);
      throw error;
    }
  },

  // Descargar XML de DataIco
  descargarXml: async (notaId) => {
    try {
      const response = await api.get(`/notas-credito/${notaId}/descargar-xml`, {
        responseType: 'blob',
      });
      return response.data;
    } catch (error) {
      console.error('Error downloading XML:', error);
      throw error;
    }
  },

  // Obtener estadísticas
  estadisticas: async (filtros = {}) => {
    try {
      const response = await api.get('/notas-credito/estadisticas', {
        params: filtros,
      });
      return response.data;
    } catch (error) {
      console.error('Error getting estadisticas:', error);
      throw error;
    }
  },
};

export default notaCreditoService;
