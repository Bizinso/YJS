<template>
  <div class="flash-sales-page">
    <div class="container py-5">
      <!-- Header -->
      <div class="flash-header text-center mb-5">
        <h1 class="display-4 fw-bold text-danger">
          <i class="bi bi-lightning-charge-fill"></i> Flash Sales
        </h1>
        <p class="lead text-muted">Limited time offers - Don't miss out!</p>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="text-center py-5">
        <div class="spinner-border text-primary" role="status">
          <span class="visually-hidden">Loading...</span>
        </div>
        <p class="mt-3">Loading flash sales...</p>
      </div>

      <!-- Flash Sales Grid -->
      <div v-else-if="flashSales.length > 0" class="row g-4">
        <div v-for="sale in flashSales" :key="sale.id" class="col-12 col-md-6 col-lg-4">
          <div class="card flash-card h-100 shadow-sm">
            <!-- Sale Badge -->
            <div class="sale-badge">
              <span class="badge bg-danger fs-6">
                {{ sale.discount_value }}{{ sale.discount_type === 'percentage' ? '%' : '₹' }} OFF
              </span>
            </div>

            <!-- Countdown Timer -->
            <div class="countdown-timer bg-dark text-white p-2 text-center">
              <small>Ends in:</small>
              <div class="timer-display">
                <span class="time-unit">{{ getCountdown(sale).days }}<small>d</small></span>
                <span class="time-unit">{{ getCountdown(sale).hours }}<small>h</small></span>
                <span class="time-unit">{{ getCountdown(sale).minutes }}<small>m</small></span>
                <span class="time-unit">{{ getCountdown(sale).seconds }}<small>s</small></span>
              </div>
            </div>

            <div class="card-body">
              <h5 class="card-title">{{ sale.name }}</h5>
              <p class="card-text text-muted">{{ sale.description }}</p>

              <!-- Stock Progress -->
              <div v-if="sale.remaining_flash_stock !== null" class="stock-progress mb-3">
                <div class="d-flex justify-content-between mb-1">
                  <small class="text-muted">Stock remaining</small>
                  <small class="fw-bold text-danger">{{ sale.remaining_flash_stock }} left</small>
                </div>
                <div class="progress" style="height: 8px;">
                  <div
                    class="progress-bar bg-danger"
                    :style="{ width: getStockPercentage(sale) + '%' }"
                  ></div>
                </div>
              </div>

              <!-- Terms -->
              <div v-if="sale.min_order_amount" class="terms-info small text-muted mb-3">
                <i class="bi bi-info-circle"></i>
                Min. order: ₹{{ Number(sale.min_order_amount).toLocaleString() }}
              </div>
            </div>

            <div class="card-footer bg-transparent">
              <button
                @click="viewSaleProducts(sale)"
                class="btn btn-danger w-100"
              >
                Shop Now <i class="bi bi-arrow-right"></i>
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- No Sales -->
      <div v-else class="text-center py-5">
        <i class="bi bi-lightning text-muted" style="font-size: 4rem;"></i>
        <h3 class="mt-3">No Flash Sales Active</h3>
        <p class="text-muted">Check back soon for exciting flash deals!</p>
        <router-link to="/products" class="btn btn-primary">
          Browse Products
        </router-link>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import axiosCustomer from '@axiosCustomer'
import { toast } from "vue3-toastify"

const router = useRouter()

const flashSales = ref([])
const loading = ref(true)
const countdowns = ref({})
let countdownInterval = null

// Fetch flash sales
async function fetchFlashSales() {
  loading.value = true
  try {
    const { data } = await axiosCustomer.get('/promotions/flash-sales')
    if (data.success) {
      flashSales.value = data.flash_sales || []
      initializeCountdowns()
    }
  } catch (error) {
    console.error('Failed to fetch flash sales:', error)
    toast.error('Failed to load flash sales')
  } finally {
    loading.value = false
  }
}

// Initialize countdown timers
function initializeCountdowns() {
  flashSales.value.forEach(sale => {
    updateCountdown(sale)
  })

  // Update every second
  countdownInterval = setInterval(() => {
    flashSales.value.forEach(sale => {
      updateCountdown(sale)
    })
  }, 1000)
}

// Update countdown for a sale
function updateCountdown(sale) {
  const now = new Date().getTime()
  const end = new Date(sale.end_date).getTime()
  const distance = end - now

  if (distance < 0) {
    countdowns.value[sale.id] = { days: 0, hours: 0, minutes: 0, seconds: 0, expired: true }
    return
  }

  countdowns.value[sale.id] = {
    days: Math.floor(distance / (1000 * 60 * 60 * 24)),
    hours: Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60)),
    minutes: Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60)),
    seconds: Math.floor((distance % (1000 * 60)) / 1000),
    expired: false
  }
}

// Get countdown for display
function getCountdown(sale) {
  return countdowns.value[sale.id] || { days: 0, hours: 0, minutes: 0, seconds: 0 }
}

// Get stock percentage
function getStockPercentage(sale) {
  if (!sale.flash_sale_quantity || !sale.remaining_flash_stock) return 0
  return (sale.remaining_flash_stock / sale.flash_sale_quantity) * 100
}

// View sale products
function viewSaleProducts(sale) {
  router.push({
    name: 'products',
    query: { flash_sale: sale.id }
  })
}

onMounted(() => {
  fetchFlashSales()
})

onUnmounted(() => {
  if (countdownInterval) {
    clearInterval(countdownInterval)
  }
})
</script>

<style scoped>
.flash-sales-page {
  min-height: 60vh;
  background: linear-gradient(135deg, #fff5f5 0%, #ffffff 100%);
}

.flash-header h1 {
  color: #dc3545;
}

.flash-card {
  border: none;
  border-radius: 12px;
  overflow: hidden;
  transition: transform 0.3s ease, box-shadow 0.3s ease;
  position: relative;
}

.flash-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 10px 30px rgba(220, 53, 69, 0.2) !important;
}

.sale-badge {
  position: absolute;
  top: 60px;
  right: 10px;
  z-index: 10;
}

.countdown-timer {
  background: linear-gradient(135deg, #dc3545 0%, #c82333 100%) !important;
}

.timer-display {
  display: flex;
  justify-content: center;
  gap: 10px;
  font-size: 1.2rem;
  font-weight: bold;
}

.time-unit {
  background: rgba(255, 255, 255, 0.2);
  padding: 4px 8px;
  border-radius: 4px;
}

.time-unit small {
  font-size: 0.7rem;
  opacity: 0.8;
}

.stock-progress .progress {
  background: #f8d7da;
}

.terms-info {
  padding: 8px;
  background: #f8f9fa;
  border-radius: 6px;
}
</style>
