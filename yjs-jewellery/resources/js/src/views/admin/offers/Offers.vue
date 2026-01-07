<script setup>
import { ref, onMounted, watch, computed } from 'vue'
import axios from '@/plugins/axiosAdmin'

const loading = ref(true)
const offers = ref([])
const pagination = ref({ current_page: 1, last_page: 1, total: 0 })
const filters = ref({ search: '', status: '', type: '' })
const showModal = ref(false)
const editingOffer = ref(null)

// Master data for product targeting
const categories = ref([])
const subcategories = ref([])
const products = ref([])
const loadingMasters = ref(false)

const defaultForm = {
    title: '',
    type: 'percentage',
    value: 0,
    min_order_amount: 0,
    max_discount: null,
    start_date: '',
    end_date: '',
    status: 'active',
    description: '',
    // Product targeting
    applies_to: 'all', // all, categories, subcategories, products
    category_ids: [],
    subcategory_ids: [],
    product_ids: [],
    exclude_sale_items: false,
    // BOGO fields
    buy_quantity: 1,
    get_quantity: 1,
    get_discount_percent: 100,
    buy_product_ids: [],
    get_product_ids: [],
    // Coupon fields
    coupon_code: '',
    usage_limit: null,
    per_user_limit: null,
    // Flash Sale fields
    flash_quantity: null,
    // Tiered discount fields
    tier_rules: [],
    // Combo fields
    combo_products: [],
    combo_discount_type: 'percentage',
    combo_discount_value: 10
}

const form = ref({ ...defaultForm })

const offerTypes = [
    { value: 'percentage', label: 'Percentage Discount', icon: '📊' },
    { value: 'fixed', label: 'Fixed Amount', icon: '💰' },
    { value: 'bogo', label: 'Buy One Get One', icon: '🎁' },
    { value: 'flash_sale', label: 'Flash Sale', icon: '⚡' },
    { value: 'combo', label: 'Combo Deal', icon: '📦' },
    { value: 'tiered', label: 'Tiered Discount', icon: '📈' },
    { value: 'coupon', label: 'Coupon Code', icon: '🎟️' },
    { value: 'free_shipping', label: 'Free Shipping', icon: '🚚' }
]

const applicabilityOptions = [
    { value: 'all', label: 'All Products', icon: '🌐', desc: 'Offer applies to entire catalog' },
    { value: 'categories', label: 'Specific Categories', icon: '📁', desc: 'Select one or more categories' },
    { value: 'subcategories', label: 'Specific Subcategories', icon: '📂', desc: 'Select specific subcategories' },
    { value: 'products', label: 'Specific Products', icon: '🏷️', desc: 'Hand-pick individual products' }
]

// Computed properties for conditional rendering
const showValueField = computed(() => ['percentage', 'fixed'].includes(form.value.type))
const showBogoFields = computed(() => form.value.type === 'bogo')
const showCouponFields = computed(() => form.value.type === 'coupon')
const showFlashFields = computed(() => form.value.type === 'flash_sale')
const showTieredFields = computed(() => form.value.type === 'tiered')
const showComboFields = computed(() => form.value.type === 'combo')
const showFreeShippingFields = computed(() => form.value.type === 'free_shipping')

// Filtered subcategories based on selected categories
const filteredSubcategories = computed(() => {
    if (form.value.category_ids.length === 0) return subcategories.value
    return subcategories.value.filter(sc => form.value.category_ids.includes(sc.category_id))
})

// Filtered products based on selected categories/subcategories
const filteredProducts = computed(() => {
    let result = products.value
    if (form.value.category_ids.length > 0) {
        result = result.filter(p => form.value.category_ids.includes(p.category_id))
    }
    if (form.value.subcategory_ids.length > 0) {
        result = result.filter(p => form.value.subcategory_ids.includes(p.subcategory_id))
    }
    return result
})

// Get names for display
const getApplicabilityDisplay = (offer) => {
    if (!offer.applies_to || offer.applies_to === 'all') return 'All Products'
    if (offer.applies_to === 'categories' && offer.category_ids?.length) {
        return `${offer.category_ids.length} Categories`
    }
    if (offer.applies_to === 'subcategories' && offer.subcategory_ids?.length) {
        return `${offer.subcategory_ids.length} Subcategories`
    }
    if (offer.applies_to === 'products' && offer.product_ids?.length) {
        return `${offer.product_ids.length} Products`
    }
    return 'All Products'
}

const fetchOffers = async (page = 1) => {
    loading.value = true
    try {
        const response = await axios.get('/offers', { params: { page, ...filters.value } })
        if (response.data.success) {
            offers.value = response.data.data.data || response.data.data || []
            pagination.value = response.data.data.meta || response.data.data
        }
    } catch (error) { console.error('Error:', error) }
    finally { loading.value = false }
}

const fetchMasterData = async () => {
    loadingMasters.value = true
    try {
        const [catRes, subRes, prodRes] = await Promise.all([
            axios.get('/categories').catch(() => ({ data: { data: [] } })),
            axios.get('/subcategories').catch(() => ({ data: { data: [] } })),
            axios.get('/products', { params: { limit: 500, status: 'active' } }).catch(() => ({ data: { data: [] } }))
        ])
        categories.value = catRes.data.data || catRes.data || []
        subcategories.value = subRes.data.data || subRes.data || []
        products.value = prodRes.data.data?.data || prodRes.data.data || prodRes.data || []
    } catch (error) {
        console.error('Error fetching master data:', error)
    } finally {
        loadingMasters.value = false
    }
}

const openCreateModal = () => {
    editingOffer.value = null
    form.value = {
        ...defaultForm,
        tier_rules: [],
        combo_products: [],
        category_ids: [],
        subcategory_ids: [],
        product_ids: [],
        buy_product_ids: [],
        get_product_ids: []
    }
    showModal.value = true
    if (categories.value.length === 0) fetchMasterData()
}

const openEditModal = (offer) => {
    editingOffer.value = offer
    form.value = {
        ...defaultForm,
        ...offer,
        tier_rules: offer.tier_rules || [],
        combo_products: offer.combo_products || [],
        category_ids: offer.category_ids || [],
        subcategory_ids: offer.subcategory_ids || [],
        product_ids: offer.product_ids || [],
        buy_product_ids: offer.buy_product_ids || [],
        get_product_ids: offer.get_product_ids || []
    }
    showModal.value = true
    if (categories.value.length === 0) fetchMasterData()
}

const saveOffer = async () => {
    try {
        const payload = { ...form.value }
        if (editingOffer.value) {
            await axios.put(`/offers/${editingOffer.value.id}`, payload)
        } else {
            await axios.post('/offers', payload)
        }
        showModal.value = false
        fetchOffers(pagination.value.current_page)
    } catch (error) {
        alert(error.response?.data?.message || 'Failed to save')
    }
}

const deleteOffer = async (offer) => {
    if (confirm('Delete this offer?')) {
        await axios.delete(`/offers/${offer.id}`)
        fetchOffers(pagination.value.current_page)
    }
}

const toggleStatus = async (offer) => {
    await axios.post(`/offers/${offer.id}/${offer.status === 'active' ? 'deactivate' : 'activate'}`)
    fetchOffers(pagination.value.current_page)
}

// Tiered discount helpers
const addTier = () => {
    form.value.tier_rules.push({ min_amount: 0, discount_type: 'percentage', discount_value: 0 })
}

const removeTier = (index) => {
    form.value.tier_rules.splice(index, 1)
}

// Multi-select helpers
const toggleCategory = (catId) => {
    const idx = form.value.category_ids.indexOf(catId)
    if (idx > -1) {
        form.value.category_ids.splice(idx, 1)
    } else {
        form.value.category_ids.push(catId)
    }
}

const toggleSubcategory = (subId) => {
    const idx = form.value.subcategory_ids.indexOf(subId)
    if (idx > -1) {
        form.value.subcategory_ids.splice(idx, 1)
    } else {
        form.value.subcategory_ids.push(subId)
    }
}

const toggleProduct = (prodId) => {
    const idx = form.value.product_ids.indexOf(prodId)
    if (idx > -1) {
        form.value.product_ids.splice(idx, 1)
    } else {
        form.value.product_ids.push(prodId)
    }
}

// Watch for type changes to set defaults
watch(() => form.value.type, (newType) => {
    if (newType === 'bogo') {
        form.value.buy_quantity = form.value.buy_quantity || 1
        form.value.get_quantity = form.value.get_quantity || 1
        form.value.get_discount_percent = form.value.get_discount_percent || 100
    } else if (newType === 'tiered' && form.value.tier_rules.length === 0) {
        form.value.tier_rules = [{ min_amount: 1000, discount_type: 'percentage', discount_value: 5 }]
    } else if (newType === 'free_shipping') {
        form.value.value = 0
    }
})

// Clear irrelevant selections when applicability changes
watch(() => form.value.applies_to, (newVal) => {
    if (newVal === 'all') {
        form.value.category_ids = []
        form.value.subcategory_ids = []
        form.value.product_ids = []
    } else if (newVal === 'categories') {
        form.value.subcategory_ids = []
        form.value.product_ids = []
    } else if (newVal === 'subcategories') {
        form.value.product_ids = []
    }
})

const formatDate = (d) => d ? new Date(d).toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric' }) : '-'
const getStatusClass = (s) => s === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'
const getTypeIcon = (type) => offerTypes.find(t => t.value === type)?.icon || '🏷️'

let timeout = null
watch(() => filters.value.search, () => { clearTimeout(timeout); timeout = setTimeout(() => fetchOffers(1), 500) })
onMounted(() => fetchOffers())
</script>

<template>
    <div class="p-6">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Offers & Promotions</h1>
                <p class="text-gray-600">Manage discounts and promotions</p>
            </div>
            <button @click="openCreateModal" class="px-4 py-2 bg-amber-600 text-white rounded-lg hover:bg-amber-700 flex items-center space-x-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                <span>Create Offer</span>
            </button>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-lg shadow-sm p-4 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <input v-model="filters.search" type="text" placeholder="Search offers..." class="px-4 py-2 border rounded-lg">
                <select v-model="filters.type" @change="fetchOffers(1)" class="px-4 py-2 border rounded-lg">
                    <option value="">All Types</option>
                    <option v-for="t in offerTypes" :key="t.value" :value="t.value">{{ t.icon }} {{ t.label }}</option>
                </select>
                <select v-model="filters.status" @change="fetchOffers(1)" class="px-4 py-2 border rounded-lg">
                    <option value="">All Status</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
                <button @click="filters = { search: '', status: '', type: '' }; fetchOffers(1)" class="px-4 py-2 border rounded-lg hover:bg-gray-50">Reset</button>
            </div>
        </div>

        <!-- Offers Table -->
        <div class="bg-white rounded-lg shadow-sm overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Offer</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Applies To</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Value</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Validity</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <tr v-if="loading">
                        <td colspan="7" class="px-6 py-8 text-center">
                            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-amber-600 mx-auto"></div>
                        </td>
                    </tr>
                    <tr v-else-if="offers.length === 0">
                        <td colspan="7" class="px-6 py-8 text-center text-gray-500">No offers found</td>
                    </tr>
                    <tr v-else v-for="offer in offers" :key="offer.id" class="hover:bg-gray-50">
                        <td class="px-6 py-4">
                            <div class="font-medium text-gray-900">{{ offer.title }}</div>
                            <div class="text-sm text-gray-500">{{ offer.description?.substring(0, 40) }}...</div>
                        </td>
                        <td class="px-6 py-4 text-sm">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                {{ getTypeIcon(offer.type) }} {{ offer.type?.replace('_', ' ') }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm">
                            <span class="inline-flex items-center px-2 py-1 rounded text-xs bg-gray-100 text-gray-700">
                                {{ getApplicabilityDisplay(offer) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm font-medium">
                            <span v-if="offer.type === 'percentage'">{{ offer.value }}%</span>
                            <span v-else-if="offer.type === 'bogo'">Buy {{ offer.buy_quantity }} Get {{ offer.get_quantity }}</span>
                            <span v-else-if="offer.type === 'free_shipping'">Free Shipping</span>
                            <span v-else>₹{{ offer.value }}</span>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500">{{ formatDate(offer.start_date) }} - {{ formatDate(offer.end_date) }}</td>
                        <td class="px-6 py-4">
                            <button @click="toggleStatus(offer)" :class="['px-2 py-1 text-xs rounded-full', getStatusClass(offer.status)]">
                                {{ offer.status }}
                            </button>
                        </td>
                        <td class="px-6 py-4 text-right space-x-2">
                            <button @click="openEditModal(offer)" class="text-amber-600 hover:text-amber-900">Edit</button>
                            <button @click="deleteOffer(offer)" class="text-red-600 hover:text-red-900">Delete</button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Create/Edit Modal -->
        <div v-if="showModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 overflow-y-auto">
            <div class="bg-white rounded-lg shadow-xl w-full max-w-3xl my-8 mx-4">
                <div class="p-6 border-b flex justify-between items-center">
                    <h2 class="text-xl font-bold">{{ editingOffer ? 'Edit' : 'Create' }} Offer</h2>
                    <button @click="showModal = false" class="text-gray-500 text-2xl hover:text-gray-700">&times;</button>
                </div>

                <div class="p-6 space-y-6 max-h-[70vh] overflow-y-auto">
                    <!-- Basic Info -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Offer Title *</label>
                        <input v-model="form.title" type="text" class="w-full px-4 py-2 border rounded-lg" placeholder="e.g., Summer Sale 20% Off">
                    </div>

                    <!-- Offer Type Selection -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Offer Type *</label>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                            <button
                                v-for="t in offerTypes"
                                :key="t.value"
                                @click="form.type = t.value"
                                :class="[
                                    'p-3 border rounded-lg text-left transition-all',
                                    form.type === t.value
                                        ? 'border-amber-500 bg-amber-50 ring-2 ring-amber-500'
                                        : 'border-gray-200 hover:border-gray-300'
                                ]"
                            >
                                <span class="text-xl">{{ t.icon }}</span>
                                <span class="block text-xs mt-1 font-medium">{{ t.label }}</span>
                            </button>
                        </div>
                    </div>

                    <!-- ==================== APPLICABILITY SECTION ==================== -->
                    <div class="border-t pt-4">
                        <h3 class="font-medium text-gray-800 mb-3">🎯 Offer Applies To</h3>

                        <!-- Applicability Type Selection -->
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-2 mb-4">
                            <button
                                v-for="opt in applicabilityOptions"
                                :key="opt.value"
                                @click="form.applies_to = opt.value"
                                :class="[
                                    'p-3 border rounded-lg text-left transition-all',
                                    form.applies_to === opt.value
                                        ? 'border-green-500 bg-green-50 ring-2 ring-green-500'
                                        : 'border-gray-200 hover:border-gray-300'
                                ]"
                            >
                                <span class="text-lg">{{ opt.icon }}</span>
                                <span class="block text-xs mt-1 font-medium">{{ opt.label }}</span>
                            </button>
                        </div>

                        <!-- Loading indicator -->
                        <div v-if="loadingMasters && form.applies_to !== 'all'" class="text-center py-4">
                            <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-amber-600 mx-auto"></div>
                            <p class="text-sm text-gray-500 mt-2">Loading...</p>
                        </div>

                        <!-- Category Selection -->
                        <div v-if="form.applies_to === 'categories' && !loadingMasters" class="bg-gray-50 rounded-lg p-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Select Categories ({{ form.category_ids.length }} selected)
                            </label>
                            <div class="max-h-48 overflow-y-auto border rounded-lg bg-white p-2">
                                <div v-if="categories.length === 0" class="text-gray-500 text-sm p-2">No categories found</div>
                                <label
                                    v-for="cat in categories"
                                    :key="cat.id"
                                    class="flex items-center p-2 hover:bg-gray-100 rounded cursor-pointer"
                                >
                                    <input
                                        type="checkbox"
                                        :checked="form.category_ids.includes(cat.id)"
                                        @change="toggleCategory(cat.id)"
                                        class="w-4 h-4 text-amber-600 rounded"
                                    >
                                    <span class="ml-2 text-sm">{{ cat.name }}</span>
                                </label>
                            </div>
                        </div>

                        <!-- Subcategory Selection -->
                        <div v-if="form.applies_to === 'subcategories' && !loadingMasters" class="space-y-3">
                            <!-- First select parent categories to filter subcategories -->
                            <div class="bg-gray-50 rounded-lg p-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Filter by Category (optional)
                                </label>
                                <div class="flex flex-wrap gap-2">
                                    <button
                                        v-for="cat in categories"
                                        :key="cat.id"
                                        @click="toggleCategory(cat.id)"
                                        :class="[
                                            'px-3 py-1 text-xs rounded-full border transition-all',
                                            form.category_ids.includes(cat.id)
                                                ? 'bg-amber-100 border-amber-500 text-amber-700'
                                                : 'bg-white border-gray-300 hover:border-gray-400'
                                        ]"
                                    >
                                        {{ cat.name }}
                                    </button>
                                </div>
                            </div>

                            <div class="bg-gray-50 rounded-lg p-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Select Subcategories ({{ form.subcategory_ids.length }} selected)
                                </label>
                                <div class="max-h-48 overflow-y-auto border rounded-lg bg-white p-2">
                                    <div v-if="filteredSubcategories.length === 0" class="text-gray-500 text-sm p-2">No subcategories found</div>
                                    <label
                                        v-for="sub in filteredSubcategories"
                                        :key="sub.id"
                                        class="flex items-center p-2 hover:bg-gray-100 rounded cursor-pointer"
                                    >
                                        <input
                                            type="checkbox"
                                            :checked="form.subcategory_ids.includes(sub.id)"
                                            @change="toggleSubcategory(sub.id)"
                                            class="w-4 h-4 text-amber-600 rounded"
                                        >
                                        <span class="ml-2 text-sm">{{ sub.name }}</span>
                                        <span class="ml-auto text-xs text-gray-400">{{ sub.category?.name }}</span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Product Selection -->
                        <div v-if="form.applies_to === 'products' && !loadingMasters" class="space-y-3">
                            <!-- Filter by category/subcategory -->
                            <div class="bg-gray-50 rounded-lg p-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Filter Products by Category</label>
                                <div class="flex flex-wrap gap-2">
                                    <button
                                        v-for="cat in categories"
                                        :key="cat.id"
                                        @click="toggleCategory(cat.id)"
                                        :class="[
                                            'px-3 py-1 text-xs rounded-full border transition-all',
                                            form.category_ids.includes(cat.id)
                                                ? 'bg-amber-100 border-amber-500 text-amber-700'
                                                : 'bg-white border-gray-300 hover:border-gray-400'
                                        ]"
                                    >
                                        {{ cat.name }}
                                    </button>
                                </div>
                            </div>

                            <div class="bg-gray-50 rounded-lg p-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Select Products ({{ form.product_ids.length }} selected)
                                </label>
                                <div class="max-h-64 overflow-y-auto border rounded-lg bg-white p-2">
                                    <div v-if="filteredProducts.length === 0" class="text-gray-500 text-sm p-2">No products found</div>
                                    <label
                                        v-for="prod in filteredProducts"
                                        :key="prod.id"
                                        class="flex items-center p-2 hover:bg-gray-100 rounded cursor-pointer"
                                    >
                                        <input
                                            type="checkbox"
                                            :checked="form.product_ids.includes(prod.id)"
                                            @change="toggleProduct(prod.id)"
                                            class="w-4 h-4 text-amber-600 rounded"
                                        >
                                        <img v-if="prod.main_image" :src="prod.main_image" class="w-8 h-8 object-cover rounded ml-2" />
                                        <div class="ml-2 flex-1">
                                            <span class="text-sm block">{{ prod.name }}</span>
                                            <span class="text-xs text-gray-400">SKU: {{ prod.sku }}</span>
                                        </div>
                                        <span class="text-sm font-medium text-gray-600">₹{{ prod.sale_price || prod.price }}</span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Exclude sale items option -->
                        <div class="mt-3">
                            <label class="flex items-center cursor-pointer">
                                <input
                                    type="checkbox"
                                    v-model="form.exclude_sale_items"
                                    class="w-4 h-4 text-amber-600 rounded"
                                >
                                <span class="ml-2 text-sm text-gray-700">Exclude products already on sale</span>
                            </label>
                        </div>
                    </div>
                    <!-- ==================== END APPLICABILITY SECTION ==================== -->

                    <!-- Percentage/Fixed Value Fields -->
                    <div v-if="showValueField" class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                {{ form.type === 'percentage' ? 'Discount Percentage' : 'Discount Amount' }} *
                            </label>
                            <div class="relative">
                                <input v-model="form.value" type="number" class="w-full px-4 py-2 border rounded-lg" min="0">
                                <span class="absolute right-3 top-2 text-gray-500">{{ form.type === 'percentage' ? '%' : '₹' }}</span>
                            </div>
                        </div>
                        <div v-if="form.type === 'percentage'">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Max Discount (Optional)</label>
                            <div class="relative">
                                <input v-model="form.max_discount" type="number" class="w-full px-4 py-2 border rounded-lg" min="0" placeholder="No limit">
                                <span class="absolute right-3 top-2 text-gray-500">₹</span>
                            </div>
                        </div>
                    </div>

                    <!-- BOGO Fields -->
                    <div v-if="showBogoFields" class="bg-green-50 border border-green-200 rounded-lg p-4">
                        <h3 class="font-medium text-green-800 mb-3">🎁 Buy One Get One Settings</h3>
                        <div class="grid grid-cols-3 gap-4 mb-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Buy Quantity *</label>
                                <input v-model="form.buy_quantity" type="number" class="w-full px-4 py-2 border rounded-lg" min="1">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Get Quantity *</label>
                                <input v-model="form.get_quantity" type="number" class="w-full px-4 py-2 border rounded-lg" min="1">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Discount on Free Item</label>
                                <div class="relative">
                                    <input v-model="form.get_discount_percent" type="number" class="w-full px-4 py-2 border rounded-lg" min="0" max="100">
                                    <span class="absolute right-3 top-2 text-gray-500">%</span>
                                </div>
                            </div>
                        </div>
                        <p class="text-sm text-green-600">
                            Buy {{ form.buy_quantity }} item(s), get {{ form.get_quantity }} item(s) at {{ form.get_discount_percent }}% off
                            {{ form.get_discount_percent == 100 ? '(FREE!)' : '' }}
                        </p>
                    </div>

                    <!-- Flash Sale Fields -->
                    <div v-if="showFlashFields" class="bg-red-50 border border-red-200 rounded-lg p-4">
                        <h3 class="font-medium text-red-800 mb-3">⚡ Flash Sale Settings</h3>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Discount Value *</label>
                                <div class="relative">
                                    <input v-model="form.value" type="number" class="w-full px-4 py-2 border rounded-lg" min="0">
                                    <span class="absolute right-3 top-2 text-gray-500">%</span>
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Limited Quantity (Optional)</label>
                                <input v-model="form.flash_quantity" type="number" class="w-full px-4 py-2 border rounded-lg" min="1" placeholder="Unlimited">
                            </div>
                        </div>
                        <p class="text-sm text-red-600 mt-2">Flash sales create urgency with limited-time discounts!</p>
                    </div>

                    <!-- Coupon Fields -->
                    <div v-if="showCouponFields" class="bg-purple-50 border border-purple-200 rounded-lg p-4">
                        <h3 class="font-medium text-purple-800 mb-3">🎟️ Coupon Code Settings</h3>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Coupon Code *</label>
                                <input v-model="form.coupon_code" type="text" class="w-full px-4 py-2 border rounded-lg uppercase" placeholder="e.g., SAVE20">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Discount Value *</label>
                                <div class="relative">
                                    <input v-model="form.value" type="number" class="w-full px-4 py-2 border rounded-lg" min="0">
                                    <span class="absolute right-3 top-2 text-gray-500">%</span>
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Total Usage Limit</label>
                                <input v-model="form.usage_limit" type="number" class="w-full px-4 py-2 border rounded-lg" min="1" placeholder="Unlimited">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Per User Limit</label>
                                <input v-model="form.per_user_limit" type="number" class="w-full px-4 py-2 border rounded-lg" min="1" placeholder="Unlimited">
                            </div>
                        </div>
                    </div>

                    <!-- Tiered Discount Fields -->
                    <div v-if="showTieredFields" class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                        <h3 class="font-medium text-blue-800 mb-3">📈 Tiered Discount Rules</h3>
                        <p class="text-sm text-blue-600 mb-3">Add discount tiers based on order amount</p>

                        <div v-for="(tier, idx) in form.tier_rules" :key="idx" class="flex gap-2 mb-2 items-end">
                            <div class="flex-1">
                                <label v-if="idx === 0" class="block text-xs font-medium text-gray-600 mb-1">Min Order Amount</label>
                                <div class="relative">
                                    <span class="absolute left-3 top-2 text-gray-500">₹</span>
                                    <input v-model="tier.min_amount" type="number" class="w-full pl-8 pr-4 py-2 border rounded-lg" min="0">
                                </div>
                            </div>
                            <div class="w-32">
                                <label v-if="idx === 0" class="block text-xs font-medium text-gray-600 mb-1">Type</label>
                                <select v-model="tier.discount_type" class="w-full px-3 py-2 border rounded-lg">
                                    <option value="percentage">%</option>
                                    <option value="flat">₹ Flat</option>
                                </select>
                            </div>
                            <div class="w-24">
                                <label v-if="idx === 0" class="block text-xs font-medium text-gray-600 mb-1">Value</label>
                                <input v-model="tier.discount_value" type="number" class="w-full px-3 py-2 border rounded-lg" min="0">
                            </div>
                            <button @click="removeTier(idx)" class="px-3 py-2 text-red-600 hover:bg-red-100 rounded-lg">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </button>
                        </div>
                        <button @click="addTier" class="mt-2 px-4 py-2 text-sm text-blue-600 border border-blue-300 rounded-lg hover:bg-blue-100">
                            + Add Tier
                        </button>
                    </div>

                    <!-- Combo Fields -->
                    <div v-if="showComboFields" class="bg-orange-50 border border-orange-200 rounded-lg p-4">
                        <h3 class="font-medium text-orange-800 mb-3">📦 Combo Deal Settings</h3>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Combo Discount Type</label>
                                <select v-model="form.combo_discount_type" class="w-full px-4 py-2 border rounded-lg">
                                    <option value="percentage">Percentage</option>
                                    <option value="flat">Flat Amount</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Combo Discount Value *</label>
                                <div class="relative">
                                    <input v-model="form.combo_discount_value" type="number" class="w-full px-4 py-2 border rounded-lg" min="0">
                                    <span class="absolute right-3 top-2 text-gray-500">{{ form.combo_discount_type === 'percentage' ? '%' : '₹' }}</span>
                                </div>
                            </div>
                        </div>
                        <p class="text-sm text-orange-600 mt-2">Select the products in the "Applies To" section above to create the combo.</p>
                    </div>

                    <!-- Free Shipping Fields -->
                    <div v-if="showFreeShippingFields" class="bg-teal-50 border border-teal-200 rounded-lg p-4">
                        <h3 class="font-medium text-teal-800 mb-3">🚚 Free Shipping Settings</h3>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Minimum Order Amount for Free Shipping</label>
                            <div class="relative">
                                <span class="absolute left-3 top-2 text-gray-500">₹</span>
                                <input v-model="form.min_order_amount" type="number" class="w-full pl-8 pr-4 py-2 border rounded-lg" min="0" placeholder="0 for all orders">
                            </div>
                        </div>
                        <p class="text-sm text-teal-600 mt-2">Free shipping applies to orders above this amount.</p>
                    </div>

                    <!-- Validity Section -->
                    <div class="border-t pt-4">
                        <h3 class="font-medium text-gray-800 mb-3">📅 Validity Period</h3>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Start Date *</label>
                                <input v-model="form.start_date" type="date" class="w-full px-4 py-2 border rounded-lg">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">End Date *</label>
                                <input v-model="form.end_date" type="date" class="w-full px-4 py-2 border rounded-lg">
                            </div>
                        </div>
                    </div>

                    <!-- Min Order Amount (for non-free-shipping types) -->
                    <div v-if="!showFreeShippingFields">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Minimum Order Amount (Optional)</label>
                        <div class="relative">
                            <span class="absolute left-3 top-2 text-gray-500">₹</span>
                            <input v-model="form.min_order_amount" type="number" class="w-full pl-8 pr-4 py-2 border rounded-lg" min="0" placeholder="No minimum">
                        </div>
                    </div>

                    <!-- Description -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                        <textarea v-model="form.description" rows="3" class="w-full px-4 py-2 border rounded-lg" placeholder="Describe this offer..."></textarea>
                    </div>
                </div>

                <div class="p-6 border-t flex justify-end space-x-3 bg-gray-50">
                    <button @click="showModal = false" class="px-4 py-2 border rounded-lg hover:bg-gray-100">Cancel</button>
                    <button @click="saveOffer" class="px-4 py-2 bg-amber-600 text-white rounded-lg hover:bg-amber-700">
                        {{ editingOffer ? 'Update' : 'Create' }} Offer
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
