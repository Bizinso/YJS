<script setup>
import { ref, computed, onMounted } from 'vue'
import { usePageBuilder } from '@/composables/usePageBuilder'
import axiosAdmin from '@/plugins/axiosAdmin'

const emit = defineEmits(['close'])

const { page, updatePageInfo } = usePageBuilder()

// Local state
const form = ref({
    title: '',
    slug: '',
    type: 'static',
    status: 'draft',
    template_id: null,
    scheduled_for: ''
})
const templates = ref([])
const originalSlug = ref('')

const pageTypes = [
    { value: 'homepage', label: 'Homepage' },
    { value: 'static', label: 'Static Page' },
    { value: 'landing', label: 'Landing Page' },
    { value: 'category', label: 'Category Page' }
]

const statusOptions = [
    { value: 'draft', label: 'Draft' },
    { value: 'published', label: 'Published' },
    { value: 'scheduled', label: 'Scheduled' }
]

// Computed
const slugChanged = computed(() => {
    return originalSlug.value && form.value.slug !== originalSlug.value
})

// Methods
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

const generateSlug = () => {
    form.value.slug = form.value.title
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/(^-|-$)/g, '')
}

const handleSave = () => {
    if (!form.value.title.trim()) {
        alert('Please enter a page title')
        return
    }

    if (!form.value.slug.trim()) {
        alert('Please enter a URL slug')
        return
    }

    // Update page info
    updatePageInfo({
        title: form.value.title,
        slug: form.value.slug,
        type: form.value.type,
        status: form.value.status,
        template_id: form.value.template_id,
        scheduled_for: form.value.status === 'scheduled' ? form.value.scheduled_for : null
    })

    emit('close')
}

// Lifecycle
onMounted(() => {
    if (page.value) {
        form.value = {
            title: page.value.title || '',
            slug: page.value.slug || '',
            type: page.value.type || 'static',
            status: page.value.status || 'draft',
            template_id: page.value.template_id || null,
            scheduled_for: page.value.scheduled_for || ''
        }
        originalSlug.value = page.value.slug || ''
    }
    fetchTemplates()
})
</script>

<template>
    <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-lg">
            <!-- Header -->
            <div class="p-6 border-b flex justify-between items-center">
                <h2 class="text-xl font-bold text-gray-800">Page Settings</h2>
                <button @click="$emit('close')" class="text-gray-500 hover:text-gray-700 text-2xl">&times;</button>
            </div>

            <!-- Content -->
            <div class="p-6 space-y-4">
                <!-- Title -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Page Title *</label>
                    <input
                        v-model="form.title"
                        @blur="!originalSlug && generateSlug()"
                        type="text"
                        class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500"
                    >
                </div>

                <!-- Slug -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">URL Slug *</label>
                    <div class="flex items-center">
                        <span class="px-3 py-2 bg-gray-100 border border-r-0 rounded-l-lg text-gray-500">/</span>
                        <input
                            v-model="form.slug"
                            type="text"
                            class="flex-1 px-4 py-2 border rounded-r-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500"
                        >
                    </div>
                    <p v-if="slugChanged" class="mt-1 text-xs text-amber-600">
                        <i class="bi bi-info-circle me-1"></i>
                        Changing the slug will create a redirect from the old URL
                    </p>
                </div>

                <!-- Type and Status Row -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Page Type</label>
                        <select
                            v-model="form.type"
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500"
                        >
                            <option v-for="type in pageTypes" :key="type.value" :value="type.value">
                                {{ type.label }}
                            </option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                        <select
                            v-model="form.status"
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500"
                        >
                            <option v-for="status in statusOptions" :key="status.value" :value="status.value">
                                {{ status.label }}
                            </option>
                        </select>
                    </div>
                </div>

                <!-- Scheduled Date (if scheduled) -->
                <div v-if="form.status === 'scheduled'">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Publish Date & Time</label>
                    <input
                        v-model="form.scheduled_for"
                        type="datetime-local"
                        class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500"
                    >
                </div>

                <!-- Template -->
                <div v-if="templates.length > 0">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Template</label>
                    <select
                        v-model="form.template_id"
                        class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500"
                    >
                        <option :value="null">No Template</option>
                        <option v-for="template in templates" :key="template.id" :value="template.id">
                            {{ template.name }}
                        </option>
                    </select>
                    <p class="mt-1 text-xs text-gray-500">
                        Applying a template will replace current page content
                    </p>
                </div>
            </div>

            <!-- Footer -->
            <div class="p-6 border-t flex justify-end space-x-3">
                <button
                    @click="$emit('close')"
                    class="px-4 py-2 border rounded-lg hover:bg-gray-50"
                >
                    Cancel
                </button>
                <button
                    @click="handleSave"
                    class="px-4 py-2 bg-amber-600 text-white rounded-lg hover:bg-amber-700"
                >
                    Save Changes
                </button>
            </div>
        </div>
    </div>
</template>
