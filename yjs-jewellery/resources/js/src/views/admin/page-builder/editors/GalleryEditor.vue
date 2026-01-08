<script setup>
import { computed } from 'vue'

const props = defineProps({ block: { type: Object, required: true } })
const emit = defineEmits(['update:data', 'update:settings'])

const data = computed(() => props.block.data || {})
const settings = computed(() => props.block.settings || {})

const updateData = (key, value) => emit('update:data', { [key]: value })
const updateSettings = (key, value) => emit('update:settings', { [key]: value })

const addImage = () => {
    const images = [...(data.value.images || [])]
    images.push({ src: '', alt: '', caption: '' })
    updateData('images', images)
}

const updateImage = (index, key, value) => {
    const images = [...(data.value.images || [])]
    images[index][key] = value
    updateData('images', images)
}

const removeImage = (index) => {
    const images = [...(data.value.images || [])]
    images.splice(index, 1)
    updateData('images', images)
}
</script>

<template>
    <div class="space-y-4">
        <div><label class="block text-sm font-medium text-gray-700 mb-1">Gallery Title</label><input type="text" :value="data.title" @input="updateData('title', $event.target.value)" class="w-full px-3 py-2 border rounded-lg text-sm"></div>

        <div class="pt-4 border-t">
            <div class="flex justify-between items-center mb-3"><label class="text-sm font-medium text-gray-700">Images</label><button @click="addImage" class="text-sm text-amber-600">+ Add Image</button></div>
            <div v-for="(img, i) in data.images" :key="i" class="mb-3 p-3 bg-gray-50 rounded-lg">
                <div class="flex justify-between mb-2"><span class="text-xs text-gray-500">#{{ i + 1 }}</span><button @click="removeImage(i)" class="text-red-500 text-xs">Remove</button></div>
                <input type="text" :value="img.src" @input="updateImage(i, 'src', $event.target.value)" placeholder="Image URL" class="w-full px-2 py-1 border rounded text-sm mb-2">
                <input type="text" :value="img.alt" @input="updateImage(i, 'alt', $event.target.value)" placeholder="Alt text" class="w-full px-2 py-1 border rounded text-sm mb-2">
                <input type="text" :value="img.caption" @input="updateImage(i, 'caption', $event.target.value)" placeholder="Caption" class="w-full px-2 py-1 border rounded text-sm">
            </div>
        </div>

        <div class="pt-4 border-t space-y-4">
            <div><label class="block text-sm font-medium text-gray-700 mb-1">Layout</label><select :value="settings.layout" @change="updateSettings('layout', $event.target.value)" class="w-full px-3 py-2 border rounded-lg text-sm"><option value="grid">Grid</option><option value="carousel">Carousel</option><option value="masonry">Masonry</option></select></div>
            <div><label class="block text-sm font-medium text-gray-700 mb-1">Columns</label><select :value="settings.columns" @change="updateSettings('columns', parseInt($event.target.value))" class="w-full px-3 py-2 border rounded-lg text-sm"><option :value="2">2</option><option :value="3">3</option><option :value="4">4</option><option :value="5">5</option></select></div>
            <label class="flex items-center"><input type="checkbox" :checked="settings.lightbox" @change="updateSettings('lightbox', $event.target.checked)" class="rounded border-gray-300 text-amber-600"><span class="ml-2 text-sm">Enable Lightbox</span></label>
            <label class="flex items-center"><input type="checkbox" :checked="settings.show_captions" @change="updateSettings('show_captions', $event.target.checked)" class="rounded border-gray-300 text-amber-600"><span class="ml-2 text-sm">Show Captions</span></label>
        </div>
    </div>
</template>
