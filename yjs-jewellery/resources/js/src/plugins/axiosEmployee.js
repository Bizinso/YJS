import axios from 'axios'

const axiosEmployee = axios.create({
  baseURL: '/api/employee',
  withCredentials: true,
})

axiosEmployee.interceptors.request.use(config => {
  const token = localStorage.getItem('employee_token')
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }
  return config
})

export default axiosEmployee