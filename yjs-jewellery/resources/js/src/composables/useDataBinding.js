import { ref, computed, readonly } from 'vue'
import axiosAdmin from '@/plugins/axiosAdmin'

/**
 * useDataBinding - Dynamic data resolution for CMS Kernel
 *
 * Manages data providers for widgets to fetch dynamic content
 * from any domain (products, categories, offers, external APIs)
 */
export function useDataBinding() {
    // State
    const providers = ref([])
    const isLoadingProviders = ref(false)
    const boundData = ref({}) // Cache for resolved data keyed by widget ID
    const previewData = ref(null)
    const isPreviewLoading = ref(false)
    const error = ref(null)

    // Computed
    const availableProviders = computed(() => {
        return providers.value.filter(p => p.is_active !== false)
    })

    const providersByCategory = computed(() => {
        const grouped = {}
        providers.value.forEach(provider => {
            const category = provider.category || 'general'
            if (!grouped[category]) {
                grouped[category] = []
            }
            grouped[category].push(provider)
        })
        return grouped
    })

    // Methods

    /**
     * Load available data providers from backend
     */
    const loadProviders = async () => {
        if (providers.value.length > 0) return providers.value

        isLoadingProviders.value = true
        error.value = null

        try {
            const response = await axiosAdmin.get('/cms/data-providers')

            if (response.data.success) {
                providers.value = response.data.data || []
                return providers.value
            }
        } catch (err) {
            error.value = err.message || 'Failed to load providers'
            console.error('Error loading providers:', err)
        } finally {
            isLoadingProviders.value = false
        }

        return []
    }

    /**
     * Get schema for a specific provider
     */
    const getProviderSchema = async (providerId) => {
        try {
            const response = await axiosAdmin.get(`/cms/data-providers/${providerId}/schema`)

            if (response.data.success) {
                return response.data.data
            }
        } catch (err) {
            console.error('Error loading provider schema:', err)
        }
        return null
    }

    /**
     * Preview data for a binding configuration
     */
    const previewBinding = async (bindingConfig) => {
        isPreviewLoading.value = true
        previewData.value = null
        error.value = null

        try {
            const response = await axiosAdmin.post('/cms/data-providers/preview', {
                provider: bindingConfig.provider,
                config: bindingConfig.config || {}
            })

            if (response.data.success) {
                previewData.value = response.data.data
                return response.data.data
            }
        } catch (err) {
            error.value = err.message || 'Failed to preview binding'
            console.error('Error previewing binding:', err)
        } finally {
            isPreviewLoading.value = false
        }

        return null
    }

    /**
     * Resolve all bindings for a set of widgets
     */
    const resolveAllBindings = async (widgets) => {
        const resolvedData = {}

        for (const widget of widgets) {
            if (widget.data_binding) {
                try {
                    const result = await previewBinding(widget.data_binding)
                    if (result) {
                        resolvedData[widget.id] = result
                    }
                } catch (err) {
                    console.error(`Error resolving binding for widget ${widget.id}:`, err)
                }
            }
        }

        boundData.value = { ...boundData.value, ...resolvedData }
        return resolvedData
    }

    /**
     * Get cached data for a widget
     */
    const getWidgetData = (widgetId) => {
        return boundData.value[widgetId] || null
    }

    /**
     * Clear cached data for a widget
     */
    const clearWidgetData = (widgetId) => {
        if (widgetId) {
            delete boundData.value[widgetId]
        }
    }

    /**
     * Clear all cached data
     */
    const clearAllData = () => {
        boundData.value = {}
    }

    /**
     * Create a new binding configuration
     */
    const createBindingConfig = (providerId, config = {}) => {
        return {
            provider: providerId,
            config: config,
            field_mappings: {},
            cache_ttl: 300 // 5 minutes default
        }
    }

    /**
     * Update field mappings for a binding
     */
    const updateFieldMappings = (binding, mappings) => {
        return {
            ...binding,
            field_mappings: {
                ...binding.field_mappings,
                ...mappings
            }
        }
    }

    /**
     * Get available fields for a provider
     */
    const getProviderFields = (providerId) => {
        const provider = providers.value.find(p => p.identifier === providerId)
        return provider?.available_fields || []
    }

    /**
     * Check if a provider supports a capability
     */
    const providerSupports = (providerId, capability) => {
        const provider = providers.value.find(p => p.identifier === providerId)
        return provider?.capabilities?.includes(capability) || false
    }

    /**
     * Reset state
     */
    const reset = () => {
        boundData.value = {}
        previewData.value = null
        error.value = null
    }

    return {
        // State
        providers: readonly(providers),
        isLoadingProviders: readonly(isLoadingProviders),
        boundData: readonly(boundData),
        previewData: readonly(previewData),
        isPreviewLoading: readonly(isPreviewLoading),
        error: readonly(error),

        // Computed
        availableProviders,
        providersByCategory,

        // Methods
        loadProviders,
        getProviderSchema,
        previewBinding,
        resolveAllBindings,
        getWidgetData,
        clearWidgetData,
        clearAllData,
        createBindingConfig,
        updateFieldMappings,
        getProviderFields,
        providerSupports,
        reset
    }
}
