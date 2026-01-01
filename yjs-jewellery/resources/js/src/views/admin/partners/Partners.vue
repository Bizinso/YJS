<template>
  <div class="listing_screen global_table_liting">
    <div class="masterTabs">
      <div class="masterTabContent">
        <div v-if="showList">
          <!-- Search and Filter Section -->
          <div class="listing_tab_and_actions mb-3">
            <div class="listing_actions">
              <div class="d-flex align-items-center gap-3">
                <!-- Global Search -->
                <div class="listing_search">
                  <img src="../../assets/img/header/search.svg" class="listing_search_icon" alt="search" />
                  <b-form-input 
                    v-model="search.globalSearch" 
                    @input="debouncedSearch" 
                    placeholder="Search partners..." 
                  />
                </div>
                
                <!-- Filter Button -->
                <b-button 
                  title="filter" 
                  class="btn_listing_action"
                  @click="sidebarstatus.filter = !sidebarstatus.filter"
                >
                  <img src="../../assets/img/filter.svg" alt="filter" /> Filter
                </b-button>
                
                <!-- Add Partner Button -->
                <!-- <b-button 
                  v-if="$can('do', 'add_admin.partners')" 
                  class="fillBTN"
                  @click="addNew"
                >
                  Add Partner
                </b-button> -->
              </div>
              
              <!-- Bulk Actions -->
              <div class="buttonGrid" v-if="selectedItems.length > 0">
                <b-button 
                  class="fillBTN" 
                  @click="openBulkStatusModal"
                  v-b-tooltip.hover title="Change Status"
                >
                  Change Status
                </b-button>
                <b-button 
                  class="transBTN" 
                  @click="openDeleteModal()"
                  v-b-tooltip.hover title="Delete Selected"
                >
                  Delete
                </b-button>
              </div>
            </div>
          </div>

          <!-- Filter Sidebar -->
          <div :class="{ parentBackground: sidebarstatus.filter }">
            <div class="filter_sidebar sidebar_main" :class="[sidebarstatus.filter ? 'filter_active' : '']">
              <div class="sidebar_toolbox p-3">
                <h6>Filter Partners</h6>
                <CloseIcon @click="resetFilter" />
              </div>
              <div class="sidebar_form">
                <b-form>
                  <div class="px-4 py-3 column_sidebar">
                    <!-- Business Type Filter -->
                    <b-form-group label="Business Type">
                      <v-select 
                        v-model="search.business_type" 
                        :options="businessTypeOptions"
                        :reduce="(val) => val.value" 
                        label="label" 
                        :clearable="true"
                        placeholder="Select Business Type" 
                      />
                    </b-form-group>

                    <!-- Status Filter -->
                    <b-form-group label="Status">
                      <v-select 
                        v-model="search.status" 
                        :options="statusOptions"
                        :reduce="(val) => val.value" 
                        label="label" 
                        :clearable="true"
                        placeholder="Select Status" 
                      />
                    </b-form-group>

                    <!-- State Filter -->
                    <b-form-group label="State">
                      <v-select 
                        v-model="search.state" 
                        :options="stateOptions"
                        :reduce="(val) => val.value" 
                        label="label" 
                        :clearable="true"
                        placeholder="Select State" 
                      />
                    </b-form-group>

                    <!-- City Filter -->
                    <b-form-group label="City">
                      <v-select 
                        v-model="search.city" 
                        :options="cityOptions"
                        :reduce="(val) => val.value" 
                        label="label" 
                        :clearable="true"
                        placeholder="Select City" 
                        :disabled="!search.state"
                      />
                    </b-form-group>

                    <!-- Registration Date -->
                    <b-form-group label="Registration Date">
                      <VueDatePicker 
                        v-model="search.registration_date" 
                        :enable-time-picker="false"
                        placeholder="Select Date" 
                        auto-apply 
                        class="breack" 
                      />
                    </b-form-group>
                  </div>
                  
                  <div class="sidebarbtn_group">
                    <b-button type="submit" class="btn_primary me-2" @click="applyFilter">
                      Apply
                    </b-button>
                    <b-button class="btn_secondary_border" @click="resetFilter">
                      Reset
                    </b-button>
                  </div>
                </b-form>
              </div>
            </div>
          </div>

          <!-- Active Filters -->
          <div class="filter_selected px-4" v-if="hasActiveFilters">
            <span class="selected_filter_item_icon me-2">
              <i class="fa-solid fa-sliders"></i>
            </span>
            
            <span 
              v-for="(value, key) in activeFilters" 
              :key="key"
              class="selected_filter_item"
            >
              {{ getFilterLabel(key, value) }}
              <i class="fa-solid fa-xmark" @click="removeFilter(key)"></i>
            </span>
          </div>

          <!-- Loading State -->
          <div v-if="loading" class="text-center p-5">
            <b-spinner variant="primary"></b-spinner>
            <p class="mt-2">Loading partners...</p>
          </div>

          <!-- Data Table -->
          <div v-else>
            <b-table 
              responsive="sm" 
              :items="paginatedData" 
              :fields="fields"
              :sort-by.sync="sortBy"
              :sort-desc.sync="sortDesc"
              @sort-changed="onSortChanged"
              class="mt-3"
            >
              <!-- Select Checkbox -->
              <!-- <template #head(select)>
                <b-form-checkbox 
                  v-model="selectAllChecked" 
                  aria-label="Select all"
                  @change="toggleSelectAll"
                />
              </template>

              <template #cell(select)="row">
                <b-form-checkbox 
                  v-model="selectedItems" 
                  :value="row.item.id"
                  aria-label="Select row"
                />
              </template> -->

              <!-- Business Name -->
              <template #cell(business_name)="row">
                <b-link 
                 :to="`/admin/partners/view/${encodeBase64(row.item.id)}`"
                  class="table_linking"
                >
                  {{ row.item.business_name }}
                </b-link>
              </template>

              <!-- Contact Person -->
              <template #cell(contact_person)="row">
                <div>
                  {{ row.item.contact_person }} 
                  <small class="text-muted d-block">{{ row.item.user?.email }}</small>
                </div>
              </template>

              <!-- Mobile -->
              <template #cell(mobile)="row">
                {{ formatPhoneNumber(row.item.phone_number) }}
              </template>

              <!-- GST Number -->
              <template #cell(gst_number)="row">
                {{ row.item.gst_number || 'N/A' }}
              </template>

              <!-- Status -->
              <template #cell(status)="row">
                <b-badge 
                  :variant="getStatusVariant(row.item.status)"
                  class="cursor-pointer"
                  @click="openStatusModal(row.item)"
                >
                  {{ formatStatus(row.item.status) }}
                </b-badge>
              </template>

              <!-- Registration Date -->
              <template #cell(created_at)="row">
                {{ formatDate(row.item.created_at) }}
              </template>

              <!-- Actions -->
              <template #cell(actions)="row">
                <b-dropdown right variant="link" toggle-class="text-decoration-none p-0" no-caret>
                  <template #button-content>
                    <i class="fas fa-ellipsis-v"></i>
                  </template>
                  
              <b-dropdown-item
                 :to="`/admin/partners/view/${encodeBase64(row.item.id)}`">
                <i class="fas fa-eye me-2"></i> View
                </b-dropdown-item>

                  
                  <!-- <b-dropdown-item @click="editPartner(row.item)" v-if="$can('do', 'edit_admin.partners')">
                    <i class="fas fa-edit me-2"></i> Edit
                  </b-dropdown-item>
                  
                  <b-dropdown-item @click="openDeleteModal(row.item.id)" v-if="$can('do', 'delete_admin.partners')">
                    <i class="fas fa-trash me-2"></i> Delete
                  </b-dropdown-item>
                   -->
                  <b-dropdown-divider />
                  
                  <b-dropdown-item @click="openStatusModal(row.item)">
                    <i class="fas fa-exchange-alt me-2"></i> Change Status
                  </b-dropdown-item>
                </b-dropdown>
              </template>
            </b-table>

            <!-- No Data State -->
            <div v-if="paginatedData.length === 0" class="text-center p-5">
              <div class="mb-3">
                <i class="fas fa-users fa-3x text-muted"></i>
              </div>
              <h5 class="mb-2">No Partners Found</h5>
              <p class="text-muted mb-4">
                {{ hasActiveFilters ? 'Try adjusting your filters' : 'Add your first partner to get started' }}
              </p>
              <!-- <b-button 
                v-if="!hasActiveFilters && $can('do', 'add_admin.partners')"
                variant="primary"
                @click="addNew"
              >
                <i class="fas fa-plus me-2"></i> Add Partner
              </b-button> -->
            </div>

            <!-- Pagination -->
            <div class="tablecounter mt-4" v-if="paginatedData.length > 0">
              <div class="d-flex justify-content-between align-items-center">
                <div class="show_entries">
                  <span class="mr-2">Show</span>
                  <v-select 
                    v-model="perPage" 
                    :options="perPageOptions" 
                    :clearable="false" 
                    style="width: 80px" 
                    @update:modelValue="resetPage"
                  />
                  <span class="ml-2">entries</span>
                </div>
                
                <div class="count">
                  Showing {{ startItem }} to {{ endItem }} of {{ totalItems }}
                </div>
                
                <div>
                  <b-pagination 
                    v-model="currentPage" 
                    :total-rows="totalItems" 
                    :per-page="perPage"
                    align="right"
                    @change="onPageChange"
                  />
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Status Change Modal -->
    <b-modal 
      v-model="showStatusModal" 
      title="Change Status" 
      hide-footer
      centered
      size="md"
    >
      <div class="p-3">
        <p class="mb-3">
          Change status for 
          <strong>{{ selectedPartner?.business_name || selectedPartner?.user?.first_name }}</strong>
        </p>
        
        <v-select 
          v-model="selectedStatus" 
          :options="statusOptions"
          label="label"
          :reduce="(option) => option.value"
          class="mb-3"
          placeholder="Select new status"
          :class="{ 'is-invalid': statusError }"
        />
        <small v-if="statusError" class="text-danger">{{ statusError }}</small>
      </div>
      
      <div class="modal-footer">
        <b-button variant="secondary" @click="showStatusModal = false">
          Cancel
        </b-button>
        <b-button variant="primary" @click="confirmChangeStatus" :disabled="!selectedStatus">
          Update Status
        </b-button>
      </div>
    </b-modal>

    <!-- Delete Modal -->
    <b-modal 
      v-model="deleteModal" 
      title="Confirm Delete"
      hide-footer
      centered
      size="md"
    >
      <div class="p-3 text-center">
        <i class="fas fa-exclamation-triangle fa-3x text-warning mb-3"></i>
        <h5 class="mb-3">
          {{ deleteType === 'single' 
            ? 'Are you sure you want to delete this partner?' 
            : `Are you sure you want to delete ${selectedItems.length} selected partners?` 
          }}
        </h5>
        <p class="text-muted">This action cannot be undone.</p>
      </div>
      
      <div class="modal-footer justify-content-center">
        <b-button variant="secondary" @click="deleteModal = false" class="mr-2">
          Cancel
        </b-button>
        <b-button variant="danger" @click="confirmDelete">
          Delete
        </b-button>
      </div>
    </b-modal>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import axiosEmployee from '@axiosEmployee'
import moment from 'moment'
import CloseIcon from "../../assets/img/icons/Close.vue"
import VueDatePicker from "@vuepic/vue-datepicker"

const router = useRouter()

// Refs
const showList = ref(true)
const loading = ref(false)
const allData = ref([])
const currentPage = ref(1)
const perPage = ref(10)
const selectedItems = ref([])
const sortBy = ref('created_at')
const sortDesc = ref(true)

// Modals
const showStatusModal = ref(false)
const deleteModal = ref(false)
const selectedPartner = ref(null)
const selectedStatus = ref('')
const statusError = ref('')
const deleteType = ref('single')
const deleteId = ref(null)

// Search & Filters
const search = ref({
  globalSearch: '',
  business_type: null,
  status: null,
  state: null,
  city: null,
  registration_date: null
})

// Options
const businessTypeOptions = ref([
  { label: 'Proprietorship', value: 'proprietorship' },
  { label: 'Partnership', value: 'partnership' },
  { label: 'Private Limited', value: 'private_limited' },
  { label: 'LLP', value: 'llp' },
  { label: 'Other', value: 'other' }
])
const statusOptions = ref([
  { label: 'Pending', value: 'pending' },
  { label: 'Approved', value: 'approved' },
  { label: 'Rejected', value: 'rejected' }
])

const stateOptions = ref([])
const cityOptions = ref([])

// Table Fields
const fields = [
//   { key: 'select', label: '', sortable: false, thClass: 'text-center', tdClass: 'text-center' },
  { key: 'business_name', label: 'Business Name', sortable: true },
  { key: 'contact_person', label: 'Contact Person', sortable: false },
  { key: 'mobile', label: 'Mobile', sortable: false },
  { key: 'business_type', label: 'Business Type', sortable: true },
  { key: 'gst_number', label: 'GST No.', sortable: false },
//   { key: 'city', label: 'City', sortable: true },
//   { key: 'state', label: 'State', sortable: true },
  { key: 'status', label: 'Status', sortable: true },
  { key: 'created_at', label: 'Registered On', sortable: true },
  { key: 'actions', label: 'Actions', sortable: false, thClass: 'text-center', tdClass: 'text-center' }
]

// Computed Properties
const totalItems = computed(() => allData.value.length)
const totalPages = computed(() => Math.ceil(totalItems.value / perPage.value))
const startItem = computed(() => (currentPage.value - 1) * perPage.value + 1)
const endItem = computed(() => Math.min(currentPage.value * perPage.value, totalItems.value))

const paginatedData = computed(() => {
  const start = (currentPage.value - 1) * perPage.value
  return allData.value.slice(start, start + perPage.value)
})

const selectAllChecked = computed({
  get: () => paginatedData.value.length > 0 && 
         paginatedData.value.every(item => selectedItems.value.includes(item.id)),
  set: (value) => {
    if (value) {
      selectedItems.value = paginatedData.value.map(item => item.id)
    } else {
      selectedItems.value = []
    }
  }
})

const hasActiveFilters = computed(() => {
  return Object.values(search.value).some(value => 
    value !== null && value !== '' && value !== undefined
  )
})

const activeFilters = computed(() => {
  const filters = {}
  Object.entries(search.value).forEach(([key, value]) => {
    if (value !== null && value !== '' && value !== undefined) {
      filters[key] = value
    }
  })
  return filters
})

// Sidebar
const sidebarstatus = ref({
  filter: false,
  shadow: false
})

// Pagination Options
const perPageOptions = [10, 25, 50, 100]

// Methods
const fetchPartners = async () => {
  loading.value = true
  try {
    const params = {
      page: currentPage.value,
      per_page: perPage.value,
      sort_by: sortBy.value,
      sort_desc: sortDesc.value ? 1 : 0,
      ...search.value
    }

    // Clean up params
    Object.keys(params).forEach(key => {
      if (params[key] === null || params[key] === '') {
        delete params[key]
      }
    })

    const response = await axiosEmployee.get('/partners', { params })
    allData.value = response.data.data || []
    
  } catch (error) {
    console.error('Error fetching partners:', error)
    allData.value = []
  } finally {
    loading.value = false
  }
}
function debounce(func, wait) {
  let timeout
  return function executedFunction(...args) {
    const later = () => {
      clearTimeout(timeout)
      func(...args)
    }
    clearTimeout(timeout)
    timeout = setTimeout(later, wait)
  }
}

// Debounced Search
const debouncedSearch = debounce(() => {
  currentPage.value = 1
  fetchPartners()
}, 500)

const handleSearch = () => {
  debouncedSearch()
}


const applyFilter = () => {
  currentPage.value = 1
  sidebarstatus.value.filter = false
  fetchPartners()
}

const resetFilter = () => {
  search.value = {
    globalSearch: '',
    business_type: null,
    status: null,
    state: null,
    city: null,
    registration_date: null
  }
  cityOptions.value = []
  sidebarstatus.value.filter = false
  fetchPartners()
}

const removeFilter = (key) => {
  if (key === 'state') {
    search.value.city = null
    cityOptions.value = []
  }
  search.value[key] = key === 'globalSearch' ? '' : null
  fetchPartners()
}

const getFilterLabel = (key, value) => {
  const labels = {
    business_type: () => businessTypeOptions.value.find(opt => opt.value === value)?.label || value,
    status: () => statusOptions.value.find(opt => opt.value === value)?.label || value,
    state: () => stateOptions.value.find(opt => opt.value === value)?.label || value,
    city: () => cityOptions.value.find(opt => opt.value === value)?.label || value,
    registration_date: () => formatDate(value),
    globalSearch: () => `Search: ${value}`
  }
  
  return labels[key] ? labels[key]() : value
}

const onSortChanged = (ctx) => {
  sortBy.value = ctx.sortBy
  sortDesc.value = ctx.sortDesc
  fetchPartners()
}

const resetPage = () => {
  currentPage.value = 1
  fetchPartners()
}

const onPageChange = (page) => {
  currentPage.value = page
  fetchPartners()
}

// Partner Actions
const addNew = () => {
//   router.push('/partners/create')
}

const editPartner = (partner) => {
  router.push(`/admin/partners/edit/${encodeBase64(partner.id)}`)
}

const encodeBase64 = (data) => {
  return btoa(data.toString())
}

// Status Management
const openStatusModal = (partner) => {
  selectedPartner.value = partner
  selectedStatus.value = partner.status
  statusError.value = ''
  showStatusModal.value = true
}

const openBulkStatusModal = () => {
  if (selectedItems.value.length === 0) return
  selectedPartner.value = null
  selectedStatus.value = ''
  statusError.value = ''
  showStatusModal.value = true
}

const confirmChangeStatus = async () => {
  if (!selectedStatus.value) {
    statusError.value = 'Please select a status'
    return
  }

  try {
    const ids = selectedPartner.value 
      ? [selectedPartner.value.id] 
      : selectedItems.value

    const response = await axiosEmployee.post('/partners/change-status', {
      ids,
      status: selectedStatus.value
    })

    if (response.data.success) {
      // Show success notification
      fetchPartners()
      showStatusModal.value = false
      selectedItems.value = []
    }
  } catch (error) {
    console.error('Error changing status:', error)
  }
}

// Delete Management
const openDeleteModal = (id = null) => {
  if (id) {
    deleteId.value = id
    deleteType.value = 'single'
  } else {
    deleteType.value = 'multiple'
  }
  deleteModal.value = true
}

const confirmDelete = async () => {
  try {
    const ids = deleteType.value === 'single' 
      ? [deleteId.value] 
      : selectedItems.value

    await axiosEmployee.post('/partners/bulk-delete', { ids })

    deleteModal.value = false
    deleteId.value = null
    selectedItems.value = []
    fetchPartners()

  } catch (error) {
    console.error('Error deleting partners:', error)
  }
}

// Formatting Helpers
const formatPhoneNumber = (phone) => {
  if (!phone) return 'N/A'
  return phone.replace(/(\d{3})(\d{3})(\d{4})/, '$1 $2 $3')
}

const formatDate = (date) => {
  if (!date) return 'N/A'
  return moment(date).format('DD/MM/YYYY')
}

const formatStatus = (status) => {
  const statusMap = {
    active: 'Active',
    inactive: 'Inactive',
    pending: 'Pending',
    suspended: 'Suspended'
  }
  return statusMap[status] || status
}

const getStatusVariant = (status) => {
  const variants = {
    active: 'success',
    inactive: 'secondary',
    pending: 'warning',
    suspended: 'danger'
  }
  return variants[status] || 'light'
}

// Fetch States
const fetchStates = async () => {
  try {
    const response = await axiosEmployee.get('/api/states/101') // 101 for India
    stateOptions.value = response.data.map(state => ({
      label: state.name,
      value: state.id
    }))
  } catch (error) {
    console.error('Error fetching states:', error)
  }
}

// Watch for state change to fetch cities
watch(() => search.value.state, async (stateId) => {
  if (stateId) {
    try {
      const response = await axiosEmployee.get(`/api/cities/${stateId}`)
      cityOptions.value = response.data.map(city => ({
        label: city.name,
        value: city.id
      }))
    } catch (error) {
      console.error('Error fetching cities:', error)
    }
  } else {
    cityOptions.value = []
    search.value.city = null
  }
})

// Lifecycle
onMounted(() => {
  fetchPartners()
//   fetchStates()
})
</script>

<style scoped>
.listing_search {
  position: relative;
  width: 300px;
}

.listing_search_icon {
  position: absolute;
  left: 12px;
  top: 50%;
  transform: translateY(-50%);
  width: 18px;
  height: 18px;
  z-index: 10;
}

.listing_search input {
  padding-left: 40px;
  border-radius: 8px;
  border: 1px solid #ddd;
}

.btn_listing_action {
  background: #fff;
  border: 1px solid #ddd;
  color: #333;
  border-radius: 8px;
  padding: 8px 16px;
}

.btn_listing_action:hover {
  background: #f8f9fa;
  border-color: #ccc;
}

.fillBTN {
  background: #4e54c8;
  border-color: #4e54c8;
  color: white;
  border-radius: 8px;
  padding: 8px 20px;
}

.fillBTN:hover {
  background: #3f44a8;
  border-color: #3f44a8;
}

.transBTN {
  background: transparent;
  border: 1px solid #4e54c8;
  color: #4e54c8;
  border-radius: 8px;
  padding: 8px 20px;
}

.transBTN:hover {
  background: #4e54c8;
  color: white;
}

.table_linking {
  color: #4e54c8;
  text-decoration: none;
  font-weight: 500;
}

.table_linking:hover {
  text-decoration: underline;
  color: #3f44a8;
}

.filter_selected {
  background: #f8f9fa;
  padding: 12px;
  border-radius: 8px;
  margin-bottom: 20px;
}

.selected_filter_item {
  background: white;
  border: 1px solid #ddd;
  border-radius: 20px;
  padding: 4px 12px;
  margin-right: 8px;
  font-size: 14px;
  display: inline-block;
  margin-bottom: 8px;
}

.selected_filter_item i {
  margin-left: 6px;
  cursor: pointer;
  color: #999;
}

.selected_filter_item i:hover {
  color: #dc3545;
}

.selected_filter_item_icon {
  color: #4e54c8;
}

.cursor-pointer {
  cursor: pointer;
}

.tablecounter {
  background: #f8f9fa;
  padding: 15px;
  border-radius: 8px;
  margin-top: 20px;
}

.show_entries {
  display: flex;
  align-items: center;
}
</style>