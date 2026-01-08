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
            <label class="block text-sm font-medium text-gray-700 mb-1">HTML</label>
            <textarea :value="data.html" @input="updateData('html', $event.target.value)" rows="8" class="w-full px-3 py-2 border rounded-lg text-sm font-mono" placeholder="<div>Your HTML here</div>"></textarea>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">CSS</label>
            <textarea :value="data.css" @input="updateData('css', $event.target.value)" rows="6" class="w-full px-3 py-2 border rounded-lg text-sm font-mono" placeholder=".my-class { color: red; }"></textarea>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">JavaScript</label>
            <textarea :value="data.js" @input="updateData('js', $event.target.value)" rows="6" class="w-full px-3 py-2 border rounded-lg text-sm font-mono" placeholder="console.log('Hello');"></textarea>
            <p class="text-xs text-amber-600 mt-1"><i class="bi bi-exclamation-triangle me-1"></i>JS is disabled in preview for security</p>
        </div>
        <div class="pt-4 border-t">
            <label class="flex items-center"><input type="checkbox" :checked="settings.sandbox" @change="updateSettings('sandbox', $event.target.checked)" class="rounded border-gray-300 text-amber-600"><span class="ml-2 text-sm">Sandbox Mode (recommended)</span></label>
        </div>
    </div>
</template>
