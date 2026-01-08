<script setup>
import { ref, computed } from 'vue'
const props = defineProps({ block: { type: Object, required: true } })

const data = computed(() => props.block.data || {})
const settings = computed(() => props.block.settings || {})

const lightboxOpen = ref(false)
const currentImage = ref(0)

const gridClass = computed(() => {
    const cols = settings.value.columns || 3
    return `grid-cols-2 md:grid-cols-${cols}`
})

const openLightbox = (index) => {
    if (settings.value.lightbox !== false) {
        currentImage.value = index
        lightboxOpen.value = true
    }
}

const nextImage = () => {
    currentImage.value = (currentImage.value + 1) % (data.value.images?.length || 1)
}

const prevImage = () => {
    currentImage.value = (currentImage.value - 1 + (data.value.images?.length || 1)) % (data.value.images?.length || 1)
}
</script>

<template>
    <section class="gallery-block py-12">
        <div class="container mx-auto px-4">
            <h2 v-if="data.title" class="text-3xl font-bold text-center mb-8">{{ data.title }}</h2>

            <div :class="['grid gap-4', gridClass]">
                <div
                    v-for="(img, i) in (data.images || [])"
                    :key="i"
                    class="relative group cursor-pointer overflow-hidden rounded-lg"
                    @click="openLightbox(i)"
                >
                    <img
                        :src="img.src"
                        :alt="img.alt || ''"
                        class="w-full h-64 object-cover transition-transform duration-300 group-hover:scale-110"
                    >
                    <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                        <i class="bi bi-zoom-in text-white text-2xl"></i>
                    </div>
                    <p v-if="settings.show_captions && img.caption" class="absolute bottom-0 left-0 right-0 bg-black/60 text-white text-sm p-2">
                        {{ img.caption }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Lightbox -->
        <div
            v-if="lightboxOpen"
            class="fixed inset-0 bg-black/90 z-50 flex items-center justify-center"
            @click.self="lightboxOpen = false"
        >
            <button @click="lightboxOpen = false" class="absolute top-4 right-4 text-white text-2xl hover:text-gray-300">
                <i class="bi bi-x-lg"></i>
            </button>
            <button @click="prevImage" class="absolute left-4 text-white text-3xl hover:text-gray-300">
                <i class="bi bi-chevron-left"></i>
            </button>
            <img
                :src="data.images[currentImage]?.src"
                :alt="data.images[currentImage]?.alt"
                class="max-w-[90vw] max-h-[90vh] object-contain"
            >
            <button @click="nextImage" class="absolute right-4 text-white text-3xl hover:text-gray-300">
                <i class="bi bi-chevron-right"></i>
            </button>
        </div>
    </section>
</template>
