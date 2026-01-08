<script setup>
const props = defineProps({
    block: { type: Object, required: true }
})

const data = props.block.data || {}
const settings = props.block.settings || {}

const mockTestimonials = [
    { name: 'Sarah M.', title: 'Verified Buyer', content: 'Beautiful craftsmanship! The necklace exceeded my expectations.', rating: 5 },
    { name: 'Priya K.', title: 'Loyal Customer', content: 'Excellent quality and fast delivery. Highly recommend!', rating: 5 },
    { name: 'Anita R.', title: 'First-time Buyer', content: 'The earrings are stunning. Perfect for my wedding day.', rating: 4 }
]

const testimonials = data.testimonials?.length ? data.testimonials : mockTestimonials
</script>

<template>
    <div>
        <!-- Header -->
        <div v-if="data.title" class="text-center mb-8">
            <h2 class="text-2xl font-bold text-gray-800">{{ data.title }}</h2>
            <p v-if="data.subtitle" class="text-gray-600 mt-2">{{ data.subtitle }}</p>
        </div>

        <!-- Testimonials -->
        <div class="grid md:grid-cols-3 gap-6">
            <div
                v-for="(testimonial, index) in testimonials.slice(0, 3)"
                :key="index"
                class="bg-white p-6 rounded-lg shadow-sm border"
            >
                <!-- Rating -->
                <div v-if="settings.show_rating !== false" class="flex items-center mb-3">
                    <i
                        v-for="star in 5"
                        :key="star"
                        :class="[
                            'bi text-sm',
                            star <= testimonial.rating ? 'bi-star-fill text-amber-400' : 'bi-star text-gray-300'
                        ]"
                    ></i>
                </div>

                <!-- Content -->
                <p class="text-gray-600 italic mb-4">"{{ testimonial.content }}"</p>

                <!-- Author -->
                <div class="flex items-center">
                    <div
                        v-if="settings.show_avatar !== false"
                        class="w-10 h-10 rounded-full bg-amber-100 flex items-center justify-center mr-3"
                    >
                        <span class="text-amber-600 font-medium">{{ testimonial.name?.charAt(0) }}</span>
                    </div>
                    <div>
                        <p class="font-medium text-gray-800">{{ testimonial.name }}</p>
                        <p class="text-sm text-gray-500">{{ testimonial.title }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
