<template>
    <div class="customer-dashboard">
        <div class="container">
            <!-- Welcome Section -->
            <div class="welcome-section">
                <div class="welcome-content">
                    <h1>Welcome back, {{ customer?.name || 'Customer' }}!</h1>
                    <p>Manage your orders, profile, and preferences</p>
                </div>
                <div class="loyalty-badge" v-if="loyaltyData">
                    <div class="badge-icon">
                        <i class="bi-gem"></i>
                    </div>
                    <div class="badge-info">
                        <span class="tier">{{ loyaltyData.tier }} Member</span>
                        <span class="points">{{ loyaltyData.points }} Points</span>
                    </div>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon orders">
                        <i class="bi-bag-check"></i>
                    </div>
                    <div class="stat-info">
                        <h3>{{ stats.totalOrders }}</h3>
                        <p>Total Orders</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon wishlist">
                        <i class="bi-heart"></i>
                    </div>
                    <div class="stat-info">
                        <h3>{{ stats.wishlistItems }}</h3>
                        <p>Wishlist Items</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon pending">
                        <i class="bi-clock-history"></i>
                    </div>
                    <div class="stat-info">
                        <h3>{{ stats.pendingOrders }}</h3>
                        <p>Pending Orders</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon rewards">
                        <i class="bi-gift"></i>
                    </div>
                    <div class="stat-info">
                        <h3>₹{{ stats.rewardsValue }}</h3>
                        <p>Rewards Value</p>
                    </div>
                </div>
            </div>

            <div class="dashboard-grid">
                <!-- Recent Orders -->
                <div class="dashboard-card recent-orders">
                    <div class="card-header">
                        <h2>Recent Orders</h2>
                        <router-link to="/orders" class="view-all">View All</router-link>
                    </div>
                    <div class="card-body">
                        <div v-if="loading" class="loading-state">
                            <div class="spinner"></div>
                        </div>
                        <div v-else-if="recentOrders.length === 0" class="empty-state">
                            <i class="bi-bag-x"></i>
                            <p>No orders yet</p>
                            <router-link to="/products" class="btn-primary">Start Shopping</router-link>
                        </div>
                        <div v-else class="orders-list">
                            <div v-for="order in recentOrders" :key="order.id" class="order-item">
                                <div class="order-image">
                                    <img :src="order.items[0]?.product?.image || '/images/placeholder.png'" :alt="order.items[0]?.product?.name" />
                                </div>
                                <div class="order-info">
                                    <h4>Order #{{ order.order_number }}</h4>
                                    <p class="order-date">{{ formatDate(order.created_at) }}</p>
                                    <p class="order-items">{{ order.items.length }} item(s)</p>
                                </div>
                                <div class="order-status">
                                    <span :class="['status-badge', order.status]">{{ formatStatus(order.status) }}</span>
                                    <p class="order-total">₹{{ formatPrice(order.total) }}</p>
                                </div>
                                <router-link :to="`/orders/${order.id}`" class="order-link">
                                    <i class="bi-chevron-right"></i>
                                </router-link>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="dashboard-card quick-actions">
                    <div class="card-header">
                        <h2>Quick Actions</h2>
                    </div>
                    <div class="card-body">
                        <div class="actions-grid">
                            <router-link to="/orders" class="action-item">
                                <i class="bi-bag-check"></i>
                                <span>My Orders</span>
                            </router-link>
                            <router-link to="/wishlist" class="action-item">
                                <i class="bi-heart"></i>
                                <span>Wishlist</span>
                            </router-link>
                            <router-link to="/profile" class="action-item">
                                <i class="bi-person"></i>
                                <span>Profile</span>
                            </router-link>
                            <router-link to="/addresses" class="action-item">
                                <i class="bi-geo-alt"></i>
                                <span>Addresses</span>
                            </router-link>
                            <router-link to="/loyalty" class="action-item">
                                <i class="bi-gem"></i>
                                <span>Loyalty</span>
                            </router-link>
                            <router-link to="/referrals" class="action-item">
                                <i class="bi-people"></i>
                                <span>Referrals</span>
                            </router-link>
                        </div>
                    </div>
                </div>

                <!-- Notifications -->
                <div class="dashboard-card notifications">
                    <div class="card-header">
                        <h2>Notifications</h2>
                        <router-link to="/notifications" class="view-all">View All</router-link>
                    </div>
                    <div class="card-body">
                        <div v-if="notifications.length === 0" class="empty-state small">
                            <i class="bi-bell-slash"></i>
                            <p>No new notifications</p>
                        </div>
                        <div v-else class="notifications-list">
                            <div v-for="notification in notifications.slice(0, 5)" :key="notification.id"
                                 class="notification-item" :class="{ unread: !notification.read_at }">
                                <div class="notification-icon" :class="notification.type">
                                    <i :class="getNotificationIcon(notification.type)"></i>
                                </div>
                                <div class="notification-content">
                                    <p>{{ notification.data.message }}</p>
                                    <span class="notification-time">{{ formatTimeAgo(notification.created_at) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Loyalty Progress -->
                <div class="dashboard-card loyalty-progress" v-if="loyaltyData">
                    <div class="card-header">
                        <h2>Loyalty Status</h2>
                        <router-link to="/loyalty" class="view-all">View Details</router-link>
                    </div>
                    <div class="card-body">
                        <div class="tier-info">
                            <div class="current-tier">
                                <span class="tier-label">Current Tier</span>
                                <span class="tier-name" :class="loyaltyData.tier.toLowerCase()">{{ loyaltyData.tier }}</span>
                            </div>
                            <div class="points-balance">
                                <span class="points-label">Points Balance</span>
                                <span class="points-value">{{ loyaltyData.points }}</span>
                            </div>
                        </div>
                        <div class="tier-progress" v-if="loyaltyData.next_tier">
                            <div class="progress-header">
                                <span>Progress to {{ loyaltyData.next_tier }}</span>
                                <span>{{ loyaltyData.points_to_next }} points to go</span>
                            </div>
                            <div class="progress-bar">
                                <div class="progress-fill" :style="{ width: loyaltyData.progress + '%' }"></div>
                            </div>
                        </div>
                        <div class="tier-benefits">
                            <h4>Your Benefits</h4>
                            <ul>
                                <li v-for="(benefit, index) in loyaltyData.benefits" :key="index">
                                    <i class="bi-check-circle-fill"></i>
                                    {{ benefit }}
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'

const customer = ref(null)
const loading = ref(true)
const recentOrders = ref([])
const notifications = ref([])
const loyaltyData = ref(null)
const stats = ref({
    totalOrders: 0,
    wishlistItems: 0,
    pendingOrders: 0,
    rewardsValue: 0
})

const getAuthHeaders = () => {
    const token = localStorage.getItem('token')
    return { Authorization: `Bearer ${token}` }
}

const fetchDashboardData = async () => {
    loading.value = true
    try {
        const [ordersRes, statsRes, notificationsRes, loyaltyRes] = await Promise.all([
            axios.get('/api/customer/orders?limit=5', { headers: getAuthHeaders() }),
            axios.get('/api/customer/dashboard/stats', { headers: getAuthHeaders() }),
            axios.get('/api/customer/notifications?limit=5', { headers: getAuthHeaders() }),
            axios.get('/api/customer/loyalty', { headers: getAuthHeaders() })
        ])

        recentOrders.value = ordersRes.data.data || []
        stats.value = statsRes.data || stats.value
        notifications.value = notificationsRes.data.data || []
        loyaltyData.value = loyaltyRes.data
    } catch (error) {
        console.error('Failed to fetch dashboard data:', error)
    } finally {
        loading.value = false
    }
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

const formatTimeAgo = (date) => {
    const now = new Date()
    const then = new Date(date)
    const diff = Math.floor((now - then) / 1000)

    if (diff < 60) return 'Just now'
    if (diff < 3600) return `${Math.floor(diff / 60)}m ago`
    if (diff < 86400) return `${Math.floor(diff / 3600)}h ago`
    if (diff < 604800) return `${Math.floor(diff / 86400)}d ago`
    return formatDate(date)
}

const getNotificationIcon = (type) => {
    const icons = {
        order: 'bi-bag-check',
        shipping: 'bi-truck',
        loyalty: 'bi-gem',
        promotion: 'bi-tag',
        default: 'bi-bell'
    }
    return icons[type] || icons.default
}

onMounted(() => {
    const storedCustomer = localStorage.getItem('customer')
    if (storedCustomer) {
        customer.value = JSON.parse(storedCustomer)
    }
    fetchDashboardData()
})
</script>

<style scoped>
.customer-dashboard {
    min-height: 100vh;
    background: #f8f9fa;
    padding: 30px 0;
}

.container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
}

.welcome-section {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
    border-radius: 16px;
    padding: 30px 40px;
    margin-bottom: 30px;
    color: #fff;
}

.welcome-content h1 {
    font-size: 28px;
    margin-bottom: 8px;
}

.welcome-content p {
    opacity: 0.8;
}

.loyalty-badge {
    display: flex;
    align-items: center;
    gap: 15px;
    background: rgba(255,255,255,0.1);
    padding: 15px 25px;
    border-radius: 12px;
}

.badge-icon {
    width: 50px;
    height: 50px;
    background: linear-gradient(135deg, #b8860b 0%, #daa520 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
}

.badge-info {
    display: flex;
    flex-direction: column;
}

.badge-info .tier {
    font-weight: 600;
    font-size: 14px;
    color: #daa520;
}

.badge-info .points {
    font-size: 20px;
    font-weight: 700;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: #fff;
    border-radius: 12px;
    padding: 25px;
    display: flex;
    align-items: center;
    gap: 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}

.stat-icon {
    width: 60px;
    height: 60px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
}

.stat-icon.orders {
    background: rgba(184, 134, 11, 0.1);
    color: #b8860b;
}

.stat-icon.wishlist {
    background: rgba(220, 53, 69, 0.1);
    color: #dc3545;
}

.stat-icon.pending {
    background: rgba(0, 123, 255, 0.1);
    color: #007bff;
}

.stat-icon.rewards {
    background: rgba(40, 167, 69, 0.1);
    color: #28a745;
}

.stat-info h3 {
    font-size: 24px;
    color: #1a1a2e;
    margin-bottom: 5px;
}

.stat-info p {
    color: #666;
    font-size: 14px;
}

.dashboard-grid {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 25px;
}

.dashboard-card {
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    overflow: hidden;
}

.card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px 25px;
    border-bottom: 1px solid #eee;
}

.card-header h2 {
    font-size: 18px;
    color: #1a1a2e;
}

.view-all {
    color: #b8860b;
    text-decoration: none;
    font-weight: 500;
    font-size: 14px;
}

.card-body {
    padding: 20px 25px;
}

.loading-state, .empty-state {
    text-align: center;
    padding: 40px 20px;
    color: #666;
}

.empty-state i {
    font-size: 48px;
    margin-bottom: 15px;
    opacity: 0.3;
}

.empty-state.small {
    padding: 20px;
}

.empty-state.small i {
    font-size: 32px;
}

.orders-list {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.order-item {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 10px;
    transition: background 0.3s;
}

.order-item:hover {
    background: #f0f0f0;
}

.order-image {
    width: 60px;
    height: 60px;
    border-radius: 8px;
    overflow: hidden;
}

.order-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.order-info {
    flex: 1;
}

.order-info h4 {
    font-size: 15px;
    color: #1a1a2e;
    margin-bottom: 4px;
}

.order-date, .order-items {
    font-size: 13px;
    color: #666;
}

.order-status {
    text-align: right;
}

.status-badge {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 500;
}

.status-badge.pending { background: #fff3cd; color: #856404; }
.status-badge.confirmed { background: #cce5ff; color: #004085; }
.status-badge.processing { background: #d4edda; color: #155724; }
.status-badge.shipped { background: #d1ecf1; color: #0c5460; }
.status-badge.delivered { background: #d4edda; color: #155724; }
.status-badge.cancelled { background: #f8d7da; color: #721c24; }

.order-total {
    font-weight: 600;
    color: #1a1a2e;
    margin-top: 5px;
}

.order-link {
    color: #b8860b;
    font-size: 20px;
}

.actions-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 15px;
}

.action-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 10px;
    padding: 20px;
    background: #f8f9fa;
    border-radius: 10px;
    text-decoration: none;
    color: #1a1a2e;
    transition: all 0.3s;
}

.action-item:hover {
    background: linear-gradient(135deg, #b8860b 0%, #daa520 100%);
    color: #fff;
}

.action-item i {
    font-size: 24px;
}

.action-item span {
    font-size: 13px;
    font-weight: 500;
}

.notifications-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.notification-item {
    display: flex;
    gap: 12px;
    padding: 12px;
    background: #f8f9fa;
    border-radius: 8px;
}

.notification-item.unread {
    background: #fff8e5;
    border-left: 3px solid #b8860b;
}

.notification-icon {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #e0e0e0;
    color: #666;
}

.notification-icon.order { background: rgba(184, 134, 11, 0.1); color: #b8860b; }
.notification-icon.shipping { background: rgba(0, 123, 255, 0.1); color: #007bff; }
.notification-icon.loyalty { background: rgba(40, 167, 69, 0.1); color: #28a745; }

.notification-content {
    flex: 1;
}

.notification-content p {
    font-size: 14px;
    color: #333;
    margin-bottom: 4px;
}

.notification-time {
    font-size: 12px;
    color: #999;
}

.tier-info {
    display: flex;
    justify-content: space-between;
    margin-bottom: 20px;
}

.current-tier, .points-balance {
    display: flex;
    flex-direction: column;
}

.tier-label, .points-label {
    font-size: 12px;
    color: #666;
    margin-bottom: 5px;
}

.tier-name {
    font-size: 18px;
    font-weight: 700;
}

.tier-name.bronze { color: #cd7f32; }
.tier-name.silver { color: #c0c0c0; }
.tier-name.gold { color: #ffd700; }
.tier-name.platinum { color: #e5e4e2; }

.points-value {
    font-size: 24px;
    font-weight: 700;
    color: #b8860b;
}

.tier-progress {
    margin-bottom: 20px;
}

.progress-header {
    display: flex;
    justify-content: space-between;
    font-size: 13px;
    color: #666;
    margin-bottom: 8px;
}

.progress-bar {
    height: 8px;
    background: #e0e0e0;
    border-radius: 4px;
    overflow: hidden;
}

.progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #b8860b, #daa520);
    border-radius: 4px;
    transition: width 0.3s;
}

.tier-benefits h4 {
    font-size: 14px;
    margin-bottom: 10px;
    color: #333;
}

.tier-benefits ul {
    list-style: none;
    padding: 0;
    margin: 0;
}

.tier-benefits li {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
    color: #666;
    padding: 6px 0;
}

.tier-benefits li i {
    color: #28a745;
}

.spinner {
    width: 30px;
    height: 30px;
    border: 3px solid #e0e0e0;
    border-top-color: #b8860b;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
    margin: 0 auto;
}

@keyframes spin {
    to { transform: rotate(360deg); }
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
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }

    .dashboard-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 768px) {
    .welcome-section {
        flex-direction: column;
        text-align: center;
        gap: 20px;
    }

    .stats-grid {
        grid-template-columns: 1fr;
    }

    .actions-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}
</style>
