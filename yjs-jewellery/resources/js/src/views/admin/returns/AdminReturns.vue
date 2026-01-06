<template>
    <div class="listing_screen global_table_liting">
        <div class="masterTabs">
            <div class="masterTabContent">
                <!-- Dashboard Stats -->
                <div class="row mb-4">
                    <div class="col-md-2">
                        <div class="stat-card warning">
                            <h3>{{ stats.pending }}</h3>
                            <p>Pending</p>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="stat-card info">
                            <h3>{{ stats.approved }}</h3>
                            <p>Approved</p>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="stat-card primary">
                            <h3>{{ stats.in_transit }}</h3>
                            <p>In Transit</p>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="stat-card info">
                            <h3>{{ stats.received }}</h3>
                            <p>Received</p>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="stat-card success">
                            <h3>{{ stats.completed }}</h3>
                            <p>Completed</p>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="stat-card">
                            <h3>₹{{ formatNumber(stats.total_refunds || 0) }}</h3>
                            <p>Total Refunds</p>
                        </div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="listing_tab_and_actions mb-3">
                    <div class="listing_actions">
                        <div class="d-flex gap-2">
                            <v-select v-model="statusFilter" :options="statusOptions"
                                placeholder="Status" :clearable="true" class="status-select" />
                            <div class="listing_search">
                                <b-form-input v-model="search" @input="fetchReturns" placeholder="Search returns..." />
                            </div>
                        </div>
                        <div class="buttonGrid">
                            <b-button class="transBTN" @click="exportReturns">Export</b-button>
                        </div>
                    </div>
                </div>

                <!-- Returns Table -->
                <b-table responsive="sm" :items="returns" :fields="returnFields" v-if="returns.length > 0">
                    <template #cell(return_code)="row">
                        <a href="#" @click.prevent="viewReturn(row.item)">{{ row.item.return_code }}</a>
                    </template>
                    <template #cell(customer)="row">
                        {{ row.item.customer?.name || '-' }}
                    </template>
                    <template #cell(order)="row">
                        {{ row.item.order?.custom_order_code || '-' }}
                    </template>
                    <template #cell(status)="row">
                        <b-badge :variant="getStatusVariant(row.item.status)">{{ formatStatus(row.item.status) }}</b-badge>
                    </template>
                    <template #cell(refund_amount)="row">
                        <span v-if="row.item.refund_amount">₹{{ formatNumber(row.item.refund_amount) }}</span>
                        <span v-else class="text-muted">-</span>
                    </template>
                    <template #cell(created_at)="row">
                        {{ formatDate(row.item.created_at) }}
                    </template>
                    <template #cell(actions)="row">
                        <b-dropdown right text="" variant="link" no-caret toggle-class="p-0">
                            <b-dropdown-item @click="viewReturn(row.item)">View Details</b-dropdown-item>
                            <b-dropdown-item v-if="row.item.status === 'pending'" @click="startReview(row.item.id)">Start Review</b-dropdown-item>
                            <b-dropdown-item v-if="row.item.status === 'under_review'" @click="approveReturn(row.item.id)">Approve</b-dropdown-item>
                            <b-dropdown-item v-if="row.item.status === 'under_review'" @click="rejectReturn(row.item.id)">Reject</b-dropdown-item>
                            <b-dropdown-item v-if="row.item.status === 'approved'" @click="schedulePickup(row.item)">Schedule Pickup</b-dropdown-item>
                            <b-dropdown-item v-if="row.item.status === 'pickup_scheduled'" @click="markPickedUp(row.item.id)">Mark Picked Up</b-dropdown-item>
                            <b-dropdown-item v-if="row.item.status === 'picked_up'" @click="markReceived(row.item.id)">Mark Received</b-dropdown-item>
                            <b-dropdown-item v-if="row.item.status === 'received'" @click="openInspectionModal(row.item)">Record Inspection</b-dropdown-item>
                            <b-dropdown-item v-if="row.item.status === 'inspected'" @click="initiateRefund(row.item)">Initiate Refund</b-dropdown-item>
                        </b-dropdown>
                    </template>
                </b-table>

                <div v-else class="text-center p-5">
                    <p>No return requests found.</p>
                </div>
            </div>
        </div>

        <!-- View Return Modal -->
        <b-modal v-model="showViewModal" :title="'Return: ' + (selectedReturn?.return_code || '')" size="xl" hide-footer>
            <div v-if="selectedReturn">
                <div class="row mb-3">
                    <div class="col-md-3">
                        <strong>Customer:</strong> {{ selectedReturn.customer?.name }}
                    </div>
                    <div class="col-md-3">
                        <strong>Order:</strong> {{ selectedReturn.order?.custom_order_code }}
                    </div>
                    <div class="col-md-3">
                        <strong>Status:</strong> <b-badge :variant="getStatusVariant(selectedReturn.status)">{{ formatStatus(selectedReturn.status) }}</b-badge>
                    </div>
                    <div class="col-md-3">
                        <strong>Date:</strong> {{ formatDate(selectedReturn.created_at) }}
                    </div>
                </div>
                <div class="mb-3">
                    <strong>Reason:</strong> {{ selectedReturn.reason }}
                </div>
                <div class="mb-3" v-if="selectedReturn.description">
                    <strong>Description:</strong> {{ selectedReturn.description }}
                </div>
                <hr>
                <h6>Items</h6>
                <b-table responsive="sm" :items="selectedReturn.items" :fields="itemFields" small>
                    <template #cell(product)="row">
                        {{ row.item.product?.name || 'Product' }}
                    </template>
                    <template #cell(inspection_result)="row">
                        <b-badge v-if="row.item.inspection_result" :variant="row.item.inspection_result === 'approved' ? 'success' : 'danger'">
                            {{ row.item.inspection_result }}
                        </b-badge>
                        <span v-else class="text-muted">Pending</span>
                    </template>
                </b-table>
                <hr>
                <div class="row">
                    <div class="col-md-4">
                        <strong>Refund Amount:</strong> ₹{{ formatNumber(selectedReturn.refund_amount || 0) }}
                    </div>
                    <div class="col-md-4">
                        <strong>Refund Status:</strong> {{ selectedReturn.refund_status || 'Pending' }}
                    </div>
                    <div class="col-md-4">
                        <strong>Refund Method:</strong> {{ selectedReturn.refund_method || '-' }}
                    </div>
                </div>
                <hr>
                <h6>Admin Notes</h6>
                <b-form-textarea v-model="adminNotes" rows="2" placeholder="Internal notes..." />
                <b-button variant="outline-primary" size="sm" class="mt-2" @click="saveNotes">Save Notes</b-button>
            </div>
        </b-modal>

        <!-- Inspection Modal -->
        <b-modal v-model="showInspectionModal" title="Record Inspection" @ok="saveInspection" ok-title="Save">
            <b-form @submit.prevent="saveInspection">
                <b-form-group label="Inspection Result" label-for="result">
                    <v-select id="result" v-model="inspectionForm.inspection_result" :options="inspectionResults" placeholder="Select result" />
                </b-form-group>
                <b-form-group label="Condition" label-for="condition">
                    <v-select id="condition" v-model="inspectionForm.item_condition" :options="itemConditions" placeholder="Select condition" />
                </b-form-group>
                <b-form-group label="Notes" label-for="inspection-notes">
                    <b-form-textarea id="inspection-notes" v-model="inspectionForm.inspection_notes" rows="3" placeholder="Inspection findings..." />
                </b-form-group>
            </b-form>
        </b-modal>

        <!-- Refund Modal -->
        <b-modal v-model="showRefundModal" title="Initiate Refund" @ok="processRefund" ok-title="Process Refund">
            <b-form @submit.prevent="processRefund">
                <b-form-group label="Refund Amount" label-for="refund-amount">
                    <b-form-input id="refund-amount" v-model="refundForm.amount" type="number" step="0.01" required />
                </b-form-group>
                <b-form-group label="Refund Method" label-for="refund-method">
                    <v-select id="refund-method" v-model="refundForm.method" :options="refundMethods" :reduce="m => m.value" label="label" placeholder="Select method" />
                </b-form-group>
                <b-form-group label="Notes" label-for="refund-notes">
                    <b-form-textarea id="refund-notes" v-model="refundForm.notes" rows="2" placeholder="Refund notes..." />
                </b-form-group>
            </b-form>
        </b-modal>
    </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue'
import axiosEmployee from '@axiosEmployee'
import { toast } from 'vue3-toastify'

// State
const stats = ref({ pending: 0, approved: 0, in_transit: 0, received: 0, completed: 0, total_refunds: 0 })
const returns = ref([])
const search = ref('')
const statusFilter = ref(null)

const showViewModal = ref(false)
const showInspectionModal = ref(false)
const showRefundModal = ref(false)
const selectedReturn = ref(null)
const adminNotes = ref('')

// Options
const statusOptions = ['pending', 'under_review', 'approved', 'pickup_scheduled', 'picked_up', 'received', 'inspected', 'refund_initiated', 'completed', 'rejected', 'cancelled']
const inspectionResults = ['approved', 'partial', 'rejected']
const itemConditions = ['excellent', 'good', 'fair', 'poor', 'damaged']
const refundMethods = [
    { value: 'original_payment', label: 'Original Payment Method' },
    { value: 'bank_transfer', label: 'Bank Transfer' },
    { value: 'store_credit', label: 'Store Credit' }
]

// Fields
const returnFields = [
    { key: 'return_code', label: 'Return #' },
    { key: 'customer', label: 'Customer' },
    { key: 'order', label: 'Order' },
    { key: 'reason', label: 'Reason' },
    { key: 'status', label: 'Status' },
    { key: 'refund_amount', label: 'Refund' },
    { key: 'created_at', label: 'Date' },
    { key: 'actions', label: 'Actions' }
]

const itemFields = [
    { key: 'product', label: 'Product' },
    { key: 'quantity', label: 'Qty' },
    { key: 'reason', label: 'Reason' },
    { key: 'inspection_result', label: 'Inspection' }
]

// Form Data
const inspectionForm = ref({ inspection_result: '', item_condition: '', inspection_notes: '' })
const refundForm = ref({ amount: 0, method: 'original_payment', notes: '' })

// Fetch functions
const fetchDashboard = async () => {
    try {
        const response = await axiosEmployee.get('/admin/returns/dashboard')
        if (response.data.success) {
            stats.value = response.data.data.stats || response.data.data
        }
    } catch (error) {
        console.error('Error fetching dashboard:', error)
    }
}

const fetchReturns = async () => {
    try {
        const response = await axiosEmployee.get('/admin/returns', {
            params: { search: search.value, status: statusFilter.value }
        })
        if (response.data.success) {
            returns.value = response.data.data.data || []
        }
    } catch (error) {
        console.error('Error fetching returns:', error)
    }
}

// Helpers
const formatStatus = (status) => status?.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase()) || ''
const formatDate = (date) => date ? new Date(date).toLocaleDateString() : '-'
const formatNumber = (num) => Number(num).toLocaleString('en-IN')

const getStatusVariant = (status) => ({
    pending: 'warning', under_review: 'info', approved: 'info',
    pickup_scheduled: 'primary', picked_up: 'primary', received: 'info',
    inspected: 'info', refund_initiated: 'success', completed: 'success',
    rejected: 'danger', cancelled: 'secondary'
}[status] || 'secondary')

// Actions
const viewReturn = async (ret) => {
    try {
        const response = await axiosEmployee.get(`/admin/returns/${ret.id}`)
        if (response.data.success) {
            selectedReturn.value = response.data.data
            adminNotes.value = response.data.data.admin_notes || ''
            showViewModal.value = true
        }
    } catch (error) {
        toast.error('Failed to load return details')
    }
}

const startReview = async (id) => {
    try {
        await axiosEmployee.post(`/admin/returns/${id}/start-review`)
        toast.success('Review started')
        fetchReturns()
        fetchDashboard()
    } catch (error) {
        toast.error(error.response?.data?.message || 'Failed to start review')
    }
}

const approveReturn = async (id) => {
    try {
        await axiosEmployee.post(`/admin/returns/${id}/approve`)
        toast.success('Return approved')
        fetchReturns()
        fetchDashboard()
    } catch (error) {
        toast.error(error.response?.data?.message || 'Failed to approve')
    }
}

const rejectReturn = async (id) => {
    const reason = prompt('Reason for rejection:')
    if (!reason) return
    try {
        await axiosEmployee.post(`/admin/returns/${id}/reject`, { reason })
        toast.success('Return rejected')
        fetchReturns()
        fetchDashboard()
    } catch (error) {
        toast.error(error.response?.data?.message || 'Failed to reject')
    }
}

const schedulePickup = async (ret) => {
    const date = prompt('Pickup date (YYYY-MM-DD):')
    if (!date) return
    try {
        await axiosEmployee.post(`/admin/returns/${ret.id}/schedule-pickup`, { pickup_date: date })
        toast.success('Pickup scheduled')
        fetchReturns()
    } catch (error) {
        toast.error(error.response?.data?.message || 'Failed to schedule pickup')
    }
}

const markPickedUp = async (id) => {
    try {
        await axiosEmployee.post(`/admin/returns/${id}/mark-picked-up`)
        toast.success('Marked as picked up')
        fetchReturns()
        fetchDashboard()
    } catch (error) {
        toast.error(error.response?.data?.message || 'Failed to update')
    }
}

const markReceived = async (id) => {
    try {
        await axiosEmployee.post(`/admin/returns/${id}/mark-received`)
        toast.success('Marked as received')
        fetchReturns()
        fetchDashboard()
    } catch (error) {
        toast.error(error.response?.data?.message || 'Failed to update')
    }
}

const openInspectionModal = (ret) => {
    selectedReturn.value = ret
    inspectionForm.value = { inspection_result: '', item_condition: '', inspection_notes: '' }
    showInspectionModal.value = true
}

const saveInspection = async () => {
    try {
        await axiosEmployee.post(`/admin/returns/${selectedReturn.value.id}/inspection`, inspectionForm.value)
        toast.success('Inspection recorded')
        showInspectionModal.value = false
        fetchReturns()
        fetchDashboard()
    } catch (error) {
        toast.error(error.response?.data?.message || 'Failed to save inspection')
    }
}

const initiateRefund = (ret) => {
    selectedReturn.value = ret
    refundForm.value = { amount: ret.refund_amount || 0, method: 'original_payment', notes: '' }
    showRefundModal.value = true
}

const processRefund = async () => {
    try {
        await axiosEmployee.post(`/admin/returns/${selectedReturn.value.id}/initiate-refund`, refundForm.value)
        toast.success('Refund initiated')
        showRefundModal.value = false
        fetchReturns()
        fetchDashboard()
    } catch (error) {
        toast.error(error.response?.data?.message || 'Failed to initiate refund')
    }
}

const saveNotes = async () => {
    try {
        await axiosEmployee.put(`/admin/returns/${selectedReturn.value.id}/notes`, { admin_notes: adminNotes.value })
        toast.success('Notes saved')
    } catch (error) {
        toast.error(error.response?.data?.message || 'Failed to save notes')
    }
}

const exportReturns = async () => {
    try {
        const response = await axiosEmployee.get('/admin/returns/export', { responseType: 'blob' })
        const url = window.URL.createObjectURL(new Blob([response.data]))
        const link = document.createElement('a')
        link.href = url
        link.setAttribute('download', 'returns.csv')
        document.body.appendChild(link)
        link.click()
        link.remove()
    } catch (error) {
        toast.error('Failed to export')
    }
}

// Watchers
watch(statusFilter, fetchReturns)

// Lifecycle
onMounted(() => {
    fetchDashboard()
    fetchReturns()
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
</style>
