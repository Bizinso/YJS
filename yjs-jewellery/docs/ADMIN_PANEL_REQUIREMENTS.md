# Admin Panel Requirements - Complete Specification

## Executive Summary

This document outlines all backend APIs and frontend components required for a production-ready admin panel.

**Total Requirements:**
- Backend APIs: 83 new endpoints
- Frontend Components: 78 new Vue components
- Organized into 10 pillars

---

## PILLAR 1: USER & ACCESS MANAGEMENT

### Existing (Backend)
| Feature | API | Controller |
|---------|-----|------------|
| Admin auth | Sanctum | LoginController |
| Role CRUD | Yes | RoleController |
| Permission CRUD | Yes | PermissionController |
| Customer listing | Yes | CustomerController |
| Partner listing | Yes | PartnerController |

### Backend Needed
| Feature | API Endpoints |
|---------|---------------|
| Fine-grained admin roles | `POST /admin/roles` with permissions matrix |
| Admin users CRUD | `GET/POST/PUT/DELETE /admin/users` |
| Permission assignment | `POST /admin/users/{id}/permissions` |
| Admin activity logs | `GET /admin/audit-logs` with filters |
| Session management | `GET /admin/sessions`, `DELETE /admin/sessions/{id}` |
| Login history | `GET /admin/users/{id}/login-history` |

### Frontend Needed
| Component | Description |
|-----------|-------------|
| AdminUsersList.vue | List all admin users with roles |
| AdminUserForm.vue | Create/edit admin user |
| RolePermissionMatrix.vue | Assign permissions to roles |
| AuditLogViewer.vue | Search/filter activity logs |
| SessionManager.vue | View/revoke active sessions |

---

## PILLAR 2: PRODUCT & CATALOG MANAGEMENT

### Existing (Backend)
| Feature | API | Controller |
|---------|-----|------------|
| Product CRUD | Yes | ProductController |
| Category CRUD | Yes | CategoryController |
| Subcategory CRUD | Yes | CategoryController |
| Attributes | Yes | AttributeController |
| Product types | Yes | ProductTypeController |
| Metal types | Yes | MetalTypeController |
| Purity | Yes | PurityController |
| Tags | Yes | TagController |

### Backend Needed
| Feature | API Endpoints |
|---------|---------------|
| Bulk product upload | `POST /admin/products/bulk-upload` (CSV/Excel) |
| Bulk price update | `POST /admin/products/bulk-price-update` |
| Product status workflow | `PUT /admin/products/{id}/status` (draft→active→archived) |
| Media reordering | `PUT /admin/products/{id}/media/reorder` |
| SEO metadata | `PUT /admin/products/{id}/seo` |
| Product clone | `POST /admin/products/{id}/clone` |
| Bulk delete | `POST /admin/products/bulk-delete` |

### Frontend Needed
| Component | Description |
|-----------|-------------|
| ProductList.vue | Enhanced with bulk actions, filters |
| ProductForm.vue | Multi-step form with all fields |
| BulkUploadModal.vue | CSV/Excel upload with preview |
| MediaGallery.vue | Drag-drop reorder, multiple images |
| SEOEditor.vue | Meta title, description, keywords |
| ProductStatusBadge.vue | Visual status indicator |
| BulkPriceEditor.vue | Update prices for multiple products |

---

## PILLAR 3: PRICING, PROMOTIONS & TAX

### Existing (Backend)
| Feature | API | Controller |
|---------|-----|------------|
| Basic offers | Yes | AdminOfferController |
| BOGO offers | Yes | AdminAdvancedOfferController |
| Combo offers | Yes | AdminAdvancedOfferController |
| Flash sales | Yes | AdminAdvancedOfferController |
| Tiered discounts | Yes | AdminAdvancedOfferController |
| Offer analytics | Yes | AdminAdvancedOfferController |
| Tax master | Yes | TaxController |

### Backend Needed
| Feature | API Endpoints |
|---------|---------------|
| Tax rules engine | `POST /admin/tax-rules` (region/category based) |
| Tax calculation preview | `POST /admin/tax/calculate` |
| GST/VAT reports | `GET /admin/reports/tax` |
| Promotion scheduler | `PUT /admin/offers/{id}/schedule` |
| Stackable rules config | `PUT /admin/offers/{id}/stacking` |
| Coupon generation | `POST /admin/coupons/generate-bulk` |
| Coupon usage report | `GET /admin/coupons/{id}/usage-report` |

### Frontend Needed
| Component | Description |
|-----------|-------------|
| TaxRulesManager.vue | Create region/category tax rules |
| TaxReportViewer.vue | GST/VAT summary reports |
| PromotionScheduler.vue | Calendar view for scheduled promos |
| CouponGenerator.vue | Bulk generate unique codes |
| OfferAnalytics.vue | Performance charts |
| StackingRulesEditor.vue | Configure offer combinations |

---

## PILLAR 4: ORDER MANAGEMENT (Critical)

### Existing (Backend)
| Feature | API | Controller |
|---------|-----|------------|
| Order listing | Yes | AdminOrderController |
| Order details | Yes | AdminOrderController |
| Status update | Yes | AdminOrderController |
| Order statistics | Yes | AdminOrderController |
| Add notes | Yes | AdminOrderController |
| Process refund | Yes | AdminOrderController |
| Invoice | Yes | InvoiceController |

### Backend Needed
| Feature | API Endpoints |
|---------|---------------|
| Order state machine | Enhanced `PUT /admin/orders/{id}/status` with validation |
| Partial fulfillment | `POST /admin/orders/{id}/fulfill-partial` |
| Split shipment | `POST /admin/orders/{id}/split` |
| Order hold/release | `POST /admin/orders/{id}/hold`, `/release` |
| Manual override | `POST /admin/orders/{id}/override` with reason |
| Order timeline | `GET /admin/orders/{id}/timeline` |
| Bulk status update | `POST /admin/orders/bulk-status` |
| Order export | `GET /admin/orders/export` (CSV/Excel) |
| SLA tracking | `GET /admin/orders/sla-breaches` |

### Frontend Needed
| Component | Description |
|-----------|-------------|
| OrderList.vue | Enhanced filters, bulk actions |
| OrderDetail.vue | Full order view with timeline |
| OrderStatusFlow.vue | Visual state machine |
| OrderTimeline.vue | Activity history |
| PartialFulfillment.vue | Select items to fulfill |
| SplitShipmentModal.vue | Create multiple shipments |
| OrderHoldModal.vue | Hold with reason |
| BulkOrderActions.vue | Bulk status/export |
| SLADashboard.vue | Orders at risk |

---

## PILLAR 5: PAYMENTS, REFUNDS & FINANCE (Critical)

### Existing (Backend)
| Feature | API | Controller |
|---------|-----|------------|
| Payment linkage | Yes | OrderPaymentController |
| Invoice generation | Yes | InvoiceController |
| Basic refund | Yes | AdminOrderController |

### Backend Needed
| Feature | API Endpoints |
|---------|---------------|
| Refund approval workflow | `POST /admin/refunds/{id}/approve`, `/reject` |
| Partial refund | `POST /admin/orders/{id}/refund-partial` |
| Refund listing | `GET /admin/refunds` with filters |
| Credit notes | `POST /admin/credit-notes` |
| Settlement report | `GET /admin/reports/settlements` |
| Payment reconciliation | `POST /admin/payments/reconcile` |
| Finance dashboard | `GET /admin/finance/dashboard` |
| Revenue by period | `GET /admin/finance/revenue` |
| Outstanding payments | `GET /admin/finance/outstanding` |
| Payment export | `GET /admin/payments/export` |

### Frontend Needed
| Component | Description |
|-----------|-------------|
| FinanceDashboard.vue | Revenue, collections, pending |
| RefundList.vue | All refunds with status |
| RefundApproval.vue | Approve/reject with notes |
| PartialRefundModal.vue | Select amount/items |
| CreditNoteList.vue | Generated credit notes |
| SettlementReport.vue | Daily/weekly settlements |
| ReconciliationTool.vue | Match payments with gateway |
| PaymentExport.vue | Export transactions |

---

## PILLAR 6: INVENTORY & SUPPLY CHAIN

### Existing (Backend)
| Feature | API | Controller |
|---------|-----|------------|
| Inventory listing | Yes | AdminInventoryController |
| Stock update | Yes | AdminInventoryController |
| Bulk update | Yes | AdminInventoryController |
| Low stock alert | Yes | AdminInventoryController |
| Out of stock | Yes | AdminInventoryController |
| Stock history | Yes | AdminInventoryController |
| Summary | Yes | AdminInventoryController |

### Backend Needed
| Feature | API Endpoints |
|---------|---------------|
| Stock movement logs | Enhanced `GET /admin/inventory/{id}/movements` |
| Reserved vs available | `GET /admin/inventory/{id}/availability` |
| Warehouse/location | `POST /admin/warehouses`, `GET /admin/inventory/by-warehouse` |
| Stock transfer | `POST /admin/inventory/transfer` |
| Stock reconciliation | `POST /admin/inventory/reconcile` |
| Reorder alerts | `GET /admin/inventory/reorder-needed` |
| Inventory valuation | `GET /admin/inventory/valuation` |

### Frontend Needed
| Component | Description |
|-----------|-------------|
| InventoryDashboard.vue | Overview with alerts |
| StockMovementLog.vue | All in/out movements |
| WarehouseManager.vue | Multi-location support |
| StockTransfer.vue | Move between warehouses |
| ReconciliationTool.vue | Physical vs system count |
| ReorderAlerts.vue | Items below threshold |
| InventoryValuation.vue | Total stock value |

---

## PILLAR 7: CUSTOMER SUPPORT & COMMUNICATION

### Existing (Backend)
| Feature | API | Controller |
|---------|-----|------------|
| Enquiry CRUD | Yes | EnquiryController |
| Enquiry logs | Yes | EnquiryLogController |
| Notifications | Yes | NotificationController |

### Backend Needed
| Feature | API Endpoints |
|---------|---------------|
| Support tickets | `GET/POST /admin/tickets` |
| Ticket assignment | `POST /admin/tickets/{id}/assign` |
| Ticket status | `PUT /admin/tickets/{id}/status` |
| Canned responses | `GET/POST /admin/canned-responses` |
| Return requests | `GET /admin/returns`, `PUT /admin/returns/{id}/status` |
| Exchange requests | `GET /admin/exchanges`, `PUT /admin/exchanges/{id}/status` |
| Communication log | `GET /admin/customers/{id}/communications` |
| Email templates | `GET/PUT /admin/email-templates` |
| Send notification | `POST /admin/notifications/send` |

### Frontend Needed
| Component | Description |
|-----------|-------------|
| TicketList.vue | Support tickets queue |
| TicketDetail.vue | Conversation thread |
| TicketAssignment.vue | Assign to agent |
| CannedResponses.vue | Manage quick replies |
| ReturnRequestList.vue | Pending returns |
| ReturnProcessing.vue | Approve/reject return |
| ExchangeProcessing.vue | Handle exchanges |
| EmailTemplateEditor.vue | Edit notification templates |
| CustomerCommunications.vue | Full history |

---

## PILLAR 8: REPORTING, AUDIT & GOVERNANCE (Critical)

### Existing (Backend)
| Feature | API | Controller |
|---------|-----|------------|
| Dashboard overview | Yes | AdminDashboardController |
| Sales report | Yes | AdminDashboardController |
| Revenue trends | Yes | AdminDashboardController |
| Top products | Yes | AdminDashboardController |
| Top customers | Yes | AdminDashboardController |
| Recent activities | Yes | AdminDashboardController |
| Export report | Yes | AdminDashboardController |

### Backend Needed
| Feature | API Endpoints |
|---------|---------------|
| B2C vs B2B report | `GET /admin/reports/b2c-vs-b2b` |
| Product performance | `GET /admin/reports/product-performance` |
| Customer LTV | `GET /admin/reports/customer-ltv` |
| Category performance | `GET /admin/reports/category-performance` |
| Conversion funnel | `GET /admin/reports/conversion-funnel` |
| Audit logs | `GET /admin/audit-logs` with filters |
| Admin action log | `GET /admin/audit-logs/admin-actions` |
| Data change log | `GET /admin/audit-logs/data-changes` |
| Scheduled reports | `POST /admin/reports/schedule` |
| Custom report builder | `POST /admin/reports/custom` |

### Frontend Needed
| Component | Description |
|-----------|-------------|
| ReportsDashboard.vue | All reports overview |
| SalesAnalytics.vue | Charts, trends, comparisons |
| B2CvsB2BReport.vue | Segment comparison |
| ProductPerformance.vue | Best/worst sellers |
| CustomerLTV.vue | Lifetime value analysis |
| ConversionFunnel.vue | Visitor → Order funnel |
| AuditLogViewer.vue | Searchable audit trail |
| ReportScheduler.vue | Auto-generate reports |
| ReportExporter.vue | PDF/Excel/CSV export |
| CustomReportBuilder.vue | Drag-drop report creation |

---

## PILLAR 9: RETURNS, EXCHANGES & CANCELLATIONS (New)

### Backend Needed (Full Implementation)
| Feature | API Endpoints |
|---------|---------------|
| Return policy config | `GET/PUT /admin/settings/return-policy` |
| Return requests list | `GET /admin/returns` |
| Return detail | `GET /admin/returns/{id}` |
| Approve return | `POST /admin/returns/{id}/approve` |
| Reject return | `POST /admin/returns/{id}/reject` |
| Process return | `POST /admin/returns/{id}/process` (refund/exchange) |
| Exchange requests | `GET /admin/exchanges` |
| Process exchange | `POST /admin/exchanges/{id}/process` |
| Cancellation requests | `GET /admin/cancellations` |
| Approve cancellation | `POST /admin/cancellations/{id}/approve` |
| Cancellation rules | `GET/PUT /admin/settings/cancellation-rules` |

### Frontend Needed
| Component | Description |
|-----------|-------------|
| ReturnPolicySettings.vue | Configure return window, rules |
| ReturnRequestList.vue | All return requests |
| ReturnDetail.vue | Request details, images |
| ReturnApproval.vue | Approve/reject flow |
| ReturnProcessing.vue | Trigger refund/exchange |
| ExchangeList.vue | Exchange requests |
| ExchangeProcessing.vue | Select replacement item |
| CancellationList.vue | Cancellation requests |
| CancellationRules.vue | Auto-cancellation config |

---

## PILLAR 10: SETTINGS & CONFIGURATION

### Backend Needed
| Feature | API Endpoints |
|---------|---------------|
| Store settings | `GET/PUT /admin/settings/store` |
| Payment gateway config | `GET/PUT /admin/settings/payments` |
| Shipping config | `GET/PUT /admin/settings/shipping` |
| Email config | `GET/PUT /admin/settings/email` |
| SMS/WhatsApp config | `GET/PUT /admin/settings/notifications` |
| Currency settings | `GET/PUT /admin/settings/currency` |
| GST/Tax settings | `GET/PUT /admin/settings/tax` |

### Frontend Needed
| Component | Description |
|-----------|-------------|
| SettingsDashboard.vue | All settings overview |
| StoreSettings.vue | Name, logo, contact |
| PaymentSettings.vue | Gateway credentials |
| ShippingSettings.vue | Carriers, rates |
| EmailSettings.vue | SMTP config |
| NotificationSettings.vue | SMS/WhatsApp setup |
| CurrencySettings.vue | Default currency, format |
| TaxSettings.vue | GST numbers, rates |

---

## Summary Count

| Pillar | Backend APIs | Frontend Components |
|--------|--------------|---------------------|
| 1. User & Access | 6 new | 5 new |
| 2. Product & Catalog | 7 new | 7 new |
| 3. Pricing & Tax | 7 new | 6 new |
| 4. Order Management | 9 new | 9 new |
| 5. Finance | 10 new | 8 new |
| 6. Inventory | 7 new | 7 new |
| 7. Customer Support | 9 new | 9 new |
| 8. Reports & Audit | 10 new | 10 new |
| 9. Returns/Exchanges | 11 new | 9 new |
| 10. Settings | 7 new | 8 new |
| **TOTAL** | **83 APIs** | **78 Components** |

---

## Implementation Priority

1. **Critical (Blocks Go-Live)**
   - Pillar 9: Returns, Exchanges, Cancellations
   - Pillar 4: Order Management enhancements
   - Pillar 5: Finance & Refunds

2. **Important (Blocks Scale)**
   - Pillar 1: User & Access Management
   - Pillar 7: Customer Support
   - Pillar 8: Reports & Audit

3. **Enhancement**
   - Pillar 2: Product bulk operations
   - Pillar 3: Tax rules
   - Pillar 6: Inventory enhancements
   - Pillar 10: Settings
