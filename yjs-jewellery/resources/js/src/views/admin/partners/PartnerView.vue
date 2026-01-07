<template>
    <div class="listing_screen">
        <div class="masterTabs">
            <div class="masterTabContent">
                <!-- Back Button -->
                <div class="mb-3">
                    <b-button variant="outline-secondary" size="sm" @click="$router.back()">
                        <i class="fas fa-arrow-left"></i> Back to Partners
                    </b-button>
                </div>

                <div v-if="loading" class="text-center p-5">
                    <b-spinner></b-spinner>
                    <p>Loading partner details...</p>
                </div>

                <div v-else-if="partner">
                    <!-- Header -->
                    <div class="d-flex justify-content-between align-items-start mb-4">
                        <div>
                            <h2>{{ partner.company_name || partner.business_name }}</h2>
                            <p class="text-muted mb-0">Partner ID: #{{ partner.id }}</p>
                        </div>
                        <div>
                            <b-badge :variant="getStatusVariant(partner.status)" class="mr-2" style="font-size: 1rem;">
                                {{ formatStatus(partner.status) }}
                            </b-badge>
                            <b-dropdown right variant="primary" text="Actions">
                                <b-dropdown-item v-if="partner.status === 'pending'" @click="approvePartner">Approve</b-dropdown-item>
                                <b-dropdown-item v-if="partner.status === 'pending'" @click="rejectPartner">Reject</b-dropdown-item>
                                <b-dropdown-item v-if="partner.status === 'approved'" @click="suspendPartner">Suspend</b-dropdown-item>
                                <b-dropdown-item v-if="partner.status === 'suspended'" @click="activatePartner">Activate</b-dropdown-item>
                                <b-dropdown-divider></b-dropdown-divider>
                                <b-dropdown-item @click="editPartner">Edit Partner</b-dropdown-item>
                            </b-dropdown>
                        </div>
                    </div>

                    <!-- Stats Row -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="stat-card">
                                <h3>{{ partner.orders_count || 0 }}</h3>
                                <p>Total Orders</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card">
                                <h3>{{ formatCurrency(partner.total_spent || 0) }}</h3>
                                <p>Total Spent</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card">
                                <h3>{{ partner.inquiries_count || 0 }}</h3>
                                <p>Inquiries</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card">
                                <h3>{{ formatDate(partner.created_at) }}</h3>
                                <p>Registered</p>
                            </div>
                        </div>
                    </div>

                    <!-- Tabs -->
                    <b-tabs content-class="mt-3">
                        <!-- Details Tab -->
                        <b-tab title="Details" active>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="card mb-3">
                                        <div class="card-header"><strong>Business Information</strong></div>
                                        <div class="card-body">
                                            <table class="table table-borderless mb-0">
                                                <tr><td width="40%"><strong>Company Name:</strong></td><td>{{ partner.company_name || partner.business_name }}</td></tr>
                                                <tr><td><strong>Business Type:</strong></td><td>{{ formatBusinessType(partner.business_type) }}</td></tr>
                                                <tr><td><strong>GSTIN:</strong></td><td>{{ partner.gstin || '-' }}</td></tr>
                                                <tr><td><strong>PAN:</strong></td><td>{{ partner.pan || '-' }}</td></tr>
                                                <tr><td><strong>Credit Limit:</strong></td><td>{{ formatCurrency(partner.credit_limit || 0) }}</td></tr>
                                                <tr><td><strong>Payment Terms:</strong></td><td>{{ partner.payment_terms || 'Standard' }}</td></tr>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card mb-3">
                                        <div class="card-header"><strong>Contact Information</strong></div>
                                        <div class="card-body">
                                            <table class="table table-borderless mb-0">
                                                <tr><td width="40%"><strong>Contact Person:</strong></td><td>{{ partner.user?.first_name }} {{ partner.user?.last_name }}</td></tr>
                                                <tr><td><strong>Email:</strong></td><td>{{ partner.user?.email }}</td></tr>
                                                <tr><td><strong>Phone:</strong></td><td>{{ partner.user?.phone }}</td></tr>
                                                <tr><td><strong>Address:</strong></td><td>{{ partner.address }}</td></tr>
                                                <tr><td><strong>City:</strong></td><td>{{ partner.city }}, {{ partner.state }}</td></tr>
                                                <tr><td><strong>Pincode:</strong></td><td>{{ partner.pincode }}</td></tr>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Documents -->
                            <div class="card mb-3" v-if="partner.documents?.length">
                                <div class="card-header"><strong>Documents</strong></div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-4" v-for="doc in partner.documents" :key="doc.id">
                                            <div class="document-card">
                                                <i class="fas fa-file-alt fa-2x mb-2"></i>
                                                <p class="mb-1">{{ doc.type }}</p>
                                                <a :href="doc.url" target="_blank" class="btn btn-sm btn-outline-primary">View</a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Admin Notes -->
                            <div class="card">
                                <div class="card-header"><strong>Admin Notes</strong></div>
                                <div class="card-body">
                                    <b-form-textarea v-model="adminNotes" rows="3" placeholder="Internal notes about this partner..." />
                                    <b-button variant="primary" size="sm" class="mt-2" @click="saveNotes">Save Notes</b-button>
                                </div>
                            </div>
                        </b-tab>

                        <!-- Orders Tab -->
                        <b-tab title="Orders">
                            <b-table responsive="sm" :items="orders" :fields="orderFields" v-if="orders.length > 0">
                                <template #cell(custom_order_code)="row">
                                    <router-link :to="{ name: 'admin.order-detail', params: { id: row.item.id } }">
                                        {{ row.item.custom_order_code }}
                                    </router-link>
                                </template>
                                <template #cell(status)="row">
                                    <b-badge :variant="getOrderStatusVariant(row.item.status)">{{ formatStatus(row.item.status) }}</b-badge>
                                </template>
                                <template #cell(order_total)="row">
                                    {{ formatCurrency(row.item.order_total) }}
                                </template>
                                <template #cell(created_at)="row">
                                    {{ formatDate(row.item.created_at) }}
                                </template>
                            </b-table>
                            <div v-else class="text-center p-4 text-muted">No orders found.</div>
                        </b-tab>

                        <!-- Inquiries Tab -->
                        <b-tab title="Inquiries">
                            <b-table responsive="sm" :items="inquiries" :fields="inquiryFields" v-if="inquiries.length > 0">
                                <template #cell(inquiry_code)="row">
                                    {{ row.item.inquiry_code }}
                                </template>
                                <template #cell(status)="row">
                                    <b-badge :variant="getInquiryStatusVariant(row.item.status)">{{ formatStatus(row.item.status) }}</b-badge>
                                </template>
                                <template #cell(total_items)="row">
                                    {{ row.item.items_count || row.item.items?.length || 0 }}
                                </template>
                                <template #cell(created_at)="row">
                                    {{ formatDate(row.item.created_at) }}
                                </template>
                            </b-table>
                            <div v-else class="text-center p-4 text-muted">No inquiries found.</div>
                        </b-tab>

                        <!-- Activity Log Tab -->
                        <b-tab title="Activity">
                            <div class="timeline" v-if="activities.length > 0">
                                <div class="timeline-item" v-for="activity in activities" :key="activity.id">
                                    <div class="timeline-marker"></div>
                                    <div class="timeline-content">
                                        <p class="mb-1"><strong>{{ activity.description }}</strong></p>
                                        <small class="text-muted">{{ formatDateTime(activity.created_at) }}</small>
                                    </div>
                                </div>
                            </div>
                            <div v-else class="text-center p-4 text-muted">No activity recorded.</div>
                        </b-tab>
                    </b-tabs>
                </div>

                <div v-else class="text-center p-5">
                    <p>Partner not found.</p>
                </div>
            </div>
        </div>

        <!-- Reject Modal -->
        <b-modal v-model="showRejectModal" title="Reject Partner" @ok="confirmReject" ok-title="Reject" ok-variant="danger">
            <b-form-group label="Reason for rejection">
                <b-form-textarea v-model="rejectReason" rows="3" placeholder="Enter reason..." required />
            </b-form-group>
        </b-modal>

        <!-- Suspend Modal -->
        <b-modal v-model="showSuspendModal" title="Suspend Partner" @ok="confirmSuspend" ok-title="Suspend" ok-variant="warning">
            <b-form-group label="Reason for suspension">
                <b-form-textarea v-model="suspendReason" rows="3" placeholder="Enter reason..." required />
            </b-form-group>
        </b-modal>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axiosEmployee from '@axiosEmployee'
import { toast } from 'vue3-toastify'

const route = useRoute()
const router = useRouter()

// State
const loading = ref(true)
const partner = ref(null)
const orders = ref([])
const inquiries = ref([])
const activities = ref([])
const adminNotes = ref('')

const showRejectModal = ref(false)
const showSuspendModal = ref(false)
const rejectReason = ref('')
const suspendReason = ref('')

// Fields
const orderFields = [
    { key: 'custom_order_code', label: 'Order #' },
    { key: 'status', label: 'Status' },
    { key: 'order_total', label: 'Total' },
    { key: 'created_at', label: 'Date' }
]

const inquiryFields = [
    { key: 'inquiry_code', label: 'Inquiry #' },
    { key: 'status', label: 'Status' },
    { key: 'total_items', label: 'Items' },
    { key: 'created_at', label: 'Date' }
]

// Fetch
const fetchPartner = async () => {
    loading.value = true
    try {
        const response = await axiosEmployee.get(`/admin/partners/${route.params.id}`)
        if (response.data.success) {
            partner.value = response.data.data
            adminNotes.value = response.data.data.admin_notes || ''
        }
    } catch (error) {
        toast.error('Failed to load partner')
    } finally {
        loading.value = false
    }
}

const fetchOrders = async () => {
    try {
        const response = await axiosEmployee.get('/admin/orders', {
            params: { partner_id: route.params.id, limit: 10 }
        })
        if (response.data.success) {
            orders.value = response.data.data.data || response.data.data || []
        }
    } catch (error) {
        console.error('Error fetching orders:', error)
    }
}

const fetchInquiries = async () => {
    try {
        const response = await axiosEmployee.get('/admin/partner-inquiries', {
            params: { partner_id: route.params.id, limit: 10 }
        })
        if (response.data.success) {
            inquiries.value = response.data.data.data || response.data.data || []
        }
    } catch (error) {
        console.error('Error fetching inquiries:', error)
    }
}

const fetchActivities = async () => {
    try {
        const response = await axiosEmployee.get(`/admin/partners/${route.params.id}/activities`)
        if (response.data.success) {
            activities.value = response.data.data || []
        }
    } catch (error) {
        console.error('Error fetching activities:', error)
    }
}

// Helpers
const formatStatus = (status) => status?.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase()) || ''
const formatDate = (date) => date ? new Date(date).toLocaleDateString('en-IN') : '-'
const formatDateTime = (date) => date ? new Date(date).toLocaleString('en-IN') : '-'
const formatCurrency = (amount) => '₹' + Number(amount || 0).toLocaleString('en-IN')

const formatBusinessType = (type) => {
    const types = { retailer: 'Retailer', wholesaler: 'Wholesaler', manufacturer: 'Manufacturer', distributor: 'Distributor', jeweler: 'Jeweler', other: 'Other' }
    return types[type] || type || '-'
}

const getStatusVariant = (status) => ({ pending: 'warning', approved: 'success', rejected: 'danger', suspended: 'secondary' }[status] || 'secondary')
const getOrderStatusVariant = (status) => ({ pending: 'warning', processing: 'info', shipped: 'primary', delivered: 'success', cancelled: 'danger' }[status] || 'secondary')
const getInquiryStatusVariant = (status) => ({ pending: 'warning', processing: 'info', quoted: 'primary', accepted: 'success', rejected: 'danger' }[status] || 'secondary')

// Actions
const approvePartner = async () => {
    if (!confirm('Approve this partner?')) return
    try {
        await axiosEmployee.post(`/admin/partners/${partner.value.id}/approve`)
        toast.success('Partner approved')
        fetchPartner()
    } catch (error) {
        toast.error(error.response?.data?.message || 'Failed to approve')
    }
}

const rejectPartner = () => {
    rejectReason.value = ''
    showRejectModal.value = true
}

const confirmReject = async () => {
    if (!rejectReason.value) return toast.error('Please provide a reason')
    try {
        await axiosEmployee.post(`/admin/partners/${partner.value.id}/reject`, { reason: rejectReason.value })
        toast.success('Partner rejected')
        showRejectModal.value = false
        fetchPartner()
    } catch (error) {
        toast.error(error.response?.data?.message || 'Failed to reject')
    }
}

const suspendPartner = () => {
    suspendReason.value = ''
    showSuspendModal.value = true
}

const confirmSuspend = async () => {
    if (!suspendReason.value) return toast.error('Please provide a reason')
    try {
        await axiosEmployee.post(`/admin/partners/${partner.value.id}/suspend`, { reason: suspendReason.value })
        toast.success('Partner suspended')
        showSuspendModal.value = false
        fetchPartner()
    } catch (error) {
        toast.error(error.response?.data?.message || 'Failed to suspend')
    }
}

const activatePartner = async () => {
    if (!confirm('Activate this partner?')) return
    try {
        await axiosEmployee.post(`/admin/partners/${partner.value.id}/activate`)
        toast.success('Partner activated')
        fetchPartner()
    } catch (error) {
        toast.error(error.response?.data?.message || 'Failed to activate')
    }
}

const editPartner = () => {
    router.push({ name: 'admin.edit-partner', params: { id: partner.value.id } })
}

const saveNotes = async () => {
    try {
        await axiosEmployee.put(`/admin/partners/${partner.value.id}`, { admin_notes: adminNotes.value })
        toast.success('Notes saved')
    } catch (error) {
        toast.error('Failed to save notes')
    }
}

// Lifecycle
onMounted(() => {
    fetchPartner()
    fetchOrders()
    fetchInquiries()
    fetchActivities()
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
.stat-card h3 { font-size: 1.5rem; margin-bottom: 5px; color: #333; }
.stat-card p { margin: 0; color: #666; font-size: 0.85rem; }
.document-card {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 15px;
    text-align: center;
}
.timeline { position: relative; padding-left: 30px; }
.timeline-item { position: relative; padding-bottom: 20px; }
.timeline-item:before { content: ''; position: absolute; left: -24px; top: 8px; bottom: -12px; width: 2px; background: #dee2e6; }
.timeline-marker { position: absolute; left: -30px; top: 4px; width: 12px; height: 12px; border-radius: 50%; background: #007bff; border: 2px solid #fff; }
.timeline-content { background: #f8f9fa; padding: 10px 15px; border-radius: 6px; }
</style>
