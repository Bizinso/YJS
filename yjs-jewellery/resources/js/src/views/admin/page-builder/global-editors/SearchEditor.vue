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
            <label class="block text-sm font-medium text-gray-700 mb-1">Placeholder Text</label>
            <input type="text" :value="data.placeholder" @input="updateData('placeholder', $event.target.value)" class="w-full px-3 py-2 border rounded-lg text-sm">
        </div>

        <div class="pt-4 border-t">
            <label class="block text-sm font-medium text-gray-700 mb-1">Style</label>
            <select :value="settings.style" @change="updateSettings('style', $event.target.value)" class="w-full px-3 py-2 border rounded-lg text-sm">
                <option value="inline">Inline Search Bar</option>
                <option value="icon">Icon Only (Expandable)</option>
            </select>
        </div>

        <label class="flex items-center pt-2">
            <input type="checkbox" :checked="settings.show_suggestions" @change="updateSettings('show_suggestions', $event.target.checked)" class="rounded border-gray-300 text-amber-600">
            <span class="ml-2 text-sm">Show Search Suggestions</span>
        </label>
    </div>
</template>
