<script setup>
import { computed } from 'vue'

const props = defineProps({ block: { type: Object, required: true } })
const emit = defineEmits(['update:data', 'update:settings'])

const data = computed(() => props.block.data || {})
const settings = computed(() => props.block.settings || {})

const updateData = (key, value) => emit('update:data', { [key]: value })
const updateSettings = (key, value) => emit('update:settings', { [key]: value })

const fieldTypes = ['text', 'email', 'phone', 'textarea', 'select']

const addField = () => {
    const fields = [...(data.value.fields || [])]
    fields.push({ type: 'text', name: 'field_' + Date.now(), label: 'New Field', required: false })
    updateData('fields', fields)
}

const updateField = (index, key, value) => {
    const fields = [...(data.value.fields || [])]
    fields[index][key] = value
    if (key === 'label' && !fields[index].name.startsWith('field_')) {
        fields[index].name = value.toLowerCase().replace(/[^a-z0-9]+/g, '_')
    }
    updateData('fields', fields)
}

const removeField = (index) => {
    const fields = [...(data.value.fields || [])]
    fields.splice(index, 1)
    updateData('fields', fields)
}
</script>

<template>
    <div class="space-y-4">
        <div><label class="block text-sm font-medium text-gray-700 mb-1">Form Name</label><input type="text" :value="data.form_name" @input="updateData('form_name', $event.target.value)" class="w-full px-3 py-2 border rounded-lg text-sm"></div>
        <div><label class="block text-sm font-medium text-gray-700 mb-1">Title</label><input type="text" :value="data.title" @input="updateData('title', $event.target.value)" class="w-full px-3 py-2 border rounded-lg text-sm"></div>
        <div><label class="block text-sm font-medium text-gray-700 mb-1">Submit Button Text</label><input type="text" :value="data.submit_text" @input="updateData('submit_text', $event.target.value)" class="w-full px-3 py-2 border rounded-lg text-sm"></div>

        <div class="pt-4 border-t">
            <div class="flex justify-between items-center mb-3"><label class="text-sm font-medium text-gray-700">Form Fields</label><button @click="addField" class="text-sm text-amber-600">+ Add Field</button></div>
            <div v-for="(f, i) in data.fields" :key="i" class="mb-3 p-3 bg-gray-50 rounded-lg">
                <div class="flex justify-between mb-2"><span class="text-xs text-gray-500">{{ f.name }}</span><button @click="removeField(i)" class="text-red-500 text-xs">Remove</button></div>
                <div class="grid grid-cols-2 gap-2 mb-2">
                    <input type="text" :value="f.label" @input="updateField(i, 'label', $event.target.value)" placeholder="Label" class="px-2 py-1 border rounded text-sm">
                    <select :value="f.type" @change="updateField(i, 'type', $event.target.value)" class="px-2 py-1 border rounded text-sm"><option v-for="t in fieldTypes" :key="t" :value="t">{{ t }}</option></select>
                </div>
                <label class="flex items-center"><input type="checkbox" :checked="f.required" @change="updateField(i, 'required', $event.target.checked)" class="rounded border-gray-300 text-amber-600"><span class="ml-2 text-xs">Required</span></label>
            </div>
        </div>

        <div class="pt-4 border-t space-y-4">
            <div><label class="block text-sm font-medium text-gray-700 mb-1">Notification Email</label><input type="email" :value="settings.notify_email" @input="updateSettings('notify_email', $event.target.value)" placeholder="admin@example.com" class="w-full px-3 py-2 border rounded-lg text-sm"></div>
            <div><label class="block text-sm font-medium text-gray-700 mb-1">Success Message</label><input type="text" :value="settings.success_message" @input="updateSettings('success_message', $event.target.value)" class="w-full px-3 py-2 border rounded-lg text-sm"></div>
        </div>
    </div>
</template>
