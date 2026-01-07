import axios from 'axios'

const axiosAdmin = axios.create({
  baseURL: '/api/admin',
  withCredentials: true,
})

axiosAdmin.interceptors.request.use(config => {
  const token = localStorage.getItem('employee_token')
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }
  return config
})

axiosAdmin.interceptors.response.use(
  response => response,
  error => {
    if (error.response?.status === 401) {
      localStorage.removeItem('employee_token')
      localStorage.removeItem('employee')
      window.location.href = '/admin/login'
    }
    return Promise.reject(error)
  }
)

export default axiosAdmin
