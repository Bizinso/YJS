<script setup>
import { computed } from 'vue'

const props = defineProps({ block: { type: Object, required: true } })
const emit = defineEmits(['update:data', 'update:settings'])

const data = computed(() => props.block.data || {})
const settings = computed(() => props.block.settings || {})

const updateData = (key, value) => emit('update:data', { [key]: value })
const updateSettings = (key, value) => emit('update:settings', { [key]: value })
</script>

<template>
    <div class="space-y-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Logo Image URL</label>
            <input type="text" :value="data.image_url" @input="updateData('image_url', $event.target.value)" placeholder="https://..." class="w-full px-3 py-2 border rounded-lg text-sm">
            <p class="text-xs text-gray-500 mt-1">Or upload via Media Manager</p>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Alt Text</label>
            <input type="text" :value="data.alt" @input="updateData('alt', $event.target.value)" placeholder="Company Logo" class="w-full px-3 py-2 border rounded-lg text-sm">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Link URL</label>
            <input type="text" :value="data.link" @input="updateData('link', $event.target.value)" placeholder="/" class="w-full px-3 py-2 border rounded-lg text-sm">
        </div>
        <div class="pt-4 border-t">
            <label class="block text-sm font-medium text-gray-700 mb-1">Max Width (px)</label>
            <input type="number" :value="settings.max_width" @input="updateSettings('max_width', parseInt($event.target.value))" class="w-full px-3 py-2 border rounded-lg text-sm">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Mobile Width (px)</label>
            <input type="number" :value="settings.mobile_width" @input="updateSettings('mobile_width', parseInt($event.target.value))" class="w-full px-3 py-2 border rounded-lg text-sm">
        </div>
    </div>
</template>
