<template>
    <div class="notifications-page">
        <div class="container">
            <div class="page-header">
                <div>
                    <h1>Notifications</h1>
                    <p>{{ unreadCount }} unread notification(s)</p>
                </div>
                <div class="header-actions">
                    <button v-if="unreadCount > 0" class="btn-text" @click="markAllAsRead">
                        <i class="bi-check-all"></i> Mark all as read
                    </button>
                    <button v-if="notifications.length > 0" class="btn-text danger" @click="clearAll">
                        <i class="bi-trash"></i> Clear all
                    </button>
                </div>
            </div>

            <!-- Filter Tabs -->
            <div class="filter-tabs">
                <button :class="['tab', activeFilter === 'all' ? 'active' : '']" @click="activeFilter = 'all'">
                    All
                </button>
                <button :class="['tab', activeFilter === 'unread' ? 'active' : '']" @click="activeFilter = 'unread'">
                    Unread ({{ unreadCount }})
                </button>
                <button :class="['tab', activeFilter === 'orders' ? 'active' : '']" @click="activeFilter = 'orders'">
                    Orders
                </button>
                <button :class="['tab', activeFilter === 'promotions' ? 'active' : '']" @click="activeFilter = 'promotions'">
                    Promotions
                </button>
            </div>

            <div v-if="loading" class="loading-state">
                <div class="spinner"></div>
                <p>Loading notifications...</p>
            </div>

            <div v-else-if="filteredNotifications.length === 0" class="empty-state">
                <i class="bi-bell-slash"></i>
                <h3>No notifications</h3>
                <p>You're all caught up!</p>
            </div>

            <div v-else class="notifications-list">
                <div v-for="notification in filteredNotifications" :key="notification.id"
                     class="notification-card" :class="{ unread: !notification.read_at }"
                     @click="handleNotificationClick(notification)">

                    <div class="notification-icon" :class="notification.data?.type || 'default'">
                        <i :class="getNotificationIcon(notification.data?.type)"></i>
                    </div>

                    <div class="notification-content">
                        <h4>{{ notification.data?.title || 'Notification' }}</h4>
                        <p>{{ notification.data?.message }}</p>
                        <span class="notification-time">{{ formatTimeAgo(notification.created_at) }}</span>
                    </div>

                    <div class="notification-actions">
                        <button v-if="!notification.read_at" class="action-btn" @click.stop="markAsRead(notification)">
                            <i class="bi-check"></i>
                        </button>
                        <button class="action-btn delete" @click.stop="deleteNotification(notification)">
                            <i class="bi-x"></i>
                        </button>
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
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'

const router = useRouter()
const notifications = ref([])
const loading = ref(true)
const activeFilter = ref('all')

const pagination = ref({
    currentPage: 1,
    lastPage: 1,
    perPage: 20,
    total: 0
})

const getAuthHeaders = () => {
    const token = localStorage.getItem('token')
    return { Authorization: `Bearer ${token}` }
}

const unreadCount = computed(() => {
    return notifications.value.filter(n => !n.read_at).length
})

const filteredNotifications = computed(() => {
    let filtered = [...notifications.value]

    switch (activeFilter.value) {
        case 'unread':
            filtered = filtered.filter(n => !n.read_at)
            break
        case 'orders':
            filtered = filtered.filter(n => ['order', 'shipping', 'delivery'].includes(n.data?.type))
            break
        case 'promotions':
            filtered = filtered.filter(n => ['promotion', 'offer', 'discount'].includes(n.data?.type))
            break
    }

    return filtered
})

const fetchNotifications = async () => {
    loading.value = true
    try {
        const response = await axios.get('/api/customer/notifications', {
            headers: getAuthHeaders(),
            params: {
                page: pagination.value.currentPage,
                per_page: pagination.value.perPage
            }
        })
        notifications.value = response.data.data || response.data || []
        if (response.data.meta) {
            pagination.value = {
                currentPage: response.data.meta.current_page || 1,
                lastPage: response.data.meta.last_page || 1,
                perPage: response.data.meta.per_page || 20,
                total: response.data.meta.total || 0
            }
        }
    } catch (error) {
        console.error('Failed to fetch notifications:', error)
    } finally {
        loading.value = false
    }
}

const markAsRead = async (notification) => {
    try {
        await axios.post(`/api/customer/notifications/${notification.id}/read`, {}, {
            headers: getAuthHeaders()
        })
        notification.read_at = new Date().toISOString()
    } catch (error) {
        console.error('Failed to mark notification as read:', error)
    }
}

const markAllAsRead = async () => {
    try {
        await axios.post('/api/customer/notifications/read-all', {}, {
            headers: getAuthHeaders()
        })
        notifications.value.forEach(n => {
            if (!n.read_at) {
                n.read_at = new Date().toISOString()
            }
        })
    } catch (error) {
        alert('Failed to mark all as read')
    }
}

const deleteNotification = async (notification) => {
    try {
        await axios.delete(`/api/customer/notifications/${notification.id}`, {
            headers: getAuthHeaders()
        })
        notifications.value = notifications.value.filter(n => n.id !== notification.id)
    } catch (error) {
        alert('Failed to delete notification')
    }
}

const clearAll = async () => {
    if (!confirm('Are you sure you want to clear all notifications?')) return

    try {
        await axios.delete('/api/customer/notifications', {
            headers: getAuthHeaders()
        })
        notifications.value = []
    } catch (error) {
        alert('Failed to clear notifications')
    }
}

const handleNotificationClick = (notification) => {
    if (!notification.read_at) {
        markAsRead(notification)
    }

    // Navigate based on notification type
    if (notification.data?.action_url) {
        router.push(notification.data.action_url)
    } else if (notification.data?.order_id) {
        router.push(`/orders/${notification.data.order_id}`)
    }
}

const changePage = (page) => {
    pagination.value.currentPage = page
    fetchNotifications()
}

const getNotificationIcon = (type) => {
    const icons = {
        order: 'bi-bag-check',
        shipping: 'bi-truck',
        delivery: 'bi-house-check',
        payment: 'bi-credit-card',
        promotion: 'bi-tag',
        offer: 'bi-percent',
        discount: 'bi-gift',
        loyalty: 'bi-gem',
        referral: 'bi-people',
        account: 'bi-person',
        default: 'bi-bell'
    }
    return icons[type] || icons.default
}

const formatTimeAgo = (date) => {
    const now = new Date()
    const then = new Date(date)
    const diff = Math.floor((now - then) / 1000)

    if (diff < 60) return 'Just now'
    if (diff < 3600) return `${Math.floor(diff / 60)} minutes ago`
    if (diff < 86400) return `${Math.floor(diff / 3600)} hours ago`
    if (diff < 604800) return `${Math.floor(diff / 86400)} days ago`

    return then.toLocaleDateString('en-IN', {
        day: 'numeric',
        month: 'short',
        year: 'numeric'
    })
}

onMounted(() => {
    fetchNotifications()
})
</script>

<style scoped>
.notifications-page {
    min-height: 100vh;
    background: #f8f9fa;
    padding: 30px 0;
}

.container {
    max-width: 800px;
    margin: 0 auto;
    padding: 0 20px;
}

.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 25px;
}

.page-header h1 {
    font-size: 28px;
    color: #1a1a2e;
    margin-bottom: 8px;
}

.page-header p {
    color: #666;
}

.header-actions {
    display: flex;
    gap: 15px;
}

.btn-text {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 8px 12px;
    background: transparent;
    border: none;
    color: #666;
    cursor: pointer;
    font-weight: 500;
    font-size: 14px;
}

.btn-text:hover {
    color: #b8860b;
}

.btn-text.danger:hover {
    color: #dc3545;
}

.filter-tabs {
    display: flex;
    gap: 10px;
    margin-bottom: 25px;
    overflow-x: auto;
    padding-bottom: 5px;
}

.tab {
    padding: 10px 20px;
    background: #fff;
    border: 2px solid #e0e0e0;
    border-radius: 25px;
    cursor: pointer;
    font-weight: 500;
    white-space: nowrap;
    transition: all 0.3s;
}

.tab:hover {
    border-color: #b8860b;
}

.tab.active {
    background: linear-gradient(135deg, #b8860b 0%, #daa520 100%);
    border-color: #b8860b;
    color: #fff;
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
}

.notifications-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.notification-card {
    display: flex;
    align-items: flex-start;
    gap: 15px;
    background: #fff;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    cursor: pointer;
    transition: all 0.3s;
}

.notification-card:hover {
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.notification-card.unread {
    background: #fffbf0;
    border-left: 4px solid #b8860b;
}

.notification-icon {
    width: 45px;
    height: 45px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    flex-shrink: 0;
}

.notification-icon.order {
    background: rgba(184, 134, 11, 0.1);
    color: #b8860b;
}

.notification-icon.shipping,
.notification-icon.delivery {
    background: rgba(0, 123, 255, 0.1);
    color: #007bff;
}

.notification-icon.payment {
    background: rgba(40, 167, 69, 0.1);
    color: #28a745;
}

.notification-icon.promotion,
.notification-icon.offer,
.notification-icon.discount {
    background: rgba(220, 53, 69, 0.1);
    color: #dc3545;
}

.notification-icon.loyalty {
    background: rgba(255, 193, 7, 0.1);
    color: #ffc107;
}

.notification-icon.default {
    background: #f0f0f0;
    color: #666;
}

.notification-content {
    flex: 1;
}

.notification-content h4 {
    font-size: 15px;
    color: #1a1a2e;
    margin-bottom: 5px;
}

.notification-content p {
    font-size: 14px;
    color: #666;
    line-height: 1.5;
    margin-bottom: 8px;
}

.notification-time {
    font-size: 12px;
    color: #999;
}

.notification-actions {
    display: flex;
    gap: 8px;
}

.action-btn {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    border: 1px solid #e0e0e0;
    background: #fff;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    color: #666;
    transition: all 0.3s;
}

.action-btn:hover {
    background: #f5f5f5;
}

.action-btn.delete:hover {
    background: #f8d7da;
    border-color: #dc3545;
    color: #dc3545;
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

@media (max-width: 576px) {
    .page-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 15px;
    }

    .notification-card {
        padding: 15px;
    }

    .notification-icon {
        width: 40px;
        height: 40px;
        font-size: 16px;
    }
}
</style>
