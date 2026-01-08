<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import axiosAdmin from '@/plugins/axiosAdmin'

// State
const loading = ref(true)
const data = ref(null)
const selectedDays = ref(30)

const dayOptions = [
    { value: 7, label: 'Last 7 days' },
    { value: 30, label: 'Last 30 days' },
    { value: 90, label: 'Last 90 days' },
]

// Computed
const chartData = computed(() => {
    if (!data.value?.daily_data) return []
    return data.value.daily_data
})

const maxViews = computed(() => {
    if (!chartData.value.length) return 1
    return Math.max(...chartData.value.map(d => d.views), 1)
})

// Methods
const fetchAnalytics = async () => {
    loading.value = true
    try {
        const { data: response } = await axiosAdmin.get('/cms/analytics/overview', {
            params: { days: selectedDays.value }
        })
        if (response.success) {
            data.value = response.data
        }
    } catch (error) {
        console.error('Failed to fetch analytics:', error)
    } finally {
        loading.value = false
    }
}

const formatNumber = (num) => {
    if (num >= 1000000) return (num / 1000000).toFixed(1) + 'M'
    if (num >= 1000) return (num / 1000).toFixed(1) + 'K'
    return num?.toString() || '0'
}

const formatDate = (d) => new Date(d).toLocaleDateString('en-IN', { day: '2-digit', month: 'short' })

const getBarHeight = (views) => {
    return Math.max((views / maxViews.value) * 100, 2) + '%'
}

// Watch for period changes
watch(selectedDays, () => {
    fetchAnalytics()
})

// Lifecycle
onMounted(() => {
    fetchAnalytics()
})
</script>

<template>
    <div class="p-6">
        <!-- Header -->
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Analytics</h1>
                <p class="text-gray-600">Track page views and visitor statistics</p>
            </div>
            <div class="flex items-center gap-3">
                <select
                    v-model="selectedDays"
                    class="px-3 py-2 border rounded-lg text-sm"
                >
                    <option v-for="opt in dayOptions" :key="opt.value" :value="opt.value">
                        {{ opt.label }}
                    </option>
                </select>
                <button
                    @click="fetchAnalytics"
                    class="px-3 py-2 border rounded-lg hover:bg-gray-50"
                >
                    <i class="bi bi-arrow-clockwise"></i>
                </button>
            </div>
        </div>

        <div v-if="loading" class="text-center py-12">
            <i class="bi bi-arrow-repeat animate-spin text-3xl text-amber-500"></i>
            <p class="text-gray-500 mt-2">Loading analytics...</p>
        </div>

        <div v-else-if="data">
            <!-- Stats Cards -->
            <div class="grid grid-cols-4 gap-4 mb-6">
                <div class="bg-white rounded-lg shadow-sm p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500">Total Views</p>
                            <p class="text-2xl font-bold text-gray-800">
                                {{ formatNumber(data.stats.total_views) }}
                            </p>
                        </div>
                        <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                            <i class="bi bi-eye text-xl text-blue-600"></i>
                        </div>
                    </div>
                    <div
                        v-if="data.stats.views_change !== 0"
                        :class="['text-xs mt-2', data.stats.views_change > 0 ? 'text-green-600' : 'text-red-600']"
                    >
                        <i :class="['bi', data.stats.views_change > 0 ? 'bi-arrow-up' : 'bi-arrow-down']"></i>
                        {{ Math.abs(data.stats.views_change) }}% vs previous period
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-sm p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500">Unique Visitors</p>
                            <p class="text-2xl font-bold text-gray-800">
                                {{ formatNumber(data.stats.unique_visitors) }}
                            </p>
                        </div>
                        <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                            <i class="bi bi-people text-xl text-green-600"></i>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-sm p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500">Avg. Daily Views</p>
                            <p class="text-2xl font-bold text-gray-800">
                                {{ formatNumber(data.stats.avg_daily_views) }}
                            </p>
                        </div>
                        <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                            <i class="bi bi-graph-up text-xl text-purple-600"></i>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-sm p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500">Period</p>
                            <p class="text-lg font-semibold text-gray-800">
                                {{ selectedDays }} days
                            </p>
                        </div>
                        <div class="w-12 h-12 bg-amber-100 rounded-lg flex items-center justify-center">
                            <i class="bi bi-calendar3 text-xl text-amber-600"></i>
                        </div>
                    </div>
                    <p class="text-xs text-gray-500 mt-2">
                        {{ data.period.start }} - {{ data.period.end }}
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-3 gap-6">
                <!-- Chart -->
                <div class="col-span-2 bg-white rounded-lg shadow-sm p-6">
                    <h3 class="font-semibold text-gray-800 mb-4">Page Views Over Time</h3>
                    <div v-if="chartData.length" class="relative h-64">
                        <!-- Y-axis labels -->
                        <div class="absolute left-0 top-0 bottom-6 w-12 flex flex-col justify-between text-xs text-gray-400">
                            <span>{{ formatNumber(maxViews) }}</span>
                            <span>{{ formatNumber(Math.round(maxViews / 2)) }}</span>
                            <span>0</span>
                        </div>

                        <!-- Chart area -->
                        <div class="ml-14 h-full flex items-end gap-1 pb-6">
                            <div
                                v-for="(day, i) in chartData"
                                :key="i"
                                class="flex-1 flex flex-col items-center"
                            >
                                <div
                                    class="w-full bg-amber-500 rounded-t hover:bg-amber-600 transition-colors cursor-pointer group relative"
                                    :style="{ height: getBarHeight(day.views) }"
                                    :title="`${day.date}: ${day.views} views`"
                                >
                                    <!-- Tooltip -->
                                    <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 px-2 py-1 bg-gray-800 text-white text-xs rounded opacity-0 group-hover:opacity-100 whitespace-nowrap pointer-events-none z-10">
                                        {{ day.views }} views
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- X-axis labels -->
                        <div class="absolute left-14 right-0 bottom-0 flex justify-between text-xs text-gray-400">
                            <span>{{ formatDate(chartData[0]?.date) }}</span>
                            <span v-if="chartData.length > 7">{{ formatDate(chartData[Math.floor(chartData.length / 2)]?.date) }}</span>
                            <span>{{ formatDate(chartData[chartData.length - 1]?.date) }}</span>
                        </div>
                    </div>
                    <div v-else class="h-64 flex items-center justify-center text-gray-400">
                        <div class="text-center">
                            <i class="bi bi-bar-chart text-3xl mb-2"></i>
                            <p>No data for this period</p>
                        </div>
                    </div>
                </div>

                <!-- Top Pages -->
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <h3 class="font-semibold text-gray-800 mb-4">Top Pages</h3>
                    <div v-if="data.top_pages.length" class="space-y-3">
                        <div
                            v-for="(page, i) in data.top_pages"
                            :key="i"
                            class="flex items-center justify-between py-2 border-b last:border-0"
                        >
                            <div class="flex items-center gap-3 min-w-0">
                                <span class="w-6 h-6 bg-gray-100 rounded flex items-center justify-center text-xs font-medium text-gray-600">
                                    {{ i + 1 }}
                                </span>
                                <div class="min-w-0">
                                    <p class="text-sm font-medium text-gray-800 truncate">
                                        {{ page.title }}
                                    </p>
                                    <p class="text-xs text-gray-400 truncate">/{{ page.slug }}</p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-semibold text-gray-800">{{ formatNumber(page.views) }}</p>
                                <p class="text-xs text-gray-400">views</p>
                            </div>
                        </div>
                    </div>
                    <div v-else class="text-center py-8 text-gray-400">
                        <i class="bi bi-file-text text-2xl mb-2"></i>
                        <p class="text-sm">No page data yet</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- No Data -->
        <div v-else class="text-center py-12">
            <i class="bi bi-bar-chart text-4xl text-gray-300 mb-3"></i>
            <p class="text-gray-500">No analytics data available</p>
        </div>
    </div>
</template>

<style scoped>
@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}
.animate-spin {
    animation: spin 1s linear infinite;
}
</style>
