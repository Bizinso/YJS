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
        <div><label class="block text-sm font-medium text-gray-700 mb-1">Title</label><input type="text" :value="data.title" @input="updateData('title', $event.target.value)" class="w-full px-3 py-2 border rounded-lg text-sm"></div>
        <div><label class="block text-sm font-medium text-gray-700 mb-1">Subtitle</label><input type="text" :value="data.subtitle" @input="updateData('subtitle', $event.target.value)" class="w-full px-3 py-2 border rounded-lg text-sm"></div>
        <div><label class="block text-sm font-medium text-gray-700 mb-1">Background Image URL</label><input type="text" :value="data.background_image" @input="updateData('background_image', $event.target.value)" class="w-full px-3 py-2 border rounded-lg text-sm"></div>
        <div><label class="block text-sm font-medium text-gray-700 mb-1">Background Color</label><div class="flex space-x-2"><input type="color" :value="data.background_color || '#f8f9fa'" @input="updateData('background_color', $event.target.value)" class="w-10 h-10 border rounded"><input type="text" :value="data.background_color" @input="updateData('background_color', $event.target.value)" class="flex-1 px-3 py-2 border rounded-lg text-sm"></div></div>
        <div class="grid grid-cols-2 gap-2"><div><label class="block text-sm font-medium text-gray-700 mb-1">Button Text</label><input type="text" :value="data.cta_text" @input="updateData('cta_text', $event.target.value)" class="w-full px-3 py-2 border rounded-lg text-sm"></div><div><label class="block text-sm font-medium text-gray-700 mb-1">Button Link</label><input type="text" :value="data.cta_link" @input="updateData('cta_link', $event.target.value)" class="w-full px-3 py-2 border rounded-lg text-sm"></div></div>
        <div class="pt-4 border-t space-y-2">
            <label class="flex items-center"><input type="checkbox" :checked="settings.show_countdown" @change="updateSettings('show_countdown', $event.target.checked)" class="rounded border-gray-300 text-amber-600"><span class="ml-2 text-sm">Show Countdown Timer</span></label>
            <label class="flex items-center"><input type="checkbox" :checked="settings.show_discount" @change="updateSettings('show_discount', $event.target.checked)" class="rounded border-gray-300 text-amber-600"><span class="ml-2 text-sm">Show Discount Badge</span></label>
        </div>
    </div>
</template>
