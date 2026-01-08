<script setup>
import { computed } from 'vue'

const props = defineProps({ block: { type: Object, required: true } })
const emit = defineEmits(['update:data', 'update:settings'])

const data = computed(() => props.block.data || {})
const settings = computed(() => props.block.settings || {})

const updateData = (key, value) => emit('update:data', { [key]: value })
const updateSettings = (key, value) => emit('update:settings', { [key]: value })

const addMenuItem = () => {
    const items = [...(data.value.items || [])]
    items.push({ label: 'New Item', url: '#', children: [] })
    updateData('items', items)
}

const updateMenuItem = (index, key, value) => {
    const items = [...(data.value.items || [])]
    items[index][key] = value
    updateData('items', items)
}

const removeMenuItem = (index) => {
    const items = [...(data.value.items || [])]
    items.splice(index, 1)
    updateData('items', items)
}

const addChildItem = (parentIndex) => {
    const items = [...(data.value.items || [])]
    if (!items[parentIndex].children) items[parentIndex].children = []
    items[parentIndex].children.push({ label: 'Sub Item', url: '#' })
    updateData('items', items)
}

const updateChildItem = (parentIndex, childIndex, key, value) => {
    const items = [...(data.value.items || [])]
    items[parentIndex].children[childIndex][key] = value
    updateData('items', items)
}

const removeChildItem = (parentIndex, childIndex) => {
    const items = [...(data.value.items || [])]
    items[parentIndex].children.splice(childIndex, 1)
    updateData('items', items)
}
</script>

<template>
    <div class="space-y-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Style</label>
            <select :value="settings.style" @change="updateSettings('style', $event.target.value)" class="w-full px-3 py-2 border rounded-lg text-sm">
                <option value="horizontal">Horizontal</option>
                <option value="vertical">Vertical</option>
            </select>
        </div>

        <div class="pt-4 border-t">
            <div class="flex justify-between items-center mb-3">
                <label class="text-sm font-medium text-gray-700">Menu Items</label>
                <button @click="addMenuItem" class="text-sm text-amber-600 hover:text-amber-700">+ Add Item</button>
            </div>

            <div v-for="(item, i) in (data.items || [])" :key="i" class="mb-4 p-3 bg-gray-50 rounded-lg">
                <div class="flex justify-between items-center mb-2">
                    <span class="text-xs font-medium text-gray-500">Item {{ i + 1 }}</span>
                    <button @click="removeMenuItem(i)" class="text-red-500 text-xs hover:text-red-700">Remove</button>
                </div>
                <input type="text" :value="item.label" @input="updateMenuItem(i, 'label', $event.target.value)" placeholder="Label" class="w-full px-2 py-1 border rounded text-sm mb-2">
                <input type="text" :value="item.url" @input="updateMenuItem(i, 'url', $event.target.value)" placeholder="URL" class="w-full px-2 py-1 border rounded text-sm mb-2">

                <!-- Children -->
                <div class="mt-2 pl-3 border-l-2 border-gray-200">
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-xs text-gray-500">Dropdown Items</span>
                        <button @click="addChildItem(i)" class="text-xs text-amber-600 hover:text-amber-700">+ Add</button>
                    </div>
                    <div v-for="(child, j) in (item.children || [])" :key="j" class="mb-2 p-2 bg-white rounded border">
                        <div class="flex justify-between mb-1">
                            <span class="text-xs text-gray-400">{{ j + 1 }}</span>
                            <button @click="removeChildItem(i, j)" class="text-red-400 text-xs">x</button>
                        </div>
                        <input type="text" :value="child.label" @input="updateChildItem(i, j, 'label', $event.target.value)" placeholder="Label" class="w-full px-2 py-1 border rounded text-xs mb-1">
                        <input type="text" :value="child.url" @input="updateChildItem(i, j, 'url', $event.target.value)" placeholder="URL" class="w-full px-2 py-1 border rounded text-xs">
                    </div>
                </div>
            </div>
        </div>

        <div class="pt-4 border-t">
            <label class="block text-sm font-medium text-gray-700 mb-1">Mobile Breakpoint (px)</label>
            <input type="number" :value="settings.mobile_breakpoint" @input="updateSettings('mobile_breakpoint', parseInt($event.target.value))" class="w-full px-3 py-2 border rounded-lg text-sm">
        </div>
    </div>
</template>
