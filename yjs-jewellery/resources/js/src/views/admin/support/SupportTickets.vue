<template>
  <div class="support-tickets">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h4 class="mb-1">Support Tickets</h4>
        <p class="text-muted mb-0">Manage customer support requests and inquiries</p>
      </div>
      <div class="d-flex gap-2">
        <b-button variant="outline-primary" @click="exportTickets">
          <i class="bi bi-download me-1"></i> Export
        </b-button>
        <b-button variant="primary" @click="showCreateTicketModal = true">
          <i class="bi bi-plus me-1"></i> Create Ticket
        </b-button>
      </div>
    </div>

    <!-- Stats Cards -->
    <div class="row mb-4">
      <div class="col-md-3">
        <b-card class="stats-card border-0 shadow-sm text-center">
          <h2 class="mb-1">{{ stats.open }}</h2>
          <p class="text-muted mb-0">Open Tickets</p>
        </b-card>
      </div>
      <div class="col-md-3">
        <b-card class="stats-card border-0 shadow-sm text-center">
          <h2 class="mb-1 text-warning">{{ stats.pending }}</h2>
          <p class="text-muted mb-0">Pending Response</p>
        </b-card>
      </div>
      <div class="col-md-3">
        <b-card class="stats-card border-0 shadow-sm text-center">
          <h2 class="mb-1 text-success">{{ stats.resolved }}</h2>
          <p class="text-muted mb-0">Resolved Today</p>
        </b-card>
      </div>
      <div class="col-md-3">
        <b-card class="stats-card border-0 shadow-sm text-center">
          <h2 class="mb-1">{{ stats.avgResponseTime }}</h2>
          <p class="text-muted mb-0">Avg Response Time</p>
        </b-card>
      </div>
    </div>

    <!-- Main Content -->
    <div class="row">
      <!-- Tickets List -->
      <div class="col-md-5">
        <b-card>
          <!-- Filters -->
          <div class="mb-3">
            <div class="row g-2">
              <div class="col-12">
                <b-form-input
                  v-model="filters.search"
                  placeholder="Search tickets..."
                  @input="debounceSearch"
                />
              </div>
              <div class="col-6">
                <b-form-select v-model="filters.status" :options="statusOptions" @change="loadTickets" />
              </div>
              <div class="col-6">
                <b-form-select v-model="filters.priority" :options="priorityOptions" @change="loadTickets" />
              </div>
              <div class="col-6">
                <b-form-select v-model="filters.category" :options="categoryOptions" @change="loadTickets" />
              </div>
              <div class="col-6">
                <b-form-select v-model="filters.assignee" :options="assigneeOptions" @change="loadTickets" />
              </div>
            </div>
          </div>

          <!-- Tickets List -->
          <div class="tickets-list">
            <div v-if="loadingTickets" class="text-center py-4">
              <b-spinner />
            </div>
            <div v-else-if="tickets.length === 0" class="text-center py-4 text-muted">
              No tickets found
            </div>
            <div
              v-else
              v-for="ticket in tickets"
              :key="ticket.id"
              class="ticket-item p-3 border-bottom cursor-pointer"
              :class="{ 'active': selectedTicket?.id === ticket.id }"
              @click="selectTicket(ticket)"
            >
              <div class="d-flex justify-content-between align-items-start mb-2">
                <div>
                  <span class="ticket-number text-muted">#{{ ticket.ticket_number }}</span>
                  <h6 class="mb-0">{{ ticket.subject }}</h6>
                </div>
                <b-badge :variant="getPriorityVariant(ticket.priority)" pill>
                  {{ ticket.priority }}
                </b-badge>
              </div>
              <div class="d-flex justify-content-between align-items-center">
                <div class="text-muted small">
                  <i class="bi bi-person me-1"></i>{{ ticket.customer_name }}
                </div>
                <b-badge :variant="getStatusVariant(ticket.status)">
                  {{ ticket.status }}
                </b-badge>
              </div>
              <div class="d-flex justify-content-between align-items-center mt-2">
                <small class="text-muted">{{ formatRelativeTime(ticket.updated_at) }}</small>
                <small v-if="ticket.assignee" class="text-muted">
                  <i class="bi bi-person-check me-1"></i>{{ ticket.assignee.name }}
                </small>
              </div>
            </div>
          </div>

          <!-- Pagination -->
          <b-pagination
            v-model="pagination.currentPage"
            :total-rows="pagination.total"
            :per-page="pagination.perPage"
            class="mt-3"
            @change="loadTickets"
          />
        </b-card>
      </div>

      <!-- Ticket Detail -->
      <div class="col-md-7">
        <b-card v-if="selectedTicket">
          <!-- Ticket Header -->
          <div class="d-flex justify-content-between align-items-start mb-4">
            <div>
              <div class="d-flex align-items-center gap-2 mb-2">
                <span class="text-muted">#{{ selectedTicket.ticket_number }}</span>
                <b-badge :variant="getStatusVariant(selectedTicket.status)">
                  {{ selectedTicket.status }}
                </b-badge>
                <b-badge :variant="getPriorityVariant(selectedTicket.priority)" pill>
                  {{ selectedTicket.priority }}
                </b-badge>
              </div>
              <h5 class="mb-0">{{ selectedTicket.subject }}</h5>
            </div>
            <div class="btn-group">
              <b-dropdown variant="outline-secondary" right>
                <template #button-content>
                  <i class="bi bi-gear"></i>
                </template>
                <b-dropdown-item @click="showAssignModal = true">
                  <i class="bi bi-person-plus me-2"></i> Assign
                </b-dropdown-item>
                <b-dropdown-item @click="showChangeStatusModal = true">
                  <i class="bi bi-arrow-repeat me-2"></i> Change Status
                </b-dropdown-item>
                <b-dropdown-item @click="showChangePriorityModal = true">
                  <i class="bi bi-exclamation-triangle me-2"></i> Change Priority
                </b-dropdown-item>
                <b-dropdown-divider />
                <b-dropdown-item @click="mergeTicket">
                  <i class="bi bi-link me-2"></i> Merge
                </b-dropdown-item>
                <b-dropdown-item @click="duplicateTicket">
                  <i class="bi bi-files me-2"></i> Duplicate
                </b-dropdown-item>
              </b-dropdown>
            </div>
          </div>

          <!-- Customer Info -->
          <div class="customer-info bg-light p-3 rounded mb-4">
            <div class="row">
              <div class="col-md-4">
                <p class="text-muted mb-1">Customer</p>
                <p class="mb-0 fw-bold">{{ selectedTicket.customer_name }}</p>
              </div>
              <div class="col-md-4">
                <p class="text-muted mb-1">Email</p>
                <p class="mb-0">{{ selectedTicket.customer_email }}</p>
              </div>
              <div class="col-md-4">
                <p class="text-muted mb-1">Phone</p>
                <p class="mb-0">{{ selectedTicket.customer_phone || '-' }}</p>
              </div>
            </div>
            <div class="row mt-2" v-if="selectedTicket.order_id">
              <div class="col-md-4">
                <p class="text-muted mb-1">Related Order</p>
                <router-link :to="`/admin/orders/${selectedTicket.order_id}`" class="text-primary">
                  {{ selectedTicket.order_number }}
                </router-link>
              </div>
              <div class="col-md-4">
                <p class="text-muted mb-1">Category</p>
                <p class="mb-0">{{ selectedTicket.category }}</p>
              </div>
              <div class="col-md-4">
                <p class="text-muted mb-1">Created</p>
                <p class="mb-0">{{ formatDateTime(selectedTicket.created_at) }}</p>
              </div>
            </div>
          </div>

          <!-- Conversation -->
          <div class="conversation mb-4">
            <h6 class="mb-3">Conversation</h6>
            <div class="messages" ref="messagesContainer">
              <div
                v-for="message in selectedTicket.messages"
                :key="message.id"
                class="message mb-3"
                :class="{ 'message-staff': message.is_staff }"
              >
                <div class="message-header d-flex justify-content-between align-items-center mb-2">
                  <div class="d-flex align-items-center gap-2">
                    <div class="avatar" :class="message.is_staff ? 'bg-primary' : 'bg-secondary'">
                      {{ message.sender_name.charAt(0).toUpperCase() }}
                    </div>
                    <div>
                      <span class="fw-bold">{{ message.sender_name }}</span>
                      <span v-if="message.is_staff" class="badge bg-primary ms-2">Staff</span>
                    </div>
                  </div>
                  <small class="text-muted">{{ formatDateTime(message.created_at) }}</small>
                </div>
                <div class="message-body p-3 rounded" v-html="message.content"></div>
                <div v-if="message.attachments?.length" class="message-attachments mt-2">
                  <a
                    v-for="attachment in message.attachments"
                    :key="attachment.id"
                    :href="attachment.url"
                    target="_blank"
                    class="attachment-link me-2"
                  >
                    <i class="bi bi-paperclip"></i> {{ attachment.name }}
                  </a>
                </div>
              </div>
            </div>
          </div>

          <!-- Reply Form -->
          <div class="reply-form">
            <h6 class="mb-3">Reply</h6>
            <div class="mb-3">
              <b-form-select v-model="replyForm.template" :options="templateOptions" @change="applyTemplate">
                <template #first>
                  <option value="">Select a template (optional)</option>
                </template>
              </b-form-select>
            </div>
            <b-form-textarea
              v-model="replyForm.content"
              rows="4"
              placeholder="Type your reply..."
            />
            <div class="d-flex justify-content-between align-items-center mt-3">
              <div>
                <b-form-file
                  v-model="replyForm.attachments"
                  multiple
                  placeholder="Attach files..."
                  class="d-inline-block"
                  style="max-width: 200px"
                />
              </div>
              <div class="d-flex gap-2">
                <b-form-checkbox v-model="replyForm.closeTicket">
                  Close ticket after reply
                </b-form-checkbox>
                <b-button variant="primary" @click="sendReply" :disabled="!replyForm.content">
                  <i class="bi bi-send me-1"></i> Send Reply
                </b-button>
              </div>
            </div>
          </div>

          <!-- Internal Notes -->
          <div class="internal-notes mt-4 pt-4 border-top">
            <div class="d-flex justify-content-between align-items-center mb-3">
              <h6 class="mb-0">Internal Notes</h6>
              <b-button size="sm" variant="outline-secondary" @click="showAddNoteModal = true">
                <i class="bi bi-plus"></i> Add Note
              </b-button>
            </div>
            <div v-if="selectedTicket.notes?.length" class="notes-list">
              <div
                v-for="note in selectedTicket.notes"
                :key="note.id"
                class="note-item p-2 bg-warning-subtle rounded mb-2"
              >
                <div class="d-flex justify-content-between align-items-start">
                  <div>
                    <strong>{{ note.author_name }}</strong>
                    <small class="text-muted ms-2">{{ formatDateTime(note.created_at) }}</small>
                  </div>
                  <b-button size="sm" variant="link" class="text-danger p-0" @click="deleteNote(note)">
                    <i class="bi bi-trash"></i>
                  </b-button>
                </div>
                <p class="mb-0 mt-1">{{ note.content }}</p>
              </div>
            </div>
            <p v-else class="text-muted small mb-0">No internal notes</p>
          </div>
        </b-card>
        <b-card v-else class="text-center py-5">
          <i class="bi bi-inbox text-muted" style="font-size: 3rem"></i>
          <p class="text-muted mt-3 mb-0">Select a ticket to view details</p>
        </b-card>
      </div>
    </div>

    <!-- Create Ticket Modal -->
    <b-modal v-model="showCreateTicketModal" title="Create Support Ticket" size="lg" @ok="createTicket">
      <b-form>
        <div class="row">
          <div class="col-md-6">
            <b-form-group label="Customer" class="mb-3">
              <b-form-input v-model="ticketForm.customer_search" placeholder="Search customer..." />
              <div v-if="customerSearchResults.length" class="customer-results mt-2">
                <div
                  v-for="customer in customerSearchResults"
                  :key="customer.id"
                  class="p-2 border-bottom cursor-pointer"
                  @click="selectCustomer(customer)"
                >
                  {{ customer.name }} - {{ customer.email }}
                </div>
              </div>
            </b-form-group>
          </div>
          <div class="col-md-6">
            <b-form-group label="Related Order (optional)" class="mb-3">
              <b-form-input v-model="ticketForm.order_number" placeholder="Order number" />
            </b-form-group>
          </div>
        </div>
        <b-form-group label="Subject" class="mb-3">
          <b-form-input v-model="ticketForm.subject" required />
        </b-form-group>
        <div class="row">
          <div class="col-md-4">
            <b-form-group label="Category" class="mb-3">
              <b-form-select v-model="ticketForm.category" :options="categoryList" />
            </b-form-group>
          </div>
          <div class="col-md-4">
            <b-form-group label="Priority" class="mb-3">
              <b-form-select v-model="ticketForm.priority" :options="priorityList" />
            </b-form-group>
          </div>
          <div class="col-md-4">
            <b-form-group label="Assign To" class="mb-3">
              <b-form-select v-model="ticketForm.assignee_id" :options="staffList" />
            </b-form-group>
          </div>
        </div>
        <b-form-group label="Description" class="mb-3">
          <b-form-textarea v-model="ticketForm.description" rows="4" required />
        </b-form-group>
      </b-form>
    </b-modal>

    <!-- Assign Modal -->
    <b-modal v-model="showAssignModal" title="Assign Ticket" @ok="assignTicket">
      <b-form-group label="Assign To">
        <b-form-select v-model="assignForm.assignee_id" :options="staffList" />
      </b-form-group>
      <b-form-group label="Note (optional)" class="mt-3">
        <b-form-textarea v-model="assignForm.note" rows="2" />
      </b-form-group>
    </b-modal>

    <!-- Change Status Modal -->
    <b-modal v-model="showChangeStatusModal" title="Change Status" @ok="changeStatus">
      <b-form-group label="New Status">
        <b-form-select v-model="statusForm.status" :options="statusList" />
      </b-form-group>
      <b-form-group label="Reason (optional)" class="mt-3">
        <b-form-textarea v-model="statusForm.reason" rows="2" />
      </b-form-group>
    </b-modal>

    <!-- Change Priority Modal -->
    <b-modal v-model="showChangePriorityModal" title="Change Priority" @ok="changePriority">
      <b-form-group label="New Priority">
        <b-form-select v-model="priorityForm.priority" :options="priorityList" />
      </b-form-group>
    </b-modal>

    <!-- Add Note Modal -->
    <b-modal v-model="showAddNoteModal" title="Add Internal Note" @ok="addNote">
      <b-form-textarea v-model="noteForm.content" rows="4" placeholder="Enter your note..." />
    </b-modal>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, watch } from 'vue'
import axios from 'axios'
import { debounce } from 'lodash'

// State
const loadingTickets = ref(false)
const tickets = ref([])
const selectedTicket = ref(null)
const customerSearchResults = ref([])

// Stats
const stats = reactive({
  open: 0,
  pending: 0,
  resolved: 0,
  avgResponseTime: '0h'
})

// Filters
const filters = reactive({
  search: '',
  status: '',
  priority: '',
  category: '',
  assignee: ''
})

// Pagination
const pagination = reactive({
  currentPage: 1,
  total: 0,
  perPage: 20
})

// Forms
const ticketForm = reactive({
  customer_id: null,
  customer_search: '',
  order_number: '',
  subject: '',
  category: 'general',
  priority: 'medium',
  assignee_id: null,
  description: ''
})

const replyForm = reactive({
  content: '',
  attachments: [],
  template: '',
  closeTicket: false
})

const assignForm = reactive({
  assignee_id: null,
  note: ''
})

const statusForm = reactive({
  status: '',
  reason: ''
})

const priorityForm = reactive({
  priority: ''
})

const noteForm = reactive({
  content: ''
})

// Modals
const showCreateTicketModal = ref(false)
const showAssignModal = ref(false)
const showChangeStatusModal = ref(false)
const showChangePriorityModal = ref(false)
const showAddNoteModal = ref(false)

// Options
const statusOptions = [
  { value: '', text: 'All Statuses' },
  { value: 'open', text: 'Open' },
  { value: 'pending', text: 'Pending' },
  { value: 'in_progress', text: 'In Progress' },
  { value: 'resolved', text: 'Resolved' },
  { value: 'closed', text: 'Closed' }
]

const statusList = [
  { value: 'open', text: 'Open' },
  { value: 'pending', text: 'Pending' },
  { value: 'in_progress', text: 'In Progress' },
  { value: 'resolved', text: 'Resolved' },
  { value: 'closed', text: 'Closed' }
]

const priorityOptions = [
  { value: '', text: 'All Priorities' },
  { value: 'low', text: 'Low' },
  { value: 'medium', text: 'Medium' },
  { value: 'high', text: 'High' },
  { value: 'urgent', text: 'Urgent' }
]

const priorityList = [
  { value: 'low', text: 'Low' },
  { value: 'medium', text: 'Medium' },
  { value: 'high', text: 'High' },
  { value: 'urgent', text: 'Urgent' }
]

const categoryOptions = ref([{ value: '', text: 'All Categories' }])
const categoryList = ref([])
const assigneeOptions = ref([{ value: '', text: 'All Assignees' }])
const staffList = ref([])
const templateOptions = ref([])

// Methods
const getStatusVariant = (status) => {
  const variants = {
    open: 'primary',
    pending: 'warning',
    in_progress: 'info',
    resolved: 'success',
    closed: 'secondary'
  }
  return variants[status] || 'secondary'
}

const getPriorityVariant = (priority) => {
  const variants = {
    low: 'secondary',
    medium: 'info',
    high: 'warning',
    urgent: 'danger'
  }
  return variants[priority] || 'secondary'
}

const formatDateTime = (date) => {
  if (!date) return '-'
  return new Date(date).toLocaleString('en-IN')
}

const formatRelativeTime = (date) => {
  if (!date) return '-'
  const now = new Date()
  const past = new Date(date)
  const diffMs = now - past
  const diffMins = Math.floor(diffMs / 60000)
  const diffHours = Math.floor(diffMs / 3600000)
  const diffDays = Math.floor(diffMs / 86400000)

  if (diffMins < 1) return 'Just now'
  if (diffMins < 60) return `${diffMins}m ago`
  if (diffHours < 24) return `${diffHours}h ago`
  return `${diffDays}d ago`
}

const loadStats = async () => {
  try {
    const response = await axios.get('/api/admin/support/stats')
    if (response.data.success) {
      Object.assign(stats, response.data.data)
    }
  } catch (error) {
    console.error('Failed to load stats:', error)
  }
}

const loadTickets = async () => {
  loadingTickets.value = true
  try {
    const params = {
      ...filters,
      page: pagination.currentPage,
      per_page: pagination.perPage
    }
    const response = await axios.get('/api/admin/support/tickets', { params })
    if (response.data.success) {
      tickets.value = response.data.data.data
      pagination.total = response.data.data.total
    }
  } catch (error) {
    console.error('Failed to load tickets:', error)
  } finally {
    loadingTickets.value = false
  }
}

const loadOptions = async () => {
  try {
    const response = await axios.get('/api/admin/support/options')
    if (response.data.success) {
      categoryOptions.value = [
        { value: '', text: 'All Categories' },
        ...response.data.data.categories.map(c => ({ value: c.id, text: c.name }))
      ]
      categoryList.value = response.data.data.categories.map(c => ({ value: c.id, text: c.name }))
      assigneeOptions.value = [
        { value: '', text: 'All Assignees' },
        ...response.data.data.staff.map(s => ({ value: s.id, text: s.name }))
      ]
      staffList.value = response.data.data.staff.map(s => ({ value: s.id, text: s.name }))
      templateOptions.value = response.data.data.templates.map(t => ({ value: t.id, text: t.name }))
    }
  } catch (error) {
    console.error('Failed to load options:', error)
  }
}

const selectTicket = async (ticket) => {
  try {
    const response = await axios.get(`/api/admin/support/tickets/${ticket.id}`)
    if (response.data.success) {
      selectedTicket.value = response.data.data
      statusForm.status = selectedTicket.value.status
      priorityForm.priority = selectedTicket.value.priority
    }
  } catch (error) {
    console.error('Failed to load ticket details:', error)
  }
}

const debounceSearch = debounce(() => {
  pagination.currentPage = 1
  loadTickets()
}, 300)

const searchCustomers = debounce(async () => {
  if (ticketForm.customer_search.length < 2) {
    customerSearchResults.value = []
    return
  }
  try {
    const response = await axios.get('/api/admin/customers/search', {
      params: { q: ticketForm.customer_search }
    })
    if (response.data.success) {
      customerSearchResults.value = response.data.data
    }
  } catch (error) {
    console.error('Failed to search customers:', error)
  }
}, 300)

const selectCustomer = (customer) => {
  ticketForm.customer_id = customer.id
  ticketForm.customer_search = customer.name
  customerSearchResults.value = []
}

const createTicket = async () => {
  try {
    const response = await axios.post('/api/admin/support/tickets', ticketForm)
    if (response.data.success) {
      showCreateTicketModal.value = false
      resetTicketForm()
      loadTickets()
      loadStats()
    }
  } catch (error) {
    console.error('Failed to create ticket:', error)
  }
}

const resetTicketForm = () => {
  ticketForm.customer_id = null
  ticketForm.customer_search = ''
  ticketForm.order_number = ''
  ticketForm.subject = ''
  ticketForm.category = 'general'
  ticketForm.priority = 'medium'
  ticketForm.assignee_id = null
  ticketForm.description = ''
}

const applyTemplate = async () => {
  if (!replyForm.template) return
  try {
    const response = await axios.get(`/api/admin/support/templates/${replyForm.template}`)
    if (response.data.success) {
      replyForm.content = response.data.data.content
    }
  } catch (error) {
    console.error('Failed to load template:', error)
  }
}

const sendReply = async () => {
  try {
    const formData = new FormData()
    formData.append('content', replyForm.content)
    formData.append('close_ticket', replyForm.closeTicket)
    replyForm.attachments.forEach(file => {
      formData.append('attachments[]', file)
    })

    const response = await axios.post(
      `/api/admin/support/tickets/${selectedTicket.value.id}/reply`,
      formData,
      { headers: { 'Content-Type': 'multipart/form-data' } }
    )

    if (response.data.success) {
      replyForm.content = ''
      replyForm.attachments = []
      replyForm.template = ''
      replyForm.closeTicket = false
      selectTicket(selectedTicket.value)
      if (replyForm.closeTicket) {
        loadTickets()
        loadStats()
      }
    }
  } catch (error) {
    console.error('Failed to send reply:', error)
  }
}

const assignTicket = async () => {
  try {
    const response = await axios.post(
      `/api/admin/support/tickets/${selectedTicket.value.id}/assign`,
      assignForm
    )
    if (response.data.success) {
      showAssignModal.value = false
      selectTicket(selectedTicket.value)
      loadTickets()
    }
  } catch (error) {
    console.error('Failed to assign ticket:', error)
  }
}

const changeStatus = async () => {
  try {
    const response = await axios.post(
      `/api/admin/support/tickets/${selectedTicket.value.id}/status`,
      statusForm
    )
    if (response.data.success) {
      showChangeStatusModal.value = false
      selectTicket(selectedTicket.value)
      loadTickets()
      loadStats()
    }
  } catch (error) {
    console.error('Failed to change status:', error)
  }
}

const changePriority = async () => {
  try {
    const response = await axios.post(
      `/api/admin/support/tickets/${selectedTicket.value.id}/priority`,
      priorityForm
    )
    if (response.data.success) {
      showChangePriorityModal.value = false
      selectTicket(selectedTicket.value)
      loadTickets()
    }
  } catch (error) {
    console.error('Failed to change priority:', error)
  }
}

const addNote = async () => {
  try {
    const response = await axios.post(
      `/api/admin/support/tickets/${selectedTicket.value.id}/notes`,
      noteForm
    )
    if (response.data.success) {
      showAddNoteModal.value = false
      noteForm.content = ''
      selectTicket(selectedTicket.value)
    }
  } catch (error) {
    console.error('Failed to add note:', error)
  }
}

const deleteNote = async (note) => {
  if (!confirm('Delete this note?')) return
  try {
    const response = await axios.delete(
      `/api/admin/support/tickets/${selectedTicket.value.id}/notes/${note.id}`
    )
    if (response.data.success) {
      selectTicket(selectedTicket.value)
    }
  } catch (error) {
    console.error('Failed to delete note:', error)
  }
}

const mergeTicket = () => {
  // Implement merge functionality
}

const duplicateTicket = () => {
  // Implement duplicate functionality
}

const exportTickets = async () => {
  try {
    const response = await axios.get('/api/admin/support/tickets/export', {
      params: filters,
      responseType: 'blob'
    })
    const url = window.URL.createObjectURL(new Blob([response.data]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', 'support-tickets.xlsx')
    document.body.appendChild(link)
    link.click()
    link.remove()
  } catch (error) {
    console.error('Failed to export tickets:', error)
  }
}

// Watch customer search
watch(() => ticketForm.customer_search, searchCustomers)

// Lifecycle
onMounted(() => {
  loadStats()
  loadTickets()
  loadOptions()
})
</script>

<style scoped>
.tickets-list {
  max-height: 500px;
  overflow-y: auto;
}
.ticket-item {
  transition: background-color 0.2s;
}
.ticket-item:hover {
  background-color: #f8f9fa;
}
.ticket-item.active {
  background-color: #e3f2fd;
  border-left: 3px solid #2196f3;
}
.ticket-number {
  font-size: 0.8rem;
}
.cursor-pointer {
  cursor: pointer;
}
.messages {
  max-height: 400px;
  overflow-y: auto;
}
.message-staff .message-body {
  background-color: #e3f2fd;
}
.message-body {
  background-color: #f8f9fa;
}
.avatar {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-weight: bold;
}
.customer-results {
  max-height: 150px;
  overflow-y: auto;
  border: 1px solid #dee2e6;
  border-radius: 4px;
}
.attachment-link {
  display: inline-block;
  padding: 4px 8px;
  background: #f8f9fa;
  border-radius: 4px;
  text-decoration: none;
  font-size: 0.85rem;
}
.stats-card {
  transition: transform 0.2s;
}
.stats-card:hover {
  transform: translateY(-2px);
}
</style>
