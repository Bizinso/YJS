<template>
  <div class="loyalty-dashboard">
    <div class="container py-5">
      <!-- Header -->
      <div class="loyalty-header text-center mb-5">
        <h1 class="display-4 fw-bold">
          <i class="bi bi-star-fill text-warning"></i> Loyalty Rewards
        </h1>
        <p class="lead text-muted">Earn points with every purchase and unlock exclusive benefits</p>
      </div>

      <!-- Not Logged In -->
      <div v-if="!isLoggedIn" class="text-center py-5">
        <i class="bi bi-person-circle text-muted" style="font-size: 4rem;"></i>
        <h3 class="mt-3">Join Our Rewards Program</h3>
        <p class="text-muted">Sign in or create an account to start earning loyalty points</p>
        <router-link to="/" class="btn btn-primary btn-lg">
          Sign In
        </router-link>
      </div>

      <!-- Logged In Content -->
      <div v-else>
        <!-- Loading -->
        <div v-if="loading" class="text-center py-5">
          <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
          </div>
        </div>

        <div v-else>
          <!-- Points Overview Card -->
          <div class="row mb-5">
            <div class="col-12">
              <div class="card points-card shadow-lg border-0">
                <div class="card-body p-4">
                  <div class="row align-items-center">
                    <!-- Current Tier Badge -->
                    <div class="col-12 col-md-3 text-center mb-4 mb-md-0">
                      <div :class="['tier-badge', `tier-${account?.tier || 'bronze'}`]">
                        <i class="bi bi-gem"></i>
                        <span class="tier-name">{{ account?.tier || 'Bronze' }}</span>
                      </div>
                      <small class="text-muted d-block mt-2">Current Tier</small>
                    </div>

                    <!-- Points Balance -->
                    <div class="col-6 col-md-3 text-center">
                      <div class="points-value display-4 fw-bold text-primary">
                        {{ Number(account?.points_balance || 0).toLocaleString() }}
                      </div>
                      <small class="text-muted">Available Points</small>
                    </div>

                    <!-- Lifetime Points -->
                    <div class="col-6 col-md-3 text-center">
                      <div class="points-value h3 fw-bold text-success">
                        {{ Number(account?.lifetime_points || 0).toLocaleString() }}
                      </div>
                      <small class="text-muted">Lifetime Points</small>
                    </div>

                    <!-- Points Value -->
                    <div class="col-12 col-md-3 text-center mt-4 mt-md-0">
                      <div class="points-value h3 fw-bold text-warning">
                        ₹{{ Number((account?.points_balance || 0) * (pointsConfig?.points_value || 1)).toLocaleString() }}
                      </div>
                      <small class="text-muted">Points Value</small>
                    </div>
                  </div>

                  <!-- Progress to Next Tier -->
                  <div v-if="tierProgress" class="tier-progress mt-4 pt-4 border-top">
                    <div class="d-flex justify-content-between mb-2">
                      <span class="text-muted">Progress to {{ tierProgress.nextTier }}</span>
                      <span class="fw-bold">{{ tierProgress.percentage }}%</span>
                    </div>
                    <div class="progress" style="height: 10px;">
                      <div
                        class="progress-bar bg-gradient"
                        :style="{ width: tierProgress.percentage + '%' }"
                      ></div>
                    </div>
                    <small class="text-muted mt-1 d-block">
                      {{ tierProgress.pointsNeeded.toLocaleString() }} more points to reach {{ tierProgress.nextTier }}
                    </small>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Tier Benefits -->
          <div class="row mb-5">
            <div class="col-12">
              <h4 class="mb-4">
                <i class="bi bi-award"></i> Your Tier Benefits
              </h4>
              <div class="row g-4">
                <div v-for="tier in tiers" :key="tier.slug" class="col-12 col-md-6 col-lg-3">
                  <div
                    :class="[
                      'card tier-card h-100',
                      account?.tier === tier.slug ? 'active-tier' : '',
                      `tier-${tier.slug}`
                    ]"
                  >
                    <div class="card-header text-center py-3">
                      <h5 class="mb-0">{{ tier.name }}</h5>
                      <small>{{ tier.min_points.toLocaleString() }}+ points</small>
                    </div>
                    <div class="card-body">
                      <div class="multiplier text-center mb-3">
                        <span class="display-6 fw-bold">{{ tier.points_multiplier }}x</span>
                        <span class="d-block small text-muted">Points Multiplier</span>
                      </div>
                      <ul class="list-unstyled benefits-list">
                        <li v-for="(benefit, idx) in parseBenefits(tier.benefits)" :key="idx">
                          <i class="bi bi-check-circle-fill text-success me-2"></i>
                          {{ benefit }}
                        </li>
                      </ul>
                    </div>
                    <div v-if="account?.tier === tier.slug" class="card-footer text-center bg-success text-white">
                      <i class="bi bi-check-circle"></i> Current Tier
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Redeem Points -->
          <div class="row mb-5">
            <div class="col-12 col-lg-6">
              <div class="card shadow-sm h-100">
                <div class="card-header bg-primary text-white">
                  <h5 class="mb-0">
                    <i class="bi bi-gift"></i> Redeem Points
                  </h5>
                </div>
                <div class="card-body">
                  <p class="text-muted mb-4">
                    Convert your points to discount on your next purchase
                  </p>
                  <div class="redeem-calculator">
                    <label class="form-label">Points to redeem</label>
                    <input
                      v-model.number="redeemPoints"
                      type="number"
                      class="form-control form-control-lg"
                      :max="account?.points_balance || 0"
                      min="0"
                      step="100"
                    />
                    <div class="mt-3 p-3 bg-light rounded">
                      <div class="d-flex justify-content-between">
                        <span>Discount Value:</span>
                        <strong class="text-success">
                          ₹{{ Number(redeemPoints * (pointsConfig?.points_value || 1)).toLocaleString() }}
                        </strong>
                      </div>
                    </div>
                    <small class="text-muted d-block mt-2">
                      Min. redemption: {{ pointsConfig?.min_redemption || 100 }} points
                    </small>
                  </div>
                </div>
              </div>
            </div>

            <!-- Earning Rules -->
            <div class="col-12 col-lg-6 mt-4 mt-lg-0">
              <div class="card shadow-sm h-100">
                <div class="card-header bg-success text-white">
                  <h5 class="mb-0">
                    <i class="bi bi-plus-circle"></i> How to Earn
                  </h5>
                </div>
                <div class="card-body">
                  <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                      <span><i class="bi bi-cart-check text-primary me-2"></i> Every ₹100 spent</span>
                      <span class="badge bg-primary rounded-pill">
                        {{ pointsConfig?.points_per_rupee || 1 }} point
                      </span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                      <span><i class="bi bi-star text-warning me-2"></i> First Purchase Bonus</span>
                      <span class="badge bg-warning text-dark rounded-pill">100 points</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                      <span><i class="bi bi-cake text-danger me-2"></i> Birthday Bonus</span>
                      <span class="badge bg-danger rounded-pill">2x points</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                      <span><i class="bi bi-people text-success me-2"></i> Referral Bonus</span>
                      <span class="badge bg-success rounded-pill">500 points</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                      <span><i class="bi bi-chat-quote text-info me-2"></i> Product Review</span>
                      <span class="badge bg-info rounded-pill">25 points</span>
                    </li>
                  </ul>
                </div>
              </div>
            </div>
          </div>

          <!-- Transaction History -->
          <div class="row">
            <div class="col-12">
              <div class="card shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center">
                  <h5 class="mb-0">
                    <i class="bi bi-clock-history"></i> Points History
                  </h5>
                  <select v-model="historyFilter" class="form-select form-select-sm w-auto">
                    <option value="all">All</option>
                    <option value="earn">Earned</option>
                    <option value="redeem">Redeemed</option>
                    <option value="expire">Expired</option>
                  </select>
                </div>
                <div class="card-body p-0">
                  <div class="table-responsive">
                    <table class="table table-hover mb-0">
                      <thead class="table-light">
                        <tr>
                          <th>Date</th>
                          <th>Description</th>
                          <th>Type</th>
                          <th class="text-end">Points</th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr v-for="tx in filteredHistory" :key="tx.id">
                          <td>{{ formatDate(tx.created_at) }}</td>
                          <td>{{ tx.description }}</td>
                          <td>
                            <span :class="['badge', getTypeBadgeClass(tx.type)]">
                              {{ tx.type }}
                            </span>
                          </td>
                          <td :class="['text-end fw-bold', tx.points > 0 ? 'text-success' : 'text-danger']">
                            {{ tx.points > 0 ? '+' : '' }}{{ tx.points }}
                          </td>
                        </tr>
                        <tr v-if="filteredHistory.length === 0">
                          <td colspan="4" class="text-center text-muted py-4">
                            No transactions found
                          </td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import axiosCustomer from '@axiosCustomer'
import { toast } from "vue3-toastify"

const isLoggedIn = ref(!!localStorage.getItem('customer_token'))
const loading = ref(true)
const account = ref(null)
const tiers = ref([])
const transactions = ref([])
const pointsConfig = ref(null)
const redeemPoints = ref(0)
const historyFilter = ref('all')

// Tier progress calculation
const tierProgress = computed(() => {
  if (!account.value || !tiers.value.length) return null

  const currentTierIndex = tiers.value.findIndex(t => t.slug === account.value.tier)
  if (currentTierIndex === -1 || currentTierIndex === tiers.value.length - 1) return null

  const currentTier = tiers.value[currentTierIndex]
  const nextTier = tiers.value[currentTierIndex + 1]
  const pointsInRange = account.value.lifetime_points - currentTier.min_points
  const rangeSize = nextTier.min_points - currentTier.min_points
  const percentage = Math.min(100, Math.round((pointsInRange / rangeSize) * 100))
  const pointsNeeded = nextTier.min_points - account.value.lifetime_points

  return {
    nextTier: nextTier.name,
    percentage,
    pointsNeeded: Math.max(0, pointsNeeded)
  }
})

// Filtered transactions
const filteredHistory = computed(() => {
  if (historyFilter.value === 'all') return transactions.value
  return transactions.value.filter(tx => tx.type === historyFilter.value)
})

// Fetch dashboard data
async function fetchDashboard() {
  if (!isLoggedIn.value) return

  loading.value = true
  try {
    const { data } = await axiosCustomer.get('/loyalty/dashboard')
    if (data.success) {
      account.value = data.account
      tiers.value = data.tiers || []
      transactions.value = data.recent_transactions || []
      pointsConfig.value = {
        points_per_rupee: data.points_per_rupee || 1,
        points_value: data.points_value || 1,
        min_redemption: data.min_redemption || 100
      }
    }
  } catch (error) {
    console.error('Failed to fetch loyalty dashboard:', error)
    if (error.response?.status !== 401) {
      toast.error('Failed to load loyalty data')
    }
  } finally {
    loading.value = false
  }
}

// Parse benefits
function parseBenefits(benefits) {
  if (!benefits) return []
  if (typeof benefits === 'string') {
    try {
      return JSON.parse(benefits)
    } catch {
      return [benefits]
    }
  }
  return benefits
}

// Format date
function formatDate(dateStr) {
  if (!dateStr) return '-'
  return new Date(dateStr).toLocaleDateString('en-IN', {
    day: 'numeric',
    month: 'short',
    year: 'numeric'
  })
}

// Get badge class for transaction type
function getTypeBadgeClass(type) {
  const classes = {
    earn: 'bg-success',
    redeem: 'bg-primary',
    expire: 'bg-warning text-dark',
    adjust: 'bg-info'
  }
  return classes[type] || 'bg-secondary'
}

onMounted(() => {
  fetchDashboard()
})
</script>

<style scoped>
.loyalty-dashboard {
  min-height: 60vh;
  background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
}

.points-card {
  background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
  border-radius: 16px;
}

.tier-badge {
  width: 100px;
  height: 100px;
  border-radius: 50%;
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  margin: 0 auto;
  font-size: 1.5rem;
}

.tier-badge i {
  font-size: 2rem;
  margin-bottom: 4px;
}

.tier-badge .tier-name {
  font-size: 0.9rem;
  font-weight: 600;
  text-transform: capitalize;
}

.tier-bronze {
  background: linear-gradient(135deg, #cd7f32 0%, #8b4513 100%);
  color: white;
}

.tier-silver {
  background: linear-gradient(135deg, #c0c0c0 0%, #808080 100%);
  color: white;
}

.tier-gold {
  background: linear-gradient(135deg, #ffd700 0%, #daa520 100%);
  color: #333;
}

.tier-platinum {
  background: linear-gradient(135deg, #e5e4e2 0%, #8a8d8f 100%);
  color: #333;
}

.tier-card {
  border: none;
  border-radius: 12px;
  transition: transform 0.3s ease;
}

.tier-card:hover {
  transform: translateY(-5px);
}

.tier-card.active-tier {
  box-shadow: 0 0 0 3px #198754, 0 10px 30px rgba(25, 135, 84, 0.2);
}

.tier-card .card-header {
  border-radius: 12px 12px 0 0;
}

.tier-card.tier-bronze .card-header {
  background: linear-gradient(135deg, #cd7f32 0%, #8b4513 100%);
  color: white;
}

.tier-card.tier-silver .card-header {
  background: linear-gradient(135deg, #c0c0c0 0%, #808080 100%);
  color: white;
}

.tier-card.tier-gold .card-header {
  background: linear-gradient(135deg, #ffd700 0%, #daa520 100%);
  color: #333;
}

.tier-card.tier-platinum .card-header {
  background: linear-gradient(135deg, #e5e4e2 0%, #8a8d8f 100%);
  color: #333;
}

.benefits-list li {
  padding: 8px 0;
  border-bottom: 1px solid #eee;
}

.benefits-list li:last-child {
  border-bottom: none;
}

.progress-bar.bg-gradient {
  background: linear-gradient(90deg, #ffc107 0%, #28a745 100%);
}
</style>
