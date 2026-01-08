<script setup>
const props = defineProps({
    block: { type: Object, required: true }
})

const data = props.block.data || {}
const settings = props.block.settings || {}
</script>

<template>
    <div
        class="relative overflow-hidden rounded-lg min-h-[200px] flex items-center"
        :style="{
            backgroundImage: data.background_image ? `url(${data.background_image})` : 'none',
            backgroundColor: data.background_color || '#f8f9fa'
        }"
    >
        <div class="relative z-10 p-8 w-full" :style="{ color: settings.text_color || '#000000' }">
            <div class="max-w-2xl">
                <h2 class="text-3xl font-bold mb-2">{{ data.title || 'Special Offer' }}</h2>
                <p class="text-lg opacity-80 mb-4">{{ data.subtitle || 'Limited time offer on selected items' }}</p>

                <!-- Countdown (placeholder) -->
                <div v-if="settings.show_countdown" class="flex items-center space-x-4 mb-4">
                    <div class="text-center">
                        <div class="text-2xl font-bold">02</div>
                        <div class="text-xs uppercase opacity-70">Days</div>
                    </div>
                    <div class="text-2xl">:</div>
                    <div class="text-center">
                        <div class="text-2xl font-bold">14</div>
                        <div class="text-xs uppercase opacity-70">Hours</div>
                    </div>
                    <div class="text-2xl">:</div>
                    <div class="text-center">
                        <div class="text-2xl font-bold">32</div>
                        <div class="text-xs uppercase opacity-70">Mins</div>
                    </div>
                </div>

                <a
                    v-if="data.cta_text"
                    :href="data.cta_link || '#'"
                    class="inline-block px-6 py-3 bg-amber-600 text-white rounded-lg font-medium hover:bg-amber-700"
                >
                    {{ data.cta_text }}
                </a>
            </div>
        </div>

        <!-- Discount Badge -->
        <div
            v-if="settings.show_discount"
            class="absolute top-4 right-4 bg-red-500 text-white px-4 py-2 rounded-full font-bold"
        >
            Up to 50% OFF
        </div>
    </div>
</template>
