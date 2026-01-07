<script setup>
import { ref, onMounted, computed } from 'vue'
import axiosEmployee from '@axiosEmployee'

const loading = ref(false)
const error = ref('')
const otpLogs = ref([])
const pagination = ref({
  current_page: 1,
  per_page: 20,
  total: 0,
  last_page: 1
})

const filters = ref({
  identifier: '',
  status: '',
  date_from: '',
  date_to: ''
})

const sidebarstatus = ref({ filter: false })

const fields = [
  { key: 'id', label: 'ID', sortable: true },
  { key: 'identifier', label: 'Email/Phone', sortable: true },
  { key: 'otp', label: 'OTP Code', sortable: false },
  { key: 'status', label: 'Status', sortable: true },
  { key: 'expires_at', label: 'Expires At', sortable: true },
  { key: 'created_at', label: 'Sent At', sortable: true }
]

const fetchOtpLogs = async (page = 1) => {
  loading.value = true
  error.value = ''
  try {
    const params = {
      page,
      per_page: pagination.value.per_page,
      ...filters.value
    }
    const response = await axiosEmployee.get('/otp-logs', { params })
    otpLogs.value = response.data.data || []
    pagination.value = {
      current_page: response.data.current_page || 1,
      per_page: response.data.per_page || 20,
      total: response.data.total || 0,
      last_page: response.data.last_page || 1
    }
  } catch (err) {
    console.error('Failed to fetch OTP logs:', err)
    error.value = err.response?.data?.message || 'Failed to load OTP logs'
    otpLogs.value = []
  } finally {
    loading.value = false
  }
}

const applyFilters = () => {
  sidebarstatus.value.filter = false
  fetchOtpLogs(1)
}

const resetFilters = () => {
  filters.value = {
    identifier: '',
    status: '',
    date_from: '',
    date_to: ''
  }
  sidebarstatus.value.filter = false
  fetchOtpLogs(1)
}

const formatDate = (dateStr) => {
  if (!dateStr) return '-'
  return new Date(dateStr).toLocaleString('en-IN', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const getStatusBadge = (log) => {
  const now = new Date()
  const expiresAt = new Date(log.expires_at)
  if (log.verified_at) return { class: 'bg-success', text: 'Verified' }
  if (expiresAt < now) return { class: 'bg-secondary', text: 'Expired' }
  return { class: 'bg-warning text-dark', text: 'Pending' }
}

const hasFilters = computed(() => {
  return filters.value.identifier || filters.value.status ||
         filters.value.date_from || filters.value.date_to
})

onMounted(() => {
  fetchOtpLogs()
})
</script>

<template>
  <div class="listing_screen global_table_liting">
    <div class="listing_tab_and_actions mb-3">
      <div class="listing_actions">
        <div class="d-flex">
          <div class="listing_search">
            <img src="../../../assets/img/header/search.svg" class="listing_search_icon" alt="search" />
            <b-form-input
              v-model="filters.identifier"
              @input="fetchOtpLogs(1)"
              placeholder="Search by email or phone..."
            />
          </div>
          <b-button
            title="filter"
            class="btn_listing_action"
            @click="sidebarstatus.filter = !sidebarstatus.filter"
          >
            <img src="../../../assets/img/filter.svg" alt="filter" /> Filter
          </b-button>
        </div>
      </div>
    </div>

    <!-- Filter Sidebar -->
    <div class="filter_sidebar sidebar_main" :class="[sidebarstatus.filter ? 'filter_active' : '']">
      <div class="sidebar_toolbox p-3">
        <h6>Filter OTP Logs</h6>
        <button class="btn-close" @click="sidebarstatus.filter = false"></button>
      </div>
      <div class="sidebar_form">
        <b-form @submit.prevent="applyFilters">
          <div class="px-4 py-3">
            <b-form-group label="Email/Phone">
              <b-form-input v-model="filters.identifier" placeholder="Search identifier" />
            </b-form-group>
            <b-form-group label="Status">
              <b-form-select v-model="filters.status">
                <option value="">All</option>
                <option value="pending">Pending</option>
                <option value="verified">Verified</option>
                <option value="expired">Expired</option>
              </b-form-select>
            </b-form-group>
            <b-form-group label="From Date">
              <b-form-input type="date" v-model="filters.date_from" />
            </b-form-group>
            <b-form-group label="To Date">
              <b-form-input type="date" v-model="filters.date_to" />
            </b-form-group>
          </div>
          <div class="sidebarbtn_group">
            <b-button type="submit" class="btn_primary me-2">Apply</b-button>
            <b-button class="btn_secondary_border" @click="resetFilters">Reset</b-button>
          </div>
        </b-form>
      </div>
    </div>

    <!-- Active Filters -->
    <div v-if="hasFilters" class="filter_selected px-4 mb-3">
      <span class="selected_filter_item_icon me-2"><i class="fa-solid fa-sliders"></i></span>
      <span v-if="filters.identifier" class="selected_filter_item">
        {{ filters.identifier }}
        <i class="fa-solid fa-xmark" @click="filters.identifier = ''; fetchOtpLogs(1)"></i>
      </span>
      <span v-if="filters.status" class="selected_filter_item">
        {{ filters.status }}
        <i class="fa-solid fa-xmark" @click="filters.status = ''; fetchOtpLogs(1)"></i>
      </span>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="text-center py-5">
      <b-spinner variant="primary"></b-spinner>
      <p class="mt-2 text-muted">Loading OTP logs...</p>
    </div>

    <!-- Error -->
    <div v-else-if="error" class="alert alert-danger m-3">
      <i class="fa-solid fa-exclamation-circle me-2"></i>
      {{ error }}
      <b-button size="sm" variant="outline-danger" class="ms-3" @click="fetchOtpLogs">
        Retry
      </b-button>
    </div>

    <!-- Empty State -->
    <div v-else-if="otpLogs.length === 0" class="text-center py-5">
      <i class="fa-solid fa-envelope-circle-check text-muted" style="font-size: 3rem;"></i>
      <h5 class="mt-3">No OTP Logs Found</h5>
      <p class="text-muted">OTP verification logs will appear here</p>
    </div>

    <!-- Table -->
    <div v-else class="table_listing">
      <b-table
        responsive
        :items="otpLogs"
        :fields="fields"
        class="mb-2"
      >
        <template #cell(identifier)="data">
          <span class="fw-medium">{{ data.value }}</span>
        </template>
        <template #cell(otp)="data">
          <code>{{ data.value }}</code>
        </template>
        <template #cell(status)="data">
          <span :class="['badge', getStatusBadge(data.item).class]">
            {{ getStatusBadge(data.item).text }}
          </span>
        </template>
        <template #cell(expires_at)="data">
          {{ formatDate(data.value) }}
        </template>
        <template #cell(created_at)="data">
          {{ formatDate(data.value) }}
        </template>
      </b-table>

      <!-- Pagination -->
      <div class="d-flex justify-content-between align-items-center px-3">
        <span class="text-muted">
          Showing {{ otpLogs.length }} of {{ pagination.total }} entries
        </span>
        <b-pagination
          v-model="pagination.current_page"
          :total-rows="pagination.total"
          :per-page="pagination.per_page"
          @change="fetchOtpLogs"
          size="sm"
        />
      </div>
    </div>
  </div>
</template>
