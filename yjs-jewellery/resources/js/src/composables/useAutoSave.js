import { ref, watch, onBeforeUnmount } from 'vue'

/**
 * useAutoSave - Automatic saving with debounce for CMS Kernel
 *
 * Automatically saves changes after a period of inactivity
 * to prevent data loss while maintaining immutable versioning
 */
export function useAutoSave(options = {}) {
    const {
        delay = 30000,           // 30 seconds default
        enabled = true,
        onSave = null,
        onError = null,
        maxRetries = 3
    } = options

    // State
    const isAutoSaveEnabled = ref(enabled)
    const lastSaveTime = ref(null)
    const pendingChanges = ref(false)
    const isSaving = ref(false)
    const retryCount = ref(0)
    const saveError = ref(null)

    let saveTimeout = null
    let retryTimeout = null

    /**
     * Schedule an auto-save
     */
    const scheduleSave = () => {
        if (!isAutoSaveEnabled.value || isSaving.value) return

        // Clear existing timeout
        if (saveTimeout) {
            clearTimeout(saveTimeout)
        }

        pendingChanges.value = true

        // Schedule new save
        saveTimeout = setTimeout(async () => {
            await performSave()
        }, delay)
    }

    /**
     * Perform the actual save
     */
    const performSave = async () => {
        if (!pendingChanges.value || isSaving.value) return

        isSaving.value = true
        saveError.value = null

        try {
            if (onSave && typeof onSave === 'function') {
                await onSave()
            }

            pendingChanges.value = false
            lastSaveTime.value = new Date()
            retryCount.value = 0
        } catch (error) {
            saveError.value = error.message || 'Auto-save failed'

            if (onError && typeof onError === 'function') {
                onError(error)
            }

            // Retry logic
            if (retryCount.value < maxRetries) {
                retryCount.value++
                scheduleRetry()
            }
        } finally {
            isSaving.value = false
        }
    }

    /**
     * Schedule a retry after failure
     */
    const scheduleRetry = () => {
        if (retryTimeout) {
            clearTimeout(retryTimeout)
        }

        // Exponential backoff: 5s, 15s, 45s
        const retryDelay = 5000 * Math.pow(3, retryCount.value - 1)

        retryTimeout = setTimeout(async () => {
            await performSave()
        }, retryDelay)
    }

    /**
     * Force an immediate save
     */
    const saveNow = async () => {
        if (saveTimeout) {
            clearTimeout(saveTimeout)
            saveTimeout = null
        }

        pendingChanges.value = true
        await performSave()
    }

    /**
     * Cancel pending auto-save
     */
    const cancel = () => {
        if (saveTimeout) {
            clearTimeout(saveTimeout)
            saveTimeout = null
        }
        if (retryTimeout) {
            clearTimeout(retryTimeout)
            retryTimeout = null
        }
        pendingChanges.value = false
    }

    /**
     * Enable/disable auto-save
     */
    const setEnabled = (value) => {
        isAutoSaveEnabled.value = value
        if (!value) {
            cancel()
        }
    }

    /**
     * Mark that changes have been made
     */
    const markDirty = () => {
        scheduleSave()
    }

    /**
     * Reset state
     */
    const reset = () => {
        cancel()
        lastSaveTime.value = null
        saveError.value = null
        retryCount.value = 0
    }

    // Cleanup on unmount
    onBeforeUnmount(() => {
        cancel()
    })

    return {
        // State
        isAutoSaveEnabled,
        lastSaveTime,
        pendingChanges,
        isSaving,
        saveError,
        retryCount,

        // Methods
        scheduleSave,
        saveNow,
        cancel,
        setEnabled,
        markDirty,
        reset
    }
}

/**
 * Create a watcher for auto-save on data changes
 */
export function watchForAutoSave(dataRef, autoSave, options = {}) {
    const { deep = true, immediate = false } = options

    return watch(
        dataRef,
        () => {
            autoSave.markDirty()
        },
        { deep, immediate }
    )
}
