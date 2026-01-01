// src/axios.js
import axios from 'axios';

const instance = axios.create({
    baseURL:  window.location.origin + '/api/',
});

instance.interceptors.request.use((config) => {
  const token = localStorage.getItem('access_token');
  if (token) {
    config.headers['Authorization'] = `Bearer ${token}`;
  }
  return config;
});

export default instance;
