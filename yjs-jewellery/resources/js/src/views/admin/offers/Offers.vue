<script setup>
import { ref, onMounted, watch } from 'vue'
import axios from 'axios'

const loading = ref(true)
const offers = ref([])
const pagination = ref({ current_page: 1, last_page: 1, total: 0 })
const filters = ref({ search: '', status: '', type: '' })
const showModal = ref(false)
const editingOffer = ref(null)
const form = ref({ title: '', type: 'percentage', value: 0, min_order_amount: 0, max_discount: null, start_date: '', end_date: '', status: 'active', description: '' })

const offerTypes = [
    { value: 'percentage', label: 'Percentage Discount' },
    { value: 'fixed', label: 'Fixed Amount' },
    { value: 'bogo', label: 'Buy One Get One' },
    { value: 'flash_sale', label: 'Flash Sale' }
]

const fetchOffers = async (page = 1) => {
    loading.value = true
    try {
        const response = await axios.get('/api/admin/offers', { params: { page, ...filters.value } })
        if (response.data.success) {
            offers.value = response.data.data.data || response.data.data || []
            pagination.value = response.data.data.meta || response.data.data
        }
    } catch (error) { console.error('Error:', error) }
    finally { loading.value = false }
}

const openCreateModal = () => { editingOffer.value = null; form.value = { title: '', type: 'percentage', value: 0, min_order_amount: 0, max_discount: null, start_date: '', end_date: '', status: 'active', description: '' }; showModal.value = true }
const openEditModal = (offer) => { editingOffer.value = offer; form.value = { ...offer }; showModal.value = true }

const saveOffer = async () => {
    try {
        if (editingOffer.value) await axios.put(`/api/admin/offers/${editingOffer.value.id}`, form.value)
        else await axios.post('/api/admin/offers', form.value)
        showModal.value = false; fetchOffers(pagination.value.current_page)
    } catch (error) { alert(error.response?.data?.message || 'Failed to save') }
}

const deleteOffer = async (offer) => { if (confirm('Delete this offer?')) { await axios.delete(`/api/admin/offers/${offer.id}`); fetchOffers(pagination.value.current_page) } }
const toggleStatus = async (offer) => { await axios.patch(`/api/admin/offers/${offer.id}/toggle-status`); fetchOffers(pagination.value.current_page) }
const formatDate = (d) => d ? new Date(d).toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric' }) : '-'
const getStatusClass = (s) => s === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'

let timeout = null
watch(() => filters.value.search, () => { clearTimeout(timeout); timeout = setTimeout(() => fetchOffers(1), 500) })
onMounted(() => fetchOffers())
</script>

<template>
    <div class="p-6">
        <div class="flex items-center justify-between mb-6">
            <div><h1 class="text-2xl font-bold text-gray-800">Offers & Promotions</h1><p class="text-gray-600">Manage discounts and promotions</p></div>
            <button @click="openCreateModal" class="px-4 py-2 bg-amber-600 text-white rounded-lg hover:bg-amber-700 flex items-center space-x-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg><span>Create Offer</span>
            </button>
        </div>

        <div class="bg-white rounded-lg shadow-sm p-4 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <input v-model="filters.search" type="text" placeholder="Search offers..." class="px-4 py-2 border rounded-lg">
                <select v-model="filters.type" @change="fetchOffers(1)" class="px-4 py-2 border rounded-lg"><option value="">All Types</option><option v-for="t in offerTypes" :key="t.value" :value="t.value">{{ t.label }}</option></select>
                <select v-model="filters.status" @change="fetchOffers(1)" class="px-4 py-2 border rounded-lg"><option value="">All Status</option><option value="active">Active</option><option value="inactive">Inactive</option></select>
                <button @click="filters = { search: '', status: '', type: '' }; fetchOffers(1)" class="px-4 py-2 border rounded-lg hover:bg-gray-50">Reset</button>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50"><tr><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Offer</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Value</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Validity</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th><th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th></tr></thead>
                <tbody class="divide-y divide-gray-200">
                    <tr v-if="loading"><td colspan="6" class="px-6 py-8 text-center"><div class="animate-spin rounded-full h-8 w-8 border-b-2 border-amber-600 mx-auto"></div></td></tr>
                    <tr v-else-if="offers.length === 0"><td colspan="6" class="px-6 py-8 text-center text-gray-500">No offers found</td></tr>
                    <tr v-else v-for="offer in offers" :key="offer.id" class="hover:bg-gray-50">
                        <td class="px-6 py-4"><div class="font-medium text-gray-900">{{ offer.title }}</div><div class="text-sm text-gray-500">{{ offer.description?.substring(0, 50) }}...</div></td>
                        <td class="px-6 py-4 text-sm text-gray-500 capitalize">{{ offer.type?.replace('_', ' ') }}</td>
                        <td class="px-6 py-4 text-sm font-medium"><span v-if="offer.type === 'percentage'">{{ offer.value }}%</span><span v-else>₹{{ offer.value }}</span></td>
                        <td class="px-6 py-4 text-sm text-gray-500">{{ formatDate(offer.start_date) }} - {{ formatDate(offer.end_date) }}</td>
                        <td class="px-6 py-4"><button @click="toggleStatus(offer)" :class="['px-2 py-1 text-xs rounded-full', getStatusClass(offer.status)]">{{ offer.status }}</button></td>
                        <td class="px-6 py-4 text-right space-x-2"><button @click="openEditModal(offer)" class="text-amber-600 hover:text-amber-900">Edit</button><button @click="deleteOffer(offer)" class="text-red-600 hover:text-red-900">Delete</button></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-if="showModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg shadow-xl w-full max-w-lg">
                <div class="p-6 border-b flex justify-between items-center"><h2 class="text-xl font-bold">{{ editingOffer ? 'Edit' : 'Create' }} Offer</h2><button @click="showModal = false" class="text-gray-500 text-2xl">&times;</button></div>
                <div class="p-6 space-y-4">
                    <div><label class="block text-sm font-medium text-gray-700 mb-1">Title</label><input v-model="form.title" type="text" class="w-full px-4 py-2 border rounded-lg"></div>
                    <div class="grid grid-cols-2 gap-4">
                        <div><label class="block text-sm font-medium text-gray-700 mb-1">Type</label><select v-model="form.type" class="w-full px-4 py-2 border rounded-lg"><option v-for="t in offerTypes" :key="t.value" :value="t.value">{{ t.label }}</option></select></div>
                        <div><label class="block text-sm font-medium text-gray-700 mb-1">Value</label><input v-model="form.value" type="number" class="w-full px-4 py-2 border rounded-lg"></div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div><label class="block text-sm font-medium text-gray-700 mb-1">Start Date</label><input v-model="form.start_date" type="date" class="w-full px-4 py-2 border rounded-lg"></div>
                        <div><label class="block text-sm font-medium text-gray-700 mb-1">End Date</label><input v-model="form.end_date" type="date" class="w-full px-4 py-2 border rounded-lg"></div>
                    </div>
                    <div><label class="block text-sm font-medium text-gray-700 mb-1">Description</label><textarea v-model="form.description" rows="3" class="w-full px-4 py-2 border rounded-lg"></textarea></div>
                </div>
                <div class="p-6 border-t flex justify-end space-x-3"><button @click="showModal = false" class="px-4 py-2 border rounded-lg">Cancel</button><button @click="saveOffer" class="px-4 py-2 bg-amber-600 text-white rounded-lg">Save</button></div>
            </div>
        </div>
    </div>
</template>
