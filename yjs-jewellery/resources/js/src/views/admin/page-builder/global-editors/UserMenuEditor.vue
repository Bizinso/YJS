<script setup>
import { computed } from 'vue'

const props = defineProps({ block: { type: Object, required: true } })
const emit = defineEmits(['update:data', 'update:settings'])

const data = computed(() => props.block.data || {})
const settings = computed(() => props.block.settings || {})

const updateData = (key, value) => emit('update:data', { [key]: value })
const updateSettings = (key, value) => emit('update:settings', { [key]: value })

const updateGuestItem = (index, key, value) => {
    const items = [...(data.value.guest_items || [])]
    items[index][key] = value
    updateData('guest_items', items)
}

const addGuestItem = () => {
    const items = [...(data.value.guest_items || [])]
    items.push({ label: 'New Link', url: '#' })
    updateData('guest_items', items)
}

const removeGuestItem = (index) => {
    const items = [...(data.value.guest_items || [])]
    items.splice(index, 1)
    updateData('guest_items', items)
}

const updateLoggedInItem = (index, key, value) => {
    const items = [...(data.value.logged_in_items || [])]
    items[index][key] = value
    updateData('logged_in_items', items)
}

const addLoggedInItem = () => {
    const items = [...(data.value.logged_in_items || [])]
    items.push({ label: 'New Link', url: '#' })
    updateData('logged_in_items', items)
}

const removeLoggedInItem = (index) => {
    const items = [...(data.value.logged_in_items || [])]
    items.splice(index, 1)
    updateData('logged_in_items', items)
}
</script>

<template>
    <div class="space-y-4">
        <div class="space-y-3">
            <label class="flex items-center">
                <input type="checkbox" :checked="settings.show_avatar !== false" @change="updateSettings('show_avatar', $event.target.checked)" class="rounded border-gray-300 text-amber-600">
                <span class="ml-2 text-sm">Show Avatar</span>
            </label>
            <label class="flex items-center">
                <input type="checkbox" :checked="settings.show_name !== false" @change="updateSettings('show_name', $event.target.checked)" class="rounded border-gray-300 text-amber-600">
                <span class="ml-2 text-sm">Show Name/Label</span>
            </label>
        </div>

        <!-- Guest Menu Items -->
        <div class="pt-4 border-t">
            <div class="flex justify-between items-center mb-2">
                <label class="text-sm font-medium text-gray-700">Guest Menu Items</label>
                <button @click="addGuestItem" class="text-xs text-amber-600">+ Add</button>
            </div>
            <div v-for="(item, i) in (data.guest_items || [])" :key="'g'+i" class="mb-2 p-2 bg-gray-50 rounded">
                <div class="flex justify-between mb-1">
                    <span class="text-xs text-gray-400">{{ i + 1 }}</span>
                    <button @click="removeGuestItem(i)" class="text-red-400 text-xs">x</button>
                </div>
                <input type="text" :value="item.label" @input="updateGuestItem(i, 'label', $event.target.value)" placeholder="Label" class="w-full px-2 py-1 border rounded text-sm mb-1">
                <input type="text" :value="item.url" @input="updateGuestItem(i, 'url', $event.target.value)" placeholder="URL" class="w-full px-2 py-1 border rounded text-sm">
            </div>
        </div>

        <!-- Logged In Menu Items -->
        <div class="pt-4 border-t">
            <div class="flex justify-between items-center mb-2">
                <label class="text-sm font-medium text-gray-700">Logged-in Menu Items</label>
                <button @click="addLoggedInItem" class="text-xs text-amber-600">+ Add</button>
            </div>
            <div v-for="(item, i) in (data.logged_in_items || [])" :key="'l'+i" class="mb-2 p-2 bg-gray-50 rounded">
                <div class="flex justify-between mb-1">
                    <span class="text-xs text-gray-400">{{ i + 1 }}</span>
                    <button @click="removeLoggedInItem(i)" class="text-red-400 text-xs">x</button>
                </div>
                <input type="text" :value="item.label" @input="updateLoggedInItem(i, 'label', $event.target.value)" placeholder="Label" class="w-full px-2 py-1 border rounded text-sm mb-1">
                <input type="text" :value="item.url" @input="updateLoggedInItem(i, 'url', $event.target.value)" placeholder="URL" class="w-full px-2 py-1 border rounded text-sm">
            </div>
        </div>
    </div>
</template>
