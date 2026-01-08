<script setup>
const props = defineProps({
    block: { type: Object, required: true }
})

const data = props.block.data || {}
const settings = props.block.settings || {}

// Mock products for preview
const mockProducts = [
    { id: 1, name: 'Diamond Necklace', price: 45000, image: '' },
    { id: 2, name: 'Gold Earrings', price: 28000, image: '' },
    { id: 3, name: 'Pearl Bracelet', price: 15000, image: '' },
    { id: 4, name: 'Ruby Ring', price: 35000, image: '' }
]

const displayProducts = data._products || mockProducts.slice(0, data.limit || 4)
</script>

<template>
    <div>
        <!-- Header -->
        <div v-if="data.title" class="text-center mb-6">
            <h2 class="text-2xl font-bold text-gray-800">{{ data.title }}</h2>
            <p v-if="data.subtitle" class="text-gray-600 mt-2">{{ data.subtitle }}</p>
        </div>

        <!-- Products Grid -->
        <div
            class="grid gap-4"
            :style="{ gridTemplateColumns: `repeat(${settings.columns || 4}, 1fr)` }"
        >
            <div
                v-for="(product, index) in displayProducts"
                :key="product.id || index"
                class="bg-white rounded-lg border hover:shadow-lg transition-shadow p-4"
            >
                <!-- Product Image -->
                <div class="aspect-square bg-gray-100 rounded-lg mb-3 flex items-center justify-center">
                    <img
                        v-if="product.image"
                        :src="product.image"
                        :alt="product.name"
                        class="w-full h-full object-cover rounded-lg"
                    >
                    <i v-else class="bi bi-gem text-4xl text-gray-300"></i>
                </div>

                <!-- Product Info -->
                <h3 class="font-medium text-gray-800 truncate">{{ product.name }}</h3>
                <p v-if="settings.show_price !== false" class="text-amber-600 font-semibold mt-1">
                    ₹{{ product.price?.toLocaleString() }}
                </p>

                <!-- Rating -->
                <div v-if="settings.show_rating" class="flex items-center mt-2">
                    <i class="bi bi-star-fill text-amber-400 text-sm"></i>
                    <span class="text-sm text-gray-500 ml-1">4.5</span>
                </div>

                <!-- Add to Cart -->
                <button
                    v-if="settings.show_add_to_cart"
                    class="w-full mt-3 px-4 py-2 bg-amber-600 text-white rounded-lg text-sm hover:bg-amber-700"
                >
                    Add to Cart
                </button>
            </div>
        </div>

        <!-- Source indicator -->
        <p class="text-center text-xs text-gray-400 mt-4">
            Source: {{ data.source || 'featured' }}
            <span v-if="data.category_id"> (Category #{{ data.category_id }})</span>
        </p>
    </div>
</template>
