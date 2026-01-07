<template>
  <div class="order-success-wrapper">
    <b-container>
      <b-row class="justify-content-center">
        <b-col md="6" class="text-center">

          <!-- Loading State -->
          <div v-if="loading" class="py-5">
            <b-spinner variant="primary" class="mb-3"></b-spinner>
            <p>Loading order details...</p>
          </div>

          <!-- Error State -->
          <div v-else-if="error" class="py-5">
            <div class="error-icon mb-4">
              <i class="fa-solid fa-circle-exclamation text-danger" style="font-size: 4rem;"></i>
            </div>
            <h2 class="mb-3">Something went wrong</h2>
            <p class="text-muted mb-4">{{ error }}</p>
            <b-button variant="primary" @click="router.push('/products')">
              Continue Shopping
            </b-button>
          </div>

          <!-- Success State -->
          <template v-else>
            <!-- Success Icon -->
            <div class="success-icon mb-4">
              <i class="fa-solid fa-circle-check"></i>
            </div>

            <!-- Title -->
            <h2 class="success-title mb-2">
              Order Placed Successfully!
            </h2>

            <!-- Message -->
            <p class="success-message mb-4">
              Thank you for your purchase. Your order has been placed successfully
              and is being processed.
            </p>

            <!-- Order Info Box -->
            <div class="order-info-box mb-4">
              <p><strong>Order ID:</strong> {{ orderData.order_code || orderData.custom_order_code || 'N/A' }}</p>
              <p><strong>Order Total:</strong> ₹{{ formatCurrency(orderData.order_total || orderData.grand_total) }}</p>
              <p><strong>Payment Method:</strong> {{ orderData.payment_method || 'Online Payment' }}</p>
              <p><strong>Estimated Delivery:</strong> {{ formatDeliveryDate(orderData.delivery_date) }}</p>
            </div>

            <!-- Order Items Summary (if available) -->
            <div v-if="orderData.items && orderData.items.length" class="order-items-summary mb-4 text-start">
              <h6 class="mb-3">Items Ordered</h6>
              <div v-for="item in orderData.items" :key="item.id" class="order-item d-flex justify-content-between py-2 border-bottom">
                <span>{{ item.product?.name || item.product_name }} x {{ item.quantity }}</span>
                <span>₹{{ formatCurrency(item.total || item.price * item.quantity) }}</span>
              </div>
            </div>

            <!-- Action Buttons -->
            <div class="action-buttons">
              <b-button variant="primary" class="me-2" @click="viewOrder">
                View Order
              </b-button>
              <b-button variant="outline-secondary" @click="router.push('/products')">
                Continue Shopping
              </b-button>
            </div>
          </template>

        </b-col>
      </b-row>
    </b-container>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import axios from 'axios'

const router = useRouter()
const route = useRoute()

const loading = ref(true)
const error = ref('')
const orderData = ref({})

// Format currency
const formatCurrency = (amount) => {
  if (!amount) return '0'
  return Number(amount).toLocaleString('en-IN')
}

// Format delivery date
const formatDeliveryDate = (date) => {
  if (!date) return '3-5 Business Days'
  try {
    return new Date(date).toLocaleDateString('en-IN', {
      day: 'numeric',
      month: 'long',
      year: 'numeric'
    })
  } catch {
    return '3-5 Business Days'
  }
}

// Fetch order details
const fetchOrderDetails = async () => {
  loading.value = true
  error.value = ''

  // Try to get order ID from route params or query
  const orderId = route.params.orderId || route.query.order_id || route.query.orderId

  // If no order ID, check localStorage for recent order
  const recentOrder = localStorage.getItem('recent_order')

  if (orderId) {
    try {
      const response = await axios.get(`/api/customer/orders/${orderId}`)
      orderData.value = response.data.data || response.data || {}
    } catch (err) {
      console.error('Failed to fetch order:', err)
      // If API fails, try localStorage
      if (recentOrder) {
        try {
          orderData.value = JSON.parse(recentOrder)
        } catch {
          error.value = 'Could not load order details'
        }
      } else {
        error.value = 'Could not load order details'
      }
    }
  } else if (recentOrder) {
    // No order ID in route, use localStorage
    try {
      orderData.value = JSON.parse(recentOrder)
    } catch {
      error.value = 'No order information available'
    }
  } else {
    // No order info available
    error.value = 'No order information available'
  }

  loading.value = false
}

// View order details
const viewOrder = () => {
  const orderId = orderData.value.id || route.params.orderId || route.query.order_id
  if (orderId) {
    router.push(`/orders/${orderId}`)
  } else {
    router.push('/orders')
  }
}

onMounted(() => {
  fetchOrderDetails()
  // Clean up localStorage after displaying
  setTimeout(() => {
    localStorage.removeItem('recent_order')
  }, 5000)
})
</script>

<style scoped>
.order-success-wrapper {
  min-height: 70vh;
  display: flex;
  align-items: center;
  padding: 40px 0;
  background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
}

.success-icon {
  font-size: 5rem;
  color: #28a745;
}

.success-title {
  color: #1a1a2e;
  font-weight: 600;
}

.success-message {
  color: #666;
}

.order-info-box {
  background: #fff;
  border-radius: 12px;
  padding: 25px;
  box-shadow: 0 2px 10px rgba(0,0,0,0.08);
  text-align: left;
}

.order-info-box p {
  margin-bottom: 10px;
  color: #333;
}

.order-info-box p:last-child {
  margin-bottom: 0;
}

.order-items-summary {
  background: #fff;
  border-radius: 12px;
  padding: 20px;
  box-shadow: 0 2px 10px rgba(0,0,0,0.08);
}

.order-item {
  font-size: 14px;
}

.action-buttons {
  display: flex;
  justify-content: center;
  gap: 10px;
  flex-wrap: wrap;
}
</style>
