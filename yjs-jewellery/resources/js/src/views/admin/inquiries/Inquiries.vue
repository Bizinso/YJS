<script setup>
import { ref, onMounted, watch } from 'vue'
import axios from 'axios'

const loading = ref(true)
const inquiries = ref([])
const pagination = ref({ current_page: 1, last_page: 1, total: 0 })
const filters = ref({ search: '', status: '', priority: '' })
const selectedInquiry = ref(null)
const showModal = ref(false)
const replyMessage = ref('')

const statusOptions = ['pending', 'in_review', 'quoted', 'accepted', 'rejected', 'completed']
const priorityOptions = ['normal', 'high', 'urgent']

const fetchInquiries = async (page = 1) => {
    loading.value = true
    try {
        const response = await axios.get('/api/admin/partner-inquiries', { params: { page, ...filters.value } })
        if (response.data.success) { inquiries.value = response.data.data.data || response.data.data || []; pagination.value = response.data.data.meta || response.data.data }
    } catch (error) { console.error('Error:', error) }
    finally { loading.value = false }
}

const viewInquiry = async (inquiry) => {
    try {
        const response = await axios.get(`/api/admin/partner-inquiries/${inquiry.id}`)
        if (response.data.success) { selectedInquiry.value = response.data.data; showModal.value = true }
    } catch (error) { console.error('Error:', error) }
}

const updateStatus = async (inquiry, status) => { await axios.patch(`/api/admin/partner-inquiries/${inquiry.id}/status`, { status }); fetchInquiries(pagination.value.current_page) }

const sendReply = async () => {
    if (!replyMessage.value.trim()) return
    await axios.post(`/api/admin/partner-inquiries/${selectedInquiry.value.id}/messages`, { message: replyMessage.value })
    replyMessage.value = ''; viewInquiry(selectedInquiry.value)
}

const formatDate = (d) => d ? new Date(d).toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' }) : '-'
const getStatusClass = (s) => ({ pending: 'bg-yellow-100 text-yellow-800', in_review: 'bg-blue-100 text-blue-800', quoted: 'bg-purple-100 text-purple-800', accepted: 'bg-green-100 text-green-800', rejected: 'bg-red-100 text-red-800', completed: 'bg-gray-100 text-gray-800' })[s] || 'bg-gray-100 text-gray-800'
const getPriorityClass = (p) => ({ normal: 'text-gray-600', high: 'text-orange-600', urgent: 'text-red-600' })[p] || 'text-gray-600'

let timeout = null
watch(() => filters.value.search, () => { clearTimeout(timeout); timeout = setTimeout(() => fetchInquiries(1), 500) })
onMounted(() => fetchInquiries())
</script>

<template>
    <div class="p-6">
        <div class="flex items-center justify-between mb-6"><div><h1 class="text-2xl font-bold text-gray-800">Partner Inquiries</h1><p class="text-gray-600">Manage B2B partner inquiries and quotes</p></div></div>

        <div class="bg-white rounded-lg shadow-sm p-4 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <input v-model="filters.search" type="text" placeholder="Search..." class="px-4 py-2 border rounded-lg">
                <select v-model="filters.status" @change="fetchInquiries(1)" class="px-4 py-2 border rounded-lg"><option value="">All Status</option><option v-for="s in statusOptions" :key="s" :value="s" class="capitalize">{{ s.replace('_', ' ') }}</option></select>
                <select v-model="filters.priority" @change="fetchInquiries(1)" class="px-4 py-2 border rounded-lg"><option value="">All Priority</option><option v-for="p in priorityOptions" :key="p" :value="p" class="capitalize">{{ p }}</option></select>
                <button @click="filters = { search: '', status: '', priority: '' }; fetchInquiries(1)" class="px-4 py-2 border rounded-lg hover:bg-gray-50">Reset</button>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50"><tr><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Inquiry</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Partner</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Items</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Priority</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th><th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th></tr></thead>
                <tbody class="divide-y divide-gray-200">
                    <tr v-if="loading"><td colspan="7" class="px-6 py-8 text-center"><div class="animate-spin rounded-full h-8 w-8 border-b-2 border-amber-600 mx-auto"></div></td></tr>
                    <tr v-else-if="inquiries.length === 0"><td colspan="7" class="px-6 py-8 text-center text-gray-500">No inquiries found</td></tr>
                    <tr v-else v-for="inq in inquiries" :key="inq.id" class="hover:bg-gray-50">
                        <td class="px-6 py-4"><div class="font-medium text-gray-900">#{{ inq.inquiry_code }}</div><div class="text-sm text-gray-500">{{ inq.reference_number || '-' }}</div></td>
                        <td class="px-6 py-4"><div class="text-sm font-medium text-gray-900">{{ inq.partner?.business_name }}</div><div class="text-sm text-gray-500">{{ inq.partner?.phone }}</div></td>
                        <td class="px-6 py-4 text-sm text-gray-500">{{ inq.items_count || inq.items?.length || 0 }} items</td>
                        <td class="px-6 py-4"><span :class="['text-sm font-medium capitalize', getPriorityClass(inq.priority)]">{{ inq.priority }}</span></td>
                        <td class="px-6 py-4"><select :value="inq.status" @change="updateStatus(inq, $event.target.value)" :class="['px-2 py-1 text-xs rounded-full border-0', getStatusClass(inq.status)]"><option v-for="s in statusOptions" :key="s" :value="s" class="capitalize">{{ s.replace('_', ' ') }}</option></select></td>
                        <td class="px-6 py-4 text-sm text-gray-500">{{ formatDate(inq.created_at) }}</td>
                        <td class="px-6 py-4 text-right"><button @click="viewInquiry(inq)" class="text-amber-600 hover:text-amber-900">View</button></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-if="showModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg shadow-xl w-full max-w-4xl max-h-[90vh] overflow-y-auto">
                <div class="p-6 border-b flex justify-between items-center sticky top-0 bg-white"><h2 class="text-xl font-bold">Inquiry #{{ selectedInquiry?.inquiry_code }}</h2><button @click="showModal = false" class="text-gray-500 text-2xl">&times;</button></div>
                <div v-if="selectedInquiry" class="p-6">
                    <div class="grid grid-cols-2 gap-6 mb-6">
                        <div><h3 class="font-semibold text-gray-700 mb-2">Partner</h3><p class="font-medium">{{ selectedInquiry.partner?.business_name }}</p><p class="text-gray-500">{{ selectedInquiry.partner?.email }}</p><p class="text-gray-500">{{ selectedInquiry.partner?.phone }}</p></div>
                        <div><h3 class="font-semibold text-gray-700 mb-2">Details</h3><p><span class="text-gray-500">Expected:</span> {{ formatDate(selectedInquiry.expected_delivery_date) }}</p><p><span class="text-gray-500">Priority:</span> <span :class="getPriorityClass(selectedInquiry.priority)" class="capitalize">{{ selectedInquiry.priority }}</span></p></div>
                    </div>
                    <h3 class="font-semibold text-gray-700 mb-2">Items</h3>
                    <table class="w-full mb-6"><thead class="bg-gray-50"><tr><th class="px-4 py-2 text-left text-sm">Product</th><th class="px-4 py-2 text-center text-sm">Qty</th><th class="px-4 py-2 text-left text-sm">Specs</th></tr></thead><tbody><tr v-for="item in selectedInquiry.items" :key="item.id" class="border-b"><td class="px-4 py-2"><div class="flex items-center space-x-3"><img :src="item.product?.main_image || '/images/placeholder.png'" class="w-10 h-10 rounded object-cover"><div><p class="font-medium">{{ item.product?.name }}</p><p class="text-sm text-gray-500">SKU: {{ item.product?.sku }}</p></div></div></td><td class="px-4 py-2 text-center">{{ item.quantity }}</td><td class="px-4 py-2 text-sm text-gray-500">{{ item.specifications || '-' }}</td></tr></tbody></table>
                    <h3 class="font-semibold text-gray-700 mb-2">Messages</h3>
                    <div class="border rounded-lg mb-4 max-h-60 overflow-y-auto"><div v-if="!selectedInquiry.messages?.length" class="p-4 text-center text-gray-500">No messages</div><div v-else v-for="msg in selectedInquiry.messages" :key="msg.id" :class="['p-4 border-b', msg.sender_type === 'admin' ? 'bg-amber-50' : 'bg-white']"><div class="flex justify-between mb-1"><span class="font-medium text-sm">{{ msg.sender_type === 'admin' ? 'Admin' : 'Partner' }}</span><span class="text-xs text-gray-500">{{ formatDate(msg.created_at) }}</span></div><p class="text-sm">{{ msg.message }}</p></div></div>
                    <div class="flex space-x-2"><input v-model="replyMessage" type="text" placeholder="Type message..." class="flex-1 px-4 py-2 border rounded-lg" @keyup.enter="sendReply"><button @click="sendReply" class="px-4 py-2 bg-amber-600 text-white rounded-lg">Send</button></div>
                </div>
            </div>
        </div>
    </div>
</template>
