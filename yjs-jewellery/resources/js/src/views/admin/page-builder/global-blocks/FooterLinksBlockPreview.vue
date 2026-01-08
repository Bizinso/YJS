<script setup>
defineProps({ block: { type: Object, required: true } })
</script>

<template>
    <div class="p-6 bg-gray-50">
        <div v-if="block.data?.columns?.length" class="grid gap-8" :style="{ gridTemplateColumns: `repeat(${block.settings?.columns_count || 3}, minmax(0, 1fr))` }">
            <div v-for="(col, i) in block.data.columns" :key="i">
                <h4 class="font-semibold text-gray-800 mb-3">{{ col.title || 'Column Title' }}</h4>
                <ul class="space-y-2">
                    <li v-for="(link, j) in (col.links || [])" :key="j">
                        <a href="#" class="text-gray-600 hover:text-amber-600 text-sm">{{ link.label }}</a>
                    </li>
                </ul>
            </div>
        </div>
        <div v-else class="text-center text-gray-400 py-4">
            <p>Add link columns in the editor</p>
        </div>
    </div>
</template>
