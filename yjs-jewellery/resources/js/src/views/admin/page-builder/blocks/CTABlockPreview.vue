<script setup>
const props = defineProps({
    block: { type: Object, required: true }
})

const data = props.block.data || {}
const settings = props.block.settings || {}
</script>

<template>
    <div
        class="py-12 px-6"
        :style="{
            backgroundColor: data.background_color || '#1a1a2e',
            color: data.text_color || '#ffffff'
        }"
    >
        <div
            :class="[
                'max-w-4xl mx-auto',
                settings.layout === 'horizontal' ? 'flex items-center justify-between' : 'text-center'
            ]"
        >
            <div :class="settings.layout === 'horizontal' ? 'flex-1' : ''">
                <h2 class="text-2xl font-bold mb-2">{{ data.title || 'Ready to get started?' }}</h2>
                <p class="opacity-80">{{ data.description || 'Take your next step with us today.' }}</p>
            </div>

            <div :class="['flex gap-3', settings.layout === 'horizontal' ? '' : 'justify-center mt-6']">
                <a
                    v-if="data.primary_button_text"
                    :href="data.primary_button_link || '#'"
                    class="px-6 py-3 bg-amber-600 text-white rounded-lg font-medium hover:bg-amber-700"
                >
                    {{ data.primary_button_text }}
                </a>
                <a
                    v-if="data.secondary_button_text"
                    :href="data.secondary_button_link || '#'"
                    class="px-6 py-3 border border-current rounded-lg font-medium hover:bg-white hover:bg-opacity-10"
                >
                    {{ data.secondary_button_text }}
                </a>
            </div>
        </div>
    </div>
</template>
