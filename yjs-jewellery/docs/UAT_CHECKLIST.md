# UAT Testing Checklist - YJS Jewellers Go-Live

## Purpose
User Acceptance Testing to verify the system meets business requirements from an end-user perspective.

---

## 1. CUSTOMER PURCHASE JOURNEY (B2C)

### 1.1 Browse & Add to Cart
| # | Scenario | Steps | Expected | Status |
|---|----------|-------|----------|--------|
| 1.1.1 | Browse products | Open product listing | Products displayed with prices | ☐ |
| 1.1.2 | View product details | Click on a product | Details, images, pricing shown | ☐ |
| 1.1.3 | Add to cart | Click "Add to Cart" | Item added, cart count updated | ☐ |
| 1.1.4 | View cart | Open cart | All items with correct prices | ☐ |
| 1.1.5 | Update quantity | Change quantity in cart | Price updates correctly | ☐ |
| 1.1.6 | Remove item | Remove item from cart | Item removed, total updated | ☐ |

### 1.2 Apply Offers
| # | Scenario | Steps | Expected | Status |
|---|----------|-------|----------|--------|
| 1.2.1 | View available offers | Go to checkout | Applicable offers shown | ☐ |
| 1.2.2 | Apply percentage discount | Select 10% off offer | Discount calculated correctly | ☐ |
| 1.2.3 | Apply coupon code | Enter "WELCOME10" | Discount applied | ☐ |
| 1.2.4 | Invalid coupon | Enter "INVALID123" | Error message shown | ☐ |
| 1.2.5 | Remove offer | Click remove offer | Original price restored | ☐ |

### 1.3 Checkout & Payment
| # | Scenario | Steps | Expected | Status |
|---|----------|-------|----------|--------|
| 1.3.1 | Check delivery | Enter pincode | Delivery availability shown | ☐ |
| 1.3.2 | Non-serviceable pincode | Enter remote pincode | "Not serviceable" message | ☐ |
| 1.3.3 | Add shipping address | Fill address form | Address saved | ☐ |
| 1.3.4 | Proceed to payment | Click "Pay Now" | Razorpay popup opens | ☐ |
| 1.3.5 | Pay with card | Enter card details | Payment successful | ☐ |
| 1.3.6 | Pay with UPI | Enter UPI ID | Payment successful | ☐ |
| 1.3.7 | Payment failure | Use failing card | Error shown, retry option | ☐ |
| 1.3.8 | Order confirmation | After successful payment | Confirmation page shown | ☐ |

### 1.4 Order Tracking
| # | Scenario | Steps | Expected | Status |
|---|----------|-------|----------|--------|
| 1.4.1 | View order history | Go to "My Orders" | List of orders shown | ☐ |
| 1.4.2 | View order details | Click on an order | Full details displayed | ☐ |
| 1.4.3 | Track shipment | Click "Track Order" | Tracking info shown | ☐ |
| 1.4.4 | Download invoice | Click "Download Invoice" | PDF downloaded | ☐ |

### 1.5 Cancellation
| # | Scenario | Steps | Expected | Status |
|---|----------|-------|----------|--------|
| 1.5.1 | Cancel before shipping | Click "Cancel Order" | Cancellation successful | ☐ |
| 1.5.2 | Cancel after shipping | Try to cancel | "Cannot cancel" message | ☐ |
| 1.5.3 | Refund status | Check cancelled order | Refund status shown | ☐ |

---

## 2. ADMIN/EMPLOYEE OPERATIONS

### 2.1 Order Management
| # | Scenario | Steps | Expected | Status |
|---|----------|-------|----------|--------|
| 2.1.1 | View all orders | Open Orders list | All orders displayed | ☐ |
| 2.1.2 | Filter by status | Filter "Confirmed" | Only confirmed orders | ☐ |
| 2.1.3 | Search order | Search by order code | Order found | ☐ |
| 2.1.4 | View order details | Click on order | Full details with payments | ☐ |

### 2.2 Shipping Management
| # | Scenario | Steps | Expected | Status |
|---|----------|-------|----------|--------|
| 2.2.1 | Push to Shiprocket | Click "Create Shipment" | Shiprocket ID generated | ☐ |
| 2.2.2 | Generate AWB | Click "Generate AWB" | AWB number assigned | ☐ |
| 2.2.3 | Schedule pickup | Click "Schedule Pickup" | Pickup date set | ☐ |
| 2.2.4 | Print label | Click "Print Label" | Shipping label opens | ☐ |
| 2.2.5 | Cancel shipment | Click "Cancel Shipment" | Shiprocket order cancelled | ☐ |

### 2.3 Refund Management
| # | Scenario | Steps | Expected | Status |
|---|----------|-------|----------|--------|
| 2.3.1 | Initiate full refund | Select order → Refund | Refund initiated | ☐ |
| 2.3.2 | Partial refund | Enter amount → Refund | Partial refund created | ☐ |
| 2.3.3 | View refund status | Open refund details | Status shown | ☐ |

### 2.4 Offer Management
| # | Scenario | Steps | Expected | Status |
|---|----------|-------|----------|--------|
| 2.4.1 | Create new offer | Fill offer form → Save | Offer created | ☐ |
| 2.4.2 | Edit offer | Modify offer → Save | Changes saved | ☐ |
| 2.4.3 | Deactivate offer | Change status → Inactive | Offer not shown to customers | ☐ |
| 2.4.4 | View offer usage | Open offer analytics | Usage count shown | ☐ |

---

## 3. B2B PARTNER JOURNEY

### 3.1 Partner Registration
| # | Scenario | Steps | Expected | Status |
|---|----------|-------|----------|--------|
| 3.1.1 | Register as partner | Fill registration form | Registration submitted | ☐ |
| 3.1.2 | Approval notification | Admin approves | Partner notified | ☐ |
| 3.1.3 | Login as partner | Use partner credentials | Partner dashboard shown | ☐ |

### 3.2 Partner Ordering
| # | Scenario | Steps | Expected | Status |
|---|----------|-------|----------|--------|
| 3.2.1 | View partner prices | Browse products | Partner pricing displayed | ☐ |
| 3.2.2 | Bulk order | Add multiple items | Bulk pricing applied | ☐ |
| 3.2.3 | Complete purchase | Checkout and pay | Order confirmed | ☐ |

---

## 4. NOTIFICATIONS

### 4.1 Email Notifications
| # | Scenario | Trigger | Expected | Status |
|---|----------|---------|----------|--------|
| 4.1.1 | Order confirmation | Payment success | Email received | ☐ |
| 4.1.2 | Shipping notification | AWB generated | Email with tracking | ☐ |
| 4.1.3 | Delivery confirmation | Order delivered | Email received | ☐ |
| 4.1.4 | Refund notification | Refund processed | Email received | ☐ |

### 4.2 SMS Notifications (if enabled)
| # | Scenario | Trigger | Expected | Status |
|---|----------|---------|----------|--------|
| 4.2.1 | Order confirmation | Payment success | SMS received | ☐ |
| 4.2.2 | Out for delivery | Status update | SMS received | ☐ |

---

## 5. EDGE CASES & ERROR HANDLING

### 5.1 Payment Scenarios
| # | Scenario | Steps | Expected | Status |
|---|----------|-------|----------|--------|
| 5.1.1 | Browser closed during payment | Close browser mid-payment | Can retry or order restored | ☐ |
| 5.1.2 | Network timeout | Slow network during pay | Proper error message | ☐ |
| 5.1.3 | Double payment attempt | Click pay twice quickly | Only one payment processed | ☐ |

### 5.2 Stock Scenarios
| # | Scenario | Steps | Expected | Status |
|---|----------|-------|----------|--------|
| 5.2.1 | Out of stock during checkout | Item goes OOS | Error message shown | ☐ |
| 5.2.2 | Last item purchase | Buy last available | Successful, stock = 0 | ☐ |

---

## 6. PERFORMANCE CHECKS

| # | Scenario | Acceptance Criteria | Status |
|---|----------|---------------------|--------|
| 6.1 | Page load time | < 3 seconds | ☐ |
| 6.2 | Payment popup load | < 2 seconds | ☐ |
| 6.3 | Order creation | < 5 seconds | ☐ |
| 6.4 | Tracking load | < 3 seconds | ☐ |

---

## 7. SECURITY CHECKS

| # | Check | Expected | Status |
|---|-------|----------|--------|
| 7.1 | View other user's order | Access denied | ☐ |
| 7.2 | Cancel other user's order | Access denied | ☐ |
| 7.3 | Admin-only endpoints | 403 for regular users | ☐ |
| 7.4 | Payment data in response | No sensitive card data | ☐ |

---

## Sign-off

### UAT Approval

| Stakeholder | Name | Date | Status | Signature |
|-------------|------|------|--------|-----------|
| Business Owner | | | ☐ Approved / ☐ Rejected | |
| Product Manager | | | ☐ Approved / ☐ Rejected | |
| Operations Lead | | | ☐ Approved / ☐ Rejected | |
| Finance Lead | | | ☐ Approved / ☐ Rejected | |

### Go-Live Decision

- [ ] All critical test cases passed
- [ ] All stakeholders approved
- [ ] Production credentials configured
- [ ] Monitoring & alerts set up
- [ ] Support team briefed

**Go-Live Date:** _______________

**Approved By:** _______________
