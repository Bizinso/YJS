<script setup>
import { ref, computed, onMounted } from 'vue'
import axios from '@/plugins/axiosAdmin'

// State
const loading = ref(true)
const saving = ref(false)
const contentTypes = ref([])
const fieldTypes = ref([])
const showModal = ref(false)
const editingType = ref(null)

// Form state
const form = ref({
    name: '',
    description: '',
    icon: 'bi-file-earmark',
    fields: [],
    supports_versioning: true,
    supports_scheduling: true,
})

// Computed
const modalTitle = computed(() => {
    return editingType.value ? 'Edit Content Type' : 'Create Content Type'
})

const isEditing = computed(() => !!editingType.value)

// Methods
const fetchContentTypes = async () => {
    loading.value = true
    try {
        const { data } = await axios.get('/cms/content-types')
        if (data.success) {
            contentTypes.value = data.data
        }
    } catch (error) {
        console.error('Failed to fetch content types:', error)
    } finally {
        loading.value = false
    }
}

const fetchFieldTypes = async () => {
    try {
        const { data } = await axios.get('/cms/content-types/field-types')
        if (data.success) {
            fieldTypes.value = data.data
        }
    } catch (error) {
        console.error('Failed to fetch field types:', error)
    }
}

const openCreateModal = () => {
    editingType.value = null
    form.value = {
        name: '',
        description: '',
        icon: 'bi-file-earmark',
        fields: [],
        supports_versioning: true,
        supports_scheduling: true,
    }
    showModal.value = true
}

const openEditModal = (contentType) => {
    editingType.value = contentType
    form.value = {
        name: contentType.name,
        description: contentType.description || '',
        icon: contentType.icon || 'bi-file-earmark',
        fields: [...(contentType.fields || [])],
        supports_versioning: contentType.supports_versioning,
        supports_scheduling: contentType.supports_scheduling,
    }
    showModal.value = true
}

const closeModal = () => {
    showModal.value = false
    editingType.value = null
}

const addField = () => {
    form.value.fields.push({
        name: '',
        type: 'text',
        label: '',
        required: false,
        options: {},
    })
}

const removeField = (index) => {
    form.value.fields.splice(index, 1)
}

const moveField = (index, direction) => {
    const newIndex = index + direction
    if (newIndex < 0 || newIndex >= form.value.fields.length) return

    const fields = [...form.value.fields]
    const [field] = fields.splice(index, 1)
    fields.splice(newIndex, 0, field)
    form.value.fields = fields
}

const saveContentType = async () => {
    if (!form.value.name || form.value.fields.length === 0) {
        alert('Name and at least one field are required')
        return
    }

    // Validate fields have names and labels
    for (const field of form.value.fields) {
        if (!field.name || !field.label) {
            alert('All fields must have a name and label')
            return
        }
    }

    saving.value = true
    try {
        if (editingType.value) {
            await axios.put(`/cms/content-types/${editingType.value.id}`, form.value)
        } else {
            await axios.post('/cms/content-types', form.value)
        }

        closeModal()
        await fetchContentTypes()
    } catch (error) {
        console.error('Failed to save content type:', error)
        alert(error.response?.data?.message || 'Failed to save content type')
    } finally {
        saving.value = false
    }
}

const deleteContentType = async (contentType) => {
    if (contentType.is_system) {
        alert('Cannot delete system content types')
        return
    }

    if (contentType.pages_count > 0) {
        alert(`Cannot delete: ${contentType.pages_count} page(s) use this content type`)
        return
    }

    if (!confirm(`Delete content type "${contentType.name}"?`)) return

    try {
        await axios.delete(`/cms/content-types/${contentType.id}`)
        await fetchContentTypes()
    } catch (error) {
        console.error('Failed to delete content type:', error)
        alert(error.response?.data?.message || 'Failed to delete content type')
    }
}

const getFieldTypeLabel = (type) => {
    const fieldType = fieldTypes.value.find(f => f.type === type)
    return fieldType?.label || type
}

const getFieldTypeIcon = (type) => {
    const fieldType = fieldTypes.value.find(f => f.type === type)
    return fieldType?.icon || 'bi-input-cursor'
}

// Icon options for content types
const iconOptions = [
    'bi-file-earmark',
    'bi-file-text',
    'bi-file-richtext',
    'bi-newspaper',
    'bi-collection',
    'bi-grid',
    'bi-card-text',
    'bi-layout-text-window',
    'bi-megaphone',
    'bi-calendar-event',
    'bi-people',
    'bi-building',
    'bi-briefcase',
    'bi-cart',
    'bi-star',
]

// Lifecycle
onMounted(() => {
    fetchContentTypes()
    fetchFieldTypes()
})
</script>

<template>
    <div class="content-types-page p-6">
        <!-- Header -->
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Content Types</h1>
                <p class="text-gray-500 mt-1">Define custom page structures with fields</p>
            </div>
            <button
                @click="openCreateModal"
                class="px-4 py-2 bg-amber-600 text-white rounded-lg hover:bg-amber-700 flex items-center gap-2"
            >
                <i class="bi bi-plus-lg"></i>
                Create Content Type
            </button>
        </div>

        <!-- Loading -->
        <div v-if="loading" class="text-center py-12">
            <div class="w-8 h-8 border-2 border-amber-500 border-t-transparent rounded-full animate-spin mx-auto"></div>
            <p class="text-gray-500 mt-2">Loading content types...</p>
        </div>

        <!-- Empty State -->
        <div v-else-if="contentTypes.length === 0" class="text-center py-12 bg-white rounded-xl border border-gray-200">
            <i class="bi bi-collection text-5xl text-gray-300"></i>
            <h3 class="text-lg font-medium text-gray-800 mt-4">No Content Types</h3>
            <p class="text-gray-500 mt-1">Create your first content type to define custom page structures</p>
            <button
                @click="openCreateModal"
                class="mt-4 px-4 py-2 bg-amber-600 text-white rounded-lg hover:bg-amber-700"
            >
                Create Content Type
            </button>
        </div>

        <!-- Content Types Grid -->
        <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <div
                v-for="ct in contentTypes"
                :key="ct.id"
                class="bg-white rounded-xl border border-gray-200 p-5 hover:shadow-md transition-shadow"
            >
                <div class="flex items-start justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-amber-100 rounded-lg flex items-center justify-center">
                            <i :class="[ct.icon || 'bi-file-earmark', 'text-xl text-amber-600']"></i>
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-800">{{ ct.name }}</h3>
                            <p class="text-sm text-gray-500">{{ ct.slug }}</p>
                        </div>
                    </div>
                    <span
                        v-if="ct.is_system"
                        class="px-2 py-0.5 bg-gray-100 text-gray-600 text-xs rounded-full"
                    >
                        System
                    </span>
                </div>

                <p v-if="ct.description" class="text-sm text-gray-600 mt-3 line-clamp-2">
                    {{ ct.description }}
                </p>

                <div class="mt-4 flex items-center gap-4 text-sm text-gray-500">
                    <span>
                        <i class="bi bi-list-ul mr-1"></i>
                        {{ ct.fields?.length || 0 }} fields
                    </span>
                    <span>
                        <i class="bi bi-file-earmark mr-1"></i>
                        {{ ct.pages_count || 0 }} pages
                    </span>
                </div>

                <div class="mt-4 flex items-center gap-2 pt-4 border-t border-gray-100">
                    <button
                        @click="openEditModal(ct)"
                        :disabled="ct.is_system"
                        class="flex-1 px-3 py-1.5 text-sm border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <i class="bi bi-pencil mr-1"></i> Edit
                    </button>
                    <button
                        @click="deleteContentType(ct)"
                        :disabled="ct.is_system || ct.pages_count > 0"
                        class="px-3 py-1.5 text-sm text-red-600 hover:bg-red-50 rounded-lg disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Create/Edit Modal -->
        <Teleport to="body">
            <div
                v-if="showModal"
                class="fixed inset-0 z-50 flex items-center justify-center p-4"
            >
                <div class="absolute inset-0 bg-black bg-opacity-50" @click="closeModal"></div>
                <div class="relative bg-white rounded-xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-hidden">
                    <!-- Modal Header -->
                    <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                        <h2 class="text-lg font-semibold text-gray-800">{{ modalTitle }}</h2>
                        <button @click="closeModal" class="text-gray-400 hover:text-gray-600">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>

                    <!-- Modal Body -->
                    <div class="p-6 overflow-y-auto max-h-[calc(90vh-140px)]">
                        <!-- Basic Info -->
                        <div class="grid grid-cols-2 gap-4 mb-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Name *</label>
                                <input
                                    v-model="form.name"
                                    type="text"
                                    placeholder="e.g., Blog Post"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-amber-500 focus:border-amber-500"
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Icon</label>
                                <select
                                    v-model="form.icon"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-amber-500 focus:border-amber-500"
                                >
                                    <option v-for="icon in iconOptions" :key="icon" :value="icon">
                                        {{ icon }}
                                    </option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                            <textarea
                                v-model="form.description"
                                rows="2"
                                placeholder="Describe this content type..."
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-amber-500 focus:border-amber-500"
                            ></textarea>
                        </div>

                        <!-- Options -->
                        <div class="flex gap-6 mb-6">
                            <label class="flex items-center gap-2">
                                <input
                                    v-model="form.supports_versioning"
                                    type="checkbox"
                                    class="rounded border-gray-300 text-amber-600 focus:ring-amber-500"
                                />
                                <span class="text-sm text-gray-700">Enable versioning</span>
                            </label>
                            <label class="flex items-center gap-2">
                                <input
                                    v-model="form.supports_scheduling"
                                    type="checkbox"
                                    class="rounded border-gray-300 text-amber-600 focus:ring-amber-500"
                                />
                                <span class="text-sm text-gray-700">Enable scheduling</span>
                            </label>
                        </div>

                        <!-- Fields -->
                        <div>
                            <div class="flex items-center justify-between mb-3">
                                <h3 class="font-medium text-gray-800">Custom Fields</h3>
                                <button
                                    @click="addField"
                                    class="text-sm text-amber-600 hover:text-amber-700"
                                >
                                    <i class="bi bi-plus-lg mr-1"></i> Add Field
                                </button>
                            </div>

                            <div v-if="form.fields.length === 0" class="text-center py-8 bg-gray-50 rounded-lg border-2 border-dashed border-gray-200">
                                <i class="bi bi-input-cursor text-3xl text-gray-300"></i>
                                <p class="text-sm text-gray-500 mt-2">No fields added yet</p>
                                <button
                                    @click="addField"
                                    class="mt-2 text-sm text-amber-600 hover:text-amber-700"
                                >
                                    Add your first field
                                </button>
                            </div>

                            <div v-else class="space-y-3">
                                <div
                                    v-for="(field, index) in form.fields"
                                    :key="index"
                                    class="p-4 bg-gray-50 rounded-lg border border-gray-200"
                                >
                                    <div class="flex items-start gap-3">
                                        <!-- Move buttons -->
                                        <div class="flex flex-col gap-1">
                                            <button
                                                @click="moveField(index, -1)"
                                                :disabled="index === 0"
                                                class="w-6 h-6 text-gray-400 hover:text-gray-600 disabled:opacity-30"
                                            >
                                                <i class="bi bi-chevron-up"></i>
                                            </button>
                                            <button
                                                @click="moveField(index, 1)"
                                                :disabled="index === form.fields.length - 1"
                                                class="w-6 h-6 text-gray-400 hover:text-gray-600 disabled:opacity-30"
                                            >
                                                <i class="bi bi-chevron-down"></i>
                                            </button>
                                        </div>

                                        <!-- Field config -->
                                        <div class="flex-1 grid grid-cols-3 gap-3">
                                            <div>
                                                <label class="block text-xs text-gray-500 mb-1">Field Name *</label>
                                                <input
                                                    v-model="field.name"
                                                    type="text"
                                                    placeholder="e.g., author"
                                                    class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded focus:ring-amber-500 focus:border-amber-500"
                                                />
                                            </div>
                                            <div>
                                                <label class="block text-xs text-gray-500 mb-1">Label *</label>
                                                <input
                                                    v-model="field.label"
                                                    type="text"
                                                    placeholder="e.g., Author"
                                                    class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded focus:ring-amber-500 focus:border-amber-500"
                                                />
                                            </div>
                                            <div>
                                                <label class="block text-xs text-gray-500 mb-1">Type</label>
                                                <select
                                                    v-model="field.type"
                                                    class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded focus:ring-amber-500 focus:border-amber-500"
                                                >
                                                    <option
                                                        v-for="ft in fieldTypes"
                                                        :key="ft.type"
                                                        :value="ft.type"
                                                    >
                                                        {{ ft.label }}
                                                    </option>
                                                </select>
                                            </div>
                                        </div>

                                        <!-- Required + Delete -->
                                        <div class="flex items-center gap-2">
                                            <label class="flex items-center gap-1">
                                                <input
                                                    v-model="field.required"
                                                    type="checkbox"
                                                    class="rounded border-gray-300 text-amber-600 focus:ring-amber-500"
                                                />
                                                <span class="text-xs text-gray-500">Required</span>
                                            </label>
                                            <button
                                                @click="removeField(index)"
                                                class="p-1 text-red-500 hover:text-red-700"
                                            >
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Modal Footer -->
                    <div class="px-6 py-4 border-t border-gray-200 flex justify-end gap-3">
                        <button
                            @click="closeModal"
                            class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50"
                        >
                            Cancel
                        </button>
                        <button
                            @click="saveContentType"
                            :disabled="saving"
                            class="px-4 py-2 bg-amber-600 text-white rounded-lg hover:bg-amber-700 disabled:opacity-50"
                        >
                            <i v-if="saving" class="bi bi-hourglass-split animate-spin mr-1"></i>
                            {{ saving ? 'Saving...' : (isEditing ? 'Update' : 'Create') }}
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </div>
</template>

<style scoped>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
