<script setup>
import { computed } from 'vue'
import { usePageBuilder } from '@/composables/usePageBuilder'

const props = defineProps({
    block: {
        type: Object,
        required: true
    }
})

const { blockConfig } = usePageBuilder()

const blockInfo = computed(() => {
    return blockConfig.value?.blocks[props.block.type] || {
        name: props.block.type,
        icon: 'bi-square',
        description: ''
    }
})

// Extract display content from block data
const displayTitle = computed(() => {
    const data = props.block.data || {}
    return data.title || data.heading || data.name || ''
})

const displaySubtitle = computed(() => {
    const data = props.block.data || {}
    return data.subtitle || data.description || data.text || ''
})

const displayImage = computed(() => {
    const data = props.block.data || {}
    return data.image || data.background_image || data.src || data.thumbnail || ''
})

const displayContent = computed(() => {
    const data = props.block.data || {}
    return data.content || data.html || data.body || ''
})

const hasContent = computed(() => {
    return displayTitle.value || displaySubtitle.value || displayImage.value || displayContent.value
})

// Determine block category for styling
const categoryClass = computed(() => {
    const category = blockInfo.value.category || 'content'
    const classes = {
        layout: 'bg-blue-50 border-blue-200',
        content: 'bg-gray-50 border-gray-200',
        media: 'bg-purple-50 border-purple-200',
        ecommerce: 'bg-amber-50 border-amber-200',
        conversion: 'bg-green-50 border-green-200',
        social: 'bg-pink-50 border-pink-200',
        forms: 'bg-cyan-50 border-cyan-200',
        advanced: 'bg-slate-50 border-slate-200'
    }
    return classes[category] || classes.content
})

const iconClass = computed(() => {
    const category = blockInfo.value.category || 'content'
    const classes = {
        layout: 'text-blue-500',
        content: 'text-gray-500',
        media: 'text-purple-500',
        ecommerce: 'text-amber-500',
        conversion: 'text-green-500',
        social: 'text-pink-500',
        forms: 'text-cyan-500',
        advanced: 'text-slate-500'
    }
    return classes[category] || classes.content
})
</script>

<template>
    <div :class="['p-6 border-2 border-dashed rounded-lg min-h-[120px]', categoryClass]">
        <!-- Block with content -->
        <div v-if="hasContent" class="space-y-3">
            <!-- Image preview -->
            <div v-if="displayImage" class="relative">
                <img
                    :src="displayImage"
                    :alt="displayTitle || blockInfo.name"
                    class="w-full h-48 object-cover rounded-lg"
                    @error="$event.target.style.display='none'"
                >
            </div>

            <!-- Title -->
            <h3 v-if="displayTitle" class="text-xl font-semibold text-gray-800">
                {{ displayTitle }}
            </h3>

            <!-- Subtitle -->
            <p v-if="displaySubtitle" class="text-gray-600">
                {{ displaySubtitle }}
            </p>

            <!-- HTML Content -->
            <div
                v-if="displayContent"
                class="prose prose-sm max-w-none"
                v-html="displayContent"
            ></div>

            <!-- Block type badge -->
            <div class="flex items-center text-xs text-gray-400 mt-4 pt-3 border-t border-gray-200">
                <i :class="['bi', blockInfo.icon, 'me-1', iconClass]"></i>
                <span>{{ blockInfo.name }}</span>
            </div>
        </div>

        <!-- Empty block placeholder -->
        <div v-else class="flex flex-col items-center justify-center py-8 text-center">
            <div :class="['w-16 h-16 rounded-full flex items-center justify-center mb-4', categoryClass]">
                <i :class="['bi', blockInfo.icon, 'text-3xl', iconClass]"></i>
            </div>
            <h4 class="font-medium text-gray-700 mb-1">{{ blockInfo.name }}</h4>
            <p class="text-sm text-gray-500 max-w-xs">
                {{ blockInfo.description || 'Click to configure this block' }}
            </p>
            <p class="text-xs text-gray-400 mt-2">
                Select this block to edit its content
            </p>
        </div>
    </div>
</template>
