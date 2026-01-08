<script setup>
defineProps({ block: { type: Object, required: true } })
</script>

<template>
    <div class="p-6 bg-gray-100">
        <div class="max-w-md mx-auto text-center">
            <h4 class="font-semibold text-gray-800 mb-2">
                {{ block.data?.title || 'Subscribe to Newsletter' }}
            </h4>
            <p class="text-sm text-gray-600 mb-4">
                {{ block.data?.description || 'Get updates on new arrivals and offers' }}
            </p>
            <div :class="block.settings?.style === 'stacked' ? 'space-y-2' : 'flex gap-2'">
                <input
                    type="email"
                    placeholder="Enter your email"
                    class="flex-1 px-4 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:border-amber-500"
                >
                <button class="px-6 py-2 bg-amber-600 text-white rounded-lg text-sm font-medium hover:bg-amber-700">
                    {{ block.data?.button_text || 'Subscribe' }}
                </button>
            </div>
        </div>
    </div>
</template>
