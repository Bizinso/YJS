import { ref, computed } from 'vue'

export function useUndoRedo(options = {}) {
    const {
        maxHistory = 50,
        debounceDelay = 300,
    } = options

    const history = ref([])
    const currentIndex = ref(-1)
    let debounceTimer = null

    // Check if undo is available
    const canUndo = computed(() => currentIndex.value > 0)

    // Check if redo is available
    const canRedo = computed(() => currentIndex.value < history.value.length - 1)

    // Get current state
    const currentState = computed(() => {
        if (currentIndex.value >= 0 && currentIndex.value < history.value.length) {
            return history.value[currentIndex.value]
        }
        return null
    })

    // Deep clone helper
    const deepClone = (obj) => {
        return JSON.parse(JSON.stringify(obj))
    }

    // Push a new state to history
    const push = (state, label = '') => {
        // Clear any debounce timer
        clearTimeout(debounceTimer)

        // If we're not at the end of history, remove future states
        if (currentIndex.value < history.value.length - 1) {
            history.value = history.value.slice(0, currentIndex.value + 1)
        }

        // Add new state
        history.value.push({
            state: deepClone(state),
            label,
            timestamp: Date.now()
        })

        // Trim history if it exceeds max
        if (history.value.length > maxHistory) {
            history.value.shift()
        } else {
            currentIndex.value++
        }
    }

    // Push with debouncing (for rapid changes like typing)
    const pushDebounced = (state, label = '') => {
        clearTimeout(debounceTimer)
        debounceTimer = setTimeout(() => {
            push(state, label)
        }, debounceDelay)
    }

    // Undo to previous state
    const undo = () => {
        if (!canUndo.value) return null

        currentIndex.value--
        return deepClone(history.value[currentIndex.value].state)
    }

    // Redo to next state
    const redo = () => {
        if (!canRedo.value) return null

        currentIndex.value++
        return deepClone(history.value[currentIndex.value].state)
    }

    // Clear all history
    const clear = () => {
        history.value = []
        currentIndex.value = -1
    }

    // Initialize with a state
    const init = (initialState) => {
        clear()
        push(initialState, 'Initial state')
    }

    // Get history list for display
    const getHistoryList = () => {
        return history.value.map((item, index) => ({
            index,
            label: item.label || `Change ${index + 1}`,
            timestamp: item.timestamp,
            isCurrent: index === currentIndex.value,
        })).reverse() // Most recent first
    }

    // Go to a specific point in history
    const goTo = (index) => {
        if (index >= 0 && index < history.value.length) {
            currentIndex.value = index
            return deepClone(history.value[index].state)
        }
        return null
    }

    // Get undo stack size
    const undoStackSize = computed(() => currentIndex.value)

    // Get redo stack size
    const redoStackSize = computed(() => history.value.length - currentIndex.value - 1)

    // Keyboard shortcuts helper
    const handleKeyboard = (event, onUndo, onRedo) => {
        const isMac = navigator.platform.toUpperCase().indexOf('MAC') >= 0
        const modifier = isMac ? event.metaKey : event.ctrlKey

        if (modifier && event.key === 'z') {
            if (event.shiftKey) {
                // Ctrl+Shift+Z or Cmd+Shift+Z for redo
                event.preventDefault()
                const state = redo()
                if (state && onRedo) onRedo(state)
            } else {
                // Ctrl+Z or Cmd+Z for undo
                event.preventDefault()
                const state = undo()
                if (state && onUndo) onUndo(state)
            }
        } else if (modifier && event.key === 'y') {
            // Ctrl+Y for redo (Windows)
            event.preventDefault()
            const state = redo()
            if (state && onRedo) onRedo(state)
        }
    }

    return {
        history,
        currentIndex,
        currentState,
        canUndo,
        canRedo,
        undoStackSize,
        redoStackSize,
        push,
        pushDebounced,
        undo,
        redo,
        clear,
        init,
        goTo,
        getHistoryList,
        handleKeyboard,
    }
}
