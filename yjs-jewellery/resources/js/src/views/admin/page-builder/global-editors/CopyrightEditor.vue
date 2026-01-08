<script setup>
import { computed } from 'vue'

const props = defineProps({ block: { type: Object, required: true } })
const emit = defineEmits(['update:data', 'update:settings'])

const data = computed(() => props.block.data || {})

const updateData = (key, value) => emit('update:data', { [key]: value })

const addLink = () => {
    const links = [...(data.value.links || [])]
    links.push({ label: 'New Link', url: '#' })
    updateData('links', links)
}

const updateLink = (index, key, value) => {
    const links = [...(data.value.links || [])]
    links[index][key] = value
    updateData('links', links)
}

const removeLink = (index) => {
    const links = [...(data.value.links || [])]
    links.splice(index, 1)
    updateData('links', links)
}
</script>

<template>
    <div class="space-y-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Copyright Text</label>
            <input type="text" :value="data.text" @input="updateData('text', $event.target.value)" class="w-full px-3 py-2 border rounded-lg text-sm">
            <p class="text-xs text-gray-500 mt-1">Use {year} for dynamic year</p>
        </div>

        <div class="pt-4 border-t">
            <div class="flex justify-between items-center mb-2">
                <label class="text-sm font-medium text-gray-700">Links</label>
                <button @click="addLink" class="text-sm text-amber-600">+ Add Link</button>
            </div>
            <div v-for="(link, i) in (data.links || [])" :key="i" class="mb-2 p-2 bg-gray-50 rounded flex gap-2 items-start">
                <div class="flex-1 space-y-1">
                    <input type="text" :value="link.label" @input="updateLink(i, 'label', $event.target.value)" placeholder="Label" class="w-full px-2 py-1 border rounded text-sm">
                    <input type="text" :value="link.url" @input="updateLink(i, 'url', $event.target.value)" placeholder="URL" class="w-full px-2 py-1 border rounded text-sm">
                </div>
                <button @click="removeLink(i)" class="text-red-400 hover:text-red-600">
                    <i class="bi bi-trash"></i>
                </button>
            </div>
        </div>
    </div>
</template>
