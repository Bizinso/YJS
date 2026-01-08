<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import axiosAdmin from '@/plugins/axiosAdmin'

// State
const loading = ref(true)
const submissions = ref([])
const pagination = ref({ current_page: 1, last_page: 1, total: 0, per_page: 20 })
const unreadCount = ref(0)
const selectedSubmission = ref(null)
const showDetailModal = ref(false)

// Filters
const filters = ref({
    page_id: '',
    is_read: '',
    from_date: '',
    to_date: ''
})

// Data for filters
const pages = ref([])

// Computed
const hasActiveFilters = computed(() =>
    filters.value.page_id || filters.value.is_read !== '' || filters.value.from_date || filters.value.to_date
)

// Methods
const fetchSubmissions = async (page = 1) => {
    loading.value = true
    try {
        const params = {
            page,
            per_page: pagination.value.per_page,
            ...filters.value
        }
        // Remove empty values
        Object.keys(params).forEach(key => {
            if (params[key] === '' || params[key] === null) delete params[key]
        })

        const { data } = await axiosAdmin.get('/cms/form-submissions', { params })
        if (data.success) {
            submissions.value = data.data
            if (data.pagination) {
                pagination.value = data.pagination
            }
            if (data.unread_count !== undefined) {
                unreadCount.value = data.unread_count
            }
        }
    } catch (error) {
        console.error('Error fetching submissions:', error)
    } finally {
        loading.value = false
    }
}

const fetchPages = async () => {
    try {
        const { data } = await axiosAdmin.get('/cms/pages', { params: { per_page: 100 } })
        if (data.success) {
            pages.value = data.data
        }
    } catch (error) {
        console.error('Error fetching pages:', error)
    }
}

const viewSubmission = async (submission) => {
    try {
        const { data } = await axiosAdmin.get(`/cms/form-submissions/${submission.id}`)
        if (data.success) {
            selectedSubmission.value = data.data
            showDetailModal.value = true

            // Update local data to reflect read status
            const index = submissions.value.findIndex(s => s.id === submission.id)
            if (index > -1 && !submissions.value[index].is_read) {
                submissions.value[index].is_read = true
                submissions.value[index].read_at = new Date().toISOString()
                unreadCount.value = Math.max(0, unreadCount.value - 1)
            }
        }
    } catch (error) {
        console.error('Error fetching submission:', error)
    }
}

const deleteSubmission = async (id) => {
    if (!confirm('Are you sure you want to delete this submission?')) return

    try {
        const { data } = await axiosAdmin.delete(`/cms/form-submissions/${id}`)
        if (data.success) {
            submissions.value = submissions.value.filter(s => s.id !== id)
            pagination.value.total--
            if (showDetailModal.value && selectedSubmission.value?.id === id) {
                showDetailModal.value = false
                selectedSubmission.value = null
            }
        }
    } catch (error) {
        console.error('Error deleting submission:', error)
    }
}

const exportToCsv = async () => {
    try {
        const params = { ...filters.value }
        Object.keys(params).forEach(key => {
            if (params[key] === '' || params[key] === null) delete params[key]
        })

        const response = await axiosAdmin.get('/cms/form-submissions/export', {
            params,
            responseType: 'blob'
        })

        const url = window.URL.createObjectURL(new Blob([response.data]))
        const link = document.createElement('a')
        link.href = url
        link.setAttribute('download', `form-submissions-${new Date().toISOString().split('T')[0]}.csv`)
        document.body.appendChild(link)
        link.click()
        link.remove()
    } catch (error) {
        console.error('Export failed:', error)
        alert('Failed to export submissions')
    }
}

const applyFilters = () => {
    fetchSubmissions(1)
}

const resetFilters = () => {
    filters.value = { page_id: '', is_read: '', from_date: '', to_date: '' }
    fetchSubmissions(1)
}

const formatDate = (d) => d ? new Date(d).toLocaleString('en-IN', {
    day: '2-digit', month: 'short', year: 'numeric',
    hour: '2-digit', minute: '2-digit'
}) : '-'

const getFieldLabel = (key) => {
    const labels = {
        name: 'Name',
        email: 'Email',
        phone: 'Phone',
        message: 'Message',
        subject: 'Subject',
        company: 'Company'
    }
    return labels[key] || key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())
}

// Lifecycle
onMounted(() => {
    fetchSubmissions()
    fetchPages()
})
</script>

<template>
    <div class="p-6">
        <!-- Header -->
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Form Submissions</h1>
                <p class="text-gray-600">
                    View and manage contact form submissions
                    <span v-if="unreadCount > 0" class="ml-2 bg-red-100 text-red-600 text-xs px-2 py-0.5 rounded-full">
                        {{ unreadCount }} unread
                    </span>
                </p>
            </div>
            <button
                @click="exportToCsv"
                class="px-4 py-2 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 flex items-center gap-2"
            >
                <i class="bi bi-download"></i>
                Export CSV
            </button>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-lg shadow-sm p-4 mb-4">
            <div class="grid grid-cols-5 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Page</label>
                    <select v-model="filters.page_id" class="w-full px-3 py-2 border rounded-lg text-sm">
                        <option value="">All Pages</option>
                        <option v-for="page in pages" :key="page.id" :value="page.id">
                            {{ page.title }}
                        </option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <select v-model="filters.is_read" class="w-full px-3 py-2 border rounded-lg text-sm">
                        <option value="">All</option>
                        <option value="0">Unread</option>
                        <option value="1">Read</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">From Date</label>
                    <input type="date" v-model="filters.from_date" class="w-full px-3 py-2 border rounded-lg text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">To Date</label>
                    <input type="date" v-model="filters.to_date" class="w-full px-3 py-2 border rounded-lg text-sm">
                </div>
                <div class="flex items-end gap-2">
                    <button @click="applyFilters" class="px-4 py-2 bg-amber-600 text-white rounded-lg hover:bg-amber-700">
                        Apply
                    </button>
                    <button v-if="hasActiveFilters" @click="resetFilters" class="px-4 py-2 border rounded-lg hover:bg-gray-50">
                        Reset
                    </button>
                </div>
            </div>
        </div>

        <!-- Submissions Table -->
        <div class="bg-white rounded-lg shadow-sm overflow-hidden">
            <table class="w-full">
                <thead class="bg-gray-50 border-b">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Submitted</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Page</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Preview</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <tr v-if="loading">
                        <td colspan="5" class="px-4 py-8 text-center text-gray-500">
                            <i class="bi bi-arrow-repeat animate-spin text-2xl"></i>
                            <p class="mt-2">Loading...</p>
                        </td>
                    </tr>
                    <tr v-else-if="submissions.length === 0">
                        <td colspan="5" class="px-4 py-8 text-center text-gray-500">
                            <i class="bi bi-inbox text-3xl mb-2"></i>
                            <p>No submissions found</p>
                        </td>
                    </tr>
                    <tr
                        v-else
                        v-for="submission in submissions"
                        :key="submission.id"
                        :class="[
                            'hover:bg-gray-50 cursor-pointer transition-colors',
                            !submission.is_read && 'bg-amber-50'
                        ]"
                        @click="viewSubmission(submission)"
                    >
                        <td class="px-4 py-3">
                            <span
                                :class="[
                                    'inline-flex items-center px-2 py-0.5 rounded text-xs font-medium',
                                    submission.is_read ? 'bg-gray-100 text-gray-600' : 'bg-amber-100 text-amber-700'
                                ]"
                            >
                                <i :class="['bi me-1', submission.is_read ? 'bi-envelope-open' : 'bi-envelope-fill']"></i>
                                {{ submission.is_read ? 'Read' : 'New' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-600">
                            {{ formatDate(submission.created_at) }}
                        </td>
                        <td class="px-4 py-3">
                            <span class="text-sm text-gray-800">{{ submission.page?.title || 'Unknown Page' }}</span>
                        </td>
                        <td class="px-4 py-3">
                            <div class="text-sm text-gray-600 truncate max-w-xs">
                                <template v-if="submission.form_data">
                                    {{ submission.form_data.name || submission.form_data.email || Object.values(submission.form_data)[0] || 'No preview' }}
                                </template>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <button
                                @click.stop="deleteSubmission(submission.id)"
                                class="p-1.5 text-red-500 hover:bg-red-50 rounded"
                                title="Delete"
                            >
                                <i class="bi bi-trash"></i>
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>

            <!-- Pagination -->
            <div v-if="pagination.last_page > 1" class="px-4 py-3 border-t flex items-center justify-between">
                <p class="text-sm text-gray-600">
                    Showing {{ (pagination.current_page - 1) * pagination.per_page + 1 }} to
                    {{ Math.min(pagination.current_page * pagination.per_page, pagination.total) }} of
                    {{ pagination.total }} submissions
                </p>
                <div class="flex gap-1">
                    <button
                        v-for="p in pagination.last_page"
                        :key="p"
                        @click="fetchSubmissions(p)"
                        :class="[
                            'px-3 py-1 rounded text-sm',
                            p === pagination.current_page
                                ? 'bg-amber-600 text-white'
                                : 'bg-gray-100 hover:bg-gray-200'
                        ]"
                    >
                        {{ p }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Detail Modal -->
        <div
            v-if="showDetailModal"
            class="fixed inset-0 bg-black/50 flex items-center justify-center z-50"
            @click.self="showDetailModal = false"
        >
            <div class="bg-white rounded-xl shadow-xl w-full max-w-2xl max-h-[80vh] overflow-hidden">
                <div class="p-4 border-b flex items-center justify-between">
                    <h3 class="font-semibold text-gray-800">Submission Details</h3>
                    <button @click="showDetailModal = false" class="p-1 hover:bg-gray-100 rounded">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
                <div class="p-6 overflow-y-auto max-h-[60vh]">
                    <div v-if="selectedSubmission" class="space-y-4">
                        <!-- Meta Info -->
                        <div class="grid grid-cols-2 gap-4 pb-4 border-b">
                            <div>
                                <label class="text-xs text-gray-500 uppercase">Page</label>
                                <p class="text-sm font-medium">{{ selectedSubmission.page?.title || 'Unknown' }}</p>
                            </div>
                            <div>
                                <label class="text-xs text-gray-500 uppercase">Submitted</label>
                                <p class="text-sm">{{ formatDate(selectedSubmission.created_at) }}</p>
                            </div>
                            <div>
                                <label class="text-xs text-gray-500 uppercase">IP Address</label>
                                <p class="text-sm font-mono">{{ selectedSubmission.ip_address || 'N/A' }}</p>
                            </div>
                            <div>
                                <label class="text-xs text-gray-500 uppercase">Status</label>
                                <p class="text-sm">
                                    <span :class="selectedSubmission.is_read ? 'text-gray-600' : 'text-amber-600 font-medium'">
                                        {{ selectedSubmission.is_read ? `Read on ${formatDate(selectedSubmission.read_at)}` : 'Unread' }}
                                    </span>
                                </p>
                            </div>
                        </div>

                        <!-- Form Data -->
                        <div>
                            <h4 class="text-sm font-semibold text-gray-700 mb-3">Form Data</h4>
                            <div class="space-y-3">
                                <div
                                    v-for="(value, key) in selectedSubmission.form_data"
                                    :key="key"
                                    class="bg-gray-50 rounded-lg p-3"
                                >
                                    <label class="text-xs text-gray-500 uppercase">{{ getFieldLabel(key) }}</label>
                                    <p class="text-sm text-gray-800 mt-1 whitespace-pre-wrap">{{ value || '-' }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="p-4 border-t bg-gray-50 flex justify-between">
                    <button
                        @click="deleteSubmission(selectedSubmission?.id)"
                        class="px-4 py-2 text-red-600 hover:bg-red-50 rounded-lg flex items-center gap-2"
                    >
                        <i class="bi bi-trash"></i>
                        Delete
                    </button>
                    <button
                        @click="showDetailModal = false"
                        class="px-4 py-2 bg-gray-200 rounded-lg hover:bg-gray-300"
                    >
                        Close
                    </button>
                </div>
            </div>
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
