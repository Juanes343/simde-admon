import api from '../../../utils/api';

export const terceroService = {
  getAll: async (params) => {
    const response = await api.get('/terceros', { params });
    return response.data;
  },

  getOne: async (tipo_id_tercero, tercero_id) => {
    const response = await api.get(`/terceros/${tipo_id_tercero}/${tercero_id}`);
    return response.data;
  },

  create: async (data) => {
    const response = await api.post('/terceros', data);
    return response.data;
  },

  update: async (tipo_id_tercero, tercero_id, data) => {
    const response = await api.put(`/terceros/${tipo_id_tercero}/${tercero_id}`, data);
    return response.data;
  },

  delete: async (tipo_id_tercero, tercero_id) => {
    const response = await api.delete(`/terceros/${tipo_id_tercero}/${tercero_id}`);
    return response.data;
  },

  uploadPdf: async (formData) => {
    const response = await api.post('/terceros/upload-pdf', formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
    });
    return response.data;
  },
};
