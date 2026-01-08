<script setup>
import { computed } from 'vue'
import { usePageBuilder } from '@/composables/usePageBuilder'

// Block preview components
import HeroBlockPreview from '../blocks/HeroBlockPreview.vue'
import RichTextBlockPreview from '../blocks/RichTextBlockPreview.vue'
import SpacerBlockPreview from '../blocks/SpacerBlockPreview.vue'
import GalleryBlockPreview from '../blocks/GalleryBlockPreview.vue'
import VideoBlockPreview from '../blocks/VideoBlockPreview.vue'
import ProductShowcaseBlockPreview from '../blocks/ProductShowcaseBlockPreview.vue'
import CategoryGridBlockPreview from '../blocks/CategoryGridBlockPreview.vue'
import OfferBannerBlockPreview from '../blocks/OfferBannerBlockPreview.vue'
import CTABlockPreview from '../blocks/CTABlockPreview.vue'
import TestimonialsBlockPreview from '../blocks/TestimonialsBlockPreview.vue'
import ContactFormBlockPreview from '../blocks/ContactFormBlockPreview.vue'
import FAQBlockPreview from '../blocks/FAQBlockPreview.vue'
import CustomHtmlBlockPreview from '../blocks/CustomHtmlBlockPreview.vue'

const props = defineProps({
    block: {
        type: Object,
        required: true
    },
    index: {
        type: Number,
        required: true
    },
    isSelected: {
        type: Boolean,
        default: false
    }
})

const emit = defineEmits(['select'])

const { blockConfig, deleteBlock, duplicateBlock, updateBlock } = usePageBuilder()

// Block component mapping
const blockComponents = {
    hero: HeroBlockPreview,
    rich_text: RichTextBlockPreview,
    spacer: SpacerBlockPreview,
    gallery: GalleryBlockPreview,
    video: VideoBlockPreview,
    product_showcase: ProductShowcaseBlockPreview,
    category_grid: CategoryGridBlockPreview,
    offer_banner: OfferBannerBlockPreview,
    cta: CTABlockPreview,
    testimonials: TestimonialsBlockPreview,
    contact_form: ContactFormBlockPreview,
    faq: FAQBlockPreview,
    custom_html: CustomHtmlBlockPreview
}

// Computed
const blockComponent = computed(() => {
    return blockComponents[props.block.type] || null
})

const blockInfo = computed(() => {
    return blockConfig.value?.blocks[props.block.type] || {
        name: props.block.type,
        icon: 'bi-square'
    }
})

const blockStyle = computed(() => {
    const style = props.block.style || {}
    const padding = style.padding || {}
    const margin = style.margin || {}

    return {
        backgroundColor: style.backgroundColor || '#ffffff',
        paddingTop: `${padding.top || 0}px`,
        paddingRight: `${padding.right || 0}px`,
        paddingBottom: `${padding.bottom || 0}px`,
        paddingLeft: `${padding.left || 0}px`,
        marginTop: `${margin.top || 0}px`,
        marginRight: `${margin.right || 0}px`,
        marginBottom: `${margin.bottom || 0}px`,
        marginLeft: `${margin.left || 0}px`
    }
})

const isHidden = computed(() => {
    return !props.block.visible
})

// Methods
const handleSelect = (event) => {
    event.stopPropagation()
    emit('select')
}

const handleDelete = (event) => {
    event.stopPropagation()
    if (confirm('Delete this block?')) {
        deleteBlock(props.block.id)
    }
}

const handleDuplicate = (event) => {
    event.stopPropagation()
    duplicateBlock(props.block.id)
}

const handleToggleVisibility = (event) => {
    event.stopPropagation()
    updateBlock(props.block.id, { visible: !props.block.visible })
}
</script>

<template>
    <div
        :class="[
            'relative group transition-all',
            isSelected ? 'ring-2 ring-amber-500 ring-offset-2' : 'hover:ring-2 hover:ring-gray-300 hover:ring-offset-2',
            isHidden ? 'opacity-50' : ''
        ]"
        @click="handleSelect"
    >
        <!-- Block Toolbar -->
        <div
            :class="[
                'absolute -top-10 left-1/2 -translate-x-1/2 flex items-center space-x-1 bg-white rounded-lg shadow-lg border px-2 py-1 z-10 transition-opacity',
                isSelected ? 'opacity-100' : 'opacity-0 group-hover:opacity-100'
            ]"
        >
            <!-- Drag Handle -->
            <button
                class="drag-handle p-1.5 hover:bg-gray-100 rounded cursor-move text-gray-500"
                title="Drag to reorder"
            >
                <i class="bi bi-grip-vertical"></i>
            </button>

            <div class="w-px h-4 bg-gray-200"></div>

            <!-- Block Type Label -->
            <span class="px-2 text-xs font-medium text-gray-600 flex items-center">
                <i :class="['bi', blockInfo.icon, 'me-1']"></i>
                {{ blockInfo.name }}
            </span>

            <div class="w-px h-4 bg-gray-200"></div>

            <!-- Toggle Visibility -->
            <button
                @click="handleToggleVisibility"
                class="p-1.5 hover:bg-gray-100 rounded text-gray-500"
                :title="isHidden ? 'Show block' : 'Hide block'"
            >
                <i :class="['bi', isHidden ? 'bi-eye-slash' : 'bi-eye']"></i>
            </button>

            <!-- Duplicate -->
            <button
                @click="handleDuplicate"
                class="p-1.5 hover:bg-gray-100 rounded text-gray-500"
                title="Duplicate block"
            >
                <i class="bi bi-copy"></i>
            </button>

            <!-- Delete -->
            <button
                @click="handleDelete"
                class="p-1.5 hover:bg-red-100 rounded text-red-500"
                title="Delete block"
            >
                <i class="bi bi-trash"></i>
            </button>
        </div>

        <!-- Block Content -->
        <div
            :style="blockStyle"
            class="relative"
        >
            <!-- Hidden Overlay -->
            <div
                v-if="isHidden"
                class="absolute inset-0 bg-gray-100 bg-opacity-50 flex items-center justify-center z-5"
            >
                <span class="text-gray-500 text-sm flex items-center">
                    <i class="bi bi-eye-slash me-1"></i>
                    Hidden
                </span>
            </div>

            <!-- Block Preview Component -->
            <component
                v-if="blockComponent"
                :is="blockComponent"
                :block="block"
            />

            <!-- Fallback for unknown blocks -->
            <div v-else class="p-6 bg-gray-50 border border-dashed border-gray-300 rounded text-center">
                <i :class="['bi', blockInfo.icon, 'text-2xl text-gray-400']"></i>
                <p class="mt-2 text-gray-500">{{ blockInfo.name }}</p>
                <p class="text-xs text-gray-400">Block preview not available</p>
            </div>
        </div>
    </div>
</template>
