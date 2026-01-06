<template>
    <div class="listing_screen global_table_liting">
        <div class="masterTabs">
            <div class="masterTabContent">
                <!-- Dashboard Stats -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="stat-card">
                            <h3>{{ stats.total_inquiries }}</h3>
                            <p>Total Inquiries</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <h3>{{ stats.pending_quotes }}</h3>
                            <p>Pending Quotes</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <h3>{{ stats.active_inquiries }}</h3>
                            <p>Active Inquiries</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card success">
                            <h3>{{ stats.completed }}</h3>
                            <p>Completed</p>
                        </div>
                    </div>
                </div>

                <!-- Filters and Actions -->
                <div class="listing_tab_and_actions mb-3">
                    <div class="listing_actions">
                        <div class="d-flex gap-2">
                            <v-select v-model="statusFilter" :options="statusOptions"
                                placeholder="Filter by status" :clearable="true" class="status-select" />
                            <div class="listing_search">
                                <b-form-input v-model="search" @input="fetchInquiries" placeholder="Search inquiries..." />
                            </div>
                        </div>
                        <div class="buttonGrid">
                            <b-button class="fillBTN" @click="openCreateModal">New Inquiry</b-button>
                        </div>
                    </div>
                </div>

                <!-- Inquiries Table -->
                <b-table responsive="sm" :items="inquiries" :fields="inquiryFields" v-if="inquiries.length > 0">
                    <template #cell(inquiry_number)="row">
                        <a href="#" @click.prevent="viewInquiry(row.item)">{{ row.item.inquiry_number }}</a>
                    </template>
                    <template #cell(status)="row">
                        <b-badge :variant="getStatusVariant(row.item.status)">{{ formatStatus(row.item.status) }}</b-badge>
                    </template>
                    <template #cell(total_items)="row">
                        {{ row.item.items?.length || 0 }} items
                    </template>
                    <template #cell(quoted_amount)="row">
                        <span v-if="row.item.quoted_amount">₹{{ formatNumber(row.item.quoted_amount) }}</span>
                        <span v-else class="text-muted">Pending</span>
                    </template>
                    <template #cell(created_at)="row">
                        {{ formatDate(row.item.created_at) }}
                    </template>
                    <template #cell(actions)="row">
                        <b-dropdown right text="" variant="link" no-caret toggle-class="p-0">
                            <b-dropdown-item @click="viewInquiry(row.item)">View Details</b-dropdown-item>
                            <b-dropdown-item @click="openMessageModal(row.item)">Send Message</b-dropdown-item>
                            <b-dropdown-item v-if="row.item.status === 'quoted'" @click="acceptQuote(row.item.id)">Accept Quote</b-dropdown-item>
                            <b-dropdown-item v-if="row.item.status === 'quoted'" @click="rejectQuote(row.item.id)">Reject Quote</b-dropdown-item>
                            <b-dropdown-item v-if="canCancel(row.item)" @click="cancelInquiry(row.item.id)">Cancel</b-dropdown-item>
                        </b-dropdown>
                    </template>
                </b-table>

                <div v-else class="text-center p-5">
                    <p>No inquiries found. Create your first inquiry to get started.</p>
                </div>
            </div>
        </div>

        <!-- Create Inquiry Modal -->
        <b-modal v-model="showCreateModal" title="New Inquiry" size="lg" @ok="createInquiry" ok-title="Submit">
            <b-form @submit.prevent="createInquiry">
                <b-form-group label="Subject *" label-for="subject">
                    <b-form-input id="subject" v-model="inquiryForm.subject" required placeholder="Brief description of your inquiry" />
                </b-form-group>
                <b-form-group label="Description" label-for="description">
                    <b-form-textarea id="description" v-model="inquiryForm.description" rows="3" placeholder="Detailed requirements" />
                </b-form-group>
                <b-form-group label="Priority" label-for="priority">
                    <v-select id="priority" v-model="inquiryForm.priority" :options="priorityOptions" :reduce="p => p.value" label="label" placeholder="Select priority" />
                </b-form-group>
                <hr>
                <h6>Items</h6>
                <div v-for="(item, index) in inquiryForm.items" :key="index" class="row align-items-end mb-2">
                    <div class="col-md-5">
                        <b-form-group label="Product/Description" :label-for="'item-desc-' + index">
                            <b-form-input :id="'item-desc-' + index" v-model="item.description" placeholder="Product description" />
                        </b-form-group>
                    </div>
                    <div class="col-md-2">
                        <b-form-group label="Quantity" :label-for="'item-qty-' + index">
                            <b-form-input :id="'item-qty-' + index" v-model="item.quantity" type="number" min="1" placeholder="Qty" />
                        </b-form-group>
                    </div>
                    <div class="col-md-3">
                        <b-form-group label="Notes" :label-for="'item-notes-' + index">
                            <b-form-input :id="'item-notes-' + index" v-model="item.notes" placeholder="Notes" />
                        </b-form-group>
                    </div>
                    <div class="col-md-2">
                        <b-button variant="outline-danger" size="sm" @click="removeItem(index)" v-if="inquiryForm.items.length > 1">Remove</b-button>
                    </div>
                </div>
                <b-button variant="outline-primary" size="sm" @click="addItem">+ Add Item</b-button>
            </b-form>
        </b-modal>

        <!-- View Inquiry Modal -->
        <b-modal v-model="showViewModal" :title="'Inquiry: ' + (selectedInquiry?.inquiry_number || '')" size="xl" hide-footer>
            <div v-if="selectedInquiry">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <strong>Status:</strong> <b-badge :variant="getStatusVariant(selectedInquiry.status)">{{ formatStatus(selectedInquiry.status) }}</b-badge>
                    </div>
                    <div class="col-md-6">
                        <strong>Priority:</strong> {{ selectedInquiry.priority || 'Normal' }}
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
                        <span v-if="row.item.quoted_price">₹{{ formatNumber(row.item.quoted_price) }}</span>
                        <span v-else class="text-muted">-</span>
                    </template>
                </b-table>
                <hr>
                <div class="row" v-if="selectedInquiry.quoted_amount">
                    <div class="col-md-6">
                        <strong>Quoted Amount:</strong> ₹{{ formatNumber(selectedInquiry.quoted_amount) }}
                    </div>
                    <div class="col-md-6">
                        <strong>Valid Until:</strong> {{ formatDate(selectedInquiry.quote_valid_until) }}
                    </div>
                </div>
                <hr>
                <h6>Messages</h6>
                <div class="messages-container" style="max-height: 300px; overflow-y: auto;">
                    <div v-for="msg in selectedInquiry.messages" :key="msg.id" class="message-item mb-2 p-2" :class="msg.sender_type === 'partner' ? 'bg-light' : 'bg-info-light'">
                        <small class="text-muted">{{ msg.sender_type }} - {{ formatDate(msg.created_at) }}</small>
                        <p class="mb-0">{{ msg.message }}</p>
                    </div>
                </div>
            </div>
        </b-modal>

        <!-- Message Modal -->
        <b-modal v-model="showMessageModal" title="Send Message" @ok="sendMessage" ok-title="Send">
            <b-form-group label="Message *" label-for="message">
                <b-form-textarea id="message" v-model="messageForm.message" rows="4" required placeholder="Type your message..." />
            </b-form-group>
        </b-modal>
    </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue'
import axiosPartner from '@axiosPartner'
import { toast } from 'vue3-toastify'

// State
const stats = ref({ total_inquiries: 0, pending_quotes: 0, active_inquiries: 0, completed: 0 })
const inquiries = ref([])
const search = ref('')
const statusFilter = ref(null)

const showCreateModal = ref(false)
const showViewModal = ref(false)
const showMessageModal = ref(false)
const selectedInquiry = ref(null)

// Options
const statusOptions = ['pending', 'under_review', 'quoted', 'accepted', 'in_progress', 'completed', 'cancelled']
const priorityOptions = [
    { value: 'low', label: 'Low' },
    { value: 'normal', label: 'Normal' },
    { value: 'high', label: 'High' },
    { value: 'urgent', label: 'Urgent' }
]

// Fields
const inquiryFields = [
    { key: 'inquiry_number', label: 'Inquiry #' },
    { key: 'subject', label: 'Subject' },
    { key: 'status', label: 'Status' },
    { key: 'total_items', label: 'Items' },
    { key: 'quoted_amount', label: 'Quote' },
    { key: 'created_at', label: 'Date' },
    { key: 'actions', label: 'Actions' }
]

const itemFields = [
    { key: 'description', label: 'Description' },
    { key: 'quantity', label: 'Qty' },
    { key: 'quoted_price', label: 'Price' },
    { key: 'notes', label: 'Notes' }
]

// Form Data
const inquiryForm = ref({
    subject: '',
    description: '',
    priority: 'normal',
    items: [{ description: '', quantity: 1, notes: '' }]
})

const messageForm = ref({ message: '' })

// Fetch functions
const fetchStats = async () => {
    try {
        const response = await axiosPartner.get('/partner/inquiries/statistics')
        if (response.data.success) {
            stats.value = response.data.data
        }
    } catch (error) {
        console.error('Error fetching stats:', error)
    }
}

const fetchInquiries = async () => {
    try {
        const response = await axiosPartner.get('/partner/inquiries', {
            params: { search: search.value, status: statusFilter.value }
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

const getStatusVariant = (status) => {
    return {
        pending: 'warning', under_review: 'info', quoted: 'primary',
        accepted: 'success', in_progress: 'info', completed: 'success',
        cancelled: 'danger', rejected: 'danger'
    }[status] || 'secondary'
}

const canCancel = (inquiry) => ['pending', 'under_review'].includes(inquiry.status)

// Actions
const openCreateModal = () => {
    inquiryForm.value = {
        subject: '', description: '', priority: 'normal',
        items: [{ description: '', quantity: 1, notes: '' }]
    }
    showCreateModal.value = true
}

const addItem = () => {
    inquiryForm.value.items.push({ description: '', quantity: 1, notes: '' })
}

const removeItem = (index) => {
    inquiryForm.value.items.splice(index, 1)
}

const createInquiry = async () => {
    try {
        await axiosPartner.post('/partner/inquiries', inquiryForm.value)
        toast.success('Inquiry created successfully')
        showCreateModal.value = false
        fetchInquiries()
        fetchStats()
    } catch (error) {
        toast.error(error.response?.data?.message || 'Failed to create inquiry')
    }
}

const viewInquiry = async (inquiry) => {
    try {
        const response = await axiosPartner.get(`/partner/inquiries/${inquiry.id}`)
        if (response.data.success) {
            selectedInquiry.value = response.data.data
            showViewModal.value = true
        }
    } catch (error) {
        toast.error('Failed to load inquiry details')
    }
}

const openMessageModal = (inquiry) => {
    selectedInquiry.value = inquiry
    messageForm.value = { message: '' }
    showMessageModal.value = true
}

const sendMessage = async () => {
    try {
        await axiosPartner.post(`/partner/inquiries/${selectedInquiry.value.id}/message`, messageForm.value)
        toast.success('Message sent')
        showMessageModal.value = false
        if (showViewModal.value) {
            viewInquiry(selectedInquiry.value)
        }
    } catch (error) {
        toast.error(error.response?.data?.message || 'Failed to send message')
    }
}

const acceptQuote = async (id) => {
    if (!confirm('Accept this quote?')) return
    try {
        await axiosPartner.post(`/partner/inquiries/${id}/accept-quote`)
        toast.success('Quote accepted')
        fetchInquiries()
        fetchStats()
    } catch (error) {
        toast.error(error.response?.data?.message || 'Failed to accept quote')
    }
}

const rejectQuote = async (id) => {
    const reason = prompt('Reason for rejection:')
    if (!reason) return
    try {
        await axiosPartner.post(`/partner/inquiries/${id}/reject-quote`, { reason })
        toast.success('Quote rejected')
        fetchInquiries()
    } catch (error) {
        toast.error(error.response?.data?.message || 'Failed to reject quote')
    }
}

const cancelInquiry = async (id) => {
    const reason = prompt('Reason for cancellation:')
    if (!reason) return
    try {
        await axiosPartner.post(`/partner/inquiries/${id}/cancel`, { reason })
        toast.success('Inquiry cancelled')
        fetchInquiries()
        fetchStats()
    } catch (error) {
        toast.error(error.response?.data?.message || 'Failed to cancel inquiry')
    }
}

// Watchers
watch(statusFilter, fetchInquiries)

// Lifecycle
onMounted(() => {
    fetchStats()
    fetchInquiries()
})
</script>

<style scoped>
.stat-card {
    background: #fff;
    border-radius: 8px;
    padding: 20px;
    text-align: center;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}
.stat-card h3 {
    font-size: 2rem;
    margin-bottom: 5px;
    color: #333;
}
.stat-card p {
    margin: 0;
    color: #666;
}
.stat-card.success h3 {
    color: #28a745;
}
.status-select {
    min-width: 200px;
}
.bg-info-light {
    background-color: #e7f3ff;
}
</style>
