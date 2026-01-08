<script setup>
import { ref, computed, onMounted } from 'vue'
import axios from '@/plugins/axiosAdmin'

// State
const loading = ref(true)
const saving = ref(false)
const taxonomies = ref([])
const selectedTaxonomy = ref(null)
const terms = ref([])
const loadingTerms = ref(false)

// Modal state
const showTaxonomyModal = ref(false)
const showTermModal = ref(false)
const editingTaxonomy = ref(null)
const editingTerm = ref(null)

// Forms
const taxonomyForm = ref({
    name: '',
    description: '',
    hierarchical: false,
})

const termForm = ref({
    name: '',
    description: '',
    parent_id: null,
})

// Computed
const taxonomyModalTitle = computed(() => {
    return editingTaxonomy.value ? 'Edit Taxonomy' : 'Create Taxonomy'
})

const termModalTitle = computed(() => {
    return editingTerm.value ? 'Edit Term' : 'Create Term'
})

const flatTerms = computed(() => {
    // Flatten hierarchical terms for parent selection
    const flat = []
    const flatten = (items, depth = 0) => {
        items.forEach(item => {
            flat.push({ ...item, depth })
            if (item.children?.length) {
                flatten(item.children, depth + 1)
            }
        })
    }
    flatten(terms.value)
    return flat
})

// Methods
const fetchTaxonomies = async () => {
    loading.value = true
    try {
        const { data } = await axios.get('/cms/taxonomies')
        if (data.success) {
            taxonomies.value = data.data
        }
    } catch (error) {
        console.error('Failed to fetch taxonomies:', error)
    } finally {
        loading.value = false
    }
}

const fetchTerms = async (taxonomyId) => {
    loadingTerms.value = true
    try {
        const { data } = await axios.get(`/cms/taxonomies/${taxonomyId}/terms`)
        if (data.success) {
            terms.value = data.data
        }
    } catch (error) {
        console.error('Failed to fetch terms:', error)
    } finally {
        loadingTerms.value = false
    }
}

const selectTaxonomy = async (taxonomy) => {
    selectedTaxonomy.value = taxonomy
    await fetchTerms(taxonomy.id)
}

// Taxonomy CRUD
const openTaxonomyModal = (taxonomy = null) => {
    editingTaxonomy.value = taxonomy
    taxonomyForm.value = taxonomy ? {
        name: taxonomy.name,
        description: taxonomy.description || '',
        hierarchical: taxonomy.hierarchical,
    } : {
        name: '',
        description: '',
        hierarchical: false,
    }
    showTaxonomyModal.value = true
}

const closeTaxonomyModal = () => {
    showTaxonomyModal.value = false
    editingTaxonomy.value = null
}

const saveTaxonomy = async () => {
    if (!taxonomyForm.value.name) {
        alert('Name is required')
        return
    }

    saving.value = true
    try {
        if (editingTaxonomy.value) {
            await axios.put(`/cms/taxonomies/${editingTaxonomy.value.id}`, taxonomyForm.value)
        } else {
            await axios.post('/cms/taxonomies', taxonomyForm.value)
        }

        closeTaxonomyModal()
        await fetchTaxonomies()
    } catch (error) {
        console.error('Failed to save taxonomy:', error)
        alert(error.response?.data?.message || 'Failed to save taxonomy')
    } finally {
        saving.value = false
    }
}

const deleteTaxonomy = async (taxonomy) => {
    if (taxonomy.terms_count > 0) {
        alert(`Cannot delete: Taxonomy has ${taxonomy.terms_count} term(s). Delete terms first.`)
        return
    }

    if (!confirm(`Delete taxonomy "${taxonomy.name}"?`)) return

    try {
        await axios.delete(`/cms/taxonomies/${taxonomy.id}`)
        if (selectedTaxonomy.value?.id === taxonomy.id) {
            selectedTaxonomy.value = null
            terms.value = []
        }
        await fetchTaxonomies()
    } catch (error) {
        console.error('Failed to delete taxonomy:', error)
        alert(error.response?.data?.message || 'Failed to delete taxonomy')
    }
}

// Term CRUD
const openTermModal = (term = null) => {
    editingTerm.value = term
    termForm.value = term ? {
        name: term.name,
        description: term.description || '',
        parent_id: term.parent_id,
    } : {
        name: '',
        description: '',
        parent_id: null,
    }
    showTermModal.value = true
}

const closeTermModal = () => {
    showTermModal.value = false
    editingTerm.value = null
}

const saveTerm = async () => {
    if (!termForm.value.name) {
        alert('Name is required')
        return
    }

    saving.value = true
    try {
        if (editingTerm.value) {
            await axios.put(
                `/cms/taxonomies/${selectedTaxonomy.value.id}/terms/${editingTerm.value.id}`,
                termForm.value
            )
        } else {
            await axios.post(
                `/cms/taxonomies/${selectedTaxonomy.value.id}/terms`,
                termForm.value
            )
        }

        closeTermModal()
        await fetchTerms(selectedTaxonomy.value.id)
        await fetchTaxonomies() // Update counts
    } catch (error) {
        console.error('Failed to save term:', error)
        alert(error.response?.data?.message || 'Failed to save term')
    } finally {
        saving.value = false
    }
}

const deleteTerm = async (term) => {
    if (term.children?.length > 0) {
        alert('Cannot delete: Term has child terms')
        return
    }

    if (term.pages_count > 0) {
        alert(`Cannot delete: Term is used by ${term.pages_count} page(s)`)
        return
    }

    if (!confirm(`Delete term "${term.name}"?`)) return

    try {
        await axios.delete(`/cms/taxonomies/${selectedTaxonomy.value.id}/terms/${term.id}`)
        await fetchTerms(selectedTaxonomy.value.id)
        await fetchTaxonomies() // Update counts
    } catch (error) {
        console.error('Failed to delete term:', error)
        alert(error.response?.data?.message || 'Failed to delete term')
    }
}

// Render hierarchical terms
const renderTerms = (items, depth = 0) => {
    return items
}

// Lifecycle
onMounted(() => {
    fetchTaxonomies()
})
</script>

<template>
    <div class="taxonomies-page p-6">
        <div class="flex gap-6">
            <!-- Left Panel: Taxonomies List -->
            <div class="w-80 flex-shrink-0">
                <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                    <div class="p-4 border-b border-gray-200 flex items-center justify-between">
                        <h2 class="font-semibold text-gray-800">Taxonomies</h2>
                        <button
                            @click="openTaxonomyModal()"
                            class="p-1.5 text-amber-600 hover:bg-amber-50 rounded"
                        >
                            <i class="bi bi-plus-lg"></i>
                        </button>
                    </div>

                    <!-- Loading -->
                    <div v-if="loading" class="p-4 text-center">
                        <div class="w-6 h-6 border-2 border-amber-500 border-t-transparent rounded-full animate-spin mx-auto"></div>
                    </div>

                    <!-- Taxonomy List -->
                    <div v-else class="divide-y divide-gray-100">
                        <div
                            v-for="taxonomy in taxonomies"
                            :key="taxonomy.id"
                            @click="selectTaxonomy(taxonomy)"
                            :class="[
                                'p-4 cursor-pointer transition-colors',
                                selectedTaxonomy?.id === taxonomy.id
                                    ? 'bg-amber-50 border-l-2 border-amber-500'
                                    : 'hover:bg-gray-50'
                            ]"
                        >
                            <div class="flex items-start justify-between">
                                <div>
                                    <h3 class="font-medium text-gray-800">{{ taxonomy.name }}</h3>
                                    <p class="text-xs text-gray-500 mt-0.5">
                                        {{ taxonomy.terms_count }} terms
                                        <span v-if="taxonomy.hierarchical" class="ml-1">
                                            <i class="bi bi-diagram-3"></i>
                                        </span>
                                    </p>
                                </div>
                                <div class="flex gap-1">
                                    <button
                                        @click.stop="openTaxonomyModal(taxonomy)"
                                        class="p-1 text-gray-400 hover:text-gray-600"
                                    >
                                        <i class="bi bi-pencil text-sm"></i>
                                    </button>
                                    <button
                                        @click.stop="deleteTaxonomy(taxonomy)"
                                        class="p-1 text-gray-400 hover:text-red-600"
                                    >
                                        <i class="bi bi-trash text-sm"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div v-if="taxonomies.length === 0" class="p-8 text-center">
                            <i class="bi bi-tags text-3xl text-gray-300"></i>
                            <p class="text-sm text-gray-500 mt-2">No taxonomies yet</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Panel: Terms -->
            <div class="flex-1">
                <!-- No taxonomy selected -->
                <div v-if="!selectedTaxonomy" class="bg-white rounded-xl border border-gray-200 p-12 text-center">
                    <i class="bi bi-arrow-left text-4xl text-gray-300"></i>
                    <h3 class="text-lg font-medium text-gray-800 mt-4">Select a Taxonomy</h3>
                    <p class="text-gray-500 mt-1">Choose a taxonomy from the left to manage its terms</p>
                </div>

                <!-- Terms Panel -->
                <div v-else class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                    <div class="p-4 border-b border-gray-200 flex items-center justify-between">
                        <div>
                            <h2 class="font-semibold text-gray-800">{{ selectedTaxonomy.name }} Terms</h2>
                            <p class="text-sm text-gray-500">
                                {{ selectedTaxonomy.hierarchical ? 'Hierarchical taxonomy' : 'Flat taxonomy' }}
                            </p>
                        </div>
                        <button
                            @click="openTermModal()"
                            class="px-3 py-1.5 bg-amber-600 text-white text-sm rounded-lg hover:bg-amber-700 flex items-center gap-1"
                        >
                            <i class="bi bi-plus-lg"></i>
                            Add Term
                        </button>
                    </div>

                    <!-- Loading Terms -->
                    <div v-if="loadingTerms" class="p-8 text-center">
                        <div class="w-6 h-6 border-2 border-amber-500 border-t-transparent rounded-full animate-spin mx-auto"></div>
                    </div>

                    <!-- Terms List -->
                    <div v-else-if="terms.length > 0" class="divide-y divide-gray-100">
                        <template v-for="term in flatTerms" :key="term.id">
                            <div
                                class="p-4 flex items-center justify-between hover:bg-gray-50"
                                :style="{ paddingLeft: `${1 + term.depth * 1.5}rem` }"
                            >
                                <div class="flex items-center gap-2">
                                    <i v-if="term.depth > 0" class="bi bi-arrow-return-right text-gray-400"></i>
                                    <div>
                                        <h4 class="font-medium text-gray-800">{{ term.name }}</h4>
                                        <p class="text-xs text-gray-500">
                                            {{ term.slug }}
                                            <span v-if="term.pages_count" class="ml-2">
                                                <i class="bi bi-file-earmark mr-0.5"></i>
                                                {{ term.pages_count }} pages
                                            </span>
                                        </p>
                                    </div>
                                </div>
                                <div class="flex gap-1">
                                    <button
                                        @click="openTermModal(term)"
                                        class="p-1.5 text-gray-400 hover:text-gray-600 rounded"
                                    >
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button
                                        @click="deleteTerm(term)"
                                        :disabled="term.children?.length > 0 || term.pages_count > 0"
                                        class="p-1.5 text-gray-400 hover:text-red-600 rounded disabled:opacity-30"
                                    >
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </template>
                    </div>

                    <!-- Empty Terms -->
                    <div v-else class="p-12 text-center">
                        <i class="bi bi-tag text-4xl text-gray-300"></i>
                        <h3 class="text-lg font-medium text-gray-800 mt-4">No Terms Yet</h3>
                        <p class="text-gray-500 mt-1">Add your first term to this taxonomy</p>
                        <button
                            @click="openTermModal()"
                            class="mt-4 px-4 py-2 bg-amber-600 text-white rounded-lg hover:bg-amber-700"
                        >
                            Add Term
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Taxonomy Modal -->
        <Teleport to="body">
            <div
                v-if="showTaxonomyModal"
                class="fixed inset-0 z-50 flex items-center justify-center p-4"
            >
                <div class="absolute inset-0 bg-black bg-opacity-50" @click="closeTaxonomyModal"></div>
                <div class="relative bg-white rounded-xl shadow-xl w-full max-w-md">
                    <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                        <h2 class="text-lg font-semibold text-gray-800">{{ taxonomyModalTitle }}</h2>
                        <button @click="closeTaxonomyModal" class="text-gray-400 hover:text-gray-600">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>

                    <div class="p-6 space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Name *</label>
                            <input
                                v-model="taxonomyForm.name"
                                type="text"
                                placeholder="e.g., Categories"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-amber-500 focus:border-amber-500"
                            />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                            <textarea
                                v-model="taxonomyForm.description"
                                rows="2"
                                placeholder="Describe this taxonomy..."
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-amber-500 focus:border-amber-500"
                            ></textarea>
                        </div>

                        <label class="flex items-center gap-2">
                            <input
                                v-model="taxonomyForm.hierarchical"
                                type="checkbox"
                                class="rounded border-gray-300 text-amber-600 focus:ring-amber-500"
                            />
                            <span class="text-sm text-gray-700">Hierarchical (allow parent/child terms)</span>
                        </label>
                    </div>

                    <div class="px-6 py-4 border-t border-gray-200 flex justify-end gap-3">
                        <button
                            @click="closeTaxonomyModal"
                            class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50"
                        >
                            Cancel
                        </button>
                        <button
                            @click="saveTaxonomy"
                            :disabled="saving"
                            class="px-4 py-2 bg-amber-600 text-white rounded-lg hover:bg-amber-700 disabled:opacity-50"
                        >
                            {{ saving ? 'Saving...' : (editingTaxonomy ? 'Update' : 'Create') }}
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Term Modal -->
        <Teleport to="body">
            <div
                v-if="showTermModal"
                class="fixed inset-0 z-50 flex items-center justify-center p-4"
            >
                <div class="absolute inset-0 bg-black bg-opacity-50" @click="closeTermModal"></div>
                <div class="relative bg-white rounded-xl shadow-xl w-full max-w-md">
                    <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                        <h2 class="text-lg font-semibold text-gray-800">{{ termModalTitle }}</h2>
                        <button @click="closeTermModal" class="text-gray-400 hover:text-gray-600">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>

                    <div class="p-6 space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Name *</label>
                            <input
                                v-model="termForm.name"
                                type="text"
                                placeholder="e.g., Technology"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-amber-500 focus:border-amber-500"
                            />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                            <textarea
                                v-model="termForm.description"
                                rows="2"
                                placeholder="Describe this term..."
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-amber-500 focus:border-amber-500"
                            ></textarea>
                        </div>

                        <div v-if="selectedTaxonomy?.hierarchical">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Parent Term</label>
                            <select
                                v-model="termForm.parent_id"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-amber-500 focus:border-amber-500"
                            >
                                <option :value="null">None (Top Level)</option>
                                <option
                                    v-for="term in flatTerms.filter(t => t.id !== editingTerm?.id)"
                                    :key="term.id"
                                    :value="term.id"
                                >
                                    {{ '—'.repeat(term.depth) }} {{ term.name }}
                                </option>
                            </select>
                        </div>
                    </div>

                    <div class="px-6 py-4 border-t border-gray-200 flex justify-end gap-3">
                        <button
                            @click="closeTermModal"
                            class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50"
                        >
                            Cancel
                        </button>
                        <button
                            @click="saveTerm"
                            :disabled="saving"
                            class="px-4 py-2 bg-amber-600 text-white rounded-lg hover:bg-amber-700 disabled:opacity-50"
                        >
                            {{ saving ? 'Saving...' : (editingTerm ? 'Update' : 'Create') }}
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </div>
</template>
