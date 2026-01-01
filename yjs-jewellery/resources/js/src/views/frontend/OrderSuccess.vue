<template>
  <div class="order-success-wrapper">
    <b-container>
      <b-row class="justify-content-center">
        <b-col md="6" class="text-center">

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
            <p><strong>Order ID:</strong> #ORD123456</p>
            <p><strong>Payment Method:</strong> Online Payment</p>
            <p><strong>Estimated Delivery:</strong> 3–5 Business Days</p>
          </div>

          <!-- Action Buttons -->
          <div class="action-buttons">
            <b-button variant="primary" class="me-2">
              View Order
            </b-button>
            <b-button variant="outline-secondary">
              Continue Shopping
            </b-button>
          </div>

        </b-col>
      </b-row>
    </b-container>
  </div>
</template>
