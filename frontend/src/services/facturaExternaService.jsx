import api from './api';

const facturaExternaService = {
  /**
   * Sube un archivo CSV con facturas externas.
   * @param {File} archivo - Archivo CSV seleccionado por el usuario.
   * @param {Function} onUploadProgress - Callback opcional de progreso (0-100).
   */
  uploadCsv: async (archivo, onUploadProgress = null) => {
    const formData = new FormData();
    formData.append('archivo', archivo);

    const config = {
      headers: { 'Content-Type': 'multipart/form-data' },
    };
    if (onUploadProgress) {
      config.onUploadProgress = (e) => {
        const pct = Math.round((e.loaded * 100) / e.total);
        onUploadProgress(pct);
      };
    }

    const response = await api.post('/facturas-externas/upload', formData, config);
    return response.data;
  },

  /**
   * Lista facturas externas con filtros y paginación.
   * @param {Object} params - { empresa_id, prefijo, tercero_id, estado, fecha_desde, fecha_hasta, page, per_page }
   */
  getAll: async (params = {}) => {
    const response = await api.get('/facturas-externas', { params });
    return response.data;
  },

  /**
   * Detalle de una factura externa por id.
   */
  getOne: async (id) => {
    const response = await api.get(`/facturas-externas/${id}`);
    return response.data;
  },

  /**
   * Elimina una factura externa por id.
   */
  deleteOne: async (id) => {
    const response = await api.delete(`/facturas-externas/${id}`);
    return response.data;
  },

  /**
   * Descarga la plantilla CSV de ejemplo directamente (abre en nueva pestaña).
   */
  descargarPlantilla: () => {
    // Construir URL usando la base de la API configurada
    const base = api.defaults.baseURL || '';
    window.open(`${base}/facturas-externas/plantilla-csv`, '_blank');
  },
};

export default facturaExternaService;
