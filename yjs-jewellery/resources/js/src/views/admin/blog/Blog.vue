<script setup>
import { ref, onMounted, watch } from 'vue'
import axios from 'axios'

const loading = ref(true)
const posts = ref([])
const pagination = ref({ current_page: 1, last_page: 1, total: 0 })
const filters = ref({ search: '', status: '' })
const showModal = ref(false)
const editingPost = ref(null)
const form = ref({ title: '', slug: '', content: '', excerpt: '', featured_image: '', status: 'draft', meta_title: '', meta_description: '' })

const fetchPosts = async (page = 1) => {
    loading.value = true
    try {
        const response = await axios.get('/api/admin/blog/posts', { params: { page, ...filters.value } })
        if (response.data.success) { posts.value = response.data.data.data || response.data.data || []; pagination.value = response.data.data.meta || response.data.data }
    } catch (error) { console.error('Error:', error) }
    finally { loading.value = false }
}

const openCreateModal = () => { editingPost.value = null; form.value = { title: '', slug: '', content: '', excerpt: '', featured_image: '', status: 'draft', meta_title: '', meta_description: '' }; showModal.value = true }
const openEditModal = (post) => { editingPost.value = post; form.value = { ...post }; showModal.value = true }

const savePost = async () => {
    try {
        if (editingPost.value) await axios.put(`/api/admin/blog/posts/${editingPost.value.id}`, form.value)
        else await axios.post('/api/admin/blog/posts', form.value)
        showModal.value = false; fetchPosts(pagination.value.current_page)
    } catch (error) { alert(error.response?.data?.message || 'Failed to save') }
}

const deletePost = async (post) => { if (confirm('Delete this post?')) { await axios.delete(`/api/admin/blog/posts/${post.id}`); fetchPosts(pagination.value.current_page) } }
const generateSlug = () => { form.value.slug = form.value.title.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)/g, '') }
const formatDate = (d) => d ? new Date(d).toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric' }) : '-'
const getStatusClass = (s) => s === 'published' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'

let timeout = null
watch(() => filters.value.search, () => { clearTimeout(timeout); timeout = setTimeout(() => fetchPosts(1), 500) })
onMounted(() => fetchPosts())
</script>

<template>
    <div class="p-6">
        <div class="flex items-center justify-between mb-6">
            <div><h1 class="text-2xl font-bold text-gray-800">Blog Posts</h1><p class="text-gray-600">Manage blog content</p></div>
            <button @click="openCreateModal" class="px-4 py-2 bg-amber-600 text-white rounded-lg hover:bg-amber-700 flex items-center space-x-2"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg><span>New Post</span></button>
        </div>

        <div class="bg-white rounded-lg shadow-sm p-4 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <input v-model="filters.search" type="text" placeholder="Search posts..." class="px-4 py-2 border rounded-lg">
                <select v-model="filters.status" @change="fetchPosts(1)" class="px-4 py-2 border rounded-lg"><option value="">All Status</option><option value="published">Published</option><option value="draft">Draft</option></select>
                <button @click="filters = { search: '', status: '' }; fetchPosts(1)" class="px-4 py-2 border rounded-lg hover:bg-gray-50">Reset</button>
            </div>
        </div>

        <div v-if="loading" class="text-center py-8"><div class="animate-spin rounded-full h-8 w-8 border-b-2 border-amber-600 mx-auto"></div></div>
        <div v-else-if="posts.length === 0" class="bg-white rounded-lg shadow-sm p-8 text-center text-gray-500">No posts found</div>
        <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div v-for="post in posts" :key="post.id" class="bg-white rounded-lg shadow-sm overflow-hidden">
                <img :src="post.featured_image || '/images/blog-placeholder.jpg'" :alt="post.title" class="w-full h-48 object-cover">
                <div class="p-4">
                    <div class="flex items-center justify-between mb-2"><span :class="['px-2 py-1 text-xs rounded-full', getStatusClass(post.status)]">{{ post.status }}</span><span class="text-xs text-gray-500">{{ formatDate(post.published_at || post.created_at) }}</span></div>
                    <h3 class="font-semibold text-gray-800 mb-2 line-clamp-2">{{ post.title }}</h3>
                    <p class="text-sm text-gray-500 mb-4 line-clamp-2">{{ post.excerpt }}</p>
                    <div class="flex justify-end space-x-2"><button @click="openEditModal(post)" class="text-amber-600 hover:text-amber-900 text-sm">Edit</button><button @click="deletePost(post)" class="text-red-600 hover:text-red-900 text-sm">Delete</button></div>
                </div>
            </div>
        </div>

        <div v-if="showModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg shadow-xl w-full max-w-3xl max-h-[90vh] overflow-y-auto">
                <div class="p-6 border-b flex justify-between items-center sticky top-0 bg-white"><h2 class="text-xl font-bold">{{ editingPost ? 'Edit' : 'Create' }} Post</h2><button @click="showModal = false" class="text-gray-500 text-2xl">&times;</button></div>
                <div class="p-6 space-y-4">
                    <div><label class="block text-sm font-medium text-gray-700 mb-1">Title</label><input v-model="form.title" @blur="!editingPost && generateSlug()" type="text" class="w-full px-4 py-2 border rounded-lg"></div>
                    <div><label class="block text-sm font-medium text-gray-700 mb-1">Slug</label><input v-model="form.slug" type="text" class="w-full px-4 py-2 border rounded-lg"></div>
                    <div><label class="block text-sm font-medium text-gray-700 mb-1">Excerpt</label><textarea v-model="form.excerpt" rows="2" class="w-full px-4 py-2 border rounded-lg"></textarea></div>
                    <div><label class="block text-sm font-medium text-gray-700 mb-1">Content</label><textarea v-model="form.content" rows="8" class="w-full px-4 py-2 border rounded-lg"></textarea></div>
                    <div class="grid grid-cols-2 gap-4">
                        <div><label class="block text-sm font-medium text-gray-700 mb-1">Featured Image URL</label><input v-model="form.featured_image" type="text" class="w-full px-4 py-2 border rounded-lg"></div>
                        <div><label class="block text-sm font-medium text-gray-700 mb-1">Status</label><select v-model="form.status" class="w-full px-4 py-2 border rounded-lg"><option value="draft">Draft</option><option value="published">Published</option></select></div>
                    </div>
                </div>
                <div class="p-6 border-t flex justify-end space-x-3"><button @click="showModal = false" class="px-4 py-2 border rounded-lg">Cancel</button><button @click="savePost" class="px-4 py-2 bg-amber-600 text-white rounded-lg">Save</button></div>
            </div>
        </div>
    </div>
</template>
