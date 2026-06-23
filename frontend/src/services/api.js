import axios from 'axios';

const baseURL = import.meta.env.VITE_API_BASE_URL || 'http://localhost/backend/api';

const client = axios.create({
  baseURL,
  headers: {
    'Content-Type': 'application/json'
  }
});

client.interceptors.request.use(config => {
  const token = localStorage.getItem('project_tracker_token');
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

client.interceptors.response.use(
  response => response,
  error => {
    if (error.response?.status === 401) {
      localStorage.removeItem('project_tracker_token');
    }
    return Promise.reject(error);
  }
);

const api = {
  setToken(token) {
    if (token) {
      client.defaults.headers.common.Authorization = `Bearer ${token}`;
    } else {
      delete client.defaults.headers.common.Authorization;
    }
  },
  get(path, config) {
    return client.get(path, config);
  },
  post(path, payload, config) {
    return client.post(path, payload, config);
  },
  put(path, payload, config) {
    return client.put(path, payload, config);
  },
  patch(path, payload, config) {
    return client.patch(path, payload, config);
  },
  delete(path, config) {
    return client.delete(path, config);
  }
};

export default api;
