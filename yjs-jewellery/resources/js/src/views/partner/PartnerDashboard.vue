<template>
    <div class="partner-dashboard">
        <div class="container">
            <!-- Welcome Section -->
            <div class="welcome-section">
                <div class="welcome-content">
                    <h1>Welcome, {{ partner?.business_name || 'Partner' }}!</h1>
                    <p>Manage your inquiries, orders, and business profile</p>
                </div>
                <div class="partner-badge" v-if="partner?.tier">
                    <i class="bi-award"></i>
                    <span>{{ partner.tier }} Partner</span>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon inquiries">
                        <i class="bi-envelope-paper"></i>
                    </div>
                    <div class="stat-info">
                        <h3>{{ stats.totalInquiries }}</h3>
                        <p>Total Inquiries</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon pending">
                        <i class="bi-clock-history"></i>
                    </div>
                    <div class="stat-info">
                        <h3>{{ stats.pendingInquiries }}</h3>
                        <p>Pending</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon approved">
                        <i class="bi-check-circle"></i>
                    </div>
                    <div class="stat-info">
                        <h3>{{ stats.approvedInquiries }}</h3>
                        <p>Approved</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon revenue">
                        <i class="bi-currency-rupee"></i>
                    </div>
                    <div class="stat-info">
                        <h3>₹{{ formatPrice(stats.totalValue) }}</h3>
                        <p>Total Value</p>
                    </div>
                </div>
            </div>

            <div class="dashboard-grid">
                <!-- Recent Inquiries -->
                <div class="dashboard-card">
                    <div class="card-header">
                        <h2>Recent Inquiries</h2>
                        <router-link to="/partner/inquiries" class="view-all">View All</router-link>
                    </div>
                    <div class="card-body">
                        <div v-if="loading" class="loading-state">
                            <div class="spinner"></div>
                        </div>
                        <div v-else-if="recentInquiries.length === 0" class="empty-state">
                            <i class="bi-inbox"></i>
                            <p>No inquiries yet</p>
                            <router-link to="/partner/products" class="btn-primary">Browse Products</router-link>
                        </div>
                        <div v-else class="inquiries-list">
                            <div v-for="inquiry in recentInquiries" :key="inquiry.id" class="inquiry-item">
                                <div class="inquiry-info">
                                    <h4>{{ inquiry.inquiry_number }}</h4>
                                    <p>{{ inquiry.items_count }} items · {{ formatDate(inquiry.created_at) }}</p>
                                </div>
                                <div class="inquiry-status">
                                    <span :class="['status-badge', inquiry.status]">{{ formatStatus(inquiry.status) }}</span>
                                    <p class="inquiry-value">₹{{ formatPrice(inquiry.total_value) }}</p>
                                </div>
                                <router-link :to="`/partner/inquiries/${inquiry.id}`" class="inquiry-link">
                                    <i class="bi-chevron-right"></i>
                                </router-link>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="dashboard-card">
                    <div class="card-header">
                        <h2>Quick Actions</h2>
                    </div>
                    <div class="card-body">
                        <div class="actions-list">
                            <router-link to="/partner/products" class="action-item">
                                <div class="action-icon">
                                    <i class="bi-search"></i>
                                </div>
                                <div class="action-info">
                                    <span>Browse Products</span>
                                    <small>Find products to inquire about</small>
                                </div>
                                <i class="bi-chevron-right"></i>
                            </router-link>
                            <router-link to="/partner/selected" class="action-item">
                                <div class="action-icon">
                                    <i class="bi-cart-check"></i>
                                </div>
                                <div class="action-info">
                                    <span>Selected Products</span>
                                    <small>{{ selectedCount }} items selected</small>
                                </div>
                                <i class="bi-chevron-right"></i>
                            </router-link>
                            <router-link to="/partner/inquiries" class="action-item">
                                <div class="action-icon">
                                    <i class="bi-list-ul"></i>
                                </div>
                                <div class="action-info">
                                    <span>My Inquiries</span>
                                    <small>View all inquiry history</small>
                                </div>
                                <i class="bi-chevron-right"></i>
                            </router-link>
                            <router-link to="/partner/profile" class="action-item">
                                <div class="action-icon">
                                    <i class="bi-building"></i>
                                </div>
                                <div class="action-info">
                                    <span>Business Profile</span>
                                    <small>Manage your details</small>
                                </div>
                                <i class="bi-chevron-right"></i>
                            </router-link>
                        </div>
                    </div>
                </div>

                <!-- Price Tiers Info -->
                <div class="dashboard-card tier-card">
                    <div class="card-header">
                        <h2>Your Pricing Tier</h2>
                    </div>
                    <div class="card-body">
                        <div class="tier-info">
                            <div class="current-tier">
                                <i class="bi-award-fill"></i>
                                <div>
                                    <span class="tier-name">{{ partner?.tier || 'Standard' }}</span>
                                    <span class="tier-discount">{{ tierDiscount }}% off on all products</span>
                                </div>
                            </div>
                            <div class="tier-benefits">
                                <h4>Benefits</h4>
                                <ul>
                                    <li><i class="bi-check"></i> Exclusive B2B pricing</li>
                                    <li><i class="bi-check"></i> Bulk order discounts</li>
                                    <li><i class="bi-check"></i> Priority support</li>
                                    <li><i class="bi-check"></i> Extended payment terms</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="dashboard-card">
                    <div class="card-header">
                        <h2>Recent Activity</h2>
                    </div>
                    <div class="card-body">
                        <div v-if="activities.length === 0" class="empty-state small">
                            <p>No recent activity</p>
                        </div>
                        <div v-else class="activity-list">
                            <div v-for="activity in activities" :key="activity.id" class="activity-item">
                                <div class="activity-icon" :class="activity.type">
                                    <i :class="getActivityIcon(activity.type)"></i>
                                </div>
                                <div class="activity-content">
                                    <p>{{ activity.description }}</p>
                                    <span class="activity-time">{{ formatTimeAgo(activity.created_at) }}</span>
                                </div>
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
import axios from 'axios'

const partner = ref(null)
const loading = ref(true)
const recentInquiries = ref([])
const activities = ref([])
const selectedCount = ref(0)

const stats = ref({
    totalInquiries: 0,
    pendingInquiries: 0,
    approvedInquiries: 0,
    totalValue: 0
})

const tierDiscount = computed(() => {
    const discounts = {
        'bronze': 10,
        'silver': 15,
        'gold': 20,
        'platinum': 25,
        'standard': 5
    }
    return discounts[partner.value?.tier?.toLowerCase()] || 5
})

const getAuthHeaders = () => {
    const token = localStorage.getItem('partner_token')
    return { Authorization: `Bearer ${token}` }
}

const fetchDashboardData = async () => {
    loading.value = true
    try {
        const [profileRes, inquiriesRes, statsRes, activityRes] = await Promise.all([
            axios.get('/api/partner/profile', { headers: getAuthHeaders() }),
            axios.get('/api/partner/inquiries?limit=5', { headers: getAuthHeaders() }),
            axios.get('/api/partner/dashboard/stats', { headers: getAuthHeaders() }),
            axios.get('/api/partner/activity?limit=5', { headers: getAuthHeaders() })
        ])

        partner.value = profileRes.data.data || profileRes.data
        recentInquiries.value = inquiriesRes.data.data || []
        stats.value = statsRes.data || stats.value
        activities.value = activityRes.data.data || []

        // Get selected products count from localStorage
        const selected = JSON.parse(localStorage.getItem('selected_products') || '[]')
        selectedCount.value = selected.length
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
    return parseFloat(price || 0).toLocaleString('en-IN')
}

const formatStatus = (status) => {
    const statusMap = {
        pending: 'Pending',
        under_review: 'Under Review',
        quoted: 'Quoted',
        approved: 'Approved',
        rejected: 'Rejected',
        completed: 'Completed'
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
    return `${Math.floor(diff / 86400)}d ago`
}

const getActivityIcon = (type) => {
    const icons = {
        inquiry: 'bi-envelope-paper',
        quote: 'bi-receipt',
        approval: 'bi-check-circle',
        message: 'bi-chat-dots',
        default: 'bi-bell'
    }
    return icons[type] || icons.default
}

onMounted(() => {
    fetchDashboardData()
})
</script>

<style scoped>
.partner-dashboard {
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

.partner-badge {
    display: flex;
    align-items: center;
    gap: 10px;
    background: rgba(255,255,255,0.1);
    padding: 12px 20px;
    border-radius: 25px;
}

.partner-badge i {
    font-size: 20px;
    color: #ffd700;
}

.partner-badge span {
    font-weight: 600;
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

.stat-icon.inquiries { background: rgba(0, 123, 255, 0.1); color: #007bff; }
.stat-icon.pending { background: rgba(255, 193, 7, 0.1); color: #ffc107; }
.stat-icon.approved { background: rgba(40, 167, 69, 0.1); color: #28a745; }
.stat-icon.revenue { background: rgba(184, 134, 11, 0.1); color: #b8860b; }

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
    grid-template-columns: repeat(2, 1fr);
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

.loading-state {
    text-align: center;
    padding: 40px;
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

.empty-state {
    text-align: center;
    padding: 40px;
    color: #666;
}

.empty-state i {
    font-size: 40px;
    opacity: 0.3;
    margin-bottom: 10px;
}

.empty-state.small {
    padding: 20px;
}

.inquiries-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.inquiry-item {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 10px;
    transition: background 0.3s;
}

.inquiry-item:hover {
    background: #f0f0f0;
}

.inquiry-info {
    flex: 1;
}

.inquiry-info h4 {
    font-size: 15px;
    color: #1a1a2e;
    margin-bottom: 4px;
}

.inquiry-info p {
    font-size: 13px;
    color: #666;
}

.inquiry-status {
    text-align: right;
}

.status-badge {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 15px;
    font-size: 12px;
    font-weight: 500;
}

.status-badge.pending { background: #fff3cd; color: #856404; }
.status-badge.under_review { background: #cce5ff; color: #004085; }
.status-badge.quoted { background: #d1ecf1; color: #0c5460; }
.status-badge.approved { background: #d4edda; color: #155724; }
.status-badge.rejected { background: #f8d7da; color: #721c24; }
.status-badge.completed { background: #d4edda; color: #155724; }

.inquiry-value {
    font-weight: 600;
    color: #1a1a2e;
    margin-top: 5px;
    font-size: 14px;
}

.inquiry-link {
    color: #b8860b;
    font-size: 18px;
}

.actions-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.action-item {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 10px;
    text-decoration: none;
    color: inherit;
    transition: all 0.3s;
}

.action-item:hover {
    background: #f0f0f0;
    transform: translateX(5px);
}

.action-icon {
    width: 45px;
    height: 45px;
    background: linear-gradient(135deg, #b8860b 0%, #daa520 100%);
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-size: 18px;
}

.action-info {
    flex: 1;
}

.action-info span {
    display: block;
    font-weight: 600;
    color: #1a1a2e;
}

.action-info small {
    color: #666;
    font-size: 13px;
}

.action-item > i:last-child {
    color: #999;
}

.tier-info {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.current-tier {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 20px;
    background: linear-gradient(135deg, #fff8e5 0%, #fff5d6 100%);
    border-radius: 10px;
}

.current-tier i {
    font-size: 36px;
    color: #b8860b;
}

.tier-name {
    display: block;
    font-size: 20px;
    font-weight: 700;
    color: #1a1a2e;
}

.tier-discount {
    font-size: 14px;
    color: #28a745;
}

.tier-benefits h4 {
    font-size: 14px;
    color: #666;
    margin-bottom: 12px;
}

.tier-benefits ul {
    list-style: none;
    padding: 0;
    margin: 0;
}

.tier-benefits li {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 8px 0;
    font-size: 14px;
    color: #333;
}

.tier-benefits li i {
    color: #28a745;
}

.activity-list {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.activity-item {
    display: flex;
    gap: 12px;
}

.activity-icon {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    flex-shrink: 0;
}

.activity-icon.inquiry { background: rgba(0, 123, 255, 0.1); color: #007bff; }
.activity-icon.quote { background: rgba(184, 134, 11, 0.1); color: #b8860b; }
.activity-icon.approval { background: rgba(40, 167, 69, 0.1); color: #28a745; }
.activity-icon.message { background: rgba(108, 117, 125, 0.1); color: #6c757d; }

.activity-content p {
    font-size: 14px;
    color: #333;
    margin-bottom: 4px;
}

.activity-time {
    font-size: 12px;
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
}
</style>
