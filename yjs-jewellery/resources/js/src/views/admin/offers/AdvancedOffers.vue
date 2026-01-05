<template>
  <div class="advanced-offers">
    <div class="container-fluid">
      <!-- Header -->
      <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
          <h2 class="mb-1">Advanced Offers Management</h2>
          <p class="text-muted mb-0">Create and manage all offer types</p>
        </div>
        <button @click="openCreateModal" class="btn btn-primary">
          <i class="bi bi-plus-lg"></i> Create Offer
        </button>
      </div>

      <!-- Stats Cards -->
      <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
          <div class="card stat-card bg-primary text-white">
            <div class="card-body">
              <h3 class="mb-0">{{ stats.active_offers }}</h3>
              <small>Active Offers</small>
            </div>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="card stat-card bg-success text-white">
            <div class="card-body">
              <h3 class="mb-0">{{ stats.total_usage }}</h3>
              <small>Total Usage</small>
            </div>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="card stat-card bg-warning text-dark">
            <div class="card-body">
              <h3 class="mb-0">₹{{ formatNumber(stats.total_discount_given) }}</h3>
              <small>Total Discounts</small>
            </div>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="card stat-card bg-info text-white">
            <div class="card-body">
              <h3 class="mb-0">{{ stats.flash_sales_active }}</h3>
              <small>Flash Sales Active</small>
            </div>
          </div>
        </div>
      </div>

      <!-- Offer Type Tabs -->
      <ul class="nav nav-tabs mb-4">
        <li class="nav-item" v-for="tab in tabs" :key="tab.value">
          <button
            @click="activeTab = tab.value"
            :class="['nav-link', activeTab === tab.value ? 'active' : '']"
          >
            <i :class="tab.icon"></i> {{ tab.label }}
          </button>
        </li>
      </ul>

      <!-- Loading -->
      <div v-if="loading" class="text-center py-5">
        <div class="spinner-border text-primary"></div>
      </div>

      <!-- Offers Table -->
      <div v-else class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
          <div class="d-flex gap-2">
            <select v-model="filters.status" class="form-select form-select-sm" @change="fetchOffers">
              <option value="">All Status</option>
              <option value="active">Active</option>
              <option value="scheduled">Scheduled</option>
              <option value="expired">Expired</option>
            </select>
            <select v-model="filters.type" class="form-select form-select-sm" @change="fetchOffers">
              <option value="">All Types</option>
              <option v-for="type in offerTypes" :key="type.id" :value="type.id">
                {{ type.name }}
              </option>
            </select>
          </div>
          <div class="input-group input-group-sm" style="width: 250px;">
            <input
              v-model="searchQuery"
              type="text"
              class="form-control"
              placeholder="Search offers..."
              @input="debounceSearch"
            />
            <button class="btn btn-outline-secondary" type="button">
              <i class="bi bi-search"></i>
            </button>
          </div>
        </div>
        <div class="table-responsive">
          <table class="table table-hover mb-0">
            <thead>
              <tr>
                <th>Offer</th>
                <th>Type</th>
                <th>Discount</th>
                <th>Duration</th>
                <th>Usage</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="offer in filteredOffers" :key="offer.id">
                <td>
                  <div class="d-flex align-items-center">
                    <div>
                      <strong>{{ offer.name }}</strong>
                      <small class="d-block text-muted">{{ offer.coupon_code || '-' }}</small>
                    </div>
                  </div>
                </td>
                <td>
                  <span :class="['badge', getTypeBadge(offer)]">
                    {{ getTypeLabel(offer) }}
                  </span>
                </td>
                <td>
                  <strong>
                    {{ offer.discount_value }}{{ offer.discount_type === 'percentage' ? '%' : '₹' }}
                  </strong>
                  <small v-if="offer.max_discount_amount" class="d-block text-muted">
                    Max: ₹{{ offer.max_discount_amount }}
                  </small>
                </td>
                <td>
                  <small>
                    {{ formatDate(offer.start_date) }}<br />
                    to {{ formatDate(offer.end_date) }}
                  </small>
                </td>
                <td>
                  {{ offer.usage_count || 0 }}
                  <small v-if="offer.usage_limit" class="text-muted">/ {{ offer.usage_limit }}</small>
                </td>
                <td>
                  <span :class="['badge', getStatusBadge(offer)]">
                    {{ getStatus(offer) }}
                  </span>
                </td>
                <td>
                  <div class="btn-group btn-group-sm">
                    <button @click="editOffer(offer)" class="btn btn-outline-primary" title="Edit">
                      <i class="bi bi-pencil"></i>
                    </button>
                    <button @click="duplicateOffer(offer)" class="btn btn-outline-secondary" title="Duplicate">
                      <i class="bi bi-copy"></i>
                    </button>
                    <button
                      @click="toggleStatus(offer)"
                      :class="['btn', offer.is_active ? 'btn-outline-warning' : 'btn-outline-success']"
                      :title="offer.is_active ? 'Deactivate' : 'Activate'"
                    >
                      <i :class="['bi', offer.is_active ? 'bi-pause' : 'bi-play']"></i>
                    </button>
                    <button @click="deleteOffer(offer)" class="btn btn-outline-danger" title="Delete">
                      <i class="bi bi-trash"></i>
                    </button>
                  </div>
                </td>
              </tr>
              <tr v-if="filteredOffers.length === 0">
                <td colspan="7" class="text-center py-4 text-muted">
                  No offers found
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <!-- Pagination -->
        <div v-if="pagination.total > pagination.per_page" class="card-footer">
          <nav>
            <ul class="pagination pagination-sm mb-0 justify-content-center">
              <li :class="['page-item', pagination.current_page === 1 ? 'disabled' : '']">
                <button class="page-link" @click="goToPage(pagination.current_page - 1)">
                  Previous
                </button>
              </li>
              <li
                v-for="page in visiblePages"
                :key="page"
                :class="['page-item', pagination.current_page === page ? 'active' : '']"
              >
                <button class="page-link" @click="goToPage(page)">{{ page }}</button>
              </li>
              <li :class="['page-item', pagination.current_page === pagination.last_page ? 'disabled' : '']">
                <button class="page-link" @click="goToPage(pagination.current_page + 1)">
                  Next
                </button>
              </li>
            </ul>
          </nav>
        </div>
      </div>

      <!-- Create/Edit Modal -->
      <div class="modal fade" id="offerModal" tabindex="-1" ref="offerModalRef">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">{{ editingOffer ? 'Edit Offer' : 'Create New Offer' }}</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
              <!-- Offer Type Selection -->
              <div class="mb-4" v-if="!editingOffer">
                <label class="form-label fw-bold">Select Offer Type</label>
                <div class="row g-2">
                  <div v-for="type in quickOfferTypes" :key="type.value" class="col-6 col-md-4">
                    <button
                      @click="selectOfferType(type.value)"
                      :class="['btn w-100 text-start', form.offer_category === type.value ? 'btn-primary' : 'btn-outline-secondary']"
                    >
                      <i :class="type.icon"></i> {{ type.label }}
                    </button>
                  </div>
                </div>
              </div>

              <!-- Basic Info -->
              <div class="row g-3 mb-4">
                <div class="col-12">
                  <label class="form-label">Offer Name *</label>
                  <input v-model="form.name" type="text" class="form-control" />
                </div>
                <div class="col-12">
                  <label class="form-label">Description</label>
                  <textarea v-model="form.description" class="form-control" rows="2"></textarea>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Coupon Code</label>
                  <input v-model="form.coupon_code" type="text" class="form-control text-uppercase" />
                </div>
                <div class="col-md-6">
                  <label class="form-label">Offer Type *</label>
                  <select v-model="form.offer_type_id" class="form-select">
                    <option value="">Select type</option>
                    <option v-for="type in offerTypes" :key="type.id" :value="type.id">
                      {{ type.name }}
                    </option>
                  </select>
                </div>
              </div>

              <!-- Discount Settings -->
              <div class="card mb-4">
                <div class="card-header">
                  <h6 class="mb-0">Discount Settings</h6>
                </div>
                <div class="card-body">
                  <div class="row g-3">
                    <div class="col-md-4">
                      <label class="form-label">Discount Type *</label>
                      <select v-model="form.discount_type" class="form-select">
                        <option value="percentage">Percentage</option>
                        <option value="flat">Flat Amount</option>
                      </select>
                    </div>
                    <div class="col-md-4">
                      <label class="form-label">Discount Value *</label>
                      <input v-model.number="form.discount_value" type="number" class="form-control" />
                    </div>
                    <div class="col-md-4">
                      <label class="form-label">Max Discount</label>
                      <input v-model.number="form.max_discount_amount" type="number" class="form-control" placeholder="Optional" />
                    </div>
                  </div>
                </div>
              </div>

              <!-- BOGO Settings -->
              <div v-if="form.offer_category === 'bogo'" class="card mb-4">
                <div class="card-header bg-success text-white">
                  <h6 class="mb-0">Buy One Get One Settings</h6>
                </div>
                <div class="card-body">
                  <div class="row g-3">
                    <div class="col-md-6">
                      <label class="form-label">Buy Quantity *</label>
                      <input v-model.number="form.buy_quantity" type="number" class="form-control" min="1" />
                    </div>
                    <div class="col-md-6">
                      <label class="form-label">Get Quantity *</label>
                      <input v-model.number="form.get_quantity" type="number" class="form-control" min="1" />
                    </div>
                    <div class="col-md-6">
                      <label class="form-label">Get Discount %</label>
                      <input v-model.number="form.get_discount_percentage" type="number" class="form-control" min="0" max="100" placeholder="100 = FREE" />
                    </div>
                  </div>
                </div>
              </div>

              <!-- Flash Sale Settings -->
              <div v-if="form.offer_category === 'flash'" class="card mb-4">
                <div class="card-header bg-danger text-white">
                  <h6 class="mb-0">Flash Sale Settings</h6>
                </div>
                <div class="card-body">
                  <div class="row g-3">
                    <div class="col-md-6">
                      <label class="form-label">Flash Sale Quantity</label>
                      <input v-model.number="form.flash_sale_quantity" type="number" class="form-control" min="1" />
                    </div>
                    <div class="col-md-6">
                      <div class="form-check mt-4">
                        <input v-model="form.is_flash_sale" type="checkbox" class="form-check-input" id="isFlashSale" />
                        <label class="form-check-label" for="isFlashSale">Mark as Flash Sale</label>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Tiered Discount Settings -->
              <div v-if="form.offer_category === 'tiered'" class="card mb-4">
                <div class="card-header bg-purple text-white">
                  <h6 class="mb-0">Tiered Discount Rules</h6>
                </div>
                <div class="card-body">
                  <div v-for="(tier, idx) in form.tier_rules" :key="idx" class="row g-2 mb-2 align-items-end">
                    <div class="col-4">
                      <label v-if="idx === 0" class="form-label">Min Amount</label>
                      <input v-model.number="tier.min_amount" type="number" class="form-control" placeholder="Min ₹" />
                    </div>
                    <div class="col-3">
                      <label v-if="idx === 0" class="form-label">Type</label>
                      <select v-model="tier.discount_type" class="form-select">
                        <option value="percentage">%</option>
                        <option value="flat">₹</option>
                      </select>
                    </div>
                    <div class="col-3">
                      <label v-if="idx === 0" class="form-label">Value</label>
                      <input v-model.number="tier.discount_value" type="number" class="form-control" />
                    </div>
                    <div class="col-2">
                      <button @click="removeTier(idx)" class="btn btn-outline-danger btn-sm">
                        <i class="bi bi-trash"></i>
                      </button>
                    </div>
                  </div>
                  <button @click="addTier" class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-plus"></i> Add Tier
                  </button>
                </div>
              </div>

              <!-- Validity -->
              <div class="card mb-4">
                <div class="card-header">
                  <h6 class="mb-0">Validity & Limits</h6>
                </div>
                <div class="card-body">
                  <div class="row g-3">
                    <div class="col-md-6">
                      <label class="form-label">Start Date *</label>
                      <input v-model="form.start_date" type="datetime-local" class="form-control" />
                    </div>
                    <div class="col-md-6">
                      <label class="form-label">End Date *</label>
                      <input v-model="form.end_date" type="datetime-local" class="form-control" />
                    </div>
                    <div class="col-md-4">
                      <label class="form-label">Min Order Amount</label>
                      <input v-model.number="form.min_order_amount" type="number" class="form-control" />
                    </div>
                    <div class="col-md-4">
                      <label class="form-label">Usage Limit</label>
                      <input v-model.number="form.usage_limit" type="number" class="form-control" placeholder="Unlimited" />
                    </div>
                    <div class="col-md-4">
                      <label class="form-label">Per User Limit</label>
                      <input v-model.number="form.per_user_limit" type="number" class="form-control" placeholder="Unlimited" />
                    </div>
                  </div>
                </div>
              </div>

              <!-- Status -->
              <div class="d-flex gap-3">
                <div class="form-check">
                  <input v-model="form.is_active" type="checkbox" class="form-check-input" id="isActive" />
                  <label class="form-check-label" for="isActive">Active</label>
                </div>
                <div class="form-check">
                  <input v-model="form.is_stackable" type="checkbox" class="form-check-input" id="isStackable" />
                  <label class="form-check-label" for="isStackable">Stackable with other offers</label>
                </div>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
              <button @click="saveOffer" class="btn btn-primary" :disabled="saving">
                <span v-if="saving" class="spinner-border spinner-border-sm me-1"></span>
                {{ editingOffer ? 'Update' : 'Create' }} Offer
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { Modal } from 'bootstrap'
import axiosEmployee from '@axiosEmployee'
import { toast } from "vue3-toastify"

// State
const loading = ref(true)
const saving = ref(false)
const offers = ref([])
const offerTypes = ref([])
const editingOffer = ref(null)
const searchQuery = ref('')
const activeTab = ref('all')
const offerModalRef = ref(null)
let offerModal = null
let searchTimeout = null

const stats = ref({
  active_offers: 0,
  total_usage: 0,
  total_discount_given: 0,
  flash_sales_active: 0
})

const filters = reactive({
  status: '',
  type: ''
})

const pagination = reactive({
  current_page: 1,
  last_page: 1,
  per_page: 20,
  total: 0
})

const tabs = [
  { label: 'All', value: 'all', icon: 'bi bi-grid' },
  { label: 'BOGO', value: 'bogo', icon: 'bi bi-bag-plus' },
  { label: 'Flash Sales', value: 'flash', icon: 'bi bi-lightning' },
  { label: 'Combos', value: 'combo', icon: 'bi bi-collection' },
  { label: 'Tiered', value: 'tiered', icon: 'bi bi-graph-up' },
  { label: 'Coupons', value: 'coupon', icon: 'bi bi-ticket' }
]

const quickOfferTypes = [
  { label: 'Percentage Off', value: 'percentage', icon: 'bi bi-percent' },
  { label: 'Flat Discount', value: 'flat', icon: 'bi bi-currency-rupee' },
  { label: 'BOGO', value: 'bogo', icon: 'bi bi-bag-plus' },
  { label: 'Flash Sale', value: 'flash', icon: 'bi bi-lightning' },
  { label: 'Tiered Discount', value: 'tiered', icon: 'bi bi-graph-up' },
  { label: 'Free Gift', value: 'gift', icon: 'bi bi-gift' }
]

// Form
const defaultForm = {
  name: '',
  description: '',
  coupon_code: '',
  offer_type_id: '',
  offer_category: '',
  discount_type: 'percentage',
  discount_value: 10,
  max_discount_amount: null,
  min_order_amount: null,
  start_date: '',
  end_date: '',
  usage_limit: null,
  per_user_limit: null,
  is_active: true,
  is_stackable: false,
  // BOGO
  buy_quantity: 1,
  get_quantity: 1,
  get_discount_percentage: 100,
  // Flash Sale
  is_flash_sale: false,
  flash_sale_quantity: null,
  // Tiered
  tier_rules: []
}

const form = reactive({ ...defaultForm })

// Computed
const filteredOffers = computed(() => {
  let result = offers.value

  if (activeTab.value !== 'all') {
    result = result.filter(o => {
      if (activeTab.value === 'bogo') return o.is_bogo
      if (activeTab.value === 'flash') return o.is_flash_sale
      if (activeTab.value === 'combo') return o.is_combo
      if (activeTab.value === 'tiered') return o.tier_rules && o.tier_rules.length > 0
      if (activeTab.value === 'coupon') return o.coupon_code
      return true
    })
  }

  return result
})

const visiblePages = computed(() => {
  const pages = []
  const current = pagination.current_page
  const last = pagination.last_page
  const start = Math.max(1, current - 2)
  const end = Math.min(last, current + 2)
  for (let i = start; i <= end; i++) {
    pages.push(i)
  }
  return pages
})

// Methods
async function fetchOffers() {
  loading.value = true
  try {
    const params = new URLSearchParams({
      page: pagination.current_page,
      per_page: pagination.per_page,
      ...(filters.status && { status: filters.status }),
      ...(filters.type && { offer_type_id: filters.type }),
      ...(searchQuery.value && { search: searchQuery.value })
    })

    const { data } = await axiosEmployee.get(`/admin/advanced-offers?${params}`)
    if (data.success) {
      offers.value = data.offers || []
      pagination.current_page = data.pagination?.current_page || 1
      pagination.last_page = data.pagination?.last_page || 1
      pagination.total = data.pagination?.total || 0
    }
  } catch (error) {
    console.error('Failed to fetch offers:', error)
    toast.error('Failed to load offers')
  } finally {
    loading.value = false
  }
}

async function fetchOfferTypes() {
  try {
    const { data } = await axiosEmployee.get('/admin/advanced-offers/types')
    if (data.success) {
      offerTypes.value = data.offer_types || []
    }
  } catch (error) {
    console.error('Failed to fetch offer types:', error)
  }
}

async function fetchStats() {
  try {
    const { data } = await axiosEmployee.get('/admin/advanced-offers/analytics')
    if (data.success) {
      stats.value = {
        active_offers: data.analytics?.active_offers || 0,
        total_usage: data.analytics?.total_usage || 0,
        total_discount_given: data.analytics?.total_discount_given || 0,
        flash_sales_active: data.analytics?.flash_sales_active || 0
      }
    }
  } catch (error) {
    console.error('Failed to fetch stats:', error)
  }
}

function openCreateModal() {
  editingOffer.value = null
  Object.assign(form, { ...defaultForm, tier_rules: [] })
  if (!offerModal) {
    offerModal = new Modal(offerModalRef.value)
  }
  offerModal.show()
}

function editOffer(offer) {
  editingOffer.value = offer
  Object.assign(form, {
    ...defaultForm,
    ...offer,
    start_date: offer.start_date ? offer.start_date.slice(0, 16) : '',
    end_date: offer.end_date ? offer.end_date.slice(0, 16) : '',
    tier_rules: offer.tier_rules || []
  })
  if (!offerModal) {
    offerModal = new Modal(offerModalRef.value)
  }
  offerModal.show()
}

async function saveOffer() {
  if (!form.name || !form.discount_value || !form.start_date || !form.end_date) {
    toast.error('Please fill in all required fields')
    return
  }

  saving.value = true
  try {
    const payload = { ...form }

    let response
    if (editingOffer.value) {
      response = await axiosEmployee.put(`/admin/advanced-offers/${editingOffer.value.id}`, payload)
    } else {
      response = await axiosEmployee.post('/admin/advanced-offers', payload)
    }

    if (response.data.success) {
      toast.success(editingOffer.value ? 'Offer updated!' : 'Offer created!')
      offerModal.hide()
      fetchOffers()
      fetchStats()
    }
  } catch (error) {
    console.error('Failed to save offer:', error)
    toast.error(error.response?.data?.error || 'Failed to save offer')
  } finally {
    saving.value = false
  }
}

async function duplicateOffer(offer) {
  try {
    const { data } = await axiosEmployee.post(`/admin/advanced-offers/${offer.id}/duplicate`)
    if (data.success) {
      toast.success('Offer duplicated!')
      fetchOffers()
    }
  } catch (error) {
    toast.error('Failed to duplicate offer')
  }
}

async function toggleStatus(offer) {
  try {
    const { data } = await axiosEmployee.put(`/admin/advanced-offers/${offer.id}`, {
      is_active: !offer.is_active
    })
    if (data.success) {
      offer.is_active = !offer.is_active
      toast.success(`Offer ${offer.is_active ? 'activated' : 'deactivated'}`)
    }
  } catch (error) {
    toast.error('Failed to update status')
  }
}

async function deleteOffer(offer) {
  if (!confirm(`Delete "${offer.name}"?`)) return

  try {
    await axiosEmployee.delete(`/admin/advanced-offers/${offer.id}`)
    toast.success('Offer deleted!')
    fetchOffers()
    fetchStats()
  } catch (error) {
    toast.error('Failed to delete offer')
  }
}

function selectOfferType(type) {
  form.offer_category = type
  if (type === 'bogo') {
    form.buy_quantity = 1
    form.get_quantity = 1
    form.get_discount_percentage = 100
  } else if (type === 'flash') {
    form.is_flash_sale = true
  } else if (type === 'tiered') {
    form.tier_rules = [{ min_amount: 1000, discount_type: 'percentage', discount_value: 5 }]
  }
}

function addTier() {
  form.tier_rules.push({ min_amount: 0, discount_type: 'percentage', discount_value: 0 })
}

function removeTier(idx) {
  form.tier_rules.splice(idx, 1)
}

function goToPage(page) {
  if (page < 1 || page > pagination.last_page) return
  pagination.current_page = page
  fetchOffers()
}

function debounceSearch() {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    pagination.current_page = 1
    fetchOffers()
  }, 300)
}

// Helpers
function formatNumber(num) {
  return Number(num || 0).toLocaleString()
}

function formatDate(dateStr) {
  if (!dateStr) return '-'
  return new Date(dateStr).toLocaleDateString('en-IN', {
    day: 'numeric',
    month: 'short'
  })
}

function getTypeLabel(offer) {
  if (offer.is_bogo) return 'BOGO'
  if (offer.is_flash_sale) return 'Flash Sale'
  if (offer.is_combo) return 'Combo'
  if (offer.tier_rules?.length > 0) return 'Tiered'
  if (offer.coupon_code) return 'Coupon'
  return offer.discount_type === 'percentage' ? '% Off' : 'Flat'
}

function getTypeBadge(offer) {
  if (offer.is_bogo) return 'bg-success'
  if (offer.is_flash_sale) return 'bg-danger'
  if (offer.is_combo) return 'bg-info'
  if (offer.tier_rules?.length > 0) return 'bg-purple'
  return 'bg-warning text-dark'
}

function getStatus(offer) {
  const now = new Date()
  const start = new Date(offer.start_date)
  const end = new Date(offer.end_date)
  if (!offer.is_active) return 'Inactive'
  if (now < start) return 'Scheduled'
  if (now > end) return 'Expired'
  return 'Active'
}

function getStatusBadge(offer) {
  const status = getStatus(offer)
  const badges = {
    Active: 'bg-success',
    Scheduled: 'bg-info',
    Expired: 'bg-secondary',
    Inactive: 'bg-warning text-dark'
  }
  return badges[status] || 'bg-secondary'
}

onMounted(() => {
  fetchOffers()
  fetchOfferTypes()
  fetchStats()
})
</script>

<style scoped>
.stat-card {
  border: none;
  border-radius: 10px;
}

.bg-purple {
  background-color: #6f42c1 !important;
}

.nav-tabs .nav-link {
  border: none;
  color: #6c757d;
  padding: 0.75rem 1rem;
}

.nav-tabs .nav-link.active {
  color: #0d6efd;
  border-bottom: 2px solid #0d6efd;
  background: transparent;
}

.nav-tabs .nav-link:hover {
  border-color: transparent;
}
</style>
