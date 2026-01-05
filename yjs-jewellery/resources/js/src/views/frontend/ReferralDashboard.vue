<template>
  <div class="referral-dashboard">
    <div class="container py-5">
      <!-- Header -->
      <div class="referral-header text-center mb-5">
        <h1 class="display-4 fw-bold">
          <i class="bi bi-people-fill text-primary"></i> Refer & Earn
        </h1>
        <p class="lead text-muted">Share the love, earn rewards together</p>
      </div>

      <!-- Not Logged In -->
      <div v-if="!isLoggedIn" class="text-center py-5">
        <i class="bi bi-person-circle text-muted" style="font-size: 4rem;"></i>
        <h3 class="mt-3">Join Our Referral Program</h3>
        <p class="text-muted">Sign in to get your unique referral code and start earning</p>
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
          <!-- Referral Code Card -->
          <div class="row mb-5">
            <div class="col-12 col-lg-8 mx-auto">
              <div class="card referral-code-card shadow-lg border-0">
                <div class="card-body p-5 text-center">
                  <h4 class="mb-3">Your Referral Code</h4>
                  <div class="code-display mb-4">
                    <code class="referral-code">{{ referralCode }}</code>
                    <button
                      @click="copyCode"
                      class="btn btn-outline-primary btn-sm ms-3"
                      title="Copy code"
                    >
                      <i class="bi bi-clipboard"></i>
                    </button>
                  </div>

                  <div class="share-link mb-4">
                    <label class="form-label text-muted">Share this link:</label>
                    <div class="input-group">
                      <input
                        :value="shareLink"
                        type="text"
                        class="form-control"
                        readonly
                      />
                      <button @click="copyLink" class="btn btn-primary">
                        <i class="bi bi-link-45deg"></i> Copy
                      </button>
                    </div>
                  </div>

                  <!-- Share Buttons -->
                  <div class="share-buttons">
                    <p class="text-muted mb-3">Share via:</p>
                    <div class="d-flex justify-content-center gap-3">
                      <button @click="shareWhatsApp" class="btn btn-success btn-lg rounded-circle">
                        <i class="bi bi-whatsapp"></i>
                      </button>
                      <button @click="shareEmail" class="btn btn-danger btn-lg rounded-circle">
                        <i class="bi bi-envelope"></i>
                      </button>
                      <button @click="shareTwitter" class="btn btn-info btn-lg rounded-circle text-white">
                        <i class="bi bi-twitter"></i>
                      </button>
                      <button @click="shareFacebook" class="btn btn-primary btn-lg rounded-circle">
                        <i class="bi bi-facebook"></i>
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Stats Cards -->
          <div class="row mb-5 g-4">
            <div class="col-6 col-md-3">
              <div class="card stat-card h-100 text-center">
                <div class="card-body">
                  <div class="stat-icon text-primary mb-2">
                    <i class="bi bi-people"></i>
                  </div>
                  <div class="stat-value display-6 fw-bold">{{ stats.total_referrals }}</div>
                  <div class="stat-label text-muted">Total Referrals</div>
                </div>
              </div>
            </div>
            <div class="col-6 col-md-3">
              <div class="card stat-card h-100 text-center">
                <div class="card-body">
                  <div class="stat-icon text-success mb-2">
                    <i class="bi bi-check-circle"></i>
                  </div>
                  <div class="stat-value display-6 fw-bold">{{ stats.successful_referrals }}</div>
                  <div class="stat-label text-muted">Successful</div>
                </div>
              </div>
            </div>
            <div class="col-6 col-md-3">
              <div class="card stat-card h-100 text-center">
                <div class="card-body">
                  <div class="stat-icon text-warning mb-2">
                    <i class="bi bi-hourglass-split"></i>
                  </div>
                  <div class="stat-value display-6 fw-bold">{{ stats.pending_referrals }}</div>
                  <div class="stat-label text-muted">Pending</div>
                </div>
              </div>
            </div>
            <div class="col-6 col-md-3">
              <div class="card stat-card h-100 text-center">
                <div class="card-body">
                  <div class="stat-icon text-info mb-2">
                    <i class="bi bi-currency-rupee"></i>
                  </div>
                  <div class="stat-value display-6 fw-bold">
                    ₹{{ Number(stats.total_earnings || 0).toLocaleString() }}
                  </div>
                  <div class="stat-label text-muted">Total Earned</div>
                </div>
              </div>
            </div>
          </div>

          <!-- How It Works -->
          <div class="row mb-5">
            <div class="col-12">
              <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                  <h5 class="mb-0">
                    <i class="bi bi-info-circle"></i> How It Works
                  </h5>
                </div>
                <div class="card-body">
                  <div class="row text-center">
                    <div class="col-12 col-md-4 mb-4 mb-md-0">
                      <div class="step-circle mx-auto mb-3">1</div>
                      <h5>Share Your Code</h5>
                      <p class="text-muted">Share your unique referral code with friends and family</p>
                    </div>
                    <div class="col-12 col-md-4 mb-4 mb-md-0">
                      <div class="step-circle mx-auto mb-3">2</div>
                      <h5>Friend Makes Purchase</h5>
                      <p class="text-muted">When they sign up and make their first purchase using your code</p>
                    </div>
                    <div class="col-12 col-md-4">
                      <div class="step-circle mx-auto mb-3">3</div>
                      <h5>Both Get Rewards</h5>
                      <p class="text-muted">
                        You get <strong>{{ referrerReward }}</strong> and they get <strong>{{ refereeReward }}</strong>!
                      </p>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Referral Discount Banner -->
          <div v-if="refereeDiscount" class="row mb-5">
            <div class="col-12">
              <div class="alert alert-success d-flex align-items-center" role="alert">
                <i class="bi bi-gift-fill fs-3 me-3"></i>
                <div>
                  <h5 class="alert-heading mb-1">You have a referral discount!</h5>
                  <p class="mb-0">
                    Get <strong>{{ refereeDiscount.discount_value }}{{ refereeDiscount.discount_type === 'percentage' ? '%' : '₹' }} off</strong>
                    on your first order. This discount will be automatically applied at checkout.
                  </p>
                </div>
              </div>
            </div>
          </div>

          <!-- Referral History -->
          <div class="row">
            <div class="col-12">
              <div class="card shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center">
                  <h5 class="mb-0">
                    <i class="bi bi-clock-history"></i> Referral History
                  </h5>
                </div>
                <div class="card-body p-0">
                  <div class="table-responsive">
                    <table class="table table-hover mb-0">
                      <thead class="table-light">
                        <tr>
                          <th>Date</th>
                          <th>Friend</th>
                          <th>Status</th>
                          <th class="text-end">Reward</th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr v-for="referral in referrals" :key="referral.id">
                          <td>{{ formatDate(referral.created_at) }}</td>
                          <td>
                            <span v-if="referral.referee">
                              {{ referral.referee.first_name }} {{ referral.referee.last_name }}
                            </span>
                            <span v-else class="text-muted">Pending signup</span>
                          </td>
                          <td>
                            <span :class="['badge', getStatusBadgeClass(referral.status)]">
                              {{ referral.status }}
                            </span>
                          </td>
                          <td class="text-end">
                            <span v-if="referral.status === 'completed'" class="text-success fw-bold">
                              +{{ referral.referrer_reward_type === 'points'
                                ? referral.referrer_reward_value + ' pts'
                                : '₹' + referral.referrer_reward_value }}
                            </span>
                            <span v-else class="text-muted">-</span>
                          </td>
                        </tr>
                        <tr v-if="referrals.length === 0">
                          <td colspan="4" class="text-center text-muted py-4">
                            No referrals yet. Share your code to start earning!
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
const referralCode = ref('')
const shareLink = ref('')
const shareContent = ref({})
const stats = ref({
  total_referrals: 0,
  successful_referrals: 0,
  pending_referrals: 0,
  total_earnings: 0
})
const referrals = ref([])
const refereeDiscount = ref(null)

const referrerReward = computed(() => '500 loyalty points')
const refereeReward = computed(() => '10% off first order')

// Fetch dashboard data
async function fetchDashboard() {
  if (!isLoggedIn.value) return

  loading.value = true
  try {
    const [dashboardRes, discountRes, shareRes] = await Promise.all([
      axiosCustomer.get('/referral/dashboard'),
      axiosCustomer.get('/referral/discount').catch(() => ({ data: { has_discount: false } })),
      axiosCustomer.get('/referral/share-content')
    ])

    if (dashboardRes.data.success) {
      referralCode.value = dashboardRes.data.referral_code
      shareLink.value = dashboardRes.data.share_link
      stats.value = {
        total_referrals: dashboardRes.data.total_referrals || 0,
        successful_referrals: dashboardRes.data.successful_referrals || 0,
        pending_referrals: dashboardRes.data.pending_referrals || 0,
        total_earnings: dashboardRes.data.total_earnings || 0
      }
      referrals.value = dashboardRes.data.referrals || []
    }

    if (discountRes.data.has_discount) {
      refereeDiscount.value = discountRes.data
    }

    if (shareRes.data.success) {
      shareContent.value = shareRes.data.messages || {}
    }
  } catch (error) {
    console.error('Failed to fetch referral dashboard:', error)
    if (error.response?.status !== 401) {
      toast.error('Failed to load referral data')
    }
  } finally {
    loading.value = false
  }
}

// Copy code to clipboard
function copyCode() {
  navigator.clipboard.writeText(referralCode.value)
  toast.success('Referral code copied!')
}

// Copy link to clipboard
function copyLink() {
  navigator.clipboard.writeText(shareLink.value)
  toast.success('Share link copied!')
}

// Share functions
function shareWhatsApp() {
  const message = shareContent.value.whatsapp || `Check out YJS Jewellers! Use my code ${referralCode.value} for 10% off: ${shareLink.value}`
  window.open(`https://wa.me/?text=${encodeURIComponent(message)}`, '_blank')
}

function shareEmail() {
  const subject = shareContent.value.email?.subject || 'Check out YJS Jewellers!'
  const body = shareContent.value.email?.body || `Use my referral code ${referralCode.value} for 10% off your first order: ${shareLink.value}`
  window.open(`mailto:?subject=${encodeURIComponent(subject)}&body=${encodeURIComponent(body)}`, '_blank')
}

function shareTwitter() {
  const message = shareContent.value.twitter || `Check out YJS Jewellers! Use my code ${referralCode.value} for 10% off: ${shareLink.value}`
  window.open(`https://twitter.com/intent/tweet?text=${encodeURIComponent(message)}`, '_blank')
}

function shareFacebook() {
  window.open(`https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(shareLink.value)}`, '_blank')
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

// Get badge class for status
function getStatusBadgeClass(status) {
  const classes = {
    pending: 'bg-warning text-dark',
    completed: 'bg-success',
    expired: 'bg-secondary',
    cancelled: 'bg-danger'
  }
  return classes[status] || 'bg-secondary'
}

onMounted(() => {
  fetchDashboard()
})
</script>

<style scoped>
.referral-dashboard {
  min-height: 60vh;
  background: linear-gradient(135deg, #e3f2fd 0%, #ffffff 100%);
}

.referral-code-card {
  background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
  border-radius: 20px;
}

.code-display {
  display: flex;
  align-items: center;
  justify-content: center;
}

.referral-code {
  font-size: 2.5rem;
  font-weight: bold;
  background: linear-gradient(135deg, #0d6efd 0%, #6610f2 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  padding: 10px 20px;
  border: 3px dashed #0d6efd;
  border-radius: 10px;
}

.share-buttons .btn {
  width: 50px;
  height: 50px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.stat-card {
  border: none;
  border-radius: 12px;
  transition: transform 0.3s ease;
}

.stat-card:hover {
  transform: translateY(-5px);
}

.stat-icon {
  font-size: 2rem;
}

.step-circle {
  width: 60px;
  height: 60px;
  border-radius: 50%;
  background: linear-gradient(135deg, #0d6efd 0%, #6610f2 100%);
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.5rem;
  font-weight: bold;
}
</style>
