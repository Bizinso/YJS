<script setup>
import { computed, ref, onMounted } from 'vue'
import axiosAdmin from '@/plugins/axiosAdmin'

const props = defineProps({
    block: { type: Object, required: true }
})

const emit = defineEmits(['update:data', 'update:settings'])

const data = computed(() => props.block.data || {})
const settings = computed(() => props.block.settings || {})

const categories = ref([])
const products = ref([])
const loadingCategories = ref(false)
const loadingProducts = ref(false)

const sourceOptions = [
    { value: 'featured', label: 'Featured Products' },
    { value: 'bestsellers', label: 'Best Sellers' },
    { value: 'new_arrivals', label: 'New Arrivals' },
    { value: 'category', label: 'From Category' },
    { value: 'manual', label: 'Manual Selection' }
]

const updateData = (key, value) => {
    emit('update:data', { [key]: value })
}

const updateSettings = (key, value) => {
    emit('update:settings', { [key]: value })
}

const fetchCategories = async () => {
    loadingCategories.value = true
    try {
        const response = await axiosAdmin.get('/cms/block-data/categories')
        if (response.data.success) {
            categories.value = response.data.data || []
        }
    } catch (error) {
        console.error('Error fetching categories:', error)
    } finally {
        loadingCategories.value = false
    }
}

const fetchProducts = async () => {
    loadingProducts.value = true
    try {
        const response = await axiosAdmin.get('/cms/block-data/products')
        if (response.data.success) {
            products.value = response.data.data || []
        }
    } catch (error) {
        console.error('Error fetching products:', error)
    } finally {
        loadingProducts.value = false
    }
}

onMounted(() => {
    fetchCategories()
    fetchProducts()
})
</script>

<template>
    <div class="space-y-4">
        <!-- Title -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Section Title</label>
            <input
                type="text"
                :value="data.title"
                @input="updateData('title', $event.target.value)"
                placeholder="Featured Products"
                class="w-full px-3 py-2 border rounded-lg text-sm"
            >
        </div>

        <!-- Subtitle -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Subtitle</label>
            <input
                type="text"
                :value="data.subtitle"
                @input="updateData('subtitle', $event.target.value)"
                placeholder="Check out our latest collection"
                class="w-full px-3 py-2 border rounded-lg text-sm"
            >
        </div>

        <!-- Source -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Product Source</label>
            <select
                :value="data.source"
                @change="updateData('source', $event.target.value)"
                class="w-full px-3 py-2 border rounded-lg text-sm"
            >
                <option v-for="option in sourceOptions" :key="option.value" :value="option.value">
                    {{ option.label }}
                </option>
            </select>
        </div>

        <!-- Category Selection -->
        <div v-if="data.source === 'category'">
            <label class="block text-sm font-medium text-gray-700 mb-1">Select Category</label>
            <select
                :value="data.category_id"
                @change="updateData('category_id', parseInt($event.target.value))"
                class="w-full px-3 py-2 border rounded-lg text-sm"
                :disabled="loadingCategories"
            >
                <option :value="null">Select a category</option>
                <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                    {{ cat.name }}
                </option>
            </select>
        </div>

        <!-- Product Count -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Number of Products</label>
            <input
                type="number"
                :value="data.limit"
                @input="updateData('limit', parseInt($event.target.value))"
                min="1"
                max="24"
                class="w-full px-3 py-2 border rounded-lg text-sm"
            >
        </div>

        <!-- Layout Settings -->
        <div class="pt-4 border-t space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Layout</label>
                <select
                    :value="settings.layout"
                    @change="updateSettings('layout', $event.target.value)"
                    class="w-full px-3 py-2 border rounded-lg text-sm"
                >
                    <option value="grid">Grid</option>
                    <option value="carousel">Carousel</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Columns</label>
                <select
                    :value="settings.columns"
                    @change="updateSettings('columns', parseInt($event.target.value))"
                    class="w-full px-3 py-2 border rounded-lg text-sm"
                >
                    <option :value="2">2 Columns</option>
                    <option :value="3">3 Columns</option>
                    <option :value="4">4 Columns</option>
                    <option :value="5">5 Columns</option>
                </select>
            </div>

            <!-- Display Options -->
            <div class="space-y-2">
                <label class="flex items-center">
                    <input
                        type="checkbox"
                        :checked="settings.show_price !== false"
                        @change="updateSettings('show_price', $event.target.checked)"
                        class="rounded border-gray-300 text-amber-600"
                    >
                    <span class="ml-2 text-sm text-gray-700">Show Price</span>
                </label>
                <label class="flex items-center">
                    <input
                        type="checkbox"
                        :checked="settings.show_rating"
                        @change="updateSettings('show_rating', $event.target.checked)"
                        class="rounded border-gray-300 text-amber-600"
                    >
                    <span class="ml-2 text-sm text-gray-700">Show Rating</span>
                </label>
                <label class="flex items-center">
                    <input
                        type="checkbox"
                        :checked="settings.show_add_to_cart"
                        @change="updateSettings('show_add_to_cart', $event.target.checked)"
                        class="rounded border-gray-300 text-amber-600"
                    >
                    <span class="ml-2 text-sm text-gray-700">Show Add to Cart Button</span>
                </label>
                <label class="flex items-center">
                    <input
                        type="checkbox"
                        :checked="settings.show_wishlist"
                        @change="updateSettings('show_wishlist', $event.target.checked)"
                        class="rounded border-gray-300 text-amber-600"
                    >
                    <span class="ml-2 text-sm text-gray-700">Show Wishlist Button</span>
                </label>
            </div>
        </div>
    </div>
</template>
