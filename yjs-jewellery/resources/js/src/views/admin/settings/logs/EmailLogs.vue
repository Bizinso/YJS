<script setup>
import { ref, onMounted, computed } from 'vue'
import axiosEmployee from '@axiosEmployee'

const loading = ref(false)
const error = ref('')
const emailLogs = ref([])
const pagination = ref({
  current_page: 1,
  per_page: 20,
  total: 0,
  last_page: 1
})

const filters = ref({
  recipient: '',
  subject: '',
  status: '',
  date_from: '',
  date_to: ''
})

const sidebarstatus = ref({ filter: false })
const selectedEmail = ref(null)
const showDetailModal = ref(false)

const fields = [
  { key: 'id', label: 'ID', sortable: true },
  { key: 'recipient', label: 'Recipient', sortable: true },
  { key: 'subject', label: 'Subject', sortable: true },
  { key: 'status', label: 'Status', sortable: true },
  { key: 'sent_at', label: 'Sent At', sortable: true },
  { key: 'actions', label: 'Actions', sortable: false }
]

const fetchEmailLogs = async (page = 1) => {
  loading.value = true
  error.value = ''
  try {
    const params = {
      page,
      per_page: pagination.value.per_page,
      ...filters.value
    }
    const response = await axiosEmployee.get('/email-logs', { params })
    emailLogs.value = response.data.data || []
    pagination.value = {
      current_page: response.data.current_page || 1,
      per_page: response.data.per_page || 20,
      total: response.data.total || 0,
      last_page: response.data.last_page || 1
    }
  } catch (err) {
    console.error('Failed to fetch email logs:', err)
    error.value = err.response?.data?.message || 'Failed to load email logs'
    emailLogs.value = []
  } finally {
    loading.value = false
  }
}

const viewEmailDetail = (email) => {
  selectedEmail.value = email
  showDetailModal.value = true
}

const applyFilters = () => {
  sidebarstatus.value.filter = false
  fetchEmailLogs(1)
}

const resetFilters = () => {
  filters.value = {
    recipient: '',
    subject: '',
    status: '',
    date_from: '',
    date_to: ''
  }
  sidebarstatus.value.filter = false
  fetchEmailLogs(1)
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

const getStatusBadge = (status) => {
  const statusMap = {
    sent: { class: 'bg-success', text: 'Sent' },
    delivered: { class: 'bg-success', text: 'Delivered' },
    failed: { class: 'bg-danger', text: 'Failed' },
    pending: { class: 'bg-warning text-dark', text: 'Pending' },
    bounced: { class: 'bg-danger', text: 'Bounced' }
  }
  return statusMap[status] || { class: 'bg-secondary', text: status || 'Unknown' }
}

const hasFilters = computed(() => {
  return filters.value.recipient || filters.value.subject ||
         filters.value.status || filters.value.date_from || filters.value.date_to
})

onMounted(() => {
  fetchEmailLogs()
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
              v-model="filters.recipient"
              @input="fetchEmailLogs(1)"
              placeholder="Search by recipient..."
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
        <h6>Filter Email Logs</h6>
        <button class="btn-close" @click="sidebarstatus.filter = false"></button>
      </div>
      <div class="sidebar_form">
        <b-form @submit.prevent="applyFilters">
          <div class="px-4 py-3">
            <b-form-group label="Recipient">
              <b-form-input v-model="filters.recipient" placeholder="Email address" />
            </b-form-group>
            <b-form-group label="Subject">
              <b-form-input v-model="filters.subject" placeholder="Email subject" />
            </b-form-group>
            <b-form-group label="Status">
              <b-form-select v-model="filters.status">
                <option value="">All</option>
                <option value="sent">Sent</option>
                <option value="delivered">Delivered</option>
                <option value="failed">Failed</option>
                <option value="pending">Pending</option>
                <option value="bounced">Bounced</option>
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
      <span v-if="filters.recipient" class="selected_filter_item">
        {{ filters.recipient }}
        <i class="fa-solid fa-xmark" @click="filters.recipient = ''; fetchEmailLogs(1)"></i>
      </span>
      <span v-if="filters.subject" class="selected_filter_item">
        {{ filters.subject }}
        <i class="fa-solid fa-xmark" @click="filters.subject = ''; fetchEmailLogs(1)"></i>
      </span>
      <span v-if="filters.status" class="selected_filter_item">
        {{ filters.status }}
        <i class="fa-solid fa-xmark" @click="filters.status = ''; fetchEmailLogs(1)"></i>
      </span>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="text-center py-5">
      <b-spinner variant="primary"></b-spinner>
      <p class="mt-2 text-muted">Loading email logs...</p>
    </div>

    <!-- Error -->
    <div v-else-if="error" class="alert alert-danger m-3">
      <i class="fa-solid fa-exclamation-circle me-2"></i>
      {{ error }}
      <b-button size="sm" variant="outline-danger" class="ms-3" @click="fetchEmailLogs">
        Retry
      </b-button>
    </div>

    <!-- Empty State -->
    <div v-else-if="emailLogs.length === 0" class="text-center py-5">
      <i class="fa-solid fa-envelope text-muted" style="font-size: 3rem;"></i>
      <h5 class="mt-3">No Email Logs Found</h5>
      <p class="text-muted">Sent email logs will appear here</p>
    </div>

    <!-- Table -->
    <div v-else class="table_listing">
      <b-table
        responsive
        :items="emailLogs"
        :fields="fields"
        class="mb-2"
      >
        <template #cell(recipient)="data">
          <span class="fw-medium">{{ data.value }}</span>
        </template>
        <template #cell(subject)="data">
          <span class="text-truncate d-inline-block" style="max-width: 250px;">
            {{ data.value }}
          </span>
        </template>
        <template #cell(status)="data">
          <span :class="['badge', getStatusBadge(data.value).class]">
            {{ getStatusBadge(data.value).text }}
          </span>
        </template>
        <template #cell(sent_at)="data">
          {{ formatDate(data.value) }}
        </template>
        <template #cell(actions)="data">
          <b-button
            size="sm"
            variant="outline-primary"
            @click="viewEmailDetail(data.item)"
          >
            <i class="fa-solid fa-eye"></i>
          </b-button>
        </template>
      </b-table>

      <!-- Pagination -->
      <div class="d-flex justify-content-between align-items-center px-3">
        <span class="text-muted">
          Showing {{ emailLogs.length }} of {{ pagination.total }} entries
        </span>
        <b-pagination
          v-model="pagination.current_page"
          :total-rows="pagination.total"
          :per-page="pagination.per_page"
          @change="fetchEmailLogs"
          size="sm"
        />
      </div>
    </div>

    <!-- Email Detail Modal -->
    <b-modal v-model="showDetailModal" title="Email Details" size="lg" hide-footer>
      <div v-if="selectedEmail">
        <div class="mb-3">
          <strong>Recipient:</strong> {{ selectedEmail.recipient }}
        </div>
        <div class="mb-3">
          <strong>Subject:</strong> {{ selectedEmail.subject }}
        </div>
        <div class="mb-3">
          <strong>Status:</strong>
          <span :class="['badge', getStatusBadge(selectedEmail.status).class]">
            {{ getStatusBadge(selectedEmail.status).text }}
          </span>
        </div>
        <div class="mb-3">
          <strong>Sent At:</strong> {{ formatDate(selectedEmail.sent_at) }}
        </div>
        <div v-if="selectedEmail.body" class="mb-3">
          <strong>Content:</strong>
          <div class="border rounded p-3 mt-2 bg-light" v-html="selectedEmail.body"></div>
        </div>
        <div v-if="selectedEmail.error" class="mb-3">
          <strong>Error:</strong>
          <div class="text-danger">{{ selectedEmail.error }}</div>
        </div>
      </div>
    </b-modal>
  </div>
</template>
