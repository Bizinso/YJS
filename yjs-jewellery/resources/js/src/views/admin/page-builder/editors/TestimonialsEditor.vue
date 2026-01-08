<script setup>
import { computed, ref } from 'vue'

const props = defineProps({ block: { type: Object, required: true } })
const emit = defineEmits(['update:data', 'update:settings'])

const data = computed(() => props.block.data || {})
const settings = computed(() => props.block.settings || {})

const updateData = (key, value) => emit('update:data', { [key]: value })
const updateSettings = (key, value) => emit('update:settings', { [key]: value })

const addTestimonial = () => {
    const testimonials = [...(data.value.testimonials || [])]
    testimonials.push({ name: '', title: '', content: '', rating: 5 })
    updateData('testimonials', testimonials)
}

const updateTestimonial = (index, key, value) => {
    const testimonials = [...(data.value.testimonials || [])]
    testimonials[index][key] = value
    updateData('testimonials', testimonials)
}

const removeTestimonial = (index) => {
    const testimonials = [...(data.value.testimonials || [])]
    testimonials.splice(index, 1)
    updateData('testimonials', testimonials)
}
</script>

<template>
    <div class="space-y-4">
        <div><label class="block text-sm font-medium text-gray-700 mb-1">Section Title</label><input type="text" :value="data.title" @input="updateData('title', $event.target.value)" class="w-full px-3 py-2 border rounded-lg text-sm"></div>
        <div><label class="block text-sm font-medium text-gray-700 mb-1">Subtitle</label><input type="text" :value="data.subtitle" @input="updateData('subtitle', $event.target.value)" class="w-full px-3 py-2 border rounded-lg text-sm"></div>

        <div class="pt-4 border-t">
            <div class="flex justify-between items-center mb-3"><label class="text-sm font-medium text-gray-700">Testimonials</label><button @click="addTestimonial" class="text-sm text-amber-600 hover:text-amber-700">+ Add</button></div>
            <div v-for="(t, i) in data.testimonials" :key="i" class="mb-4 p-3 bg-gray-50 rounded-lg">
                <div class="flex justify-between mb-2"><span class="text-xs text-gray-500">#{{ i + 1 }}</span><button @click="removeTestimonial(i)" class="text-red-500 text-xs">Remove</button></div>
                <input type="text" :value="t.name" @input="updateTestimonial(i, 'name', $event.target.value)" placeholder="Name" class="w-full px-2 py-1 border rounded text-sm mb-2">
                <input type="text" :value="t.title" @input="updateTestimonial(i, 'title', $event.target.value)" placeholder="Title" class="w-full px-2 py-1 border rounded text-sm mb-2">
                <textarea :value="t.content" @input="updateTestimonial(i, 'content', $event.target.value)" placeholder="Content" rows="2" class="w-full px-2 py-1 border rounded text-sm mb-2"></textarea>
                <select :value="t.rating" @change="updateTestimonial(i, 'rating', parseInt($event.target.value))" class="w-full px-2 py-1 border rounded text-sm"><option v-for="r in 5" :key="r" :value="r">{{ r }} Star{{ r > 1 ? 's' : '' }}</option></select>
            </div>
        </div>

        <div class="pt-4 border-t space-y-2">
            <label class="flex items-center"><input type="checkbox" :checked="settings.show_rating !== false" @change="updateSettings('show_rating', $event.target.checked)" class="rounded border-gray-300 text-amber-600"><span class="ml-2 text-sm">Show Rating</span></label>
            <label class="flex items-center"><input type="checkbox" :checked="settings.show_avatar !== false" @change="updateSettings('show_avatar', $event.target.checked)" class="rounded border-gray-300 text-amber-600"><span class="ml-2 text-sm">Show Avatar</span></label>
        </div>
    </div>
</template>
