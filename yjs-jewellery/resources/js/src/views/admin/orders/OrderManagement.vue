<template>
    <div class="listing_screen global_table_liting">
        <div class="masterTabs">
            <div class="masterTabContent">
                <!-- Dashboard Stats -->
                <div class="row mb-4">
                    <div class="col-md-2">
                        <div class="stat-card">
                            <h3>{{ stats.total_orders }}</h3>
                            <p>Total Orders</p>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="stat-card warning">
                            <h3>{{ stats.pending_orders }}</h3>
                            <p>Pending</p>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="stat-card info">
                            <h3>{{ stats.processing_orders }}</h3>
                            <p>Processing</p>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="stat-card primary">
                            <h3>{{ stats.shipped_orders }}</h3>
                            <p>Shipped</p>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="stat-card success">
                            <h3>{{ stats.delivered_orders }}</h3>
                            <p>Delivered</p>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="stat-card danger">
                            <h3>{{ stats.on_hold_orders }}</h3>
                            <p>On Hold</p>
                        </div>
                    </div>
                </div>

                <!-- Filters and Actions -->
                <div class="listing_tab_and_actions mb-3">
                    <div class="listing_actions">
                        <div class="d-flex gap-2">
                            <div class="listing_search">
                                <img src="../../assets/img/header/search.svg" class="listing_search_icon" alt="search" />
                                <b-form-input v-model="search" @input="fetchOrders" placeholder="Search orders..." />
                            </div>
                            <v-select v-model="statusFilter" :options="orderStatuses" placeholder="Status" :clearable="true" class="status-select" />
                            <VueDatePicker v-model="dateRange" range :enable-time-picker="false" placeholder="Date Range" class="date-picker" />
                        </div>
                        <div class="buttonGrid">
                            <b-button class="transBTN" @click="exportOrders">Export</b-button>
                            <b-button class="fillBTN" @click="bulkUpdateStatus" :disabled="selectedOrders.length === 0">
                                Bulk Update ({{ selectedOrders.length }})
                            </b-button>
                        </div>
                    </div>
                </div>

                <!-- Orders Table -->
                <b-table responsive="sm" :items="orders" :fields="orderFields" v-if="orders.length > 0" selectable select-mode="multi" @row-selected="onRowSelected">
                    <template #cell(selected)="{ rowSelected }">
                        <b-form-checkbox :checked="rowSelected" />
                    </template>
                    <template #cell(order_number)="row">
                        <a href="#" @click.prevent="viewOrder(row.item)">{{ row.item.order_number }}</a>
                    </template>
                    <template #cell(customer)="row">
                        <div>
                            <strong>{{ row.item.customer?.name }}</strong>
                            <br><small class="text-muted">{{ row.item.customer?.email }}</small>
                        </div>
                    </template>
                    <template #cell(total)="row">
                        {{ formatCurrency(row.item.order_total) }}
                    </template>
                    <template #cell(payment_status)="row">
                        <b-badge :variant="getPaymentStatusVariant(row.item.payment_status)">{{ row.item.payment_status }}</b-badge>
                    </template>
                    <template #cell(order_status)="row">
                        <b-badge :variant="getOrderStatusVariant(row.item.order_status)">{{ row.item.order_status }}</b-badge>
                        <b-badge v-if="row.item.is_on_hold" variant="danger" class="ms-1">HOLD</b-badge>
                    </template>
                    <template #cell(created_at)="row">
                        {{ formatDate(row.item.created_at) }}
                    </template>
                    <template #cell(actions)="row">
                        <b-dropdown right text="⋮" variant="link" no-caret toggle-class="p-0">
                            <b-dropdown-item @click="viewOrder(row.item)">View Details</b-dropdown-item>
                            <b-dropdown-item @click="updateOrderStatus(row.item)">Update Status</b-dropdown-item>
                            <b-dropdown-item @click="viewTimeline(row.item)">View Timeline</b-dropdown-item>
                            <b-dropdown-divider />
                            <b-dropdown-item v-if="!row.item.is_on_hold" @click="holdOrder(row.item)">Put on Hold</b-dropdown-item>
                            <b-dropdown-item v-else @click="releaseOrder(row.item)">Release Hold</b-dropdown-item>
                            <b-dropdown-item @click="processRefund(row.item)">Process Refund</b-dropdown-item>
                        </b-dropdown>
                    </template>
                </b-table>

                <div v-else-if="!loading" class="text-center p-5">
                    <p>No orders found matching your criteria.</p>
                </div>

                <div v-if="loading" class="text-center p-5">
                    <b-spinner variant="primary" />
                </div>

                <!-- Pagination -->
                <div class="tablecounter" v-if="orders.length > 0">
                    <div class="count">
                        Showing {{ (currentPage - 1) * perPage + 1 }} to {{ Math.min(currentPage * perPage, totalOrders) }} of {{ totalOrders }}
                    </div>
                    <b-pagination v-model="currentPage" :total-rows="totalOrders" :per-page="perPage" @change="fetchOrders" />
                </div>
            </div>
        </div>

        <!-- Order Detail Modal -->
        <b-modal v-model="showOrderModal" size="xl" :title="'Order #' + selectedOrder?.order_number" hide-footer>
            <div v-if="selectedOrder" class="order-detail">
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h6>Customer Information</h6>
                        <p><strong>{{ selectedOrder.customer?.name }}</strong></p>
                        <p>{{ selectedOrder.customer?.email }}</p>
                        <p>{{ selectedOrder.customer?.phone }}</p>
                    </div>
                    <div class="col-md-6">
                        <h6>Shipping Address</h6>
                        <p>{{ selectedOrder.shipping_address }}</p>
                    </div>
                </div>

                <h6>Order Items</h6>
                <b-table :items="selectedOrder.items" :fields="itemFields" small>
                    <template #cell(product)="row">
                        {{ row.item.product?.product_title }}
                    </template>
                    <template #cell(price)="row">
                        {{ formatCurrency(row.item.price) }}
                    </template>
                    <template #cell(total)="row">
                        {{ formatCurrency(row.item.price * row.item.quantity) }}
                    </template>
                </b-table>

                <div class="row mt-4">
                    <div class="col-md-6">
                        <h6>Order Timeline</h6>
                        <div class="timeline" v-if="orderTimeline.length > 0">
                            <div v-for="event in orderTimeline" :key="event.id" class="timeline-item">
                                <div class="timeline-marker"></div>
                                <div class="timeline-content">
                                    <strong>{{ event.title }}</strong>
                                    <p>{{ event.description }}</p>
                                    <small class="text-muted">{{ formatDateTime(event.created_at) }}</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <h6>Order Summary</h6>
                        <div class="order-summary">
                            <div class="d-flex justify-content-between"><span>Subtotal:</span><span>{{ formatCurrency(selectedOrder.order_subtotal) }}</span></div>
                            <div class="d-flex justify-content-between"><span>Tax:</span><span>{{ formatCurrency(selectedOrder.gst_applied) }}</span></div>
                            <div class="d-flex justify-content-between"><span>Shipping:</span><span>{{ formatCurrency(selectedOrder.shipping_charges) }}</span></div>
                            <hr>
                            <div class="d-flex justify-content-between"><strong>Total:</strong><strong>{{ formatCurrency(selectedOrder.order_total) }}</strong></div>
                        </div>
                    </div>
                </div>
            </div>
        </b-modal>

        <!-- Status Update Modal -->
        <b-modal v-model="showStatusModal" title="Update Order Status" hide-footer>
            <b-form @submit.prevent="confirmStatusUpdate">
                <b-form-group label="New Status">
                    <v-select v-model="newStatus" :options="orderStatuses" placeholder="Select status" />
                </b-form-group>
                <b-form-group label="Notes (Optional)">
                    <b-form-textarea v-model="statusNotes" rows="3" />
                </b-form-group>
                <div class="d-flex justify-content-end gap-2">
                    <b-button variant="secondary" @click="showStatusModal = false">Cancel</b-button>
                    <b-button variant="primary" type="submit">Update</b-button>
                </div>
            </b-form>
        </b-modal>
    </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue'
import axiosEmployee from '@axiosEmployee'
import { toast } from 'vue3-toastify'
import moment from 'moment'
import VueDatePicker from '@vuepic/vue-datepicker'

// State
const loading = ref(false)
const orders = ref([])
const totalOrders = ref(0)
const currentPage = ref(1)
const perPage = ref(15)
const search = ref('')
const statusFilter = ref(null)
const dateRange = ref(null)
const selectedOrders = ref([])

const stats = ref({
    total_orders: 0,
    pending_orders: 0,
    processing_orders: 0,
    shipped_orders: 0,
    delivered_orders: 0,
    on_hold_orders: 0
})

const orderStatuses = ['pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled']

const orderFields = [
    { key: 'selected', label: '' },
    { key: 'order_number', label: 'Order #', sortable: true },
    { key: 'customer', label: 'Customer' },
    { key: 'total', label: 'Total', sortable: true },
    { key: 'payment_status', label: 'Payment' },
    { key: 'order_status', label: 'Status' },
    { key: 'created_at', label: 'Date', sortable: true },
    { key: 'actions', label: 'Actions' }
]

const itemFields = [
    { key: 'product', label: 'Product' },
    { key: 'quantity', label: 'Qty' },
    { key: 'price', label: 'Price' },
    { key: 'total', label: 'Total' }
]

// Modals
const showOrderModal = ref(false)
const showStatusModal = ref(false)
const selectedOrder = ref(null)
const orderTimeline = ref([])
const newStatus = ref(null)
const statusNotes = ref('')

// Fetch functions
const fetchStatistics = async () => {
    try {
        const response = await axiosEmployee.get('/admin/orders/statistics')
        if (response.data.success) {
            stats.value = response.data.data
        }
    } catch (error) {
        console.error('Error fetching stats:', error)
    }
}

const fetchOrders = async () => {
    loading.value = true
    try {
        const params = {
            page: currentPage.value,
            per_page: perPage.value,
            search: search.value,
            status: statusFilter.value
        }
        if (dateRange.value) {
            params.start_date = moment(dateRange.value[0]).format('YYYY-MM-DD')
            params.end_date = moment(dateRange.value[1]).format('YYYY-MM-DD')
        }
        const response = await axiosEmployee.get('/admin/orders', { params })
        if (response.data.success) {
            orders.value = response.data.data.data || []
            totalOrders.value = response.data.data.total || 0
        }
    } catch (error) {
        console.error('Error fetching orders:', error)
    } finally {
        loading.value = false
    }
}

const fetchOrderTimeline = async (orderId) => {
    try {
        const response = await axiosEmployee.get(`/admin/orders/${orderId}/timeline`)
        if (response.data.success) {
            orderTimeline.value = response.data.data || []
        }
    } catch (error) {
        console.error('Error fetching timeline:', error)
    }
}

// Helper functions
const formatCurrency = (amount) => new Intl.NumberFormat('en-IN', { style: 'currency', currency: 'INR' }).format(amount || 0)
const formatDate = (date) => moment(date).format('DD MMM YYYY')
const formatDateTime = (date) => moment(date).format('DD MMM YYYY HH:mm')

const getPaymentStatusVariant = (status) => ({ paid: 'success', pending: 'warning', failed: 'danger', refunded: 'info' }[status] || 'secondary')
const getOrderStatusVariant = (status) => ({ pending: 'warning', confirmed: 'info', processing: 'primary', shipped: 'info', delivered: 'success', cancelled: 'danger' }[status] || 'secondary')

// Actions
const onRowSelected = (items) => { selectedOrders.value = items }

const viewOrder = async (order) => {
    selectedOrder.value = order
    showOrderModal.value = true
    await fetchOrderTimeline(order.id)
}

const viewTimeline = async (order) => {
    await viewOrder(order)
}

const updateOrderStatus = (order) => {
    selectedOrder.value = order
    newStatus.value = order.order_status
    statusNotes.value = ''
    showStatusModal.value = true
}

const confirmStatusUpdate = async () => {
    try {
        await axiosEmployee.put(`/admin/orders/${selectedOrder.value.id}/status`, {
            status: newStatus.value,
            notes: statusNotes.value
        })
        toast.success('Order status updated')
        showStatusModal.value = false
        fetchOrders()
        fetchStatistics()
    } catch (error) {
        toast.error(error.response?.data?.message || 'Failed to update status')
    }
}

const holdOrder = async (order) => {
    const reason = prompt('Enter hold reason:')
    if (!reason) return
    try {
        await axiosEmployee.post(`/admin/orders/${order.id}/hold`, { reason })
        toast.success('Order put on hold')
        fetchOrders()
        fetchStatistics()
    } catch (error) {
        toast.error(error.response?.data?.message || 'Failed to hold order')
    }
}

const releaseOrder = async (order) => {
    try {
        await axiosEmployee.post(`/admin/orders/${order.id}/release`)
        toast.success('Order released from hold')
        fetchOrders()
        fetchStatistics()
    } catch (error) {
        toast.error(error.response?.data?.message || 'Failed to release order')
    }
}

const processRefund = (order) => {
    toast.info('Refund processing - implement as needed')
}

const bulkUpdateStatus = () => {
    toast.info('Bulk update - implement as needed')
}

const exportOrders = async () => {
    try {
        const response = await axiosEmployee.get('/admin/orders/export', { responseType: 'blob' })
        const url = window.URL.createObjectURL(new Blob([response.data]))
        const link = document.createElement('a')
        link.href = url
        link.setAttribute('download', `orders-${moment().format('YYYY-MM-DD')}.xlsx`)
        document.body.appendChild(link)
        link.click()
        link.remove()
    } catch (error) {
        toast.error('Failed to export orders')
    }
}

// Watchers
watch([statusFilter, dateRange], () => { currentPage.value = 1; fetchOrders() })

// Lifecycle
onMounted(() => {
    fetchStatistics()
    fetchOrders()
})
</script>

<style scoped>
.stat-card { background: #fff; border-radius: 8px; padding: 15px; text-align: center; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
.stat-card h3 { font-size: 1.5rem; margin-bottom: 5px; }
.stat-card p { margin: 0; color: #666; font-size: 0.85rem; }
.stat-card.warning h3 { color: #ffc107; }
.stat-card.info h3 { color: #17a2b8; }
.stat-card.primary h3 { color: #007bff; }
.stat-card.success h3 { color: #28a745; }
.stat-card.danger h3 { color: #dc3545; }
.status-select { min-width: 150px; }
.date-picker { min-width: 220px; }
.order-summary { background: #f8f9fa; padding: 15px; border-radius: 8px; }
.timeline { position: relative; padding-left: 30px; }
.timeline-item { position: relative; padding-bottom: 20px; }
.timeline-marker { position: absolute; left: -25px; width: 10px; height: 10px; border-radius: 50%; background: #007bff; }
.timeline-content { background: #f8f9fa; padding: 10px; border-radius: 4px; }
</style>
