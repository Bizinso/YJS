<script setup>
import { computed } from 'vue'

const props = defineProps({
    block: {
        type: Object,
        required: true
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

const displayButtonText = computed(() => {
    const data = props.block.data || {}
    return data.button_text || data.cta_text || data.btn_text || ''
})

const displayButtonLink = computed(() => {
    const data = props.block.data || {}
    return data.button_link || data.cta_link || data.link || data.url || '#'
})

const hasContent = computed(() => {
    return displayTitle.value || displaySubtitle.value || displayImage.value || displayContent.value
})

// Block type to determine styling
const blockCategory = computed(() => {
    const type = props.block.type || ''
    if (type.includes('hero') || type.includes('banner')) return 'hero'
    if (type.includes('product') || type.includes('category') || type.includes('offer')) return 'ecommerce'
    if (type.includes('cta') || type.includes('newsletter') || type.includes('button')) return 'conversion'
    if (type.includes('testimonial') || type.includes('review') || type.includes('team')) return 'social'
    if (type.includes('form') || type.includes('contact') || type.includes('inquiry')) return 'form'
    if (type.includes('faq') || type.includes('accordion') || type.includes('text')) return 'content'
    if (type.includes('gallery') || type.includes('video') || type.includes('image') || type.includes('slider')) return 'media'
    return 'default'
})
</script>

<template>
    <section class="generic-block py-12 px-4">
        <div class="max-w-6xl mx-auto">
            <!-- Hero-style blocks -->
            <div v-if="blockCategory === 'hero' && hasContent" class="text-center">
                <div v-if="displayImage" class="relative mb-8">
                    <img :src="displayImage" :alt="displayTitle" class="w-full h-64 md:h-96 object-cover rounded-lg">
                    <div v-if="displayTitle || displaySubtitle" class="absolute inset-0 bg-black bg-opacity-40 flex items-center justify-center rounded-lg">
                        <div class="text-white text-center px-4">
                            <h1 v-if="displayTitle" class="text-3xl md:text-5xl font-bold mb-4">{{ displayTitle }}</h1>
                            <p v-if="displaySubtitle" class="text-lg md:text-xl mb-6">{{ displaySubtitle }}</p>
                            <a v-if="displayButtonText" :href="displayButtonLink" class="inline-block bg-amber-600 hover:bg-amber-700 text-white px-8 py-3 rounded-lg font-semibold transition-colors">
                                {{ displayButtonText }}
                            </a>
                        </div>
                    </div>
                </div>
                <div v-else>
                    <h1 v-if="displayTitle" class="text-3xl md:text-5xl font-bold text-gray-800 mb-4">{{ displayTitle }}</h1>
                    <p v-if="displaySubtitle" class="text-lg md:text-xl text-gray-600 mb-6">{{ displaySubtitle }}</p>
                    <a v-if="displayButtonText" :href="displayButtonLink" class="inline-block bg-amber-600 hover:bg-amber-700 text-white px-8 py-3 rounded-lg font-semibold transition-colors">
                        {{ displayButtonText }}
                    </a>
                </div>
            </div>

            <!-- Content blocks -->
            <div v-else-if="hasContent">
                <h2 v-if="displayTitle" class="text-2xl md:text-3xl font-bold text-gray-800 mb-4 text-center">{{ displayTitle }}</h2>
                <p v-if="displaySubtitle" class="text-lg text-gray-600 mb-6 text-center max-w-3xl mx-auto">{{ displaySubtitle }}</p>

                <div v-if="displayImage" class="mb-6">
                    <img :src="displayImage" :alt="displayTitle" class="w-full max-w-4xl mx-auto rounded-lg shadow-lg">
                </div>

                <div v-if="displayContent" class="prose prose-lg max-w-3xl mx-auto" v-html="displayContent"></div>

                <div v-if="displayButtonText" class="text-center mt-8">
                    <a :href="displayButtonLink" class="inline-block bg-amber-600 hover:bg-amber-700 text-white px-6 py-3 rounded-lg font-semibold transition-colors">
                        {{ displayButtonText }}
                    </a>
                </div>
            </div>

            <!-- Empty placeholder -->
            <div v-else class="text-center py-8 text-gray-400">
                <p class="text-sm">{{ block.type }} block</p>
            </div>
        </div>
    </section>
</template>

<style scoped>
.generic-block {
    min-height: 100px;
}
</style>
