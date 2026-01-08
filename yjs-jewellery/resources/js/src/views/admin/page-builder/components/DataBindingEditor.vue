<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import axios from '@/plugins/axiosAdmin'

const props = defineProps({
    widgetId: {
        type: String,
        required: true
    },
    binding: {
        type: Object,
        default: null
    },
    supportedProviders: {
        type: Array,
        default: () => []
    }
})

const emit = defineEmits(['update', 'preview', 'close'])

// State
const loading = ref(false)
const previewLoading = ref(false)
const providers = ref([])
const selectedProvider = ref(null)
const providerConfig = ref({})
const previewData = ref(null)
const previewError = ref(null)

// Computed
const availableProviders = computed(() => {
    if (props.supportedProviders.length === 0) {
        return providers.value
    }
    return providers.value.filter(p =>
        props.supportedProviders.includes(p.identifier)
    )
})

const providerSchema = computed(() => {
    return selectedProvider.value?.config_schema || {}
})

const providerFields = computed(() => {
    return selectedProvider.value?.available_fields || []
})

const hasBinding = computed(() => {
    return selectedProvider.value !== null
})

// Methods
const loadProviders = async () => {
    loading.value = true
    try {
        const { data } = await axios.get('/cms/data-providers')
        if (data.success) {
            providers.value = data.data || []
        }
    } catch (error) {
        console.error('Failed to load providers:', error)
    } finally {
        loading.value = false
    }
}

const selectProvider = (provider) => {
    selectedProvider.value = provider

    // Initialize config with defaults
    providerConfig.value = {}
    if (provider.config_schema) {
        Object.entries(provider.config_schema).forEach(([key, schema]) => {
            if (schema.default !== undefined) {
                providerConfig.value[key] = schema.default
            }
        })
    }

    previewData.value = null
    previewError.value = null
}

const updateConfig = (key, value) => {
    providerConfig.value = {
        ...providerConfig.value,
        [key]: value
    }
}

const loadPreview = async () => {
    if (!selectedProvider.value) return

    previewLoading.value = true
    previewError.value = null

    try {
        const { data } = await axios.post('/cms/data-providers/preview', {
            provider: selectedProvider.value.identifier,
            config: providerConfig.value
        })

        if (data.success) {
            previewData.value = data.data
            emit('preview', data.data)
        }
    } catch (error) {
        previewError.value = error.response?.data?.message || 'Failed to load preview'
        console.error('Failed to preview binding:', error)
    } finally {
        previewLoading.value = false
    }
}

const saveBinding = () => {
    if (!selectedProvider.value) return

    const binding = {
        provider: selectedProvider.value.identifier,
        config: providerConfig.value,
        cache_ttl: selectedProvider.value.cache_ttl || 300
    }

    emit('update', binding)
}

const removeBinding = () => {
    selectedProvider.value = null
    providerConfig.value = {}
    previewData.value = null
    emit('update', null)
}

const isFieldVisible = (schema, key) => {
    if (!schema.visible_when) return true

    return Object.entries(schema.visible_when).every(([field, value]) => {
        return providerConfig.value[field] === value
    })
}

// Initialize from existing binding
const initFromBinding = () => {
    if (props.binding) {
        const provider = providers.value.find(p => p.identifier === props.binding.provider)
        if (provider) {
            selectedProvider.value = provider
            providerConfig.value = { ...props.binding.config }
        }
    }
}

// Lifecycle
onMounted(async () => {
    await loadProviders()
    initFromBinding()
})

// Watch for binding changes
watch(() => props.binding, () => {
    initFromBinding()
}, { deep: true })
</script>

<template>
    <div class="data-binding-editor">
        <!-- Header -->
        <div class="flex items-center justify-between mb-4">
            <h4 class="font-medium text-gray-800">Data Binding</h4>
            <button
                v-if="hasBinding"
                @click="removeBinding"
                class="text-sm text-red-600 hover:text-red-700"
            >
                <i class="bi bi-x-circle mr-1"></i> Remove
            </button>
        </div>

        <!-- Loading -->
        <div v-if="loading" class="text-center py-4">
            <div class="w-6 h-6 border-2 border-amber-500 border-t-transparent rounded-full animate-spin mx-auto"></div>
            <p class="text-sm text-gray-500 mt-2">Loading providers...</p>
        </div>

        <!-- Provider Selection -->
        <div v-else-if="!selectedProvider" class="space-y-2">
            <p class="text-sm text-gray-600 mb-3">
                Connect dynamic data to this widget
            </p>

            <div
                v-for="provider in availableProviders"
                :key="provider.identifier"
                @click="selectProvider(provider)"
                class="p-3 border border-gray-200 rounded-lg hover:border-amber-500 hover:bg-amber-50 cursor-pointer transition-colors"
            >
                <div class="flex items-center gap-2">
                    <i :class="[provider.icon || 'bi-database', 'text-lg text-amber-600']"></i>
                    <div>
                        <p class="font-medium text-gray-800">{{ provider.name }}</p>
                        <p class="text-xs text-gray-500">{{ provider.description }}</p>
                    </div>
                </div>
            </div>

            <p v-if="availableProviders.length === 0" class="text-sm text-gray-500 text-center py-4">
                No data providers available for this widget type
            </p>
        </div>

        <!-- Provider Configuration -->
        <div v-else class="space-y-4">
            <!-- Selected Provider -->
            <div class="flex items-center justify-between p-2 bg-amber-50 rounded-lg">
                <div class="flex items-center gap-2">
                    <i :class="[selectedProvider.icon || 'bi-database', 'text-amber-600']"></i>
                    <span class="font-medium text-gray-800">{{ selectedProvider.name }}</span>
                </div>
                <button
                    @click="selectedProvider = null; providerConfig = {}"
                    class="text-gray-400 hover:text-gray-600"
                >
                    <i class="bi bi-pencil"></i>
                </button>
            </div>

            <!-- Config Fields -->
            <div class="space-y-3">
                <template v-for="(schema, key) in providerSchema" :key="key">
                    <div v-if="isFieldVisible(schema, key)" class="form-group">
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            {{ schema.label }}
                        </label>

                        <!-- Select -->
                        <select
                            v-if="schema.type === 'select'"
                            :value="providerConfig[key]"
                            @change="updateConfig(key, $event.target.value)"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-amber-500 focus:border-amber-500"
                        >
                            <option
                                v-for="option in schema.options"
                                :key="option.value"
                                :value="option.value"
                            >
                                {{ option.label }}
                            </option>
                        </select>

                        <!-- Number -->
                        <input
                            v-else-if="schema.type === 'number'"
                            type="number"
                            :value="providerConfig[key]"
                            @input="updateConfig(key, parseInt($event.target.value))"
                            :min="schema.min"
                            :max="schema.max"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-amber-500 focus:border-amber-500"
                        />

                        <!-- Multi-select (IDs) -->
                        <div v-else-if="schema.type === 'multi_select'" class="text-xs text-gray-500">
                            Manual selection coming soon
                        </div>

                        <!-- Text -->
                        <input
                            v-else
                            type="text"
                            :value="providerConfig[key]"
                            @input="updateConfig(key, $event.target.value)"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-amber-500 focus:border-amber-500"
                        />
                    </div>
                </template>
            </div>

            <!-- Preview Button -->
            <button
                @click="loadPreview"
                :disabled="previewLoading"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm hover:bg-gray-50 disabled:opacity-50"
            >
                <i :class="[previewLoading ? 'bi-hourglass-split animate-spin' : 'bi-eye', 'mr-1']"></i>
                {{ previewLoading ? 'Loading...' : 'Preview Data' }}
            </button>

            <!-- Preview Result -->
            <div v-if="previewData" class="mt-3 p-3 bg-gray-50 rounded-lg">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-medium text-gray-600">
                        {{ previewData.items?.length || 0 }} items
                    </span>
                    <span class="text-xs text-gray-400">
                        Cache: {{ selectedProvider.cache_ttl || 300 }}s
                    </span>
                </div>

                <div class="max-h-32 overflow-y-auto">
                    <div
                        v-for="(item, index) in (previewData.items || []).slice(0, 3)"
                        :key="index"
                        class="text-xs text-gray-600 truncate py-1 border-b border-gray-200 last:border-0"
                    >
                        {{ item.name || item.title || `Item ${index + 1}` }}
                    </div>
                    <p v-if="(previewData.items || []).length > 3" class="text-xs text-gray-400 pt-1">
                        +{{ previewData.items.length - 3 }} more
                    </p>
                </div>
            </div>

            <!-- Preview Error -->
            <div v-if="previewError" class="mt-3 p-3 bg-red-50 text-red-600 rounded-lg text-sm">
                <i class="bi bi-exclamation-circle mr-1"></i>
                {{ previewError }}
            </div>

            <!-- Save Button -->
            <button
                @click="saveBinding"
                class="w-full px-4 py-2 bg-amber-600 text-white rounded-lg text-sm hover:bg-amber-700"
            >
                <i class="bi bi-check-lg mr-1"></i>
                Apply Binding
            </button>
        </div>
    </div>
</template>

<style scoped>
.data-binding-editor {
    padding: 1rem;
    background: white;
    border-radius: 0.5rem;
    border: 1px solid #e5e7eb;
}
</style>
