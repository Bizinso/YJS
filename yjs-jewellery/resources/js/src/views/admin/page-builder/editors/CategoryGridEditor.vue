<script setup>
import { computed, ref, onMounted } from 'vue'
import axiosAdmin from '@/plugins/axiosAdmin'

const props = defineProps({ block: { type: Object, required: true } })
const emit = defineEmits(['update:data', 'update:settings'])

const data = computed(() => props.block.data || {})
const settings = computed(() => props.block.settings || {})
const categories = ref([])

const updateData = (key, value) => emit('update:data', { [key]: value })
const updateSettings = (key, value) => emit('update:settings', { [key]: value })

onMounted(async () => {
    try {
        const response = await axiosAdmin.get('/cms/block-data/categories')
        if (response.data.success) categories.value = response.data.data || []
    } catch (e) { console.error(e) }
})
</script>

<template>
    <div class="space-y-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Section Title</label>
            <input type="text" :value="data.title" @input="updateData('title', $event.target.value)" placeholder="Shop by Category" class="w-full px-3 py-2 border rounded-lg text-sm">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Subtitle</label>
            <input type="text" :value="data.subtitle" @input="updateData('subtitle', $event.target.value)" class="w-full px-3 py-2 border rounded-lg text-sm">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Source</label>
            <select :value="data.source" @change="updateData('source', $event.target.value)" class="w-full px-3 py-2 border rounded-lg text-sm">
                <option value="all">All Categories</option>
                <option value="parent_only">Parent Categories Only</option>
                <option value="selected">Selected Categories</option>
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Number of Categories</label>
            <input type="number" :value="data.limit" @input="updateData('limit', parseInt($event.target.value))" min="1" max="12" class="w-full px-3 py-2 border rounded-lg text-sm">
        </div>
        <div class="pt-4 border-t">
            <label class="block text-sm font-medium text-gray-700 mb-1">Columns</label>
            <select :value="settings.columns" @change="updateSettings('columns', parseInt($event.target.value))" class="w-full px-3 py-2 border rounded-lg text-sm">
                <option :value="2">2</option><option :value="3">3</option><option :value="4">4</option>
            </select>
        </div>
        <label class="flex items-center">
            <input type="checkbox" :checked="settings.show_product_count" @change="updateSettings('show_product_count', $event.target.checked)" class="rounded border-gray-300 text-amber-600">
            <span class="ml-2 text-sm text-gray-700">Show Product Count</span>
        </label>
    </div>
</template>
