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
            <label class="block text-sm font-medium text-gray-700 mb-1">Phone Number</label>
            <input type="text" :value="data.phone" @input="updateData('phone', $event.target.value)" placeholder="+91 12345 67890" class="w-full px-3 py-2 border rounded-lg text-sm">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
            <input type="email" :value="data.email" @input="updateData('email', $event.target.value)" placeholder="info@company.com" class="w-full px-3 py-2 border rounded-lg text-sm">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Address</label>
            <textarea :value="data.address" @input="updateData('address', $event.target.value)" rows="2" placeholder="123 Main Street, City" class="w-full px-3 py-2 border rounded-lg text-sm"></textarea>
        </div>

        <div class="pt-4 border-t">
            <label class="flex items-center">
                <input type="checkbox" :checked="settings.show_icons !== false" @change="updateSettings('show_icons', $event.target.checked)" class="rounded border-gray-300 text-amber-600">
                <span class="ml-2 text-sm">Show Icons</span>
            </label>
        </div>
    </div>
</template>
