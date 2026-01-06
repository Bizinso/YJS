<template>
    <div class="listing_screen global_table_liting">
        <div class="masterTabs">
            <div class="masterTabContent">
                <!-- Dashboard Stats -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="stat-card warning">
                            <h3>{{ stats.pending_returns }}</h3>
                            <p>Pending Returns</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card info">
                            <h3>{{ stats.pending_exchanges }}</h3>
                            <p>Pending Exchanges</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card primary">
                            <h3>{{ stats.pending_cancellations }}</h3>
                            <p>Pending Cancellations</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card success">
                            <h3>{{ formatCurrency(stats.refunds_processed) }}</h3>
                            <p>Refunds Processed</p>
                        </div>
                    </div>
                </div>

                <!-- Tabs -->
                <b-tabs v-model="activeTab" content-class="mt-3">
                    <!-- Returns Tab -->
                    <b-tab title="Returns" active>
                        <div class="listing_tab_and_actions mb-3">
                            <div class="listing_actions">
                                <div class="d-flex gap-2">
                                    <v-select v-model="returnStatusFilter" :options="returnStatuses" placeholder="Status" :clearable="true" class="status-select" />
                                </div>
                                <div class="buttonGrid">
                                    <b-button class="transBTN" @click="exportReturns">Export</b-button>
                                </div>
                            </div>
                        </div>

                        <b-table responsive="sm" :items="returns" :fields="returnFields" v-if="returns.length > 0">
                            <template #cell(return_number)="row">
                                <a href="#" @click.prevent="viewReturn(row.item)">{{ row.item.return_number }}</a>
                            </template>
                            <template #cell(order)="row">
                                {{ row.item.order?.order_number }}
                            </template>
                            <template #cell(customer)="row">
                                {{ row.item.customer?.name }}
                            </template>
                            <template #cell(refund_amount)="row">
                                {{ formatCurrency(row.item.refund_amount) }}
                            </template>
                            <template #cell(status)="row">
                                <b-badge :variant="getReturnStatusVariant(row.item.status)">{{ row.item.status }}</b-badge>
                            </template>
                            <template #cell(actions)="row">
                                <b-dropdown right text="⋮" variant="link" no-caret toggle-class="p-0">
                                    <b-dropdown-item @click="viewReturn(row.item)">View Details</b-dropdown-item>
                                    <b-dropdown-item v-if="row.item.status === 'pending'" @click="approveReturn(row.item)">Approve</b-dropdown-item>
                                    <b-dropdown-item v-if="row.item.status === 'pending'" @click="rejectReturn(row.item)">Reject</b-dropdown-item>
                                    <b-dropdown-item v-if="row.item.status === 'approved'" @click="schedulePickup(row.item)">Schedule Pickup</b-dropdown-item>
                                    <b-dropdown-item v-if="row.item.status === 'picked_up'" @click="markReceived(row.item)">Mark Received</b-dropdown-item>
                                    <b-dropdown-item v-if="row.item.status === 'received'" @click="initiateRefund(row.item)">Initiate Refund</b-dropdown-item>
                                </b-dropdown>
                            </template>
                        </b-table>
                        <div v-else class="text-center p-5"><p>No returns found.</p></div>
                    </b-tab>

                    <!-- Exchanges Tab -->
                    <b-tab title="Exchanges">
                        <div class="listing_tab_and_actions mb-3">
                            <div class="listing_actions">
                                <div class="d-flex gap-2">
                                    <v-select v-model="exchangeStatusFilter" :options="exchangeStatuses" placeholder="Status" :clearable="true" class="status-select" />
                                </div>
                                <div class="buttonGrid">
                                    <b-button class="transBTN" @click="exportExchanges">Export</b-button>
                                </div>
                            </div>
                        </div>

                        <b-table responsive="sm" :items="exchanges" :fields="exchangeFields" v-if="exchanges.length > 0">
                            <template #cell(exchange_number)="row">
                                <a href="#" @click.prevent="viewExchange(row.item)">{{ row.item.exchange_number }}</a>
                            </template>
                            <template #cell(order)="row">
                                {{ row.item.order?.order_number }}
                            </template>
                            <template #cell(customer)="row">
                                {{ row.item.customer?.name }}
                            </template>
                            <template #cell(price_difference)="row">
                                <span :class="row.item.price_difference >= 0 ? 'text-success' : 'text-danger'">
                                    {{ formatCurrency(Math.abs(row.item.price_difference)) }}
                                    {{ row.item.price_difference >= 0 ? '(Pay)' : '(Refund)' }}
                                </span>
                            </template>
                            <template #cell(status)="row">
                                <b-badge :variant="getExchangeStatusVariant(row.item.status)">{{ row.item.status }}</b-badge>
                            </template>
                            <template #cell(actions)="row">
                                <b-dropdown right text="⋮" variant="link" no-caret toggle-class="p-0">
                                    <b-dropdown-item @click="viewExchange(row.item)">View Details</b-dropdown-item>
                                    <b-dropdown-item v-if="row.item.status === 'pending'" @click="approveExchange(row.item)">Approve</b-dropdown-item>
                                    <b-dropdown-item v-if="row.item.status === 'pending'" @click="rejectExchange(row.item)">Reject</b-dropdown-item>
                                    <b-dropdown-item v-if="row.item.status === 'return_received'" @click="processExchange(row.item)">Process Exchange</b-dropdown-item>
                                    <b-dropdown-item v-if="row.item.status === 'processing'" @click="shipExchange(row.item)">Ship New Item</b-dropdown-item>
                                </b-dropdown>
                            </template>
                        </b-table>
                        <div v-else class="text-center p-5"><p>No exchanges found.</p></div>
                    </b-tab>

                    <!-- Cancellations Tab -->
                    <b-tab title="Cancellations">
                        <div class="listing_tab_and_actions mb-3">
                            <div class="listing_actions">
                                <div class="d-flex gap-2">
                                    <v-select v-model="cancellationStatusFilter" :options="cancellationStatuses" placeholder="Status" :clearable="true" class="status-select" />
                                </div>
                                <div class="buttonGrid">
                                    <b-button class="transBTN" @click="exportCancellations">Export</b-button>
                                </div>
                            </div>
                        </div>

                        <b-table responsive="sm" :items="cancellations" :fields="cancellationFields" v-if="cancellations.length > 0">
                            <template #cell(cancellation_number)="row">
                                <a href="#" @click.prevent="viewCancellation(row.item)">{{ row.item.cancellation_number }}</a>
                            </template>
                            <template #cell(order)="row">
                                {{ row.item.order?.order_number }}
                            </template>
                            <template #cell(customer)="row">
                                {{ row.item.customer?.name }}
                            </template>
                            <template #cell(refund_amount)="row">
                                {{ formatCurrency(row.item.refund_amount) }}
                            </template>
                            <template #cell(status)="row">
                                <b-badge :variant="getCancellationStatusVariant(row.item.status)">{{ row.item.status }}</b-badge>
                            </template>
                            <template #cell(actions)="row">
                                <b-dropdown right text="⋮" variant="link" no-caret toggle-class="p-0">
                                    <b-dropdown-item @click="viewCancellation(row.item)">View Details</b-dropdown-item>
                                    <b-dropdown-item v-if="row.item.status === 'pending'" @click="approveCancellation(row.item)">Approve</b-dropdown-item>
                                    <b-dropdown-item v-if="row.item.status === 'pending'" @click="rejectCancellation(row.item)">Reject</b-dropdown-item>
                                    <b-dropdown-item v-if="row.item.status === 'approved'" @click="initiateCancellationRefund(row.item)">Initiate Refund</b-dropdown-item>
                                </b-dropdown>
                            </template>
                        </b-table>
                        <div v-else class="text-center p-5"><p>No cancellations found.</p></div>
                    </b-tab>

                    <!-- Return Policy Tab -->
                    <b-tab title="Return Policy">
                        <div class="policy-section">
                            <h5>Return Policy Settings</h5>
                            <b-form @submit.prevent="updatePolicy">
                                <div class="row">
                                    <div class="col-md-6">
                                        <b-form-group label="Return Window (Days)">
                                            <b-form-input v-model="policy.return_window_days" type="number" />
                                        </b-form-group>
                                        <b-form-group label="Exchange Window (Days)">
                                            <b-form-input v-model="policy.exchange_window_days" type="number" />
                                        </b-form-group>
                                        <b-form-group label="Cancellation Window (Hours)">
                                            <b-form-input v-model="policy.cancellation_window_hours" type="number" />
                                        </b-form-group>
                                    </div>
                                    <div class="col-md-6">
                                        <b-form-group label="Restocking Fee (%)">
                                            <b-form-input v-model="policy.restocking_fee_percent" type="number" step="0.1" />
                                        </b-form-group>
                                        <b-form-group label="Free Return Threshold">
                                            <b-form-input v-model="policy.free_return_threshold" type="number" />
                                        </b-form-group>
                                        <b-form-group>
                                            <b-form-checkbox v-model="policy.allow_partial_returns">Allow Partial Returns</b-form-checkbox>
                                        </b-form-group>
                                    </div>
                                </div>
                                <b-button type="submit" class="fillBTN">Save Policy</b-button>
                            </b-form>
                        </div>
                    </b-tab>
                </b-tabs>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue'
import axiosEmployee from '@axiosEmployee'
import { toast } from 'vue3-toastify'
import moment from 'moment'

// State
const activeTab = ref(0)
const stats = ref({ pending_returns: 0, pending_exchanges: 0, pending_cancellations: 0, refunds_processed: 0 })

// Returns
const returns = ref([])
const returnStatusFilter = ref(null)
const returnStatuses = ['pending', 'approved', 'rejected', 'picked_up', 'received', 'refund_initiated', 'refunded']
const returnFields = [
    { key: 'return_number', label: 'Return #' },
    { key: 'order', label: 'Order' },
    { key: 'customer', label: 'Customer' },
    { key: 'reason', label: 'Reason' },
    { key: 'refund_amount', label: 'Refund' },
    { key: 'status', label: 'Status' },
    { key: 'created_at', label: 'Date' },
    { key: 'actions', label: '' }
]

// Exchanges
const exchanges = ref([])
const exchangeStatusFilter = ref(null)
const exchangeStatuses = ['pending', 'approved', 'rejected', 'return_received', 'processing', 'shipped', 'delivered']
const exchangeFields = [
    { key: 'exchange_number', label: 'Exchange #' },
    { key: 'order', label: 'Order' },
    { key: 'customer', label: 'Customer' },
    { key: 'reason', label: 'Reason' },
    { key: 'price_difference', label: 'Difference' },
    { key: 'status', label: 'Status' },
    { key: 'actions', label: '' }
]

// Cancellations
const cancellations = ref([])
const cancellationStatusFilter = ref(null)
const cancellationStatuses = ['pending', 'approved', 'rejected', 'refund_initiated', 'refunded']
const cancellationFields = [
    { key: 'cancellation_number', label: 'Cancellation #' },
    { key: 'order', label: 'Order' },
    { key: 'customer', label: 'Customer' },
    { key: 'reason', label: 'Reason' },
    { key: 'refund_amount', label: 'Refund' },
    { key: 'status', label: 'Status' },
    { key: 'actions', label: '' }
]

// Policy
const policy = ref({
    return_window_days: 7,
    exchange_window_days: 15,
    cancellation_window_hours: 24,
    restocking_fee_percent: 0,
    free_return_threshold: 5000,
    allow_partial_returns: true
})

// Fetch functions
const fetchDashboard = async () => {
    try {
        const [returnsRes, exchangesRes, cancellationsRes] = await Promise.all([
            axiosEmployee.get('/admin/returns/dashboard'),
            axiosEmployee.get('/admin/exchanges/dashboard'),
            axiosEmployee.get('/admin/cancellations/dashboard')
        ])
        stats.value = {
            pending_returns: returnsRes.data.data?.pending_count || 0,
            pending_exchanges: exchangesRes.data.data?.pending_count || 0,
            pending_cancellations: cancellationsRes.data.data?.pending_count || 0,
            refunds_processed: returnsRes.data.data?.total_refunds || 0
        }
    } catch (error) {
        console.error('Error fetching dashboard:', error)
    }
}

const fetchReturns = async () => {
    try {
        const response = await axiosEmployee.get('/admin/returns/', { params: { status: returnStatusFilter.value } })
        if (response.data.success) returns.value = response.data.data.data || []
    } catch (error) { console.error('Error:', error) }
}

const fetchExchanges = async () => {
    try {
        const response = await axiosEmployee.get('/admin/exchanges/', { params: { status: exchangeStatusFilter.value } })
        if (response.data.success) exchanges.value = response.data.data.data || []
    } catch (error) { console.error('Error:', error) }
}

const fetchCancellations = async () => {
    try {
        const response = await axiosEmployee.get('/admin/cancellations/', { params: { status: cancellationStatusFilter.value } })
        if (response.data.success) cancellations.value = response.data.data.data || []
    } catch (error) { console.error('Error:', error) }
}

const fetchPolicy = async () => {
    try {
        const response = await axiosEmployee.get('/admin/return-policy/')
        if (response.data.success) policy.value = { ...policy.value, ...response.data.data }
    } catch (error) { console.error('Error:', error) }
}

// Helpers
const formatCurrency = (amount) => new Intl.NumberFormat('en-IN', { style: 'currency', currency: 'INR' }).format(amount || 0)
const getReturnStatusVariant = (s) => ({ pending: 'warning', approved: 'info', rejected: 'danger', picked_up: 'primary', received: 'info', refund_initiated: 'primary', refunded: 'success' }[s] || 'secondary')
const getExchangeStatusVariant = (s) => ({ pending: 'warning', approved: 'info', rejected: 'danger', return_received: 'info', processing: 'primary', shipped: 'info', delivered: 'success' }[s] || 'secondary')
const getCancellationStatusVariant = (s) => ({ pending: 'warning', approved: 'info', rejected: 'danger', refund_initiated: 'primary', refunded: 'success' }[s] || 'secondary')

// Actions
const viewReturn = (item) => toast.info('View return details')
const approveReturn = async (item) => { try { await axiosEmployee.post(`/admin/returns/${item.id}/approve`); toast.success('Return approved'); fetchReturns(); fetchDashboard() } catch (e) { toast.error(e.response?.data?.message || 'Failed') } }
const rejectReturn = async (item) => { const reason = prompt('Rejection reason:'); if (!reason) return; try { await axiosEmployee.post(`/admin/returns/${item.id}/reject`, { reason }); toast.success('Return rejected'); fetchReturns() } catch (e) { toast.error('Failed') } }
const schedulePickup = async (item) => { try { await axiosEmployee.post(`/admin/returns/${item.id}/schedule-pickup`); toast.success('Pickup scheduled'); fetchReturns() } catch (e) { toast.error('Failed') } }
const markReceived = async (item) => { try { await axiosEmployee.post(`/admin/returns/${item.id}/mark-received`); toast.success('Marked as received'); fetchReturns() } catch (e) { toast.error('Failed') } }
const initiateRefund = async (item) => { try { await axiosEmployee.post(`/admin/returns/${item.id}/initiate-refund`); toast.success('Refund initiated'); fetchReturns(); fetchDashboard() } catch (e) { toast.error('Failed') } }

const viewExchange = (item) => toast.info('View exchange details')
const approveExchange = async (item) => { try { await axiosEmployee.post(`/admin/exchanges/${item.id}/approve`); toast.success('Exchange approved'); fetchExchanges(); fetchDashboard() } catch (e) { toast.error('Failed') } }
const rejectExchange = async (item) => { const reason = prompt('Rejection reason:'); if (!reason) return; try { await axiosEmployee.post(`/admin/exchanges/${item.id}/reject`, { reason }); toast.success('Exchange rejected'); fetchExchanges() } catch (e) { toast.error('Failed') } }
const processExchange = async (item) => { try { await axiosEmployee.post(`/admin/exchanges/${item.id}/process`); toast.success('Exchange processed'); fetchExchanges() } catch (e) { toast.error('Failed') } }
const shipExchange = async (item) => { try { await axiosEmployee.post(`/admin/exchanges/${item.id}/ship`); toast.success('Exchange shipped'); fetchExchanges() } catch (e) { toast.error('Failed') } }

const viewCancellation = (item) => toast.info('View cancellation details')
const approveCancellation = async (item) => { try { await axiosEmployee.post(`/admin/cancellations/${item.id}/approve`); toast.success('Cancellation approved'); fetchCancellations(); fetchDashboard() } catch (e) { toast.error('Failed') } }
const rejectCancellation = async (item) => { const reason = prompt('Rejection reason:'); if (!reason) return; try { await axiosEmployee.post(`/admin/cancellations/${item.id}/reject`, { reason }); toast.success('Cancellation rejected'); fetchCancellations() } catch (e) { toast.error('Failed') } }
const initiateCancellationRefund = async (item) => { try { await axiosEmployee.post(`/admin/cancellations/${item.id}/initiate-refund`); toast.success('Refund initiated'); fetchCancellations(); fetchDashboard() } catch (e) { toast.error('Failed') } }

const exportReturns = () => toast.info('Export returns')
const exportExchanges = () => toast.info('Export exchanges')
const exportCancellations = () => toast.info('Export cancellations')

const updatePolicy = async () => {
    try {
        await axiosEmployee.put('/admin/return-policy/', policy.value)
        toast.success('Policy updated')
    } catch (error) { toast.error('Failed to update policy') }
}

// Watchers
watch(returnStatusFilter, fetchReturns)
watch(exchangeStatusFilter, fetchExchanges)
watch(cancellationStatusFilter, fetchCancellations)

// Lifecycle
onMounted(() => { fetchDashboard(); fetchReturns(); fetchExchanges(); fetchCancellations(); fetchPolicy() })
</script>

<style scoped>
.stat-card { background: #fff; border-radius: 8px; padding: 20px; text-align: center; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
.stat-card h3 { font-size: 1.8rem; margin-bottom: 5px; }
.stat-card p { margin: 0; color: #666; }
.stat-card.warning h3 { color: #ffc107; }
.stat-card.info h3 { color: #17a2b8; }
.stat-card.primary h3 { color: #007bff; }
.stat-card.success h3 { color: #28a745; }
.status-select { min-width: 150px; }
.policy-section { background: #fff; padding: 20px; border-radius: 8px; }
</style>
