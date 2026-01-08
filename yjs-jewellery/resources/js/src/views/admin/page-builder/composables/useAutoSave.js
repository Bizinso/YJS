import { ref, watch, onUnmounted } from 'vue'
import axios from '@/plugins/axiosAdmin'

export function useAutoSave(pageId, getPageData, options = {}) {
    const {
        interval = 30000, // 30 seconds default
        debounceDelay = 2000, // 2 seconds debounce
        onSave = () => {},
        onError = () => {},
    } = options

    const lastSaved = ref(null)
    const isSaving = ref(false)
    const hasUnsavedChanges = ref(false)
    const autoSaveEnabled = ref(true)

    let autoSaveTimer = null
    let debounceTimer = null
    let lastHash = null

    // Simple hash to detect changes
    const hashData = (data) => {
        return JSON.stringify(data)
    }

    // Save the current page state
    const save = async () => {
        if (!pageId.value || isSaving.value || !autoSaveEnabled.value) return

        const data = getPageData()
        const currentHash = hashData(data)

        // Skip if no changes
        if (currentHash === lastHash) {
            hasUnsavedChanges.value = false
            return
        }

        isSaving.value = true

        try {
            await axios.put(`/admin/cms/pages/${pageId.value}`, {
                ...data,
                _autosave: true
            })

            lastSaved.value = new Date()
            lastHash = currentHash
            hasUnsavedChanges.value = false

            onSave()
        } catch (error) {
            console.error('Auto-save failed:', error)
            onError(error)
        } finally {
            isSaving.value = false
        }
    }

    // Manual save trigger
    const saveNow = async () => {
        clearTimeout(debounceTimer)
        await save()
    }

    // Mark as having unsaved changes (called on any edit)
    const markDirty = () => {
        hasUnsavedChanges.value = true

        // Debounced save
        clearTimeout(debounceTimer)
        debounceTimer = setTimeout(() => {
            if (autoSaveEnabled.value) {
                save()
            }
        }, debounceDelay)
    }

    // Start auto-save interval
    const startAutoSave = () => {
        stopAutoSave()
        autoSaveTimer = setInterval(() => {
            if (hasUnsavedChanges.value && autoSaveEnabled.value) {
                save()
            }
        }, interval)
    }

    // Stop auto-save interval
    const stopAutoSave = () => {
        if (autoSaveTimer) {
            clearInterval(autoSaveTimer)
            autoSaveTimer = null
        }
    }

    // Toggle auto-save
    const toggleAutoSave = (enabled) => {
        autoSaveEnabled.value = enabled
        if (enabled) {
            startAutoSave()
        } else {
            stopAutoSave()
        }
    }

    // Get time since last save
    const getTimeSinceLastSave = () => {
        if (!lastSaved.value) return null

        const seconds = Math.floor((new Date() - lastSaved.value) / 1000)

        if (seconds < 60) return 'Just now'
        if (seconds < 3600) return `${Math.floor(seconds / 60)} min ago`
        return `${Math.floor(seconds / 3600)} hr ago`
    }

    // Watch for page changes
    watch(pageId, (newId) => {
        if (newId) {
            lastHash = null
            hasUnsavedChanges.value = false
            startAutoSave()
        } else {
            stopAutoSave()
        }
    }, { immediate: true })

    // Cleanup on unmount
    onUnmounted(() => {
        stopAutoSave()
        clearTimeout(debounceTimer)
    })

    // Warn before leaving with unsaved changes
    if (typeof window !== 'undefined') {
        window.addEventListener('beforeunload', (e) => {
            if (hasUnsavedChanges.value) {
                e.preventDefault()
                e.returnValue = ''
            }
        })
    }

    return {
        lastSaved,
        isSaving,
        hasUnsavedChanges,
        autoSaveEnabled,
        save,
        saveNow,
        markDirty,
        startAutoSave,
        stopAutoSave,
        toggleAutoSave,
        getTimeSinceLastSave,
    }
}
