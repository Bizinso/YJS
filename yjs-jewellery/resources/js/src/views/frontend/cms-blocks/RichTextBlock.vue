<script setup>
import { computed } from 'vue'
const props = defineProps({ block: { type: Object, required: true } })

const data = computed(() => props.block.data || {})
const settings = computed(() => props.block.settings || {})

const containerClass = computed(() => {
    const width = settings.value.max_width || 'medium'
    return {
        'small': 'max-w-2xl',
        'medium': 'max-w-4xl',
        'large': 'max-w-6xl',
        'full': 'w-full'
    }[width]
})
</script>

<template>
    <section class="rich-text-block py-12">
        <div class="container mx-auto px-4">
            <div :class="[containerClass, 'mx-auto']">
                <div class="prose prose-lg max-w-none" v-html="data.content"></div>
            </div>
        </div>
    </section>
</template>

<style scoped>
.prose :deep(h1) { @apply text-3xl font-bold mb-4; }
.prose :deep(h2) { @apply text-2xl font-bold mb-3; }
.prose :deep(h3) { @apply text-xl font-semibold mb-2; }
.prose :deep(p) { @apply mb-4 text-gray-600 leading-relaxed; }
.prose :deep(ul) { @apply list-disc pl-6 mb-4; }
.prose :deep(ol) { @apply list-decimal pl-6 mb-4; }
.prose :deep(li) { @apply mb-2; }
.prose :deep(a) { @apply text-amber-600 hover:underline; }
.prose :deep(blockquote) { @apply border-l-4 border-amber-500 pl-4 italic text-gray-600 my-4; }
.prose :deep(img) { @apply rounded-lg max-w-full h-auto my-4; }
</style>
