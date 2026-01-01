import { ref } from 'vue'

export const isPartnerLoggedIn = ref(!!localStorage.getItem('partner_token'))

export const partnerData = ref(
  localStorage.getItem('partner_data')
    ? JSON.parse(localStorage.getItem('partner_data'))
    : null
)

export const setPartnerLogin = (token, partner) => {
  localStorage.setItem('partner_token', token)
  localStorage.setItem('partner_data', JSON.stringify(partner))

  partnerData.value = partner
  isPartnerLoggedIn.value = true
}

export const setPartnerLogout = () => {
  localStorage.removeItem('partner_token')
  localStorage.removeItem('partner_data')

  partnerData.value = null
  isPartnerLoggedIn.value = false
}
