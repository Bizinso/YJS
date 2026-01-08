<script setup>
defineProps({ block: { type: Object, required: true } })
</script>

<template>
    <div class="p-4">
        <div v-if="block.settings?.style === 'icon'" class="cursor-pointer">
            <i class="bi bi-search text-xl text-gray-600 hover:text-amber-600"></i>
        </div>
        <div v-else class="relative">
            <input
                type="text"
                :placeholder="block.data?.placeholder || 'Search...'"
                class="w-full md:w-64 px-4 py-2 pl-10 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-amber-500"
            >
            <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
        </div>
    </div>
</template>
