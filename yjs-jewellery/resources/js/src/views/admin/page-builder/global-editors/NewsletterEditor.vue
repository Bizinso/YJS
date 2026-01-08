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
            <label class="block text-sm font-medium text-gray-700 mb-1">Title</label>
            <input type="text" :value="data.title" @input="updateData('title', $event.target.value)" class="w-full px-3 py-2 border rounded-lg text-sm">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
            <textarea :value="data.description" @input="updateData('description', $event.target.value)" rows="2" class="w-full px-3 py-2 border rounded-lg text-sm"></textarea>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Button Text</label>
            <input type="text" :value="data.button_text" @input="updateData('button_text', $event.target.value)" class="w-full px-3 py-2 border rounded-lg text-sm">
        </div>

        <div class="pt-4 border-t">
            <label class="block text-sm font-medium text-gray-700 mb-1">Layout Style</label>
            <select :value="settings.style" @change="updateSettings('style', $event.target.value)" class="w-full px-3 py-2 border rounded-lg text-sm">
                <option value="inline">Inline (input + button side by side)</option>
                <option value="stacked">Stacked (input above button)</option>
            </select>
        </div>
    </div>
</template>
