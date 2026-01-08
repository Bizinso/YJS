<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
const props = defineProps({ block: { type: Object, required: true } })

const data = computed(() => props.block.data || {})
const settings = computed(() => props.block.settings || {})

const currentIndex = ref(0)
let autoplayInterval = null

const testimonials = computed(() => data.value.testimonials || [])
const layout = computed(() => settings.value.layout || 'carousel')

const nextSlide = () => {
    currentIndex.value = (currentIndex.value + 1) % testimonials.value.length
}

const prevSlide = () => {
    currentIndex.value = (currentIndex.value - 1 + testimonials.value.length) % testimonials.value.length
}

onMounted(() => {
    if (settings.value.autoplay && layout.value === 'carousel' && testimonials.value.length > 1) {
        autoplayInterval = setInterval(nextSlide, settings.value.autoplay_interval || 5000)
    }
})

onUnmounted(() => {
    if (autoplayInterval) clearInterval(autoplayInterval)
})
</script>

<template>
    <section class="testimonials-block py-16 bg-gray-50">
        <div class="container mx-auto px-4">
            <h2 v-if="data.title" class="text-3xl font-bold text-center mb-2">{{ data.title }}</h2>
            <p v-if="data.subtitle" class="text-gray-600 text-center mb-12">{{ data.subtitle }}</p>

            <!-- Carousel Layout -->
            <div v-if="layout === 'carousel'" class="relative max-w-3xl mx-auto">
                <div class="overflow-hidden">
                    <div
                        class="transition-transform duration-500"
                        :style="{ transform: `translateX(-${currentIndex * 100}%)` }"
                    >
                        <div class="flex">
                            <div
                                v-for="(testimonial, i) in testimonials"
                                :key="i"
                                class="w-full flex-shrink-0 px-4"
                            >
                                <div class="bg-white rounded-xl p-8 shadow-sm text-center">
                                    <div class="text-amber-500 text-4xl mb-4">
                                        <i class="bi bi-quote"></i>
                                    </div>
                                    <p class="text-lg text-gray-600 italic mb-6">{{ testimonial.content }}</p>
                                    <div class="flex items-center justify-center gap-4">
                                        <img
                                            v-if="testimonial.avatar"
                                            :src="testimonial.avatar"
                                            :alt="testimonial.name"
                                            class="w-14 h-14 rounded-full object-cover"
                                        >
                                        <div v-else class="w-14 h-14 rounded-full bg-amber-100 flex items-center justify-center">
                                            <span class="text-amber-600 font-bold text-xl">{{ testimonial.name?.charAt(0) }}</span>
                                        </div>
                                        <div class="text-left">
                                            <p class="font-semibold text-gray-800">{{ testimonial.name }}</p>
                                            <p v-if="testimonial.title" class="text-sm text-gray-500">{{ testimonial.title }}</p>
                                        </div>
                                    </div>
                                    <div v-if="testimonial.rating" class="mt-4 text-amber-500">
                                        <i v-for="n in 5" :key="n" :class="['bi', n <= testimonial.rating ? 'bi-star-fill' : 'bi-star']"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Navigation -->
                <button
                    v-if="testimonials.length > 1"
                    @click="prevSlide"
                    class="absolute left-0 top-1/2 -translate-y-1/2 -translate-x-4 w-10 h-10 bg-white rounded-full shadow-lg flex items-center justify-center hover:bg-gray-50"
                >
                    <i class="bi bi-chevron-left"></i>
                </button>
                <button
                    v-if="testimonials.length > 1"
                    @click="nextSlide"
                    class="absolute right-0 top-1/2 -translate-y-1/2 translate-x-4 w-10 h-10 bg-white rounded-full shadow-lg flex items-center justify-center hover:bg-gray-50"
                >
                    <i class="bi bi-chevron-right"></i>
                </button>

                <!-- Dots -->
                <div v-if="testimonials.length > 1" class="flex justify-center gap-2 mt-6">
                    <button
                        v-for="(_, i) in testimonials"
                        :key="i"
                        @click="currentIndex = i"
                        :class="[
                            'w-2 h-2 rounded-full transition-colors',
                            i === currentIndex ? 'bg-amber-600' : 'bg-gray-300'
                        ]"
                    ></button>
                </div>
            </div>

            <!-- Grid Layout -->
            <div v-else class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div
                    v-for="(testimonial, i) in testimonials"
                    :key="i"
                    class="bg-white rounded-xl p-6 shadow-sm"
                >
                    <div class="flex items-start gap-4 mb-4">
                        <img
                            v-if="testimonial.avatar"
                            :src="testimonial.avatar"
                            :alt="testimonial.name"
                            class="w-12 h-12 rounded-full object-cover"
                        >
                        <div v-else class="w-12 h-12 rounded-full bg-amber-100 flex items-center justify-center flex-shrink-0">
                            <span class="text-amber-600 font-bold">{{ testimonial.name?.charAt(0) }}</span>
                        </div>
                        <div>
                            <p class="font-semibold text-gray-800">{{ testimonial.name }}</p>
                            <p v-if="testimonial.title" class="text-sm text-gray-500">{{ testimonial.title }}</p>
                        </div>
                    </div>
                    <p class="text-gray-600">{{ testimonial.content }}</p>
                    <div v-if="testimonial.rating" class="mt-4 text-amber-500 text-sm">
                        <i v-for="n in 5" :key="n" :class="['bi', n <= testimonial.rating ? 'bi-star-fill' : 'bi-star']"></i>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>
