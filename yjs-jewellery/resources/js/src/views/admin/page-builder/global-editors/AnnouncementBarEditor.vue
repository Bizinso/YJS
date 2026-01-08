<script setup>
import { computed } from 'vue'

const props = defineProps({ block: { type: Object, required: true } })
const emit = defineEmits(['update:data', 'update:settings'])

const data = computed(() => props.block.data || {})
const settings = computed(() => props.block.settings || {})

const updateData = (key, value) => emit('update:data', { [key]: value })
const updateSettings = (key, value) => emit('update:settings', { [key]: value })

const addMessage = () => {
    const messages = [...(data.value.messages || [])]
    messages.push({ text: 'New announcement' })
    updateData('messages', messages)
}

const updateMessage = (index, value) => {
    const messages = [...(data.value.messages || [])]
    messages[index].text = value
    updateData('messages', messages)
}

const removeMessage = (index) => {
    const messages = [...(data.value.messages || [])]
    messages.splice(index, 1)
    updateData('messages', messages)
}
</script>

<template>
    <div class="space-y-4">
        <div>
            <div class="flex justify-between items-center mb-2">
                <label class="text-sm font-medium text-gray-700">Messages</label>
                <button @click="addMessage" class="text-xs text-amber-600">+ Add Message</button>
            </div>
            <div v-for="(msg, i) in (data.messages || [])" :key="i" class="mb-2 flex gap-2">
                <input type="text" :value="msg.text" @input="updateMessage(i, $event.target.value)" class="flex-1 px-3 py-2 border rounded-lg text-sm">
                <button @click="removeMessage(i)" class="px-2 text-red-400 hover:text-red-600">
                    <i class="bi bi-trash"></i>
                </button>
            </div>
            <p class="text-xs text-gray-500">Add multiple messages to rotate between them</p>
        </div>

        <div class="pt-4 border-t">
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Background</label>
                    <input type="color" :value="settings.background || '#1a1a1a'" @input="updateSettings('background', $event.target.value)" class="w-full h-10 rounded border">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Text Color</label>
                    <input type="color" :value="settings.text_color || '#ffffff'" @input="updateSettings('text_color', $event.target.value)" class="w-full h-10 rounded border">
                </div>
            </div>
        </div>

        <div class="pt-4 border-t space-y-3">
            <label class="flex items-center">
                <input type="checkbox" :checked="settings.rotate" @change="updateSettings('rotate', $event.target.checked)" class="rounded border-gray-300 text-amber-600">
                <span class="ml-2 text-sm">Rotate Messages</span>
            </label>
            <div v-if="settings.rotate">
                <label class="block text-sm font-medium text-gray-700 mb-1">Rotation Interval (ms)</label>
                <input type="number" :value="settings.rotate_interval || 5000" @input="updateSettings('rotate_interval', parseInt($event.target.value))" class="w-full px-3 py-2 border rounded-lg text-sm">
            </div>
        </div>
    </div>
</template>
