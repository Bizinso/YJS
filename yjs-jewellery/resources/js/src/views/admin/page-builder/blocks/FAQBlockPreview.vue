<script setup>
import { ref } from 'vue'

const props = defineProps({
    block: { type: Object, required: true }
})

const data = props.block.data || {}
const settings = props.block.settings || {}

const openItems = ref(settings.first_open ? [0] : [])

const mockItems = [
    { question: 'What is your return policy?', answer: 'We offer a 30-day return policy on all items.' },
    { question: 'How long does shipping take?', answer: 'Standard shipping takes 5-7 business days.' },
    { question: 'Do you offer gift wrapping?', answer: 'Yes, complimentary gift wrapping is available at checkout.' }
]

const items = data.items?.length ? data.items : mockItems

const toggleItem = (index) => {
    if (settings.allow_multiple_open) {
        const i = openItems.value.indexOf(index)
        if (i > -1) {
            openItems.value.splice(i, 1)
        } else {
            openItems.value.push(index)
        }
    } else {
        openItems.value = openItems.value.includes(index) ? [] : [index]
    }
}

const isOpen = (index) => openItems.value.includes(index)
</script>

<template>
    <div class="max-w-2xl mx-auto">
        <!-- Header -->
        <div v-if="data.title" class="text-center mb-6">
            <h2 class="text-2xl font-bold text-gray-800">{{ data.title }}</h2>
        </div>

        <!-- FAQ Items -->
        <div class="space-y-3">
            <div
                v-for="(item, index) in items"
                :key="index"
                class="border rounded-lg overflow-hidden"
            >
                <button
                    @click="toggleItem(index)"
                    class="w-full px-4 py-3 flex items-center justify-between text-left hover:bg-gray-50"
                >
                    <span class="font-medium text-gray-800">{{ item.question }}</span>
                    <i :class="['bi transition-transform', isOpen(index) ? 'bi-chevron-up' : 'bi-chevron-down']"></i>
                </button>

                <div
                    v-show="isOpen(index)"
                    class="px-4 pb-4 text-gray-600"
                >
                    {{ item.answer }}
                </div>
            </div>
        </div>
    </div>
</template>
