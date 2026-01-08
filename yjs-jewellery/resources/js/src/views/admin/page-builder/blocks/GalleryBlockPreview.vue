<script setup>
const props = defineProps({
    block: { type: Object, required: true }
})

const data = props.block.data || {}
const settings = props.block.settings || {}

const mockImages = [
    { src: '', alt: 'Image 1', caption: 'Caption 1' },
    { src: '', alt: 'Image 2', caption: 'Caption 2' },
    { src: '', alt: 'Image 3', caption: 'Caption 3' },
    { src: '', alt: 'Image 4', caption: 'Caption 4' },
    { src: '', alt: 'Image 5', caption: 'Caption 5' },
    { src: '', alt: 'Image 6', caption: 'Caption 6' }
]

const images = data.images?.length ? data.images : mockImages
</script>

<template>
    <div>
        <!-- Header -->
        <div v-if="data.title" class="text-center mb-6">
            <h2 class="text-2xl font-bold text-gray-800">{{ data.title }}</h2>
        </div>

        <!-- Gallery Grid -->
        <div
            class="grid"
            :style="{
                gridTemplateColumns: `repeat(${settings.columns || 3}, 1fr)`,
                gap: `${settings.gap || 16}px`
            }"
        >
            <div
                v-for="(image, index) in images"
                :key="index"
                class="aspect-square bg-gray-100 rounded-lg overflow-hidden cursor-pointer hover:opacity-90 transition-opacity"
            >
                <img
                    v-if="image.src"
                    :src="image.src"
                    :alt="image.alt || `Image ${index + 1}`"
                    class="w-full h-full object-cover"
                >
                <div v-else class="w-full h-full flex items-center justify-center">
                    <i class="bi bi-image text-3xl text-gray-300"></i>
                </div>

                <div
                    v-if="settings.show_captions && image.caption"
                    class="absolute bottom-0 left-0 right-0 bg-black bg-opacity-50 text-white text-sm p-2"
                >
                    {{ image.caption }}
                </div>
            </div>
        </div>

        <p v-if="!data.images?.length" class="text-center text-gray-400 text-sm mt-4">
            Click to add images to the gallery
        </p>
    </div>
</template>
