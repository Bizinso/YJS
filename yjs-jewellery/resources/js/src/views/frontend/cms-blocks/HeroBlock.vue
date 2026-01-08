<script setup>
import { computed } from 'vue'
const props = defineProps({ block: { type: Object, required: true } })

const data = computed(() => props.block.data || {})
const settings = computed(() => props.block.settings || {})

const heroStyle = computed(() => {
    const style = { minHeight: (settings.value.min_height || 500) + 'px' }
    if (data.value.background_image) {
        style.backgroundImage = `url(${data.value.background_image})`
        style.backgroundSize = 'cover'
        style.backgroundPosition = 'center'
    }
    if (data.value.background_color) {
        style.backgroundColor = data.value.background_color
    }
    return style
})

const overlayStyle = computed(() => {
    if (!settings.value.overlay_opacity) return {}
    return {
        backgroundColor: `rgba(0,0,0,${settings.value.overlay_opacity / 100})`
    }
})

const alignmentClass = computed(() => {
    const align = settings.value.text_alignment || 'center'
    return {
        'center': 'text-center items-center',
        'left': 'text-left items-start',
        'right': 'text-right items-end'
    }[align]
})
</script>

<template>
    <section class="hero-block relative flex items-center" :style="heroStyle">
        <!-- Overlay -->
        <div v-if="settings.overlay_opacity" class="absolute inset-0" :style="overlayStyle"></div>

        <!-- Content -->
        <div class="container mx-auto px-4 relative z-10">
            <div :class="['flex flex-col', alignmentClass, 'max-w-4xl mx-auto']">
                <h1
                    v-if="data.title"
                    class="text-4xl md:text-5xl lg:text-6xl font-bold mb-4"
                    :style="{ color: settings.text_color || '#ffffff' }"
                >
                    {{ data.title }}
                </h1>
                <p
                    v-if="data.subtitle"
                    class="text-lg md:text-xl mb-8 opacity-90"
                    :style="{ color: settings.text_color || '#ffffff' }"
                >
                    {{ data.subtitle }}
                </p>
                <div v-if="data.button_text" class="flex gap-4" :class="alignmentClass">
                    <a
                        :href="data.button_url || '#'"
                        class="inline-block px-8 py-3 bg-amber-600 text-white font-semibold rounded-lg hover:bg-amber-700 transition-colors"
                    >
                        {{ data.button_text }}
                    </a>
                    <a
                        v-if="data.secondary_button_text"
                        :href="data.secondary_button_url || '#'"
                        class="inline-block px-8 py-3 border-2 border-white text-white font-semibold rounded-lg hover:bg-white hover:text-gray-800 transition-colors"
                    >
                        {{ data.secondary_button_text }}
                    </a>
                </div>
            </div>
        </div>
    </section>
</template>
