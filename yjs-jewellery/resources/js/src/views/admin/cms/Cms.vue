<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'

const router = useRouter()
const loading = ref(true)
const pages = ref([])
const showModal = ref(false)
const editingPage = ref(null)
const form = ref({ title: '', slug: '', content: '', meta_title: '', meta_description: '', status: 'published' })

const fetchPages = async () => {
    loading.value = true
    try {
        const response = await axios.get('/api/admin/cms/pages')
        if (response.data.success) pages.value = response.data.data || []
    } catch (error) { console.error('Error:', error) }
    finally { loading.value = false }
}

const openCreateModal = () => { editingPage.value = null; form.value = { title: '', slug: '', content: '', meta_title: '', meta_description: '', status: 'published' }; showModal.value = true }
const openEditModal = (page) => { editingPage.value = page; form.value = { ...page }; showModal.value = true }

const savePage = async () => {
    try {
        if (editingPage.value) await axios.put(`/api/admin/cms/pages/${editingPage.value.id}`, form.value)
        else await axios.post('/api/admin/cms/pages', form.value)
        showModal.value = false; fetchPages()
    } catch (error) { alert(error.response?.data?.message || 'Failed to save') }
}

const deletePage = async (page) => { if (confirm('Delete this page?')) { await axios.delete(`/api/admin/cms/pages/${page.id}`); fetchPages() } }
const editInPageBuilder = (page) => { router.push({ name: 'admin.page-builder.edit', params: { id: page.id } }) }
const generateSlug = () => { form.value.slug = form.value.title.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)/g, '') }
const formatDate = (d) => d ? new Date(d).toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric' }) : '-'
const getStatusClass = (s) => s === 'published' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'

onMounted(() => fetchPages())
</script>

<template>
    <div class="p-6">
        <div class="flex items-center justify-between mb-6">
            <div><h1 class="text-2xl font-bold text-gray-800">CMS Pages</h1><p class="text-gray-600">Manage static pages (About, Contact, etc.)</p></div>
            <button @click="openCreateModal" class="px-4 py-2 bg-amber-600 text-white rounded-lg hover:bg-amber-700 flex items-center space-x-2"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg><span>New Page</span></button>
        </div>

        <div class="bg-white rounded-lg shadow-sm overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50"><tr><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Page</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Slug</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Updated</th><th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th></tr></thead>
                <tbody class="divide-y divide-gray-200">
                    <tr v-if="loading"><td colspan="5" class="px-6 py-8 text-center"><div class="animate-spin rounded-full h-8 w-8 border-b-2 border-amber-600 mx-auto"></div></td></tr>
                    <tr v-else-if="pages.length === 0"><td colspan="5" class="px-6 py-8 text-center text-gray-500">No pages found</td></tr>
                    <tr v-else v-for="page in pages" :key="page.id" class="hover:bg-gray-50">
                        <td class="px-6 py-4"><div class="font-medium text-gray-900">{{ page.title }}</div></td>
                        <td class="px-6 py-4 text-sm text-gray-500">/{{ page.slug }}</td>
                        <td class="px-6 py-4"><span :class="['px-2 py-1 text-xs rounded-full', getStatusClass(page.status)]">{{ page.status }}</span></td>
                        <td class="px-6 py-4 text-sm text-gray-500">{{ formatDate(page.updated_at) }}</td>
                        <td class="px-6 py-4 text-right space-x-2">
                            <button @click="editInPageBuilder(page)" class="inline-flex items-center px-2 py-1 bg-amber-50 text-amber-600 hover:bg-amber-100 rounded text-sm" title="Edit in Page Builder">
                                <i class="bi bi-grid-1x2 me-1"></i>Builder
                            </button>
                            <button @click="openEditModal(page)" class="text-gray-600 hover:text-gray-900" title="Quick Edit">Edit</button>
                            <button @click="deletePage(page)" class="text-red-600 hover:text-red-900">Delete</button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-if="showModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg shadow-xl w-full max-w-3xl max-h-[90vh] overflow-y-auto">
                <div class="p-6 border-b flex justify-between items-center sticky top-0 bg-white"><h2 class="text-xl font-bold">{{ editingPage ? 'Edit' : 'Create' }} Page</h2><button @click="showModal = false" class="text-gray-500 text-2xl">&times;</button></div>
                <div class="p-6 space-y-4">
                    <div><label class="block text-sm font-medium text-gray-700 mb-1">Title</label><input v-model="form.title" @blur="!editingPage && generateSlug()" type="text" class="w-full px-4 py-2 border rounded-lg"></div>
                    <div class="grid grid-cols-2 gap-4">
                        <div><label class="block text-sm font-medium text-gray-700 mb-1">Slug</label><input v-model="form.slug" type="text" class="w-full px-4 py-2 border rounded-lg"></div>
                        <div><label class="block text-sm font-medium text-gray-700 mb-1">Status</label><select v-model="form.status" class="w-full px-4 py-2 border rounded-lg"><option value="published">Published</option><option value="draft">Draft</option></select></div>
                    </div>
                    <div><label class="block text-sm font-medium text-gray-700 mb-1">Content</label><textarea v-model="form.content" rows="12" class="w-full px-4 py-2 border rounded-lg font-mono text-sm"></textarea></div>
                    <div><label class="block text-sm font-medium text-gray-700 mb-1">Meta Title</label><input v-model="form.meta_title" type="text" class="w-full px-4 py-2 border rounded-lg"></div>
                    <div><label class="block text-sm font-medium text-gray-700 mb-1">Meta Description</label><textarea v-model="form.meta_description" rows="2" class="w-full px-4 py-2 border rounded-lg"></textarea></div>
                </div>
                <div class="p-6 border-t flex justify-end space-x-3"><button @click="showModal = false" class="px-4 py-2 border rounded-lg">Cancel</button><button @click="savePage" class="px-4 py-2 bg-amber-600 text-white rounded-lg">Save</button></div>
            </div>
        </div>
    </div>
</template>
