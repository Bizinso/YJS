<template>
    <div class="order-details-page">
        <div class="container">
            <!-- Back Button -->
            <router-link to="/orders" class="back-link">
                <i class="bi-arrow-left"></i> Back to Orders
            </router-link>

            <div v-if="loading" class="loading-state">
                <div class="spinner"></div>
                <p>Loading order details...</p>
            </div>

            <div v-else-if="!order" class="error-state">
                <i class="bi-exclamation-circle"></i>
                <h3>Order not found</h3>
                <router-link to="/orders" class="btn-primary">View All Orders</router-link>
            </div>

            <div v-else class="order-content">
                <!-- Order Header -->
                <div class="order-header">
                    <div class="header-info">
                        <h1>Order #{{ order.order_number }}</h1>
                        <p>Placed on {{ formatDate(order.created_at) }}</p>
                    </div>
                    <span :class="['status-badge', order.status]">{{ formatStatus(order.status) }}</span>
                </div>

                <!-- Order Timeline -->
                <div class="order-timeline" v-if="order.status !== 'cancelled'">
                    <div class="timeline-step" :class="{ active: isStepActive('confirmed'), completed: isStepCompleted('confirmed') }">
                        <div class="step-icon"><i class="bi-check-lg"></i></div>
                        <span>Confirmed</span>
                    </div>
                    <div class="timeline-line" :class="{ active: isStepCompleted('confirmed') }"></div>
                    <div class="timeline-step" :class="{ active: isStepActive('processing'), completed: isStepCompleted('processing') }">
                        <div class="step-icon"><i class="bi-gear"></i></div>
                        <span>Processing</span>
                    </div>
                    <div class="timeline-line" :class="{ active: isStepCompleted('processing') }"></div>
                    <div class="timeline-step" :class="{ active: isStepActive('shipped'), completed: isStepCompleted('shipped') }">
                        <div class="step-icon"><i class="bi-truck"></i></div>
                        <span>Shipped</span>
                    </div>
                    <div class="timeline-line" :class="{ active: isStepCompleted('shipped') }"></div>
                    <div class="timeline-step" :class="{ active: isStepActive('delivered'), completed: isStepCompleted('delivered') }">
                        <div class="step-icon"><i class="bi-house-check"></i></div>
                        <span>Delivered</span>
                    </div>
                </div>

                <div class="order-grid">
                    <!-- Order Items -->
                    <div class="order-card items-card">
                        <h2>Order Items</h2>
                        <div class="items-list">
                            <div v-for="item in order.items" :key="item.id" class="order-item">
                                <div class="item-image">
                                    <img :src="item.product?.image || '/images/placeholder.png'" :alt="item.product?.name" />
                                </div>
                                <div class="item-details">
                                    <h4>{{ item.product?.name }}</h4>
                                    <p class="item-sku" v-if="item.product?.sku">SKU: {{ item.product?.sku }}</p>
                                    <p class="item-variant" v-if="item.variant">{{ item.variant }}</p>
                                    <div class="item-meta">
                                        <span>Qty: {{ item.quantity }}</span>
                                        <span>₹{{ formatPrice(item.price) }} each</span>
                                    </div>
                                </div>
                                <div class="item-total">
                                    ₹{{ formatPrice(item.price * item.quantity) }}
                                </div>
                            </div>
                        </div>

                        <!-- Price Summary -->
                        <div class="price-summary">
                            <div class="summary-row">
                                <span>Subtotal</span>
                                <span>₹{{ formatPrice(order.subtotal) }}</span>
                            </div>
                            <div class="summary-row" v-if="order.discount > 0">
                                <span>Discount</span>
                                <span class="discount">-₹{{ formatPrice(order.discount) }}</span>
                            </div>
                            <div class="summary-row" v-if="order.tax > 0">
                                <span>Tax ({{ order.tax_rate || 18 }}%)</span>
                                <span>₹{{ formatPrice(order.tax) }}</span>
                            </div>
                            <div class="summary-row">
                                <span>Shipping</span>
                                <span>{{ order.shipping_cost > 0 ? '₹' + formatPrice(order.shipping_cost) : 'Free' }}</span>
                            </div>
                            <div class="summary-row total">
                                <span>Total</span>
                                <span>₹{{ formatPrice(order.total) }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Order Info Cards -->
                    <div class="info-column">
                        <!-- Shipping Address -->
                        <div class="order-card">
                            <h3>Shipping Address</h3>
                            <div class="address-info">
                                <p class="name">{{ order.shipping_address?.name }}</p>
                                <p>{{ order.shipping_address?.address_line_1 }}</p>
                                <p v-if="order.shipping_address?.address_line_2">{{ order.shipping_address?.address_line_2 }}</p>
                                <p>{{ order.shipping_address?.city }}, {{ order.shipping_address?.state }} {{ order.shipping_address?.pincode }}</p>
                                <p class="phone">Phone: {{ order.shipping_address?.phone }}</p>
                            </div>
                        </div>

                        <!-- Payment Info -->
                        <div class="order-card">
                            <h3>Payment Information</h3>
                            <div class="payment-info">
                                <div class="info-row">
                                    <span>Method</span>
                                    <span>{{ formatPaymentMethod(order.payment_method) }}</span>
                                </div>
                                <div class="info-row">
                                    <span>Status</span>
                                    <span :class="['payment-status', order.payment_status]">
                                        {{ formatPaymentStatus(order.payment_status) }}
                                    </span>
                                </div>
                                <div class="info-row" v-if="order.payment_id">
                                    <span>Transaction ID</span>
                                    <span class="mono">{{ order.payment_id }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Tracking Info -->
                        <div class="order-card" v-if="order.tracking_number">
                            <h3>Tracking Information</h3>
                            <div class="tracking-info">
                                <div class="info-row">
                                    <span>Carrier</span>
                                    <span>{{ order.carrier || 'Shiprocket' }}</span>
                                </div>
                                <div class="info-row">
                                    <span>Tracking Number</span>
                                    <span class="mono">{{ order.tracking_number }}</span>
                                </div>
                                <a :href="getTrackingUrl()" target="_blank" class="btn-track">
                                    <i class="bi-box-arrow-up-right"></i> Track Shipment
                                </a>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="order-card actions-card">
                            <h3>Actions</h3>
                            <div class="actions-list">
                                <button v-if="order.status === 'delivered'" class="action-btn" @click="downloadInvoice">
                                    <i class="bi-file-earmark-pdf"></i> Download Invoice
                                </button>
                                <button v-if="canReturn" class="action-btn" @click="initiateReturn">
                                    <i class="bi-arrow-return-left"></i> Request Return
                                </button>
                                <button v-if="canCancel" class="action-btn danger" @click="cancelOrder">
                                    <i class="bi-x-circle"></i> Cancel Order
                                </button>
                                <button class="action-btn" @click="contactSupport">
                                    <i class="bi-headset"></i> Contact Support
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Order History -->
                <div class="order-card history-card" v-if="order.history && order.history.length">
                    <h2>Order History</h2>
                    <div class="history-list">
                        <div v-for="(event, index) in order.history" :key="index" class="history-item">
                            <div class="history-icon">
                                <i :class="getHistoryIcon(event.status)"></i>
                            </div>
                            <div class="history-content">
                                <h4>{{ formatStatus(event.status) }}</h4>
                                <p v-if="event.comment">{{ event.comment }}</p>
                                <span class="history-time">{{ formatDateTime(event.created_at) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from 'axios'

const route = useRoute()
const router = useRouter()

const order = ref(null)
const loading = ref(true)

const statusOrder = ['pending', 'confirmed', 'processing', 'shipped', 'delivered']

const getAuthHeaders = () => {
    const token = localStorage.getItem('token')
    return { Authorization: `Bearer ${token}` }
}

const fetchOrder = async () => {
    loading.value = true
    try {
        const response = await axios.get(`/api/customer/orders/${route.params.id}`, {
            headers: getAuthHeaders()
        })
        order.value = response.data.data || response.data
    } catch (error) {
        console.error('Failed to fetch order:', error)
        order.value = null
    } finally {
        loading.value = false
    }
}

const isStepActive = (step) => {
    return order.value?.status === step
}

const isStepCompleted = (step) => {
    const currentIndex = statusOrder.indexOf(order.value?.status)
    const stepIndex = statusOrder.indexOf(step)
    return currentIndex > stepIndex
}

const canCancel = computed(() => {
    return ['pending', 'confirmed'].includes(order.value?.status)
})

const canReturn = computed(() => {
    if (order.value?.status !== 'delivered') return false
    const deliveredDate = new Date(order.value?.delivered_at || order.value?.updated_at)
    const daysSinceDelivery = (new Date() - deliveredDate) / (1000 * 60 * 60 * 24)
    return daysSinceDelivery <= 7
})

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('en-IN', {
        day: 'numeric',
        month: 'short',
        year: 'numeric'
    })
}

const formatDateTime = (date) => {
    return new Date(date).toLocaleString('en-IN', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    })
}

const formatPrice = (price) => {
    return parseFloat(price).toLocaleString('en-IN')
}

const formatStatus = (status) => {
    const statusMap = {
        pending: 'Order Placed',
        confirmed: 'Order Confirmed',
        processing: 'Processing',
        shipped: 'Shipped',
        delivered: 'Delivered',
        cancelled: 'Cancelled'
    }
    return statusMap[status] || status
}

const formatPaymentMethod = (method) => {
    const methods = {
        razorpay: 'Razorpay',
        cod: 'Cash on Delivery',
        card: 'Credit/Debit Card',
        upi: 'UPI',
        netbanking: 'Net Banking'
    }
    return methods[method] || method
}

const formatPaymentStatus = (status) => {
    const statuses = {
        pending: 'Pending',
        paid: 'Paid',
        failed: 'Failed',
        refunded: 'Refunded'
    }
    return statuses[status] || status
}

const getHistoryIcon = (status) => {
    const icons = {
        pending: 'bi-clock',
        confirmed: 'bi-check-circle',
        processing: 'bi-gear',
        shipped: 'bi-truck',
        delivered: 'bi-house-check',
        cancelled: 'bi-x-circle'
    }
    return icons[status] || 'bi-circle'
}

const getTrackingUrl = () => {
    return `https://www.shiprocket.in/tracking/${order.value?.tracking_number}`
}

const downloadInvoice = async () => {
    try {
        const response = await axios.get(`/api/customer/orders/${order.value.id}/invoice`, {
            headers: getAuthHeaders(),
            responseType: 'blob'
        })
        const url = window.URL.createObjectURL(new Blob([response.data]))
        const link = document.createElement('a')
        link.href = url
        link.setAttribute('download', `invoice-${order.value.order_number}.pdf`)
        document.body.appendChild(link)
        link.click()
        link.remove()
    } catch (error) {
        alert('Failed to download invoice')
    }
}

const initiateReturn = () => {
    router.push(`/returns/new?order=${order.value.id}`)
}

const cancelOrder = async () => {
    if (!confirm('Are you sure you want to cancel this order?')) return

    try {
        await axios.post(`/api/customer/orders/${order.value.id}/cancel`, {}, {
            headers: getAuthHeaders()
        })
        order.value.status = 'cancelled'
    } catch (error) {
        alert(error.response?.data?.message || 'Failed to cancel order')
    }
}

const contactSupport = () => {
    router.push(`/support?order=${order.value.order_number}`)
}

onMounted(() => {
    fetchOrder()
})
</script>

<style scoped>
.order-details-page {
    min-height: 100vh;
    background: #f8f9fa;
    padding: 30px 0;
}

.container {
    max-width: 1100px;
    margin: 0 auto;
    padding: 0 20px;
}

.back-link {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    color: #666;
    text-decoration: none;
    margin-bottom: 20px;
    font-weight: 500;
}

.back-link:hover {
    color: #b8860b;
}

.loading-state, .error-state {
    text-align: center;
    padding: 60px 20px;
    background: #fff;
    border-radius: 12px;
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

.error-state i {
    font-size: 64px;
    color: #dc3545;
    margin-bottom: 15px;
}

.order-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
}

.header-info h1 {
    font-size: 28px;
    color: #1a1a2e;
    margin-bottom: 5px;
}

.header-info p {
    color: #666;
}

.status-badge {
    padding: 8px 20px;
    border-radius: 25px;
    font-size: 14px;
    font-weight: 600;
}

.status-badge.pending { background: #fff3cd; color: #856404; }
.status-badge.confirmed { background: #cce5ff; color: #004085; }
.status-badge.processing { background: #d4edda; color: #155724; }
.status-badge.shipped { background: #d1ecf1; color: #0c5460; }
.status-badge.delivered { background: #d4edda; color: #155724; }
.status-badge.cancelled { background: #f8d7da; color: #721c24; }

.order-timeline {
    display: flex;
    align-items: center;
    justify-content: center;
    background: #fff;
    border-radius: 12px;
    padding: 30px;
    margin-bottom: 25px;
}

.timeline-step {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 10px;
}

.step-icon {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background: #e0e0e0;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    color: #999;
}

.timeline-step.active .step-icon {
    background: linear-gradient(135deg, #b8860b 0%, #daa520 100%);
    color: #fff;
}

.timeline-step.completed .step-icon {
    background: #28a745;
    color: #fff;
}

.timeline-step span {
    font-size: 13px;
    color: #666;
    font-weight: 500;
}

.timeline-step.active span,
.timeline-step.completed span {
    color: #333;
}

.timeline-line {
    width: 80px;
    height: 3px;
    background: #e0e0e0;
    margin: 0 10px;
    margin-bottom: 30px;
}

.timeline-line.active {
    background: #28a745;
}

.order-grid {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 25px;
    margin-bottom: 25px;
}

.order-card {
    background: #fff;
    border-radius: 12px;
    padding: 25px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}

.order-card h2 {
    font-size: 18px;
    color: #1a1a2e;
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 1px solid #eee;
}

.order-card h3 {
    font-size: 16px;
    color: #1a1a2e;
    margin-bottom: 15px;
}

.info-column {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.items-list {
    margin-bottom: 25px;
}

.order-item {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 15px 0;
    border-bottom: 1px solid #f0f0f0;
}

.order-item:last-child {
    border-bottom: none;
}

.item-image {
    width: 80px;
    height: 80px;
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
    margin-bottom: 5px;
}

.item-sku, .item-variant {
    font-size: 13px;
    color: #666;
}

.item-meta {
    display: flex;
    gap: 15px;
    font-size: 13px;
    color: #999;
    margin-top: 5px;
}

.item-total {
    font-size: 16px;
    font-weight: 600;
    color: #1a1a2e;
}

.price-summary {
    border-top: 2px solid #eee;
    padding-top: 20px;
}

.summary-row {
    display: flex;
    justify-content: space-between;
    padding: 8px 0;
    font-size: 15px;
}

.summary-row .discount {
    color: #28a745;
}

.summary-row.total {
    border-top: 2px solid #1a1a2e;
    margin-top: 10px;
    padding-top: 15px;
    font-size: 18px;
    font-weight: 700;
}

.address-info p {
    color: #666;
    line-height: 1.6;
}

.address-info .name {
    font-weight: 600;
    color: #333;
    margin-bottom: 5px;
}

.address-info .phone {
    margin-top: 10px;
    color: #333;
}

.payment-info, .tracking-info {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.info-row {
    display: flex;
    justify-content: space-between;
    font-size: 14px;
}

.info-row span:first-child {
    color: #666;
}

.info-row span:last-child {
    color: #333;
    font-weight: 500;
}

.info-row .mono {
    font-family: monospace;
    font-size: 13px;
}

.payment-status.paid { color: #28a745; }
.payment-status.pending { color: #ffc107; }
.payment-status.failed { color: #dc3545; }
.payment-status.refunded { color: #17a2b8; }

.btn-track {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 12px;
    background: #007bff;
    color: #fff;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 500;
    margin-top: 10px;
}

.btn-track:hover {
    background: #0056b3;
}

.actions-list {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.action-btn {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 15px;
    background: #f8f9fa;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    cursor: pointer;
    font-size: 14px;
    color: #333;
    transition: all 0.3s;
}

.action-btn:hover {
    background: #e9ecef;
    border-color: #b8860b;
}

.action-btn.danger {
    color: #dc3545;
}

.action-btn.danger:hover {
    background: #f8d7da;
    border-color: #dc3545;
}

.history-card {
    grid-column: 1 / -1;
}

.history-list {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.history-item {
    display: flex;
    gap: 15px;
}

.history-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: #f0f0f0;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #666;
    flex-shrink: 0;
}

.history-content h4 {
    font-size: 15px;
    color: #333;
    margin-bottom: 4px;
}

.history-content p {
    font-size: 14px;
    color: #666;
    margin-bottom: 4px;
}

.history-time {
    font-size: 13px;
    color: #999;
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

@media (max-width: 992px) {
    .order-grid {
        grid-template-columns: 1fr;
    }

    .order-timeline {
        flex-wrap: wrap;
        gap: 10px;
    }

    .timeline-line {
        width: 40px;
    }
}

@media (max-width: 576px) {
    .order-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 15px;
    }

    .order-timeline {
        display: none;
    }
}
</style>
