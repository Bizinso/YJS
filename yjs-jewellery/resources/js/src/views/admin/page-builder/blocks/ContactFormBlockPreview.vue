<script setup>
const props = defineProps({
    block: { type: Object, required: true }
})

const data = props.block.data || {}
const settings = props.block.settings || {}

const defaultFields = [
    { type: 'text', name: 'name', label: 'Your Name', required: true },
    { type: 'email', name: 'email', label: 'Email Address', required: true },
    { type: 'textarea', name: 'message', label: 'Message', required: true }
]

const fields = data.fields?.length ? data.fields : defaultFields
</script>

<template>
    <div class="max-w-lg mx-auto">
        <!-- Header -->
        <div v-if="data.title" class="text-center mb-6">
            <h2 class="text-2xl font-bold text-gray-800">{{ data.title }}</h2>
            <p v-if="data.subtitle" class="text-gray-600 mt-2">{{ data.subtitle }}</p>
        </div>

        <!-- Form -->
        <form class="space-y-4" @submit.prevent>
            <div v-for="(field, index) in fields" :key="index">
                <label
                    v-if="settings.show_labels !== false"
                    class="block text-sm font-medium text-gray-700 mb-1"
                >
                    {{ field.label }}
                    <span v-if="field.required" class="text-red-500">*</span>
                </label>

                <textarea
                    v-if="field.type === 'textarea'"
                    :placeholder="field.placeholder || field.label"
                    rows="4"
                    class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500"
                    disabled
                ></textarea>

                <input
                    v-else
                    :type="field.type || 'text'"
                    :placeholder="field.placeholder || field.label"
                    class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500"
                    disabled
                >
            </div>

            <button
                type="submit"
                class="w-full px-6 py-3 bg-amber-600 text-white rounded-lg font-medium hover:bg-amber-700"
                disabled
            >
                {{ data.submit_text || 'Send Message' }}
            </button>
        </form>

        <p class="text-xs text-gray-400 text-center mt-4">
            Form preview - submissions disabled in builder
        </p>
    </div>
</template>
