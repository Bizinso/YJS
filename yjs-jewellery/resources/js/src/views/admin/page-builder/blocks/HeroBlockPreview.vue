<script setup>
const props = defineProps({
    block: { type: Object, required: true }
})

const data = props.block.data || {}
</script>

<template>
    <div
        class="relative min-h-[300px] flex items-center justify-center bg-cover bg-center"
        :style="{
            backgroundImage: data.background_image ? `url(${data.background_image})` : 'none',
            backgroundColor: data.background_image ? 'transparent' : '#1a1a2e',
            minHeight: data.height || '400px'
        }"
    >
        <!-- Overlay -->
        <div
            class="absolute inset-0"
            :style="{ backgroundColor: data.overlay_color || 'rgba(0,0,0,0.4)' }"
        ></div>

        <!-- Content -->
        <div
            class="relative z-10 text-center px-6 max-w-3xl"
            :class="{
                'text-left': data.alignment === 'left',
                'text-right': data.alignment === 'right'
            }"
            :style="{ color: data.text_color || '#ffffff' }"
        >
            <h1 class="text-4xl font-bold mb-4">
                {{ data.title || 'Hero Title' }}
            </h1>
            <p class="text-xl opacity-90 mb-6">
                {{ data.subtitle || 'Add a subtitle to describe your page' }}
            </p>
            <a
                v-if="data.cta_text"
                :href="data.cta_link || '#'"
                :class="[
                    'inline-block px-6 py-3 rounded-lg font-medium transition-colors',
                    data.cta_style === 'outline'
                        ? 'border-2 border-current hover:bg-white hover:bg-opacity-10'
                        : 'bg-amber-600 hover:bg-amber-700 text-white'
                ]"
            >
                {{ data.cta_text }}
            </a>
        </div>
    </div>
</template>
