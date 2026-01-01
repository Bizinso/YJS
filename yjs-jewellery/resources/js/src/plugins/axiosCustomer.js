import axios from 'axios'

const axiosCustomer = axios.create({
  baseURL: '/api/customer',
  withCredentials: true,
})

axiosCustomer.interceptors.request.use(config => {
  const token = localStorage.getItem('customer_token')
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }
  return config
})

export default axiosCustomer

