<template>
  <div class="product-listing-page">
    <div class="featuredProducts">
      <div class="mobBlockFiter">
        <button class="btn btn-outline-secondary d-md-none" type="button" data-bs-toggle="offcanvas"
          data-bs-target="#mobileFilters">
          <i class="bi bi-funnel"></i> Filters
        </button>

        <button class="btn btn-outline-secondary d-md-none" type="button" data-bs-toggle="offcanvas"
          data-bs-target="#mobileSorting">
          <i class="bi bi-sort-down"></i> Sort
        </button>
      </div>
      <div class="container">
        <div class="magnitudeFix">
          <div class="productListFilters d-none d-md-flex">
            <div class="productListFiltersDropdown" v-for="filter in filterSections" :key="filter.key"
              :ref="(el) => dropdownRefs.set(filter.key, el)">
              <button @click.stop="toggleDropdown(filter.key)" class="productListFiltersBtn">
                {{ filter.label }}
                <span class="badgeCount" v-if="selectedFilters[filter.key].length">
                  {{ selectedFilters[filter.key].length }}
                </span>
                <img src="../assets/images/static/product/landing/arrow_drop_down.svg" alt="Dropdown Arrow"
                  class="imageFrame" />
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
        @change="applyFilters"
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
        @change="applyFilters"
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
          @change="applyFilters"
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
        @change="applyFilters"
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
        @change="applyFilters"
      />
      {{ occasion.label }}
    </label>
  </div>

</div>

            </div>
          </div>

          <!-- Mobile Filter Sidebar -->
          <div class="offcanvas offcanvas-start" tabindex="-1" id="mobileFilters" aria-labelledby="mobileFiltersLabel">
            <div class="offcanvas-header">
              <h5 class="offcanvas-title" id="mobileFiltersLabel">Filters</h5>
              <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
            </div>
            <div class="offcanvas-body">
              <div class="accordion" id="mobileFilterAccordion">
                <div class="accordion-item" v-for="filter in filterSections" :key="filter.key">
                  <h2 class="accordion-header" :id="`heading-${filter.key}`">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                      :data-bs-target="`#collapse-${filter.key}`" aria-expanded="false"
                      :aria-controls="`collapse-${filter.key}`">
                      {{ filter.label }}
                      <span class="badge bg-primary ms-2" v-if="selectedFilters[filter.key].length">
                        {{ selectedFilters[filter.key].length }}
                      </span>
                    </button>
                  </h2>
                  <div :id="`collapse-${filter.key}`" class="accordion-collapse collapse"
                    :aria-labelledby="`heading-${filter.key}`" data-bs-parent="#mobileFilterAccordion">
                    <div class="accordion-body">
                      <div v-if="filter.key === 'priceRange'">
                        <label v-for="priceRange in priceRangeOptions" :key="priceRange.value" class="d-block mb-2">
                          <input type="checkbox" :value="priceRange.value" v-model="selectedFilters.priceRange" />
                          {{ priceRange.label }}
                        </label>
                      </div>

                      <div v-else-if="filter.key === 'purity'">
                        <label v-for="purity in purityOptions" :key="purity.id" class="d-block mb-2">
                          <input type="checkbox" :value="purity.id" v-model="selectedFilters.purity" />
                          {{ purity.name }}
                        </label>
                      </div>

                      <div v-else-if="filter.key === 'products'">
                        <div v-for="product in productOptions" :key="product.value" class="">
                          <label class="d-block">
                            <input type="checkbox" :value="product.value" v-model="selectedFilters.products"
                              @change="onCategoryChanged(product.value)" />
                            {{ product.label }}
                          </label>
                          
                          <!-- Subcategories for mobile -->
                          <div v-if="selectedFilters.products.includes(product.value) && subCategoryOptions[product.value]"
                            class="magnetBox">
                            <label class="d-block mb-1">
                              <input type="checkbox" :checked="isAllSubcategoriesSelected(product.value)"
                                @change="(e) => toggleAllSubcategories(product.value, e.target.checked)" />
                              All
                            </label>
                            <label v-for="sub in subCategoryOptions[product.value]" :key="sub.value"
                              class="d-block mb-1">
                              <input type="checkbox" :value="sub.value" v-model="selectedFilters.subcategories" />
                              {{ sub.label }}
                            </label>
                          </div>
                        </div>
                      </div>

                      <div v-else-if="filter.key === 'gender'">
                        <label v-for="gender in genderOptions" :key="gender.value" class="d-block mb-2">
                          <input type="checkbox" :value="gender.value" v-model="selectedFilters.gender" />
                          {{ gender.label }}
                        </label>
                      </div>

                      <div v-else-if="filter.key === 'occasion'">
                        <label v-for="occasion in occasionOptions" :key="occasion.value" class="d-block mb-2">
                          <input type="checkbox" :value="occasion.value" v-model="selectedFilters.occasion" />
                          {{ occasion.label }}
                        </label>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <div class="applythings mt-3">
                <button class="btn btn-outline-secondary me-2" @click="resetFilters">Reset</button>
                <button class="btn btn-primary" data-bs-dismiss="offcanvas" @click="applyFilters">
                  Apply Filters
                </button>
              </div>
            </div>
          </div>

          <!-- Mobile Sorting Sidebar -->
          <div class="offcanvas offcanvas-start" tabindex="-1" id="mobileSorting" aria-labelledby="mobileSortingLabel">
            <div class="offcanvas-header">
              <h5 class="offcanvas-title" id="mobileSortingLabel">Sort</h5>
              <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
            </div>
            <div class="offcanvas-body">
              <div>
                <label v-for="sortOption in sortOptions" :key="sortOption.value" class="d-block mb-2">
                  <input type="radio" :value="sortOption.value" v-model="selectedSort" class="me-2" />
                  {{ sortOption.label }}
                </label>
              </div>
            </div>

            <div class="applythings">
              <button class="btn btn-primary w-100" data-bs-dismiss="offcanvas" @click="applySorting">
                Apply Sorting
              </button>
            </div>
          </div>

          <!-- Desktop Sorting -->
          <div class="turner d-none d-md-flex align-items-center">
            <label class="me-2 mb-0">Sort by:</label>
            <select v-model="selectedSort" class="form-select justmolic" @change="applySorting" style="width: auto;">
              <option v-for="option in sortOptions" :key="option.value" :value="option.value">
                {{ option.label }}
              </option>
            </select>
          </div>
        </div>

        <!-- Applied Filters Bar -->
        <div class="appliedFiltersBar mb-4" v-if="hasAnyFilterApplied">
          <div class="appliedFiltersRow">
            <span class="appliedChip" v-for="chip in appliedFilterChips" :key="`${chip.key}-${chip.value}`">
              {{ chip.display }}
              <button type="button" aria-label="Remove filter" class="chipClose"
                @click.stop="removeFilter(chip.key, chip.value)">
                ×
              </button>
            </span>
          </div>
          <button class="resetFiltersBtn" @click="resetFilters">
            Reset Filters
          </button>
        </div>

        <!-- Products Grid -->
        <div class="productList">
          <b-link v-for="product in products" :key="product.id"
            :to="{ name: 'productsDetails', params: { id: encodeBase64(product.id) } }" class="productItem">
            <p class="bestSellerTag" v-if="JSON.parse(product.tags_id || '[]').includes('best seller')">
              Bestseller
            </p>

            <div class="productImg" @click="goToProductDetails(product)" style="cursor: pointer">
              <div class="CTA">
                <span class="ctaWishlist" @click.stop="handleWishlist(product)" :id="`addwish-${product.id}`">
                  <img src="../assets/images/static/product/landing/outline-whishlist.svg" alt="wishlist" />
                </span>

                <span class="ctaViews" @click.stop>
                  <img src="../assets/images/static/product/landing/outline-eye.svg" alt="views" />
                  <p>20</p>
                </span>
              </div>

              <p class="offerTag" v-if="JSON.parse(product.tags_id || '[]').includes('20')">
                Flat 20% Off
              </p>

              <img :src="`storage/${product.main_image}`" :alt="product.name" class="imageFrame" />
            </div>

            <div class="productDetails" @click="goToProductDetails(product)" style="cursor: pointer">
              <div class="productDisplay">
                <p class="productType">
                  {{ product.category?.name || '' }}
                </p>

                <div class="productRating">
                  0.0
                  <img src="../assets/images/static/product/landing/Star.svg" alt="rating" class="imageFrame" />
                  | 0
                </div>
              </div>
              <p class="productName">{{ product.name }}</p>
              <p class="productPrice">
                <span class="discountedPrice">
                  ₹ {{ Number(product.final_price).toLocaleString() }}
                </span>

                <span class="originalPrice" v-if="product.discount > 0">
                  ₹ {{ Number(product.base_price).toLocaleString() }}
                </span>
              </p>
            </div>

            <div class="productAction">
              <button @click.stop.prevent="handleAddToCart(product)" :id="`addCartBtn-${product.id}`">
                Add To Cart
              </button>
            </div>
          </b-link>
        </div>

        <!-- Load More / Pagination -->
        <div v-if="products.length > 0 && pagination.current_page < pagination.last_page" class="text-center mt-4">
          <button @click="loadMore" class="btn btn-primary" :disabled="loading">
            <span v-if="loading">Loading...</span>
            <span v-else>Load More</span>
          </button>
        </div>

        <div v-else-if="products.length === 0 && !loading" class="text-center py-5">
          <p class="text-muted">No products found matching your filters.</p>
          <button @click="resetFilters" class="btn btn-outline-primary">Reset Filters</button>
        </div>

        <div v-if="loading" class="loading-spinner">
          Loading products<span class="dots"><span>.</span><span>.</span><span>.</span></span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, reactive, computed, watch ,onUnmounted} from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axiosCustomer from '@axiosCustomer'
import { toast } from "vue3-toastify"
import "vue3-toastify/dist/index.css"
import { addToLocalCart } from "../../utils/localStorageCart"

const route = useRoute()
const router = useRouter()

// Reactive data
const products = ref([])
const loading = ref(false)
const loaderBase = ref(false)

// Filter options
const collectionOptions = ref([
  { label: "22KT Range", value: "22KT Range" },
  { label: "Aaheli", value: "Aaheli" },
])

const priceRangeOptions = ref([
  { label: "Under ₹1000", value: "0-1000" },
  { label: "₹1,000 - ₹5,000", value: "1000-5000" },
  { label: "₹5,000 - ₹10,000", value: "5000-10000" },
  { label: "₹10,000 - ₹50,000", value: "10000-50000" },
  { label: "₹50,000 - ₹1,00,000", value: "50000-100000" },
  { label: "₹1,00,000 - ₹2,00,000", value: "100000-200000" },
  { label: "Above ₹2,00,000", value: "200000+" }
])

const purityOptions = ref([])
const productOptions = ref([])
const occasionOptions = ref([])

const genderOptions = ref([
  { label: "Kids", value: "Kids" },
  { label: "Men", value: "Men" },
  { label: "Unisex", value: "Unisex" },
  { label: "Women", value: "Women" }
])

const communityOptions = ref([
  { label: "Bengali", value: "Bengali" },
  { label: "Bihari", value: "Bihari" },
])

const sortOptions = ref([
  { label: "Featured", value: "featured" },
  { label: "Best Selling", value: "best_selling" },
  { label: "Alphabetically, A-Z", value: "name_asc" },
  { label: "Alphabetically, Z-A", value: "name_desc" },
  { label: "Price, Low to High", value: "price_asc" },
  { label: "Price, High to Low", value: "price_desc" },
  { label: "Date, Old to New", value: "date_asc" },
  { label: "Date, New to Old", value: "date_desc" }
])

// Selected filters
const selectedFilters = reactive({
  collections: [],
  priceRange: [],
  purity: [],
  products: [],
  subcategories: [],
  gender: [],
  occasion: [],
  community: [],
})

const selectedSort = ref("featured")
const pagination = reactive({
  current_page: 1,
  last_page: 1,
  per_page: 12,
  total: 0
})

const filterSections = [
  { key: "priceRange", label: "Price Range" },
  { key: "purity", label: "Purity" },
  { key: "products", label: "Products" },
  { key: "gender", label: "Gender" },
  { key: "occasion", label: "Occasion" },
]

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
const subCategoryOptions = ref({})
const hoveredProduct = ref(null)
const hideTimers = ref({})

// Computed properties
const hasAnyFilterApplied = computed(() => {
  return Object.values(selectedFilters).some(arr => arr.length > 0) || selectedSort.value !== 'featured'
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
      const sub = subCategoryOptions.value[catId].find(s => s.value == value)
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
function buildQueryParams() {
  const params = new URLSearchParams()
  
  // Add filters
  Object.keys(selectedFilters).forEach(key => {
    if (selectedFilters[key].length > 0) {
      params.set(key, selectedFilters[key].join(','))
    }
  })
  
  // Add sorting
  if (selectedSort.value !== 'featured') {
    params.set('sort', selectedSort.value)
  }
  
  // Add pagination
  if (pagination.current_page > 1) {
    params.set('page', pagination.current_page)
  }
  
  return params.toString()
}

function updateURL() {
  const queryString = buildQueryParams()
  const newUrl = queryString ? `${window.location.pathname}?${queryString}` : window.location.pathname
  
  // Update browser URL without reload
  history.pushState({}, '', newUrl)
}

async function fetchProducts(resetPage = true) {
  if (resetPage) {
    pagination.current_page = 1
  }
  
  loading.value = true
  
  try {
    const params = new URLSearchParams()
    
    // Add all filters
    Object.keys(selectedFilters).forEach(key => {
      if (selectedFilters[key].length > 0) {
        params.set(key, selectedFilters[key].join(','))
      }
    })
    
    // Add sorting
    if (selectedSort.value !== 'featured') {
      params.set('sort', selectedSort.value)
    }
    
    // Add pagination
    params.set('page', pagination.current_page)
    params.set('per_page', pagination.per_page)
    
    const { data } = await axiosCustomer.get(`/products?${params.toString()}`)
    
    if (resetPage) {
      products.value = data.data
    } else {
      products.value = [...products.value, ...data.data]
    }
    
    // Update pagination info
    // if (data.meta) {
    //   pagination.current_page = data.meta.current_page
    //   pagination.last_page = data.meta.last_page
    //   pagination.total = data.meta.total
    // }
    
    // Update URL
    updateURL()
    
  } catch (error) {
    console.error('Failed to load products:', error)
    toast.error('Failed to load products')
  } finally {
    loading.value = false
  }
}

function applyFilters() {
  // Close all dropdowns
  for (const k in dropdowns) dropdowns[k] = false
  
  // Fetch products with filters
  fetchProducts(true)
}

function applySorting() {
  fetchProducts(true)
}

function resetFilters() {
  // Reset all filters
  Object.keys(selectedFilters).forEach(key => {
    selectedFilters[key] = []
  })
  selectedSort.value = 'featured'
  
  // Fetch products
  fetchProducts(true)
}

function removeFilter(filterKey, value) {
  const index = selectedFilters[filterKey].indexOf(value)
  if (index > -1) {
    selectedFilters[filterKey].splice(index, 1)
    fetchProducts(true)
  }
}

function loadMore() {
  if (pagination.current_page < pagination.last_page) {
    pagination.current_page++
    fetchProducts(false)
  }
}

// Subcategory methods
function onProductHover(productId) {
  hoveredProduct.value = productId
  cancelHide(productId)

  if (!subCategoryOptions.value[productId]) {
    fetchSubcategories(productId)
  }
}

function scheduleHide(productId) {
  clearTimeout(hideTimers.value[productId])

  hideTimers.value[productId] = setTimeout(() => {
    if (hoveredProduct.value === productId) {
      hoveredProduct.value = null
    }
  }, 120)
}

function cancelHide(productId) {
  clearTimeout(hideTimers.value[productId])
}

async function fetchSubcategories(categoryId) {
  try {
    const res = await axiosCustomer.get(`/filter/SubCategoryOptions/${categoryId}`)
    const raw = res?.data?.data ?? res?.data ?? []
    
    subCategoryOptions.value[categoryId] = raw.map((s) => ({
      label: s.label ?? s.name ?? s.subcategory_name ?? String(s.value ?? s.id ?? s),
      value: s.value ?? s.id ?? s,
    }))
  } catch (err) {
    console.error("Failed to fetch subcategories:", err)
  }
}

function isAllSubcategoriesSelected(categoryId) {
  const subcats = subCategoryOptions.value[categoryId] || []
  return subcats.length > 0 && subcats.every(sub => 
    selectedFilters.subcategories.includes(sub.value)
  )
}

function toggleAllSubcategories(categoryId, checked) {
  const subcats = subCategoryOptions.value[categoryId] || []
  
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
  
  applyFilters()
}

function onCategoryChanged(categoryId) {
  // When a category is unchecked, remove its subcategories
  if (!selectedFilters.products.includes(categoryId)) {
    const subcats = subCategoryOptions.value[categoryId] || []
    selectedFilters.subcategories = selectedFilters.subcategories.filter(
      subId => !subcats.some(sub => sub.value == subId)
    )
  }
}

// Load filter options
Promise.all([
  axiosCustomer.get("/filter/getPurityOptions").then(response => {
    purityOptions.value = response?.data?.data ?? []
  }).catch(err => {
    console.error("Error loading purity options:", err)
  }),
  
  axiosCustomer.get("/filter/CategoryOptions").then(response => {
    const rawData = response?.data?.data ?? response?.data ?? []
    productOptions.value = rawData.map(c => ({
      label: c?.label ?? c?.name ?? c?.category_name ?? String(c?.value ?? c?.id ?? c),
      value: c?.value ?? c?.id ?? c,
    }))
  }).catch(err => {
    console.error("Error loading category options:", err)
  }),
  
  axiosCustomer.get("/filter/Occasion").then(response => {
    occasionOptions.value = response?.data?.data ?? []
  }).catch(err => {
    console.error("Error loading occasion options:", err)
  }),
]).catch(err => {
  console.error("Error loading filter options:", err)
})

// Parse URL parameters on load
function parseUrlParams() {
  const params = new URLSearchParams(window.location.search)
  
  // Parse filters from URL
  Object.keys(selectedFilters).forEach(key => {
    if (params.has(key)) {
      const value = params.get(key)
      selectedFilters[key] = value.split(',').filter(v => v !== '')
    }
  })
  
  // Parse sort
  if (params.has('sort')) {
    selectedSort.value = params.get('sort')
  }
  
  // Parse page
  if (params.has('page')) {
    pagination.current_page = parseInt(params.get('page')) || 1
  }
}

// Other existing methods
const encodeBase64 = (data) => {
  if (data === undefined || data === null) {
    return ""
  }
  return btoa(data.toString())
}

const goToProductDetails = (product) => {
  router.push(`/product/${product.id}`)
}

const handleWishlist = (product) => {
  console.log("Wishlist clicked:", product)
}

const handleAddToCart = async (product) => {
  const token = localStorage.getItem("customer_token")
  const userData = JSON.parse(localStorage.getItem("customer_data") || "{}")
  
  if (!token || !userData.id) {
    addToLocalCart(product, 1)
    toast.success("Product added to cart!", {
      title: "Cart",
      variant: "success",
      solid: true,
      autoHideDelay: 3000,
    })
    window.dispatchEvent(new Event("cart-updated"))
    return
  }
  
  try {
    const payload = {
      user_id: userData.id,
      product_id: product.id,
      quantity: 1,
    }
    
    const res = await axiosCustomer.post("cart", payload)
    
    window.dispatchEvent(new Event("cart-updated"))
    
    toast.success(res.data.message || "Product added to cart successfully!", {
      title: "Cart",
      variant: "success",
      solid: true,
      autoHideDelay: 3000,
    })
  } catch (error) {
    console.error("Add to cart error:", error.response?.data)
    
    const backendError = error.response?.data?.error ||
      error.response?.data?.message ||
      error.message ||
      "Failed to update cart!"
    
    if (backendError === "Unauthenticated.") {
      addToLocalCart(product, 1)
      toast.warning("Please login to sync your cart", {
        title: "Cart",
        variant: "warning",
        solid: true,
        autoHideDelay: 3000,
      })
      return
    }
    
    toast.error(backendError, {
      title: "Error",
      variant: "danger",
      solid: true,
      autoHideDelay: 4000,
    })
  }
}

// Initialize
onMounted(() => {
  // Parse URL parameters
  parseUrlParams()
  
  // Fetch initial products
  fetchProducts(true)
  
  // Set up click outside listener
  document.addEventListener('click', handleClickOutside)
})

// Cleanup
onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
})
</script>

<style scoped>
/* .product-listing-page .cms-header--productlist {
  background-color: #fff7ec;
} */
.offcanvas-backdrop.show {
  background-color: rgba(0, 0, 0, 0.5);
}

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
  font-size: 14px;
}


.category-label {
  display: inline-block;
  color: #111827;
}

.productListFiltersBtn .badgeCount {
  display: none;
}

.originalPrice {
  text-decoration: line-through;
  color: #888;
  margin-right: 8px;
}

.discountedPrice {
  font-weight: bold;
  color: #404054;
  /* red color for discounted price */
}

.magnitudeFix {
  display: flex !important;
  justify-content: space-between !important;
  align-items: center !important;
}

.magnitudeFix .pagination {
  padding: 20px 0;
  display: flex;
  gap: 10px;
  justify-content: center;
  align-items: center;
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
  /* background: #ef4444;
  border: none;
  padding: 8px 12px;
  border-radius: 6px;
  cursor: pointer; */
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

  .magnitudeFix {
    display: block !important;
  }

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
  padding-left: 8px; /* 👈 GAP SAFE AREA */
  background: #fff;
  border: 1px solid #ddd;
  border-radius: 6px;
  padding: 8px 12px;
  z-index: 999;
  width: max-content;
  min-width: 200px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.subcategory-header {
  font-weight: 600;
  margin-bottom: 8px;
}

.sub-option {
  padding: 4px 0 !important;
}



.offerTag {
  display: block;
  color: #000;

  font-family: "DM Sans";
  font-size: 12px;
  font-style: normal;
  font-weight: 400;
  line-height: 160%;
  padding: 6px 10px;
  border-radius: 0px;
  background: var(--SECONDARY-COLORS-S---1, #FDC25C);
  margin: 0;
  position: absolute;
  bottom: 0;
  left: 0;
  z-index: 9;
}

.loading-spinner {
  text-align: center;
  padding: 40px 20px;
  opacity: 1;
  font-size: 16px;
  font-weight: 500;
  color: #333;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 5px;
}

.dots span {
  animation: blink 1.4s infinite both;
  display: inline-block;
  font-weight: bold;
}

.dots span:nth-child(2) {
  animation-delay: 0.2s;
}

.dots span:nth-child(3) {
  animation-delay: 0.4s;
}

@keyframes blink {
  0% {
    opacity: 0;
  }

  20% {
    opacity: 1;
  }

  100% {
    opacity: 0;
  }
}

.observer-trigger {
  height: 1px;
  width: 100%;
  visibility: hidden;
  pointer-events: none;
}

/* Image Optimization Styles */
.productImg img {
  transition: opacity 0.3s ease;
  will-change: opacity;
}

.productImg img[loading="lazy"] {
  background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
  background-size: 200% 100%;
  animation: loading-shimmer 1.5s infinite;
}

@keyframes loading-shimmer {
  0% {
    background-position: 200% 0;
  }

  100% {
    background-position: -200% 0;
  }
}

/* Optimize image rendering */
.imageFrame {
  image-rendering: -webkit-optimize-contrast;
  image-rendering: crisp-edges;
  backface-visibility: hidden;
  transform: translateZ(0);
}
</style>