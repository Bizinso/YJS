import { ref, computed, watch } from 'vue'
import axiosAdmin from '@/plugins/axiosAdmin'

// Shared state (singleton pattern)
const page = ref(null)
const blocks = ref([])
const selectedBlockId = ref(null)
const isDirty = ref(false)
const isSaving = ref(false)
const isLoading = ref(false)
const previewMode = ref('desktop') // desktop, tablet, mobile
const showSeoPanel = ref(false)
const undoStack = ref([])
const redoStack = ref([])

// Block configuration from backend
const blockConfig = ref(null)

export function usePageBuilder() {
    // Computed
    const selectedBlock = computed(() => {
        if (!selectedBlockId.value) return null
        return blocks.value.find(b => b.id === selectedBlockId.value)
    })

    const selectedBlockIndex = computed(() => {
        if (!selectedBlockId.value) return -1
        return blocks.value.findIndex(b => b.id === selectedBlockId.value)
    })

    const canUndo = computed(() => undoStack.value.length > 0)
    const canRedo = computed(() => redoStack.value.length > 0)

    // Watch for changes to mark as dirty
    watch(blocks, () => {
        isDirty.value = true
    }, { deep: true })

    // Methods
    const loadPage = async (pageId) => {
        isLoading.value = true
        try {
            const response = await axiosAdmin.get(`/cms/pages/${pageId}`)
            if (response.data.success) {
                page.value = response.data.data
                blocks.value = response.data.data.blocks || []
                isDirty.value = false
                selectedBlockId.value = null
                undoStack.value = []
                redoStack.value = []
            }
        } catch (error) {
            console.error('Error loading page:', error)
            throw error
        } finally {
            isLoading.value = false
        }
    }

    const loadBlockConfig = async () => {
        if (blockConfig.value) return blockConfig.value

        try {
            // This would typically come from an API endpoint
            // For now, we'll use a static config
            blockConfig.value = {
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
                blocks: {
                    hero: {
                        name: 'Hero Banner',
                        description: 'Full-width hero with image and CTA',
                        icon: 'bi-aspect-ratio',
                        category: 'layout',
                        defaultData: {
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
                        defaultSettings: {
                            full_width: true,
                            parallax: false
                        }
                    },
                    spacer: {
                        name: 'Spacer / Divider',
                        description: 'Add vertical space or divider',
                        icon: 'bi-distribute-vertical',
                        category: 'layout',
                        defaultData: {
                            height: 40,
                            show_divider: false,
                            divider_style: 'solid',
                            divider_color: '#e0e0e0'
                        },
                        defaultSettings: {}
                    },
                    rich_text: {
                        name: 'Rich Text',
                        description: 'WYSIWYG content block',
                        icon: 'bi-text-paragraph',
                        category: 'content',
                        defaultData: {
                            content: '<p>Enter your content here...</p>'
                        },
                        defaultSettings: {
                            max_width: '800px',
                            text_align: 'left'
                        }
                    },
                    faq: {
                        name: 'FAQ Accordion',
                        description: 'Expandable FAQ section',
                        icon: 'bi-question-circle',
                        category: 'content',
                        defaultData: {
                            title: 'Frequently Asked Questions',
                            items: [
                                { question: 'Sample question?', answer: 'Sample answer.' }
                            ]
                        },
                        defaultSettings: {
                            allow_multiple_open: false,
                            first_open: true
                        }
                    },
                    gallery: {
                        name: 'Image Gallery',
                        description: 'Grid or carousel of images',
                        icon: 'bi-images',
                        category: 'media',
                        defaultData: {
                            title: '',
                            images: []
                        },
                        defaultSettings: {
                            layout: 'grid',
                            columns: 3,
                            gap: 16,
                            lightbox: true
                        }
                    },
                    video: {
                        name: 'Video Embed',
                        description: 'YouTube, Vimeo, or self-hosted',
                        icon: 'bi-play-circle',
                        category: 'media',
                        defaultData: {
                            title: '',
                            video_url: '',
                            video_type: 'youtube',
                            thumbnail: ''
                        },
                        defaultSettings: {
                            autoplay: false,
                            loop: false,
                            controls: true,
                            aspect_ratio: '16:9'
                        }
                    },
                    product_showcase: {
                        name: 'Product Showcase',
                        description: 'Display products from catalog',
                        icon: 'bi-gem',
                        category: 'ecommerce',
                        defaultData: {
                            title: 'Featured Products',
                            subtitle: '',
                            source: 'featured',
                            category_id: null,
                            product_ids: [],
                            limit: 8
                        },
                        defaultSettings: {
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
                        defaultData: {
                            title: 'Shop by Category',
                            subtitle: '',
                            source: 'all',
                            category_ids: [],
                            limit: 6
                        },
                        defaultSettings: {
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
                        defaultData: {
                            title: '',
                            subtitle: '',
                            offer_id: null,
                            background_image: '',
                            background_color: '#f8f9fa',
                            cta_text: 'Shop Now',
                            cta_link: ''
                        },
                        defaultSettings: {
                            show_countdown: true,
                            show_discount: true
                        }
                    },
                    cta: {
                        name: 'Call to Action',
                        description: 'Prominent CTA section',
                        icon: 'bi-megaphone',
                        category: 'conversion',
                        defaultData: {
                            title: '',
                            description: '',
                            primary_button_text: '',
                            primary_button_link: '',
                            secondary_button_text: '',
                            secondary_button_link: '',
                            background_color: '#1a1a2e',
                            text_color: '#ffffff'
                        },
                        defaultSettings: {
                            layout: 'horizontal',
                            full_width: true
                        }
                    },
                    testimonials: {
                        name: 'Testimonials',
                        description: 'Customer testimonials',
                        icon: 'bi-chat-quote',
                        category: 'social',
                        defaultData: {
                            title: 'What Our Customers Say',
                            subtitle: '',
                            source: 'manual',
                            testimonials: [],
                            limit: 6
                        },
                        defaultSettings: {
                            layout: 'carousel',
                            show_rating: true,
                            show_avatar: true,
                            autoplay: true
                        }
                    },
                    contact_form: {
                        name: 'Contact Form',
                        description: 'Customizable contact form',
                        icon: 'bi-envelope',
                        category: 'forms',
                        defaultData: {
                            form_name: 'Contact Form',
                            title: 'Get in Touch',
                            subtitle: '',
                            fields: [
                                { type: 'text', name: 'name', label: 'Your Name', required: true },
                                { type: 'email', name: 'email', label: 'Email Address', required: true },
                                { type: 'textarea', name: 'message', label: 'Message', required: true }
                            ],
                            submit_text: 'Send Message'
                        },
                        defaultSettings: {
                            notify_email: '',
                            success_message: 'Thank you for your message!',
                            show_labels: true
                        }
                    },
                    custom_html: {
                        name: 'Custom HTML',
                        description: 'Raw HTML/CSS/JS code',
                        icon: 'bi-code-slash',
                        category: 'advanced',
                        defaultData: {
                            html: '',
                            css: '',
                            js: ''
                        },
                        defaultSettings: {
                            sandbox: true
                        }
                    }
                }
            }
            return blockConfig.value
        } catch (error) {
            console.error('Error loading block config:', error)
            throw error
        }
    }

    const savePage = async () => {
        if (!page.value || isSaving.value) return

        isSaving.value = true
        try {
            const response = await axiosAdmin.put(`/cms/pages/${page.value.id}`, {
                title: page.value.title,
                slug: page.value.slug,
                type: page.value.type,
                blocks: blocks.value,
                page_settings: page.value.page_settings || {},
                seo: page.value.seo || {},
                status: page.value.status
            })

            if (response.data.success) {
                isDirty.value = false
                page.value = response.data.data
            }
            return response.data
        } catch (error) {
            console.error('Error saving page:', error)
            throw error
        } finally {
            isSaving.value = false
        }
    }

    const generateBlockId = () => {
        return 'block_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9)
    }

    const addBlock = (blockType, index = null) => {
        const config = blockConfig.value?.blocks[blockType]
        if (!config) return null

        saveToUndoStack()

        const newBlock = {
            id: generateBlockId(),
            type: blockType,
            order: index !== null ? index : blocks.value.length,
            visible: true,
            data: JSON.parse(JSON.stringify(config.defaultData || {})),
            settings: JSON.parse(JSON.stringify(config.defaultSettings || {})),
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
            animation: { type: 'none', duration: 500 }
        }

        if (index !== null) {
            blocks.value.splice(index, 0, newBlock)
            // Update order for all blocks
            blocks.value.forEach((b, i) => b.order = i)
        } else {
            blocks.value.push(newBlock)
        }

        selectedBlockId.value = newBlock.id
        return newBlock
    }

    const updateBlock = (blockId, updates) => {
        const index = blocks.value.findIndex(b => b.id === blockId)
        if (index === -1) return

        saveToUndoStack()

        blocks.value[index] = {
            ...blocks.value[index],
            ...updates
        }
    }

    const updateBlockData = (blockId, data) => {
        const block = blocks.value.find(b => b.id === blockId)
        if (!block) return

        saveToUndoStack()

        block.data = { ...block.data, ...data }
    }

    const updateBlockSettings = (blockId, settings) => {
        const block = blocks.value.find(b => b.id === blockId)
        if (!block) return

        saveToUndoStack()

        block.settings = { ...block.settings, ...settings }
    }

    const updateBlockStyle = (blockId, style) => {
        const block = blocks.value.find(b => b.id === blockId)
        if (!block) return

        saveToUndoStack()

        block.style = { ...block.style, ...style }
    }

    const deleteBlock = (blockId) => {
        saveToUndoStack()

        const index = blocks.value.findIndex(b => b.id === blockId)
        if (index > -1) {
            blocks.value.splice(index, 1)
            blocks.value.forEach((b, i) => b.order = i)

            if (selectedBlockId.value === blockId) {
                selectedBlockId.value = null
            }
        }
    }

    const duplicateBlock = (blockId) => {
        const block = blocks.value.find(b => b.id === blockId)
        if (!block) return null

        saveToUndoStack()

        const newBlock = JSON.parse(JSON.stringify(block))
        newBlock.id = generateBlockId()

        const index = blocks.value.findIndex(b => b.id === blockId)
        blocks.value.splice(index + 1, 0, newBlock)
        blocks.value.forEach((b, i) => b.order = i)

        selectedBlockId.value = newBlock.id
        return newBlock
    }

    const moveBlock = (fromIndex, toIndex) => {
        if (fromIndex === toIndex) return

        saveToUndoStack()

        const [block] = blocks.value.splice(fromIndex, 1)
        blocks.value.splice(toIndex, 0, block)
        blocks.value.forEach((b, i) => b.order = i)
    }

    const selectBlock = (blockId) => {
        selectedBlockId.value = blockId
    }

    const deselectBlock = () => {
        selectedBlockId.value = null
    }

    // Undo/Redo
    const saveToUndoStack = () => {
        undoStack.value.push(JSON.stringify(blocks.value))
        redoStack.value = []

        // Limit stack size
        if (undoStack.value.length > 50) {
            undoStack.value.shift()
        }
    }

    const undo = () => {
        if (undoStack.value.length === 0) return

        redoStack.value.push(JSON.stringify(blocks.value))
        const previousState = undoStack.value.pop()
        blocks.value = JSON.parse(previousState)
    }

    const redo = () => {
        if (redoStack.value.length === 0) return

        undoStack.value.push(JSON.stringify(blocks.value))
        const nextState = redoStack.value.pop()
        blocks.value = JSON.parse(nextState)
    }

    // Preview mode
    const setPreviewMode = (mode) => {
        previewMode.value = mode
    }

    // Page settings
    const updatePageSettings = (settings) => {
        if (!page.value) return
        page.value.page_settings = { ...page.value.page_settings, ...settings }
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

    // Reset state (for cleanup)
    const resetState = () => {
        page.value = null
        blocks.value = []
        selectedBlockId.value = null
        isDirty.value = false
        isSaving.value = false
        isLoading.value = false
        undoStack.value = []
        redoStack.value = []
    }

    return {
        // State
        page,
        blocks,
        selectedBlockId,
        selectedBlock,
        selectedBlockIndex,
        isDirty,
        isSaving,
        isLoading,
        previewMode,
        showSeoPanel,
        blockConfig,
        canUndo,
        canRedo,

        // Methods
        loadPage,
        loadBlockConfig,
        savePage,
        addBlock,
        updateBlock,
        updateBlockData,
        updateBlockSettings,
        updateBlockStyle,
        deleteBlock,
        duplicateBlock,
        moveBlock,
        selectBlock,
        deselectBlock,
        undo,
        redo,
        setPreviewMode,
        updatePageSettings,
        updatePageSeo,
        updatePageInfo,
        resetState
    }
}
