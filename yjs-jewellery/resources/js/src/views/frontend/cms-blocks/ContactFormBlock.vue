<script setup>
import { ref, computed } from 'vue'
import axios from 'axios'
const props = defineProps({ block: { type: Object, required: true } })

const data = computed(() => props.block.data || {})
const settings = computed(() => props.block.settings || {})

const formData = ref({})
const loading = ref(false)
const submitted = ref(false)
const error = ref('')

const fields = computed(() => data.value.fields || [
    { name: 'name', label: 'Name', type: 'text', required: true },
    { name: 'email', label: 'Email', type: 'email', required: true },
    { name: 'message', label: 'Message', type: 'textarea', required: true }
])

const initFormData = () => {
    fields.value.forEach(field => {
        formData.value[field.name] = ''
    })
}
initFormData()

const submitForm = async () => {
    error.value = ''
    loading.value = true

    try {
        await axios.post('/api/form-submissions', {
            page_id: props.block.page_id,
            block_id: props.block.id,
            form_data: formData.value
        })
        submitted.value = true
    } catch (err) {
        error.value = err.response?.data?.message || 'Failed to submit form. Please try again.'
    } finally {
        loading.value = false
    }
}

const resetForm = () => {
    submitted.value = false
    initFormData()
}
</script>

<template>
    <section class="contact-form-block py-16 bg-gray-50">
        <div class="container mx-auto px-4">
            <div class="max-w-2xl mx-auto">
                <h2 v-if="data.title" class="text-3xl font-bold text-center mb-2">{{ data.title }}</h2>
                <p v-if="data.subtitle" class="text-gray-600 text-center mb-8">{{ data.subtitle }}</p>

                <!-- Success Message -->
                <div v-if="submitted" class="bg-green-50 border border-green-200 rounded-lg p-8 text-center">
                    <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="bi bi-check-lg text-green-600 text-3xl"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-green-800 mb-2">
                        {{ data.success_title || 'Thank You!' }}
                    </h3>
                    <p class="text-green-600 mb-4">
                        {{ data.success_message || 'Your message has been sent successfully. We will get back to you soon.' }}
                    </p>
                    <button
                        @click="resetForm"
                        class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors"
                    >
                        Send Another Message
                    </button>
                </div>

                <!-- Form -->
                <form v-else @submit.prevent="submitForm" class="bg-white rounded-xl p-8 shadow-sm">
                    <div v-if="error" class="bg-red-50 border border-red-200 text-red-600 rounded-lg p-4 mb-6">
                        {{ error }}
                    </div>

                    <div class="space-y-6">
                        <div v-for="field in fields" :key="field.name">
                            <label :for="field.name" class="block text-sm font-medium text-gray-700 mb-1">
                                {{ field.label }}
                                <span v-if="field.required" class="text-red-500">*</span>
                            </label>

                            <!-- Text Input -->
                            <input
                                v-if="['text', 'email', 'tel', 'number'].includes(field.type)"
                                :id="field.name"
                                v-model="formData[field.name]"
                                :type="field.type"
                                :placeholder="field.placeholder"
                                :required="field.required"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition-colors"
                            >

                            <!-- Textarea -->
                            <textarea
                                v-else-if="field.type === 'textarea'"
                                :id="field.name"
                                v-model="formData[field.name]"
                                :placeholder="field.placeholder"
                                :required="field.required"
                                rows="4"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition-colors resize-none"
                            ></textarea>

                            <!-- Select -->
                            <select
                                v-else-if="field.type === 'select'"
                                :id="field.name"
                                v-model="formData[field.name]"
                                :required="field.required"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition-colors"
                            >
                                <option value="">{{ field.placeholder || 'Select an option' }}</option>
                                <option v-for="opt in field.options" :key="opt" :value="opt">{{ opt }}</option>
                            </select>

                            <!-- Checkbox -->
                            <div v-else-if="field.type === 'checkbox'" class="flex items-center">
                                <input
                                    :id="field.name"
                                    v-model="formData[field.name]"
                                    type="checkbox"
                                    :required="field.required"
                                    class="w-4 h-4 text-amber-600 border-gray-300 rounded focus:ring-amber-500"
                                >
                                <label :for="field.name" class="ml-2 text-sm text-gray-600">
                                    {{ field.placeholder || field.label }}
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Honeypot for spam protection -->
                    <input type="text" name="website" style="display: none;" tabindex="-1" autocomplete="off">

                    <button
                        type="submit"
                        :disabled="loading"
                        class="w-full mt-6 px-8 py-3 bg-amber-600 text-white font-semibold rounded-lg hover:bg-amber-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <span v-if="loading" class="flex items-center justify-center gap-2">
                            <div class="w-5 h-5 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
                            Sending...
                        </span>
                        <span v-else>{{ data.button_text || 'Send Message' }}</span>
                    </button>
                </form>
            </div>
        </div>
    </section>
</template>
