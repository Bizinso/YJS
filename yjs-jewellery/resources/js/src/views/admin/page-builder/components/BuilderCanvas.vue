<script setup>
import { ref, computed } from 'vue'
import { usePageBuilder } from '@/composables/usePageBuilder'
import draggable from 'vuedraggable'
import BlockWrapper from './BlockWrapper.vue'

const {
    blocks,
    selectedBlockId,
    addBlock,
    moveBlock,
    selectBlock,
    deselectBlock
} = usePageBuilder()

const isDragOver = ref(false)
const dropIndicatorIndex = ref(-1)

// Computed
const sortedBlocks = computed({
    get: () => [...blocks.value].sort((a, b) => a.order - b.order),
    set: (newBlocks) => {
        // Update blocks order after drag
        newBlocks.forEach((block, index) => {
            const original = blocks.value.find(b => b.id === block.id)
            if (original) {
                original.order = index
            }
        })
    }
})

// Methods
const handleDragOver = (event) => {
    event.preventDefault()
    isDragOver.value = true
}

const handleDragLeave = () => {
    isDragOver.value = false
    dropIndicatorIndex.value = -1
}

const handleDrop = (event) => {
    event.preventDefault()
    isDragOver.value = false

    const blockType = event.dataTransfer.getData('blockType')
    if (blockType) {
        addBlock(blockType)
    }
}

const handleCanvasClick = (event) => {
    // Deselect when clicking on empty canvas area
    if (event.target === event.currentTarget) {
        deselectBlock()
    }
}

const handleDragChange = (event) => {
    if (event.moved) {
        moveBlock(event.moved.oldIndex, event.moved.newIndex)
    }
}
</script>

<template>
    <div
        class="min-h-full p-4"
        @click="handleCanvasClick"
        @dragover="handleDragOver"
        @dragleave="handleDragLeave"
        @drop="handleDrop"
    >
        <!-- Empty State -->
        <div
            v-if="blocks.length === 0"
            :class="[
                'flex flex-col items-center justify-center py-20 border-2 border-dashed rounded-lg transition-colors',
                isDragOver ? 'border-amber-400 bg-amber-50' : 'border-gray-300'
            ]"
        >
            <i class="bi bi-plus-square-dotted text-5xl text-gray-300 mb-4"></i>
            <p class="text-gray-500 mb-2">Drag blocks here to start building</p>
            <p class="text-sm text-gray-400">Or click a block from the left panel</p>
        </div>

        <!-- Blocks List -->
        <draggable
            v-else
            v-model="sortedBlocks"
            item-key="id"
            handle=".drag-handle"
            ghost-class="ghost-block"
            animation="200"
            @change="handleDragChange"
            class="space-y-4"
        >
            <template #item="{ element: block, index }">
                <BlockWrapper
                    :block="block"
                    :index="index"
                    :is-selected="selectedBlockId === block.id"
                    @select="selectBlock(block.id)"
                />
            </template>
        </draggable>

        <!-- Drop Zone Indicator (when dragging over canvas with blocks) -->
        <div
            v-if="blocks.length > 0 && isDragOver"
            class="mt-4 py-8 border-2 border-dashed border-amber-400 bg-amber-50 rounded-lg text-center"
        >
            <i class="bi bi-plus-lg text-amber-500"></i>
            <p class="text-sm text-amber-600 mt-1">Drop to add block at the end</p>
        </div>
    </div>
</template>

<style scoped>
.ghost-block {
    opacity: 0.5;
    background: #fef3c7;
    border: 2px dashed #f59e0b;
}
</style>
