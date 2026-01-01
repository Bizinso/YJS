import { ref } from 'vue'

export const isCustomerLoggedIn = ref(!!localStorage.getItem('customer_token'))

export const customerData = ref(
  localStorage.getItem('customer_data')
    ? JSON.parse(localStorage.getItem('customer_data'))
    : null
)

export const setCustomerLogin = (token, customer) => {
  localStorage.setItem('customer_token', token)
  localStorage.setItem('customer_data', JSON.stringify(customer))

  customerData.value = customer
  isCustomerLoggedIn.value = true
}

export const setCustomerLogout = () => {
  localStorage.removeItem('customer_token')
  localStorage.removeItem('customer_data')

  customerData.value = null
  isCustomerLoggedIn.value = false
}
