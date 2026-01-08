<script setup>
import { computed } from 'vue'

const props = defineProps({ block: { type: Object, required: true } })
const emit = defineEmits(['update:data', 'update:settings'])

const data = computed(() => props.block.data || {})
const settings = computed(() => props.block.settings || {})

const updateData = (key, value) => emit('update:data', { [key]: value })
const updateSettings = (key, value) => emit('update:settings', { [key]: value })

const addItem = () => {
    const items = [...(data.value.items || [])]
    items.push({ question: '', answer: '' })
    updateData('items', items)
}

const updateItem = (index, key, value) => {
    const items = [...(data.value.items || [])]
    items[index][key] = value
    updateData('items', items)
}

const removeItem = (index) => {
    const items = [...(data.value.items || [])]
    items.splice(index, 1)
    updateData('items', items)
}
</script>

<template>
    <div class="space-y-4">
        <div><label class="block text-sm font-medium text-gray-700 mb-1">Section Title</label><input type="text" :value="data.title" @input="updateData('title', $event.target.value)" class="w-full px-3 py-2 border rounded-lg text-sm"></div>

        <div class="pt-4 border-t">
            <div class="flex justify-between items-center mb-3"><label class="text-sm font-medium text-gray-700">FAQ Items</label><button @click="addItem" class="text-sm text-amber-600">+ Add Question</button></div>
            <div v-for="(item, i) in data.items" :key="i" class="mb-4 p-3 bg-gray-50 rounded-lg">
                <div class="flex justify-between mb-2"><span class="text-xs text-gray-500">Q{{ i + 1 }}</span><button @click="removeItem(i)" class="text-red-500 text-xs">Remove</button></div>
                <input type="text" :value="item.question" @input="updateItem(i, 'question', $event.target.value)" placeholder="Question" class="w-full px-2 py-1 border rounded text-sm mb-2">
                <textarea :value="item.answer" @input="updateItem(i, 'answer', $event.target.value)" placeholder="Answer" rows="2" class="w-full px-2 py-1 border rounded text-sm"></textarea>
            </div>
        </div>

        <div class="pt-4 border-t space-y-2">
            <label class="flex items-center"><input type="checkbox" :checked="settings.allow_multiple_open" @change="updateSettings('allow_multiple_open', $event.target.checked)" class="rounded border-gray-300 text-amber-600"><span class="ml-2 text-sm">Allow Multiple Open</span></label>
            <label class="flex items-center"><input type="checkbox" :checked="settings.first_open" @change="updateSettings('first_open', $event.target.checked)" class="rounded border-gray-300 text-amber-600"><span class="ml-2 text-sm">First Item Open by Default</span></label>
        </div>
    </div>
</template>
