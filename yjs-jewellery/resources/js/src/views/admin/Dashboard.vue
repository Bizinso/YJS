<script setup>
import { ref, onMounted, computed } from 'vue'
import axios from 'axios'

const loading = ref(true)
const stats = ref({
    total_orders: 0,
    pending_orders: 0,
    total_revenue: 0,
    total_customers: 0,
    total_products: 0,
    low_stock_products: 0,
    total_partners: 0,
    pending_inquiries: 0
})

const recentOrders = ref([])
const topProducts = ref([])
const recentCustomers = ref([])
const period = ref('today')

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
        year: 'numeric'
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

const fetchDashboardData = async () => {
    loading.value = true
    try {
        const [statsRes, ordersRes, productsRes, customersRes] = await Promise.all([
            axios.get('/api/admin/dashboard/stats', { params: { period: period.value } }),
            axios.get('/api/admin/orders', { params: { limit: 5 } }),
            axios.get('/api/admin/products/top-selling', { params: { limit: 5 } }),
            axios.get('/api/admin/customers', { params: { limit: 5, sort: '-created_at' } })
        ])

        if (statsRes.data.success) {
            stats.value = statsRes.data.data
        }
        if (ordersRes.data.success) {
            recentOrders.value = ordersRes.data.data.data || ordersRes.data.data || []
        }
        if (productsRes.data.success) {
            topProducts.value = productsRes.data.data || []
        }
        if (customersRes.data.success) {
            recentCustomers.value = customersRes.data.data.data || customersRes.data.data || []
        }
    } catch (error) {
        console.error('Error fetching dashboard data:', error)
    } finally {
        loading.value = false
    }
}

onMounted(() => {
    fetchDashboardData()
})
</script>

<template>
    <div class="p-6 bg-gray-50 min-h-screen">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-800">Dashboard</h1>
            <p class="text-gray-600">Welcome to YJS Jewellers Admin Panel</p>
        </div>

        <!-- Period Selector -->
        <div class="mb-6">
            <select
                v-model="period"
                @change="fetchDashboardData"
                class="px-4 py-2 border rounded-lg focus:ring-2 focus:ring-amber-500"
            >
                <option value="today">Today</option>
                <option value="week">This Week</option>
                <option value="month">This Month</option>
                <option value="year">This Year</option>
            </select>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-xl shadow-sm p-6 border-l-4 border-amber-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500 uppercase">Total Revenue</p>
                        <p class="text-2xl font-bold text-gray-800">{{ formatCurrency(stats.total_revenue) }}</p>
                    </div>
                    <div class="p-3 bg-amber-100 rounded-full">
                        <svg class="w-8 h-8 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm p-6 border-l-4 border-blue-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500 uppercase">Total Orders</p>
                        <p class="text-2xl font-bold text-gray-800">{{ stats.total_orders }}</p>
                        <p class="text-sm text-yellow-600">{{ stats.pending_orders }} pending</p>
                    </div>
                    <div class="p-3 bg-blue-100 rounded-full">
                        <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm p-6 border-l-4 border-green-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500 uppercase">Total Customers</p>
                        <p class="text-2xl font-bold text-gray-800">{{ stats.total_customers }}</p>
                    </div>
                    <div class="p-3 bg-green-100 rounded-full">
                        <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm p-6 border-l-4 border-purple-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500 uppercase">Products</p>
                        <p class="text-2xl font-bold text-gray-800">{{ stats.total_products }}</p>
                        <p class="text-sm text-red-600">{{ stats.low_stock_products }} low stock</p>
                    </div>
                    <div class="p-3 bg-purple-100 rounded-full">
                        <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Partner Stats -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <div class="bg-white rounded-xl shadow-sm p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500 uppercase">B2B Partners</p>
                        <p class="text-2xl font-bold text-gray-800">{{ stats.total_partners }}</p>
                    </div>
                    <div class="p-3 bg-indigo-100 rounded-full">
                        <svg class="w-8 h-8 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500 uppercase">Pending Inquiries</p>
                        <p class="text-2xl font-bold text-gray-800">{{ stats.pending_inquiries }}</p>
                    </div>
                    <div class="p-3 bg-orange-100 rounded-full">
                        <svg class="w-8 h-8 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Orders & Top Products -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Recent Orders -->
            <div class="bg-white rounded-xl shadow-sm">
                <div class="p-6 border-b">
                    <div class="flex items-center justify-between">
                        <h2 class="text-lg font-semibold text-gray-800">Recent Orders</h2>
                        <router-link to="/admin/orders" class="text-sm text-amber-600 hover:text-amber-700">
                            View All
                        </router-link>
                    </div>
                </div>
                <div class="p-6">
                    <div v-if="loading" class="text-center py-4">
                        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-amber-600 mx-auto"></div>
                    </div>
                    <div v-else-if="recentOrders.length === 0" class="text-center py-4 text-gray-500">
                        No orders found
                    </div>
                    <div v-else class="space-y-4">
                        <div v-for="order in recentOrders" :key="order.id" class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                            <div>
                                <p class="font-medium text-gray-800">#{{ order.order_code || order.custom_order_code }}</p>
                                <p class="text-sm text-gray-500">{{ order.customer?.first_name }} {{ order.customer?.last_name }}</p>
                            </div>
                            <div class="text-right">
                                <p class="font-medium text-gray-800">{{ formatCurrency(order.order_total) }}</p>
                                <span :class="['px-2 py-1 text-xs rounded-full', getStatusClass(order.status)]">
                                    {{ order.status }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Top Products -->
            <div class="bg-white rounded-xl shadow-sm">
                <div class="p-6 border-b">
                    <div class="flex items-center justify-between">
                        <h2 class="text-lg font-semibold text-gray-800">Top Selling Products</h2>
                        <router-link to="/admin/products" class="text-sm text-amber-600 hover:text-amber-700">
                            View All
                        </router-link>
                    </div>
                </div>
                <div class="p-6">
                    <div v-if="loading" class="text-center py-4">
                        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-amber-600 mx-auto"></div>
                    </div>
                    <div v-else-if="topProducts.length === 0" class="text-center py-4 text-gray-500">
                        No products found
                    </div>
                    <div v-else class="space-y-4">
                        <div v-for="product in topProducts" :key="product.id" class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                            <div class="flex items-center space-x-3">
                                <img
                                    :src="product.main_image || '/images/placeholder.png'"
                                    :alt="product.name"
                                    class="w-12 h-12 rounded-lg object-cover"
                                >
                                <div>
                                    <p class="font-medium text-gray-800">{{ product.name }}</p>
                                    <p class="text-sm text-gray-500">SKU: {{ product.sku }}</p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="font-medium text-gray-800">{{ formatCurrency(product.base_price) }}</p>
                                <p class="text-sm text-gray-500">{{ product.total_sold || 0 }} sold</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
