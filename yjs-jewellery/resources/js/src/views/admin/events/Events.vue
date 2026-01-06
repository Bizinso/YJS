<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'

const loading = ref(true)
const events = ref([])
const showModal = ref(false)
const editingEvent = ref(null)
const form = ref({ title: '', description: '', location: '', start_date: '', end_date: '', image: '', status: 'upcoming' })

const fetchEvents = async () => {
    loading.value = true
    try {
        const response = await axios.get('/api/admin/events')
        if (response.data.success) events.value = response.data.data || []
    } catch (error) { console.error('Error:', error) }
    finally { loading.value = false }
}

const openCreateModal = () => { editingEvent.value = null; form.value = { title: '', description: '', location: '', start_date: '', end_date: '', image: '', status: 'upcoming' }; showModal.value = true }
const openEditModal = (event) => { editingEvent.value = event; form.value = { ...event }; showModal.value = true }

const saveEvent = async () => {
    try {
        if (editingEvent.value) await axios.put(`/api/admin/events/${editingEvent.value.id}`, form.value)
        else await axios.post('/api/admin/events', form.value)
        showModal.value = false; fetchEvents()
    } catch (error) { alert(error.response?.data?.message || 'Failed to save') }
}

const deleteEvent = async (event) => { if (confirm('Delete this event?')) { await axios.delete(`/api/admin/events/${event.id}`); fetchEvents() } }
const formatDate = (d) => d ? new Date(d).toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric' }) : '-'
const getStatusClass = (s) => ({ upcoming: 'bg-blue-100 text-blue-800', ongoing: 'bg-green-100 text-green-800', completed: 'bg-gray-100 text-gray-800', cancelled: 'bg-red-100 text-red-800' })[s] || 'bg-gray-100 text-gray-800'

onMounted(() => fetchEvents())
</script>

<template>
    <div class="p-6">
        <div class="flex items-center justify-between mb-6">
            <div><h1 class="text-2xl font-bold text-gray-800">Events</h1><p class="text-gray-600">Manage events and exhibitions</p></div>
            <button @click="openCreateModal" class="px-4 py-2 bg-amber-600 text-white rounded-lg hover:bg-amber-700 flex items-center space-x-2"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg><span>New Event</span></button>
        </div>

        <div v-if="loading" class="text-center py-8"><div class="animate-spin rounded-full h-8 w-8 border-b-2 border-amber-600 mx-auto"></div></div>
        <div v-else-if="events.length === 0" class="bg-white rounded-lg shadow-sm p-8 text-center text-gray-500">No events found</div>
        <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div v-for="event in events" :key="event.id" class="bg-white rounded-lg shadow-sm overflow-hidden">
                <img :src="event.image || '/images/event-placeholder.jpg'" :alt="event.title" class="w-full h-48 object-cover">
                <div class="p-4">
                    <div class="flex items-center justify-between mb-2"><span :class="['px-2 py-1 text-xs rounded-full capitalize', getStatusClass(event.status)]">{{ event.status }}</span></div>
                    <h3 class="font-semibold text-gray-800 mb-2">{{ event.title }}</h3>
                    <p class="text-sm text-gray-500 mb-2"><svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>{{ event.location }}</p>
                    <p class="text-sm text-gray-500 mb-4"><svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>{{ formatDate(event.start_date) }} - {{ formatDate(event.end_date) }}</p>
                    <div class="flex justify-end space-x-2"><button @click="openEditModal(event)" class="text-amber-600 hover:text-amber-900 text-sm">Edit</button><button @click="deleteEvent(event)" class="text-red-600 hover:text-red-900 text-sm">Delete</button></div>
                </div>
            </div>
        </div>

        <div v-if="showModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg shadow-xl w-full max-w-lg">
                <div class="p-6 border-b flex justify-between items-center"><h2 class="text-xl font-bold">{{ editingEvent ? 'Edit' : 'Create' }} Event</h2><button @click="showModal = false" class="text-gray-500 text-2xl">&times;</button></div>
                <div class="p-6 space-y-4">
                    <div><label class="block text-sm font-medium text-gray-700 mb-1">Title</label><input v-model="form.title" type="text" class="w-full px-4 py-2 border rounded-lg"></div>
                    <div><label class="block text-sm font-medium text-gray-700 mb-1">Location</label><input v-model="form.location" type="text" class="w-full px-4 py-2 border rounded-lg"></div>
                    <div class="grid grid-cols-2 gap-4">
                        <div><label class="block text-sm font-medium text-gray-700 mb-1">Start Date</label><input v-model="form.start_date" type="date" class="w-full px-4 py-2 border rounded-lg"></div>
                        <div><label class="block text-sm font-medium text-gray-700 mb-1">End Date</label><input v-model="form.end_date" type="date" class="w-full px-4 py-2 border rounded-lg"></div>
                    </div>
                    <div><label class="block text-sm font-medium text-gray-700 mb-1">Image URL</label><input v-model="form.image" type="text" class="w-full px-4 py-2 border rounded-lg"></div>
                    <div><label class="block text-sm font-medium text-gray-700 mb-1">Status</label><select v-model="form.status" class="w-full px-4 py-2 border rounded-lg"><option value="upcoming">Upcoming</option><option value="ongoing">Ongoing</option><option value="completed">Completed</option><option value="cancelled">Cancelled</option></select></div>
                    <div><label class="block text-sm font-medium text-gray-700 mb-1">Description</label><textarea v-model="form.description" rows="4" class="w-full px-4 py-2 border rounded-lg"></textarea></div>
                </div>
                <div class="p-6 border-t flex justify-end space-x-3"><button @click="showModal = false" class="px-4 py-2 border rounded-lg">Cancel</button><button @click="saveEvent" class="px-4 py-2 bg-amber-600 text-white rounded-lg">Save</button></div>
            </div>
        </div>
    </div>
</template>
