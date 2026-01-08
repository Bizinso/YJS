<script setup>
const props = defineProps({
    block: { type: Object, required: true }
})

const data = props.block.data || {}
const settings = props.block.settings || {}
</script>

<template>
    <div
        class="prose max-w-none"
        :style="{
            maxWidth: settings.max_width || '800px',
            margin: '0 auto',
            textAlign: settings.text_align || 'left'
        }"
    >
        <div v-if="data.content" v-html="data.content"></div>
        <p v-else class="text-gray-400 italic">Click to add content...</p>
    </div>
</template>

<style scoped>
.prose {
    font-size: 1rem;
    line-height: 1.75;
}
.prose :deep(h1) { font-size: 2.25rem; font-weight: 700; margin-bottom: 1rem; }
.prose :deep(h2) { font-size: 1.875rem; font-weight: 600; margin-bottom: 0.75rem; }
.prose :deep(h3) { font-size: 1.5rem; font-weight: 600; margin-bottom: 0.5rem; }
.prose :deep(p) { margin-bottom: 1rem; }
.prose :deep(ul), .prose :deep(ol) { margin-left: 1.5rem; margin-bottom: 1rem; }
.prose :deep(li) { margin-bottom: 0.25rem; }
.prose :deep(a) { color: #d97706; text-decoration: underline; }
.prose :deep(blockquote) { border-left: 4px solid #d97706; padding-left: 1rem; font-style: italic; }
</style>
