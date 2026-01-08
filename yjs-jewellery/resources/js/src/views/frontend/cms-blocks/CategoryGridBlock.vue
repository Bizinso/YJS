<script setup>
import { ref, computed, onMounted } from 'vue'
import axios from 'axios'
const props = defineProps({ block: { type: Object, required: true } })

const data = computed(() => props.block.data || {})
const settings = computed(() => props.block.settings || {})
const categories = ref([])
const loading = ref(true)

const gridClass = computed(() => {
    const cols = settings.value.columns || 4
    return `grid-cols-2 md:grid-cols-${Math.min(cols, 3)} lg:grid-cols-${cols}`
})

onMounted(async () => {
    try {
        let fetchedCategories = []

        if (data.value.category_ids?.length) {
            // Fetch specific categories
            const { data: response } = await axios.get('/api/categories', {
                params: { ids: data.value.category_ids.join(',') }
            })
            fetchedCategories = response.data || []
        } else {
            // Fetch top-level categories
            const { data: response } = await axios.get('/api/categories', {
                params: { parent_id: 0, limit: settings.value.limit || 8 }
            })
            fetchedCategories = response.data || []
        }

        categories.value = fetchedCategories
    } catch (err) {
        console.error('Failed to fetch categories:', err)
    } finally {
        loading.value = false
    }
})
</script>

<template>
    <section class="category-grid-block py-12">
        <div class="container mx-auto px-4">
            <h2 v-if="data.title" class="text-3xl font-bold text-center mb-2">{{ data.title }}</h2>
            <p v-if="data.subtitle" class="text-gray-600 text-center mb-8">{{ data.subtitle }}</p>

            <div v-if="loading" class="flex justify-center py-8">
                <div class="w-8 h-8 border-4 border-amber-500 border-t-transparent rounded-full animate-spin"></div>
            </div>

            <div v-else :class="['grid gap-6', gridClass]">
                <router-link
                    v-for="category in categories"
                    :key="category.id"
                    :to="`/category/${category.slug}`"
                    class="group relative overflow-hidden rounded-lg aspect-square"
                >
                    <img
                        :src="category.image || '/images/category-placeholder.jpg'"
                        :alt="category.name"
                        class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-110"
                    >
                    <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/20 to-transparent"></div>
                    <div class="absolute bottom-0 left-0 right-0 p-4 text-white">
                        <h3 class="font-bold text-lg">{{ category.name }}</h3>
                        <p v-if="settings.show_count && category.products_count" class="text-sm opacity-80">
                            {{ category.products_count }} Products
                        </p>
                    </div>
                </router-link>
            </div>
        </div>
    </section>
</template>
