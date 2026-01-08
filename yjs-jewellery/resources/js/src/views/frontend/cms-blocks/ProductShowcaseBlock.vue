<script setup>
import { ref, computed, onMounted } from 'vue'
import axios from 'axios'
const props = defineProps({ block: { type: Object, required: true } })

const data = computed(() => props.block.data || {})
const settings = computed(() => props.block.settings || {})
const products = ref([])
const loading = ref(true)

const gridClass = computed(() => {
    const cols = settings.value.columns || 4
    return `grid-cols-2 md:grid-cols-${Math.min(cols, 3)} lg:grid-cols-${cols}`
})

onMounted(async () => {
    try {
        // Fetch products based on settings
        const params = { limit: settings.value.limit || 8 }
        if (data.value.source === 'category' && data.value.category_id) {
            params.category_id = data.value.category_id
        }
        if (data.value.source === 'manual' && data.value.product_ids?.length) {
            params.ids = data.value.product_ids.join(',')
        }
        if (data.value.source === 'featured') {
            params.featured = true
        }
        if (data.value.source === 'bestsellers') {
            params.bestsellers = true
        }

        const { data: response } = await axios.get('/api/products', { params })
        products.value = response.data || []
    } catch (err) {
        console.error('Failed to fetch products:', err)
    } finally {
        loading.value = false
    }
})

const formatPrice = (price) => {
    return new Intl.NumberFormat('en-IN', {
        style: 'currency',
        currency: 'INR',
        minimumFractionDigits: 0
    }).format(price)
}
</script>

<template>
    <section class="product-showcase-block py-12 bg-gray-50">
        <div class="container mx-auto px-4">
            <h2 v-if="data.title" class="text-3xl font-bold text-center mb-2">{{ data.title }}</h2>
            <p v-if="data.subtitle" class="text-gray-600 text-center mb-8">{{ data.subtitle }}</p>

            <div v-if="loading" class="flex justify-center py-8">
                <div class="w-8 h-8 border-4 border-amber-500 border-t-transparent rounded-full animate-spin"></div>
            </div>

            <div v-else :class="['grid gap-6', gridClass]">
                <div
                    v-for="product in products"
                    :key="product.id"
                    class="bg-white rounded-lg shadow-sm overflow-hidden group"
                >
                    <router-link :to="`/product/${product.slug}`" class="block">
                        <div class="aspect-square overflow-hidden bg-gray-100">
                            <img
                                :src="product.main_image || '/images/placeholder.jpg'"
                                :alt="product.name"
                                class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-110"
                            >
                        </div>
                        <div class="p-4">
                            <h3 class="font-semibold text-gray-800 truncate group-hover:text-amber-600">
                                {{ product.name }}
                            </h3>
                            <p class="text-amber-600 font-bold mt-1">
                                {{ formatPrice(product.final_price || product.price) }}
                            </p>
                        </div>
                    </router-link>
                </div>
            </div>

            <div v-if="data.button_text" class="text-center mt-8">
                <router-link
                    :to="data.button_url || '/products'"
                    class="inline-block px-8 py-3 bg-amber-600 text-white font-semibold rounded-lg hover:bg-amber-700 transition-colors"
                >
                    {{ data.button_text }}
                </router-link>
            </div>
        </div>
    </section>
</template>
