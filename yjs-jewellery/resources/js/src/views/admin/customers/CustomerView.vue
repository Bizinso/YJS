<template>
  <div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h3>Customer Details</h3>
      <b-button variant="secondary" @click="goBack">
        ← Back
      </b-button>
    </div>

    <b-card v-if="loading" class="text-center">
      <b-spinner variant="primary"></b-spinner>
      <p class="mt-2">Loading customer details...</p>
    </b-card>

    <b-alert variant="danger" v-if="error" show>
      {{ error }}
    </b-alert>

    <div v-if="customer">
      <!-- Personal Information Card -->
      <b-card class="mb-4">
        <div class="d-flex justify-content-between align-items-start mb-3">
          <h5 class="mb-0">Personal Information</h5>
          <b-badge 
            :variant="getStatusVariant(customer.user?.status)" 
            pill
            v-if="customer.user"
          >
            {{ formatStatus(customer.user?.status) }}
          </b-badge>
        </div>
        
        <b-row>
          <b-col md="3" class="text-center mb-3">
            <div class="profile-image-container">
              <img 
                v-if="customer.user?.profile_image" 
                :src="customer.user.profile_image" 
                alt="Profile" 
                class="profile-image"
              />
              <div v-else class="profile-placeholder">
                {{ getInitials(customer.user?.first_name, customer.user?.last_name) }}
              </div>
            </div>
          </b-col>
          
          <b-col md="9">
            <b-row>
              <b-col md="6">
                <strong>Name:</strong> 
                {{ customer.user?.first_name || 'N/A' }} {{ customer.user?.last_name || '' }}
              </b-col>
              <b-col md="6">
                <strong>Email:</strong> {{ customer.user?.email || 'N/A' }}
              </b-col>
              <b-col md="6">
                <strong>Phone:</strong> {{ formatPhoneNumber(customer.user?.phone) }}
              </b-col>
              <b-col md="6">
                <strong>Gender:</strong> {{ formatGender(customer.gender) }}
              </b-col>
              <b-col md="6">
                <strong>Date of Birth:</strong> {{ formatDate(customer.dob) }}
              </b-col>
              <b-col md="6">
                <strong>Member Since:</strong> {{ formatDate(customer.created_at) }}
              </b-col>
            </b-row>
          </b-col>
        </b-row>
      </b-card>

      <!-- Addresses Card -->
      <b-card 
        class="mb-4" 
        v-if="customer.addresses && customer.addresses.length > 0"
      >
        <h5 class="mb-3">Addresses</h5>
        <b-row>
          <b-col 
            md="6" 
            lg="4" 
            v-for="address in customer.addresses" 
            :key="address.id"
            class="mb-3"
          >
            <b-card 
              :class="['address-card', { 'default-address': address.is_default }]"
              :border-variant="address.is_default ? 'primary' : 'light'"
            >
              <div class="d-flex justify-content-between align-items-start mb-2">
                <strong>{{ address.full_name || 'N/A' }}</strong>
                <b-badge v-if="address.is_default" variant="primary">Default</b-badge>
              </div>
              
              <p class="mb-1" v-if="address.phone">
                <small class="text-muted">
                  <i class="fas fa-phone me-1"></i> {{ address.phone }}
                  <span v-if="address.alternate_phone"> / {{ address.alternate_phone }}</span>
                </small>
              </p>
              
              <p class="mb-1">{{ address.address_line1 }}</p>
              <p class="mb-1" v-if="address.address_line2">{{ address.address_line2 }}</p>
              <p class="mb-1" v-if="address.landmark">
                <small class="text-muted">Landmark: {{ address.landmark }}</small>
              </p>
              <p class="mb-0">
                {{ address.city }}, {{ address.state }} - {{ address.postal_code }}
              </p>
            </b-card>
          </b-col>
        </b-row>
      </b-card>

      <!-- No Address Message -->
      <b-alert variant="info" show v-else-if="customer">
        <i class="fas fa-info-circle me-2"></i>
        No addresses added by this customer.
      </b-alert>

      <!-- Recent Activity/Orders Card (You can expand this later) -->
      <!-- <b-card class="mb-4" v-if="customer">
        <h5 class="mb-3">Recent Activity</h5>
        <b-alert variant="light" show>
          <i class="fas fa-info-circle me-2"></i>
          Activity tracking feature coming soon.
        </b-alert>
      </b-card> -->

      <!-- Action Buttons -->
      <!-- <div class="d-flex justify-content-between" v-if="customer">
        <div>
          <b-button 
            variant="outline-secondary" 
            @click="goBack"
            class="me-2"
          >
            Back to List
          </b-button>
          
          <b-button 
            variant="outline-primary" 
            @click="editCustomer"
            v-if="$can('do', 'edit_admin.customers') && customer.user"
            class="me-2"
          >
            <i class="fas fa-edit me-1"></i> Edit Customer
          </b-button>
        </div>
        
        <div v-if="customer.user">
          <b-button 
            variant="outline-danger" 
            @click="changeStatus('D')"
            v-if="customer.user.status !== 'D' && $can('do', 'delete_admin.customers')"
            class="me-2"
          >
            <i class="fas fa-user-slash me-1"></i> Delete Account
          </b-button>
          
          <b-button 
            :variant="customer.user.status === 'A' ? 'warning' : 'success'"
            @click="toggleStatus"
          >
            <i class="fas fa-exchange-alt me-1"></i>
            {{ customer.user.status === 'A' ? 'Deactivate' : 'Activate' }}
          </b-button>
        </div>
      </div> -->
    </div>

    <!-- Status Change Confirmation Modal -->
 
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import axiosEmployee from '@axiosEmployee'
import { useRoute, useRouter } from 'vue-router'
import moment from 'moment'

const route = useRoute()
const router = useRouter()

// Refs
const customer = ref(null)
const loading = ref(false)
const error = ref('')
const showStatusModal = ref(false)
const statusAction = ref('')
const newStatus = ref('')

// Fetch customer details
const fetchCustomer = async () => {
  loading.value = true
  try {
    const id = atob(route.params.id)
    const res = await axiosEmployee.get(`/customer/view/${id}`)
    
    if (res.data.success) {
      customer.value = res.data.data
    } else {
      error.value = res.data.message || 'Failed to load customer details'
    }
  } catch (e) {
    error.value = 'Failed to load customer details'
    console.error('Error fetching customer:', e)
  } finally {
    loading.value = false
  }
}

// Formatting helpers
const formatPhoneNumber = (phone) => {
  if (!phone) return 'N/A'
  return phone.replace(/(\d{3})(\d{3})(\d{4})/, '$1 $2 $3')
}

const formatDate = (date) => {
  if (!date) return 'N/A'
  return moment(date).format('DD MMM YYYY')
}

const formatGender = (gender) => {
  const genderMap = {
    male: 'Male',
    female: 'Female',
    other: 'Other'
  }
  return genderMap[gender] || gender || 'N/A'
}

const formatStatus = (status) => {
  const statusMap = {
    A: 'Active',
    I: 'Inactive',
    D: 'Deleted'
  }
  return statusMap[status] || status || 'Unknown'
}

const getStatusVariant = (status) => {
  const variants = {
    A: 'success',
    I: 'warning',
    D: 'danger'
  }
  return variants[status] || 'light'
}

const getInitials = (firstName, lastName) => {
  if (!firstName && !lastName) return '?'
  return `${firstName?.[0] || ''}${lastName?.[0] || ''}`.toUpperCase()
}

// Navigation
const goBack = () => {
  router.push('/admin/customers')
}

const editCustomer = () => {
  if (!customer.value?.user) return
  router.push(`/admin/customers/edit/${route.params.id}`)
}

// Status management
const toggleStatus = () => {
  if (!customer.value?.user) return
  statusAction.value = 'toggle'
  newStatus.value = customer.value.user.status === 'A' ? 'I' : 'A'
  showStatusModal.value = true
}


// Computed properties for modal



// Lifecycle
onMounted(() => {
  fetchCustomer()
})
</script>

<style scoped>
.profile-image-container {
  width: 120px;
  height: 120px;
  margin: 0 auto;
}

.profile-image {
  width: 100%;
  height: 100%;
  border-radius: 50%;
  object-fit: cover;
  border: 3px solid #e9ecef;
}

.profile-placeholder {
  width: 100%;
  height: 100%;
  border-radius: 50%;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 32px;
  font-weight: bold;
  border: 3px solid #e9ecef;
}

.address-card {
  height: 100%;
  transition: all 0.3s ease;
}

.address-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

.default-address {
  border: 2px solid #4e54c8 !important;
}

.container {
  max-width: 1200px;
  margin: 0 auto;
}
</style>