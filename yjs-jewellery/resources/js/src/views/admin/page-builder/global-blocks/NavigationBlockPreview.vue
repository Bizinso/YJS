<script setup>
import { computed } from 'vue'
defineProps({ block: { type: Object, required: true } })
</script>

<template>
    <nav class="p-4">
        <ul :class="block.settings?.style === 'vertical' ? 'space-y-2' : 'flex items-center gap-6'">
            <li
                v-for="(item, i) in (block.data?.items || [])"
                :key="i"
                class="relative group/nav"
            >
                <a
                    href="#"
                    class="text-gray-700 hover:text-amber-600 font-medium text-sm transition-colors flex items-center gap-1"
                >
                    {{ item.label }}
                    <i v-if="item.children?.length" class="bi bi-chevron-down text-xs"></i>
                </a>
                <!-- Dropdown preview -->
                <div
                    v-if="item.children?.length"
                    class="hidden group-hover/nav:block absolute top-full left-0 mt-1 bg-white border rounded-lg shadow-lg py-2 min-w-40 z-10"
                >
                    <a
                        v-for="(child, j) in item.children"
                        :key="j"
                        href="#"
                        class="block px-4 py-2 text-sm text-gray-600 hover:bg-gray-50 hover:text-amber-600"
                    >
                        {{ child.label }}
                    </a>
                </div>
            </li>
        </ul>
    </nav>
</template>
