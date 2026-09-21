import api from '../../../utils/api';

const cotizacionService = {
  getAll: async (params) => {
    const response = await api.get('/cotizaciones', { params });
    return response.data;
  },

  getOne: async (id) => {
    const response = await api.get(`/cotizaciones/${id}`);
    return response.data;
  },

  create: async (data) => {
    const response = await api.post('/cotizaciones', data);
    return response.data;
  },

  update: async (id, data) => {
    const response = await api.put(`/cotizaciones/${id}`, data);
    return response.data;
  },

  delete: async (id) => {
    const response = await api.delete(`/cotizaciones/${id}`);
    return response.data;
  },

  cambiarEstado: async (id, estado) => {
    const response = await api.patch(`/cotizaciones/${id}/estado`, { estado });
    return response.data;
  },

  convertirAOrden: async (id, data) => {
    const response = await api.post(`/cotizaciones/${id}/convertir`, data);
    return response.data;
  },

  descargarPdf: (id, numeroCotizacion) => {
    const token = localStorage.getItem('token');
    const url = `${api.defaults.baseURL}/cotizaciones/${id}/pdf`;
    const fileName = numeroCotizacion ? `${numeroCotizacion}.pdf` : `cotizacion-${id}.pdf`;
    fetch(url, { headers: { Authorization: `Bearer ${token}` } })
      .then((res) => res.blob())
      .then((blob) => {
        const blobUrl = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = blobUrl;
        a.download = fileName;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        window.URL.revokeObjectURL(blobUrl);
      });
  },

  // `adjuntos`: File[] opcionales que se envían junto al PDF de la cotización.
  // `datosOrden`: datos de la orden de servicio que se crea si el cliente aprueba desde el correo
  // (obligatorio cuando la cotización está en borrador/enviada).
  enviarEmail: async (id, email, adjuntos = [], datosOrden = null) => {
    const formData = new FormData();
    formData.append('email', email);
    adjuntos.forEach((archivo) => formData.append('adjuntos[]', archivo));
    if (datosOrden) {
      Object.entries(datosOrden).forEach(([campo, valor]) => {
        if (valor !== '' && valor !== null && valor !== undefined) {
          formData.append(`datos_orden[${campo}]`, valor);
        }
      });
    }

    const response = await api.post(`/cotizaciones/${id}/enviar-email`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });
    return response.data;
  },

  // ── Aprobación pública (enlace del correo, sin sesión) ──────────────────
  getAprobacion: async (id, token) => {
    const response = await api.get(`/public/cotizaciones/${id}/aprobar/${token}`);
    return response.data;
  },

  aprobarPublica: async (id, token, payload) => {
    const response = await api.post(`/public/cotizaciones/${id}/aprobar/${token}`, payload);
    return response.data;
  },

  rechazarPublica: async (id, token, motivo) => {
    const response = await api.post(`/public/cotizaciones/${id}/rechazar/${token}`, { motivo });
    return response.data;
  },
};

export default cotizacionService;
