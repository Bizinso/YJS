<template>
    <div class="orders-page">
        <div class="container">
            <div class="page-header">
                <h1>My Orders</h1>
                <p>Track and manage your orders</p>
            </div>

            <!-- Filters -->
            <div class="filters-section">
                <div class="search-box">
                    <i class="bi-search"></i>
                    <input type="text" v-model="filters.search" placeholder="Search by order number or product" @input="debounceSearch" />
                </div>
                <div class="filter-group">
                    <select v-model="filters.status" @change="fetchOrders">
                        <option value="">All Status</option>
                        <option value="pending">Pending</option>
                        <option value="confirmed">Confirmed</option>
                        <option value="processing">Processing</option>
                        <option value="shipped">Shipped</option>
                        <option value="delivered">Delivered</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
                <div class="filter-group">
                    <select v-model="filters.period" @change="fetchOrders">
                        <option value="">All Time</option>
                        <option value="30">Last 30 Days</option>
                        <option value="90">Last 3 Months</option>
                        <option value="180">Last 6 Months</option>
                        <option value="365">Last Year</option>
                    </select>
                </div>
            </div>

            <!-- Orders List -->
            <div class="orders-container">
                <div v-if="loading" class="loading-state">
                    <div class="spinner"></div>
                    <p>Loading orders...</p>
                </div>

                <div v-else-if="orders.length === 0" class="empty-state">
                    <i class="bi-bag-x"></i>
                    <h3>No orders found</h3>
                    <p>You haven't placed any orders yet.</p>
                    <router-link to="/products" class="btn-primary">Start Shopping</router-link>
                </div>

                <div v-else class="orders-list">
                    <div v-for="order in orders" :key="order.id" class="order-card">
                        <div class="order-header">
                            <div class="order-info">
                                <h3>Order #{{ order.order_number }}</h3>
                                <span class="order-date">Placed on {{ formatDate(order.created_at) }}</span>
                            </div>
                            <div class="order-status">
                                <span :class="['status-badge', order.status]">{{ formatStatus(order.status) }}</span>
                            </div>
                        </div>

                        <div class="order-items">
                            <div v-for="item in order.items.slice(0, 3)" :key="item.id" class="order-item">
                                <div class="item-image">
                                    <img :src="item.product?.image || '/images/placeholder.png'" :alt="item.product?.name" />
                                </div>
                                <div class="item-details">
                                    <h4>{{ item.product?.name }}</h4>
                                    <p class="item-variant" v-if="item.variant">{{ item.variant }}</p>
                                    <p class="item-qty">Qty: {{ item.quantity }}</p>
                                </div>
                                <div class="item-price">
                                    ₹{{ formatPrice(item.price * item.quantity) }}
                                </div>
                            </div>
                            <div v-if="order.items.length > 3" class="more-items">
                                +{{ order.items.length - 3 }} more items
                            </div>
                        </div>

                        <div class="order-footer">
                            <div class="order-total">
                                <span>Total:</span>
                                <strong>₹{{ formatPrice(order.total) }}</strong>
                            </div>
                            <div class="order-actions">
                                <router-link :to="`/orders/${order.id}`" class="btn-outline">View Details</router-link>
                                <button v-if="order.status === 'delivered'" class="btn-outline" @click="openReorder(order)">
                                    Reorder
                                </button>
                                <button v-if="canCancel(order)" class="btn-text danger" @click="cancelOrder(order)">
                                    Cancel Order
                                </button>
                            </div>
                        </div>

                        <!-- Tracking Info -->
                        <div v-if="order.tracking_number && order.status === 'shipped'" class="tracking-info">
                            <i class="bi-truck"></i>
                            <span>Tracking: {{ order.tracking_number }}</span>
                            <a :href="getTrackingUrl(order)" target="_blank" class="track-link">Track Shipment</a>
                        </div>
                    </div>
                </div>

                <!-- Pagination -->
                <div v-if="pagination.total > pagination.perPage" class="pagination">
                    <button :disabled="pagination.currentPage === 1" @click="changePage(pagination.currentPage - 1)">
                        <i class="bi-chevron-left"></i>
                    </button>
                    <span>Page {{ pagination.currentPage }} of {{ pagination.lastPage }}</span>
                    <button :disabled="pagination.currentPage === pagination.lastPage" @click="changePage(pagination.currentPage + 1)">
                        <i class="bi-chevron-right"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'

const orders = ref([])
const loading = ref(true)
let searchTimeout = null

const filters = ref({
    search: '',
    status: '',
    period: ''
})

const pagination = ref({
    currentPage: 1,
    lastPage: 1,
    perPage: 10,
    total: 0
})

const getAuthHeaders = () => {
    const token = localStorage.getItem('token')
    return { Authorization: `Bearer ${token}` }
}

const fetchOrders = async () => {
    loading.value = true
    try {
        const params = {
            page: pagination.value.currentPage,
            per_page: pagination.value.perPage,
            ...filters.value
        }
        const response = await axios.get('/api/customer/orders', {
            headers: getAuthHeaders(),
            params
        })
        orders.value = response.data.data || []
        pagination.value = {
            currentPage: response.data.current_page || 1,
            lastPage: response.data.last_page || 1,
            perPage: response.data.per_page || 10,
            total: response.data.total || 0
        }
    } catch (error) {
        console.error('Failed to fetch orders:', error)
    } finally {
        loading.value = false
    }
}

const debounceSearch = () => {
    clearTimeout(searchTimeout)
    searchTimeout = setTimeout(() => {
        pagination.value.currentPage = 1
        fetchOrders()
    }, 500)
}

const changePage = (page) => {
    pagination.value.currentPage = page
    fetchOrders()
}

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('en-IN', {
        day: 'numeric',
        month: 'short',
        year: 'numeric'
    })
}

const formatPrice = (price) => {
    return parseFloat(price).toLocaleString('en-IN')
}

const formatStatus = (status) => {
    const statusMap = {
        pending: 'Pending',
        confirmed: 'Confirmed',
        processing: 'Processing',
        shipped: 'Shipped',
        delivered: 'Delivered',
        cancelled: 'Cancelled'
    }
    return statusMap[status] || status
}

const canCancel = (order) => {
    return ['pending', 'confirmed'].includes(order.status)
}

const cancelOrder = async (order) => {
    if (!confirm('Are you sure you want to cancel this order?')) return

    try {
        await axios.post(`/api/customer/orders/${order.id}/cancel`, {}, {
            headers: getAuthHeaders()
        })
        order.status = 'cancelled'
    } catch (error) {
        alert(error.response?.data?.message || 'Failed to cancel order')
    }
}

const openReorder = (order) => {
    // Add items to cart logic
    alert('Reorder functionality - Add items to cart')
}

const getTrackingUrl = (order) => {
    // Shiprocket or carrier tracking URL
    return `https://www.shiprocket.in/tracking/${order.tracking_number}`
}

onMounted(() => {
    fetchOrders()
})
</script>

<style scoped>
.orders-page {
    min-height: 100vh;
    background: #f8f9fa;
    padding: 30px 0;
}

.container {
    max-width: 900px;
    margin: 0 auto;
    padding: 0 20px;
}

.page-header {
    margin-bottom: 30px;
}

.page-header h1 {
    font-size: 28px;
    color: #1a1a2e;
    margin-bottom: 8px;
}

.page-header p {
    color: #666;
}

.filters-section {
    display: flex;
    gap: 15px;
    margin-bottom: 25px;
    flex-wrap: wrap;
}

.search-box {
    flex: 1;
    min-width: 250px;
    position: relative;
}

.search-box i {
    position: absolute;
    left: 15px;
    top: 50%;
    transform: translateY(-50%);
    color: #999;
}

.search-box input {
    width: 100%;
    padding: 12px 15px 12px 45px;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    font-size: 15px;
}

.search-box input:focus {
    outline: none;
    border-color: #b8860b;
}

.filter-group select {
    padding: 12px 35px 12px 15px;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    font-size: 15px;
    background: #fff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='%23666' viewBox='0 0 16 16'%3E%3Cpath d='M8 11L3 6h10l-5 5z'/%3E%3C/svg%3E") no-repeat right 12px center;
    appearance: none;
    cursor: pointer;
}

.filter-group select:focus {
    outline: none;
    border-color: #b8860b;
}

.loading-state {
    text-align: center;
    padding: 60px 20px;
}

.spinner {
    width: 40px;
    height: 40px;
    border: 3px solid #e0e0e0;
    border-top-color: #b8860b;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
    margin: 0 auto 15px;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

.empty-state {
    text-align: center;
    padding: 60px 20px;
    background: #fff;
    border-radius: 12px;
}

.empty-state i {
    font-size: 64px;
    color: #ddd;
    margin-bottom: 20px;
}

.empty-state h3 {
    color: #333;
    margin-bottom: 10px;
}

.empty-state p {
    color: #666;
    margin-bottom: 20px;
}

.orders-list {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.order-card {
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    overflow: hidden;
}

.order-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px;
    border-bottom: 1px solid #eee;
}

.order-info h3 {
    font-size: 16px;
    color: #1a1a2e;
    margin-bottom: 4px;
}

.order-date {
    font-size: 13px;
    color: #666;
}

.status-badge {
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 13px;
    font-weight: 500;
}

.status-badge.pending { background: #fff3cd; color: #856404; }
.status-badge.confirmed { background: #cce5ff; color: #004085; }
.status-badge.processing { background: #d4edda; color: #155724; }
.status-badge.shipped { background: #d1ecf1; color: #0c5460; }
.status-badge.delivered { background: #d4edda; color: #155724; }
.status-badge.cancelled { background: #f8d7da; color: #721c24; }

.order-items {
    padding: 20px;
    border-bottom: 1px solid #eee;
}

.order-item {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 10px 0;
}

.order-item:not(:last-child) {
    border-bottom: 1px solid #f0f0f0;
}

.item-image {
    width: 70px;
    height: 70px;
    border-radius: 8px;
    overflow: hidden;
    flex-shrink: 0;
}

.item-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.item-details {
    flex: 1;
}

.item-details h4 {
    font-size: 15px;
    color: #333;
    margin-bottom: 4px;
}

.item-variant {
    font-size: 13px;
    color: #666;
}

.item-qty {
    font-size: 13px;
    color: #999;
}

.item-price {
    font-weight: 600;
    color: #1a1a2e;
}

.more-items {
    text-align: center;
    padding: 10px;
    color: #666;
    font-size: 14px;
}

.order-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px;
}

.order-total span {
    color: #666;
    margin-right: 8px;
}

.order-total strong {
    font-size: 18px;
    color: #1a1a2e;
}

.order-actions {
    display: flex;
    gap: 10px;
}

.btn-outline {
    padding: 10px 20px;
    border: 2px solid #b8860b;
    background: transparent;
    color: #b8860b;
    border-radius: 8px;
    font-weight: 500;
    cursor: pointer;
    text-decoration: none;
    transition: all 0.3s;
}

.btn-outline:hover {
    background: #b8860b;
    color: #fff;
}

.btn-text {
    padding: 10px 20px;
    background: transparent;
    border: none;
    color: #666;
    font-weight: 500;
    cursor: pointer;
}

.btn-text.danger {
    color: #dc3545;
}

.btn-text.danger:hover {
    text-decoration: underline;
}

.tracking-info {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 15px 20px;
    background: #f0f8ff;
    border-top: 1px solid #cce5ff;
    font-size: 14px;
    color: #0c5460;
}

.tracking-info i {
    font-size: 18px;
}

.track-link {
    margin-left: auto;
    color: #007bff;
    text-decoration: none;
    font-weight: 500;
}

.track-link:hover {
    text-decoration: underline;
}

.pagination {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 20px;
    margin-top: 30px;
}

.pagination button {
    width: 40px;
    height: 40px;
    border: 2px solid #e0e0e0;
    background: #fff;
    border-radius: 8px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
}

.pagination button:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.pagination button:not(:disabled):hover {
    border-color: #b8860b;
    color: #b8860b;
}

.pagination span {
    color: #666;
}

.btn-primary {
    display: inline-block;
    padding: 12px 24px;
    background: linear-gradient(135deg, #b8860b 0%, #daa520 100%);
    color: #fff;
    border: none;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 600;
    cursor: pointer;
}

@media (max-width: 768px) {
    .filters-section {
        flex-direction: column;
    }

    .search-box {
        min-width: 100%;
    }

    .order-footer {
        flex-direction: column;
        gap: 15px;
    }

    .order-actions {
        width: 100%;
        justify-content: center;
    }
}
</style>
