<script setup>
import { ref, computed, watch } from 'vue'
import { usePageBuilder } from '@/composables/usePageBuilder'

// Block editor components
import HeroEditor from '../editors/HeroEditor.vue'
import RichTextEditor from '../editors/RichTextEditor.vue'
import SpacerEditor from '../editors/SpacerEditor.vue'
import GalleryEditor from '../editors/GalleryEditor.vue'
import VideoEditor from '../editors/VideoEditor.vue'
import ProductShowcaseEditor from '../editors/ProductShowcaseEditor.vue'
import CategoryGridEditor from '../editors/CategoryGridEditor.vue'
import OfferBannerEditor from '../editors/OfferBannerEditor.vue'
import CTAEditor from '../editors/CTAEditor.vue'
import TestimonialsEditor from '../editors/TestimonialsEditor.vue'
import ContactFormEditor from '../editors/ContactFormEditor.vue'
import FAQEditor from '../editors/FAQEditor.vue'
import CustomHtmlEditor from '../editors/CustomHtmlEditor.vue'
import GenericBlockEditor from '../editors/GenericBlockEditor.vue'

const {
    selectedBlock,
    blockConfig,
    updateBlockData,
    updateBlockSettings,
    updateBlockStyle,
    deselectBlock
} = usePageBuilder()

// Local state
const activeTab = ref('content')

// Editor component mapping
const editorComponents = {
    hero: HeroEditor,
    rich_text: RichTextEditor,
    spacer: SpacerEditor,
    gallery: GalleryEditor,
    video: VideoEditor,
    product_showcase: ProductShowcaseEditor,
    category_grid: CategoryGridEditor,
    offer_banner: OfferBannerEditor,
    cta: CTAEditor,
    testimonials: TestimonialsEditor,
    contact_form: ContactFormEditor,
    faq: FAQEditor,
    custom_html: CustomHtmlEditor
}

// Computed
const editorComponent = computed(() => {
    if (!selectedBlock.value) return null
    return editorComponents[selectedBlock.value.type] || GenericBlockEditor
})

const blockInfo = computed(() => {
    if (!selectedBlock.value || !blockConfig.value) return null
    return blockConfig.value.blocks[selectedBlock.value.type]
})

const blockStyle = computed(() => {
    return selectedBlock.value?.style || {
        backgroundColor: '#ffffff',
        padding: { top: 40, right: 20, bottom: 40, left: 20 },
        margin: { top: 0, right: 0, bottom: 0, left: 0 },
        customCSS: ''
    }
})

const blockAnimation = computed(() => {
    return selectedBlock.value?.animation || { type: 'none', duration: 500 }
})

const responsiveSettings = computed(() => {
    return selectedBlock.value?.responsive || {
        desktop: { visible: true },
        tablet: { visible: true },
        mobile: { visible: true }
    }
})

// Animation options
const animationOptions = [
    { value: 'none', label: 'No Animation' },
    { value: 'fadeIn', label: 'Fade In' },
    { value: 'fadeInUp', label: 'Fade In Up' },
    { value: 'fadeInDown', label: 'Fade In Down' },
    { value: 'fadeInLeft', label: 'Fade In Left' },
    { value: 'fadeInRight', label: 'Fade In Right' },
    { value: 'zoomIn', label: 'Zoom In' },
    { value: 'slideInUp', label: 'Slide In Up' },
    { value: 'bounce', label: 'Bounce' }
]

// Methods
const handleDataUpdate = (data) => {
    if (!selectedBlock.value) return
    updateBlockData(selectedBlock.value.id, data)
}

const handleSettingsUpdate = (settings) => {
    if (!selectedBlock.value) return
    updateBlockSettings(selectedBlock.value.id, settings)
}

const handleStyleUpdate = (style) => {
    if (!selectedBlock.value) return
    updateBlockStyle(selectedBlock.value.id, style)
}

const updatePadding = (side, value) => {
    const newPadding = { ...blockStyle.value.padding, [side]: parseInt(value) || 0 }
    handleStyleUpdate({ padding: newPadding })
}

const updateMargin = (side, value) => {
    const newMargin = { ...blockStyle.value.margin, [side]: parseInt(value) || 0 }
    handleStyleUpdate({ margin: newMargin })
}

const updateAnimation = (key, value) => {
    const newAnimation = { ...blockAnimation.value, [key]: value }
    if (!selectedBlock.value) return
    selectedBlock.value.animation = newAnimation
}

const updateResponsive = (device, key, value) => {
    if (!selectedBlock.value) return
    if (!selectedBlock.value.responsive) {
        selectedBlock.value.responsive = {
            desktop: { visible: true },
            tablet: { visible: true },
            mobile: { visible: true }
        }
    }
    selectedBlock.value.responsive[device][key] = value
}

// Reset tab when block changes
watch(() => selectedBlock.value?.id, () => {
    activeTab.value = 'content'
})
</script>

<template>
    <div class="h-full flex flex-col">
        <!-- Header -->
        <div class="p-4 border-b flex items-center justify-between">
            <div class="flex items-center space-x-2">
                <i v-if="blockInfo" :class="['bi', blockInfo.icon, 'text-gray-400']"></i>
                <span class="font-semibold text-gray-800">{{ blockInfo?.name || 'Block Settings' }}</span>
            </div>
            <button
                @click="deselectBlock"
                class="p-1 hover:bg-gray-100 rounded text-gray-400"
                title="Close"
            >
                <i class="bi bi-x-lg"></i>
            </button>
        </div>

        <!-- Tabs -->
        <div class="border-b">
            <div class="flex">
                <button
                    @click="activeTab = 'content'"
                    :class="[
                        'flex-1 px-4 py-2 text-sm font-medium border-b-2 transition-colors',
                        activeTab === 'content'
                            ? 'border-amber-500 text-amber-600'
                            : 'border-transparent text-gray-500 hover:text-gray-700'
                    ]"
                >
                    Content
                </button>
                <button
                    @click="activeTab = 'style'"
                    :class="[
                        'flex-1 px-4 py-2 text-sm font-medium border-b-2 transition-colors',
                        activeTab === 'style'
                            ? 'border-amber-500 text-amber-600'
                            : 'border-transparent text-gray-500 hover:text-gray-700'
                    ]"
                >
                    Style
                </button>
                <button
                    @click="activeTab = 'advanced'"
                    :class="[
                        'flex-1 px-4 py-2 text-sm font-medium border-b-2 transition-colors',
                        activeTab === 'advanced'
                            ? 'border-amber-500 text-amber-600'
                            : 'border-transparent text-gray-500 hover:text-gray-700'
                    ]"
                >
                    Advanced
                </button>
            </div>
        </div>

        <!-- Tab Content -->
        <div class="flex-1 overflow-y-auto p-4">
            <!-- Content Tab -->
            <div v-show="activeTab === 'content'">
                <component
                    :is="editorComponent"
                    :block="selectedBlock"
                    @update:data="handleDataUpdate"
                    @update:settings="handleSettingsUpdate"
                />
            </div>

            <!-- Style Tab -->
            <div v-show="activeTab === 'style'" class="space-y-6">
                <!-- Background Color -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Background Color</label>
                    <div class="flex items-center space-x-2">
                        <input
                            type="color"
                            :value="blockStyle.backgroundColor"
                            @input="handleStyleUpdate({ backgroundColor: $event.target.value })"
                            class="w-10 h-10 rounded border cursor-pointer"
                        >
                        <input
                            type="text"
                            :value="blockStyle.backgroundColor"
                            @input="handleStyleUpdate({ backgroundColor: $event.target.value })"
                            class="flex-1 px-3 py-2 border rounded-lg text-sm"
                        >
                    </div>
                </div>

                <!-- Padding -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Padding (px)</label>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">Top</label>
                            <input
                                type="number"
                                :value="blockStyle.padding?.top || 0"
                                @input="updatePadding('top', $event.target.value)"
                                class="w-full px-3 py-2 border rounded-lg text-sm"
                            >
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">Right</label>
                            <input
                                type="number"
                                :value="blockStyle.padding?.right || 0"
                                @input="updatePadding('right', $event.target.value)"
                                class="w-full px-3 py-2 border rounded-lg text-sm"
                            >
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">Bottom</label>
                            <input
                                type="number"
                                :value="blockStyle.padding?.bottom || 0"
                                @input="updatePadding('bottom', $event.target.value)"
                                class="w-full px-3 py-2 border rounded-lg text-sm"
                            >
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">Left</label>
                            <input
                                type="number"
                                :value="blockStyle.padding?.left || 0"
                                @input="updatePadding('left', $event.target.value)"
                                class="w-full px-3 py-2 border rounded-lg text-sm"
                            >
                        </div>
                    </div>
                </div>

                <!-- Margin -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Margin (px)</label>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">Top</label>
                            <input
                                type="number"
                                :value="blockStyle.margin?.top || 0"
                                @input="updateMargin('top', $event.target.value)"
                                class="w-full px-3 py-2 border rounded-lg text-sm"
                            >
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">Right</label>
                            <input
                                type="number"
                                :value="blockStyle.margin?.right || 0"
                                @input="updateMargin('right', $event.target.value)"
                                class="w-full px-3 py-2 border rounded-lg text-sm"
                            >
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">Bottom</label>
                            <input
                                type="number"
                                :value="blockStyle.margin?.bottom || 0"
                                @input="updateMargin('bottom', $event.target.value)"
                                class="w-full px-3 py-2 border rounded-lg text-sm"
                            >
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">Left</label>
                            <input
                                type="number"
                                :value="blockStyle.margin?.left || 0"
                                @input="updateMargin('left', $event.target.value)"
                                class="w-full px-3 py-2 border rounded-lg text-sm"
                            >
                        </div>
                    </div>
                </div>

                <!-- Custom CSS -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Custom CSS</label>
                    <textarea
                        :value="blockStyle.customCSS"
                        @input="handleStyleUpdate({ customCSS: $event.target.value })"
                        placeholder=".block { /* your CSS */ }"
                        rows="4"
                        class="w-full px-3 py-2 border rounded-lg text-sm font-mono"
                    ></textarea>
                </div>
            </div>

            <!-- Advanced Tab -->
            <div v-show="activeTab === 'advanced'" class="space-y-6">
                <!-- Animation -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Animation</label>
                    <select
                        :value="blockAnimation.type"
                        @change="updateAnimation('type', $event.target.value)"
                        class="w-full px-3 py-2 border rounded-lg text-sm"
                    >
                        <option v-for="anim in animationOptions" :key="anim.value" :value="anim.value">
                            {{ anim.label }}
                        </option>
                    </select>
                </div>

                <div v-if="blockAnimation.type !== 'none'">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Animation Duration (ms)</label>
                    <input
                        type="number"
                        :value="blockAnimation.duration"
                        @input="updateAnimation('duration', parseInt($event.target.value) || 500)"
                        min="100"
                        max="3000"
                        step="100"
                        class="w-full px-3 py-2 border rounded-lg text-sm"
                    >
                </div>

                <!-- Responsive Visibility -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Responsive Visibility</label>
                    <div class="space-y-2">
                        <label class="flex items-center">
                            <input
                                type="checkbox"
                                :checked="responsiveSettings.desktop?.visible !== false"
                                @change="updateResponsive('desktop', 'visible', $event.target.checked)"
                                class="rounded border-gray-300 text-amber-600 focus:ring-amber-500"
                            >
                            <span class="ml-2 text-sm text-gray-700">
                                <i class="bi bi-laptop me-1"></i>
                                Show on Desktop
                            </span>
                        </label>
                        <label class="flex items-center">
                            <input
                                type="checkbox"
                                :checked="responsiveSettings.tablet?.visible !== false"
                                @change="updateResponsive('tablet', 'visible', $event.target.checked)"
                                class="rounded border-gray-300 text-amber-600 focus:ring-amber-500"
                            >
                            <span class="ml-2 text-sm text-gray-700">
                                <i class="bi bi-tablet me-1"></i>
                                Show on Tablet
                            </span>
                        </label>
                        <label class="flex items-center">
                            <input
                                type="checkbox"
                                :checked="responsiveSettings.mobile?.visible !== false"
                                @change="updateResponsive('mobile', 'visible', $event.target.checked)"
                                class="rounded border-gray-300 text-amber-600 focus:ring-amber-500"
                            >
                            <span class="ml-2 text-sm text-gray-700">
                                <i class="bi bi-phone me-1"></i>
                                Show on Mobile
                            </span>
                        </label>
                    </div>
                </div>

                <!-- Block ID (read-only) -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Block ID</label>
                    <input
                        type="text"
                        :value="selectedBlock?.id"
                        readonly
                        class="w-full px-3 py-2 border rounded-lg text-sm bg-gray-50 text-gray-500"
                    >
                </div>
            </div>
        </div>
    </div>
</template>
