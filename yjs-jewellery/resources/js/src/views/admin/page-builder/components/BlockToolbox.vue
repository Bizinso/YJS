<script setup>
import { ref, computed } from 'vue'
import { usePageBuilder } from '@/composables/usePageBuilder'

const { blockConfig, addBlock } = usePageBuilder()

const searchQuery = ref('')
const expandedCategories = ref(['layout', 'content', 'ecommerce'])

// Computed
const categories = computed(() => {
    if (!blockConfig.value) return []

    return Object.entries(blockConfig.value.categories)
        .map(([key, value]) => ({
            id: key,
            ...value,
            blocks: getBlocksForCategory(key)
        }))
        .filter(cat => cat.blocks.length > 0)
        .sort((a, b) => a.order - b.order)
})

const filteredBlocks = computed(() => {
    if (!blockConfig.value) return {}

    const query = searchQuery.value.toLowerCase().trim()
    if (!query) return blockConfig.value.blocks

    const filtered = {}
    Object.entries(blockConfig.value.blocks).forEach(([key, block]) => {
        if (
            block.name.toLowerCase().includes(query) ||
            block.description.toLowerCase().includes(query)
        ) {
            filtered[key] = block
        }
    })
    return filtered
})

// Methods
const getBlocksForCategory = (categoryId) => {
    if (!blockConfig.value) return []

    const query = searchQuery.value.toLowerCase().trim()

    return Object.entries(blockConfig.value.blocks)
        .filter(([key, block]) => {
            if (block.category !== categoryId) return false
            if (query) {
                return block.name.toLowerCase().includes(query) ||
                    block.description.toLowerCase().includes(query)
            }
            return true
        })
        .map(([key, block]) => ({
            type: key,
            ...block
        }))
}

const toggleCategory = (categoryId) => {
    const index = expandedCategories.value.indexOf(categoryId)
    if (index > -1) {
        expandedCategories.value.splice(index, 1)
    } else {
        expandedCategories.value.push(categoryId)
    }
}

const isCategoryExpanded = (categoryId) => {
    return expandedCategories.value.includes(categoryId)
}

const handleDragStart = (event, blockType) => {
    event.dataTransfer.effectAllowed = 'copy'
    event.dataTransfer.setData('blockType', blockType)

    // Create a ghost image
    const ghost = event.target.cloneNode(true)
    ghost.style.opacity = '0.7'
    ghost.style.position = 'absolute'
    ghost.style.top = '-1000px'
    document.body.appendChild(ghost)
    event.dataTransfer.setDragImage(ghost, 0, 0)

    setTimeout(() => {
        document.body.removeChild(ghost)
    }, 0)
}

const handleClick = (blockType) => {
    addBlock(blockType)
}
</script>

<template>
    <div class="h-full flex flex-col">
        <!-- Header -->
        <div class="p-4 border-b">
            <h2 class="font-semibold text-gray-800 mb-3">Blocks</h2>
            <div class="relative">
                <input
                    v-model="searchQuery"
                    type="text"
                    placeholder="Search blocks..."
                    class="w-full pl-8 pr-3 py-2 text-sm border rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500"
                >
                <i class="bi bi-search absolute left-2.5 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
            </div>
        </div>

        <!-- Block Categories -->
        <div class="flex-1 overflow-y-auto">
            <div v-for="category in categories" :key="category.id" class="border-b">
                <!-- Category Header -->
                <button
                    @click="toggleCategory(category.id)"
                    class="w-full px-4 py-3 flex items-center justify-between hover:bg-gray-50 text-left"
                >
                    <div class="flex items-center space-x-2">
                        <i :class="['bi', category.icon, 'text-gray-400']"></i>
                        <span class="font-medium text-gray-700">{{ category.label }}</span>
                        <span class="text-xs text-gray-400">({{ category.blocks.length }})</span>
                    </div>
                    <i :class="['bi', isCategoryExpanded(category.id) ? 'bi-chevron-up' : 'bi-chevron-down', 'text-gray-400 text-xs']"></i>
                </button>

                <!-- Blocks Grid -->
                <div v-show="isCategoryExpanded(category.id)" class="px-3 pb-3">
                    <div class="grid grid-cols-2 gap-2">
                        <div
                            v-for="block in category.blocks"
                            :key="block.type"
                            draggable="true"
                            @dragstart="handleDragStart($event, block.type)"
                            @click="handleClick(block.type)"
                            class="p-3 bg-gray-50 hover:bg-amber-50 border border-transparent hover:border-amber-200 rounded-lg cursor-move transition-colors group"
                            :title="block.description"
                        >
                            <div class="text-center">
                                <i :class="['bi', block.icon, 'text-xl text-gray-400 group-hover:text-amber-600']"></i>
                                <p class="mt-1 text-xs font-medium text-gray-600 group-hover:text-amber-700 truncate">
                                    {{ block.name }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Help Text -->
        <div class="p-4 border-t bg-gray-50">
            <p class="text-xs text-gray-500 text-center">
                <i class="bi bi-lightbulb me-1"></i>
                Drag blocks to canvas or click to add
            </p>
        </div>
    </div>
</template>
