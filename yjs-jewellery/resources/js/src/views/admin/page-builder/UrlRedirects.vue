<script setup>
import { ref, computed, onMounted } from 'vue'
import axiosAdmin from '@/plugins/axiosAdmin'

// State
const loading = ref(true)
const redirects = ref([])
const pagination = ref({ current_page: 1, last_page: 1, total: 0, per_page: 20 })
const searchQuery = ref('')
const showCreateModal = ref(false)
const editingRedirect = ref(null)

// Form
const form = ref({
    old_url: '',
    new_url: '',
    type: '301'
})
const formErrors = ref({})
const saving = ref(false)

// Methods
const fetchRedirects = async (page = 1) => {
    loading.value = true
    try {
        const params = { page, per_page: pagination.value.per_page }
        if (searchQuery.value) {
            params.search = searchQuery.value
        }

        const { data } = await axiosAdmin.get('/cms/redirects', { params })
        if (data.success) {
            redirects.value = data.data
            if (data.pagination) {
                pagination.value = data.pagination
            }
        }
    } catch (error) {
        console.error('Error fetching redirects:', error)
    } finally {
        loading.value = false
    }
}

const openCreateModal = () => {
    form.value = { old_url: '', new_url: '', type: '301' }
    formErrors.value = {}
    editingRedirect.value = null
    showCreateModal.value = true
}

const openEditModal = (redirect) => {
    form.value = {
        old_url: redirect.old_url,
        new_url: redirect.new_url,
        type: redirect.type
    }
    formErrors.value = {}
    editingRedirect.value = redirect
    showCreateModal.value = true
}

const saveRedirect = async () => {
    formErrors.value = {}

    // Validation
    if (!form.value.old_url) {
        formErrors.value.old_url = 'Old URL is required'
    }
    if (!form.value.new_url) {
        formErrors.value.new_url = 'New URL is required'
    }
    if (Object.keys(formErrors.value).length > 0) return

    saving.value = true
    try {
        let response
        if (editingRedirect.value) {
            response = await axiosAdmin.put(`/cms/redirects/${editingRedirect.value.id}`, form.value)
        } else {
            response = await axiosAdmin.post('/cms/redirects', form.value)
        }

        if (response.data.success) {
            showCreateModal.value = false
            fetchRedirects(pagination.value.current_page)
        }
    } catch (error) {
        if (error.response?.data?.errors) {
            formErrors.value = error.response.data.errors
        } else {
            console.error('Error saving redirect:', error)
            alert('Failed to save redirect')
        }
    } finally {
        saving.value = false
    }
}

const deleteRedirect = async (id) => {
    if (!confirm('Are you sure you want to delete this redirect?')) return

    try {
        const { data } = await axiosAdmin.delete(`/cms/redirects/${id}`)
        if (data.success) {
            redirects.value = redirects.value.filter(r => r.id !== id)
            pagination.value.total--
        }
    } catch (error) {
        console.error('Error deleting redirect:', error)
    }
}

const toggleActive = async (redirect) => {
    try {
        const { data } = await axiosAdmin.put(`/cms/redirects/${redirect.id}`, {
            is_active: !redirect.is_active
        })
        if (data.success) {
            redirect.is_active = !redirect.is_active
        }
    } catch (error) {
        console.error('Error toggling redirect:', error)
    }
}

const formatDate = (d) => d ? new Date(d).toLocaleDateString('en-IN', {
    day: '2-digit', month: 'short', year: 'numeric'
}) : '-'

// Search with debounce
let searchTimeout = null
const onSearch = () => {
    clearTimeout(searchTimeout)
    searchTimeout = setTimeout(() => fetchRedirects(1), 500)
}

// Lifecycle
onMounted(() => {
    fetchRedirects()
})
</script>

<template>
    <div class="p-6">
        <!-- Header -->
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">URL Redirects</h1>
                <p class="text-gray-600">Manage 301 and 302 redirects for SEO and broken links</p>
            </div>
            <button
                @click="openCreateModal"
                class="px-4 py-2 bg-amber-600 text-white rounded-lg hover:bg-amber-700 flex items-center gap-2"
            >
                <i class="bi bi-plus-lg"></i>
                Add Redirect
            </button>
        </div>

        <!-- Search -->
        <div class="bg-white rounded-lg shadow-sm p-4 mb-4">
            <div class="relative">
                <input
                    v-model="searchQuery"
                    @input="onSearch"
                    type="text"
                    placeholder="Search redirects..."
                    class="w-full pl-10 pr-4 py-2 border rounded-lg"
                >
                <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
            </div>
        </div>

        <!-- Redirects Table -->
        <div class="bg-white rounded-lg shadow-sm overflow-hidden">
            <table class="w-full">
                <thead class="bg-gray-50 border-b">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Old URL</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">New URL</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 uppercase">Type</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 uppercase">Hits</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 uppercase">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Created</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <tr v-if="loading">
                        <td colspan="7" class="px-4 py-8 text-center text-gray-500">
                            <i class="bi bi-arrow-repeat animate-spin text-2xl"></i>
                            <p class="mt-2">Loading...</p>
                        </td>
                    </tr>
                    <tr v-else-if="redirects.length === 0">
                        <td colspan="7" class="px-4 py-8 text-center text-gray-500">
                            <i class="bi bi-arrow-left-right text-3xl mb-2"></i>
                            <p>No redirects found</p>
                        </td>
                    </tr>
                    <tr
                        v-else
                        v-for="redirect in redirects"
                        :key="redirect.id"
                        class="hover:bg-gray-50"
                    >
                        <td class="px-4 py-3">
                            <code class="text-sm bg-gray-100 px-2 py-0.5 rounded text-red-600">
                                {{ redirect.old_url }}
                            </code>
                        </td>
                        <td class="px-4 py-3">
                            <code class="text-sm bg-gray-100 px-2 py-0.5 rounded text-green-600">
                                {{ redirect.new_url }}
                            </code>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span
                                :class="[
                                    'px-2 py-0.5 rounded text-xs font-medium',
                                    redirect.type === '301' ? 'bg-blue-100 text-blue-700' : 'bg-purple-100 text-purple-700'
                                ]"
                            >
                                {{ redirect.type }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center text-sm text-gray-600">
                            {{ redirect.hits || 0 }}
                        </td>
                        <td class="px-4 py-3 text-center">
                            <button
                                @click="toggleActive(redirect)"
                                :class="[
                                    'relative inline-flex h-5 w-9 rounded-full transition-colors',
                                    redirect.is_active ? 'bg-green-500' : 'bg-gray-300'
                                ]"
                            >
                                <span
                                    :class="[
                                        'inline-block h-4 w-4 transform rounded-full bg-white shadow transition-transform mt-0.5',
                                        redirect.is_active ? 'translate-x-4 ml-0.5' : 'translate-x-0.5'
                                    ]"
                                ></span>
                            </button>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-600">
                            {{ formatDate(redirect.created_at) }}
                        </td>
                        <td class="px-4 py-3 text-right">
                            <button
                                @click="openEditModal(redirect)"
                                class="p-1.5 text-gray-500 hover:bg-gray-100 rounded me-1"
                                title="Edit"
                            >
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button
                                @click="deleteRedirect(redirect.id)"
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
                    {{ pagination.total }} redirects
                </p>
                <div class="flex gap-1">
                    <button
                        v-for="p in pagination.last_page"
                        :key="p"
                        @click="fetchRedirects(p)"
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

        <!-- Info Box -->
        <div class="mt-4 p-4 bg-blue-50 border border-blue-200 rounded-lg">
            <h4 class="text-sm font-semibold text-blue-800 mb-2">
                <i class="bi bi-info-circle me-1"></i>
                Redirect Types
            </h4>
            <div class="text-sm text-blue-700 space-y-1">
                <p><strong>301 (Permanent)</strong> - Use when a page has permanently moved. Passes SEO value to the new URL.</p>
                <p><strong>302 (Temporary)</strong> - Use for temporary redirects. Does not pass SEO value.</p>
            </div>
        </div>

        <!-- Create/Edit Modal -->
        <div
            v-if="showCreateModal"
            class="fixed inset-0 bg-black/50 flex items-center justify-center z-50"
            @click.self="showCreateModal = false"
        >
            <div class="bg-white rounded-xl shadow-xl w-full max-w-lg">
                <div class="p-4 border-b flex items-center justify-between">
                    <h3 class="font-semibold text-gray-800">
                        {{ editingRedirect ? 'Edit Redirect' : 'Create Redirect' }}
                    </h3>
                    <button @click="showCreateModal = false" class="p-1 hover:bg-gray-100 rounded">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
                <div class="p-6 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Old URL</label>
                        <input
                            v-model="form.old_url"
                            type="text"
                            placeholder="/old-page"
                            class="w-full px-3 py-2 border rounded-lg"
                            :class="formErrors.old_url && 'border-red-500'"
                        >
                        <p v-if="formErrors.old_url" class="text-red-500 text-xs mt-1">{{ formErrors.old_url }}</p>
                        <p class="text-xs text-gray-500 mt-1">The URL path to redirect from (e.g., /old-page)</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">New URL</label>
                        <input
                            v-model="form.new_url"
                            type="text"
                            placeholder="/new-page or https://..."
                            class="w-full px-3 py-2 border rounded-lg"
                            :class="formErrors.new_url && 'border-red-500'"
                        >
                        <p v-if="formErrors.new_url" class="text-red-500 text-xs mt-1">{{ formErrors.new_url }}</p>
                        <p class="text-xs text-gray-500 mt-1">The destination URL (relative or absolute)</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Redirect Type</label>
                        <select v-model="form.type" class="w-full px-3 py-2 border rounded-lg">
                            <option value="301">301 - Permanent Redirect</option>
                            <option value="302">302 - Temporary Redirect</option>
                        </select>
                    </div>
                </div>
                <div class="p-4 border-t bg-gray-50 flex justify-end gap-2">
                    <button
                        @click="showCreateModal = false"
                        class="px-4 py-2 border rounded-lg hover:bg-gray-100"
                    >
                        Cancel
                    </button>
                    <button
                        @click="saveRedirect"
                        :disabled="saving"
                        class="px-4 py-2 bg-amber-600 text-white rounded-lg hover:bg-amber-700 disabled:opacity-50"
                    >
                        {{ saving ? 'Saving...' : (editingRedirect ? 'Update' : 'Create') }}
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
