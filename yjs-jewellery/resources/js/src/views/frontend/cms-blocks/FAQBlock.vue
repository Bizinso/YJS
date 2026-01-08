<script setup>
import { ref, computed } from 'vue'
const props = defineProps({ block: { type: Object, required: true } })

const data = computed(() => props.block.data || {})
const settings = computed(() => props.block.settings || {})

const faqs = computed(() => data.value.faqs || [])
const allowMultiple = computed(() => settings.value.allow_multiple || false)

const openItems = ref([])

const toggleItem = (index) => {
    if (allowMultiple.value) {
        const idx = openItems.value.indexOf(index)
        if (idx > -1) {
            openItems.value.splice(idx, 1)
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
    <section class="faq-block py-16">
        <div class="container mx-auto px-4">
            <div class="max-w-3xl mx-auto">
                <h2 v-if="data.title" class="text-3xl font-bold text-center mb-2">{{ data.title }}</h2>
                <p v-if="data.subtitle" class="text-gray-600 text-center mb-8">{{ data.subtitle }}</p>

                <div class="space-y-4">
                    <div
                        v-for="(faq, index) in faqs"
                        :key="index"
                        class="bg-white rounded-lg shadow-sm overflow-hidden"
                    >
                        <button
                            @click="toggleItem(index)"
                            class="w-full px-6 py-4 text-left flex items-center justify-between hover:bg-gray-50 transition-colors"
                        >
                            <span class="font-semibold text-gray-800 pr-4">{{ faq.question }}</span>
                            <i
                                :class="[
                                    'bi transition-transform duration-300 text-amber-600 flex-shrink-0',
                                    isOpen(index) ? 'bi-chevron-up' : 'bi-chevron-down'
                                ]"
                            ></i>
                        </button>
                        <div
                            v-show="isOpen(index)"
                            class="px-6 pb-4"
                        >
                            <div class="border-t border-gray-100 pt-4 text-gray-600 prose prose-sm max-w-none" v-html="faq.answer"></div>
                        </div>
                    </div>
                </div>

                <!-- Schema.org FAQ structured data -->
                <script type="application/ld+json" v-if="faqs.length">
                    {{ JSON.stringify({
                        "@context": "https://schema.org",
                        "@type": "FAQPage",
                        "mainEntity": faqs.map(faq => ({
                            "@type": "Question",
                            "name": faq.question,
                            "acceptedAnswer": {
                                "@type": "Answer",
                                "text": faq.answer.replace(/<[^>]*>/g, '')
                            }
                        }))
                    }) }}
                </script>
            </div>
        </div>
    </section>
</template>
