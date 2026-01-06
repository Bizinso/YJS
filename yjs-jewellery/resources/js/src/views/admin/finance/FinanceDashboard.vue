<template>
  <div class="finance-dashboard">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h4 class="mb-1">Finance Dashboard</h4>
        <p class="text-muted mb-0">Manage payments, refunds, settlements and financial reports</p>
      </div>
      <div class="d-flex gap-2">
        <b-button variant="outline-primary" @click="exportFinanceReport">
          <i class="bi bi-download me-1"></i> Export Report
        </b-button>
        <b-button variant="primary" @click="showReconciliationModal = true">
          <i class="bi bi-arrow-repeat me-1"></i> Reconcile
        </b-button>
      </div>
    </div>

    <!-- Date Range Filter -->
    <b-card class="mb-4">
      <div class="row align-items-end">
        <div class="col-md-3">
          <label class="form-label">Date Range</label>
          <b-form-select v-model="dateRange" :options="dateRangeOptions" @change="loadDashboard" />
        </div>
        <div class="col-md-3" v-if="dateRange === 'custom'">
          <label class="form-label">Start Date</label>
          <b-form-input type="date" v-model="customStartDate" />
        </div>
        <div class="col-md-3" v-if="dateRange === 'custom'">
          <label class="form-label">End Date</label>
          <b-form-input type="date" v-model="customEndDate" />
        </div>
        <div class="col-md-3">
          <b-button variant="primary" @click="loadDashboard">Apply Filter</b-button>
        </div>
      </div>
    </b-card>

    <!-- Stats Cards -->
    <div class="row mb-4">
      <div class="col-md-3">
        <b-card class="stats-card border-0 shadow-sm">
          <div class="d-flex justify-content-between">
            <div>
              <p class="text-muted mb-1">Total Revenue</p>
              <h3 class="mb-0">{{ formatCurrency(stats.totalRevenue) }}</h3>
              <small :class="stats.revenueGrowth >= 0 ? 'text-success' : 'text-danger'">
                {{ stats.revenueGrowth >= 0 ? '+' : '' }}{{ stats.revenueGrowth }}% vs last period
              </small>
            </div>
            <div class="stats-icon bg-success-subtle">
              <i class="bi bi-currency-rupee text-success"></i>
            </div>
          </div>
        </b-card>
      </div>
      <div class="col-md-3">
        <b-card class="stats-card border-0 shadow-sm">
          <div class="d-flex justify-content-between">
            <div>
              <p class="text-muted mb-1">Total Refunds</p>
              <h3 class="mb-0">{{ formatCurrency(stats.totalRefunds) }}</h3>
              <small class="text-muted">{{ stats.refundCount }} refunds</small>
            </div>
            <div class="stats-icon bg-danger-subtle">
              <i class="bi bi-arrow-return-left text-danger"></i>
            </div>
          </div>
        </b-card>
      </div>
      <div class="col-md-3">
        <b-card class="stats-card border-0 shadow-sm">
          <div class="d-flex justify-content-between">
            <div>
              <p class="text-muted mb-1">Pending Settlements</p>
              <h3 class="mb-0">{{ formatCurrency(stats.pendingSettlements) }}</h3>
              <small class="text-muted">{{ stats.pendingCount }} pending</small>
            </div>
            <div class="stats-icon bg-warning-subtle">
              <i class="bi bi-clock text-warning"></i>
            </div>
          </div>
        </b-card>
      </div>
      <div class="col-md-3">
        <b-card class="stats-card border-0 shadow-sm">
          <div class="d-flex justify-content-between">
            <div>
              <p class="text-muted mb-1">Net Revenue</p>
              <h3 class="mb-0">{{ formatCurrency(stats.netRevenue) }}</h3>
              <small class="text-muted">After refunds & fees</small>
            </div>
            <div class="stats-icon bg-primary-subtle">
              <i class="bi bi-graph-up text-primary"></i>
            </div>
          </div>
        </b-card>
      </div>
    </div>

    <!-- Tabs -->
    <b-card>
      <b-tabs v-model="activeTab">
        <!-- Payments Tab -->
        <b-tab title="Payments">
          <div class="mt-3">
            <!-- Filters -->
            <div class="row mb-3">
              <div class="col-md-3">
                <b-form-input v-model="paymentFilters.search" placeholder="Search payments..." />
              </div>
              <div class="col-md-2">
                <b-form-select v-model="paymentFilters.status" :options="paymentStatusOptions" />
              </div>
              <div class="col-md-2">
                <b-form-select v-model="paymentFilters.method" :options="paymentMethodOptions" />
              </div>
              <div class="col-md-2">
                <b-button variant="primary" @click="loadPayments">Filter</b-button>
              </div>
            </div>

            <!-- Payments Table -->
            <b-table
              :items="payments"
              :fields="paymentFields"
              :busy="loadingPayments"
              responsive
              hover
              show-empty
            >
              <template #cell(order_number)="data">
                <router-link :to="`/admin/orders/${data.item.order_id}`" class="text-primary">
                  {{ data.item.order_number }}
                </router-link>
              </template>
              <template #cell(amount)="data">
                {{ formatCurrency(data.value) }}
              </template>
              <template #cell(status)="data">
                <b-badge :variant="getPaymentStatusVariant(data.value)">
                  {{ data.value }}
                </b-badge>
              </template>
              <template #cell(created_at)="data">
                {{ formatDate(data.value) }}
              </template>
              <template #cell(actions)="data">
                <b-dropdown variant="link" no-caret>
                  <template #button-content>
                    <i class="bi bi-three-dots-vertical"></i>
                  </template>
                  <b-dropdown-item @click="viewPaymentDetails(data.item)">
                    <i class="bi bi-eye me-2"></i> View Details
                  </b-dropdown-item>
                  <b-dropdown-item
                    v-if="data.item.status === 'completed'"
                    @click="initiateRefund(data.item)"
                  >
                    <i class="bi bi-arrow-return-left me-2"></i> Initiate Refund
                  </b-dropdown-item>
                  <b-dropdown-item @click="downloadReceipt(data.item)">
                    <i class="bi bi-download me-2"></i> Download Receipt
                  </b-dropdown-item>
                </b-dropdown>
              </template>
            </b-table>

            <b-pagination
              v-model="paymentsPagination.currentPage"
              :total-rows="paymentsPagination.total"
              :per-page="paymentsPagination.perPage"
              @change="loadPayments"
            />
          </div>
        </b-tab>

        <!-- Refunds Tab -->
        <b-tab title="Refunds">
          <div class="mt-3">
            <!-- Filters -->
            <div class="row mb-3">
              <div class="col-md-3">
                <b-form-input v-model="refundFilters.search" placeholder="Search refunds..." />
              </div>
              <div class="col-md-2">
                <b-form-select v-model="refundFilters.status" :options="refundStatusOptions" />
              </div>
              <div class="col-md-2">
                <b-button variant="primary" @click="loadRefunds">Filter</b-button>
              </div>
            </div>

            <!-- Refunds Table -->
            <b-table
              :items="refunds"
              :fields="refundFields"
              :busy="loadingRefunds"
              responsive
              hover
              show-empty
            >
              <template #cell(order_number)="data">
                <router-link :to="`/admin/orders/${data.item.order_id}`" class="text-primary">
                  {{ data.item.order_number }}
                </router-link>
              </template>
              <template #cell(amount)="data">
                {{ formatCurrency(data.value) }}
              </template>
              <template #cell(status)="data">
                <b-badge :variant="getRefundStatusVariant(data.value)">
                  {{ data.value }}
                </b-badge>
              </template>
              <template #cell(created_at)="data">
                {{ formatDate(data.value) }}
              </template>
              <template #cell(actions)="data">
                <b-dropdown variant="link" no-caret>
                  <template #button-content>
                    <i class="bi bi-three-dots-vertical"></i>
                  </template>
                  <b-dropdown-item @click="viewRefundDetails(data.item)">
                    <i class="bi bi-eye me-2"></i> View Details
                  </b-dropdown-item>
                  <b-dropdown-item
                    v-if="data.item.status === 'pending'"
                    @click="processRefund(data.item)"
                  >
                    <i class="bi bi-check me-2"></i> Process Refund
                  </b-dropdown-item>
                  <b-dropdown-item
                    v-if="data.item.status === 'pending'"
                    @click="rejectRefund(data.item)"
                  >
                    <i class="bi bi-x me-2"></i> Reject Refund
                  </b-dropdown-item>
                </b-dropdown>
              </template>
            </b-table>

            <b-pagination
              v-model="refundsPagination.currentPage"
              :total-rows="refundsPagination.total"
              :per-page="refundsPagination.perPage"
              @change="loadRefunds"
            />
          </div>
        </b-tab>

        <!-- Settlements Tab -->
        <b-tab title="Settlements">
          <div class="mt-3">
            <!-- Settlement Stats -->
            <div class="row mb-4">
              <div class="col-md-4">
                <div class="p-3 bg-light rounded">
                  <p class="text-muted mb-1">Total Settled</p>
                  <h4 class="mb-0">{{ formatCurrency(settlementStats.totalSettled) }}</h4>
                </div>
              </div>
              <div class="col-md-4">
                <div class="p-3 bg-light rounded">
                  <p class="text-muted mb-1">Pending Settlement</p>
                  <h4 class="mb-0">{{ formatCurrency(settlementStats.pendingSettlement) }}</h4>
                </div>
              </div>
              <div class="col-md-4">
                <div class="p-3 bg-light rounded">
                  <p class="text-muted mb-1">Next Settlement Date</p>
                  <h4 class="mb-0">{{ formatDate(settlementStats.nextSettlementDate) }}</h4>
                </div>
              </div>
            </div>

            <!-- Settlements Table -->
            <b-table
              :items="settlements"
              :fields="settlementFields"
              :busy="loadingSettlements"
              responsive
              hover
              show-empty
            >
              <template #cell(amount)="data">
                {{ formatCurrency(data.value) }}
              </template>
              <template #cell(status)="data">
                <b-badge :variant="getSettlementStatusVariant(data.value)">
                  {{ data.value }}
                </b-badge>
              </template>
              <template #cell(settled_at)="data">
                {{ data.value ? formatDate(data.value) : '-' }}
              </template>
              <template #cell(actions)="data">
                <b-button size="sm" variant="outline-primary" @click="viewSettlementDetails(data.item)">
                  View Details
                </b-button>
              </template>
            </b-table>
          </div>
        </b-tab>

        <!-- Invoices Tab -->
        <b-tab title="Invoices">
          <div class="mt-3">
            <!-- Filters -->
            <div class="row mb-3">
              <div class="col-md-3">
                <b-form-input v-model="invoiceFilters.search" placeholder="Search invoices..." />
              </div>
              <div class="col-md-2">
                <b-form-select v-model="invoiceFilters.status" :options="invoiceStatusOptions" />
              </div>
              <div class="col-md-2">
                <b-button variant="primary" @click="loadInvoices">Filter</b-button>
              </div>
              <div class="col-md-5 text-end">
                <b-button variant="success" @click="generateBulkInvoices">
                  <i class="bi bi-file-earmark-plus me-1"></i> Generate Pending Invoices
                </b-button>
              </div>
            </div>

            <!-- Invoices Table -->
            <b-table
              :items="invoices"
              :fields="invoiceFields"
              :busy="loadingInvoices"
              responsive
              hover
              show-empty
            >
              <template #cell(invoice_number)="data">
                <span class="text-primary">{{ data.value }}</span>
              </template>
              <template #cell(amount)="data">
                {{ formatCurrency(data.value) }}
              </template>
              <template #cell(status)="data">
                <b-badge :variant="getInvoiceStatusVariant(data.value)">
                  {{ data.value }}
                </b-badge>
              </template>
              <template #cell(created_at)="data">
                {{ formatDate(data.value) }}
              </template>
              <template #cell(actions)="data">
                <div class="btn-group">
                  <b-button size="sm" variant="outline-primary" @click="viewInvoice(data.item)">
                    <i class="bi bi-eye"></i>
                  </b-button>
                  <b-button size="sm" variant="outline-success" @click="downloadInvoice(data.item)">
                    <i class="bi bi-download"></i>
                  </b-button>
                  <b-button size="sm" variant="outline-info" @click="emailInvoice(data.item)">
                    <i class="bi bi-envelope"></i>
                  </b-button>
                </div>
              </template>
            </b-table>

            <b-pagination
              v-model="invoicesPagination.currentPage"
              :total-rows="invoicesPagination.total"
              :per-page="invoicesPagination.perPage"
              @change="loadInvoices"
            />
          </div>
        </b-tab>

        <!-- Tax Reports Tab -->
        <b-tab title="Tax Reports">
          <div class="mt-3">
            <!-- Tax Summary -->
            <div class="row mb-4">
              <div class="col-md-3">
                <div class="p-3 bg-light rounded">
                  <p class="text-muted mb-1">Total Tax Collected</p>
                  <h4 class="mb-0">{{ formatCurrency(taxStats.totalTax) }}</h4>
                </div>
              </div>
              <div class="col-md-3">
                <div class="p-3 bg-light rounded">
                  <p class="text-muted mb-1">CGST</p>
                  <h4 class="mb-0">{{ formatCurrency(taxStats.cgst) }}</h4>
                </div>
              </div>
              <div class="col-md-3">
                <div class="p-3 bg-light rounded">
                  <p class="text-muted mb-1">SGST</p>
                  <h4 class="mb-0">{{ formatCurrency(taxStats.sgst) }}</h4>
                </div>
              </div>
              <div class="col-md-3">
                <div class="p-3 bg-light rounded">
                  <p class="text-muted mb-1">IGST</p>
                  <h4 class="mb-0">{{ formatCurrency(taxStats.igst) }}</h4>
                </div>
              </div>
            </div>

            <!-- Export Options -->
            <div class="d-flex gap-2 mb-4">
              <b-button variant="outline-primary" @click="exportGSTR1">
                <i class="bi bi-file-earmark-spreadsheet me-1"></i> Export GSTR-1
              </b-button>
              <b-button variant="outline-primary" @click="exportGSTR3B">
                <i class="bi bi-file-earmark-spreadsheet me-1"></i> Export GSTR-3B
              </b-button>
              <b-button variant="outline-primary" @click="exportTaxReport">
                <i class="bi bi-file-earmark-pdf me-1"></i> Tax Report PDF
              </b-button>
            </div>

            <!-- Tax Breakdown Table -->
            <b-table
              :items="taxBreakdown"
              :fields="taxFields"
              responsive
              hover
            >
              <template #cell(taxable_amount)="data">
                {{ formatCurrency(data.value) }}
              </template>
              <template #cell(cgst)="data">
                {{ formatCurrency(data.value) }}
              </template>
              <template #cell(sgst)="data">
                {{ formatCurrency(data.value) }}
              </template>
              <template #cell(igst)="data">
                {{ formatCurrency(data.value) }}
              </template>
              <template #cell(total_tax)="data">
                {{ formatCurrency(data.value) }}
              </template>
            </b-table>
          </div>
        </b-tab>
      </b-tabs>
    </b-card>

    <!-- Refund Modal -->
    <b-modal v-model="showRefundModal" title="Initiate Refund" @ok="submitRefund">
      <b-form v-if="selectedPayment">
        <b-form-group label="Order" class="mb-3">
          <p class="mb-0">{{ selectedPayment.order_number }}</p>
        </b-form-group>
        <b-form-group label="Payment Amount" class="mb-3">
          <p class="mb-0">{{ formatCurrency(selectedPayment.amount) }}</p>
        </b-form-group>
        <b-form-group label="Refund Amount" class="mb-3">
          <b-form-input
            type="number"
            v-model="refundForm.amount"
            :max="selectedPayment.amount"
            required
          />
        </b-form-group>
        <b-form-group label="Refund Type" class="mb-3">
          <b-form-select v-model="refundForm.type" :options="refundTypeOptions" />
        </b-form-group>
        <b-form-group label="Reason" class="mb-3">
          <b-form-textarea v-model="refundForm.reason" rows="3" required />
        </b-form-group>
      </b-form>
    </b-modal>

    <!-- Reconciliation Modal -->
    <b-modal v-model="showReconciliationModal" title="Payment Reconciliation" size="lg" @ok="runReconciliation">
      <b-form>
        <b-form-group label="Date Range" class="mb-3">
          <div class="row">
            <div class="col-6">
              <b-form-input type="date" v-model="reconciliationForm.startDate" />
            </div>
            <div class="col-6">
              <b-form-input type="date" v-model="reconciliationForm.endDate" />
            </div>
          </div>
        </b-form-group>
        <b-form-group label="Payment Gateway" class="mb-3">
          <b-form-select v-model="reconciliationForm.gateway" :options="gatewayOptions" />
        </b-form-group>
        <b-form-checkbox v-model="reconciliationForm.autoFix" class="mb-3">
          Auto-fix discrepancies (if possible)
        </b-form-checkbox>
      </b-form>
    </b-modal>

    <!-- Payment Details Modal -->
    <b-modal v-model="showPaymentDetailsModal" title="Payment Details" size="lg" hide-footer>
      <div v-if="selectedPaymentDetails">
        <div class="row mb-3">
          <div class="col-6">
            <p class="text-muted mb-1">Transaction ID</p>
            <p class="mb-0 fw-bold">{{ selectedPaymentDetails.transaction_id }}</p>
          </div>
          <div class="col-6">
            <p class="text-muted mb-1">Gateway Reference</p>
            <p class="mb-0 fw-bold">{{ selectedPaymentDetails.gateway_reference || '-' }}</p>
          </div>
        </div>
        <div class="row mb-3">
          <div class="col-6">
            <p class="text-muted mb-1">Order Number</p>
            <p class="mb-0 fw-bold">{{ selectedPaymentDetails.order_number }}</p>
          </div>
          <div class="col-6">
            <p class="text-muted mb-1">Customer</p>
            <p class="mb-0 fw-bold">{{ selectedPaymentDetails.customer_name }}</p>
          </div>
        </div>
        <div class="row mb-3">
          <div class="col-6">
            <p class="text-muted mb-1">Amount</p>
            <p class="mb-0 fw-bold">{{ formatCurrency(selectedPaymentDetails.amount) }}</p>
          </div>
          <div class="col-6">
            <p class="text-muted mb-1">Payment Method</p>
            <p class="mb-0 fw-bold">{{ selectedPaymentDetails.method }}</p>
          </div>
        </div>
        <div class="row mb-3">
          <div class="col-6">
            <p class="text-muted mb-1">Status</p>
            <b-badge :variant="getPaymentStatusVariant(selectedPaymentDetails.status)">
              {{ selectedPaymentDetails.status }}
            </b-badge>
          </div>
          <div class="col-6">
            <p class="text-muted mb-1">Date</p>
            <p class="mb-0">{{ formatDateTime(selectedPaymentDetails.created_at) }}</p>
          </div>
        </div>
        <hr />
        <h6>Payment Timeline</h6>
        <div class="timeline">
          <div v-for="event in selectedPaymentDetails.timeline" :key="event.id" class="timeline-item">
            <div class="timeline-marker"></div>
            <div class="timeline-content">
              <p class="mb-0">{{ event.description }}</p>
              <small class="text-muted">{{ formatDateTime(event.created_at) }}</small>
            </div>
          </div>
        </div>
      </div>
    </b-modal>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, computed } from 'vue'
import axios from 'axios'

// State
const activeTab = ref(0)
const dateRange = ref('this_month')
const customStartDate = ref('')
const customEndDate = ref('')
const loading = ref(false)

// Stats
const stats = reactive({
  totalRevenue: 0,
  revenueGrowth: 0,
  totalRefunds: 0,
  refundCount: 0,
  pendingSettlements: 0,
  pendingCount: 0,
  netRevenue: 0
})

// Payments
const payments = ref([])
const loadingPayments = ref(false)
const paymentFilters = reactive({
  search: '',
  status: '',
  method: ''
})
const paymentsPagination = reactive({
  currentPage: 1,
  total: 0,
  perPage: 20
})

// Refunds
const refunds = ref([])
const loadingRefunds = ref(false)
const refundFilters = reactive({
  search: '',
  status: ''
})
const refundsPagination = reactive({
  currentPage: 1,
  total: 0,
  perPage: 20
})

// Settlements
const settlements = ref([])
const loadingSettlements = ref(false)
const settlementStats = reactive({
  totalSettled: 0,
  pendingSettlement: 0,
  nextSettlementDate: null
})

// Invoices
const invoices = ref([])
const loadingInvoices = ref(false)
const invoiceFilters = reactive({
  search: '',
  status: ''
})
const invoicesPagination = reactive({
  currentPage: 1,
  total: 0,
  perPage: 20
})

// Tax
const taxStats = reactive({
  totalTax: 0,
  cgst: 0,
  sgst: 0,
  igst: 0
})
const taxBreakdown = ref([])

// Modals
const showRefundModal = ref(false)
const showReconciliationModal = ref(false)
const showPaymentDetailsModal = ref(false)
const selectedPayment = ref(null)
const selectedPaymentDetails = ref(null)

// Forms
const refundForm = reactive({
  amount: 0,
  type: 'full',
  reason: ''
})
const reconciliationForm = reactive({
  startDate: '',
  endDate: '',
  gateway: 'razorpay',
  autoFix: false
})

// Options
const dateRangeOptions = [
  { value: 'today', text: 'Today' },
  { value: 'yesterday', text: 'Yesterday' },
  { value: 'this_week', text: 'This Week' },
  { value: 'last_week', text: 'Last Week' },
  { value: 'this_month', text: 'This Month' },
  { value: 'last_month', text: 'Last Month' },
  { value: 'this_quarter', text: 'This Quarter' },
  { value: 'this_year', text: 'This Year' },
  { value: 'custom', text: 'Custom Range' }
]

const paymentStatusOptions = [
  { value: '', text: 'All Statuses' },
  { value: 'pending', text: 'Pending' },
  { value: 'completed', text: 'Completed' },
  { value: 'failed', text: 'Failed' },
  { value: 'refunded', text: 'Refunded' }
]

const paymentMethodOptions = [
  { value: '', text: 'All Methods' },
  { value: 'card', text: 'Card' },
  { value: 'upi', text: 'UPI' },
  { value: 'netbanking', text: 'Net Banking' },
  { value: 'wallet', text: 'Wallet' },
  { value: 'cod', text: 'Cash on Delivery' }
]

const refundStatusOptions = [
  { value: '', text: 'All Statuses' },
  { value: 'pending', text: 'Pending' },
  { value: 'processing', text: 'Processing' },
  { value: 'completed', text: 'Completed' },
  { value: 'failed', text: 'Failed' },
  { value: 'rejected', text: 'Rejected' }
]

const refundTypeOptions = [
  { value: 'full', text: 'Full Refund' },
  { value: 'partial', text: 'Partial Refund' }
]

const invoiceStatusOptions = [
  { value: '', text: 'All Statuses' },
  { value: 'draft', text: 'Draft' },
  { value: 'generated', text: 'Generated' },
  { value: 'sent', text: 'Sent' },
  { value: 'paid', text: 'Paid' }
]

const gatewayOptions = [
  { value: 'razorpay', text: 'Razorpay' },
  { value: 'paytm', text: 'Paytm' },
  { value: 'phonepe', text: 'PhonePe' }
]

// Table Fields
const paymentFields = [
  { key: 'transaction_id', label: 'Transaction ID' },
  { key: 'order_number', label: 'Order' },
  { key: 'customer_name', label: 'Customer' },
  { key: 'amount', label: 'Amount' },
  { key: 'method', label: 'Method' },
  { key: 'status', label: 'Status' },
  { key: 'created_at', label: 'Date' },
  { key: 'actions', label: 'Actions' }
]

const refundFields = [
  { key: 'refund_id', label: 'Refund ID' },
  { key: 'order_number', label: 'Order' },
  { key: 'customer_name', label: 'Customer' },
  { key: 'amount', label: 'Amount' },
  { key: 'reason', label: 'Reason' },
  { key: 'status', label: 'Status' },
  { key: 'created_at', label: 'Date' },
  { key: 'actions', label: 'Actions' }
]

const settlementFields = [
  { key: 'settlement_id', label: 'Settlement ID' },
  { key: 'amount', label: 'Amount' },
  { key: 'transaction_count', label: 'Transactions' },
  { key: 'status', label: 'Status' },
  { key: 'settled_at', label: 'Settled At' },
  { key: 'actions', label: 'Actions' }
]

const invoiceFields = [
  { key: 'invoice_number', label: 'Invoice #' },
  { key: 'order_number', label: 'Order' },
  { key: 'customer_name', label: 'Customer' },
  { key: 'amount', label: 'Amount' },
  { key: 'status', label: 'Status' },
  { key: 'created_at', label: 'Date' },
  { key: 'actions', label: 'Actions' }
]

const taxFields = [
  { key: 'tax_rate', label: 'Tax Rate' },
  { key: 'order_count', label: 'Orders' },
  { key: 'taxable_amount', label: 'Taxable Amount' },
  { key: 'cgst', label: 'CGST' },
  { key: 'sgst', label: 'SGST' },
  { key: 'igst', label: 'IGST' },
  { key: 'total_tax', label: 'Total Tax' }
]

// Methods
const formatCurrency = (amount) => {
  return new Intl.NumberFormat('en-IN', {
    style: 'currency',
    currency: 'INR'
  }).format(amount || 0)
}

const formatDate = (date) => {
  if (!date) return '-'
  return new Date(date).toLocaleDateString('en-IN')
}

const formatDateTime = (date) => {
  if (!date) return '-'
  return new Date(date).toLocaleString('en-IN')
}

const getPaymentStatusVariant = (status) => {
  const variants = {
    pending: 'warning',
    completed: 'success',
    failed: 'danger',
    refunded: 'info'
  }
  return variants[status] || 'secondary'
}

const getRefundStatusVariant = (status) => {
  const variants = {
    pending: 'warning',
    processing: 'info',
    completed: 'success',
    failed: 'danger',
    rejected: 'dark'
  }
  return variants[status] || 'secondary'
}

const getSettlementStatusVariant = (status) => {
  const variants = {
    pending: 'warning',
    processing: 'info',
    settled: 'success',
    failed: 'danger'
  }
  return variants[status] || 'secondary'
}

const getInvoiceStatusVariant = (status) => {
  const variants = {
    draft: 'secondary',
    generated: 'info',
    sent: 'primary',
    paid: 'success'
  }
  return variants[status] || 'secondary'
}

const loadDashboard = async () => {
  loading.value = true
  try {
    const params = {
      date_range: dateRange.value,
      start_date: customStartDate.value,
      end_date: customEndDate.value
    }
    const response = await axios.get('/api/admin/finance/dashboard', { params })
    if (response.data.success) {
      Object.assign(stats, response.data.data.stats)
      Object.assign(taxStats, response.data.data.tax_stats)
      taxBreakdown.value = response.data.data.tax_breakdown || []
    }
  } catch (error) {
    console.error('Failed to load dashboard:', error)
  } finally {
    loading.value = false
  }
}

const loadPayments = async () => {
  loadingPayments.value = true
  try {
    const params = {
      ...paymentFilters,
      page: paymentsPagination.currentPage,
      per_page: paymentsPagination.perPage,
      date_range: dateRange.value,
      start_date: customStartDate.value,
      end_date: customEndDate.value
    }
    const response = await axios.get('/api/admin/finance/payments', { params })
    if (response.data.success) {
      payments.value = response.data.data.data
      paymentsPagination.total = response.data.data.total
    }
  } catch (error) {
    console.error('Failed to load payments:', error)
  } finally {
    loadingPayments.value = false
  }
}

const loadRefunds = async () => {
  loadingRefunds.value = true
  try {
    const params = {
      ...refundFilters,
      page: refundsPagination.currentPage,
      per_page: refundsPagination.perPage
    }
    const response = await axios.get('/api/admin/finance/refunds', { params })
    if (response.data.success) {
      refunds.value = response.data.data.data
      refundsPagination.total = response.data.data.total
    }
  } catch (error) {
    console.error('Failed to load refunds:', error)
  } finally {
    loadingRefunds.value = false
  }
}

const loadSettlements = async () => {
  loadingSettlements.value = true
  try {
    const response = await axios.get('/api/admin/finance/settlements')
    if (response.data.success) {
      settlements.value = response.data.data.settlements
      Object.assign(settlementStats, response.data.data.stats)
    }
  } catch (error) {
    console.error('Failed to load settlements:', error)
  } finally {
    loadingSettlements.value = false
  }
}

const loadInvoices = async () => {
  loadingInvoices.value = true
  try {
    const params = {
      ...invoiceFilters,
      page: invoicesPagination.currentPage,
      per_page: invoicesPagination.perPage
    }
    const response = await axios.get('/api/admin/finance/invoices', { params })
    if (response.data.success) {
      invoices.value = response.data.data.data
      invoicesPagination.total = response.data.data.total
    }
  } catch (error) {
    console.error('Failed to load invoices:', error)
  } finally {
    loadingInvoices.value = false
  }
}

const viewPaymentDetails = async (payment) => {
  try {
    const response = await axios.get(`/api/admin/finance/payments/${payment.id}`)
    if (response.data.success) {
      selectedPaymentDetails.value = response.data.data
      showPaymentDetailsModal.value = true
    }
  } catch (error) {
    console.error('Failed to load payment details:', error)
  }
}

const initiateRefund = (payment) => {
  selectedPayment.value = payment
  refundForm.amount = payment.amount
  refundForm.type = 'full'
  refundForm.reason = ''
  showRefundModal.value = true
}

const submitRefund = async () => {
  try {
    const response = await axios.post(`/api/admin/finance/payments/${selectedPayment.value.id}/refund`, refundForm)
    if (response.data.success) {
      showRefundModal.value = false
      loadPayments()
      loadRefunds()
      loadDashboard()
    }
  } catch (error) {
    console.error('Failed to initiate refund:', error)
  }
}

const processRefund = async (refund) => {
  if (!confirm('Are you sure you want to process this refund?')) return
  try {
    const response = await axios.post(`/api/admin/finance/refunds/${refund.id}/process`)
    if (response.data.success) {
      loadRefunds()
      loadDashboard()
    }
  } catch (error) {
    console.error('Failed to process refund:', error)
  }
}

const rejectRefund = async (refund) => {
  const reason = prompt('Enter rejection reason:')
  if (!reason) return
  try {
    const response = await axios.post(`/api/admin/finance/refunds/${refund.id}/reject`, { reason })
    if (response.data.success) {
      loadRefunds()
    }
  } catch (error) {
    console.error('Failed to reject refund:', error)
  }
}

const viewRefundDetails = (refund) => {
  // Navigate to refund details page
}

const downloadReceipt = async (payment) => {
  try {
    const response = await axios.get(`/api/admin/finance/payments/${payment.id}/receipt`, {
      responseType: 'blob'
    })
    const url = window.URL.createObjectURL(new Blob([response.data]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `receipt-${payment.transaction_id}.pdf`)
    document.body.appendChild(link)
    link.click()
    link.remove()
  } catch (error) {
    console.error('Failed to download receipt:', error)
  }
}

const viewSettlementDetails = (settlement) => {
  // Navigate to settlement details page
}

const viewInvoice = (invoice) => {
  // Open invoice preview modal
}

const downloadInvoice = async (invoice) => {
  try {
    const response = await axios.get(`/api/admin/finance/invoices/${invoice.id}/download`, {
      responseType: 'blob'
    })
    const url = window.URL.createObjectURL(new Blob([response.data]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `invoice-${invoice.invoice_number}.pdf`)
    document.body.appendChild(link)
    link.click()
    link.remove()
  } catch (error) {
    console.error('Failed to download invoice:', error)
  }
}

const emailInvoice = async (invoice) => {
  try {
    const response = await axios.post(`/api/admin/finance/invoices/${invoice.id}/email`)
    if (response.data.success) {
      alert('Invoice sent successfully!')
    }
  } catch (error) {
    console.error('Failed to email invoice:', error)
  }
}

const generateBulkInvoices = async () => {
  if (!confirm('Generate invoices for all pending orders?')) return
  try {
    const response = await axios.post('/api/admin/finance/invoices/generate-bulk')
    if (response.data.success) {
      alert(`Generated ${response.data.data.count} invoices`)
      loadInvoices()
    }
  } catch (error) {
    console.error('Failed to generate invoices:', error)
  }
}

const runReconciliation = async () => {
  try {
    const response = await axios.post('/api/admin/finance/reconcile', reconciliationForm)
    if (response.data.success) {
      alert(`Reconciliation complete. ${response.data.data.matched} matched, ${response.data.data.discrepancies} discrepancies found.`)
      showReconciliationModal.value = false
      loadPayments()
      loadDashboard()
    }
  } catch (error) {
    console.error('Failed to run reconciliation:', error)
  }
}

const exportFinanceReport = async () => {
  try {
    const params = {
      date_range: dateRange.value,
      start_date: customStartDate.value,
      end_date: customEndDate.value
    }
    const response = await axios.get('/api/admin/finance/export', {
      params,
      responseType: 'blob'
    })
    const url = window.URL.createObjectURL(new Blob([response.data]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', 'finance-report.xlsx')
    document.body.appendChild(link)
    link.click()
    link.remove()
  } catch (error) {
    console.error('Failed to export report:', error)
  }
}

const exportGSTR1 = async () => {
  try {
    const params = {
      date_range: dateRange.value,
      start_date: customStartDate.value,
      end_date: customEndDate.value
    }
    const response = await axios.get('/api/admin/finance/tax/gstr1', {
      params,
      responseType: 'blob'
    })
    const url = window.URL.createObjectURL(new Blob([response.data]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', 'gstr1-report.xlsx')
    document.body.appendChild(link)
    link.click()
    link.remove()
  } catch (error) {
    console.error('Failed to export GSTR-1:', error)
  }
}

const exportGSTR3B = async () => {
  try {
    const params = {
      date_range: dateRange.value,
      start_date: customStartDate.value,
      end_date: customEndDate.value
    }
    const response = await axios.get('/api/admin/finance/tax/gstr3b', {
      params,
      responseType: 'blob'
    })
    const url = window.URL.createObjectURL(new Blob([response.data]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', 'gstr3b-report.xlsx')
    document.body.appendChild(link)
    link.click()
    link.remove()
  } catch (error) {
    console.error('Failed to export GSTR-3B:', error)
  }
}

const exportTaxReport = async () => {
  try {
    const params = {
      date_range: dateRange.value,
      start_date: customStartDate.value,
      end_date: customEndDate.value
    }
    const response = await axios.get('/api/admin/finance/tax/report', {
      params,
      responseType: 'blob'
    })
    const url = window.URL.createObjectURL(new Blob([response.data]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', 'tax-report.pdf')
    document.body.appendChild(link)
    link.click()
    link.remove()
  } catch (error) {
    console.error('Failed to export tax report:', error)
  }
}

// Lifecycle
onMounted(() => {
  loadDashboard()
  loadPayments()
  loadRefunds()
  loadSettlements()
  loadInvoices()
})
</script>

<style scoped>
.stats-card {
  transition: transform 0.2s;
}
.stats-card:hover {
  transform: translateY(-2px);
}
.stats-icon {
  width: 48px;
  height: 48px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.5rem;
}
.timeline {
  position: relative;
  padding-left: 30px;
}
.timeline-item {
  position: relative;
  padding-bottom: 15px;
}
.timeline-item:last-child {
  padding-bottom: 0;
}
.timeline-marker {
  position: absolute;
  left: -30px;
  width: 12px;
  height: 12px;
  border-radius: 50%;
  background: #007bff;
  border: 2px solid #fff;
  box-shadow: 0 0 0 2px #007bff;
}
.timeline-item:not(:last-child)::before {
  content: '';
  position: absolute;
  left: -25px;
  top: 12px;
  width: 2px;
  height: calc(100% - 12px);
  background: #e9ecef;
}
</style>
