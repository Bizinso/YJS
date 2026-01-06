<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'

const router = useRouter()
const user = ref(null)
const showUserMenu = ref(false)
const showNotifications = ref(false)
const notifications = ref([])
const unreadCount = ref(0)
const searchQuery = ref('')

const userInitials = computed(() => {
    if (!user.value) return 'A'
    const first = user.value.first_name?.[0] || ''
    const last = user.value.last_name?.[0] || ''
    return (first + last).toUpperCase() || 'A'
})

const fetchUser = async () => {
    try {
        const response = await axios.get('/api/admin/profile')
        if (response.data.success) {
            user.value = response.data.data
        }
    } catch (error) {
        console.error('Error fetching user:', error)
    }
}

const fetchNotifications = async () => {
    try {
        const response = await axios.get('/api/admin/notifications', { params: { limit: 5 } })
        if (response.data.success) {
            notifications.value = response.data.data || []
            unreadCount.value = notifications.value.filter(n => !n.read_at).length
        }
    } catch (error) {
        console.error('Error fetching notifications:', error)
    }
}

const markAsRead = async (notification) => {
    try {
        await axios.post(`/api/admin/notifications/${notification.id}/read`)
        notification.read_at = new Date().toISOString()
        unreadCount.value = Math.max(0, unreadCount.value - 1)
    } catch (error) {
        console.error('Error marking notification as read:', error)
    }
}

const logout = async () => {
    try {
        await axios.post('/api/admin/logout')
        localStorage.removeItem('token')
        router.push('/admin/login')
    } catch (error) {
        console.error('Error logging out:', error)
        localStorage.removeItem('token')
        router.push('/admin/login')
    }
}

const handleSearch = () => {
    if (searchQuery.value.trim()) {
        router.push({ path: '/admin/search', query: { q: searchQuery.value } })
    }
}

const formatTime = (date) => {
    if (!date) return ''
    const now = new Date()
    const then = new Date(date)
    const diff = Math.floor((now - then) / 1000)

    if (diff < 60) return 'Just now'
    if (diff < 3600) return `${Math.floor(diff / 60)}m ago`
    if (diff < 86400) return `${Math.floor(diff / 3600)}h ago`
    return `${Math.floor(diff / 86400)}d ago`
}

onMounted(() => {
    fetchUser()
    fetchNotifications()
})
</script>

<template>
    <header class="bg-white shadow-sm border-b border-gray-200 sticky top-0 z-40">
        <div class="flex items-center justify-between px-6 py-3">
            <!-- Left: Logo & Search -->
            <div class="flex items-center space-x-6">
                <div class="flex items-center space-x-2">
                    <div class="w-10 h-10 bg-amber-600 rounded-lg flex items-center justify-center">
                        <span class="text-white font-bold text-xl">Y</span>
                    </div>
                    <span class="text-xl font-bold text-gray-800">YJS Admin</span>
                </div>

                <div class="hidden md:flex items-center">
                    <div class="relative">
                        <input
                            v-model="searchQuery"
                            @keyup.enter="handleSearch"
                            type="text"
                            placeholder="Search orders, products, customers..."
                            class="w-80 pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500"
                        >
                        <svg class="w-5 h-5 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Right: Notifications & User Menu -->
            <div class="flex items-center space-x-4">
                <!-- Notifications -->
                <div class="relative">
                    <button
                        @click="showNotifications = !showNotifications"
                        class="relative p-2 text-gray-600 hover:text-gray-800 hover:bg-gray-100 rounded-lg"
                    >
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                        </svg>
                        <span v-if="unreadCount > 0" class="absolute -top-1 -right-1 w-5 h-5 bg-red-500 text-white text-xs rounded-full flex items-center justify-center">
                            {{ unreadCount > 9 ? '9+' : unreadCount }}
                        </span>
                    </button>

                    <!-- Notifications Dropdown -->
                    <div
                        v-if="showNotifications"
                        class="absolute right-0 mt-2 w-80 bg-white rounded-lg shadow-lg border border-gray-200 z-50"
                    >
                        <div class="p-4 border-b border-gray-200">
                            <h3 class="font-semibold text-gray-800">Notifications</h3>
                        </div>
                        <div class="max-h-80 overflow-y-auto">
                            <div v-if="notifications.length === 0" class="p-4 text-center text-gray-500">
                                No notifications
                            </div>
                            <div
                                v-else
                                v-for="notification in notifications"
                                :key="notification.id"
                                @click="markAsRead(notification)"
                                :class="['p-4 hover:bg-gray-50 cursor-pointer border-b border-gray-100', { 'bg-amber-50': !notification.read_at }]"
                            >
                                <p class="text-sm text-gray-800">{{ notification.data?.message || notification.type }}</p>
                                <p class="text-xs text-gray-500 mt-1">{{ formatTime(notification.created_at) }}</p>
                            </div>
                        </div>
                        <div class="p-3 border-t border-gray-200">
                            <router-link to="/admin/notifications" class="text-sm text-amber-600 hover:text-amber-700">
                                View all notifications
                            </router-link>
                        </div>
                    </div>
                </div>

                <!-- User Menu -->
                <div class="relative">
                    <button
                        @click="showUserMenu = !showUserMenu"
                        class="flex items-center space-x-3 p-2 hover:bg-gray-100 rounded-lg"
                    >
                        <div class="w-9 h-9 bg-amber-600 rounded-full flex items-center justify-center">
                            <span class="text-white font-medium text-sm">{{ userInitials }}</span>
                        </div>
                        <div class="hidden md:block text-left">
                            <p class="text-sm font-medium text-gray-800">{{ user?.first_name }} {{ user?.last_name }}</p>
                            <p class="text-xs text-gray-500">Administrator</p>
                        </div>
                        <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>

                    <!-- User Dropdown -->
                    <div
                        v-if="showUserMenu"
                        class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-gray-200 z-50"
                    >
                        <router-link to="/admin/profile" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                            My Profile
                        </router-link>
                        <router-link to="/admin/settings" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                            Settings
                        </router-link>
                        <hr class="my-1">
                        <button
                            @click="logout"
                            class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50"
                        >
                            Logout
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </header>
</template>
