<script setup>
import { computed } from 'vue'

const props = defineProps({ block: { type: Object, required: true } })
const emit = defineEmits(['update:data', 'update:settings'])

const data = computed(() => props.block.data || {})
const settings = computed(() => props.block.settings || {})

const updateData = (key, value) => emit('update:data', { [key]: value })
const updateSettings = (key, value) => emit('update:settings', { [key]: value })

const addColumn = () => {
    const columns = [...(data.value.columns || [])]
    columns.push({ title: 'New Column', links: [] })
    updateData('columns', columns)
}

const updateColumn = (index, key, value) => {
    const columns = [...(data.value.columns || [])]
    columns[index][key] = value
    updateData('columns', columns)
}

const removeColumn = (index) => {
    const columns = [...(data.value.columns || [])]
    columns.splice(index, 1)
    updateData('columns', columns)
}

const addLink = (colIndex) => {
    const columns = [...(data.value.columns || [])]
    if (!columns[colIndex].links) columns[colIndex].links = []
    columns[colIndex].links.push({ label: 'New Link', url: '#' })
    updateData('columns', columns)
}

const updateLink = (colIndex, linkIndex, key, value) => {
    const columns = [...(data.value.columns || [])]
    columns[colIndex].links[linkIndex][key] = value
    updateData('columns', columns)
}

const removeLink = (colIndex, linkIndex) => {
    const columns = [...(data.value.columns || [])]
    columns[colIndex].links.splice(linkIndex, 1)
    updateData('columns', columns)
}
</script>

<template>
    <div class="space-y-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Columns</label>
            <select :value="settings.columns_count" @change="updateSettings('columns_count', parseInt($event.target.value))" class="w-full px-3 py-2 border rounded-lg text-sm">
                <option :value="2">2 Columns</option>
                <option :value="3">3 Columns</option>
                <option :value="4">4 Columns</option>
            </select>
        </div>

        <div class="pt-4 border-t">
            <div class="flex justify-between items-center mb-3">
                <label class="text-sm font-medium text-gray-700">Link Columns</label>
                <button @click="addColumn" class="text-sm text-amber-600">+ Add Column</button>
            </div>

            <div v-for="(col, i) in (data.columns || [])" :key="i" class="mb-4 p-3 bg-gray-50 rounded-lg">
                <div class="flex justify-between items-center mb-2">
                    <input type="text" :value="col.title" @input="updateColumn(i, 'title', $event.target.value)" placeholder="Column Title" class="flex-1 px-2 py-1 border rounded text-sm font-medium">
                    <button @click="removeColumn(i)" class="ml-2 text-red-500 text-xs">Remove</button>
                </div>

                <div class="mt-2 space-y-2">
                    <div class="flex justify-between items-center">
                        <span class="text-xs text-gray-500">Links</span>
                        <button @click="addLink(i)" class="text-xs text-amber-600">+ Add Link</button>
                    </div>
                    <div v-for="(link, j) in (col.links || [])" :key="j" class="flex gap-2 items-start">
                        <div class="flex-1 space-y-1">
                            <input type="text" :value="link.label" @input="updateLink(i, j, 'label', $event.target.value)" placeholder="Label" class="w-full px-2 py-1 border rounded text-xs">
                            <input type="text" :value="link.url" @input="updateLink(i, j, 'url', $event.target.value)" placeholder="URL" class="w-full px-2 py-1 border rounded text-xs">
                        </div>
                        <button @click="removeLink(i, j)" class="text-red-400 text-xs mt-1">x</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
