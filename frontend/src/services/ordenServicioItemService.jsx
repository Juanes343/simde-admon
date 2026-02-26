import api from './api';

const ordenServicioItemService = {
  /**
   * Cambiar estado de un item de orden de servicio
   * @param {number} itemId - ID del item
   * @param {string} estado - Nuevo estado (0=Inactivo, 1=Activo)
   */
  cambiarEstado: async (itemId, estado) => {
    const response = await api.post(`/orden-servicio-items/${itemId}/cambiar-estado`, {
      estado
    });
    return response.data;
  }
};

export default ordenServicioItemService;
