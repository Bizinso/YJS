<template>
    <div class="returns-page">
        <div class="container py-4">
            <h2 class="mb-4">My Returns</h2>

            <!-- Return Requests List -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Return Requests</h5>
                    <router-link to="/orders" class="btn btn-outline-primary btn-sm">Back to Orders</router-link>
                </div>
                <div class="card-body">
                    <div v-if="returns.length > 0">
                        <div v-for="ret in returns" :key="ret.id" class="return-item border-bottom py-3">
                            <div class="row">
                                <div class="col-md-2">
                                    <strong>{{ ret.return_code }}</strong>
                                    <br><small class="text-muted">{{ formatDate(ret.created_at) }}</small>
                                </div>
                                <div class="col-md-3">
                                    <span>Order: {{ ret.order?.custom_order_code }}</span>
                                    <br><small>{{ ret.items?.length || 0 }} item(s)</small>
                                </div>
                                <div class="col-md-2">
                                    <span class="badge" :class="'bg-' + getStatusVariant(ret.status)">{{ formatStatus(ret.status) }}</span>
                                </div>
                                <div class="col-md-2">
                                    <span v-if="ret.refund_amount">Refund: ₹{{ formatNumber(ret.refund_amount) }}</span>
                                </div>
                                <div class="col-md-3 text-end">
                                    <button class="btn btn-outline-primary btn-sm me-2" @click="viewReturn(ret)">View Details</button>
                                    <button v-if="canCancel(ret)" class="btn btn-outline-danger btn-sm" @click="cancelReturn(ret.id)">Cancel</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div v-else class="text-center py-4">
                        <p class="text-muted">No return requests found.</p>
                    </div>
                </div>
            </div>

            <!-- Return Details Modal -->
            <div class="modal fade" id="returnDetailsModal" tabindex="-1">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content" v-if="selectedReturn">
                        <div class="modal-header">
                            <h5 class="modal-title">Return: {{ selectedReturn.return_code }}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <strong>Status:</strong>
                                    <span class="badge ms-2" :class="'bg-' + getStatusVariant(selectedReturn.status)">{{ formatStatus(selectedReturn.status) }}</span>
                                </div>
                                <div class="col-md-4">
                                    <strong>Order:</strong> {{ selectedReturn.order?.custom_order_code }}
                                </div>
                                <div class="col-md-4">
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
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Quantity</th>
                                        <th>Reason</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="item in selectedReturn.items" :key="item.id">
                                        <td>{{ item.product?.name || 'Product' }}</td>
                                        <td>{{ item.quantity }}</td>
                                        <td>{{ item.reason }}</td>
                                        <td><span class="badge bg-secondary">{{ item.status }}</span></td>
                                    </tr>
                                </tbody>
                            </table>
                            <hr v-if="selectedReturn.refund_amount">
                            <div class="row" v-if="selectedReturn.refund_amount">
                                <div class="col-md-6">
                                    <strong>Refund Amount:</strong> ₹{{ formatNumber(selectedReturn.refund_amount) }}
                                </div>
                                <div class="col-md-6">
                                    <strong>Refund Status:</strong> {{ selectedReturn.refund_status || 'Pending' }}
                                </div>
                            </div>
                            <hr v-if="selectedReturn.tracking">
                            <div v-if="selectedReturn.tracking">
                                <h6>Tracking</h6>
                                <div v-for="(event, idx) in selectedReturn.tracking" :key="idx" class="tracking-event mb-2">
                                    <small class="text-muted">{{ formatDate(event.timestamp) }}</small>
                                    <p class="mb-0">{{ event.status }} - {{ event.description }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axiosCustomer from '@axiosCustomer'
import { toast } from 'vue3-toastify'
import { Modal } from 'bootstrap'

// State
const returns = ref([])
const selectedReturn = ref(null)
let detailsModal = null

// Fetch functions
const fetchReturns = async () => {
    try {
        const response = await axiosCustomer.get('/customer/returns')
        if (response.data.success) {
            returns.value = response.data.data.data || response.data.data || []
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
    pending: 'warning', approved: 'info', pickup_scheduled: 'info',
    picked_up: 'primary', received: 'primary', inspecting: 'info',
    refund_initiated: 'success', completed: 'success',
    rejected: 'danger', cancelled: 'secondary'
}[status] || 'secondary')

const canCancel = (ret) => ['pending', 'approved'].includes(ret.status)

// Actions
const viewReturn = async (ret) => {
    try {
        const response = await axiosCustomer.get(`/customer/returns/${ret.id}`)
        if (response.data.success) {
            selectedReturn.value = response.data.data
            if (!detailsModal) {
                detailsModal = new Modal(document.getElementById('returnDetailsModal'))
            }
            detailsModal.show()
        }
    } catch (error) {
        toast.error('Failed to load return details')
    }
}

const cancelReturn = async (id) => {
    if (!confirm('Are you sure you want to cancel this return request?')) return
    try {
        await axiosCustomer.post(`/customer/returns/${id}/cancel`)
        toast.success('Return request cancelled')
        fetchReturns()
    } catch (error) {
        toast.error(error.response?.data?.message || 'Failed to cancel return')
    }
}

// Lifecycle
onMounted(() => {
    fetchReturns()
})
</script>

<style scoped>
.returns-page { min-height: 60vh; }
.return-item:last-child { border-bottom: none !important; }
.tracking-event { padding-left: 15px; border-left: 2px solid #007bff; }
</style>
