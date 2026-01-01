<template>
  <div>
    <!-- Desktop Filters -->
    <div class="productListFilters d-none d-md-flex">
      <div 
        class="productListFiltersDropdown" 
        v-for="filter in filterSections" 
        :key="filter.key"
        :ref="(el) => dropdownRefs.set(filter.key, el)"
      >
        <button @click.stop="toggleDropdown(filter.key)" class="productListFiltersBtn">
          {{ filter.label }}
          <span class="badgeCount" v-if="selectedFilters[filter.key].length">
            {{ selectedFilters[filter.key].length }}
          </span>
          <img src="../assets/images/static/product/landing/arrow_drop_down.svg" alt="Dropdown Arrow" class="imageFrame" />
        </button>
        
        <div class="dropdown-list" v-if="dropdowns[filter.key]">
          <!-- Price Range Filter -->
          <div v-if="filter.key === 'priceRange'">
            <label
              v-for="priceRange in priceRangeOptions"
              :key="priceRange.value"
              class="d-block mb-2"
            >
              <input
                type="checkbox"
                :value="priceRange.value"
                v-model="selectedFilters.priceRange"
                @change="emitFilters"
              />
              {{ priceRange.label }}
            </label>
          </div>

          <!-- Purity Filter -->
          <div v-else-if="filter.key === 'purity'">
            <label
              v-for="purity in purityOptions"
              :key="purity.id"
              class="d-block mb-2"
            >
              <input
                type="checkbox"
                :value="purity.id"
                v-model="selectedFilters.purity"
                @change="emitFilters"
              />
              {{ purity.name }}
            </label>
          </div>

          <!-- Products (Categories) Filter -->
          <div v-else-if="filter.key === 'products'">
            <div
              v-for="product in productOptions"
              :key="product.value"
              class="category-row position-relative"
              @mouseenter="onProductHover(product.value)"
              @mouseleave="scheduleHide(product.value)"
            >
              <span class="category-label">
                {{ product.label }}
              </span>

              <!-- Subcategory popup -->
              <div
                v-show="hoveredProduct === product.value"
                class="subcategory-popup"
                @mouseenter="cancelHide(product.value)"
                @mouseleave="scheduleHide(product.value)"
              >
                <div class="subcategory-header">{{ product.label }}</div>

                <label class="d-block sub-option">
                  <input
                    type="checkbox"
                    :checked="isAllSubcategoriesSelected(product.value)"
                    @change="(e) => toggleAllSubcategories(product.value, e.target.checked)"
                  />
                  All
                </label>

                <label
                  v-for="sub in subCategoryOptions[product.value]"
                  :key="sub.value"
                  class="d-block sub-option"
                >
                  <input
                    type="checkbox"
                    :value="sub.value"
                    v-model="selectedFilters.subcategories"
                    @change="emitFilters"
                  />
                  {{ sub.label }}
                </label>
              </div>
            </div>
          </div>

          <!-- Gender Filter -->
          <div v-else-if="filter.key === 'gender'">
            <label
              v-for="gender in genderOptions"
              :key="gender.value"
              class="d-block mb-2"
            >
              <input
                type="checkbox"
                :value="gender.value"
                v-model="selectedFilters.gender"
                @change="emitFilters"
              />
              {{ gender.label }}
            </label>
          </div>

          <!-- Occasion Filter -->
          <div v-else-if="filter.key === 'occasion'">
            <label
              v-for="occasion in occasionOptions"
              :key="occasion.value"
              class="d-block mb-2"
            >
              <input
                type="checkbox"
                :value="occasion.value"
                v-model="selectedFilters.occasion"
                @change="emitFilters"
              />
              {{ occasion.label }}
            </label>
          </div>
        </div>
      </div>
    </div>

    <!-- Applied Filters Bar -->
    <div class="appliedFiltersBar" v-if="hasAnyFilterApplied">
      <div class="appliedFiltersRow">
        <span class="appliedChip" v-for="chip in appliedFilterChips" :key="`${chip.key}-${chip.value}`">
          {{ chip.display }}
          <button 
            type="button" 
            aria-label="Remove filter" 
            class="chipClose"
            @click.stop="removeFilter(chip.key, chip.value)"
          >
            ×
          </button>
        </span>
      </div>
      <button class="resetFiltersBtn" @click="resetFilters">
        Reset Filters
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, onUnmounted } from 'vue'

// Props
const props = defineProps({
  filterOptions: {
    type: Object,
    required: true
  },
  initialFilters: {
    type: Object,
    default: () => ({})
  }
})

// Emits
const emit = defineEmits(['filter-change', 'reset-filters'])

// Filter options from parent
const {
  priceRangeOptions,
  purityOptions,
  productOptions,
  genderOptions,
  occasionOptions,
  subCategoryOptions
} = props.filterOptions

// Filter sections
const filterSections = [
  { key: "priceRange", label: "Price Range" },
  { key: "purity", label: "Purity" },
  { key: "products", label: "Products" },
  { key: "gender", label: "Gender" },
  { key: "occasion", label: "Occasion" },
]

// Selected filters
const selectedFilters = reactive({
  priceRange: [],
  purity: [],
  products: [],
  subcategories: [],
  gender: [],
  occasion: [],
})

// Initialize with props if provided
if (props.initialFilters) {
  Object.keys(props.initialFilters).forEach(key => {
    if (selectedFilters[key] !== undefined) {
      selectedFilters[key] = [...props.initialFilters[key]]
    }
  })
}

// Dropdown management
const dropdowns = reactive({
  priceRange: false,
  purity: false,
  products: false,
  gender: false,
  occasion: false,
})

const dropdownRefs = new Map()

function toggleDropdown(key) {
  const wasOpen = dropdowns[key]
  for (const k in dropdowns) dropdowns[k] = false
  dropdowns[key] = !wasOpen
}

function handleClickOutside(event) {
  let clickedInsideDropdown = false
  dropdownRefs.forEach((el) => {
    if (el?.contains(event.target)) clickedInsideDropdown = true
  })
  if (!clickedInsideDropdown) {
    for (const k in dropdowns) dropdowns[k] = false
  }
}

// Subcategory management
const hoveredProduct = ref(null)
const hideTimers = ref({})

function onProductHover(productId) {
  hoveredProduct.value = productId
  cancelHide(productId)
  
  // Emit event to parent to fetch subcategories if needed
  if (!subCategoryOptions.value?.[productId]) {
    emit('fetch-subcategories', productId)
  }
}

function scheduleHide(productId) {
  clearTimeout(hideTimers.value[productId])
  hideTimers.value[productId] = setTimeout(() => {
    hoveredProduct.value = null
  }, 300)
}

function cancelHide(productId) {
  clearTimeout(hideTimers.value[productId])
}

function isAllSubcategoriesSelected(categoryId) {
  const subcats = subCategoryOptions.value?.[categoryId] || []
  return subcats.length > 0 && subcats.every(sub => 
    selectedFilters.subcategories.includes(sub.value)
  )
}

function toggleAllSubcategories(categoryId, checked) {
  const subcats = subCategoryOptions.value?.[categoryId] || []
  
  if (checked) {
    // Add all subcategories
    subcats.forEach(sub => {
      if (!selectedFilters.subcategories.includes(sub.value)) {
        selectedFilters.subcategories.push(sub.value)
      }
    })
  } else {
    // Remove all subcategories of this category
    selectedFilters.subcategories = selectedFilters.subcategories.filter(
      subId => !subcats.some(sub => sub.value == subId)
    )
  }
  
  emitFilters()
}

// Computed properties
const hasAnyFilterApplied = computed(() => {
  return Object.values(selectedFilters).some(arr => arr.length > 0)
})

const appliedFilterChips = computed(() => {
  const chips = []
  
  // Price Range chips
  selectedFilters.priceRange.forEach(value => {
    const option = priceRangeOptions.value.find(opt => opt.value === value)
    if (option) chips.push({ key: 'priceRange', value, display: option.label })
  })
  
  // Purity chips
  selectedFilters.purity.forEach(value => {
    const option = purityOptions.value.find(opt => opt.id == value)
    if (option) chips.push({ key: 'purity', value, display: option.name })
  })
  
  // Category chips
  selectedFilters.products.forEach(value => {
    const option = productOptions.value.find(opt => opt.value == value)
    if (option) chips.push({ key: 'products', value, display: option.label })
  })
  
  // Subcategory chips
  selectedFilters.subcategories.forEach(value => {
    let label = `Subcategory ${value}`
    // Find label from subCategoryOptions
    for (const catId in subCategoryOptions.value) {
      const sub = subCategoryOptions.value[catId]?.find(s => s.value == value)
      if (sub) {
        label = sub.label
        break
      }
    }
    chips.push({ key: 'subcategories', value, display: label })
  })
  
  // Gender chips
  selectedFilters.gender.forEach(value => {
    const option = genderOptions.value.find(opt => opt.value === value)
    if (option) chips.push({ key: 'gender', value, display: option.label })
  })
  
  // Occasion chips
  selectedFilters.occasion.forEach(value => {
    const option = occasionOptions.value.find(opt => opt.value == value)
    if (option) chips.push({ key: 'occasion', value, display: option.label })
  })
  
  return chips
})

// Methods
function emitFilters() {
  // Close all dropdowns
  for (const k in dropdowns) dropdowns[k] = false
  
  // Emit to parent
  emit('filter-change', { ...selectedFilters })
}

function removeFilter(filterKey, value) {
  const index = selectedFilters[filterKey].indexOf(value)
  if (index > -1) {
    selectedFilters[filterKey].splice(index, 1)
    emitFilters()
  }
}

function resetFilters() {
  // Reset all filters
  Object.keys(selectedFilters).forEach(key => {
    selectedFilters[key] = []
  })
  
  // Close all dropdowns
  for (const k in dropdowns) dropdowns[k] = false
  
  // Emit reset event
  emit('reset-filters')
}

// Lifecycle
onMounted(() => {
  document.addEventListener('click', handleClickOutside)
})

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
})

// Expose methods to parent
defineExpose({
  selectedFilters,
  resetFilters,
  removeFilter
})
</script>

<style scoped>
/* Copy all filter-related CSS from original component here */
.dropdown-list {
  border: 1px solid #ccc;
  padding: 10px;
  background-color: white;
  position: absolute;
  z-index: 1000;
}

.category-row {
  position: relative;
  padding: 6px 8px;
}

.category-label {
  display: inline-block;
  color: #111827;
}

.productListFiltersBtn .badgeCount {
  display: none;
}

.appliedFiltersBar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  flex-wrap: wrap;
  margin-top: 12px;
}

.appliedFiltersRow {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
}

.appliedChip {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  background: #f2f4f7;
  border: 1px solid #d0d5dd;
  color: #344054;
  border-radius: 16px;
  padding: 6px 10px;
  font-size: 12px;
}

.appliedChip .chipClose {
  background: transparent;
  border: none;
  color: #667085;
  font-size: 14px;
  line-height: 1;
  cursor: pointer;
}

.resetFiltersBtn {
  color: #ffffff;
  border-radius: 4px;
  border: 1px solid var(--GREYS-G-4, #FDC25C);
  background: var(--PRIMARY-COLORS-P---2, #FDC25C);
  display: flex;
  padding: 4px 20px;
  justify-content: center;
  align-items: center;
  gap: 4px;
  text-align: center;
  font-family: DM Sans;
  font-size: 14px;
  font-style: normal;
  font-weight: 400;
  line-height: 160%;
  height: 100%;
}

.resetFiltersBtn:hover {
  background: #B44536;
}

@media (max-width:786px) {
  .appliedFiltersBar {
    flex-direction: column;
    align-items: flex-start;
  }

  .resetFiltersBtn {
    width: 100%;
  }
}

.subcategory-popup {
  position: absolute;
  top: 0;
  left: 100%;
  padding-left: 8px;
  background: #fff;
  border: 1px solid #ddd;
  border-radius: 6px;
  padding: 8px 12px;
  z-index: 999;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.subcategory-header {
  font-weight: 600;
  margin-bottom: 8px;
}

.sub-option {
  padding: 4px 0 !important;
}
</style>