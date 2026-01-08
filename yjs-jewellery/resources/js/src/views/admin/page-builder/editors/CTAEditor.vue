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
        <div><label class="block text-sm font-medium text-gray-700 mb-1">Title</label><input type="text" :value="data.title" @input="updateData('title', $event.target.value)" placeholder="Ready to get started?" class="w-full px-3 py-2 border rounded-lg text-sm"></div>
        <div><label class="block text-sm font-medium text-gray-700 mb-1">Description</label><textarea :value="data.description" @input="updateData('description', $event.target.value)" rows="2" class="w-full px-3 py-2 border rounded-lg text-sm"></textarea></div>
        <div class="grid grid-cols-2 gap-2"><div><label class="block text-sm font-medium text-gray-700 mb-1">Primary Button</label><input type="text" :value="data.primary_button_text" @input="updateData('primary_button_text', $event.target.value)" class="w-full px-3 py-2 border rounded-lg text-sm mb-1"><input type="text" :value="data.primary_button_link" @input="updateData('primary_button_link', $event.target.value)" placeholder="Link" class="w-full px-3 py-2 border rounded-lg text-sm"></div><div><label class="block text-sm font-medium text-gray-700 mb-1">Secondary Button</label><input type="text" :value="data.secondary_button_text" @input="updateData('secondary_button_text', $event.target.value)" class="w-full px-3 py-2 border rounded-lg text-sm mb-1"><input type="text" :value="data.secondary_button_link" @input="updateData('secondary_button_link', $event.target.value)" placeholder="Link" class="w-full px-3 py-2 border rounded-lg text-sm"></div></div>
        <div><label class="block text-sm font-medium text-gray-700 mb-1">Background Color</label><div class="flex space-x-2"><input type="color" :value="data.background_color || '#1a1a2e'" @input="updateData('background_color', $event.target.value)" class="w-10 h-10 border rounded"><input type="text" :value="data.background_color" @input="updateData('background_color', $event.target.value)" class="flex-1 px-3 py-2 border rounded-lg text-sm"></div></div>
        <div><label class="block text-sm font-medium text-gray-700 mb-1">Text Color</label><div class="flex space-x-2"><input type="color" :value="data.text_color || '#ffffff'" @input="updateData('text_color', $event.target.value)" class="w-10 h-10 border rounded"><input type="text" :value="data.text_color" @input="updateData('text_color', $event.target.value)" class="flex-1 px-3 py-2 border rounded-lg text-sm"></div></div>
        <div><label class="block text-sm font-medium text-gray-700 mb-1">Layout</label><select :value="settings.layout" @change="updateSettings('layout', $event.target.value)" class="w-full px-3 py-2 border rounded-lg text-sm"><option value="horizontal">Horizontal</option><option value="vertical">Vertical</option><option value="centered">Centered</option></select></div>
    </div>
</template>
