<script setup>
import { computed } from 'vue'

const props = defineProps({ block: { type: Object, required: true } })
const emit = defineEmits(['update:data', 'update:settings'])

const data = computed(() => props.block.data || {})
const settings = computed(() => props.block.settings || {})

const updateData = (key, value) => emit('update:data', { [key]: value })
const updateSettings = (key, value) => emit('update:settings', { [key]: value })

const platforms = [
    { value: 'facebook', label: 'Facebook' },
    { value: 'instagram', label: 'Instagram' },
    { value: 'twitter', label: 'Twitter/X' },
    { value: 'youtube', label: 'YouTube' },
    { value: 'linkedin', label: 'LinkedIn' },
    { value: 'pinterest', label: 'Pinterest' },
    { value: 'whatsapp', label: 'WhatsApp' },
    { value: 'tiktok', label: 'TikTok' },
]

const addIcon = () => {
    const icons = [...(data.value.icons || [])]
    icons.push({ platform: 'facebook', url: '#' })
    updateData('icons', icons)
}

const updateIcon = (index, key, value) => {
    const icons = [...(data.value.icons || [])]
    icons[index][key] = value
    updateData('icons', icons)
}

const removeIcon = (index) => {
    const icons = [...(data.value.icons || [])]
    icons.splice(index, 1)
    updateData('icons', icons)
}
</script>

<template>
    <div class="space-y-4">
        <div>
            <div class="flex justify-between items-center mb-2">
                <label class="text-sm font-medium text-gray-700">Social Icons</label>
                <button @click="addIcon" class="text-sm text-amber-600">+ Add Icon</button>
            </div>
            <div v-for="(icon, i) in (data.icons || [])" :key="i" class="mb-2 p-2 bg-gray-50 rounded flex gap-2 items-start">
                <div class="flex-1 space-y-2">
                    <select :value="icon.platform" @change="updateIcon(i, 'platform', $event.target.value)" class="w-full px-2 py-1 border rounded text-sm">
                        <option v-for="p in platforms" :key="p.value" :value="p.value">{{ p.label }}</option>
                    </select>
                    <input type="text" :value="icon.url" @input="updateIcon(i, 'url', $event.target.value)" placeholder="Profile URL" class="w-full px-2 py-1 border rounded text-sm">
                </div>
                <button @click="removeIcon(i)" class="text-red-400 hover:text-red-600">
                    <i class="bi bi-trash"></i>
                </button>
            </div>
        </div>

        <div class="pt-4 border-t">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Icon Size</label>
                <select :value="settings.size" @change="updateSettings('size', $event.target.value)" class="w-full px-3 py-2 border rounded-lg text-sm">
                    <option value="small">Small</option>
                    <option value="medium">Medium</option>
                    <option value="large">Large</option>
                </select>
            </div>
            <div class="mt-3">
                <label class="block text-sm font-medium text-gray-700 mb-1">Style</label>
                <select :value="settings.style" @change="updateSettings('style', $event.target.value)" class="w-full px-3 py-2 border rounded-lg text-sm">
                    <option value="filled">Filled</option>
                    <option value="outline">Outline</option>
                </select>
            </div>
        </div>
    </div>
</template>
