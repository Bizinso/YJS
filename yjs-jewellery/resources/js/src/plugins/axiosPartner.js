import axios from 'axios'

const axiosPartner= axios.create({
  baseURL: '/api/partner',
  withCredentials: true,
})

axiosPartner.interceptors.request.use(config => {
  const token = localStorage.getItem('partner_token')
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }
  return config
})

export default axiosPartner

