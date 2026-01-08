<script setup>
import { ref, onMounted, watch, computed } from 'vue'
import axiosAdmin from '@/plugins/axiosAdmin'
import { useRouter } from 'vue-router'

const router = useRouter()

// State
const loading = ref(true)
const pages = ref([])
const templates = ref([])
const pagination = ref({ current_page: 1, last_page: 1, total: 0, per_page: 15 })
const showFilters = ref(false)
const showCreateModal = ref(false)
const selectedPages = ref([])

// Filters
const filters = ref({
    search: '',
    type: '',
    status: '',
    template_id: ''
})

// New page form
const newPageForm = ref({
    title: '',
    slug: '',
    type: 'static',
    template_id: null
})

// Page types
const pageTypes = [
    { value: 'homepage', label: 'Homepage', icon: 'bi-house' },
    { value: 'static', label: 'Static Page', icon: 'bi-file-text' },
    { value: 'landing', label: 'Landing Page', icon: 'bi-bullseye' },
    { value: 'category', label: 'Category Page', icon: 'bi-grid' }
]

const statusOptions = [
    { value: 'draft', label: 'Draft', class: 'bg-gray-100 text-gray-800' },
    { value: 'published', label: 'Published', class: 'bg-green-100 text-green-800' },
    { value: 'scheduled', label: 'Scheduled', class: 'bg-blue-100 text-blue-800' }
]

// Computed
const allSelected = computed(() =>
    pages.value.length > 0 && selectedPages.value.length === pages.value.length
)

const hasActiveFilters = computed(() =>
    filters.value.search || filters.value.type || filters.value.status || filters.value.template_id
)

// Methods
const fetchPages = async (page = 1) => {
    loading.value = true
    try {
        const params = {
            page,
            per_page: pagination.value.per_page,
            ...filters.value
        }
        const response = await axiosAdmin.get('/cms/pages', { params })
        if (response.data.success) {
            pages.value = response.data.data.data || response.data.data
            if (response.data.data.current_page) {
                pagination.value = {
                    current_page: response.data.data.current_page,
                    last_page: response.data.data.last_page,
                    total: response.data.data.total,
                    per_page: response.data.data.per_page
                }
            }
        }
    } catch (error) {
        console.error('Error fetching pages:', error)
    } finally {
        loading.value = false
    }
}

const fetchTemplates = async () => {
    try {
        const response = await axiosAdmin.get('/cms/templates')
        if (response.data.success) {
            templates.value = response.data.data || []
        }
    } catch (error) {
        console.error('Error fetching templates:', error)
    }
}

const createPage = async () => {
    if (!newPageForm.value.title.trim()) {
        alert('Please enter a page title')
        return
    }

    try {
        const response = await axiosAdmin.post('/cms/pages', {
            ...newPageForm.value,
            blocks: [],
            page_settings: {},
            seo: {},
            status: 'draft'
        })

        if (response.data.success) {
            showCreateModal.value = false
            // Navigate to page builder
            router.push({ name: 'admin.page-builder.edit', params: { id: response.data.data.id } })
        }
    } catch (error) {
        alert(error.response?.data?.message || 'Failed to create page')
    }
}

const editPage = (page) => {
    router.push({ name: 'admin.page-builder.edit', params: { id: page.id } })
}

const duplicatePage = async (page) => {
    if (!confirm(`Duplicate "${page.title}"?`)) return

    try {
        const response = await axiosAdmin.post(`/cms/pages/${page.id}/duplicate`)
        if (response.data.success) {
            fetchPages(pagination.value.current_page)
        }
    } catch (error) {
        alert(error.response?.data?.message || 'Failed to duplicate page')
    }
}

const deletePage = async (page) => {
    if (!confirm(`Delete "${page.title}"? This cannot be undone.`)) return

    try {
        await axiosAdmin.delete(`/cms/pages/${page.id}`)
        fetchPages(pagination.value.current_page)
    } catch (error) {
        alert(error.response?.data?.message || 'Failed to delete page')
    }
}

const bulkDelete = async () => {
    if (selectedPages.value.length === 0) return
    if (!confirm(`Delete ${selectedPages.value.length} pages? This cannot be undone.`)) return

    try {
        await Promise.all(selectedPages.value.map(id => axiosAdmin.delete(`/cms/pages/${id}`)))
        selectedPages.value = []
        fetchPages(pagination.value.current_page)
    } catch (error) {
        alert('Failed to delete some pages')
    }
}

const toggleSelectAll = () => {
    if (allSelected.value) {
        selectedPages.value = []
    } else {
        selectedPages.value = pages.value.map(p => p.id)
    }
}

const toggleSelect = (id) => {
    const index = selectedPages.value.indexOf(id)
    if (index > -1) {
        selectedPages.value.splice(index, 1)
    } else {
        selectedPages.value.push(id)
    }
}

const applyFilters = () => {
    fetchPages(1)
    showFilters.value = false
}

const resetFilters = () => {
    filters.value = { search: '', type: '', status: '', template_id: '' }
    fetchPages(1)
}

const openCreateModal = () => {
    newPageForm.value = { title: '', slug: '', type: 'static', template_id: null }
    showCreateModal.value = true
}

const generateSlug = () => {
    newPageForm.value.slug = newPageForm.value.title
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/(^-|-$)/g, '')
}

// Helpers
const formatDate = (d) => d ? new Date(d).toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric' }) : '-'

const getStatusClass = (status) => {
    const option = statusOptions.find(s => s.value === status)
    return option ? option.class : 'bg-gray-100 text-gray-800'
}

const getTypeIcon = (type) => {
    const option = pageTypes.find(t => t.value === type)
    return option ? option.icon : 'bi-file'
}

const getTypeLabel = (type) => {
    const option = pageTypes.find(t => t.value === type)
    return option ? option.label : type
}

// Debounced search
let searchTimeout = null
watch(() => filters.value.search, () => {
    clearTimeout(searchTimeout)
    searchTimeout = setTimeout(() => fetchPages(1), 500)
})

// Lifecycle
onMounted(() => {
    fetchPages()
    fetchTemplates()
})
</script>

<template>
    <div class="p-6">
        <!-- Header -->
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Page Builder</h1>
                <p class="text-gray-600">Create and manage dynamic pages with drag-and-drop builder</p>
            </div>
            <button @click="openCreateModal" class="px-4 py-2 bg-amber-600 text-white rounded-lg hover:bg-amber-700 flex items-center space-x-2">
                <i class="bi bi-plus-lg"></i>
                <span>Create Page</span>
            </button>
        </div>

        <!-- Global Sections -->
        <div class="bg-white rounded-lg shadow-sm p-4 mb-4">
            <h3 class="text-sm font-semibold text-gray-700 mb-3">Global Elements</h3>
            <div class="flex gap-3">
                <router-link
                    to="/admin/page-builder/global/header"
                    class="flex-1 p-4 border-2 border-dashed rounded-lg hover:border-amber-500 hover:bg-amber-50 transition-colors group"
                >
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-gray-100 rounded-lg flex items-center justify-center group-hover:bg-amber-100">
                            <i class="bi bi-layout-text-window-reverse text-xl text-gray-600 group-hover:text-amber-600"></i>
                        </div>
                        <div>
                            <h4 class="font-medium text-gray-800">Header</h4>
                            <p class="text-xs text-gray-500">Edit site header (logo, navigation, search)</p>
                        </div>
                    </div>
                </router-link>
                <router-link
                    to="/admin/page-builder/global/footer"
                    class="flex-1 p-4 border-2 border-dashed rounded-lg hover:border-amber-500 hover:bg-amber-50 transition-colors group"
                >
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-gray-100 rounded-lg flex items-center justify-center group-hover:bg-amber-100">
                            <i class="bi bi-layout-text-window text-xl text-gray-600 group-hover:text-amber-600"></i>
                        </div>
                        <div>
                            <h4 class="font-medium text-gray-800">Footer</h4>
                            <p class="text-xs text-gray-500">Edit site footer (links, social, newsletter)</p>
                        </div>
                    </div>
                </router-link>
            </div>
        </div>

        <!-- Toolbar -->
        <div class="bg-white rounded-lg shadow-sm p-4 mb-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <!-- Search -->
                    <div class="relative">
                        <input
                            v-model="filters.search"
                            type="text"
                            placeholder="Search pages..."
                            class="pl-10 pr-4 py-2 border rounded-lg w-64 focus:ring-2 focus:ring-amber-500 focus:border-amber-500"
                        >
                        <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                    </div>

                    <!-- Filter Toggle -->
                    <button
                        @click="showFilters = !showFilters"
                        :class="['px-3 py-2 border rounded-lg flex items-center space-x-2', hasActiveFilters ? 'border-amber-500 text-amber-600' : 'hover:bg-gray-50']"
                    >
                        <i class="bi bi-funnel"></i>
                        <span>Filters</span>
                        <span v-if="hasActiveFilters" class="bg-amber-500 text-white text-xs px-1.5 py-0.5 rounded-full">!</span>
                    </button>
                </div>

                <div class="flex items-center space-x-2">
                    <!-- Bulk Actions -->
                    <button
                        v-if="selectedPages.length > 0"
                        @click="bulkDelete"
                        class="px-3 py-2 text-red-600 border border-red-200 rounded-lg hover:bg-red-50"
                    >
                        <i class="bi bi-trash me-1"></i>
                        Delete ({{ selectedPages.length }})
                    </button>

                    <!-- Page count -->
                    <span class="text-sm text-gray-500">{{ pagination.total }} pages</span>
                </div>
            </div>

            <!-- Filters Panel -->
            <div v-if="showFilters" class="mt-4 pt-4 border-t grid grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Page Type</label>
                    <select v-model="filters.type" class="w-full px-3 py-2 border rounded-lg">
                        <option value="">All Types</option>
                        <option v-for="type in pageTypes" :key="type.value" :value="type.value">{{ type.label }}</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <select v-model="filters.status" class="w-full px-3 py-2 border rounded-lg">
                        <option value="">All Status</option>
                        <option v-for="status in statusOptions" :key="status.value" :value="status.value">{{ status.label }}</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Template</label>
                    <select v-model="filters.template_id" class="w-full px-3 py-2 border rounded-lg">
                        <option value="">All Templates</option>
                        <option v-for="template in templates" :key="template.id" :value="template.id">{{ template.name }}</option>
                    </select>
                </div>
                <div class="flex items-end space-x-2">
                    <button @click="applyFilters" class="px-4 py-2 bg-amber-600 text-white rounded-lg hover:bg-amber-700">Apply</button>
                    <button @click="resetFilters" class="px-4 py-2 border rounded-lg hover:bg-gray-50">Reset</button>
                </div>
            </div>
        </div>

        <!-- Pages Table -->
        <div class="bg-white rounded-lg shadow-sm overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="w-12 px-4 py-3">
                            <input
                                type="checkbox"
                                :checked="allSelected"
                                @change="toggleSelectAll"
                                class="rounded border-gray-300 text-amber-600 focus:ring-amber-500"
                            >
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Page</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Updated</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <tr v-if="loading">
                        <td colspan="6" class="px-6 py-12 text-center">
                            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-amber-600 mx-auto"></div>
                            <p class="mt-2 text-gray-500">Loading pages...</p>
                        </td>
                    </tr>
                    <tr v-else-if="pages.length === 0">
                        <td colspan="6" class="px-6 py-12 text-center">
                            <i class="bi bi-file-earmark-x text-4xl text-gray-300"></i>
                            <p class="mt-2 text-gray-500">No pages found</p>
                            <button @click="openCreateModal" class="mt-4 text-amber-600 hover:text-amber-700">
                                Create your first page
                            </button>
                        </td>
                    </tr>
                    <tr v-else v-for="page in pages" :key="page.id" class="hover:bg-gray-50">
                        <td class="px-4 py-4">
                            <input
                                type="checkbox"
                                :checked="selectedPages.includes(page.id)"
                                @change="toggleSelect(page.id)"
                                class="rounded border-gray-300 text-amber-600 focus:ring-amber-500"
                            >
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center">
                                <div>
                                    <div class="font-medium text-gray-900">{{ page.title }}</div>
                                    <div class="text-sm text-gray-500">/{{ page.slug }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <span class="inline-flex items-center text-sm text-gray-600">
                                <i :class="['bi', getTypeIcon(page.type), 'me-1']"></i>
                                {{ getTypeLabel(page.type) }}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <span :class="['px-2 py-1 text-xs rounded-full', getStatusClass(page.status)]">
                                {{ page.status }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500">
                            {{ formatDate(page.updated_at) }}
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end space-x-2">
                                <button
                                    @click="editPage(page)"
                                    class="p-2 text-amber-600 hover:bg-amber-50 rounded-lg"
                                    title="Edit in Page Builder"
                                >
                                    <i class="bi bi-pencil-square"></i>
                                </button>
                                <button
                                    @click="duplicatePage(page)"
                                    class="p-2 text-gray-600 hover:bg-gray-100 rounded-lg"
                                    title="Duplicate"
                                >
                                    <i class="bi bi-copy"></i>
                                </button>
                                <a
                                    :href="`/api/admin/cms/pages/${page.id}/preview`"
                                    target="_blank"
                                    class="p-2 text-gray-600 hover:bg-gray-100 rounded-lg"
                                    title="Preview"
                                >
                                    <i class="bi bi-eye"></i>
                                </a>
                                <button
                                    @click="deletePage(page)"
                                    class="p-2 text-red-600 hover:bg-red-50 rounded-lg"
                                    title="Delete"
                                >
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>

            <!-- Pagination -->
            <div v-if="pagination.last_page > 1" class="px-6 py-4 border-t flex items-center justify-between">
                <div class="text-sm text-gray-500">
                    Showing {{ (pagination.current_page - 1) * pagination.per_page + 1 }} to
                    {{ Math.min(pagination.current_page * pagination.per_page, pagination.total) }} of
                    {{ pagination.total }} pages
                </div>
                <div class="flex items-center space-x-2">
                    <button
                        @click="fetchPages(pagination.current_page - 1)"
                        :disabled="pagination.current_page === 1"
                        :class="['px-3 py-1 border rounded', pagination.current_page === 1 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-50']"
                    >
                        Previous
                    </button>
                    <span class="px-3 py-1">{{ pagination.current_page }} / {{ pagination.last_page }}</span>
                    <button
                        @click="fetchPages(pagination.current_page + 1)"
                        :disabled="pagination.current_page === pagination.last_page"
                        :class="['px-3 py-1 border rounded', pagination.current_page === pagination.last_page ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-50']"
                    >
                        Next
                    </button>
                </div>
            </div>
        </div>

        <!-- Create Page Modal -->
        <div v-if="showCreateModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg shadow-xl w-full max-w-lg">
                <div class="p-6 border-b flex justify-between items-center">
                    <h2 class="text-xl font-bold">Create New Page</h2>
                    <button @click="showCreateModal = false" class="text-gray-500 hover:text-gray-700 text-2xl">&times;</button>
                </div>
                <div class="p-6 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Page Title *</label>
                        <input
                            v-model="newPageForm.title"
                            @blur="generateSlug"
                            type="text"
                            placeholder="Enter page title"
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500"
                        >
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">URL Slug</label>
                        <div class="flex items-center">
                            <span class="px-3 py-2 bg-gray-100 border border-r-0 rounded-l-lg text-gray-500">/</span>
                            <input
                                v-model="newPageForm.slug"
                                type="text"
                                placeholder="page-url"
                                class="flex-1 px-4 py-2 border rounded-r-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500"
                            >
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Page Type</label>
                        <div class="grid grid-cols-2 gap-3">
                            <label
                                v-for="type in pageTypes"
                                :key="type.value"
                                :class="[
                                    'flex items-center p-3 border rounded-lg cursor-pointer transition-colors',
                                    newPageForm.type === type.value ? 'border-amber-500 bg-amber-50' : 'hover:bg-gray-50'
                                ]"
                            >
                                <input
                                    type="radio"
                                    v-model="newPageForm.type"
                                    :value="type.value"
                                    class="sr-only"
                                >
                                <i :class="['bi', type.icon, 'text-lg me-2', newPageForm.type === type.value ? 'text-amber-600' : 'text-gray-400']"></i>
                                <span :class="newPageForm.type === type.value ? 'text-amber-700' : 'text-gray-700'">{{ type.label }}</span>
                            </label>
                        </div>
                    </div>
                    <div v-if="templates.length > 0">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Start from Template (optional)</label>
                        <select v-model="newPageForm.template_id" class="w-full px-4 py-2 border rounded-lg">
                            <option :value="null">Blank Page</option>
                            <option v-for="template in templates" :key="template.id" :value="template.id">{{ template.name }}</option>
                        </select>
                    </div>
                </div>
                <div class="p-6 border-t flex justify-end space-x-3">
                    <button @click="showCreateModal = false" class="px-4 py-2 border rounded-lg hover:bg-gray-50">Cancel</button>
                    <button @click="createPage" class="px-4 py-2 bg-amber-600 text-white rounded-lg hover:bg-amber-700">
                        Create & Edit
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
