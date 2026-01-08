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
        <div><label class="block text-sm font-medium text-gray-700 mb-1">Title (optional)</label><input type="text" :value="data.title" @input="updateData('title', $event.target.value)" class="w-full px-3 py-2 border rounded-lg text-sm"></div>
        <div><label class="block text-sm font-medium text-gray-700 mb-1">Video Type</label><select :value="data.video_type" @change="updateData('video_type', $event.target.value)" class="w-full px-3 py-2 border rounded-lg text-sm"><option value="youtube">YouTube</option><option value="vimeo">Vimeo</option><option value="self-hosted">Self-hosted</option></select></div>
        <div><label class="block text-sm font-medium text-gray-700 mb-1">Video URL</label><input type="text" :value="data.video_url" @input="updateData('video_url', $event.target.value)" :placeholder="data.video_type === 'youtube' ? 'https://youtube.com/watch?v=...' : 'https://vimeo.com/...'" class="w-full px-3 py-2 border rounded-lg text-sm"></div>
        <div><label class="block text-sm font-medium text-gray-700 mb-1">Thumbnail URL (optional)</label><input type="text" :value="data.thumbnail" @input="updateData('thumbnail', $event.target.value)" class="w-full px-3 py-2 border rounded-lg text-sm"></div>

        <div class="pt-4 border-t space-y-4">
            <div><label class="block text-sm font-medium text-gray-700 mb-1">Aspect Ratio</label><select :value="settings.aspect_ratio" @change="updateSettings('aspect_ratio', $event.target.value)" class="w-full px-3 py-2 border rounded-lg text-sm"><option value="16:9">16:9</option><option value="4:3">4:3</option><option value="1:1">1:1</option><option value="21:9">21:9</option></select></div>
            <label class="flex items-center"><input type="checkbox" :checked="settings.autoplay" @change="updateSettings('autoplay', $event.target.checked)" class="rounded border-gray-300 text-amber-600"><span class="ml-2 text-sm">Autoplay</span></label>
            <label class="flex items-center"><input type="checkbox" :checked="settings.loop" @change="updateSettings('loop', $event.target.checked)" class="rounded border-gray-300 text-amber-600"><span class="ml-2 text-sm">Loop</span></label>
            <label class="flex items-center"><input type="checkbox" :checked="settings.controls !== false" @change="updateSettings('controls', $event.target.checked)" class="rounded border-gray-300 text-amber-600"><span class="ml-2 text-sm">Show Controls</span></label>
        </div>
    </div>
</template>
