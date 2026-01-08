<script setup>
import { ref, onMounted, onUnmounted, computed, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { usePageBuilder } from '@/composables/usePageBuilder'
import BlockToolbox from './components/BlockToolbox.vue'
import BuilderCanvas from './components/BuilderCanvas.vue'
import BlockEditor from './components/BlockEditor.vue'
import SeoPanel from './components/SeoPanel.vue'
import PageSettingsModal from './components/PageSettingsModal.vue'
import VersionHistoryPanel from './components/VersionHistoryPanel.vue'

const route = useRoute()
const router = useRouter()

const {
    page,
    blocks,
    selectedBlock,
    isDirty,
    isSaving,
    isLoading,
    previewMode,
    showSeoPanel,
    canUndo,
    canRedo,
    loadPage,
    loadBlockConfig,
    savePage,
    undo,
    redo,
    setPreviewMode,
    resetState
} = usePageBuilder()

// Local state
const showPageSettings = ref(false)
const showVersionHistory = ref(false)
const autoSaveInterval = ref(null)
const lastSaved = ref(null)

// Preview sizes
const previewSizes = {
    desktop: { width: '100%', label: 'Desktop' },
    tablet: { width: '768px', label: 'Tablet' },
    mobile: { width: '375px', label: 'Mobile' }
}

// Computed
const pageId = computed(() => route.params.id)

const canvasWidth = computed(() => {
    return previewSizes[previewMode.value]?.width || '100%'
})

// Methods
const handleSave = async () => {
    try {
        await savePage()
        lastSaved.value = new Date()
    } catch (error) {
        alert(error.response?.data?.message || 'Failed to save page')
    }
}

const handlePublish = async () => {
    if (!confirm('Publish this page? It will be visible to visitors.')) return

    try {
        page.value.status = 'published'
        await savePage()
        lastSaved.value = new Date()
    } catch (error) {
        alert(error.response?.data?.message || 'Failed to publish page')
    }
}

const handlePreview = () => {
    window.open(`/api/admin/cms/pages/${pageId.value}/preview`, '_blank')
}

const goBack = () => {
    if (isDirty.value) {
        if (!confirm('You have unsaved changes. Leave anyway?')) return
    }
    router.push({ name: 'admin.page-builder' })
}

const formatTime = (date) => {
    if (!date) return ''
    return new Date(date).toLocaleTimeString('en-IN', { hour: '2-digit', minute: '2-digit' })
}

const handleVersionRestore = (restoredPage) => {
    // Reload page data from the restored version
    loadPage(pageId.value)
    showVersionHistory.value = false
}

// Keyboard shortcuts
const handleKeydown = (e) => {
    // Ctrl/Cmd + S = Save
    if ((e.ctrlKey || e.metaKey) && e.key === 's') {
        e.preventDefault()
        handleSave()
    }
    // Ctrl/Cmd + Z = Undo
    if ((e.ctrlKey || e.metaKey) && e.key === 'z' && !e.shiftKey) {
        e.preventDefault()
        undo()
    }
    // Ctrl/Cmd + Shift + Z = Redo
    if ((e.ctrlKey || e.metaKey) && e.key === 'z' && e.shiftKey) {
        e.preventDefault()
        redo()
    }
    // Ctrl/Cmd + Y = Redo
    if ((e.ctrlKey || e.metaKey) && e.key === 'y') {
        e.preventDefault()
        redo()
    }
}

// Auto-save
const startAutoSave = () => {
    autoSaveInterval.value = setInterval(() => {
        if (isDirty.value && !isSaving.value) {
            handleSave()
        }
    }, 30000) // 30 seconds
}

const stopAutoSave = () => {
    if (autoSaveInterval.value) {
        clearInterval(autoSaveInterval.value)
    }
}

// Lifecycle
onMounted(async () => {
    try {
        await loadBlockConfig()
        await loadPage(pageId.value)
        startAutoSave()
        window.addEventListener('keydown', handleKeydown)
    } catch (error) {
        alert('Failed to load page')
        router.push({ name: 'admin.page-builder' })
    }
})

onUnmounted(() => {
    stopAutoSave()
    window.removeEventListener('keydown', handleKeydown)
    resetState()
})

// Warn before leaving with unsaved changes
watch(isDirty, (dirty) => {
    if (dirty) {
        window.onbeforeunload = () => true
    } else {
        window.onbeforeunload = null
    }
})
</script>

<template>
    <div class="h-screen flex flex-col bg-gray-100 overflow-hidden">
        <!-- Top Toolbar -->
        <div class="bg-white border-b px-4 py-2 flex items-center justify-between z-10">
            <!-- Left: Back & Page Info -->
            <div class="flex items-center space-x-4">
                <button
                    @click="goBack"
                    class="p-2 hover:bg-gray-100 rounded-lg text-gray-600"
                    title="Back to Pages"
                >
                    <i class="bi bi-arrow-left text-lg"></i>
                </button>

                <div v-if="page" class="flex items-center space-x-3">
                    <div>
                        <h1 class="font-semibold text-gray-800">{{ page.title }}</h1>
                        <div class="flex items-center space-x-2 text-xs text-gray-500">
                            <span>/{{ page.slug }}</span>
                            <span :class="[
                                'px-1.5 py-0.5 rounded',
                                page.status === 'published' ? 'bg-green-100 text-green-700' :
                                page.status === 'scheduled' ? 'bg-blue-100 text-blue-700' :
                                'bg-gray-100 text-gray-700'
                            ]">
                                {{ page.status }}
                            </span>
                        </div>
                    </div>
                    <button
                        @click="showPageSettings = true"
                        class="p-1 hover:bg-gray-100 rounded text-gray-400 hover:text-gray-600"
                        title="Page Settings"
                    >
                        <i class="bi bi-gear"></i>
                    </button>
                </div>
            </div>

            <!-- Center: Preview Controls -->
            <div class="flex items-center space-x-1 bg-gray-100 rounded-lg p-1">
                <button
                    v-for="(size, mode) in previewSizes"
                    :key="mode"
                    @click="setPreviewMode(mode)"
                    :class="[
                        'px-3 py-1.5 rounded-md text-sm font-medium transition-colors',
                        previewMode === mode
                            ? 'bg-white text-gray-800 shadow-sm'
                            : 'text-gray-500 hover:text-gray-700'
                    ]"
                    :title="size.label"
                >
                    <i :class="[
                        'bi',
                        mode === 'desktop' ? 'bi-laptop' :
                        mode === 'tablet' ? 'bi-tablet' : 'bi-phone'
                    ]"></i>
                </button>
            </div>

            <!-- Right: Actions -->
            <div class="flex items-center space-x-2">
                <!-- Undo/Redo -->
                <div class="flex items-center border-r pr-2 mr-2">
                    <button
                        @click="undo"
                        :disabled="!canUndo"
                        :class="['p-2 rounded-lg', canUndo ? 'hover:bg-gray-100 text-gray-600' : 'text-gray-300 cursor-not-allowed']"
                        title="Undo (Ctrl+Z)"
                    >
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </button>
                    <button
                        @click="redo"
                        :disabled="!canRedo"
                        :class="['p-2 rounded-lg', canRedo ? 'hover:bg-gray-100 text-gray-600' : 'text-gray-300 cursor-not-allowed']"
                        title="Redo (Ctrl+Y)"
                    >
                        <i class="bi bi-arrow-clockwise"></i>
                    </button>
                </div>

                <!-- SEO -->
                <button
                    @click="showSeoPanel = !showSeoPanel; showVersionHistory = false"
                    :class="['p-2 rounded-lg', showSeoPanel ? 'bg-amber-100 text-amber-600' : 'hover:bg-gray-100 text-gray-600']"
                    title="SEO Settings"
                >
                    <i class="bi bi-search"></i>
                </button>

                <!-- Version History -->
                <button
                    @click="showVersionHistory = !showVersionHistory; showSeoPanel = false"
                    :class="['p-2 rounded-lg', showVersionHistory ? 'bg-amber-100 text-amber-600' : 'hover:bg-gray-100 text-gray-600']"
                    title="Version History"
                >
                    <i class="bi bi-clock-history"></i>
                </button>

                <!-- Preview -->
                <button
                    @click="handlePreview"
                    class="p-2 hover:bg-gray-100 rounded-lg text-gray-600"
                    title="Preview"
                >
                    <i class="bi bi-eye"></i>
                </button>

                <!-- Save Status -->
                <div class="text-xs text-gray-500 px-2">
                    <span v-if="isSaving" class="flex items-center">
                        <i class="bi bi-arrow-repeat animate-spin me-1"></i>
                        Saving...
                    </span>
                    <span v-else-if="isDirty" class="text-amber-600">
                        Unsaved changes
                    </span>
                    <span v-else-if="lastSaved">
                        Saved at {{ formatTime(lastSaved) }}
                    </span>
                </div>

                <!-- Save Button -->
                <button
                    @click="handleSave"
                    :disabled="isSaving"
                    class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 text-gray-700 flex items-center space-x-1"
                >
                    <i class="bi bi-save"></i>
                    <span>Save</span>
                </button>

                <!-- Publish Button -->
                <button
                    @click="handlePublish"
                    :disabled="isSaving"
                    class="px-4 py-2 bg-amber-600 text-white rounded-lg hover:bg-amber-700 flex items-center space-x-1"
                >
                    <i class="bi bi-globe"></i>
                    <span>{{ page?.status === 'published' ? 'Update' : 'Publish' }}</span>
                </button>
            </div>
        </div>

        <!-- Loading State -->
        <div v-if="isLoading" class="flex-1 flex items-center justify-center">
            <div class="text-center">
                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-amber-600 mx-auto"></div>
                <p class="mt-4 text-gray-500">Loading page builder...</p>
            </div>
        </div>

        <!-- Builder Area -->
        <div v-else class="flex-1 flex overflow-hidden">
            <!-- Left Sidebar: Block Toolbox -->
            <div class="w-64 bg-white border-r flex flex-col overflow-hidden">
                <BlockToolbox />
            </div>

            <!-- Center: Canvas -->
            <div class="flex-1 overflow-auto p-6 bg-gray-100">
                <div
                    class="mx-auto bg-white shadow-lg transition-all duration-300"
                    :style="{ width: canvasWidth, minHeight: 'calc(100vh - 200px)' }"
                >
                    <BuilderCanvas />
                </div>
            </div>

            <!-- Right Sidebar: Block Editor or SEO Panel or Version History -->
            <div class="w-80 bg-white border-l flex flex-col overflow-hidden">
                <SeoPanel v-if="showSeoPanel" />
                <VersionHistoryPanel
                    v-else-if="showVersionHistory"
                    :page-id="pageId"
                    @restore="handleVersionRestore"
                    @close="showVersionHistory = false"
                />
                <BlockEditor v-else-if="selectedBlock" />
                <div v-else class="flex-1 flex items-center justify-center p-6 text-center">
                    <div class="text-gray-400">
                        <i class="bi bi-hand-index text-4xl"></i>
                        <p class="mt-2">Select a block to edit its settings</p>
                        <p class="text-sm mt-1">Or drag a block from the left panel</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Page Settings Modal -->
        <PageSettingsModal
            v-if="showPageSettings"
            @close="showPageSettings = false"
        />
    </div>
</template>

<style scoped>
.animate-spin {
    animation: spin 1s linear infinite;
}

@keyframes spin {
    from {
        transform: rotate(0deg);
    }
    to {
        transform: rotate(360deg);
    }
}
</style>
