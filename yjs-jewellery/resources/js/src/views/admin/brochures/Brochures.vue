<script setup>
import { ref, onMounted, watch } from 'vue'
import axios from 'axios'

const loading = ref(true)
const brochures = ref([])
const pagination = ref({ current_page: 1, last_page: 1, total: 0 })
const filters = ref({ search: '', category: '', status: '' })
const showModal = ref(false)
const editingBrochure = ref(null)
const form = ref({
    title: '',
    description: '',
    category: 'catalog',
    file_url: '',
    thumbnail_url: '',
    file_size: '',
    pages: '',
    year: new Date().getFullYear(),
    is_featured: false,
    status: 'active'
})

const categories = [
    { value: 'catalog', label: 'Product Catalog' },
    { value: 'collection', label: 'Collection Lookbook' },
    { value: 'price_list', label: 'Price List' },
    { value: 'company', label: 'Company Profile' },
    { value: 'promotional', label: 'Promotional Material' }
]

const fetchBrochures = async (page = 1) => {
    loading.value = true
    try {
        const response = await axios.get('/api/admin/brochures', { params: { page, ...filters.value } })
        if (response.data.success) {
            brochures.value = response.data.data.data || response.data.data || []
            pagination.value = response.data.data.meta || response.data.data
        }
    } catch (error) { console.error('Error:', error) }
    finally { loading.value = false }
}

const openCreateModal = () => {
    editingBrochure.value = null
    form.value = { title: '', description: '', category: 'catalog', file_url: '', thumbnail_url: '', file_size: '', pages: '', year: new Date().getFullYear(), is_featured: false, status: 'active' }
    showModal.value = true
}

const openEditModal = (brochure) => {
    editingBrochure.value = brochure
    form.value = { ...brochure }
    showModal.value = true
}

const saveBrochure = async () => {
    try {
        if (editingBrochure.value) await axios.put(`/api/admin/brochures/${editingBrochure.value.id}`, form.value)
        else await axios.post('/api/admin/brochures', form.value)
        showModal.value = false
        fetchBrochures(pagination.value.current_page)
    } catch (error) { alert(error.response?.data?.message || 'Failed to save') }
}

const deleteBrochure = async (brochure) => {
    if (confirm('Delete this brochure?')) {
        await axios.delete(`/api/admin/brochures/${brochure.id}`)
        fetchBrochures(pagination.value.current_page)
    }
}

const toggleFeatured = async (brochure) => {
    try {
        await axios.patch(`/api/admin/brochures/${brochure.id}/toggle-featured`)
        fetchBrochures(pagination.value.current_page)
    } catch (error) { console.error('Error:', error) }
}

const downloadBrochure = (brochure) => {
    if (brochure.file_url) {
        window.open(brochure.file_url, '_blank')
    }
}

const formatDate = (d) => d ? new Date(d).toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric' }) : '-'
const formatFileSize = (size) => {
    if (!size) return '-'
    if (size < 1024) return size + ' B'
    if (size < 1024 * 1024) return (size / 1024).toFixed(1) + ' KB'
    return (size / (1024 * 1024)).toFixed(1) + ' MB'
}
const getCategoryLabel = (value) => categories.find(c => c.value === value)?.label || value
const getStatusClass = (s) => s === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'

let timeout = null
watch(() => filters.value.search, () => { clearTimeout(timeout); timeout = setTimeout(() => fetchBrochures(1), 500) })
onMounted(() => fetchBrochures())
</script>

<template>
    <div class="p-6">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Brochures & Catalogs</h1>
                <p class="text-gray-600">Manage downloadable brochures and product catalogs</p>
            </div>
            <button @click="openCreateModal" class="px-4 py-2 bg-amber-600 text-white rounded-lg hover:bg-amber-700 flex items-center space-x-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                <span>Add Brochure</span>
            </button>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-white rounded-lg shadow-sm p-4">
                <div class="text-2xl font-bold text-gray-800">{{ brochures.length }}</div>
                <div class="text-gray-600 text-sm">Total Brochures</div>
            </div>
            <div class="bg-white rounded-lg shadow-sm p-4">
                <div class="text-2xl font-bold text-amber-600">{{ brochures.filter(b => b.is_featured).length }}</div>
                <div class="text-gray-600 text-sm">Featured</div>
            </div>
            <div class="bg-white rounded-lg shadow-sm p-4">
                <div class="text-2xl font-bold text-green-600">{{ brochures.filter(b => b.status === 'active').length }}</div>
                <div class="text-gray-600 text-sm">Active</div>
            </div>
            <div class="bg-white rounded-lg shadow-sm p-4">
                <div class="text-2xl font-bold text-blue-600">{{ brochures.filter(b => b.category === 'catalog').length }}</div>
                <div class="text-gray-600 text-sm">Product Catalogs</div>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-lg shadow-sm p-4 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <input v-model="filters.search" type="text" placeholder="Search brochures..." class="px-4 py-2 border rounded-lg">
                <select v-model="filters.category" @change="fetchBrochures(1)" class="px-4 py-2 border rounded-lg">
                    <option value="">All Categories</option>
                    <option v-for="cat in categories" :key="cat.value" :value="cat.value">{{ cat.label }}</option>
                </select>
                <select v-model="filters.status" @change="fetchBrochures(1)" class="px-4 py-2 border rounded-lg">
                    <option value="">All Status</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
                <button @click="filters = { search: '', category: '', status: '' }; fetchBrochures(1)" class="px-4 py-2 border rounded-lg hover:bg-gray-50">Reset</button>
            </div>
        </div>

        <div v-if="loading" class="text-center py-8">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-amber-600 mx-auto"></div>
        </div>

        <div v-else-if="brochures.length === 0" class="bg-white rounded-lg shadow-sm p-8 text-center text-gray-500">
            <svg class="w-16 h-16 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
            </svg>
            <p>No brochures found. Add your first brochure to get started.</p>
        </div>

        <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <div v-for="brochure in brochures" :key="brochure.id" class="bg-white rounded-lg shadow-sm overflow-hidden group">
                <div class="relative">
                    <img :src="brochure.thumbnail_url || '/images/brochure-placeholder.jpg'" :alt="brochure.title" class="w-full h-48 object-cover">
                    <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-50 transition-all flex items-center justify-center opacity-0 group-hover:opacity-100">
                        <button @click="downloadBrochure(brochure)" class="bg-white text-gray-800 px-4 py-2 rounded-lg shadow flex items-center space-x-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                            </svg>
                            <span>Download</span>
                        </button>
                    </div>
                    <div class="absolute top-2 left-2 flex space-x-1">
                        <span :class="['px-2 py-1 text-xs rounded-full', getStatusClass(brochure.status)]">{{ brochure.status }}</span>
                        <span v-if="brochure.is_featured" class="px-2 py-1 text-xs rounded-full bg-amber-100 text-amber-800">Featured</span>
                    </div>
                </div>
                <div class="p-4">
                    <div class="text-xs text-gray-500 mb-1">{{ getCategoryLabel(brochure.category) }}</div>
                    <h3 class="font-semibold text-gray-800 mb-2 line-clamp-2">{{ brochure.title }}</h3>

                    <div class="flex items-center justify-between text-xs text-gray-500 mb-3">
                        <span v-if="brochure.pages">{{ brochure.pages }} pages</span>
                        <span v-if="brochure.file_size">{{ formatFileSize(brochure.file_size) }}</span>
                        <span>{{ brochure.year }}</span>
                    </div>

                    <div class="flex justify-between items-center pt-3 border-t">
                        <button @click="toggleFeatured(brochure)" :class="['text-sm', brochure.is_featured ? 'text-amber-600' : 'text-gray-400']">
                            <svg class="w-5 h-5" :fill="brochure.is_featured ? 'currentColor' : 'none'" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                            </svg>
                        </button>
                        <div class="flex space-x-2">
                            <button @click="openEditModal(brochure)" class="text-amber-600 hover:text-amber-900 text-sm">Edit</button>
                            <button @click="deleteBrochure(brochure)" class="text-red-600 hover:text-red-900 text-sm">Delete</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pagination -->
        <div v-if="pagination.last_page > 1" class="mt-6 flex justify-center">
            <nav class="flex space-x-2">
                <button v-for="page in pagination.last_page" :key="page" @click="fetchBrochures(page)" :class="['px-3 py-1 rounded', page === pagination.current_page ? 'bg-amber-600 text-white' : 'bg-white border hover:bg-gray-50']">
                    {{ page }}
                </button>
            </nav>
        </div>

        <!-- Create/Edit Modal -->
        <div v-if="showModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
                <div class="p-6 border-b flex justify-between items-center sticky top-0 bg-white">
                    <h2 class="text-xl font-bold">{{ editingBrochure ? 'Edit' : 'Add' }} Brochure</h2>
                    <button @click="showModal = false" class="text-gray-500 text-2xl">&times;</button>
                </div>
                <div class="p-6 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Title</label>
                        <input v-model="form.title" type="text" class="w-full px-4 py-2 border rounded-lg" placeholder="e.g., Summer Collection 2024">
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                            <select v-model="form.category" class="w-full px-4 py-2 border rounded-lg">
                                <option v-for="cat in categories" :key="cat.value" :value="cat.value">{{ cat.label }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Year</label>
                            <input v-model="form.year" type="number" class="w-full px-4 py-2 border rounded-lg" min="2000" max="2030">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">PDF File URL</label>
                        <input v-model="form.file_url" type="text" class="w-full px-4 py-2 border rounded-lg" placeholder="https://...">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Thumbnail Image URL</label>
                        <input v-model="form.thumbnail_url" type="text" class="w-full px-4 py-2 border rounded-lg" placeholder="https://...">
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Number of Pages</label>
                            <input v-model="form.pages" type="number" class="w-full px-4 py-2 border rounded-lg" min="1">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                            <select v-model="form.status" class="w-full px-4 py-2 border rounded-lg">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                        <textarea v-model="form.description" rows="3" class="w-full px-4 py-2 border rounded-lg" placeholder="Brief description of the brochure..."></textarea>
                    </div>
                    <div class="flex items-center">
                        <input v-model="form.is_featured" type="checkbox" id="is_featured" class="mr-2">
                        <label for="is_featured" class="text-sm text-gray-700">Mark as Featured</label>
                    </div>
                </div>
                <div class="p-6 border-t flex justify-end space-x-3">
                    <button @click="showModal = false" class="px-4 py-2 border rounded-lg">Cancel</button>
                    <button @click="saveBrochure" class="px-4 py-2 bg-amber-600 text-white rounded-lg hover:bg-amber-700">Save</button>
                </div>
            </div>
        </div>
    </div>
</template>
