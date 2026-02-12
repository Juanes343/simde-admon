import axios from 'axios';

const API_URL = process.env.REACT_APP_API_URL || 'http://localhost:8000/api';

const api = axios.create({
  baseURL: API_URL,
  headers: {
    'Content-Type': 'application/json',
  },
});

// Interceptor para añadir token a las peticiones
api.interceptors.request.use(
  (config) => {
    const token = localStorage.getItem('token');
    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
  },
  (error) => {
    return Promise.reject(error);
  }
);

// Interceptor para manejar errores de respuesta
api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      localStorage.removeItem('token');
      localStorage.removeItem('user');
      window.location.href = '/login';
    }
    return Promise.reject(error);
  }
);

export const authService = {
  login: (credentials) => api.post('/login', credentials),
  register: (userData) => api.post('/register', userData),
  logout: () => api.post('/logout'),
  me: () => api.get('/me'),
};

export const terceroService = {
  getAll: (params) => api.get('/terceros', { params }),
  getOne: (tipo_id_tercero, tercero_id) => api.get(`/terceros/${tipo_id_tercero}/${tercero_id}`),
  create: (data) => api.post('/terceros', data),
  update: (tipo_id_tercero, tercero_id, data) => api.put(`/terceros/${tipo_id_tercero}/${tercero_id}`, data),
  delete: (tipo_id_tercero, tercero_id) => api.delete(`/terceros/${tipo_id_tercero}/${tercero_id}`),
  uploadPdf: (formData) => api.post('/terceros/upload-pdf', formData, {
    headers: {
      'Content-Type': 'multipart/form-data',
    },
  }),
};

export default api;
