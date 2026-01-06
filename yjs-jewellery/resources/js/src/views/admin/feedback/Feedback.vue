<script setup>
import { ref, onMounted, watch } from 'vue'
import axios from 'axios'

const loading = ref(true)
const feedbacks = ref([])
const pagination = ref({ current_page: 1, last_page: 1, total: 0 })
const filters = ref({ search: '', status: '', rating: '' })
const selectedFeedback = ref(null)
const showModal = ref(false)

const fetchFeedbacks = async (page = 1) => {
    loading.value = true
    try {
        const response = await axios.get('/api/admin/feedback', { params: { page, ...filters.value } })
        if (response.data.success) { feedbacks.value = response.data.data.data || response.data.data || []; pagination.value = response.data.data.meta || response.data.data }
    } catch (error) { console.error('Error:', error) }
    finally { loading.value = false }
}

const viewFeedback = (feedback) => { selectedFeedback.value = feedback; showModal.value = true }
const updateStatus = async (feedback, status) => { await axios.patch(`/api/admin/feedback/${feedback.id}`, { status }); fetchFeedbacks(pagination.value.current_page) }
const deleteFeedback = async (feedback) => { if (confirm('Delete this feedback?')) { await axios.delete(`/api/admin/feedback/${feedback.id}`); fetchFeedbacks(pagination.value.current_page) } }
const formatDate = (d) => d ? new Date(d).toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric' }) : '-'
const getStatusClass = (s) => ({ pending: 'bg-yellow-100 text-yellow-800', reviewed: 'bg-blue-100 text-blue-800', resolved: 'bg-green-100 text-green-800' })[s] || 'bg-gray-100 text-gray-800'

let timeout = null
watch(() => filters.value.search, () => { clearTimeout(timeout); timeout = setTimeout(() => fetchFeedbacks(1), 500) })
onMounted(() => fetchFeedbacks())
</script>

<template>
    <div class="p-6">
        <div class="flex items-center justify-between mb-6"><div><h1 class="text-2xl font-bold text-gray-800">Customer Feedback</h1><p class="text-gray-600">Manage customer feedback and reviews</p></div></div>

        <div class="bg-white rounded-lg shadow-sm p-4 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <input v-model="filters.search" type="text" placeholder="Search..." class="px-4 py-2 border rounded-lg">
                <select v-model="filters.status" @change="fetchFeedbacks(1)" class="px-4 py-2 border rounded-lg"><option value="">All Status</option><option value="pending">Pending</option><option value="reviewed">Reviewed</option><option value="resolved">Resolved</option></select>
                <select v-model="filters.rating" @change="fetchFeedbacks(1)" class="px-4 py-2 border rounded-lg"><option value="">All Ratings</option><option v-for="r in 5" :key="r" :value="r">{{ r }} Star{{ r > 1 ? 's' : '' }}</option></select>
                <button @click="filters = { search: '', status: '', rating: '' }; fetchFeedbacks(1)" class="px-4 py-2 border rounded-lg hover:bg-gray-50">Reset</button>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50"><tr><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Customer</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Subject</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Rating</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th><th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th></tr></thead>
                <tbody class="divide-y divide-gray-200">
                    <tr v-if="loading"><td colspan="6" class="px-6 py-8 text-center"><div class="animate-spin rounded-full h-8 w-8 border-b-2 border-amber-600 mx-auto"></div></td></tr>
                    <tr v-else-if="feedbacks.length === 0"><td colspan="6" class="px-6 py-8 text-center text-gray-500">No feedback found</td></tr>
                    <tr v-else v-for="fb in feedbacks" :key="fb.id" class="hover:bg-gray-50">
                        <td class="px-6 py-4"><div class="font-medium text-gray-900">{{ fb.customer?.first_name }} {{ fb.customer?.last_name }}</div><div class="text-sm text-gray-500">{{ fb.customer?.email }}</div></td>
                        <td class="px-6 py-4"><div class="text-sm text-gray-900">{{ fb.subject }}</div><div class="text-sm text-gray-500 truncate max-w-xs">{{ fb.message?.substring(0, 50) }}...</div></td>
                        <td class="px-6 py-4"><div class="flex items-center"><span v-for="i in 5" :key="i" :class="i <= fb.rating ? 'text-amber-400' : 'text-gray-300'">★</span></div></td>
                        <td class="px-6 py-4"><select :value="fb.status" @change="updateStatus(fb, $event.target.value)" :class="['px-2 py-1 text-xs rounded-full border-0', getStatusClass(fb.status)]"><option value="pending">Pending</option><option value="reviewed">Reviewed</option><option value="resolved">Resolved</option></select></td>
                        <td class="px-6 py-4 text-sm text-gray-500">{{ formatDate(fb.created_at) }}</td>
                        <td class="px-6 py-4 text-right space-x-2"><button @click="viewFeedback(fb)" class="text-amber-600 hover:text-amber-900">View</button><button @click="deleteFeedback(fb)" class="text-red-600 hover:text-red-900">Delete</button></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-if="showModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg shadow-xl w-full max-w-lg">
                <div class="p-6 border-b flex justify-between items-center"><h2 class="text-xl font-bold">Feedback Details</h2><button @click="showModal = false" class="text-gray-500 text-2xl">&times;</button></div>
                <div v-if="selectedFeedback" class="p-6">
                    <div class="mb-4"><label class="text-sm font-medium text-gray-500">Customer</label><p class="font-medium">{{ selectedFeedback.customer?.first_name }} {{ selectedFeedback.customer?.last_name }}</p><p class="text-sm text-gray-500">{{ selectedFeedback.customer?.email }}</p></div>
                    <div class="mb-4"><label class="text-sm font-medium text-gray-500">Rating</label><div class="flex items-center text-xl"><span v-for="i in 5" :key="i" :class="i <= selectedFeedback.rating ? 'text-amber-400' : 'text-gray-300'">★</span></div></div>
                    <div class="mb-4"><label class="text-sm font-medium text-gray-500">Subject</label><p class="font-medium">{{ selectedFeedback.subject }}</p></div>
                    <div class="mb-4"><label class="text-sm font-medium text-gray-500">Message</label><p class="text-gray-700 whitespace-pre-wrap">{{ selectedFeedback.message }}</p></div>
                    <div><label class="text-sm font-medium text-gray-500">Date</label><p class="text-sm text-gray-500">{{ formatDate(selectedFeedback.created_at) }}</p></div>
                </div>
                <div class="p-6 border-t flex justify-end"><button @click="showModal = false" class="px-4 py-2 border rounded-lg">Close</button></div>
            </div>
        </div>
    </div>
</template>
