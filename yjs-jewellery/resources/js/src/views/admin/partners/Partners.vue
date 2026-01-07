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
                        <div class="stat-card success">
                            <h3>{{ stats.approved }}</h3>
                            <p>Approved</p>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="stat-card danger">
                            <h3>{{ stats.rejected }}</h3>
                            <p>Rejected</p>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="stat-card info">
                            <h3>{{ stats.suspended }}</h3>
                            <p>Suspended</p>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="stat-card primary">
                            <h3>{{ stats.total }}</h3>
                            <p>Total</p>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="stat-card">
                            <h3>{{ formatNumber(stats.total_orders || 0) }}</h3>
                            <p>Total Orders</p>
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
                                <b-form-input v-model="search" @input="debouncedFetch" placeholder="Search partners..." />
                            </div>
                        </div>
                        <div class="buttonGrid">
                            <b-button class="transBTN" @click="exportPartners">Export</b-button>
                        </div>
                    </div>
                </div>

                <!-- Partners Table -->
                <b-table responsive="sm" :items="partners" :fields="partnerFields" :busy="loading" v-if="partners.length > 0 || loading">
                    <template #table-busy>
                        <div class="text-center my-4">
                            <b-spinner class="align-middle"></b-spinner>
                            <strong class="ml-2">Loading...</strong>
                        </div>
                    </template>
                    <template #cell(company_name)="row">
                        <a href="#" @click.prevent="viewPartner(row.item)">{{ row.item.company_name || row.item.business_name }}</a>
                        <br><small class="text-muted">{{ row.item.user?.email }}</small>
                    </template>
                    <template #cell(contact)="row">
                        <div>{{ row.item.user?.first_name }} {{ row.item.user?.last_name }}</div>
                        <small class="text-muted">{{ row.item.user?.phone }}</small>
                    </template>
                    <template #cell(business_type)="row">
                        {{ formatBusinessType(row.item.business_type) }}
                    </template>
                    <template #cell(status)="row">
                        <b-badge :variant="getStatusVariant(row.item.status)">{{ formatStatus(row.item.status) }}</b-badge>
                    </template>
                    <template #cell(total_orders)="row">
                        {{ row.item.orders_count || 0 }}
                    </template>
                    <template #cell(total_spent)="row">
                        {{ formatCurrency(row.item.total_spent || 0) }}
                    </template>
                    <template #cell(created_at)="row">
                        {{ formatDate(row.item.created_at) }}
                    </template>
                    <template #cell(actions)="row">
                        <b-dropdown right text="" variant="link" no-caret toggle-class="p-0">
                            <template #button-content>
                                <i class="fas fa-ellipsis-v"></i>
                            </template>
                            <b-dropdown-item @click="viewPartner(row.item)">View Details</b-dropdown-item>
                            <b-dropdown-item v-if="row.item.status === 'pending'" @click="approvePartner(row.item.id)">Approve</b-dropdown-item>
                            <b-dropdown-item v-if="row.item.status === 'pending'" @click="rejectPartner(row.item.id)">Reject</b-dropdown-item>
                            <b-dropdown-item v-if="row.item.status === 'approved'" @click="suspendPartner(row.item.id)">Suspend</b-dropdown-item>
                            <b-dropdown-item v-if="row.item.status === 'suspended'" @click="activatePartner(row.item.id)">Activate</b-dropdown-item>
                            <b-dropdown-divider></b-dropdown-divider>
                            <b-dropdown-item @click="viewOrders(row.item)">View Orders</b-dropdown-item>
                            <b-dropdown-item @click="viewInquiries(row.item)">View Inquiries</b-dropdown-item>
                        </b-dropdown>
                    </template>
                </b-table>

                <div v-if="!loading && partners.length === 0" class="text-center p-5">
                    <p>No partners found.</p>
                </div>

                <!-- Pagination -->
                <div class="d-flex justify-content-between align-items-center mt-3" v-if="pagination.total > 0">
                    <div class="text-muted">
                        Showing {{ pagination.from }} to {{ pagination.to }} of {{ pagination.total }} partners
                    </div>
                    <b-pagination
                        v-model="currentPage"
                        :total-rows="pagination.total"
                        :per-page="pagination.per_page"
                        @change="fetchPartners"
                    ></b-pagination>
                </div>
            </div>
        </div>

        <!-- View Partner Modal -->
        <b-modal v-model="showViewModal" :title="'Partner: ' + (selectedPartner?.company_name || '')" size="xl" hide-footer>
            <div v-if="selectedPartner">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <h6>Business Information</h6>
                        <table class="table table-sm">
                            <tr><td><strong>Company Name:</strong></td><td>{{ selectedPartner.company_name || selectedPartner.business_name }}</td></tr>
                            <tr><td><strong>Business Type:</strong></td><td>{{ formatBusinessType(selectedPartner.business_type) }}</td></tr>
                            <tr><td><strong>GSTIN:</strong></td><td>{{ selectedPartner.gstin || '-' }}</td></tr>
                            <tr><td><strong>PAN:</strong></td><td>{{ selectedPartner.pan || '-' }}</td></tr>
                            <tr><td><strong>Status:</strong></td><td><b-badge :variant="getStatusVariant(selectedPartner.status)">{{ formatStatus(selectedPartner.status) }}</b-badge></td></tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h6>Contact Information</h6>
                        <table class="table table-sm">
                            <tr><td><strong>Contact Person:</strong></td><td>{{ selectedPartner.user?.first_name }} {{ selectedPartner.user?.last_name }}</td></tr>
                            <tr><td><strong>Email:</strong></td><td>{{ selectedPartner.user?.email }}</td></tr>
                            <tr><td><strong>Phone:</strong></td><td>{{ selectedPartner.user?.phone }}</td></tr>
                            <tr><td><strong>Address:</strong></td><td>{{ selectedPartner.address }}, {{ selectedPartner.city }}, {{ selectedPartner.state }} - {{ selectedPartner.pincode }}</td></tr>
                        </table>
                    </div>
                </div>
                <hr>
                <div class="row">
                    <div class="col-md-3">
                        <div class="stat-card">
                            <h4>{{ selectedPartner.orders_count || 0 }}</h4>
                            <p>Total Orders</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <h4>{{ formatCurrency(selectedPartner.total_spent || 0) }}</h4>
                            <p>Total Spent</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <h4>{{ selectedPartner.inquiries_count || 0 }}</h4>
                            <p>Inquiries</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <h4>{{ formatDate(selectedPartner.created_at) }}</h4>
                            <p>Registered</p>
                        </div>
                    </div>
                </div>
                <hr>
                <h6>Admin Notes</h6>
                <b-form-textarea v-model="adminNotes" rows="2" placeholder="Internal notes about this partner..." />
                <b-button variant="outline-primary" size="sm" class="mt-2" @click="saveNotes">Save Notes</b-button>
            </div>
        </b-modal>

        <!-- Reject Modal -->
        <b-modal v-model="showRejectModal" title="Reject Partner" @ok="confirmReject" ok-title="Reject" ok-variant="danger">
            <b-form-group label="Reason for rejection" label-for="reject-reason">
                <b-form-textarea id="reject-reason" v-model="rejectReason" rows="3" placeholder="Enter reason..." required />
            </b-form-group>
        </b-modal>

        <!-- Suspend Modal -->
        <b-modal v-model="showSuspendModal" title="Suspend Partner" @ok="confirmSuspend" ok-title="Suspend" ok-variant="warning">
            <b-form-group label="Reason for suspension" label-for="suspend-reason">
                <b-form-textarea id="suspend-reason" v-model="suspendReason" rows="3" placeholder="Enter reason..." required />
            </b-form-group>
        </b-modal>
    </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue'
import axiosEmployee from '@axiosEmployee'
import { toast } from 'vue3-toastify'
import { useRouter } from 'vue-router'
import debounce from 'lodash/debounce'

const router = useRouter()

// State
const stats = ref({ pending: 0, approved: 0, rejected: 0, suspended: 0, total: 0, total_orders: 0 })
const partners = ref([])
const search = ref('')
const statusFilter = ref(null)
const loading = ref(false)
const currentPage = ref(1)
const pagination = ref({ total: 0, per_page: 15, from: 0, to: 0 })

const showViewModal = ref(false)
const showRejectModal = ref(false)
const showSuspendModal = ref(false)
const selectedPartner = ref(null)
const adminNotes = ref('')
const rejectReason = ref('')
const suspendReason = ref('')
const pendingActionId = ref(null)

// Options
const statusOptions = [
    { label: 'Pending', value: 'pending' },
    { label: 'Approved', value: 'approved' },
    { label: 'Rejected', value: 'rejected' },
    { label: 'Suspended', value: 'suspended' }
]

// Fields
const partnerFields = [
    { key: 'company_name', label: 'Company' },
    { key: 'contact', label: 'Contact Person' },
    { key: 'business_type', label: 'Type' },
    { key: 'status', label: 'Status' },
    { key: 'total_orders', label: 'Orders' },
    { key: 'total_spent', label: 'Total Spent' },
    { key: 'created_at', label: 'Registered' },
    { key: 'actions', label: 'Actions' }
]

// Fetch functions
const fetchStats = async () => {
    try {
        const response = await axiosEmployee.get('/admin/partners/stats')
        if (response.data.success) {
            stats.value = response.data.data
        }
    } catch (error) {
        console.error('Error fetching stats:', error)
    }
}

const fetchPartners = async () => {
    loading.value = true
    try {
        const response = await axiosEmployee.get('/admin/partners', {
            params: {
                search: search.value,
                status: statusFilter.value?.value || statusFilter.value,
                page: currentPage.value
            }
        })
        if (response.data.success) {
            const data = response.data.data
            partners.value = data.data || data
            if (data.meta) {
                pagination.value = {
                    total: data.meta.total,
                    per_page: data.meta.per_page,
                    from: data.meta.from,
                    to: data.meta.to
                }
            } else if (data.total) {
                pagination.value = {
                    total: data.total,
                    per_page: data.per_page,
                    from: data.from,
                    to: data.to
                }
            }
        }
    } catch (error) {
        console.error('Error fetching partners:', error)
        toast.error('Failed to load partners')
    } finally {
        loading.value = false
    }
}

const debouncedFetch = debounce(fetchPartners, 300)

// Helpers
const formatStatus = (status) => status?.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase()) || ''
const formatDate = (date) => date ? new Date(date).toLocaleDateString('en-IN') : '-'
const formatNumber = (num) => Number(num).toLocaleString('en-IN')
const formatCurrency = (amount) => '₹' + Number(amount).toLocaleString('en-IN')

const formatBusinessType = (type) => {
    const types = {
        'retailer': 'Retailer',
        'wholesaler': 'Wholesaler',
        'manufacturer': 'Manufacturer',
        'distributor': 'Distributor',
        'jeweler': 'Jeweler',
        'other': 'Other'
    }
    return types[type] || type || '-'
}

const getStatusVariant = (status) => ({
    pending: 'warning',
    approved: 'success',
    rejected: 'danger',
    suspended: 'secondary'
}[status] || 'secondary')

// Actions
const viewPartner = async (partner) => {
    try {
        const response = await axiosEmployee.get(`/admin/partners/${partner.id}`)
        if (response.data.success) {
            selectedPartner.value = response.data.data
            adminNotes.value = response.data.data.admin_notes || ''
            showViewModal.value = true
        }
    } catch (error) {
        toast.error('Failed to load partner details')
    }
}

const approvePartner = async (id) => {
    if (!confirm('Are you sure you want to approve this partner?')) return
    try {
        await axiosEmployee.post(`/admin/partners/${id}/approve`)
        toast.success('Partner approved successfully')
        fetchPartners()
        fetchStats()
    } catch (error) {
        toast.error(error.response?.data?.message || 'Failed to approve partner')
    }
}

const rejectPartner = (id) => {
    pendingActionId.value = id
    rejectReason.value = ''
    showRejectModal.value = true
}

const confirmReject = async () => {
    if (!rejectReason.value) {
        toast.error('Please provide a reason')
        return
    }
    try {
        await axiosEmployee.post(`/admin/partners/${pendingActionId.value}/reject`, { reason: rejectReason.value })
        toast.success('Partner rejected')
        showRejectModal.value = false
        fetchPartners()
        fetchStats()
    } catch (error) {
        toast.error(error.response?.data?.message || 'Failed to reject partner')
    }
}

const suspendPartner = (id) => {
    pendingActionId.value = id
    suspendReason.value = ''
    showSuspendModal.value = true
}

const confirmSuspend = async () => {
    if (!suspendReason.value) {
        toast.error('Please provide a reason')
        return
    }
    try {
        await axiosEmployee.post(`/admin/partners/${pendingActionId.value}/suspend`, { reason: suspendReason.value })
        toast.success('Partner suspended')
        showSuspendModal.value = false
        fetchPartners()
        fetchStats()
    } catch (error) {
        toast.error(error.response?.data?.message || 'Failed to suspend partner')
    }
}

const activatePartner = async (id) => {
    if (!confirm('Are you sure you want to activate this partner?')) return
    try {
        await axiosEmployee.post(`/admin/partners/${id}/activate`)
        toast.success('Partner activated')
        fetchPartners()
        fetchStats()
    } catch (error) {
        toast.error(error.response?.data?.message || 'Failed to activate partner')
    }
}

const saveNotes = async () => {
    try {
        await axiosEmployee.put(`/admin/partners/${selectedPartner.value.id}`, { admin_notes: adminNotes.value })
        toast.success('Notes saved')
    } catch (error) {
        toast.error(error.response?.data?.message || 'Failed to save notes')
    }
}

const viewOrders = (partner) => {
    router.push({ name: 'admin.orders', query: { partner_id: partner.id } })
}

const viewInquiries = (partner) => {
    router.push({ name: 'admin.partner-inquiries', query: { partner_id: partner.id } })
}

const exportPartners = async () => {
    try {
        const response = await axiosEmployee.get('/admin/partners/export', {
            params: { status: statusFilter.value?.value || statusFilter.value },
            responseType: 'blob'
        })
        const url = window.URL.createObjectURL(new Blob([response.data]))
        const link = document.createElement('a')
        link.href = url
        link.setAttribute('download', 'partners.csv')
        document.body.appendChild(link)
        link.click()
        link.remove()
    } catch (error) {
        toast.error('Failed to export')
    }
}

// Watchers
watch(statusFilter, () => {
    currentPage.value = 1
    fetchPartners()
})

// Lifecycle
onMounted(() => {
    fetchStats()
    fetchPartners()
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
.stat-card h3, .stat-card h4 { font-size: 1.5rem; margin-bottom: 5px; color: #333; }
.stat-card p { margin: 0; color: #666; font-size: 0.85rem; }
.stat-card.warning h3 { color: #ffc107; }
.stat-card.success h3 { color: #28a745; }
.stat-card.danger h3 { color: #dc3545; }
.stat-card.info h3 { color: #17a2b8; }
.stat-card.primary h3 { color: #007bff; }
.status-select { min-width: 150px; }
</style>
