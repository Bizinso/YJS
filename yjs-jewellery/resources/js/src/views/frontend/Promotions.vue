<template>
  <div class="promotions-page">
    <div class="container py-5">
      <!-- Header -->
      <div class="promo-header text-center mb-5">
        <h1 class="display-4 fw-bold">
          <i class="bi bi-gift-fill text-warning"></i> Special Offers
        </h1>
        <p class="lead text-muted">Exclusive deals on beautiful jewellery</p>
      </div>

      <!-- Categories Filter -->
      <div class="category-filter mb-4">
        <div class="d-flex flex-wrap justify-content-center gap-2">
          <button
            v-for="cat in categories"
            :key="cat.value"
            @click="selectedCategory = cat.value"
            :class="['btn', selectedCategory === cat.value ? 'btn-primary' : 'btn-outline-primary']"
          >
            {{ cat.label }}
          </button>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="text-center py-5">
        <div class="spinner-border text-primary" role="status">
          <span class="visually-hidden">Loading...</span>
        </div>
      </div>

      <!-- Active Promotions -->
      <div v-else>
        <!-- BOGO Offers -->
        <section v-if="bogoOffers.length > 0" class="promo-section mb-5">
          <h3 class="section-title">
            <i class="bi bi-bag-plus text-success"></i> Buy One Get One
          </h3>
          <div class="row g-4">
            <div v-for="offer in bogoOffers" :key="offer.id" class="col-12 col-md-6 col-lg-4">
              <div class="card promo-card bogo-card h-100">
                <div class="card-body">
                  <span class="badge bg-success mb-2">BOGO</span>
                  <h5 class="card-title">{{ offer.name }}</h5>
                  <p class="card-text text-muted">{{ offer.description }}</p>
                  <div class="offer-details">
                    <p class="mb-1">
                      <strong>Buy {{ offer.buy_quantity }}</strong> {{ offer.buy_product_type || 'items' }}
                    </p>
                    <p class="mb-0 text-success">
                      <strong>Get {{ offer.get_quantity }}</strong>
                      <span v-if="offer.get_discount_percentage === 100"> FREE!</span>
                      <span v-else> at {{ offer.get_discount_percentage }}% off</span>
                    </p>
                  </div>
                </div>
                <div class="card-footer bg-transparent">
                  <button @click="shopOffer(offer)" class="btn btn-success w-100">
                    Shop Now
                  </button>
                </div>
              </div>
            </div>
          </div>
        </section>

        <!-- Combo Offers -->
        <section v-if="comboOffers.length > 0" class="promo-section mb-5">
          <h3 class="section-title">
            <i class="bi bi-collection text-info"></i> Combo Deals
          </h3>
          <div class="row g-4">
            <div v-for="offer in comboOffers" :key="offer.id" class="col-12 col-md-6 col-lg-4">
              <div class="card promo-card combo-card h-100">
                <div class="card-body">
                  <span class="badge bg-info mb-2">COMBO</span>
                  <h5 class="card-title">{{ offer.name }}</h5>
                  <p class="card-text text-muted">{{ offer.description }}</p>
                  <div class="combo-savings text-center py-3">
                    <span class="display-6 text-info fw-bold">
                      {{ offer.discount_value }}{{ offer.discount_type === 'percentage' ? '%' : '₹' }}
                    </span>
                    <span class="d-block small">SAVINGS</span>
                  </div>
                </div>
                <div class="card-footer bg-transparent">
                  <button @click="shopOffer(offer)" class="btn btn-info text-white w-100">
                    View Combo
                  </button>
                </div>
              </div>
            </div>
          </div>
        </section>

        <!-- Tiered Discounts -->
        <section v-if="tieredOffers.length > 0" class="promo-section mb-5">
          <h3 class="section-title">
            <i class="bi bi-graph-up-arrow text-purple"></i> Spend More, Save More
          </h3>
          <div class="row g-4">
            <div v-for="offer in tieredOffers" :key="offer.id" class="col-12 col-lg-6">
              <div class="card promo-card tiered-card h-100">
                <div class="card-body">
                  <span class="badge bg-purple mb-2">TIERED</span>
                  <h5 class="card-title">{{ offer.name }}</h5>
                  <p class="card-text text-muted mb-3">{{ offer.description }}</p>
                  <div class="tier-table">
                    <table class="table table-sm table-borderless">
                      <thead>
                        <tr>
                          <th>Spend</th>
                          <th>Save</th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr v-for="(tier, idx) in parseTiers(offer.tier_rules)" :key="idx">
                          <td>₹{{ Number(tier.min_amount).toLocaleString() }}+</td>
                          <td class="text-success fw-bold">
                            {{ tier.discount_value }}{{ tier.discount_type === 'percentage' ? '%' : '₹' }}
                          </td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                </div>
                <div class="card-footer bg-transparent">
                  <button @click="shopOffer(offer)" class="btn btn-purple w-100">
                    Start Shopping
                  </button>
                </div>
              </div>
            </div>
          </div>
        </section>

        <!-- Percentage/Flat Discounts -->
        <section v-if="discountOffers.length > 0" class="promo-section mb-5">
          <h3 class="section-title">
            <i class="bi bi-percent text-warning"></i> Discount Offers
          </h3>
          <div class="row g-4">
            <div v-for="offer in discountOffers" :key="offer.id" class="col-12 col-md-6 col-lg-3">
              <div class="card promo-card discount-card h-100">
                <div class="card-body text-center">
                  <div class="discount-badge mb-3">
                    <span class="display-4 fw-bold text-warning">
                      {{ offer.discount_value }}
                    </span>
                    <span class="fs-4">{{ offer.discount_type === 'percentage' ? '%' : '₹' }}</span>
                    <span class="d-block small text-muted">OFF</span>
                  </div>
                  <h6 class="card-title">{{ offer.name }}</h6>
                  <p class="card-text small text-muted">{{ offer.description }}</p>
                  <div v-if="offer.coupon_code" class="coupon-code mt-2">
                    <small class="text-muted">Use code:</small>
                    <code class="d-block fs-5">{{ offer.coupon_code }}</code>
                  </div>
                </div>
                <div class="card-footer bg-transparent">
                  <button @click="shopOffer(offer)" class="btn btn-warning w-100">
                    Shop Now
                  </button>
                </div>
              </div>
            </div>
          </div>
        </section>

        <!-- Free Gift Offers -->
        <section v-if="freeGiftOffers.length > 0" class="promo-section mb-5">
          <h3 class="section-title">
            <i class="bi bi-gift text-danger"></i> Free Gifts
          </h3>
          <div class="row g-4">
            <div v-for="offer in freeGiftOffers" :key="offer.id" class="col-12 col-md-6 col-lg-4">
              <div class="card promo-card gift-card h-100">
                <div class="card-body">
                  <span class="badge bg-danger mb-2">FREE GIFT</span>
                  <h5 class="card-title">{{ offer.name }}</h5>
                  <p class="card-text text-muted">{{ offer.description }}</p>
                  <div class="gift-info mt-3 p-3 bg-light rounded">
                    <p class="mb-1 small">
                      <i class="bi bi-cart-check text-success"></i>
                      Min. purchase: ₹{{ Number(offer.min_order_amount || 0).toLocaleString() }}
                    </p>
                    <p class="mb-0 fw-bold text-danger">
                      <i class="bi bi-gift-fill"></i>
                      Get: {{ offer.free_gift_name || 'Surprise Gift' }}
                    </p>
                  </div>
                </div>
                <div class="card-footer bg-transparent">
                  <button @click="shopOffer(offer)" class="btn btn-danger w-100">
                    Claim Gift
                  </button>
                </div>
              </div>
            </div>
          </div>
        </section>

        <!-- No Offers -->
        <div v-if="filteredOffers.length === 0" class="text-center py-5">
          <i class="bi bi-tags text-muted" style="font-size: 4rem;"></i>
          <h3 class="mt-3">No Active Offers</h3>
          <p class="text-muted">Check back soon for exciting deals!</p>
          <router-link to="/products" class="btn btn-primary">
            Browse Products
          </router-link>
        </div>
      </div>

      <!-- Coupon Input Section -->
      <section class="coupon-section mt-5 p-4 bg-light rounded">
        <h4 class="mb-3">Have a Coupon Code?</h4>
        <div class="row g-3 align-items-end">
          <div class="col-12 col-md-8">
            <label class="form-label">Enter your coupon code</label>
            <input
              v-model="couponCode"
              type="text"
              class="form-control form-control-lg"
              placeholder="Enter coupon code"
              @keyup.enter="validateCoupon"
            />
          </div>
          <div class="col-12 col-md-4">
            <button
              @click="validateCoupon"
              class="btn btn-primary btn-lg w-100"
              :disabled="validatingCoupon || !couponCode"
            >
              <span v-if="validatingCoupon">
                <span class="spinner-border spinner-border-sm"></span> Validating...
              </span>
              <span v-else>Apply Coupon</span>
            </button>
          </div>
        </div>
        <div v-if="couponResult" class="mt-3">
          <div
            :class="['alert', couponResult.valid ? 'alert-success' : 'alert-danger']"
            role="alert"
          >
            <i :class="['bi', couponResult.valid ? 'bi-check-circle' : 'bi-x-circle']"></i>
            {{ couponResult.message }}
            <div v-if="couponResult.valid && couponResult.discount">
              <strong>Discount:</strong> {{ couponResult.discount.value }}{{ couponResult.discount.type === 'percentage' ? '%' : '₹' }}
            </div>
          </div>
        </div>
      </section>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import axiosCustomer from '@axiosCustomer'
import { toast } from "vue3-toastify"

const router = useRouter()

const offers = ref([])
const loading = ref(true)
const selectedCategory = ref('all')
const couponCode = ref('')
const validatingCoupon = ref(false)
const couponResult = ref(null)

const categories = [
  { label: 'All Offers', value: 'all' },
  { label: 'BOGO', value: 'bogo' },
  { label: 'Combos', value: 'combo' },
  { label: 'Discounts', value: 'discount' },
  { label: 'Free Gifts', value: 'gift' },
]

// Computed filtered offers
const filteredOffers = computed(() => {
  if (selectedCategory.value === 'all') return offers.value
  return offers.value.filter(o => {
    if (selectedCategory.value === 'bogo') return o.is_bogo
    if (selectedCategory.value === 'combo') return o.is_combo
    if (selectedCategory.value === 'gift') return o.has_free_gift
    if (selectedCategory.value === 'discount') return !o.is_bogo && !o.is_combo && !o.has_free_gift
    return true
  })
})

const bogoOffers = computed(() => filteredOffers.value.filter(o => o.is_bogo))
const comboOffers = computed(() => filteredOffers.value.filter(o => o.is_combo))
const tieredOffers = computed(() => filteredOffers.value.filter(o => o.tier_rules && o.tier_rules.length > 0))
const freeGiftOffers = computed(() => filteredOffers.value.filter(o => o.has_free_gift))
const discountOffers = computed(() => filteredOffers.value.filter(o =>
  !o.is_bogo && !o.is_combo && !o.has_free_gift && !(o.tier_rules && o.tier_rules.length > 0)
))

// Fetch promotions
async function fetchPromotions() {
  loading.value = true
  try {
    const { data } = await axiosCustomer.get('/promotions/active')
    if (data.success) {
      offers.value = data.promotions || []
    }
  } catch (error) {
    console.error('Failed to fetch promotions:', error)
    toast.error('Failed to load promotions')
  } finally {
    loading.value = false
  }
}

// Parse tier rules
function parseTiers(tierRules) {
  if (!tierRules) return []
  if (typeof tierRules === 'string') {
    try {
      return JSON.parse(tierRules)
    } catch {
      return []
    }
  }
  return tierRules
}

// Validate coupon
async function validateCoupon() {
  if (!couponCode.value.trim()) return

  validatingCoupon.value = true
  couponResult.value = null

  try {
    const { data } = await axiosCustomer.post('/promotions/validate-coupon', {
      coupon_code: couponCode.value.trim(),
      cart_total: 10000 // Sample cart total for validation preview
    })

    if (data.success) {
      couponResult.value = {
        valid: true,
        message: 'Coupon is valid! Add items to cart to apply.',
        discount: data.discount
      }
    } else {
      couponResult.value = {
        valid: false,
        message: data.error || 'Invalid coupon code'
      }
    }
  } catch (error) {
    couponResult.value = {
      valid: false,
      message: error.response?.data?.error || 'Failed to validate coupon'
    }
  } finally {
    validatingCoupon.value = false
  }
}

// Shop offer
function shopOffer(offer) {
  router.push({
    name: 'products',
    query: { offer: offer.id }
  })
}

onMounted(() => {
  fetchPromotions()
})
</script>

<style scoped>
.promotions-page {
  min-height: 60vh;
  background: linear-gradient(135deg, #fffbeb 0%, #ffffff 100%);
}

.section-title {
  font-size: 1.5rem;
  font-weight: 600;
  margin-bottom: 1.5rem;
  padding-bottom: 0.5rem;
  border-bottom: 2px solid #eee;
}

.promo-card {
  border: none;
  border-radius: 12px;
  transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.promo-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1) !important;
}

.bogo-card {
  border-left: 4px solid #198754;
}

.combo-card {
  border-left: 4px solid #0dcaf0;
}

.tiered-card {
  border-left: 4px solid #6f42c1;
}

.discount-card {
  border-left: 4px solid #ffc107;
}

.gift-card {
  border-left: 4px solid #dc3545;
}

.discount-badge {
  background: linear-gradient(135deg, #fff8e1 0%, #ffecb3 100%);
  border-radius: 50%;
  width: 120px;
  height: 120px;
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  margin: 0 auto;
}

.coupon-code code {
  background: #fff;
  padding: 8px 16px;
  border: 2px dashed #ffc107;
  border-radius: 4px;
}

.bg-purple {
  background-color: #6f42c1 !important;
}

.btn-purple {
  background-color: #6f42c1;
  border-color: #6f42c1;
  color: white;
}

.btn-purple:hover {
  background-color: #5a32a3;
  border-color: #5a32a3;
  color: white;
}

.text-purple {
  color: #6f42c1 !important;
}

.tier-table table {
  margin-bottom: 0;
}

.tier-table th {
  font-weight: 500;
  color: #6c757d;
}

.coupon-section {
  border: 2px dashed #dee2e6;
}
</style>
