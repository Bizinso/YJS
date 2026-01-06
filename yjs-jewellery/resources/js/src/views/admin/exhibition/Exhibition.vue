<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'

const loading = ref(true)
const exhibitions = ref([])
const showModal = ref(false)
const editingExhibition = ref(null)
const form = ref({
    name: '',
    description: '',
    venue: '',
    city: '',
    start_date: '',
    end_date: '',
    booth_number: '',
    organizer: '',
    website: '',
    banner_image: '',
    status: 'upcoming'
})

const fetchExhibitions = async () => {
    loading.value = true
    try {
        const response = await axios.get('/api/admin/exhibitions')
        if (response.data.success) exhibitions.value = response.data.data || []
    } catch (error) { console.error('Error:', error) }
    finally { loading.value = false }
}

const openCreateModal = () => {
    editingExhibition.value = null
    form.value = { name: '', description: '', venue: '', city: '', start_date: '', end_date: '', booth_number: '', organizer: '', website: '', banner_image: '', status: 'upcoming' }
    showModal.value = true
}

const openEditModal = (exhibition) => {
    editingExhibition.value = exhibition
    form.value = { ...exhibition }
    showModal.value = true
}

const saveExhibition = async () => {
    try {
        if (editingExhibition.value) await axios.put(`/api/admin/exhibitions/${editingExhibition.value.id}`, form.value)
        else await axios.post('/api/admin/exhibitions', form.value)
        showModal.value = false
        fetchExhibitions()
    } catch (error) { alert(error.response?.data?.message || 'Failed to save') }
}

const deleteExhibition = async (exhibition) => {
    if (confirm('Delete this exhibition?')) {
        await axios.delete(`/api/admin/exhibitions/${exhibition.id}`)
        fetchExhibitions()
    }
}

const formatDate = (d) => d ? new Date(d).toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric' }) : '-'
const getStatusClass = (s) => ({ upcoming: 'bg-blue-100 text-blue-800', ongoing: 'bg-green-100 text-green-800', completed: 'bg-gray-100 text-gray-800', cancelled: 'bg-red-100 text-red-800' })[s] || 'bg-gray-100 text-gray-800'
const isOngoing = (exhibition) => {
    const now = new Date()
    return new Date(exhibition.start_date) <= now && new Date(exhibition.end_date) >= now
}

onMounted(() => fetchExhibitions())
</script>

<template>
    <div class="p-6">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Exhibitions</h1>
                <p class="text-gray-600">Manage jewellery exhibitions and trade shows</p>
            </div>
            <button @click="openCreateModal" class="px-4 py-2 bg-amber-600 text-white rounded-lg hover:bg-amber-700 flex items-center space-x-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                <span>Add Exhibition</span>
            </button>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-white rounded-lg shadow-sm p-4">
                <div class="text-2xl font-bold text-gray-800">{{ exhibitions.length }}</div>
                <div class="text-gray-600 text-sm">Total Exhibitions</div>
            </div>
            <div class="bg-white rounded-lg shadow-sm p-4">
                <div class="text-2xl font-bold text-blue-600">{{ exhibitions.filter(e => e.status === 'upcoming').length }}</div>
                <div class="text-gray-600 text-sm">Upcoming</div>
            </div>
            <div class="bg-white rounded-lg shadow-sm p-4">
                <div class="text-2xl font-bold text-green-600">{{ exhibitions.filter(e => e.status === 'ongoing').length }}</div>
                <div class="text-gray-600 text-sm">Ongoing</div>
            </div>
            <div class="bg-white rounded-lg shadow-sm p-4">
                <div class="text-2xl font-bold text-gray-600">{{ exhibitions.filter(e => e.status === 'completed').length }}</div>
                <div class="text-gray-600 text-sm">Completed</div>
            </div>
        </div>

        <div v-if="loading" class="text-center py-8">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-amber-600 mx-auto"></div>
        </div>

        <div v-else-if="exhibitions.length === 0" class="bg-white rounded-lg shadow-sm p-8 text-center text-gray-500">
            <svg class="w-16 h-16 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
            </svg>
            <p>No exhibitions found. Add your first exhibition to get started.</p>
        </div>

        <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div v-for="exhibition in exhibitions" :key="exhibition.id" class="bg-white rounded-lg shadow-sm overflow-hidden">
                <div class="relative">
                    <img :src="exhibition.banner_image || '/images/exhibition-placeholder.jpg'" :alt="exhibition.name" class="w-full h-48 object-cover">
                    <span :class="['absolute top-2 right-2 px-2 py-1 text-xs rounded-full capitalize', getStatusClass(exhibition.status)]">
                        {{ exhibition.status }}
                    </span>
                </div>
                <div class="p-4">
                    <h3 class="font-semibold text-gray-800 mb-2">{{ exhibition.name }}</h3>

                    <div class="space-y-2 text-sm text-gray-500 mb-4">
                        <p class="flex items-center">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            {{ exhibition.venue }}, {{ exhibition.city }}
                        </p>
                        <p class="flex items-center">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            {{ formatDate(exhibition.start_date) }} - {{ formatDate(exhibition.end_date) }}
                        </p>
                        <p v-if="exhibition.booth_number" class="flex items-center">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"/>
                            </svg>
                            Booth: {{ exhibition.booth_number }}
                        </p>
                        <p v-if="exhibition.organizer" class="flex items-center">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5"/>
                            </svg>
                            {{ exhibition.organizer }}
                        </p>
                    </div>

                    <div class="flex justify-between items-center">
                        <a v-if="exhibition.website" :href="exhibition.website" target="_blank" class="text-amber-600 hover:text-amber-700 text-sm">
                            Visit Website
                        </a>
                        <div class="flex space-x-2">
                            <button @click="openEditModal(exhibition)" class="text-amber-600 hover:text-amber-900 text-sm">Edit</button>
                            <button @click="deleteExhibition(exhibition)" class="text-red-600 hover:text-red-900 text-sm">Delete</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Create/Edit Modal -->
        <div v-if="showModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
                <div class="p-6 border-b flex justify-between items-center sticky top-0 bg-white">
                    <h2 class="text-xl font-bold">{{ editingExhibition ? 'Edit' : 'Add' }} Exhibition</h2>
                    <button @click="showModal = false" class="text-gray-500 text-2xl">&times;</button>
                </div>
                <div class="p-6 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Exhibition Name</label>
                        <input v-model="form.name" type="text" class="w-full px-4 py-2 border rounded-lg" placeholder="e.g., India International Jewellery Show">
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Venue</label>
                            <input v-model="form.venue" type="text" class="w-full px-4 py-2 border rounded-lg" placeholder="e.g., Bombay Exhibition Centre">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">City</label>
                            <input v-model="form.city" type="text" class="w-full px-4 py-2 border rounded-lg" placeholder="e.g., Mumbai">
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Start Date</label>
                            <input v-model="form.start_date" type="date" class="w-full px-4 py-2 border rounded-lg">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">End Date</label>
                            <input v-model="form.end_date" type="date" class="w-full px-4 py-2 border rounded-lg">
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Booth Number</label>
                            <input v-model="form.booth_number" type="text" class="w-full px-4 py-2 border rounded-lg" placeholder="e.g., Hall A, Booth 23">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Organizer</label>
                            <input v-model="form.organizer" type="text" class="w-full px-4 py-2 border rounded-lg" placeholder="e.g., GJEPC">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Website URL</label>
                        <input v-model="form.website" type="url" class="w-full px-4 py-2 border rounded-lg" placeholder="https://...">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Banner Image URL</label>
                        <input v-model="form.banner_image" type="text" class="w-full px-4 py-2 border rounded-lg" placeholder="Image URL">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                        <select v-model="form.status" class="w-full px-4 py-2 border rounded-lg">
                            <option value="upcoming">Upcoming</option>
                            <option value="ongoing">Ongoing</option>
                            <option value="completed">Completed</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                        <textarea v-model="form.description" rows="3" class="w-full px-4 py-2 border rounded-lg" placeholder="Exhibition details..."></textarea>
                    </div>
                </div>
                <div class="p-6 border-t flex justify-end space-x-3">
                    <button @click="showModal = false" class="px-4 py-2 border rounded-lg">Cancel</button>
                    <button @click="saveExhibition" class="px-4 py-2 bg-amber-600 text-white rounded-lg hover:bg-amber-700">Save</button>
                </div>
            </div>
        </div>
    </div>
</template>
