<template>
    <div class="listing_screen global_table_liting">
        <div class="masterTabs">
            <div class="masterTabContent">
                <!-- Dashboard Stats -->
                <div class="row mb-4">
                    <div class="col-md-2">
                        <div class="stat-card">
                            <h3>{{ stats.total }}</h3>
                            <p>Total</p>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="stat-card warning">
                            <h3>{{ stats.pending }}</h3>
                            <p>Pending</p>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="stat-card info">
                            <h3>{{ stats.under_review }}</h3>
                            <p>Under Review</p>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="stat-card primary">
                            <h3>{{ stats.quoted }}</h3>
                            <p>Quoted</p>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="stat-card info">
                            <h3>{{ stats.in_progress }}</h3>
                            <p>In Progress</p>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="stat-card success">
                            <h3>{{ stats.completed }}</h3>
                            <p>Completed</p>
                        </div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="listing_tab_and_actions mb-3">
                    <div class="listing_actions">
                        <div class="d-flex gap-2">
                            <v-select v-model="statusFilter" :options="statusOptions"
                                placeholder="Status" :clearable="true" class="status-select" />
                            <v-select v-model="priorityFilter" :options="priorityOptions"
                                placeholder="Priority" :clearable="true" class="status-select" />
                            <div class="listing_search">
                                <b-form-input v-model="search" @input="fetchInquiries" placeholder="Search..." />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Inquiries Table -->
                <b-table responsive="sm" :items="inquiries" :fields="inquiryFields" v-if="inquiries.length > 0">
                    <template #cell(inquiry_number)="row">
                        <a href="#" @click.prevent="viewInquiry(row.item)">{{ row.item.inquiry_number }}</a>
                    </template>
                    <template #cell(partner)="row">
                        {{ row.item.partner?.name || '-' }}
                    </template>
                    <template #cell(status)="row">
                        <b-badge :variant="getStatusVariant(row.item.status)">{{ formatStatus(row.item.status) }}</b-badge>
                    </template>
                    <template #cell(priority)="row">
                        <b-badge :variant="getPriorityVariant(row.item.priority)">{{ row.item.priority }}</b-badge>
                    </template>
                    <template #cell(quoted_amount)="row">
                        <span v-if="row.item.quoted_amount">₹{{ formatNumber(row.item.quoted_amount) }}</span>
                        <span v-else class="text-muted">-</span>
                    </template>
                    <template #cell(created_at)="row">
                        {{ formatDate(row.item.created_at) }}
                    </template>
                    <template #cell(actions)="row">
                        <b-dropdown right text="" variant="link" no-caret toggle-class="p-0">
                            <b-dropdown-item @click="viewInquiry(row.item)">View Details</b-dropdown-item>
                            <b-dropdown-item v-if="row.item.status === 'pending'" @click="startReview(row.item.id)">Start Review</b-dropdown-item>
                            <b-dropdown-item v-if="row.item.status === 'under_review'" @click="openQuoteModal(row.item)">Send Quote</b-dropdown-item>
                            <b-dropdown-item @click="openMessageModal(row.item)">Send Message</b-dropdown-item>
                            <b-dropdown-item @click="updateStatus(row.item)">Update Status</b-dropdown-item>
                            <b-dropdown-item v-if="canReject(row.item)" @click="rejectInquiry(row.item.id)">Reject</b-dropdown-item>
                        </b-dropdown>
                    </template>
                </b-table>

                <div v-else class="text-center p-5">
                    <p>No partner inquiries found.</p>
                </div>
            </div>
        </div>

        <!-- View Inquiry Modal -->
        <b-modal v-model="showViewModal" :title="'Inquiry: ' + (selectedInquiry?.inquiry_number || '')" size="xl" hide-footer>
            <div v-if="selectedInquiry">
                <div class="row mb-3">
                    <div class="col-md-4">
                        <strong>Partner:</strong> {{ selectedInquiry.partner?.name }}
                    </div>
                    <div class="col-md-4">
                        <strong>Status:</strong> <b-badge :variant="getStatusVariant(selectedInquiry.status)">{{ formatStatus(selectedInquiry.status) }}</b-badge>
                    </div>
                    <div class="col-md-4">
                        <strong>Priority:</strong> <b-badge :variant="getPriorityVariant(selectedInquiry.priority)">{{ selectedInquiry.priority }}</b-badge>
                    </div>
                </div>
                <div class="mb-3">
                    <strong>Subject:</strong> {{ selectedInquiry.subject }}
                </div>
                <div class="mb-3" v-if="selectedInquiry.description">
                    <strong>Description:</strong> {{ selectedInquiry.description }}
                </div>
                <hr>
                <h6>Items</h6>
                <b-table responsive="sm" :items="selectedInquiry.items" :fields="itemFields" v-if="selectedInquiry.items?.length > 0" small>
                    <template #cell(quoted_price)="row">
                        <b-form-input v-if="editingItems" v-model="row.item.quoted_price" type="number" step="0.01" size="sm" />
                        <span v-else>{{ row.item.quoted_price ? '₹' + formatNumber(row.item.quoted_price) : '-' }}</span>
                    </template>
                    <template #cell(fulfillment_status)="row">
                        <b-badge :variant="getFulfillmentVariant(row.item.fulfillment_status)">{{ row.item.fulfillment_status || 'pending' }}</b-badge>
                    </template>
                </b-table>
                <hr>
                <div class="row" v-if="selectedInquiry.quoted_amount">
                    <div class="col-md-4">
                        <strong>Quoted Amount:</strong> ₹{{ formatNumber(selectedInquiry.quoted_amount) }}
                    </div>
                    <div class="col-md-4">
                        <strong>Valid Until:</strong> {{ formatDate(selectedInquiry.quote_valid_until) }}
                    </div>
                </div>
                <hr>
                <h6>Messages</h6>
                <div class="messages-container" style="max-height: 200px; overflow-y: auto;">
                    <div v-for="msg in selectedInquiry.messages" :key="msg.id" class="message-item mb-2 p-2" :class="msg.sender_type === 'admin' ? 'bg-light' : 'bg-info-light'">
                        <small class="text-muted">{{ msg.sender_type }} - {{ formatDate(msg.created_at) }}</small>
                        <p class="mb-0">{{ msg.message }}</p>
                    </div>
                </div>
                <hr>
                <h6>Admin Notes</h6>
                <b-form-textarea v-model="adminNotes" rows="2" placeholder="Internal notes..." />
                <b-button variant="outline-primary" size="sm" class="mt-2" @click="saveNotes">Save Notes</b-button>
            </div>
        </b-modal>

        <!-- Quote Modal -->
        <b-modal v-model="showQuoteModal" title="Send Quote" size="lg" @ok="sendQuote" ok-title="Send Quote">
            <b-form @submit.prevent="sendQuote">
                <h6>Item Prices</h6>
                <div v-for="(item, index) in quoteForm.items" :key="index" class="row mb-2">
                    <div class="col-md-6">
                        <small>{{ item.description }}</small>
                    </div>
                    <div class="col-md-3">
                        <b-form-input v-model="item.quoted_price" type="number" step="0.01" placeholder="Price" size="sm" />
                    </div>
                    <div class="col-md-3">
                        <small class="text-muted">Qty: {{ item.quantity }}</small>
                    </div>
                </div>
                <hr>
                <div class="row">
                    <div class="col-md-6">
                        <b-form-group label="Total Quoted Amount" label-for="total">
                            <b-form-input id="total" v-model="quoteForm.quoted_amount" type="number" step="0.01" required />
                        </b-form-group>
                    </div>
                    <div class="col-md-6">
                        <b-form-group label="Quote Valid Until" label-for="valid-until">
                            <b-form-input id="valid-until" v-model="quoteForm.quote_valid_until" type="date" required />
                        </b-form-group>
                    </div>
                </div>
                <b-form-group label="Quote Notes" label-for="quote-notes">
                    <b-form-textarea id="quote-notes" v-model="quoteForm.quote_notes" rows="2" placeholder="Terms, conditions, etc." />
                </b-form-group>
            </b-form>
        </b-modal>

        <!-- Message Modal -->
        <b-modal v-model="showMessageModal" title="Send Message" @ok="sendMessage" ok-title="Send">
            <b-form-group label="Message *" label-for="message">
                <b-form-textarea id="message" v-model="messageForm.message" rows="4" required placeholder="Type your message..." />
            </b-form-group>
        </b-modal>

        <!-- Status Update Modal -->
        <b-modal v-model="showStatusModal" title="Update Status" @ok="saveStatus" ok-title="Update">
            <b-form-group label="New Status" label-for="new-status">
                <v-select id="new-status" v-model="statusForm.status" :options="statusOptions" placeholder="Select status" />
            </b-form-group>
            <b-form-group label="Notes" label-for="status-notes">
                <b-form-textarea id="status-notes" v-model="statusForm.notes" rows="2" placeholder="Optional notes" />
            </b-form-group>
        </b-modal>
    </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue'
import axiosEmployee from '@axiosEmployee'
import { toast } from 'vue3-toastify'

// State
const stats = ref({ total: 0, pending: 0, under_review: 0, quoted: 0, in_progress: 0, completed: 0 })
const inquiries = ref([])
const search = ref('')
const statusFilter = ref(null)
const priorityFilter = ref(null)

const showViewModal = ref(false)
const showQuoteModal = ref(false)
const showMessageModal = ref(false)
const showStatusModal = ref(false)
const selectedInquiry = ref(null)
const editingItems = ref(false)
const adminNotes = ref('')

// Options
const statusOptions = ['pending', 'under_review', 'quoted', 'accepted', 'in_progress', 'completed', 'cancelled', 'rejected']
const priorityOptions = ['low', 'normal', 'high', 'urgent']

// Fields
const inquiryFields = [
    { key: 'inquiry_number', label: 'Inquiry #' },
    { key: 'partner', label: 'Partner' },
    { key: 'subject', label: 'Subject' },
    { key: 'status', label: 'Status' },
    { key: 'priority', label: 'Priority' },
    { key: 'quoted_amount', label: 'Quote' },
    { key: 'created_at', label: 'Date' },
    { key: 'actions', label: 'Actions' }
]

const itemFields = [
    { key: 'description', label: 'Description' },
    { key: 'quantity', label: 'Qty' },
    { key: 'quoted_price', label: 'Price' },
    { key: 'fulfillment_status', label: 'Fulfillment' },
    { key: 'notes', label: 'Notes' }
]

// Form Data
const quoteForm = ref({ items: [], quoted_amount: 0, quote_valid_until: '', quote_notes: '' })
const messageForm = ref({ message: '' })
const statusForm = ref({ status: '', notes: '' })

// Fetch functions
const fetchDashboard = async () => {
    try {
        const response = await axiosEmployee.get('/admin/partner-inquiries/dashboard')
        if (response.data.success) {
            stats.value = response.data.data.stats || response.data.data
        }
    } catch (error) {
        console.error('Error fetching dashboard:', error)
    }
}

const fetchInquiries = async () => {
    try {
        const response = await axiosEmployee.get('/admin/partner-inquiries', {
            params: { search: search.value, status: statusFilter.value, priority: priorityFilter.value }
        })
        if (response.data.success) {
            inquiries.value = response.data.data.data || []
        }
    } catch (error) {
        console.error('Error fetching inquiries:', error)
    }
}

// Helpers
const formatStatus = (status) => status?.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase()) || ''
const formatDate = (date) => date ? new Date(date).toLocaleDateString() : '-'
const formatNumber = (num) => Number(num).toLocaleString('en-IN')

const getStatusVariant = (status) => ({
    pending: 'warning', under_review: 'info', quoted: 'primary',
    accepted: 'success', in_progress: 'info', completed: 'success',
    cancelled: 'secondary', rejected: 'danger'
}[status] || 'secondary')

const getPriorityVariant = (priority) => ({
    low: 'secondary', normal: 'info', high: 'warning', urgent: 'danger'
}[priority] || 'secondary')

const getFulfillmentVariant = (status) => ({
    pending: 'warning', processing: 'info', shipped: 'primary', delivered: 'success'
}[status] || 'secondary')

const canReject = (inquiry) => ['pending', 'under_review'].includes(inquiry.status)

// Actions
const viewInquiry = async (inquiry) => {
    try {
        const response = await axiosEmployee.get(`/admin/partner-inquiries/${inquiry.id}`)
        if (response.data.success) {
            selectedInquiry.value = response.data.data
            adminNotes.value = response.data.data.admin_notes || ''
            showViewModal.value = true
        }
    } catch (error) {
        toast.error('Failed to load inquiry details')
    }
}

const startReview = async (id) => {
    try {
        await axiosEmployee.post(`/admin/partner-inquiries/${id}/start-review`)
        toast.success('Review started')
        fetchInquiries()
        fetchDashboard()
    } catch (error) {
        toast.error(error.response?.data?.message || 'Failed to start review')
    }
}

const openQuoteModal = (inquiry) => {
    selectedInquiry.value = inquiry
    quoteForm.value = {
        items: inquiry.items?.map(i => ({ ...i, quoted_price: i.quoted_price || '' })) || [],
        quoted_amount: inquiry.quoted_amount || 0,
        quote_valid_until: '',
        quote_notes: ''
    }
    showQuoteModal.value = true
}

const sendQuote = async () => {
    try {
        await axiosEmployee.post(`/admin/partner-inquiries/${selectedInquiry.value.id}/quote`, quoteForm.value)
        toast.success('Quote sent')
        showQuoteModal.value = false
        fetchInquiries()
        fetchDashboard()
    } catch (error) {
        toast.error(error.response?.data?.message || 'Failed to send quote')
    }
}

const openMessageModal = (inquiry) => {
    selectedInquiry.value = inquiry
    messageForm.value = { message: '' }
    showMessageModal.value = true
}

const sendMessage = async () => {
    try {
        await axiosEmployee.post(`/admin/partner-inquiries/${selectedInquiry.value.id}/message`, messageForm.value)
        toast.success('Message sent')
        showMessageModal.value = false
    } catch (error) {
        toast.error(error.response?.data?.message || 'Failed to send message')
    }
}

const updateStatus = (inquiry) => {
    selectedInquiry.value = inquiry
    statusForm.value = { status: inquiry.status, notes: '' }
    showStatusModal.value = true
}

const saveStatus = async () => {
    try {
        await axiosEmployee.put(`/admin/partner-inquiries/${selectedInquiry.value.id}/status`, statusForm.value)
        toast.success('Status updated')
        showStatusModal.value = false
        fetchInquiries()
        fetchDashboard()
    } catch (error) {
        toast.error(error.response?.data?.message || 'Failed to update status')
    }
}

const saveNotes = async () => {
    try {
        await axiosEmployee.put(`/admin/partner-inquiries/${selectedInquiry.value.id}/notes`, { admin_notes: adminNotes.value })
        toast.success('Notes saved')
    } catch (error) {
        toast.error(error.response?.data?.message || 'Failed to save notes')
    }
}

const rejectInquiry = async (id) => {
    const reason = prompt('Reason for rejection:')
    if (!reason) return
    try {
        await axiosEmployee.post(`/admin/partner-inquiries/${id}/reject`, { reason })
        toast.success('Inquiry rejected')
        fetchInquiries()
        fetchDashboard()
    } catch (error) {
        toast.error(error.response?.data?.message || 'Failed to reject')
    }
}

// Watchers
watch([statusFilter, priorityFilter], fetchInquiries)

// Lifecycle
onMounted(() => {
    fetchDashboard()
    fetchInquiries()
})
</script>

<style scoped>
.stat-card {
    background: #fff;
    border-radius: 8px;
    padding: 15px;
    text-align: center;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}
.stat-card h3 { font-size: 1.5rem; margin-bottom: 5px; color: #333; }
.stat-card p { margin: 0; color: #666; font-size: 0.85rem; }
.stat-card.warning h3 { color: #ffc107; }
.stat-card.info h3 { color: #17a2b8; }
.stat-card.primary h3 { color: #007bff; }
.stat-card.success h3 { color: #28a745; }
.status-select { min-width: 150px; }
.bg-info-light { background-color: #e7f3ff; }
</style>
