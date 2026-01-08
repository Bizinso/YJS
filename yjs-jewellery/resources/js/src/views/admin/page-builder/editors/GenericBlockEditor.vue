<script setup>
import { ref, computed, watch } from 'vue'
import { usePageBuilder } from '@/composables/usePageBuilder'

const props = defineProps({
    block: {
        type: Object,
        required: true
    }
})

const emit = defineEmits(['update:data', 'update:settings'])

const { blockConfig } = usePageBuilder()

// Local copy of data for editing
const localData = ref({})

// Initialize local data from block
watch(() => props.block, (block) => {
    if (block) {
        localData.value = JSON.parse(JSON.stringify(block.data || {}))
    }
}, { immediate: true, deep: true })

const blockInfo = computed(() => {
    return blockConfig.value?.blocks[props.block.type] || {
        name: props.block.type,
        icon: 'bi-square',
        description: ''
    }
})

// Infer field type from value
const getFieldType = (key, value) => {
    if (key.includes('image') || key.includes('src') || key.includes('thumbnail') || key.includes('background')) {
        return 'image'
    }
    if (key.includes('color')) {
        return 'color'
    }
    if (key.includes('link') || key.includes('url') || key.includes('href')) {
        return 'url'
    }
    if (key.includes('content') || key.includes('html') || key.includes('body') || key.includes('description')) {
        return 'textarea'
    }
    if (typeof value === 'boolean') {
        return 'checkbox'
    }
    if (typeof value === 'number') {
        return 'number'
    }
    if (Array.isArray(value)) {
        return 'array'
    }
    if (typeof value === 'object' && value !== null) {
        return 'object'
    }
    return 'text'
}

// Format field label from key
const formatLabel = (key) => {
    return key
        .replace(/_/g, ' ')
        .replace(/([A-Z])/g, ' $1')
        .replace(/^./, str => str.toUpperCase())
        .trim()
}

// Get fields from block data
const fields = computed(() => {
    const data = localData.value || {}
    return Object.entries(data)
        .filter(([key, value]) => !key.startsWith('_')) // Skip internal fields
        .map(([key, value]) => ({
            key,
            value,
            type: getFieldType(key, value),
            label: formatLabel(key)
        }))
        .sort((a, b) => {
            // Priority order: title, subtitle, description, content, then rest
            const priority = ['title', 'heading', 'name', 'subtitle', 'description', 'content', 'text', 'image', 'src']
            const aIndex = priority.findIndex(p => a.key.includes(p))
            const bIndex = priority.findIndex(p => b.key.includes(p))
            if (aIndex === -1 && bIndex === -1) return 0
            if (aIndex === -1) return 1
            if (bIndex === -1) return -1
            return aIndex - bIndex
        })
})

// Update field value
const updateField = (key, value) => {
    localData.value[key] = value
    emit('update:data', { ...localData.value })
}

// Add new field
const showAddField = ref(false)
const newFieldKey = ref('')
const newFieldType = ref('text')

const addField = () => {
    if (!newFieldKey.value.trim()) return

    const key = newFieldKey.value.trim().toLowerCase().replace(/\s+/g, '_')
    let defaultValue = ''

    switch (newFieldType.value) {
        case 'number': defaultValue = 0; break
        case 'checkbox': defaultValue = false; break
        case 'array': defaultValue = []; break
        default: defaultValue = ''
    }

    localData.value[key] = defaultValue
    emit('update:data', { ...localData.value })

    newFieldKey.value = ''
    showAddField.value = false
}

// Remove field
const removeField = (key) => {
    if (confirm(`Remove "${formatLabel(key)}" field?`)) {
        delete localData.value[key]
        emit('update:data', { ...localData.value })
    }
}
</script>

<template>
    <div class="space-y-4">
        <!-- Block info -->
        <div class="bg-gray-50 rounded-lg p-3 mb-4">
            <div class="flex items-center space-x-2">
                <i :class="['bi', blockInfo.icon, 'text-gray-400']"></i>
                <span class="font-medium text-gray-700">{{ blockInfo.name }}</span>
            </div>
            <p v-if="blockInfo.description" class="text-xs text-gray-500 mt-1">
                {{ blockInfo.description }}
            </p>
        </div>

        <!-- Fields -->
        <div v-if="fields.length > 0" class="space-y-4">
            <div v-for="field in fields" :key="field.key" class="relative group">
                <!-- Text Field -->
                <div v-if="field.type === 'text'">
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        {{ field.label }}
                    </label>
                    <input
                        type="text"
                        :value="field.value"
                        @input="updateField(field.key, $event.target.value)"
                        class="w-full px-3 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500"
                    >
                </div>

                <!-- Number Field -->
                <div v-else-if="field.type === 'number'">
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        {{ field.label }}
                    </label>
                    <input
                        type="number"
                        :value="field.value"
                        @input="updateField(field.key, parseFloat($event.target.value) || 0)"
                        class="w-full px-3 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500"
                    >
                </div>

                <!-- Textarea Field -->
                <div v-else-if="field.type === 'textarea'">
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        {{ field.label }}
                    </label>
                    <textarea
                        :value="field.value"
                        @input="updateField(field.key, $event.target.value)"
                        rows="4"
                        class="w-full px-3 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500"
                    ></textarea>
                </div>

                <!-- URL Field -->
                <div v-else-if="field.type === 'url'">
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        {{ field.label }}
                    </label>
                    <input
                        type="url"
                        :value="field.value"
                        @input="updateField(field.key, $event.target.value)"
                        placeholder="https://"
                        class="w-full px-3 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500"
                    >
                </div>

                <!-- Image Field -->
                <div v-else-if="field.type === 'image'">
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        {{ field.label }}
                    </label>
                    <div class="space-y-2">
                        <input
                            type="url"
                            :value="field.value"
                            @input="updateField(field.key, $event.target.value)"
                            placeholder="Image URL..."
                            class="w-full px-3 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500"
                        >
                        <div v-if="field.value" class="relative">
                            <img
                                :src="field.value"
                                class="w-full h-32 object-cover rounded-lg border"
                                @error="$event.target.style.display='none'"
                            >
                        </div>
                    </div>
                </div>

                <!-- Color Field -->
                <div v-else-if="field.type === 'color'">
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        {{ field.label }}
                    </label>
                    <div class="flex items-center space-x-2">
                        <input
                            type="color"
                            :value="field.value"
                            @input="updateField(field.key, $event.target.value)"
                            class="w-10 h-10 rounded border cursor-pointer"
                        >
                        <input
                            type="text"
                            :value="field.value"
                            @input="updateField(field.key, $event.target.value)"
                            class="flex-1 px-3 py-2 border rounded-lg text-sm"
                        >
                    </div>
                </div>

                <!-- Checkbox Field -->
                <div v-else-if="field.type === 'checkbox'">
                    <label class="flex items-center cursor-pointer">
                        <input
                            type="checkbox"
                            :checked="field.value"
                            @change="updateField(field.key, $event.target.checked)"
                            class="rounded border-gray-300 text-amber-600 focus:ring-amber-500"
                        >
                        <span class="ml-2 text-sm font-medium text-gray-700">{{ field.label }}</span>
                    </label>
                </div>

                <!-- Array Field (simple text list) -->
                <div v-else-if="field.type === 'array'">
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        {{ field.label }} ({{ field.value?.length || 0 }} items)
                    </label>
                    <div class="text-xs text-gray-500 bg-gray-50 p-2 rounded">
                        Complex array - edit via JSON
                    </div>
                </div>

                <!-- Object Field -->
                <div v-else-if="field.type === 'object'">
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        {{ field.label }}
                    </label>
                    <div class="text-xs text-gray-500 bg-gray-50 p-2 rounded">
                        Complex object - edit via JSON
                    </div>
                </div>

                <!-- Remove field button -->
                <button
                    @click="removeField(field.key)"
                    class="absolute -right-2 -top-2 opacity-0 group-hover:opacity-100 w-5 h-5 bg-red-500 text-white rounded-full text-xs flex items-center justify-center hover:bg-red-600 transition-opacity"
                    title="Remove field"
                >
                    <i class="bi bi-x"></i>
                </button>
            </div>
        </div>

        <!-- No fields message -->
        <div v-else class="text-center py-6 text-gray-400">
            <i class="bi bi-inbox text-3xl"></i>
            <p class="mt-2 text-sm">No content fields configured</p>
            <p class="text-xs">Add fields below to customize this block</p>
        </div>

        <!-- Add Field Section -->
        <div class="border-t pt-4 mt-4">
            <button
                v-if="!showAddField"
                @click="showAddField = true"
                class="w-full py-2 border-2 border-dashed border-gray-300 rounded-lg text-gray-500 hover:border-amber-400 hover:text-amber-600 transition-colors text-sm"
            >
                <i class="bi bi-plus-lg me-1"></i>
                Add Field
            </button>

            <div v-else class="space-y-3 bg-gray-50 p-3 rounded-lg">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Field Name</label>
                    <input
                        v-model="newFieldKey"
                        type="text"
                        placeholder="e.g., button_text"
                        class="w-full px-3 py-2 border rounded-lg text-sm"
                    >
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Field Type</label>
                    <select v-model="newFieldType" class="w-full px-3 py-2 border rounded-lg text-sm">
                        <option value="text">Text</option>
                        <option value="textarea">Long Text</option>
                        <option value="number">Number</option>
                        <option value="checkbox">Checkbox</option>
                        <option value="url">URL</option>
                        <option value="image">Image URL</option>
                        <option value="color">Color</option>
                    </select>
                </div>
                <div class="flex space-x-2">
                    <button
                        @click="addField"
                        class="flex-1 py-2 bg-amber-600 text-white rounded-lg text-sm hover:bg-amber-700"
                    >
                        Add
                    </button>
                    <button
                        @click="showAddField = false; newFieldKey = ''"
                        class="px-4 py-2 border rounded-lg text-sm hover:bg-gray-100"
                    >
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
