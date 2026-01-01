# QA Testing Checklist - YJS Jewellers Go-Live

## Pre-Testing Setup

- [ ] Environment variables configured in `.env`
- [ ] Razorpay test credentials added
- [ ] Shiprocket test credentials added
- [ ] Migrations executed: `php artisan migrate`
- [ ] Services registered in AppServiceProvider (if needed)
- [ ] Webhook routes excluded from CSRF

---

## 1. RAZORPAY PAYMENT INTEGRATION

### 1.1 Order Creation
| # | Test Case | Expected Result | Status |
|---|-----------|-----------------|--------|
| 1.1.1 | Create Razorpay order for valid order | Returns razorpay_order_id, checkout_options | ☐ |
| 1.1.2 | Create order for already paid order | Returns error "Order already paid" | ☐ |
| 1.1.3 | Create order for unauthorized user | Returns 403 Forbidden | ☐ |
| 1.1.4 | Idempotency: Create order twice | Returns same razorpay_order_id | ☐ |

### 1.2 Payment Verification
| # | Test Case | Expected Result | Status |
|---|-----------|-----------------|--------|
| 1.2.1 | Verify with valid signature | Returns success, order marked paid | ☐ |
| 1.2.2 | Verify with invalid signature | Returns error, payment marked failed | ☐ |
| 1.2.3 | Verify with wrong amount | Returns error "Amount mismatch" | ☐ |
| 1.2.4 | Verify with non-existent order_id | Returns error "Payment record not found" | ☐ |

### 1.3 Payment Methods (Test Cards)
| # | Test Case | Card/UPI | Expected | Status |
|---|-----------|----------|----------|--------|
| 1.3.1 | Card success | 4111 1111 1111 1111 | Payment captured | ☐ |
| 1.3.2 | Card failure | 4000 0000 0000 0002 | Payment failed | ☐ |
| 1.3.3 | UPI success | success@razorpay | Payment captured | ☐ |
| 1.3.4 | UPI failure | failure@razorpay | Payment failed | ☐ |

### 1.4 Retry Payment
| # | Test Case | Expected Result | Status |
|---|-----------|-----------------|--------|
| 1.4.1 | Retry within 24 hours | New razorpay_order created | ☐ |
| 1.4.2 | Retry after 24 hours | Returns error "Window expired" | ☐ |
| 1.4.3 | Retry already paid order | Returns error "Already paid" | ☐ |

### 1.5 Webhooks
| # | Test Case | Expected Result | Status |
|---|-----------|-----------------|--------|
| 1.5.1 | payment.captured webhook | Order marked paid, status confirmed | ☐ |
| 1.5.2 | payment.failed webhook | Order marked failed | ☐ |
| 1.5.3 | Invalid signature | Returns 401 Unauthorized | ☐ |
| 1.5.4 | Duplicate webhook | Returns "Already processed" | ☐ |

### 1.6 Refunds
| # | Test Case | Expected Result | Status |
|---|-----------|-----------------|--------|
| 1.6.1 | Full refund on paid order | Refund created, status pending/processed | ☐ |
| 1.6.2 | Partial refund | Refund created for specified amount | ☐ |
| 1.6.3 | Refund exceeds paid amount | Returns error | ☐ |
| 1.6.4 | Refund on unpaid order | Returns error "No payment found" | ☐ |
| 1.6.5 | refund.processed webhook | Refund status updated | ☐ |

---

## 2. SHIPROCKET SHIPPING INTEGRATION

### 2.1 Serviceability Check
| # | Test Case | Expected Result | Status |
|---|-----------|-----------------|--------|
| 2.1.1 | Valid metro pincode | serviceable: true, rates returned | ☐ |
| 2.1.2 | Invalid pincode | serviceable: false | ☐ |
| 2.1.3 | Remote area pincode | May show limited couriers | ☐ |
| 2.1.4 | Cached response (same pincode) | Fast response from cache | ☐ |

### 2.2 Order Creation in Shiprocket
| # | Test Case | Expected Result | Status |
|---|-----------|-----------------|--------|
| 2.2.1 | Create shipment for confirmed order | shiprocket_order_id, shipment_id saved | ☐ |
| 2.2.2 | Create for pending order | Returns error "Not ready" | ☐ |
| 2.2.3 | Create for unpaid order | Returns error "Not paid" | ☐ |
| 2.2.4 | Idempotency: Create twice | Returns existing shiprocket_order_id | ☐ |
| 2.2.5 | Order without shipping address | Returns error "No address" | ☐ |

### 2.3 AWB Generation
| # | Test Case | Expected Result | Status |
|---|-----------|-----------------|--------|
| 2.3.1 | Generate AWB for valid shipment | AWB returned, order status updated | ☐ |
| 2.3.2 | Generate AWB without shipment | Returns error "No shipment" | ☐ |
| 2.3.3 | Generate AWB twice | Returns existing AWB | ☐ |
| 2.3.4 | Generate with specific courier | Uses specified courier_id | ☐ |

### 2.4 Pickup Scheduling
| # | Test Case | Expected Result | Status |
|---|-----------|-----------------|--------|
| 2.4.1 | Schedule pickup | pickup_scheduled_date updated | ☐ |
| 2.4.2 | Schedule without shipment | Returns error | ☐ |
| 2.4.3 | Schedule with past date | Returns validation error | ☐ |

### 2.5 Tracking
| # | Test Case | Expected Result | Status |
|---|-----------|-----------------|--------|
| 2.5.1 | Track valid AWB | Returns status, activities | ☐ |
| 2.5.2 | Track without AWB | Returns error "No AWB" | ☐ |
| 2.5.3 | Sync tracking updates | Order status updated | ☐ |

### 2.6 Webhooks
| # | Test Case | Expected Result | Status |
|---|-----------|-----------------|--------|
| 2.6.1 | Shipped status webhook | Order status → shipped | ☐ |
| 2.6.2 | Delivered webhook | Order status → delivered, date set | ☐ |
| 2.6.3 | RTO initiated webhook | Order status → returned | ☐ |
| 2.6.4 | RTO delivered webhook | Inventory restored | ☐ |

---

## 3. OFFER/PROMOTION ENGINE

### 3.1 Offer Discovery
| # | Test Case | Expected Result | Status |
|---|-----------|-----------------|--------|
| 3.1.1 | Get offers for cart with items | Returns applicable offers | ☐ |
| 3.1.2 | Get offers for empty cart | Returns empty with message | ☐ |
| 3.1.3 | Offer outside date range | Shows in unavailable list | ☐ |
| 3.1.4 | Inactive offer | Not returned | ☐ |

### 3.2 Offer Application
| # | Test Case | Expected Result | Status |
|---|-----------|-----------------|--------|
| 3.2.1 | Apply percentage discount | Correct discount calculated | ☐ |
| 3.2.2 | Apply flat discount | Fixed amount deducted | ☐ |
| 3.2.3 | Apply with max_discount_amount | Capped at max | ☐ |
| 3.2.4 | Apply to cart below minimum | Returns error "Min not met" | ☐ |
| 3.2.5 | Apply product-specific offer | Only applies to matching products | ☐ |
| 3.2.6 | Apply category-specific offer | Only applies to matching categories | ☐ |

### 3.3 Coupon Validation
| # | Test Case | Expected Result | Status |
|---|-----------|-----------------|--------|
| 3.3.1 | Valid coupon code | Returns offer details, discount | ☐ |
| 3.3.2 | Invalid coupon code | Returns error "Invalid coupon" | ☐ |
| 3.3.3 | Expired coupon | Returns error "Expired" | ☐ |
| 3.3.4 | Coupon already used (per-user limit) | Returns error "Already used" | ☐ |

### 3.4 Offer Snapshot
| # | Test Case | Expected Result | Status |
|---|-----------|-----------------|--------|
| 3.4.1 | Order with offer | order_offers record created | ☐ |
| 3.4.2 | Modify original offer | Snapshot unchanged | ☐ |
| 3.4.3 | Usage tracking | offer_usages record created | ☐ |

### 3.5 Offer Rollback
| # | Test Case | Expected Result | Status |
|---|-----------|-----------------|--------|
| 3.5.1 | Cancel order with offer | Usage marked reversed | ☐ |
| 3.5.2 | Reuse offer after cancel | Offer can be used again | ☐ |

---

## 4. ORDER FLOW (B2C)

### 4.1 Checkout
| # | Test Case | Expected Result | Status |
|---|-----------|-----------------|--------|
| 4.1.1 | Create order from cart | Order created, stock reserved | ☐ |
| 4.1.2 | Create order with offer | Discount applied, snapshot saved | ☐ |
| 4.1.3 | Create order - out of stock | Returns error | ☐ |
| 4.1.4 | Create order - empty cart | Returns error "Cart empty" | ☐ |

### 4.2 Cancellation
| # | Test Case | Expected Result | Status |
|---|-----------|-----------------|--------|
| 4.2.1 | Cancel pending order | Status → cancelled, stock restored | ☐ |
| 4.2.2 | Cancel confirmed order | Status → cancelled, refund initiated | ☐ |
| 4.2.3 | Cancel shipped order | Returns error "Cannot cancel" | ☐ |
| 4.2.4 | Cancel after 24 hours (customer) | Returns error "Window expired" | ☐ |
| 4.2.5 | Admin cancel after 24 hours | Allowed | ☐ |

---

## 5. B2B FLOW

### 5.1 Partner Access
| # | Test Case | Expected Result | Status |
|---|-----------|-----------------|--------|
| 5.1.1 | Partner views products | Products with partner pricing | ☐ |
| 5.1.2 | Partner creates order | Order with partner pricing | ☐ |
| 5.1.3 | Non-partner tries B2B endpoint | Returns 403 | ☐ |

---

## 6. INTEGRATION TESTS

### 6.1 Full Flow - Happy Path
| # | Test Case | Steps | Status |
|---|-----------|-------|--------|
| 6.1.1 | Complete purchase | Cart → Checkout → Pay → Ship → Deliver | ☐ |
| 6.1.2 | Purchase with offer | Cart → Apply Coupon → Checkout → Pay | ☐ |
| 6.1.3 | Cancellation with refund | Checkout → Pay → Cancel → Refund | ☐ |

### 6.2 Error Recovery
| # | Test Case | Steps | Status |
|---|-----------|-------|--------|
| 6.2.1 | Payment retry after failure | Pay (fail) → Retry → Success | ☐ |
| 6.2.2 | Webhook missed | Manual reconciliation works | ☐ |

---

## Sign-off

| Role | Name | Date | Signature |
|------|------|------|-----------|
| QA Lead | | | |
| Dev Lead | | | |
| Product Owner | | | |
