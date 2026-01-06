# YJS Jewellery E-Commerce Platform - QA Documentation

**Document Version:** 1.0
**Generated:** 2026-01-06
**Purpose:** Manual End-to-End Testing Coverage

---

# TABLE OF CONTENTS

1. [B2C End-to-End User Flows](#1-b2c-end-to-end-user-flows)
2. [B2B Enterprise User Flows](#2-b2b-enterprise-user-flows)
3. [Admin/Back-Office Flows](#3-adminback-office-flows)
4. [Business Rules & Validations](#4-business-rules--validations)
5. [Negative & Edge Cases](#5-negative--edge-cases)
6. [Manual QA Test Cases](#6-manual-qa-test-cases)

---

# 1. B2C END-TO-END USER FLOWS

## 1.1 Customer Registration & Login

### 1.1.1 OTP-Based Registration/Login Flow

**Step-by-Step Flow:**
1. Customer enters phone number on login page
2. System sends OTP via `/api/customer/send-otp`
3. Customer enters received OTP
4. System verifies OTP via `/api/customer/verify-otp`
5. If new user: Account created automatically
6. If existing user: Logged in
7. System returns Sanctum token

**Validations:**
| Field | Validation Rule |
|-------|----------------|
| Phone | Required, valid format, 10 digits |
| OTP | Required, 6 digits, valid within expiry |

**Error Scenarios:**
| Scenario | Expected Behavior | HTTP Code |
|----------|-------------------|-----------|
| Invalid phone format | Return validation error | 422 |
| OTP expired | Return "OTP expired" error | 400 |
| Invalid OTP | Return "Invalid OTP" error | 400 |
| OTP not sent | Return "Request OTP first" error | 400 |
| Too many OTP requests | Rate limit error | 429 |

**System Behavior:**
- OTP expires after configured time (default: 5 minutes)
- New user gets `user_type = 'customer'` and `status = 'active'`
- Sanctum token returned with user profile

---

### 1.1.2 Password-Based Login Flow

**Precondition:** Customer has set a password

**Step-by-Step Flow:**
1. Customer enters email/phone and password
2. System validates credentials via `/api/customer/login-password`
3. If valid: Return Sanctum token
4. If invalid: Return error

**Validations:**
| Field | Validation Rule |
|-------|----------------|
| Email/Phone | Required, exists in system |
| Password | Required, matches stored hash |

**Error Scenarios:**
| Scenario | Expected Behavior | HTTP Code |
|----------|-------------------|-----------|
| Wrong password | Return "Invalid credentials" | 401 |
| User without password | Return "Use OTP login" | 400 |
| Inactive user | Return "Account disabled" | 403 |
| Non-existent user | Return "User not found" | 404 |

---

### 1.1.3 Password Reset Flow

**Step-by-Step Flow:**
1. Customer requests reset via `/api/customer/forgot-password`
2. System sends OTP to registered phone/email
3. Customer verifies OTP via `/api/customer/verify-reset-otp`
4. System returns reset token
5. Customer sets new password via `/api/customer/reset-password`

**Validations:**
| Field | Validation Rule |
|-------|----------------|
| New Password | Min 8 chars, confirmed |
| Reset Token | Valid, not expired |

**Error Scenarios:**
| Scenario | Expected Behavior |
|----------|-------------------|
| Invalid reset token | "Invalid or expired token" |
| Weak password | Password strength validation error |
| Password mismatch | "Passwords do not match" |

---

## 1.2 Product Browsing

### 1.2.1 Product Listing Flow

**Step-by-Step Flow:**
1. Customer accesses `/api/customer/products`
2. System returns paginated product list
3. Customer can filter by category, purity, occasion
4. Customer can search by keyword

**Available Filters (from routes):**
- `/api/customer/filter/category` - Category options
- `/api/customer/filter/purity` - Purity options
- `/api/customer/filter/occasion` - Occasion options

**Expected Response:**
```json
{
  "success": true,
  "data": {
    "data": [...products],
    "current_page": 1,
    "total": 100
  }
}
```

**Product Data Includes:**
- id, name, slug, sku
- base_price, final_price
- available_stock
- main_image, media
- category, sub_category
- is_featured

---

### 1.2.2 Product Detail Flow

**Step-by-Step Flow:**
1. Customer accesses `/api/customer/product/{id}`
2. System returns complete product details
3. Includes variants, attributes, reviews

**Related Products:**
- Available via `/api/customer/products/relatedProducts`
- Based on category/tags

---

### 1.2.3 Out-of-Stock Behavior

**System Behavior:**
| Condition | Display | Action Allowed |
|-----------|---------|----------------|
| `available_stock > 0` | In Stock | Add to Cart |
| `available_stock = 0` | Out of Stock | View Only |
| `status = 'inactive'` | Not Displayed | None |

---

## 1.3 Cart Operations

### 1.3.1 Add to Cart Flow

**Endpoint:** `POST /api/customer/cart`

**Step-by-Step Flow:**
1. Customer selects product and quantity
2. System validates stock availability
3. System calculates prices (base + charges + tax)
4. Item added to cart
5. Cart total updated

**Request Body:**
```json
{
  "product_id": 123,
  "quantity": 1
}
```

**Validations:**
| Rule | Error Message |
|------|---------------|
| Product must exist | "Product not found" |
| Product must be active | "Product unavailable" |
| Quantity > 0 | "Quantity must be positive" |
| Quantity <= available_stock | "Insufficient stock" |

**System Behavior:**
- If product already in cart: Quantity incremented
- Cart stores: `product_base_price`, `charges_total`, `tax_total`, `cart_total`

---

### 1.3.2 Update Cart Flow

**Endpoint:** `PUT /api/customer/cart/{id}`

**Validations:**
- New quantity <= available_stock
- Cannot update other user's cart items

**Error Scenarios:**
| Scenario | Expected Behavior |
|----------|-------------------|
| Exceed stock | "Cannot exceed available stock" |
| Other user's item | 404 Not Found |
| Invalid item ID | 404 Not Found |

---

### 1.3.3 Remove from Cart Flow

**Endpoint:** `DELETE /api/customer/cart/{id}`

**Validations:**
- Can only remove own cart items

---

### 1.3.4 Clear Cart Flow

**Endpoint:** `DELETE /api/customer/cart`

**Behavior:**
- Removes all items for authenticated user
- Does not affect other users' carts

---

### 1.3.5 Cart Sync from Local Storage

**Endpoint:** `POST /api/customer/cart/sync`

**Purpose:** Merge guest cart (localStorage) with server cart after login

**Behavior:**
- Merges local items with existing cart
- Adjusts quantities to available stock
- Returns errors for invalid products

---

## 1.4 Checkout Flow

### 1.4.1 Get Checkout Summary

**Endpoint:** `GET /api/customer/checkout/summary`

**Step-by-Step Flow:**
1. System validates cart is not empty
2. System checks stock availability for all items
3. System calculates subtotal, taxes, shipping
4. Returns summary with customer addresses

**Response Includes:**
- Cart items with current prices
- Stock issues (if any)
- Customer addresses
- Tax breakdown
- Shipping charges

**Stock Issue Detection:**
| Issue | Flag |
|-------|------|
| Product out of stock | `has_stock_issues: true` |
| Quantity exceeds stock | `has_stock_issues: true` |
| Product inactive | `has_stock_issues: true` |

---

### 1.4.2 Validate Cart

**Endpoint:** `POST /api/customer/checkout/validate`

**Returns:**
```json
{
  "valid": true/false,
  "issues": [
    {
      "product_id": 123,
      "issue": "insufficient_stock",
      "available": 5,
      "requested": 10
    }
  ]
}
```

---

### 1.4.3 Address Selection

**Step-by-Step Flow:**
1. Customer views saved addresses
2. Customer selects billing address
3. Customer selects shipping address (can be same)
4. Addresses validated before order creation

**Address Types:**
- `billing`
- `shipping`
- `both`

**Validations:**
- Address must belong to authenticated customer
- Address must have all required fields

---

### 1.4.4 Check Serviceability

**Endpoint:** `POST /api/customer/checkout/serviceability`

**Request:**
```json
{
  "pincode": "400001"
}
```

**Validations:**
- Pincode: Required, 6 digits
- Calls Shiprocket API for serviceability check

**Response:**
```json
{
  "serviceable": true,
  "estimated_delivery": "3-5 days",
  "cod_available": true
}
```

---

### 1.4.5 Apply Coupon

**Endpoint:** `POST /api/customer/checkout/coupon`

**Request:**
```json
{
  "coupon_code": "SAVE10"
}
```

**Validations:**
- Coupon must exist and be active
- Coupon must be within validity period
- Cart must meet minimum amount requirement
- User must not exceed usage limit

---

### 1.4.6 Create Order

**Endpoint:** `POST /api/customer/checkout/order`

**Request:**
```json
{
  "billing_address_id": 1,
  "shipping_address_id": 2,
  "notes": "Optional notes"
}
```

**Step-by-Step Flow:**
1. Validate cart not empty
2. Validate addresses belong to customer
3. Validate stock availability (final check)
4. Calculate final totals
5. Create order record
6. Create order items
7. Apply offer if selected
8. **ASSUMPTION:** Stock not deducted until payment success
9. Clear cart
10. Return order with payment options

**Order Created With:**
- `order_status = 'pending'`
- `payment_status = 'pending'`
- `custom_order_code` auto-generated

---

## 1.5 Payment Flow

### 1.5.1 Initiate Payment

**Endpoint:** `POST /api/customer/orders/{order}/payment`

**Step-by-Step Flow:**
1. Validate order belongs to customer
2. Validate order is pending payment
3. Create Razorpay order
4. Return checkout options for frontend

**Response:**
```json
{
  "success": true,
  "data": {
    "razorpay_order_id": "order_xxx",
    "amount": 10000,
    "currency": "INR",
    "key": "rzp_test_xxx"
  }
}
```

---

### 1.5.2 Verify Payment

**Endpoint:** `POST /api/customer/payment/verify`

**Request:**
```json
{
  "razorpay_order_id": "order_xxx",
  "razorpay_payment_id": "pay_xxx",
  "razorpay_signature": "xxx"
}
```

**Step-by-Step Flow:**
1. Verify signature using Razorpay SDK
2. If valid: Update order and payment status
3. If invalid: Mark payment failed

**On Payment Success:**
- `order.payment_status = 'paid'`
- `order.order_status = 'confirmed'`
- OrderPayment record created with `status = 'success'`
- **ASSUMPTION:** Stock deducted at this point

**On Payment Failure:**
- `order.payment_status = 'failed'`
- OrderPayment record with `status = 'failed'`
- Error details stored in `error_code`, `error_description`

---

### 1.5.3 Payment Status Check

**Endpoint:** `GET /api/customer/orders/{order}/payment-status`

**Response:**
```json
{
  "order_status": "pending",
  "payment_status": "pending",
  "can_retry": true
}
```

---

### 1.5.4 Retry Payment

**Endpoint:** `POST /api/customer/orders/{order}/retry-payment`

**Conditions:**
- Order must be in `pending` status
- Payment must be `failed` or `pending`
- Order not expired

**Behavior:**
- Creates new Razorpay order
- Previous payment attempt retained in history

---

## 1.6 Order Management (Customer)

### 1.6.1 View Orders

**Endpoint:** `GET /api/customer/orders`

**Filters:**
- `status` - Filter by order status
- `search` - Search by order code

**Response:**
- Paginated list of customer's orders only
- Cannot see other customers' orders

---

### 1.6.2 Order Detail

**Endpoint:** `GET /api/customer/orders/{id}`

**Includes:**
- Order items with product details
- Billing/Shipping addresses
- Payment information
- Applied offers
- Shipment tracking (if shipped)

---

### 1.6.3 Order Tracking

**Endpoint:** `GET /api/customer/orders/{id}/tracking`

**Returns:**
- Current order status
- Shipment tracking details (AWB, carrier)
- Timeline of status changes
- Estimated delivery

---

### 1.6.4 Order Statistics

**Endpoint:** `GET /api/customer/orders/statistics`

**Returns:**
```json
{
  "total_orders": 10,
  "pending_orders": 2,
  "completed_orders": 7,
  "cancelled_orders": 1,
  "total_spent": 50000
}
```

---

## 1.7 Order Cancellation (Customer)

### 1.7.1 Cancel Order Flow

**Endpoint:** `POST /api/customer/orders/{id}/cancel`

**Request:**
```json
{
  "reason": "Changed my mind"
}
```

**Cancellation Eligibility (from Order model `canBeCancelled()`):**

| Order Status | Can Cancel |
|--------------|------------|
| pending | YES |
| confirmed | YES |
| processing | YES (with admin approval) |
| shipped | NO |
| pickup_generated | NO |
| picked_up | NO |
| delivered | NO |
| cancelled | NO |
| returned | NO |

**Step-by-Step Flow:**
1. Validate order belongs to customer
2. Check cancellation eligibility
3. Create CancellationRequest record
4. If payment was made: Create RefundRequest automatically
5. Update order status to `cancelled`
6. Restore stock

**System Behavior on Cancellation:**
- Stock restored to `available_stock`
- If `payment_status = 'paid'`: RefundRequest created with:
  - `status = 'pending'`
  - `refund_type = 'full'`
  - `source = 'cancellation'`

---

## 1.8 Returns Flow (Customer)

### 1.8.1 Check Return Eligibility

**Endpoint:** `GET /api/customer/returns/eligibility/{orderId}`

**Eligibility Criteria:**
- Order must be `delivered`
- Within return window (configurable, e.g., 7-30 days)
- Items must be returnable (based on policy)

---

### 1.8.2 Get Return Policy

**Endpoint:** `GET /api/customer/returns/policy`

**Returns:**
- Return window (days)
- Allowed reasons
- Restocking fee policy
- Refund methods available

---

### 1.8.3 Create Return Request

**Endpoint:** `POST /api/customer/returns`

**Request:**
```json
{
  "order_id": 123,
  "reason_code": "defective",
  "reason_description": "Product has scratches",
  "customer_notes": "Found defects on arrival",
  "items": [
    {
      "order_item_id": 456,
      "quantity": 1,
      "reason": "defective"
    }
  ],
  "return_type": "refund",
  "images": ["base64_image_data"]
}
```

**Return Types:**
- `refund` - Money back
- `store_credit` - Store credit

**Return Request Created With:**
- `status = 'pending'`
- `return_code` auto-generated

---

### 1.8.4 Return Request Status Flow

```
pending → under_review → approved → pickup_scheduled → picked_up → received → inspected → refund_initiated → refund_completed → closed
                      ↘ rejected → closed
```

---

### 1.8.5 View Return Requests

**Endpoint:** `GET /api/customer/returns`

**Returns:** List of customer's return requests with status

---

### 1.8.6 Cancel Return Request

**Endpoint:** `POST /api/customer/returns/{id}/cancel`

**Can Cancel When:**
- Status is `pending` or `approved`
- Pickup not yet completed

---

### 1.8.7 Return Tracking

**Endpoint:** `GET /api/customer/returns/{id}/tracking`

**Returns:**
- Current status
- Status history with timestamps
- Pickup details (if scheduled)
- Refund status (if initiated)

---

## 1.9 Exchanges Flow (Customer)

### 1.9.1 Check Exchange Eligibility

**Endpoint:** `GET /api/customer/exchanges/eligibility/{orderId}`

---

### 1.9.2 Get Exchange Options

**Endpoint:** `GET /api/customer/exchanges/options/{productId}`

**Returns:**
- Available exchange products
- Price differences
- Stock availability

---

### 1.9.3 Create Exchange Request

**Endpoint:** `POST /api/customer/exchanges`

**Request:**
```json
{
  "order_id": 123,
  "reason_code": "size_issue",
  "reason_description": "Size too small",
  "items": [
    {
      "order_item_id": 456,
      "quantity": 1,
      "new_product_id": 789,
      "new_variant_id": null
    }
  ]
}
```

---

### 1.9.4 Exchange Status Flow

```
pending → under_review → approved → awaiting_return → return_received → processing → shipped → delivered → closed
                      ↘ rejected → closed
```

---

### 1.9.5 Price Adjustment Types

| Type | Scenario |
|------|----------|
| `none` | Same price |
| `pay_extra` | New item costs more |
| `refund_difference` | New item costs less |

---

## 1.10 Cancellation Requests Flow (Formal)

### 1.10.1 Check Cancellation Eligibility

**Endpoint:** `GET /api/customer/cancellations/eligibility/{orderId}`

---

### 1.10.2 Create Cancellation Request

**Endpoint:** `POST /api/customer/cancellations`

**Request:**
```json
{
  "order_id": 123,
  "cancellation_type": "full",
  "reason_code": "changed_mind",
  "reason_description": "Found better price elsewhere"
}
```

**Cancellation Types:**
- `full` - Cancel entire order
- `partial` - Cancel specific items (if supported)

---

### 1.10.3 Cancellation Status Flow

```
pending → under_review → approved → refund_initiated → refund_completed → closed
                      ↘ rejected → closed
```

---

## 1.11 Wishlist Operations

### 1.11.1 View Wishlist

**Endpoint:** `GET /api/customer/wishlist`

---

### 1.11.2 Add to Wishlist

**Endpoint:** `POST /api/customer/wishlist`

**Request:**
```json
{
  "product_id": 123
}
```

---

### 1.11.3 Toggle Wishlist

**Endpoint:** `POST /api/customer/wishlist/toggle`

**Behavior:**
- If in wishlist: Remove
- If not in wishlist: Add

---

### 1.11.4 Move to Cart

**Endpoint:** `POST /api/customer/wishlist/{id}/move-to-cart`

**Behavior:**
- Add to cart with quantity 1
- Remove from wishlist
- Validates stock availability

---

## 1.12 Reviews

### 1.12.1 Can Review Check

**Endpoint:** `GET /api/customer/products/{productId}/can-review`

**Eligibility:**
- Must have purchased product
- Order must be delivered
- Must not have already reviewed

---

### 1.12.2 Submit Review

**Endpoint:** `POST /api/customer/reviews`

**Request:**
```json
{
  "product_id": 123,
  "order_id": 456,
  "rating": 5,
  "title": "Great product",
  "review": "Excellent quality"
}
```

**Validations:**
- Rating: 1-5
- Must be eligible to review

---

### 1.12.3 My Reviews

**Endpoint:** `GET /api/customer/reviews`

---

### 1.12.4 Pending Reviews

**Endpoint:** `GET /api/customer/reviews/pending`

**Returns:** Products purchased but not yet reviewed

---

## 1.13 Loyalty Points (Customer)

### 1.13.1 View Dashboard

**Endpoint:** `GET /api/customer/loyalty/dashboard`

**Returns:**
- Current points balance
- Current tier
- Points history (recent)
- Tier benefits

---

### 1.13.2 View Balance

**Endpoint:** `GET /api/customer/loyalty/balance`

---

### 1.13.3 Points History

**Endpoint:** `GET /api/customer/loyalty/history`

**Transaction Types:**
- `earned` - Points earned from purchase
- `redeemed` - Points used for discount
- `expired` - Points that expired
- `adjusted` - Admin adjustment

---

### 1.13.4 Calculate Points for Cart

**Endpoint:** `GET /api/customer/loyalty/calculate`

**Returns:** Points that will be earned for current cart

---

### 1.13.5 Preview Redemption

**Endpoint:** `GET /api/customer/loyalty/preview-redemption`

**Returns:**
- Points available to redeem
- Discount value
- Points to INR conversion rate

---

### 1.13.6 Expiring Points

**Endpoint:** `GET /api/customer/loyalty/expiring`

**Returns:** Points expiring soon with dates

---

## 1.14 Referral Program (Customer)

### 1.14.1 View Dashboard

**Endpoint:** `GET /api/customer/referral/dashboard`

**Returns:**
- Referral code
- Referral count
- Rewards earned
- Pending rewards

---

### 1.14.2 Get Referral Code

**Endpoint:** `GET /api/customer/referral/code`

---

### 1.14.3 Validate Referral Code

**Endpoint:** `POST /api/customer/referral/validate`

**Request:**
```json
{
  "code": "REF123ABC"
}
```

---

### 1.14.4 Apply Referral Code

**Endpoint:** `POST /api/customer/referral/apply`

**Validations:**
- Cannot use own referral code
- Cannot use already used code
- Must be first-time purchase

---

## 1.15 Promotions & Offers (Customer)

### 1.15.1 View Applicable Offers

**Endpoint:** `GET /api/customer/promotions/applicable`

**Returns:**
- Offers applicable to current cart
- Auto-applied offers
- Coupon-required offers

---

### 1.15.2 Flash Sales

**Endpoint:** `GET /api/customer/promotions/flash-sales`

**Returns:**
- Active flash sales
- Time remaining
- Stock remaining
- Original vs sale price

---

### 1.15.3 Active Promotions

**Endpoint:** `GET /api/customer/promotions/active`

**Returns:**
- All active promotions
- BOGO deals
- Combo offers
- Tiered discounts

---

## 1.16 Invoice (Customer)

### 1.16.1 Get Invoice

**Endpoint:** `GET /api/customer/orders/{order}/invoice`

**Precondition:** Order must be `paid`

---

### 1.16.2 Download Invoice PDF

**Endpoint:** `GET /api/customer/orders/{order}/invoice/download`

**Returns:** PDF file

---

## 1.17 Notifications (Customer)

### 1.17.1 View Notifications

**Endpoint:** `GET /api/customer/notifications`

---

### 1.17.2 Unread Count

**Endpoint:** `GET /api/customer/notifications/unread-count`

---

### 1.17.3 Mark as Read

**Endpoint:** `POST /api/customer/notifications/{id}/read`

---

### 1.17.4 Mark All as Read

**Endpoint:** `POST /api/customer/notifications/read-all`

---

# 2. B2B ENTERPRISE USER FLOWS

## 2.1 Partner Registration & Approval

### 2.1.1 Partner Registration Flow

**Endpoint:** `POST /api/partner/register`

**Step-by-Step Flow:**
1. Business submits registration form
2. System creates Partner record with `status = 'pending'`
3. Admin reviews application
4. Admin approves/rejects
5. Partner notified of decision

**Registration Fields:**
```json
{
  "first_name": "John",
  "last_name": "Doe",
  "email": "partner@business.com",
  "phone": "9876543210",
  "business_name": "ABC Jewellers",
  "business_type": "retailer",
  "gst_number": "29ABCDE1234F1ZK",
  "address": "123 Business St",
  "city": "Mumbai",
  "state": "Maharashtra"
}
```

**Partner Status Flow:**
```
pending → approved
        ↘ rejected
```

---

### 2.1.2 Check Approval Status

**Endpoint:** `GET /api/partner/profile/approval-status`

**Response:**
```json
{
  "status": "pending",
  "submitted_at": "2026-01-01",
  "reviewed_at": null,
  "rejection_reason": null
}
```

---

## 2.2 Partner Authentication

### 2.2.1 OTP Login

Same as customer flow but with partner-specific routes:
- `POST /api/partner/send-otp`
- `POST /api/partner/verify-otp`

### 2.2.2 Password Login

**Endpoint:** `POST /api/partner/login-password`

**Additional Validation:**
- Partner must be `approved` to login
- Unapproved partners cannot login

---

## 2.3 Partner Dashboard

### 2.3.1 Dashboard Overview

**Endpoint:** `GET /api/partner/dashboard`

**Returns:**
- Total orders
- Pending orders
- Total spent
- Recent orders

---

### 2.3.2 Order Analytics

**Endpoint:** `GET /api/partner/dashboard/order-analytics`

**Parameters:**
- `period` - `daily`, `weekly`, `monthly`

---

### 2.3.3 Spending Analytics

**Endpoint:** `GET /api/partner/dashboard/spending-analytics`

**Parameters:**
- `year` - Year to analyze

---

### 2.3.4 Frequently Ordered Products

**Endpoint:** `GET /api/partner/dashboard/frequent-products`

---

## 2.4 Partner Product Access

### 2.4.1 Browse Products

**Endpoint:** `GET /api/partner/products`

**ASSUMPTION:** Partners see same products as customers unless B2B-specific pricing implemented

---

### 2.4.2 Category Products

**Endpoint:** `GET /api/partner/products/category/{name}`

---

## 2.5 Partner Order Management

### 2.5.1 View Orders

**Endpoint:** `GET /api/partner/orders`

**Filters:**
- `status` - Order status
- `search` - Order code search

**LIMITATION:** Only sees own organization's orders

---

### 2.5.2 Order Detail

**Endpoint:** `GET /api/partner/orders/{id}`

---

### 2.5.3 Cancel Order

**Endpoint:** `POST /api/partner/orders/{id}/cancel`

**Same rules as customer cancellation**

---

### 2.5.4 Reorder

**Endpoint:** `POST /api/partner/orders/{id}/reorder`

**Behavior:**
- Creates new cart with same items
- Shows out-of-stock warnings if any items unavailable

---

## 2.6 B2B Partner Inquiries (Bulk Orders)

**This is the key B2B feature distinguishing partners from customers**

### 2.6.1 Create Inquiry

**Endpoint:** `POST /api/partner/inquiries`

**Request:**
```json
{
  "subject": "Bulk Order - Gold Rings",
  "description": "Need 50 units of various gold rings",
  "priority": "high",
  "items": [
    {
      "product_id": 123,
      "quantity": 20,
      "notes": "Prefer 22k gold"
    },
    {
      "product_id": 456,
      "quantity": 30,
      "notes": "Mixed sizes"
    }
  ]
}
```

**Priority Levels:**
- `low`
- `normal`
- `high`
- `urgent`

---

### 2.6.2 Inquiry Status Flow

```
pending → under_review → quoted → accepted → in_progress → shipped → delivered → closed
                              ↘ rejected (by partner) → closed
       ↘ rejected (by admin) → closed
```

---

### 2.6.3 View Inquiries

**Endpoint:** `GET /api/partner/inquiries`

**Filters:**
- `status`
- `search`

---

### 2.6.4 View Inquiry Statistics

**Endpoint:** `GET /api/partner/inquiries/statistics`

**Returns:**
```json
{
  "total_inquiries": 10,
  "pending_quotes": 3,
  "active_inquiries": 5,
  "completed": 2
}
```

---

### 2.6.5 Update Inquiry

**Endpoint:** `PUT /api/partner/inquiries/{id}`

**Can update:**
- Subject
- Description
- Priority

**Cannot update:**
- Once status is beyond `pending`

---

### 2.6.6 Add Item to Inquiry

**Endpoint:** `POST /api/partner/inquiries/{id}/items`

**Can add when:**
- Status is `pending` or `under_review`

---

### 2.6.7 Remove Item from Inquiry

**Endpoint:** `DELETE /api/partner/inquiries/{id}/items/{itemId}`

---

### 2.6.8 Accept Quote

**Endpoint:** `POST /api/partner/inquiries/{id}/accept-quote`

**Preconditions:**
- Status must be `quoted`
- Quote must not be expired

**System Behavior:**
- Status changes to `accepted`
- Payment process initiated (if applicable)

---

### 2.6.9 Reject Quote

**Endpoint:** `POST /api/partner/inquiries/{id}/reject-quote`

**Request:**
```json
{
  "reason": "Price too high"
}
```

---

### 2.6.10 Cancel Inquiry

**Endpoint:** `POST /api/partner/inquiries/{id}/cancel`

**Can Cancel When:**
- Status is `pending` or `under_review`

---

### 2.6.11 Send Message

**Endpoint:** `POST /api/partner/inquiries/{id}/message`

**Request:**
```json
{
  "message": "Can you provide bulk discount?"
}
```

**Used for:**
- Clarifications
- Negotiations
- Status updates

---

### 2.6.12 Inquiry Tracking

**Endpoint:** `GET /api/partner/inquiries/{id}/tracking`

**Returns:**
- Status history
- Messages
- Tracking updates (for shipped items)

---

## 2.7 B2B Limitations

| Feature | Status | Notes |
|---------|--------|-------|
| Organization Hierarchy | NOT IMPLEMENTED | Single partner = single user |
| Multi-user per Org | NOT IMPLEMENTED | No sub-users |
| Approval Workflows | PARTIAL | Quote acceptance only |
| PO Number | NOT IMPLEMENTED | No PO tracking |
| Credit Limits | NOT IMPLEMENTED | Pay per order |
| Net Payment Terms | NOT IMPLEMENTED | Immediate payment only |
| B2B-specific Pricing | NOT IMPLEMENTED | Same as B2C pricing |

---

# 3. ADMIN/BACK-OFFICE FLOWS

## 3.A ROLE & PERMISSION MATRIX

### Available Roles (from database)

| Role | Description |
|------|-------------|
| Super Admin | Full system access |
| Admin | Administrative access |
| Operations | Order and inventory management |
| Finance | Payment and refund management |
| Support | Customer support and tickets |
| Marketing | Offers and promotions |

### Permission Matrix

| Action / Module | Super Admin | Admin | Operations | Finance | Support | Marketing |
|-----------------|-------------|-------|------------|---------|---------|-----------|
| **DASHBOARD** |||||||
| View Dashboard | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| View Reports | ✓ | ✓ | ✓ | ✓ | ✗ | ✓ |
| Export Reports | ✓ | ✓ | ✓ | ✓ | ✗ | ✗ |
| **ORDERS** |||||||
| View Orders | ✓ | ✓ | ✓ | ✓ | ✓ | ✗ |
| Update Order Status | ✓ | ✓ | ✓ | ✗ | ✗ | ✗ |
| Cancel Order | ✓ | ✓ | ✓ | ✗ | ✗ | ✗ |
| Process Refund | ✓ | ✓ | ✗ | ✓ | ✗ | ✗ |
| Hold/Release Order | ✓ | ✓ | ✓ | ✗ | ✗ | ✗ |
| Override Order | ✓ | ✓ | ✗ | ✗ | ✗ | ✗ |
| **PRODUCTS** |||||||
| View Products | ✓ | ✓ | ✓ | ✗ | ✓ | ✓ |
| Create Product | ✓ | ✓ | ✗ | ✗ | ✗ | ✗ |
| Edit Product | ✓ | ✓ | ✗ | ✗ | ✗ | ✗ |
| Delete Product | ✓ | ✓ | ✗ | ✗ | ✗ | ✗ |
| Bulk Import | ✓ | ✓ | ✗ | ✗ | ✗ | ✗ |
| **INVENTORY** |||||||
| View Inventory | ✓ | ✓ | ✓ | ✗ | ✗ | ✗ |
| Adjust Stock | ✓ | ✓ | ✓ | ✗ | ✗ | ✗ |
| Create Transfer | ✓ | ✓ | ✓ | ✗ | ✗ | ✗ |
| Approve Transfer | ✓ | ✓ | ✗ | ✗ | ✗ | ✗ |
| **CUSTOMERS** |||||||
| View Customers | ✓ | ✓ | ✓ | ✓ | ✓ | ✗ |
| Edit Customer | ✓ | ✓ | ✗ | ✗ | ✓ | ✗ |
| Disable Customer | ✓ | ✓ | ✗ | ✗ | ✗ | ✗ |
| **PARTNERS** |||||||
| View Partners | ✓ | ✓ | ✓ | ✓ | ✓ | ✗ |
| Approve Partner | ✓ | ✓ | ✗ | ✗ | ✗ | ✗ |
| Reject Partner | ✓ | ✓ | ✗ | ✗ | ✗ | ✗ |
| **RETURNS/EXCHANGES** |||||||
| View Returns | ✓ | ✓ | ✓ | ✓ | ✓ | ✗ |
| Approve Return | ✓ | ✓ | ✓ | ✗ | ✗ | ✗ |
| Reject Return | ✓ | ✓ | ✓ | ✗ | ✗ | ✗ |
| Process Refund | ✓ | ✓ | ✗ | ✓ | ✗ | ✗ |
| **FINANCE** |||||||
| View Finance | ✓ | ✓ | ✗ | ✓ | ✗ | ✗ |
| Approve Refund | ✓ | ✓ | ✗ | ✓ | ✗ | ✗ |
| Create Credit Note | ✓ | ✓ | ✗ | ✓ | ✗ | ✗ |
| Reconcile Settlement | ✓ | ✓ | ✗ | ✓ | ✗ | ✗ |
| **OFFERS** |||||||
| View Offers | ✓ | ✓ | ✗ | ✗ | ✗ | ✓ |
| Create Offer | ✓ | ✓ | ✗ | ✗ | ✗ | ✓ |
| Activate/Deactivate | ✓ | ✓ | ✗ | ✗ | ✗ | ✓ |
| **USERS** |||||||
| View Users | ✓ | ✓ | ✗ | ✗ | ✗ | ✗ |
| Create User | ✓ | ✗ | ✗ | ✗ | ✗ | ✗ |
| Edit Permissions | ✓ | ✗ | ✗ | ✗ | ✗ | ✗ |
| Reset Password | ✓ | ✓ | ✗ | ✗ | ✗ | ✗ |
| **SETTINGS** |||||||
| View Settings | ✓ | ✓ | ✗ | ✗ | ✗ | ✗ |
| Edit Settings | ✓ | ✗ | ✗ | ✗ | ✗ | ✗ |
| Clear Cache | ✓ | ✗ | ✗ | ✗ | ✗ | ✗ |
| Toggle Maintenance | ✓ | ✗ | ✗ | ✗ | ✗ | ✗ |

---

## 3.B ORDER STATE MACHINE

### Order Status Transitions

```
                                    ┌────────────────┐
                                    │    PENDING     │
                                    │ (Initial State)│
                                    └───────┬────────┘
                                            │
                            ┌───────────────┼───────────────┐
                            │               │               │
                            ▼               ▼               ▼
                    ┌───────────┐   ┌───────────┐   ┌───────────┐
                    │ CONFIRMED │   │ CANCELLED │   │  (Expire) │
                    │           │   │           │   │           │
                    └─────┬─────┘   └───────────┘   └───────────┘
                          │
                          ▼
                    ┌───────────┐
                    │PROCESSING │
                    │           │
                    └─────┬─────┘
                          │
                          ▼
                    ┌───────────┐
                    │  SHIPPED  │───────────────┐
                    │           │               │
                    └─────┬─────┘               │
                          │                     │
                          ▼                     ▼
                ┌─────────────────┐     ┌───────────┐
                │PICKUP_GENERATED │     │  RETURNED │
                │                 │     │           │
                └────────┬────────┘     └───────────┘
                         │
                         ▼
                   ┌───────────┐
                   │ PICKED_UP │
                   │           │
                   └─────┬─────┘
                         │
                         ▼
                   ┌───────────┐
                   │ DELIVERED │
                   │           │
                   └───────────┘
```

### Transition Rules Table

| From Status | To Status | Allowed | Actor | Conditions |
|-------------|-----------|---------|-------|------------|
| pending | confirmed | YES | System | On payment success |
| pending | cancelled | YES | Customer/Admin | Before payment/shipping |
| confirmed | processing | YES | Admin/Operations | Manual trigger |
| confirmed | cancelled | YES | Admin | With reason |
| processing | shipped | YES | Admin/Operations | After shipment creation |
| processing | cancelled | YES | Admin | With approval |
| shipped | pickup_generated | YES | System | Auto on AWB generation |
| pickup_generated | picked_up | YES | System/Webhook | Shiprocket callback |
| picked_up | delivered | YES | System/Webhook | Shiprocket callback |
| shipped | cancelled | NO | - | Cannot cancel after ship |
| delivered | returned | YES | System | On return completion |
| cancelled | ANY | NO | - | Terminal state |
| delivered | cancelled | NO | - | Cannot cancel delivered |

### Blocked Transitions (CRITICAL)

| Transition | Reason |
|------------|--------|
| shipped → cancelled | Goods already dispatched |
| delivered → cancelled | Order completed |
| cancelled → ANY | Terminal state |
| returned → cancelled | Already returned |
| ANY → pending | Cannot regress to pending |

---

## 3.C REFUND & FINANCE LIFECYCLE

### 3.C.1 Refund Request Sources

| Source | Auto-Created | Approval Required |
|--------|--------------|-------------------|
| Order Cancellation (Paid) | YES | NO (auto) |
| Return Request | YES | YES |
| Exchange (Price Difference) | YES | YES |
| Admin Manual | NO | YES |

### 3.C.2 Refund Status Flow

```
pending → under_review → approved → processing → completed
                      ↘ rejected
```

### 3.C.3 Refund Processing Workflow

**Step 1: Refund Request Created**
- Source: Cancellation, Return, or Manual
- Status: `pending`
- Fields captured: `order_id`, `amount`, `reason`, `source`

**Step 2: Review (Admin)**
- Endpoint: `POST /api/admin/refunds/{id}/start-review`
- Status: `under_review`
- Finance team reviews request

**Step 3: Approve/Reject**
- Approve: `POST /api/admin/refunds/{id}/approve`
- Reject: `POST /api/admin/refunds/{id}/reject`

**Step 4: Process Refund**
- Endpoint: `POST /api/admin/refunds/{id}/process`
- Calls Razorpay refund API
- Records `razorpay_refund_id`

**Step 5: Completion**
- Status: `completed`
- Gateway response stored

### 3.C.4 Refund Amount Calculation

| Component | Calculation |
|-----------|-------------|
| Original Amount | Order item total |
| Restocking Fee | - (configurable %) |
| Shipping Deduction | - (if applicable) |
| **Final Refund** | = Original - Fees |

### 3.C.5 Partial Refund Rules

| Scenario | Allowed | Notes |
|----------|---------|-------|
| Partial item return | YES | Refund for returned items only |
| Partial cancellation | NO | **LIMITATION**: Full cancel only |
| Restocking fee deduction | YES | Configurable percentage |

### 3.C.6 Duplicate Refund Prevention

**Validations:**
1. Check existing refund for same order/source
2. Validate refund amount <= remaining refundable amount
3. Track `refund_initiated_at` to prevent race conditions

### 3.C.7 Credit Notes

**Endpoint:** `POST /api/admin/credit-notes`

**Use Cases:**
- Store credit instead of refund
- Goodwill gestures
- Partial compensation

**Credit Note Fields:**
```json
{
  "user_id": 123,
  "amount": 500,
  "reason": "Compensation for delayed delivery",
  "valid_until": "2026-12-31"
}
```

---

## 3.D INVENTORY BEHAVIOR

### 3.D.1 Stock Reservation Timeline

| Event | Action | Stock Change |
|-------|--------|--------------|
| Add to Cart | None | No change |
| Create Order (Pending) | Reserve | **ASSUMPTION**: No reservation |
| Payment Success | Deduct | `available_stock -= quantity` |
| Payment Failure | None | No change |
| Order Cancelled | Restore | `available_stock += quantity` |
| Return Received | Restore | `available_stock += quantity` |
| Exchange Received | Transfer | Stock moves to new product |

**ASSUMPTION:** Stock is deducted on payment success, not on order creation. Verify this behavior.

### 3.D.2 Stock Deduction Flow

```
┌─────────────┐     ┌─────────────┐     ┌─────────────┐
│  Order      │────▶│  Payment    │────▶│   Stock     │
│  Created    │     │  Success    │     │  Deducted   │
└─────────────┘     └─────────────┘     └─────────────┘
                           │
                           │ If Failed
                           ▼
                    ┌─────────────┐
                    │  No Stock   │
                    │   Change    │
                    └─────────────┘
```

### 3.D.3 Stock Restoration Flow

```
┌─────────────┐     ┌─────────────┐     ┌─────────────┐
│   Order     │────▶│  Cancellation│───▶│   Stock     │
│ Cancelled   │     │  Processed  │     │  Restored   │
└─────────────┘     └─────────────┘     └─────────────┘

┌─────────────┐     ┌─────────────┐     ┌─────────────┐
│  Return     │────▶│   Return    │────▶│   Stock     │
│ Approved    │     │  Received   │     │  Restored   │
└─────────────┘     └─────────────┘     └─────────────┘
```

### 3.D.4 Low Stock Behavior

**Threshold:** Configurable per product or global

**Admin Alerts:**
- Endpoint: `GET /api/admin/stock-alerts`
- Alert created when `available_stock <= reorder_point`

**Customer View:**
- `available_stock = 0`: "Out of Stock" displayed
- `available_stock <= low_threshold`: "Only X left" displayed

### 3.D.5 Multi-Warehouse Stock

**Endpoint:** `GET /api/admin/warehouse/{warehouseId}/stock`

**Stock Structure:**
```
Total Stock = Sum of all warehouse stock
Available Stock = Total - Reserved - In Transit
```

### 3.D.6 Stock Transfer Flow

**Endpoints:**
- Create: `POST /api/admin/transfers`
- Approve: `POST /api/admin/transfers/{id}/approve`
- Ship: `POST /api/admin/transfers/{id}/ship`
- Receive: `POST /api/admin/transfers/{id}/receive`

**Status Flow:**
```
pending → approved → shipped → received
                            ↘ cancelled
```

---

## 3.E ADMIN AUDIT & GOVERNANCE

### 3.E.1 Actions That MUST Be Logged

| Action | Log Level | Data Captured |
|--------|-----------|---------------|
| Order Status Change | HIGH | old_status, new_status, user_id, timestamp |
| Payment Processed | HIGH | amount, payment_id, gateway_response |
| Refund Initiated | HIGH | amount, refund_id, initiated_by |
| Refund Completed | HIGH | amount, refund_id, gateway_response |
| Stock Adjustment | HIGH | product_id, old_qty, new_qty, reason |
| User Status Change | HIGH | user_id, old_status, new_status |
| Permission Change | CRITICAL | user_id, permissions_added, permissions_removed |
| Settings Change | HIGH | setting_key, old_value, new_value |
| Product Price Change | MEDIUM | product_id, old_price, new_price |
| Offer Created/Modified | MEDIUM | offer_id, changes |
| Login Attempt | MEDIUM | user_id, ip_address, success/failure |
| Password Reset | HIGH | user_id, reset_by |

### 3.E.2 Audit Log Structure

**Model:** `AuditLog`
**Fields:**
- `id`
- `user_id` (who performed action)
- `action` (action type)
- `model_type` (affected model)
- `model_id` (affected record)
- `old_values` (JSON)
- `new_values` (JSON)
- `ip_address`
- `user_agent`
- `created_at`

### 3.E.3 QA Verification Points for Audit

| What to Verify | How to Verify |
|----------------|---------------|
| All order status changes logged | Check audit_logs after each status update |
| All refunds logged | Check audit_logs for refund entries |
| All admin actions logged | Check activity_logs table |
| Login attempts logged | Check login_attempts table |
| Permission changes logged | Check audit_logs for permission changes |

### 3.E.4 Immutable vs Editable Actions

| Action | Immutable | Notes |
|--------|-----------|-------|
| Order Creation | YES | Cannot delete orders |
| Payment Records | YES | Cannot edit payment records |
| Refund Records | YES | Cannot edit processed refunds |
| Audit Logs | YES | Cannot edit/delete logs |
| Activity Logs | YES | Cannot edit/delete logs |
| Order Notes | NO | Can be added, not edited |
| Product Data | NO | Can be edited with audit trail |
| User Data | NO | Can be edited with audit trail |

---

## 3.F ADMIN ORDER MANAGEMENT

### 3.F.1 Order List

**Endpoint:** `GET /api/admin/orders`

**Filters:**
- `status` - Order status
- `payment_status` - Payment status
- `date_from`, `date_to` - Date range
- `customer_id` - Specific customer
- `search` - Order code, customer name

### 3.F.2 Update Order Status

**Endpoint:** `PUT /api/admin/orders/{id}/status`

**Request:**
```json
{
  "status": "processing",
  "notes": "Started processing"
}
```

**Validation:** Follows state machine rules

### 3.F.3 Hold Order

**Endpoint:** `POST /api/admin/orders/{id}/hold`

**Request:**
```json
{
  "reason_code": "fraud_review",
  "reason": "Suspicious order pattern"
}
```

**Hold Reasons:**
- `fraud_review`
- `payment_verification`
- `stock_issue`
- `address_verification`
- `customer_request`

### 3.F.4 Release Order

**Endpoint:** `POST /api/admin/orders/{id}/release`

### 3.F.5 Split Shipment

**Endpoint:** `POST /api/admin/orders/{id}/split`

**Use Case:** Ship items from different warehouses

### 3.F.6 Override Order

**Endpoint:** `POST /api/admin/orders/{id}/override`

**Use Case:** Admin override for exceptional cases

**Request:**
```json
{
  "override_type": "price_adjustment",
  "reason": "Customer goodwill",
  "adjustments": {
    "discount": 500
  }
}
```

---

## 3.G ADMIN RETURNS MANAGEMENT

### 3.G.1 Returns Dashboard

**Endpoint:** `GET /api/admin/returns/dashboard`

**Returns:**
- Total pending returns
- Returns by status
- Average processing time
- Returns value

### 3.G.2 Return Workflow Actions

| Action | Endpoint | Precondition |
|--------|----------|--------------|
| Start Review | `POST /api/admin/returns/{id}/start-review` | Status = pending |
| Approve | `POST /api/admin/returns/{id}/approve` | Status = under_review |
| Reject | `POST /api/admin/returns/{id}/reject` | Status = under_review |
| Schedule Pickup | `POST /api/admin/returns/{id}/schedule-pickup` | Status = approved |
| Mark Picked Up | `POST /api/admin/returns/{id}/mark-picked-up` | Status = pickup_scheduled |
| Mark Received | `POST /api/admin/returns/{id}/mark-received` | Status = picked_up |
| Record Inspection | `POST /api/admin/returns/{id}/inspection` | Status = received |
| Initiate Refund | `POST /api/admin/returns/{id}/initiate-refund` | Status = inspected |
| Complete Refund | `POST /api/admin/returns/{id}/complete-refund` | Status = refund_initiated |

---

## 3.H ADMIN PARTNER INQUIRIES

### 3.H.1 Inquiry Dashboard

**Endpoint:** `GET /api/admin/partner-inquiries/dashboard`

### 3.H.2 Inquiry Workflow Actions

| Action | Endpoint | Precondition |
|--------|----------|--------------|
| Start Review | `POST /api/admin/partner-inquiries/{id}/start-review` | Status = pending |
| Provide Quote | `POST /api/admin/partner-inquiries/{id}/quote` | Status = under_review |
| Reject | `POST /api/admin/partner-inquiries/{id}/reject` | Status = pending/under_review |
| Update Status | `PUT /api/admin/partner-inquiries/{id}/status` | Various |
| Record Payment | `POST /api/admin/partner-inquiries/{id}/payment` | Status = accepted |
| Update Item Fulfillment | `PUT /api/admin/partner-inquiries/{id}/items/{itemId}/fulfillment` | In progress |
| Send Message | `POST /api/admin/partner-inquiries/{id}/message` | Any |
| Add Tracking | `POST /api/admin/partner-inquiries/{id}/tracking` | In progress/shipped |

---

## 3.I ADMIN FINANCE

### 3.I.1 Finance Dashboard

**Endpoint:** `GET /api/admin/finance/dashboard`

**Returns:**
- Total revenue
- Pending refunds
- Outstanding payments
- Settlement status

### 3.I.2 Refund Management

**List:** `GET /api/admin/refunds`
**Show:** `GET /api/admin/refunds/{id}`
**Review:** `POST /api/admin/refunds/{id}/start-review`
**Approve:** `POST /api/admin/refunds/{id}/approve`
**Reject:** `POST /api/admin/refunds/{id}/reject`
**Process:** `POST /api/admin/refunds/{id}/process`

### 3.I.3 Credit Notes

**List:** `GET /api/admin/credit-notes`
**Create:** `POST /api/admin/credit-notes`
**Show:** `GET /api/admin/credit-notes/{id}`
**Cancel:** `DELETE /api/admin/credit-notes/{id}`

### 3.I.4 Settlements

**List:** `GET /api/admin/settlements`
**Reconcile:** `POST /api/admin/settlements/reconcile`

---

## 3.J ADMIN USER MANAGEMENT

### 3.J.1 User Dashboard

**Endpoint:** `GET /api/admin/users/dashboard`

### 3.J.2 User Actions

| Action | Endpoint |
|--------|----------|
| List Users | `GET /api/admin/users` |
| View User | `GET /api/admin/users/{id}` |
| Update Status | `PUT /api/admin/users/{id}/status` |
| Lock Account | `POST /api/admin/users/{id}/lock` |
| Unlock Account | `POST /api/admin/users/{id}/unlock` |
| Reset Password | `POST /api/admin/users/{id}/reset-password` |
| View Sessions | `GET /api/admin/users/{id}/sessions` |
| Revoke Session | `DELETE /api/admin/users/{userId}/sessions/{sessionId}` |
| Revoke All Sessions | `DELETE /api/admin/users/{id}/sessions` |
| View Activity | `GET /api/admin/users/{id}/activity` |
| View Login Attempts | `GET /api/admin/users/{id}/login-attempts` |
| Assign Permissions | `PUT /api/admin/users/{id}/permissions` |

---

# 4. BUSINESS RULES & VALIDATIONS

## 4.1 Order Validations

| Rule ID | Rule Description | When Applied |
|---------|------------------|--------------|
| ORD-001 | Cart cannot be empty when creating order | Checkout |
| ORD-002 | All cart items must have sufficient stock | Checkout |
| ORD-003 | Billing address is required | Checkout |
| ORD-004 | Shipping address is required | Checkout |
| ORD-005 | Addresses must belong to authenticated customer | Checkout |
| ORD-006 | Order cannot be cancelled after shipping | Cancellation |
| ORD-007 | Only pending/confirmed/processing orders can be cancelled | Cancellation |
| ORD-008 | Cancel reason is required | Cancellation |
| ORD-009 | Order status transitions must follow state machine | Status Update |
| ORD-010 | Cannot regress order status (except cancellation) | Status Update |

## 4.2 Payment Validations

| Rule ID | Rule Description | When Applied |
|---------|------------------|--------------|
| PAY-001 | Order must exist and belong to customer | Payment Init |
| PAY-002 | Order must be in pending payment status | Payment Init |
| PAY-003 | Payment signature must be valid | Payment Verify |
| PAY-004 | Payment amount must match order total | Payment Verify |
| PAY-005 | Duplicate payment attempts blocked for same order | Payment Init |
| PAY-006 | Retry allowed only for failed/pending payments | Payment Retry |

## 4.3 Refund Validations

| Rule ID | Rule Description | When Applied |
|---------|------------------|--------------|
| REF-001 | Refund cannot exceed order value | Refund Create |
| REF-002 | Refund cannot exceed remaining refundable amount | Refund Create |
| REF-003 | Order must have successful payment | Refund Create |
| REF-004 | Duplicate refund for same source blocked | Refund Create |
| REF-005 | Processed refunds cannot be modified | Refund Update |
| REF-006 | Rejected refunds cannot be processed | Refund Process |

## 4.4 Return Validations

| Rule ID | Rule Description | When Applied |
|---------|------------------|--------------|
| RET-001 | Order must be delivered to initiate return | Return Create |
| RET-002 | Return must be within return window | Return Create |
| RET-003 | Items must be returnable per policy | Return Create |
| RET-004 | Quantity cannot exceed ordered quantity | Return Create |
| RET-005 | Already returned items cannot be returned again | Return Create |
| RET-006 | Return request can only be cancelled before pickup | Return Cancel |

## 4.5 Cart Validations

| Rule ID | Rule Description | When Applied |
|---------|------------------|--------------|
| CRT-001 | Product must exist and be active | Add to Cart |
| CRT-002 | Quantity must be positive integer | Add to Cart |
| CRT-003 | Quantity cannot exceed available stock | Add to Cart/Update |
| CRT-004 | Cannot modify other user's cart | Any Cart Operation |

## 4.6 User Validations

| Rule ID | Rule Description | When Applied |
|---------|------------------|--------------|
| USR-001 | Email must be unique | Registration/Update |
| USR-002 | Phone must be unique | Registration/Update |
| USR-003 | Disabled users cannot login | Login |
| USR-004 | Partner must be approved to login | Partner Login |
| USR-005 | Password must meet strength requirements | Password Set/Change |

## 4.7 Offer Validations

| Rule ID | Rule Description | When Applied |
|---------|------------------|--------------|
| OFR-001 | Offer must be active | Apply Offer |
| OFR-002 | Offer must be within validity period | Apply Offer |
| OFR-003 | Cart must meet minimum amount | Apply Offer |
| OFR-004 | User must not exceed per-user limit | Apply Offer |
| OFR-005 | Offer must not exceed global usage limit | Apply Offer |
| OFR-006 | Coupon code required for coupon offers | Apply Offer |
| OFR-007 | Cannot stack non-stackable offers | Apply Multiple |

## 4.8 Inventory Validations

| Rule ID | Rule Description | When Applied |
|---------|------------------|--------------|
| INV-001 | Stock cannot go negative | Stock Adjustment |
| INV-002 | Stock adjustment requires reason | Stock Adjustment |
| INV-003 | Transfer requires source and destination warehouse | Transfer Create |
| INV-004 | Transfer quantity cannot exceed source stock | Transfer Create |
| INV-005 | Only pending transfers can be cancelled | Transfer Cancel |

---

# 5. NEGATIVE & EDGE CASES

## 5.1 Network & System Failures

| Case ID | Scenario | Expected Behavior |
|---------|----------|-------------------|
| NET-001 | Payment gateway timeout during payment verification | Retry mechanism; check payment status via API |
| NET-002 | Shiprocket API failure during order push | Queue for retry; admin notification |
| NET-003 | Database connection lost during order creation | Transaction rollback; cart preserved |
| NET-004 | Session timeout during checkout | Redirect to login; cart preserved |
| NET-005 | Webhook delivery failure from Razorpay | Idempotent retry handling |
| NET-006 | Webhook delivery failure from Shiprocket | Status sync on next manual check |

## 5.2 Duplicate Submissions

| Case ID | Scenario | Expected Behavior |
|---------|----------|-------------------|
| DUP-001 | Double-click on "Place Order" | Idempotent; single order created |
| DUP-002 | Refresh page after payment success | Show success page; no duplicate payment |
| DUP-003 | Multiple payment verify calls | Process only first valid verification |
| DUP-004 | Duplicate cancellation request | Reject if already cancelled |
| DUP-005 | Duplicate return request for same item | Reject with existing request info |
| DUP-006 | Multiple OTP requests | Rate limit; use latest OTP only |

## 5.3 Concurrent Operations

| Case ID | Scenario | Expected Behavior |
|---------|----------|-------------------|
| CON-001 | Two users buying last item simultaneously | First payment wins; second gets stock error |
| CON-002 | Admin adjusting stock while checkout | Use latest stock at payment time |
| CON-003 | Customer cancelling while admin processing | Last action wins with conflict resolution |
| CON-004 | Multiple admins updating same order | Optimistic locking or last-write-wins |
| CON-005 | Coupon usage at limit while multiple apply | First apply wins; others rejected |

## 5.4 Payment Edge Cases

| Case ID | Scenario | Expected Behavior |
|---------|----------|-------------------|
| PMT-001 | Payment success but webhook failed | Manual reconciliation via Razorpay dashboard |
| PMT-002 | Payment failed but money debited | Mark pending; support intervention |
| PMT-003 | Partial payment (should not occur) | Reject; require full payment |
| PMT-004 | Refund initiated but gateway fails | Queue for retry; admin notification |
| PMT-005 | Multiple refund attempts for same order | Block duplicate refunds |
| PMT-006 | Payment retry after order expired | Block retry; create new order |

## 5.5 Stock Edge Cases

| Case ID | Scenario | Expected Behavior |
|---------|----------|-------------------|
| STK-001 | Stock becomes 0 while in cart | Show warning at checkout; block order |
| STK-002 | Stock reduced below cart quantity | Adjust cart quantity or show error |
| STK-003 | Return approved but product discontinued | Still accept return; mark for disposal |
| STK-004 | Negative stock (should never happen) | Alert admin; investigate |
| STK-005 | Stock adjustment during inventory count | Lock stock during count |

## 5.6 Security Edge Cases

| Case ID | Scenario | Expected Behavior |
|---------|----------|-------------------|
| SEC-001 | User attempts to access other user's order | 404 Not Found (not 403) |
| SEC-002 | User attempts to use other user's address | Validation error |
| SEC-003 | Tampered payment amount | Signature verification fails |
| SEC-004 | Brute force OTP attempts | Rate limiting; account lock |
| SEC-005 | Session hijacking attempt | Token invalidation |
| SEC-006 | SQL injection in search | Parameterized queries |
| SEC-007 | XSS in product review | Input sanitization |
| SEC-008 | CSRF in form submission | CSRF token validation |

## 5.7 Business Logic Edge Cases

| Case ID | Scenario | Expected Behavior |
|---------|----------|-------------------|
| BIZ-001 | Return window exact boundary | Allow return on last day |
| BIZ-002 | Offer expires during checkout | Apply if active at order creation |
| BIZ-003 | Price change during cart session | Use price at add-to-cart time |
| BIZ-004 | Product disabled after added to cart | Show error at checkout |
| BIZ-005 | Customer disabled after order placed | Complete existing order; block new |
| BIZ-006 | Partner approval revoked mid-order | Complete existing; block new |
| BIZ-007 | Exchange for higher-priced item | Require additional payment |
| BIZ-008 | Exchange for lower-priced item | Process refund for difference |
| BIZ-009 | Full refund with partial shipping | Policy-based handling |
| BIZ-010 | Loyalty points earned but order cancelled | Reverse points |

## 5.8 Data Integrity Edge Cases

| Case ID | Scenario | Expected Behavior |
|---------|----------|-------------------|
| DAT-001 | Order with deleted product | Show "Product no longer available" |
| DAT-002 | Address deleted after order | Preserve address snapshot in order |
| DAT-003 | Category deleted with products | Cascade handling or block |
| DAT-004 | User merged accounts | Order history consolidated |
| DAT-005 | Currency conversion precision | Round to 2 decimal places |

---

# 6. MANUAL QA TEST CASES

## 6.1 B2C Test Cases

### 6.1.1 Authentication Module

| TC ID | Module | Preconditions | Steps | Expected Result | Status |
|-------|--------|---------------|-------|-----------------|--------|
| B2C-AUTH-001 | OTP Login | Valid phone number | 1. Enter phone number 2. Click "Send OTP" 3. Enter OTP 4. Click "Verify" | User logged in, redirected to home | |
| B2C-AUTH-002 | OTP Login - Invalid OTP | OTP sent | 1. Enter wrong OTP 2. Click "Verify" | Error: "Invalid OTP" | |
| B2C-AUTH-003 | OTP Login - Expired OTP | OTP sent, wait 5+ mins | 1. Enter correct OTP after expiry 2. Click "Verify" | Error: "OTP expired" | |
| B2C-AUTH-004 | Password Login | User with password | 1. Enter email/phone 2. Enter password 3. Click "Login" | User logged in | |
| B2C-AUTH-005 | Password Login - Wrong Password | User with password | 1. Enter email 2. Enter wrong password 3. Click "Login" | Error: "Invalid credentials" | |
| B2C-AUTH-006 | Password Login - Disabled User | Disabled user account | 1. Attempt login | Error: "Account disabled" | |
| B2C-AUTH-007 | Password Reset | User with email | 1. Click "Forgot Password" 2. Enter email 3. Verify OTP 4. Set new password | Password reset, can login with new password | |
| B2C-AUTH-008 | Set Password First Time | OTP-only user | 1. Go to settings 2. Set password | Password set, can login with password | |
| B2C-AUTH-009 | Change Password | User with password | 1. Go to settings 2. Enter current password 3. Enter new password | Password changed | |
| B2C-AUTH-010 | Logout | Logged in user | 1. Click logout | Session ended, redirected to home | |

### 6.1.2 Cart Module

| TC ID | Module | Preconditions | Steps | Expected Result | Status |
|-------|--------|---------------|-------|-----------------|--------|
| B2C-CART-001 | Add to Cart | Product with stock | 1. View product 2. Click "Add to Cart" | Product added, cart count updated | |
| B2C-CART-002 | Add to Cart - Out of Stock | Product with 0 stock | 1. View product 2. Attempt add | Button disabled or error shown | |
| B2C-CART-003 | Add to Cart - Exceeds Stock | Product with 5 stock | 1. Add 10 quantity | Error: Cannot exceed available stock | |
| B2C-CART-004 | Update Cart Quantity | Item in cart | 1. View cart 2. Change quantity 3. Update | Quantity and total updated | |
| B2C-CART-005 | Remove from Cart | Item in cart | 1. View cart 2. Click remove | Item removed, total updated | |
| B2C-CART-006 | Clear Cart | Multiple items in cart | 1. View cart 2. Click clear all | Cart emptied | |
| B2C-CART-007 | Cart Persistence | Items in cart | 1. Logout 2. Login again | Cart items preserved | |
| B2C-CART-008 | Cart Sync - Guest to User | Guest with localStorage cart | 1. Login | localStorage cart merged with server cart | |

### 6.1.3 Checkout Module

| TC ID | Module | Preconditions | Steps | Expected Result | Status |
|-------|--------|---------------|-------|-----------------|--------|
| B2C-CHK-001 | Checkout Summary | Items in cart | 1. Go to checkout | Summary shows items, totals, addresses | |
| B2C-CHK-002 | Checkout - Empty Cart | Empty cart | 1. Go to checkout | Error: Cart is empty | |
| B2C-CHK-003 | Select Address | Saved addresses | 1. Select billing address 2. Select shipping address | Addresses selected | |
| B2C-CHK-004 | Add New Address | No addresses | 1. Click add address 2. Fill form 3. Save | Address added and selectable | |
| B2C-CHK-005 | Apply Coupon - Valid | Valid coupon exists | 1. Enter coupon code 2. Apply | Discount applied, total reduced | |
| B2C-CHK-006 | Apply Coupon - Invalid | Invalid coupon | 1. Enter wrong code 2. Apply | Error: Invalid coupon | |
| B2C-CHK-007 | Apply Coupon - Expired | Expired coupon | 1. Enter expired code 2. Apply | Error: Coupon expired | |
| B2C-CHK-008 | Apply Coupon - Min Amount Not Met | Coupon min ₹1000, cart ₹500 | 1. Enter coupon 2. Apply | Error: Minimum cart amount not met | |
| B2C-CHK-009 | Remove Coupon | Coupon applied | 1. Click remove coupon | Discount removed, original total shown | |
| B2C-CHK-010 | Check Serviceability | Valid pincode | 1. Enter pincode 2. Check | Serviceable status shown | |
| B2C-CHK-011 | Check Serviceability - Invalid | Non-serviceable pincode | 1. Enter pincode 2. Check | Not serviceable error | |
| B2C-CHK-012 | Place Order | All details provided | 1. Complete checkout form 2. Place order | Order created, redirect to payment | |
| B2C-CHK-013 | Place Order - Stock Changed | Stock reduced during checkout | 1. Place order | Error: Stock insufficient | |

### 6.1.4 Payment Module

| TC ID | Module | Preconditions | Steps | Expected Result | Status |
|-------|--------|---------------|-------|-----------------|--------|
| B2C-PAY-001 | Initiate Payment | Order created | 1. Initiate payment | Razorpay modal opens | |
| B2C-PAY-002 | Payment Success | Order created | 1. Complete Razorpay payment | Order confirmed, success page shown | |
| B2C-PAY-003 | Payment Failure | Order created | 1. Cancel/fail payment | Order remains pending, failure message | |
| B2C-PAY-004 | Retry Payment | Failed payment | 1. Go to order 2. Retry payment | New payment attempt initiated | |
| B2C-PAY-005 | Check Payment Status | Order with payment | 1. View order | Payment status displayed correctly | |
| B2C-PAY-006 | Double Payment Prevention | Payment in progress | 1. Try to pay again | Blocked: Payment already in progress | |

### 6.1.5 Order Management Module

| TC ID | Module | Preconditions | Steps | Expected Result | Status |
|-------|--------|---------------|-------|-----------------|--------|
| B2C-ORD-001 | View Order List | Orders exist | 1. Go to orders | Order list displayed | |
| B2C-ORD-002 | View Order Detail | Order exists | 1. Click order | Order details displayed | |
| B2C-ORD-003 | Filter Orders by Status | Multiple orders | 1. Select status filter | Filtered orders shown | |
| B2C-ORD-004 | Search Orders | Orders exist | 1. Enter order code 2. Search | Matching orders shown | |
| B2C-ORD-005 | View Order Tracking | Shipped order | 1. View order 2. View tracking | Tracking details shown | |
| B2C-ORD-006 | Cancel Order - Pending | Pending order | 1. Click cancel 2. Enter reason 3. Confirm | Order cancelled | |
| B2C-ORD-007 | Cancel Order - Shipped | Shipped order | 1. Attempt cancel | Error: Cannot cancel shipped order | |
| B2C-ORD-008 | View Invoice | Paid order | 1. Click invoice | Invoice displayed | |
| B2C-ORD-009 | Download Invoice | Paid order | 1. Click download | PDF downloaded | |

### 6.1.6 Returns Module

| TC ID | Module | Preconditions | Steps | Expected Result | Status |
|-------|--------|---------------|-------|-----------------|--------|
| B2C-RET-001 | Check Return Eligibility | Delivered order within window | 1. Check eligibility | Eligible for return | |
| B2C-RET-002 | Check Return Eligibility - Outside Window | Old delivered order | 1. Check eligibility | Not eligible: Outside return window | |
| B2C-RET-003 | Create Return Request | Eligible order | 1. Select items 2. Select reason 3. Submit | Return request created | |
| B2C-RET-004 | View Return Requests | Returns exist | 1. Go to returns | Returns list displayed | |
| B2C-RET-005 | View Return Detail | Return exists | 1. Click return | Return details displayed | |
| B2C-RET-006 | Cancel Return - Pending | Pending return | 1. Cancel return | Return cancelled | |
| B2C-RET-007 | Cancel Return - Pickup Done | Picked up return | 1. Attempt cancel | Error: Cannot cancel after pickup | |
| B2C-RET-008 | View Return Tracking | Return exists | 1. View tracking | Status history shown | |

### 6.1.7 Wishlist Module

| TC ID | Module | Preconditions | Steps | Expected Result | Status |
|-------|--------|---------------|-------|-----------------|--------|
| B2C-WL-001 | Add to Wishlist | Product exists | 1. Click wishlist icon | Product added to wishlist | |
| B2C-WL-002 | Remove from Wishlist | Product in wishlist | 1. Click wishlist icon again | Product removed | |
| B2C-WL-003 | View Wishlist | Items in wishlist | 1. Go to wishlist | Wishlist displayed | |
| B2C-WL-004 | Move to Cart | Item in wishlist | 1. Click move to cart | Item moved to cart | |
| B2C-WL-005 | Move to Cart - Out of Stock | Out of stock item | 1. Click move to cart | Error: Product out of stock | |

---

## 6.2 B2B Test Cases

### 6.2.1 Partner Authentication

| TC ID | Module | Preconditions | Steps | Expected Result | Status |
|-------|--------|---------------|-------|-----------------|--------|
| B2B-AUTH-001 | Partner Registration | None | 1. Fill registration form 2. Submit | Account created with pending status | |
| B2B-AUTH-002 | Partner Login - Approved | Approved partner | 1. Enter credentials 2. Login | Logged in successfully | |
| B2B-AUTH-003 | Partner Login - Pending | Pending partner | 1. Attempt login | Error: Account pending approval | |
| B2B-AUTH-004 | Partner Login - Rejected | Rejected partner | 1. Attempt login | Error: Account rejected | |

### 6.2.2 Partner Inquiries

| TC ID | Module | Preconditions | Steps | Expected Result | Status |
|-------|--------|---------------|-------|-----------------|--------|
| B2B-INQ-001 | Create Inquiry | Approved partner | 1. Fill inquiry form 2. Add items 3. Submit | Inquiry created | |
| B2B-INQ-002 | View Inquiries | Inquiries exist | 1. Go to inquiries | List displayed | |
| B2B-INQ-003 | View Inquiry Detail | Inquiry exists | 1. Click inquiry | Details displayed | |
| B2B-INQ-004 | Add Item to Inquiry | Pending inquiry | 1. Add item | Item added | |
| B2B-INQ-005 | Remove Item | Pending inquiry with items | 1. Remove item | Item removed | |
| B2B-INQ-006 | Accept Quote | Quoted inquiry | 1. Accept quote | Status changed to accepted | |
| B2B-INQ-007 | Reject Quote | Quoted inquiry | 1. Reject with reason | Status changed to rejected | |
| B2B-INQ-008 | Cancel Inquiry - Pending | Pending inquiry | 1. Cancel | Inquiry cancelled | |
| B2B-INQ-009 | Cancel Inquiry - In Progress | In progress inquiry | 1. Attempt cancel | Error: Cannot cancel | |
| B2B-INQ-010 | Send Message | Inquiry exists | 1. Send message | Message sent | |

---

## 6.3 Admin Test Cases

### 6.3.1 Order Management

| TC ID | Module | Preconditions | Steps | Expected Result | Status |
|-------|--------|---------------|-------|-----------------|--------|
| ADM-ORD-001 | View Order List | Orders exist | 1. Go to orders | Orders displayed | |
| ADM-ORD-002 | Filter by Status | Orders exist | 1. Select status filter | Filtered results | |
| ADM-ORD-003 | Search Order | Order exists | 1. Enter order code | Order found | |
| ADM-ORD-004 | Update Order Status | Confirmed order | 1. Change to processing 2. Save | Status updated | |
| ADM-ORD-005 | Invalid Status Transition | Shipped order | 1. Try to change to pending | Error: Invalid transition | |
| ADM-ORD-006 | Hold Order | Any order | 1. Hold order with reason | Order on hold | |
| ADM-ORD-007 | Release Order | Held order | 1. Release order | Order released | |
| ADM-ORD-008 | Add Order Note | Any order | 1. Add note | Note saved | |
| ADM-ORD-009 | Process Refund | Paid cancelled order | 1. Process refund | Refund initiated | |
| ADM-ORD-010 | Export Orders | Orders exist | 1. Export | CSV/Excel downloaded | |

### 6.3.2 Returns Management

| TC ID | Module | Preconditions | Steps | Expected Result | Status |
|-------|--------|---------------|-------|-----------------|--------|
| ADM-RET-001 | View Returns Dashboard | Returns exist | 1. Go to returns | Dashboard displayed | |
| ADM-RET-002 | Start Review | Pending return | 1. Start review | Status: Under Review | |
| ADM-RET-003 | Approve Return | Under review return | 1. Approve | Status: Approved | |
| ADM-RET-004 | Reject Return | Under review return | 1. Reject with reason | Status: Rejected | |
| ADM-RET-005 | Schedule Pickup | Approved return | 1. Schedule pickup | Status: Pickup Scheduled | |
| ADM-RET-006 | Mark Received | Picked up return | 1. Mark received | Status: Received | |
| ADM-RET-007 | Record Inspection | Received return | 1. Record inspection result | Status: Inspected | |
| ADM-RET-008 | Initiate Refund | Inspected return | 1. Initiate refund | Status: Refund Initiated | |
| ADM-RET-009 | Complete Refund | Refund initiated | 1. Complete refund | Status: Refund Completed | |

### 6.3.3 Finance Management

| TC ID | Module | Preconditions | Steps | Expected Result | Status |
|-------|--------|---------------|-------|-----------------|--------|
| ADM-FIN-001 | View Finance Dashboard | Data exists | 1. Go to finance | Dashboard displayed | |
| ADM-FIN-002 | View Refund Requests | Refunds exist | 1. Go to refunds | List displayed | |
| ADM-FIN-003 | Approve Refund | Pending refund | 1. Approve | Refund approved | |
| ADM-FIN-004 | Reject Refund | Pending refund | 1. Reject with reason | Refund rejected | |
| ADM-FIN-005 | Process Refund | Approved refund | 1. Process | Refund sent to gateway | |
| ADM-FIN-006 | Create Credit Note | None | 1. Create credit note | Credit note created | |
| ADM-FIN-007 | Cancel Credit Note | Active credit note | 1. Cancel | Credit note cancelled | |

### 6.3.4 Inventory Management

| TC ID | Module | Preconditions | Steps | Expected Result | Status |
|-------|--------|---------------|-------|-----------------|--------|
| ADM-INV-001 | View Inventory | Products exist | 1. Go to inventory | Inventory displayed | |
| ADM-INV-002 | View Low Stock | Low stock products | 1. View low stock | Low stock items shown | |
| ADM-INV-003 | Adjust Stock - Add | Product exists | 1. Adjust +10 units | Stock increased | |
| ADM-INV-004 | Adjust Stock - Remove | Product with stock | 1. Adjust -5 units | Stock decreased | |
| ADM-INV-005 | Adjust Stock - Negative | Limited stock | 1. Try to remove more than available | Error: Cannot go negative | |
| ADM-INV-006 | Create Stock Transfer | Multiple warehouses | 1. Create transfer | Transfer created | |
| ADM-INV-007 | Approve Transfer | Pending transfer | 1. Approve | Transfer approved | |
| ADM-INV-008 | Receive Transfer | Shipped transfer | 1. Mark received | Stock updated at destination | |

### 6.3.5 User Management

| TC ID | Module | Preconditions | Steps | Expected Result | Status |
|-------|--------|---------------|-------|-----------------|--------|
| ADM-USR-001 | View Users | Users exist | 1. Go to users | Users listed | |
| ADM-USR-002 | Disable User | Active user | 1. Disable user | User disabled, cannot login | |
| ADM-USR-003 | Enable User | Disabled user | 1. Enable user | User enabled, can login | |
| ADM-USR-004 | Reset Password | Any user | 1. Reset password | New password set, user notified | |
| ADM-USR-005 | Lock Account | Any user | 1. Lock account | Account locked | |
| ADM-USR-006 | Unlock Account | Locked user | 1. Unlock account | Account unlocked | |
| ADM-USR-007 | Revoke Session | User with session | 1. Revoke session | Session terminated | |
| ADM-USR-008 | View Activity Log | User with activity | 1. View activity | Activity log displayed | |

### 6.3.6 Partner Management

| TC ID | Module | Preconditions | Steps | Expected Result | Status |
|-------|--------|---------------|-------|-----------------|--------|
| ADM-PTR-001 | View Partners | Partners exist | 1. Go to partners | Partners listed | |
| ADM-PTR-002 | View Partner Detail | Partner exists | 1. Click partner | Details displayed | |
| ADM-PTR-003 | Approve Partner | Pending partner | 1. Approve | Partner approved, can login | |
| ADM-PTR-004 | Reject Partner | Pending partner | 1. Reject with reason | Partner rejected | |

### 6.3.7 Partner Inquiries (Admin)

| TC ID | Module | Preconditions | Steps | Expected Result | Status |
|-------|--------|---------------|-------|-----------------|--------|
| ADM-INQ-001 | View Inquiries Dashboard | Inquiries exist | 1. Go to inquiries | Dashboard displayed | |
| ADM-INQ-002 | Start Review | Pending inquiry | 1. Start review | Status: Under Review | |
| ADM-INQ-003 | Provide Quote | Under review inquiry | 1. Provide quote | Status: Quoted | |
| ADM-INQ-004 | Reject Inquiry | Any inquiry | 1. Reject with reason | Status: Rejected | |
| ADM-INQ-005 | Record Payment | Accepted inquiry | 1. Record payment | Payment recorded | |
| ADM-INQ-006 | Update Fulfillment | In progress inquiry | 1. Update item fulfillment | Fulfillment updated | |
| ADM-INQ-007 | Add Tracking | Shipped inquiry | 1. Add tracking | Tracking added | |

---

## 6.4 Security Test Cases

| TC ID | Module | Preconditions | Steps | Expected Result | Status |
|-------|--------|---------------|-------|-----------------|--------|
| SEC-001 | Access Other User's Order | Logged in as User A | 1. Try to access User B's order via API | 404 Not Found | |
| SEC-002 | Access Other User's Address | Logged in as User A | 1. Try to use User B's address | Validation error | |
| SEC-003 | Access Other User's Cart | Logged in as User A | 1. Try to modify User B's cart | 404 Not Found | |
| SEC-004 | Admin API Without Auth | Not logged in | 1. Call admin API | 401 Unauthorized | |
| SEC-005 | Customer API Without Auth | Not logged in | 1. Call protected customer API | 401 Unauthorized | |
| SEC-006 | Tampered Payment | Valid payment | 1. Modify payment amount in request | Signature verification fails | |
| SEC-007 | OTP Brute Force | Valid phone | 1. Try 10+ wrong OTPs | Rate limited/account locked | |
| SEC-008 | SQL Injection - Search | Any search field | 1. Enter SQL injection string | No SQL error, parameterized query | |
| SEC-009 | XSS - Review Input | Review form | 1. Enter script tag | Input sanitized | |
| SEC-010 | IDOR - Order Cancel | Order exists | 1. Try to cancel other user's order | 404 Not Found | |

---

## 6.5 Performance Edge Cases

| TC ID | Module | Preconditions | Steps | Expected Result | Status |
|-------|--------|---------------|-------|-----------------|--------|
| PERF-001 | Large Cart | None | 1. Add 50 items to cart 2. Checkout | System handles gracefully | |
| PERF-002 | Large Order History | User with 100+ orders | 1. View order list | Paginated, loads quickly | |
| PERF-003 | Concurrent Stock Check | Limited stock product | 1. Multiple users checkout simultaneously | Proper stock handling | |
| PERF-004 | Large Product Catalog | 1000+ products | 1. Browse products | Paginated, responsive | |

---

## ASSUMPTIONS DOCUMENTED

Throughout this document, the following assumptions have been made:

1. **Stock Deduction Timing**: Assumed stock is deducted on payment success, not on order creation. Verify actual implementation.

2. **Return Window**: Assumed configurable return window (e.g., 7-30 days). Verify actual default.

3. **Partial Cancellation**: Assumed not supported based on CancellationRequest model. Verify if partial order cancellation is intended.

4. **B2B Pricing**: Assumed same as B2C pricing. Verify if B2B-specific pricing exists.

5. **Stock Reservation**: Assumed no reservation on cart add. Verify actual implementation.

6. **OTP Expiry**: Assumed 5 minutes. Verify actual configuration.

7. **Rate Limiting**: Assumed rate limiting exists for OTP requests. Verify implementation.

---

**END OF QA DOCUMENTATION**
