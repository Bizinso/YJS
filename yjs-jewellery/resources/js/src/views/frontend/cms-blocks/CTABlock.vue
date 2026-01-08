<script setup>
import { computed } from 'vue'
const props = defineProps({ block: { type: Object, required: true } })

const data = computed(() => props.block.data || {})
const settings = computed(() => props.block.settings || {})

const bgStyle = computed(() => {
    const style = {}
    if (settings.value.background_color) {
        style.backgroundColor = settings.value.background_color
    }
    return style
})

const alignClass = computed(() => {
    const align = settings.value.alignment || 'center'
    return {
        'center': 'text-center',
        'left': 'text-left',
        'right': 'text-right'
    }[align]
})
</script>

<template>
    <section class="cta-block py-16" :style="bgStyle">
        <div class="container mx-auto px-4">
            <div :class="['max-w-3xl', alignClass === 'center' ? 'mx-auto' : '', alignClass]">
                <h2 v-if="data.title" class="text-3xl md:text-4xl font-bold mb-4" :style="{ color: settings.text_color || '#1f2937' }">
                    {{ data.title }}
                </h2>
                <p v-if="data.description" class="text-lg mb-8 opacity-80" :style="{ color: settings.text_color || '#4b5563' }">
                    {{ data.description }}
                </p>
                <div :class="['flex gap-4 flex-wrap', alignClass === 'center' ? 'justify-center' : '']">
                    <a
                        v-if="data.button_text"
                        :href="data.button_url || '#'"
                        class="inline-block px-8 py-3 bg-amber-600 text-white font-semibold rounded-lg hover:bg-amber-700 transition-colors"
                    >
                        {{ data.button_text }}
                    </a>
                    <a
                        v-if="data.secondary_button_text"
                        :href="data.secondary_button_url || '#'"
                        class="inline-block px-8 py-3 border-2 border-gray-800 text-gray-800 font-semibold rounded-lg hover:bg-gray-800 hover:text-white transition-colors"
                    >
                        {{ data.secondary_button_text }}
                    </a>
                </div>
            </div>
        </div>
    </section>
</template>
