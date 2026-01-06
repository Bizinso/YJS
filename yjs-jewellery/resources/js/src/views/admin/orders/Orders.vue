<script setup>
import { ref, onMounted, computed, watch } from 'vue'
import axios from 'axios'

const loading = ref(true)
const orders = ref([])
const pagination = ref({
    current_page: 1,
    last_page: 1,
    per_page: 20,
    total: 0
})

const filters = ref({
    search: '',
    status: '',
    payment_status: '',
    date_from: '',
    date_to: ''
})

const selectedOrder = ref(null)
const showModal = ref(false)
const showStatusModal = ref(false)
const newStatus = ref('')
const statusNote = ref('')

const statusOptions = [
    { value: '', label: 'All Status' },
    { value: 'pending', label: 'Pending' },
    { value: 'processing', label: 'Processing' },
    { value: 'shipped', label: 'Shipped' },
    { value: 'delivered', label: 'Delivered' },
    { value: 'cancelled', label: 'Cancelled' },
    { value: 'returned', label: 'Returned' }
]

const paymentStatusOptions = [
    { value: '', label: 'All Payment Status' },
    { value: 'pending', label: 'Pending' },
    { value: 'paid', label: 'Paid' },
    { value: 'failed', label: 'Failed' },
    { value: 'refunded', label: 'Refunded' }
]

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('en-IN', {
        style: 'currency',
        currency: 'INR',
        maximumFractionDigits: 0
    }).format(amount || 0)
}

const formatDate = (date) => {
    if (!date) return '-'
    return new Date(date).toLocaleDateString('en-IN', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    })
}

const getStatusClass = (status) => {
    const classes = {
        pending: 'bg-yellow-100 text-yellow-800',
        processing: 'bg-blue-100 text-blue-800',
        shipped: 'bg-purple-100 text-purple-800',
        delivered: 'bg-green-100 text-green-800',
        cancelled: 'bg-red-100 text-red-800',
        returned: 'bg-gray-100 text-gray-800'
    }
    return classes[status] || 'bg-gray-100 text-gray-800'
}

const getPaymentStatusClass = (status) => {
    const classes = {
        pending: 'bg-yellow-100 text-yellow-800',
        paid: 'bg-green-100 text-green-800',
        failed: 'bg-red-100 text-red-800',
        refunded: 'bg-purple-100 text-purple-800'
    }
    return classes[status] || 'bg-gray-100 text-gray-800'
}

const fetchOrders = async (page = 1) => {
    loading.value = true
    try {
        const params = {
            page,
            per_page: pagination.value.per_page,
            ...filters.value
        }

        Object.keys(params).forEach(key => {
            if (!params[key]) delete params[key]
        })

        const response = await axios.get('/api/admin/orders', { params })

        if (response.data.success) {
            orders.value = response.data.data.data || response.data.data || []
            if (response.data.data.meta) {
                pagination.value = {
                    current_page: response.data.data.meta.current_page,
                    last_page: response.data.data.meta.last_page,
                    per_page: response.data.data.meta.per_page,
                    total: response.data.data.meta.total
                }
            } else if (response.data.data.current_page) {
                pagination.value = {
                    current_page: response.data.data.current_page,
                    last_page: response.data.data.last_page,
                    per_page: response.data.data.per_page,
                    total: response.data.data.total
                }
            }
        }
    } catch (error) {
        console.error('Error fetching orders:', error)
    } finally {
        loading.value = false
    }
}

const viewOrder = async (order) => {
    try {
        const response = await axios.get(`/api/admin/orders/${order.id}`)
        if (response.data.success) {
            selectedOrder.value = response.data.data
            showModal.value = true
        }
    } catch (error) {
        console.error('Error fetching order details:', error)
    }
}

const openStatusModal = (order) => {
    selectedOrder.value = order
    newStatus.value = order.status
    statusNote.value = ''
    showStatusModal.value = true
}

const updateStatus = async () => {
    if (!selectedOrder.value || !newStatus.value) return

    try {
        const response = await axios.put(`/api/admin/orders/${selectedOrder.value.id}/status`, {
            status: newStatus.value,
            note: statusNote.value
        })

        if (response.data.success) {
            showStatusModal.value = false
            fetchOrders(pagination.value.current_page)
        }
    } catch (error) {
        console.error('Error updating status:', error)
        alert(error.response?.data?.message || 'Failed to update status')
    }
}

const exportOrders = async () => {
    try {
        const response = await axios.get('/api/admin/orders/export', {
            params: filters.value,
            responseType: 'blob'
        })

        const url = window.URL.createObjectURL(new Blob([response.data]))
        const link = document.createElement('a')
        link.href = url
        link.setAttribute('download', `orders-${new Date().toISOString().split('T')[0]}.csv`)
        document.body.appendChild(link)
        link.click()
        link.remove()
    } catch (error) {
        console.error('Error exporting orders:', error)
    }
}

const downloadInvoice = async (order) => {
    try {
        const response = await axios.get(`/api/admin/orders/${order.id}/invoice`, {
            responseType: 'blob'
        })

        const url = window.URL.createObjectURL(new Blob([response.data]))
        const link = document.createElement('a')
        link.href = url
        link.setAttribute('download', `invoice-${order.order_code || order.id}.pdf`)
        document.body.appendChild(link)
        link.click()
        link.remove()
    } catch (error) {
        console.error('Error downloading invoice:', error)
    }
}

const resetFilters = () => {
    filters.value = {
        search: '',
        status: '',
        payment_status: '',
        date_from: '',
        date_to: ''
    }
    fetchOrders(1)
}

let searchTimeout = null
watch(() => filters.value.search, (val) => {
    clearTimeout(searchTimeout)
    searchTimeout = setTimeout(() => fetchOrders(1), 500)
})

onMounted(() => {
    fetchOrders()
})
</script>

<template>
    <div class="p-6">
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Orders</h1>
                <p class="text-gray-600">Manage customer orders</p>
            </div>
            <button
                @click="exportOrders"
                class="px-4 py-2 bg-amber-600 text-white rounded-lg hover:bg-amber-700 flex items-center space-x-2"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <span>Export</span>
            </button>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-lg shadow-sm p-4 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                <div>
                    <input
                        v-model="filters.search"
                        type="text"
                        placeholder="Search order code, customer..."
                        class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-amber-500"
                    >
                </div>
                <div>
                    <select
                        v-model="filters.status"
                        @change="fetchOrders(1)"
                        class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-amber-500"
                    >
                        <option v-for="option in statusOptions" :key="option.value" :value="option.value">
                            {{ option.label }}
                        </option>
                    </select>
                </div>
                <div>
                    <select
                        v-model="filters.payment_status"
                        @change="fetchOrders(1)"
                        class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-amber-500"
                    >
                        <option v-for="option in paymentStatusOptions" :key="option.value" :value="option.value">
                            {{ option.label }}
                        </option>
                    </select>
                </div>
                <div>
                    <input
                        v-model="filters.date_from"
                        type="date"
                        @change="fetchOrders(1)"
                        class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-amber-500"
                    >
                </div>
                <div>
                    <input
                        v-model="filters.date_to"
                        type="date"
                        @change="fetchOrders(1)"
                        class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-amber-500"
                    >
                </div>
            </div>
            <div class="mt-4 flex justify-end">
                <button
                    @click="resetFilters"
                    class="px-4 py-2 text-gray-600 hover:text-gray-800"
                >
                    Reset Filters
                </button>
            </div>
        </div>

        <!-- Orders Table -->
        <div class="bg-white rounded-lg shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Order</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Customer</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Payment</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr v-if="loading">
                            <td colspan="7" class="px-6 py-8 text-center">
                                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-amber-600 mx-auto"></div>
                            </td>
                        </tr>
                        <tr v-else-if="orders.length === 0">
                            <td colspan="7" class="px-6 py-8 text-center text-gray-500">
                                No orders found
                            </td>
                        </tr>
                        <tr v-else v-for="order in orders" :key="order.id" class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="font-medium text-gray-900">#{{ order.order_code || order.custom_order_code }}</div>
                                <div class="text-sm text-gray-500">{{ order.items_count || order.order_products?.length || 0 }} items</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">
                                    {{ order.customer?.first_name }} {{ order.customer?.last_name }}
                                </div>
                                <div class="text-sm text-gray-500">{{ order.customer?.phone }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ formatDate(order.created_at) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">{{ formatCurrency(order.order_total) }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span :class="['px-2 py-1 text-xs rounded-full', getPaymentStatusClass(order.payment_status)]">
                                    {{ order.payment_status }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span :class="['px-2 py-1 text-xs rounded-full', getStatusClass(order.status)]">
                                    {{ order.status }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex items-center justify-end space-x-2">
                                    <button
                                        @click="viewOrder(order)"
                                        class="text-amber-600 hover:text-amber-900"
                                        title="View"
                                    >
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                    </button>
                                    <button
                                        @click="openStatusModal(order)"
                                        class="text-blue-600 hover:text-blue-900"
                                        title="Update Status"
                                    >
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </button>
                                    <button
                                        @click="downloadInvoice(order)"
                                        class="text-green-600 hover:text-green-900"
                                        title="Download Invoice"
                                    >
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
                <div class="flex items-center justify-between">
                    <div class="text-sm text-gray-700">
                        Showing {{ (pagination.current_page - 1) * pagination.per_page + 1 }} to
                        {{ Math.min(pagination.current_page * pagination.per_page, pagination.total) }} of
                        {{ pagination.total }} results
                    </div>
                    <div class="flex items-center space-x-2">
                        <button
                            :disabled="pagination.current_page === 1"
                            @click="fetchOrders(pagination.current_page - 1)"
                            class="px-3 py-1 border rounded-md text-sm disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50"
                        >
                            Previous
                        </button>
                        <span class="px-3 py-1 text-sm">
                            Page {{ pagination.current_page }} of {{ pagination.last_page }}
                        </span>
                        <button
                            :disabled="pagination.current_page === pagination.last_page"
                            @click="fetchOrders(pagination.current_page + 1)"
                            class="px-3 py-1 border rounded-md text-sm disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50"
                        >
                            Next
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Order Detail Modal -->
        <div v-if="showModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg shadow-xl w-full max-w-4xl max-h-[90vh] overflow-y-auto">
                <div class="p-6 border-b flex items-center justify-between">
                    <h2 class="text-xl font-bold">Order #{{ selectedOrder?.order_code || selectedOrder?.custom_order_code }}</h2>
                    <button @click="showModal = false" class="text-gray-500 hover:text-gray-700">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                <div v-if="selectedOrder" class="p-6">
                    <div class="grid grid-cols-2 gap-6 mb-6">
                        <div>
                            <h3 class="font-semibold text-gray-700 mb-2">Customer Details</h3>
                            <p>{{ selectedOrder.customer?.first_name }} {{ selectedOrder.customer?.last_name }}</p>
                            <p class="text-gray-500">{{ selectedOrder.customer?.email }}</p>
                            <p class="text-gray-500">{{ selectedOrder.customer?.phone }}</p>
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-700 mb-2">Shipping Address</h3>
                            <p>{{ selectedOrder.shipping_address?.address_line_1 }}</p>
                            <p class="text-gray-500">
                                {{ selectedOrder.shipping_address?.city }}, {{ selectedOrder.shipping_address?.state }}
                            </p>
                            <p class="text-gray-500">{{ selectedOrder.shipping_address?.pincode }}</p>
                        </div>
                    </div>

                    <div class="mb-6">
                        <h3 class="font-semibold text-gray-700 mb-2">Order Items</h3>
                        <table class="w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2 text-left text-sm">Product</th>
                                    <th class="px-4 py-2 text-center text-sm">Qty</th>
                                    <th class="px-4 py-2 text-right text-sm">Price</th>
                                    <th class="px-4 py-2 text-right text-sm">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="item in selectedOrder.order_products" :key="item.id" class="border-b">
                                    <td class="px-4 py-2">
                                        <div class="flex items-center space-x-3">
                                            <img
                                                :src="item.product?.main_image || '/images/placeholder.png'"
                                                :alt="item.product?.name"
                                                class="w-12 h-12 rounded object-cover"
                                            >
                                            <div>
                                                <p class="font-medium">{{ item.product?.name }}</p>
                                                <p class="text-sm text-gray-500">SKU: {{ item.product?.sku }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-2 text-center">{{ item.quantity }}</td>
                                    <td class="px-4 py-2 text-right">{{ formatCurrency(item.price) }}</td>
                                    <td class="px-4 py-2 text-right">{{ formatCurrency(item.total) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="flex justify-end">
                        <div class="w-64">
                            <div class="flex justify-between py-2">
                                <span class="text-gray-600">Subtotal</span>
                                <span>{{ formatCurrency(selectedOrder.order_subtotal) }}</span>
                            </div>
                            <div v-if="selectedOrder.discount_amount" class="flex justify-between py-2">
                                <span class="text-gray-600">Discount</span>
                                <span class="text-green-600">-{{ formatCurrency(selectedOrder.discount_amount) }}</span>
                            </div>
                            <div class="flex justify-between py-2">
                                <span class="text-gray-600">Shipping</span>
                                <span>{{ formatCurrency(selectedOrder.shipping_charges) }}</span>
                            </div>
                            <div class="flex justify-between py-2">
                                <span class="text-gray-600">Tax</span>
                                <span>{{ formatCurrency(selectedOrder.total_taxes) }}</span>
                            </div>
                            <div class="flex justify-between py-2 border-t font-bold">
                                <span>Total</span>
                                <span>{{ formatCurrency(selectedOrder.order_total) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Status Update Modal -->
        <div v-if="showStatusModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg shadow-xl w-full max-w-md">
                <div class="p-6 border-b">
                    <h2 class="text-xl font-bold">Update Order Status</h2>
                </div>
                <div class="p-6">
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">New Status</label>
                        <select
                            v-model="newStatus"
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-amber-500"
                        >
                            <option v-for="option in statusOptions.filter(o => o.value)" :key="option.value" :value="option.value">
                                {{ option.label }}
                            </option>
                        </select>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Note (Optional)</label>
                        <textarea
                            v-model="statusNote"
                            rows="3"
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-amber-500"
                            placeholder="Add a note about this status change..."
                        ></textarea>
                    </div>
                    <div class="flex justify-end space-x-3">
                        <button
                            @click="showStatusModal = false"
                            class="px-4 py-2 border rounded-lg hover:bg-gray-50"
                        >
                            Cancel
                        </button>
                        <button
                            @click="updateStatus"
                            class="px-4 py-2 bg-amber-600 text-white rounded-lg hover:bg-amber-700"
                        >
                            Update Status
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
