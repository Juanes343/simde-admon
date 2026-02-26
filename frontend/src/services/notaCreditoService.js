import apiClient from './apiClient';

/**
 * Servicio para gestionar Notas Crédito
 */
const notaCreditoService = {
  /**
   * Obtener lista de notas crédito con filtros
   */
  async getNotas(filtros = {}) {
    try {
      const params = new URLSearchParams();
      
      if (filtros.empresa_id) params.append('empresa_id', filtros.empresa_id);
      if (filtros.estado) params.append('estado', filtros.estado);
      if (filtros.prefijo) params.append('prefijo', filtros.prefijo);
      if (filtros.fecha_desde) params.append('fecha_desde', filtros.fecha_desde);
      if (filtros.fecha_hasta) params.append('fecha_hasta', filtros.fecha_hasta);

      const response = await apiClient.get(`/notas-credito?${params.toString()}`);
      return response.data;
    } catch (error) {
      console.error('Error obteniendo notas crédito:', error);
      throw error;
    }
  },

  /**
   * Obtener detalle de una nota crédito
   */
  async getNotaDetalle(id) {
    try {
      const response = await apiClient.get(`/notas-credito/${id}`);
      return response.data;
    } catch (error) {
      console.error(`Error obteniendo nota crédito ${id}:`, error);
      throw error;
    }
  },

  /**
   * Crear nueva nota crédito
   */
  async crearNotaCredito(data) {
    try {
      const response = await apiClient.post('/notas-credito', {
        empresa_id: data.empresa_id,
        prefijo: data.prefijo,
        prefijo_factura: data.prefijo_factura,
        factura_fiscal: data.factura_fiscal,
        concepto_id: data.concepto_id,
        valor_nota: data.valor_nota,
        observacion: data.observacion || null,
      });
      return response.data;
    } catch (error) {
      console.error('Error creando nota crédito:', error);
      throw error;
    }
  },

  /**
   * Enviar nota crédito a DATAICO
   */
  async enviarNotaCredito(id) {
    try {
      const response = await apiClient.post(`/notas-credito/${id}/enviar`);
      return response.data;
    } catch (error) {
      console.error(`Error enviando nota crédito ${id}:`, error);
      throw error;
    }
  },

  /**
   * Eliminar nota crédito (solo si está PENDIENTE)
   */
  async eliminarNotaCredito(id) {
    try {
      const response = await apiClient.delete(`/notas-credito/${id}`);
      return response.data;
    } catch (error) {
      console.error(`Error eliminando nota crédito ${id}:`, error);
      throw error;
    }
  },

  /**
   * Obtener lista de conceptos de notas crédito
   */
  async getConceptos(empresaId = null) {
    try {
      let url = '/notas-credito-conceptos';
      if (empresaId) {
        url += `?empresa_id=${empresaId}`;
      }
      const response = await apiClient.get(url);
      return response.data;
    } catch (error) {
      console.error('Error obteniendo conceptos:', error);
      throw error;
    }
  },

  /**
   * Obtener estadísticas de notas crédito
   */
  async getEstadisticas(empresaId = null) {
    try {
      let url = '/notas-credito/estadisticas';
      if (empresaId) {
        url += `?empresa_id=${empresaId}`;
      }
      const response = await apiClient.get(url);
      return response.data;
    } catch (error) {
      console.error('Error obteniendo estadísticas:', error);
      throw error;
    }
  },
};

export default notaCreditoService;
