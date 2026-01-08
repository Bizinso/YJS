import { ref, computed, watch, readonly } from 'vue'
import axiosAdmin from '@/plugins/axiosAdmin'
import { useVersioning } from './useVersioning'
import { useDataBinding } from './useDataBinding'
import { useAutoSave } from './useAutoSave'

// Shared state (singleton pattern for page builder)
const page = ref(null)
const widgets = ref([])
const selectedWidgetId = ref(null)
const isDirty = ref(false)
const isSaving = ref(false)
const isLoading = ref(false)
const previewMode = ref('desktop')
const showSeoPanel = ref(false)
const showVersionPanel = ref(false)
const undoStack = ref([])
const redoStack = ref([])

// Widget registry from backend
const widgetRegistry = ref(null)

/**
 * usePageBuilder - Main composable for the CMS Page Builder
 *
 * Integrates with:
 * - useVersioning: For immutable version management
 * - useDataBinding: For dynamic data resolution
 * - useAutoSave: For automatic saving
 */
export function usePageBuilder() {
    // Sub-composables
    const versioning = useVersioning()
    const dataBinding = useDataBinding()

    // Computed
    const selectedWidget = computed(() => {
        if (!selectedWidgetId.value) return null
        return widgets.value.find(w => w.id === selectedWidgetId.value)
    })

    const selectedWidgetIndex = computed(() => {
        if (!selectedWidgetId.value) return -1
        return widgets.value.findIndex(w => w.id === selectedWidgetId.value)
    })

    const canUndo = computed(() => undoStack.value.length > 0)
    const canRedo = computed(() => redoStack.value.length > 0)

    // Block config (alias for backward compatibility with BlockToolbox)
    // Maps 'widgets' to 'blocks' for compatibility
    const blockConfig = computed(() => {
        if (!widgetRegistry.value) return null
        return {
            categories: widgetRegistry.value.categories || {},
            blocks: widgetRegistry.value.widgets || widgetRegistry.value.blocks || {}
        }
    })

    const currentVersion = computed(() => versioning.currentVersion)
    const publishedVersion = computed(() => versioning.publishedVersion)
    const draftVersion = computed(() => versioning.draftVersion)
    const versionHistory = computed(() => versioning.versionHistory)

    // Watch for widget changes to mark dirty
    watch(widgets, () => {
        if (!isLoading.value) {
            isDirty.value = true
            versioning.markDirty()
        }
    }, { deep: true })

    // Methods

    /**
     * Load a page with its current draft version
     */
    const loadPage = async (pageId) => {
        isLoading.value = true
        try {
            const data = await versioning.loadPage(pageId)

            if (data) {
                page.value = data
                widgets.value = data.draft_version?.widgets || data.widgets || []
                isDirty.value = false
                selectedWidgetId.value = null
                undoStack.value = []
                redoStack.value = []

                // Load version history
                await versioning.loadHistory(pageId)

                // Load data providers for binding
                await dataBinding.loadProviders()
            }

            return data
        } catch (error) {
            console.error('Error loading page:', error)
            throw error
        } finally {
            isLoading.value = false
        }
    }

    /**
     * Load widget registry from backend
     */
    const loadWidgetRegistry = async (forceRefresh = false) => {
        // Skip cache if force refresh requested
        if (!forceRefresh && widgetRegistry.value) return widgetRegistry.value

        try {
            const response = await axiosAdmin.get('/cms/widgets')

            if (response.data.success && response.data.data) {
                widgetRegistry.value = response.data.data
                return widgetRegistry.value
            } else {
                // Fall back to default config
                widgetRegistry.value = getDefaultWidgetConfig()
            }
        } catch (error) {
            console.error('Error loading widget registry:', error.message)
            // Fall back to default config
            widgetRegistry.value = getDefaultWidgetConfig()
        }

        return widgetRegistry.value
    }

    /**
     * Save page - creates a NEW version (immutable)
     */
    const savePage = async (note = null) => {
        if (!page.value || isSaving.value) return null

        isSaving.value = true
        try {
            const changes = {
                title: page.value.title,
                slug: page.value.slug,
                widgets: widgets.value,
                layout_settings: page.value.layout_settings || {},
                seo: page.value.seo || {},
                data_bindings: extractDataBindings(widgets.value)
            }

            const result = await versioning.saveVersion(page.value.id, changes, note)

            if (result) {
                isDirty.value = false
                page.value = { ...page.value, ...result }
            }

            return result
        } catch (error) {
            console.error('Error saving page:', error)
            throw error
        } finally {
            isSaving.value = false
        }
    }

    /**
     * Publish the current draft
     */
    const publishPage = async () => {
        return await versioning.publish()
    }

    /**
     * Revert to a previous version
     */
    const revertToVersion = async (versionId, reason = null) => {
        const result = await versioning.revertTo(page.value.id, versionId, reason)

        if (result) {
            // Reload the page to get new draft
            await loadPage(page.value.id)
        }

        return result
    }

    /**
     * Schedule version for future publish
     */
    const schedulePublish = async (publishAt) => {
        const versionId = versioning.draftVersion?.id
        if (versionId) {
            return await versioning.schedule(versionId, publishAt)
        }
    }

    /**
     * Extract data bindings from widgets
     */
    const extractDataBindings = (widgetList) => {
        const bindings = {}

        widgetList.forEach(widget => {
            if (widget.data_binding) {
                bindings[widget.id] = widget.data_binding
            }
        })

        return bindings
    }

    /**
     * Generate unique widget ID
     */
    const generateWidgetId = () => {
        return 'widget_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9)
    }

    /**
     * Add a new widget
     */
    const addWidget = (widgetType, index = null) => {
        const config = widgetRegistry.value?.widgets?.[widgetType] ||
                       widgetRegistry.value?.blocks?.[widgetType]
        if (!config) return null

        saveToUndoStack()

        const newWidget = {
            id: generateWidgetId(),
            type: widgetType,
            order: index !== null ? index : widgets.value.length,
            visible: true,
            data: JSON.parse(JSON.stringify(config.defaultData || config.default_data || {})),
            settings: JSON.parse(JSON.stringify(config.defaultSettings || config.default_settings || {})),
            style: {
                backgroundColor: '#ffffff',
                padding: { top: 40, right: 20, bottom: 40, left: 20 },
                margin: { top: 0, right: 0, bottom: 0, left: 0 },
                customCSS: ''
            },
            responsive: {
                desktop: { visible: true },
                tablet: { visible: true },
                mobile: { visible: true }
            },
            animation: { type: 'none', duration: 500 },
            data_binding: null // For dynamic data
        }

        if (index !== null) {
            widgets.value.splice(index, 0, newWidget)
            widgets.value.forEach((w, i) => w.order = i)
        } else {
            widgets.value.push(newWidget)
        }

        selectedWidgetId.value = newWidget.id
        return newWidget
    }

    /**
     * Add a new block (alias for addWidget for backward compatibility)
     */
    const addBlock = (blockType, index = null) => {
        return addWidget(blockType, index)
    }

    /**
     * Update widget properties
     */
    const updateWidget = (widgetId, updates) => {
        const index = widgets.value.findIndex(w => w.id === widgetId)
        if (index === -1) return

        saveToUndoStack()

        widgets.value[index] = {
            ...widgets.value[index],
            ...updates
        }
    }

    /**
     * Update widget data
     */
    const updateWidgetData = (widgetId, data) => {
        const widget = widgets.value.find(w => w.id === widgetId)
        if (!widget) return

        saveToUndoStack()
        widget.data = { ...widget.data, ...data }
    }

    /**
     * Update widget settings
     */
    const updateWidgetSettings = (widgetId, settings) => {
        const widget = widgets.value.find(w => w.id === widgetId)
        if (!widget) return

        saveToUndoStack()
        widget.settings = { ...widget.settings, ...settings }
    }

    /**
     * Update widget style
     */
    const updateWidgetStyle = (widgetId, style) => {
        const widget = widgets.value.find(w => w.id === widgetId)
        if (!widget) return

        saveToUndoStack()
        widget.style = { ...widget.style, ...style }
    }

    /**
     * Update widget data binding
     */
    const updateWidgetBinding = (widgetId, binding) => {
        const widget = widgets.value.find(w => w.id === widgetId)
        if (!widget) return

        saveToUndoStack()
        widget.data_binding = binding
    }

    /**
     * Delete a widget
     */
    const deleteWidget = (widgetId) => {
        saveToUndoStack()

        const index = widgets.value.findIndex(w => w.id === widgetId)
        if (index > -1) {
            widgets.value.splice(index, 1)
            widgets.value.forEach((w, i) => w.order = i)

            if (selectedWidgetId.value === widgetId) {
                selectedWidgetId.value = null
            }
        }
    }

    /**
     * Duplicate a widget
     */
    const duplicateWidget = (widgetId) => {
        const widget = widgets.value.find(w => w.id === widgetId)
        if (!widget) return null

        saveToUndoStack()

        const newWidget = JSON.parse(JSON.stringify(widget))
        newWidget.id = generateWidgetId()

        const index = widgets.value.findIndex(w => w.id === widgetId)
        widgets.value.splice(index + 1, 0, newWidget)
        widgets.value.forEach((w, i) => w.order = i)

        selectedWidgetId.value = newWidget.id
        return newWidget
    }

    /**
     * Move a widget
     */
    const moveWidget = (fromIndex, toIndex) => {
        if (fromIndex === toIndex) return

        saveToUndoStack()

        const [widget] = widgets.value.splice(fromIndex, 1)
        widgets.value.splice(toIndex, 0, widget)
        widgets.value.forEach((w, i) => w.order = i)
    }

    /**
     * Select a widget
     */
    const selectWidget = (widgetId) => {
        selectedWidgetId.value = widgetId
    }

    /**
     * Deselect widget
     */
    const deselectWidget = () => {
        selectedWidgetId.value = null
    }

    // Undo/Redo

    const saveToUndoStack = () => {
        undoStack.value.push(JSON.stringify(widgets.value))
        redoStack.value = []

        if (undoStack.value.length > 50) {
            undoStack.value.shift()
        }
    }

    const undo = () => {
        if (undoStack.value.length === 0) return

        redoStack.value.push(JSON.stringify(widgets.value))
        const previousState = undoStack.value.pop()
        widgets.value = JSON.parse(previousState)
    }

    const redo = () => {
        if (redoStack.value.length === 0) return

        undoStack.value.push(JSON.stringify(widgets.value))
        const nextState = redoStack.value.pop()
        widgets.value = JSON.parse(nextState)
    }

    // Preview and panels

    const setPreviewMode = (mode) => {
        previewMode.value = mode
    }

    const toggleSeoPanel = () => {
        showSeoPanel.value = !showSeoPanel.value
    }

    const toggleVersionPanel = () => {
        showVersionPanel.value = !showVersionPanel.value
    }

    // Page settings

    const updatePageSettings = (settings) => {
        if (!page.value) return
        page.value.layout_settings = { ...page.value.layout_settings, ...settings }
        isDirty.value = true
    }

    const updatePageSeo = (seo) => {
        if (!page.value) return
        page.value.seo = { ...page.value.seo, ...seo }
        isDirty.value = true
    }

    const updatePageInfo = (info) => {
        if (!page.value) return
        page.value = { ...page.value, ...info }
        isDirty.value = true
    }

    // Reset

    const resetState = () => {
        page.value = null
        widgets.value = []
        selectedWidgetId.value = null
        isDirty.value = false
        isSaving.value = false
        isLoading.value = false
        undoStack.value = []
        redoStack.value = []
        versioning.reset()
        dataBinding.reset()
    }

    /**
     * Load block config (alias for loadWidgetRegistry for backward compatibility)
     */
    const loadBlockConfig = async (forceRefresh = false) => {
        return await loadWidgetRegistry(forceRefresh)
    }

    return {
        // State
        page,
        widgets,
        blocks: widgets, // Alias for backward compatibility
        selectedWidgetId,
        selectedBlockId: selectedWidgetId, // Alias for backward compatibility
        selectedWidget,
        selectedBlock: selectedWidget, // Alias for backward compatibility
        selectedWidgetIndex,
        isDirty,
        isSaving,
        isLoading,
        previewMode,
        showSeoPanel,
        showVersionPanel,
        widgetRegistry,
        blockConfig,
        canUndo,
        canRedo,

        // Version state
        currentVersion,
        publishedVersion,
        draftVersion,
        versionHistory,
        isPublishing: versioning.isPublishing,
        hasUnsavedChanges: versioning.hasUnsavedChanges,

        // Data binding
        dataProviders: dataBinding.providers,
        boundData: dataBinding.boundData,

        // Methods - Page
        loadPage,
        loadWidgetRegistry,
        loadBlockConfig,
        savePage,
        publishPage,
        revertToVersion,
        schedulePublish,

        // Methods - Widget
        addWidget,
        addBlock,
        updateWidget,
        updateBlock: updateWidget, // Alias for backward compatibility
        updateWidgetData,
        updateBlockData: updateWidgetData, // Alias for backward compatibility
        updateWidgetSettings,
        updateBlockSettings: updateWidgetSettings, // Alias for backward compatibility
        updateWidgetStyle,
        updateBlockStyle: updateWidgetStyle, // Alias for backward compatibility
        updateWidgetBinding,
        deleteWidget,
        deleteBlock: deleteWidget, // Alias for backward compatibility
        duplicateWidget,
        duplicateBlock: duplicateWidget, // Alias for backward compatibility
        moveWidget,
        moveBlock: moveWidget, // Alias for backward compatibility
        selectWidget,
        selectBlock: selectWidget, // Alias for backward compatibility
        deselectWidget,
        deselectBlock: deselectWidget, // Alias for backward compatibility

        // Methods - History
        undo,
        redo,

        // Methods - UI
        setPreviewMode,
        toggleSeoPanel,
        toggleVersionPanel,

        // Methods - Settings
        updatePageSettings,
        updatePageSeo,
        updatePageInfo,

        // Methods - Data Binding
        loadDataProviders: dataBinding.loadProviders,
        previewDataBinding: dataBinding.previewBinding,
        resolveDataBindings: dataBinding.resolveAllBindings,

        // Methods - Version
        loadVersionHistory: versioning.loadHistory,
        compareVersions: versioning.compare,
        scheduleVersion: versioning.schedule,

        // Reset
        resetState,

        // Sub-composables (for advanced usage)
        versioning,
        dataBinding
    }
}

/**
 * Default widget configuration (fallback)
 */
function getDefaultWidgetConfig() {
    return {
        categories: {
            layout: { label: 'Layout', icon: 'bi-grid', order: 1 },
            content: { label: 'Content', icon: 'bi-file-text', order: 2 },
            media: { label: 'Media', icon: 'bi-image', order: 3 },
            ecommerce: { label: 'E-commerce', icon: 'bi-cart', order: 4 },
            conversion: { label: 'Conversion', icon: 'bi-bullseye', order: 5 },
            social: { label: 'Social Proof', icon: 'bi-people', order: 6 },
            forms: { label: 'Forms', icon: 'bi-envelope', order: 7 },
            advanced: { label: 'Advanced', icon: 'bi-code-slash', order: 8 }
        },
        widgets: {
            hero: {
                name: 'Hero Banner',
                description: 'Full-width hero with image and CTA',
                icon: 'bi-aspect-ratio',
                category: 'layout',
                supports_data_binding: false,
                default_data: {
                    title: 'Welcome to Our Store',
                    subtitle: 'Discover amazing products',
                    background_image: '',
                    overlay_color: 'rgba(0,0,0,0.4)',
                    text_color: '#ffffff',
                    cta_text: 'Shop Now',
                    cta_link: '/products',
                    alignment: 'center',
                    height: '500px'
                },
                default_settings: {
                    full_width: true,
                    parallax: false
                }
            },
            rich_text: {
                name: 'Rich Text',
                description: 'WYSIWYG content block',
                icon: 'bi-text-paragraph',
                category: 'content',
                supports_data_binding: false,
                default_data: {
                    content: '<p>Enter your content here...</p>'
                },
                default_settings: {
                    max_width: '800px',
                    text_align: 'left'
                }
            },
            product_showcase: {
                name: 'Product Showcase',
                description: 'Display products from catalog',
                icon: 'bi-gem',
                category: 'ecommerce',
                supports_data_binding: true,
                data_provider_types: ['products'],
                default_data: {
                    title: 'Featured Products',
                    subtitle: '',
                    source: 'featured',
                    category_id: null,
                    product_ids: [],
                    limit: 8
                },
                default_settings: {
                    layout: 'grid',
                    columns: 4,
                    show_price: true,
                    show_rating: true,
                    show_add_to_cart: true
                }
            },
            category_grid: {
                name: 'Category Grid',
                description: 'Display product categories',
                icon: 'bi-grid-3x3',
                category: 'ecommerce',
                supports_data_binding: true,
                data_provider_types: ['categories'],
                default_data: {
                    title: 'Shop by Category',
                    subtitle: '',
                    source: 'all',
                    category_ids: [],
                    limit: 6
                },
                default_settings: {
                    layout: 'grid',
                    columns: 3,
                    show_product_count: true
                }
            },
            offer_banner: {
                name: 'Offer Banner',
                description: 'Promotional banner with countdown',
                icon: 'bi-tag',
                category: 'ecommerce',
                supports_data_binding: true,
                data_provider_types: ['offers'],
                default_data: {
                    title: '',
                    subtitle: '',
                    offer_id: null,
                    background_image: '',
                    background_color: '#f8f9fa',
                    cta_text: 'Shop Now',
                    cta_link: ''
                },
                default_settings: {
                    show_countdown: true,
                    show_discount: true
                }
            }
        }
    }
}
