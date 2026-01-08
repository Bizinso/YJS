<script setup>
const props = defineProps({
    block: { type: Object, required: true }
})

const data = props.block.data || {}
const settings = props.block.settings || {}

// Mock categories for preview
const mockCategories = [
    { id: 1, name: 'Necklaces', product_count: 45 },
    { id: 2, name: 'Earrings', product_count: 32 },
    { id: 3, name: 'Rings', product_count: 28 },
    { id: 4, name: 'Bracelets', product_count: 18 },
    { id: 5, name: 'Pendants', product_count: 22 },
    { id: 6, name: 'Bangles', product_count: 15 }
]

const displayCategories = data._categories || mockCategories.slice(0, data.limit || 6)
</script>

<template>
    <div>
        <!-- Header -->
        <div v-if="data.title" class="text-center mb-6">
            <h2 class="text-2xl font-bold text-gray-800">{{ data.title }}</h2>
            <p v-if="data.subtitle" class="text-gray-600 mt-2">{{ data.subtitle }}</p>
        </div>

        <!-- Categories Grid -->
        <div
            class="grid gap-4"
            :style="{ gridTemplateColumns: `repeat(${settings.columns || 3}, 1fr)` }"
        >
            <div
                v-for="(category, index) in displayCategories"
                :key="category.id || index"
                class="group relative overflow-hidden rounded-lg cursor-pointer"
            >
                <!-- Category Image -->
                <div class="aspect-[4/3] bg-gradient-to-br from-amber-100 to-amber-200 flex items-center justify-center">
                    <img
                        v-if="category.image"
                        :src="category.image"
                        :alt="category.name"
                        class="w-full h-full object-cover"
                    >
                    <i v-else class="bi bi-grid-3x3 text-5xl text-amber-300"></i>
                </div>

                <!-- Overlay -->
                <div class="absolute inset-0 bg-black bg-opacity-40 group-hover:bg-opacity-50 transition-colors flex items-center justify-center">
                    <div class="text-center text-white">
                        <h3 class="font-bold text-lg">{{ category.name }}</h3>
                        <p v-if="settings.show_product_count" class="text-sm opacity-80">
                            {{ category.product_count || 0 }} Products
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
