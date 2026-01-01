import { ref } from 'vue'

export const isEmployeeLoggedIn = ref(!!localStorage.getItem('employee_token'))

export const employeeData = ref(
  localStorage.getItem('employee_data')
    ? JSON.parse(localStorage.getItem('employee_data'))
    : null
)

export const setEmployeeLogin = (token, employee) => {
  localStorage.setItem('employee_token', token)
  localStorage.setItem('employee_data', JSON.stringify(employee))

  employeeData.value = employee
  isEmployeeLoggedIn.value = true
}

export const setEmployeeLogout = () => {
  localStorage.removeItem('employee_token')
  localStorage.removeItem('employee_data')

  employeeData.value = null
  isEmployeeLoggedIn.value = false
}
