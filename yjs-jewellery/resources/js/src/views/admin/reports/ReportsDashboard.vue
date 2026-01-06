<template>
    <div class="listing_screen global_table_liting">
        <div class="masterTabs">
            <div class="masterTabContent">
                <!-- Dashboard Header -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4>Reports & Analytics</h4>
                    <div class="d-flex gap-2">
                        <v-select v-model="dateRange" :options="dateRanges" placeholder="Date Range" class="date-select" />
                        <b-button class="transBTN" @click="exportReport">Export</b-button>
                    </div>
                </div>

                <!-- Stats Cards -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="stat-card">
                            <h3>{{ formatCurrency(stats.total_revenue) }}</h3>
                            <p>Total Revenue</p>
                            <small :class="stats.revenue_change >= 0 ? 'text-success' : 'text-danger'">
                                {{ stats.revenue_change >= 0 ? '+' : '' }}{{ stats.revenue_change }}% vs last period
                            </small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <h3>{{ stats.total_orders }}</h3>
                            <p>Total Orders</p>
                            <small :class="stats.orders_change >= 0 ? 'text-success' : 'text-danger'">
                                {{ stats.orders_change >= 0 ? '+' : '' }}{{ stats.orders_change }}% vs last period
                            </small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <h3>{{ formatCurrency(stats.avg_order_value) }}</h3>
                            <p>Avg Order Value</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <h3>{{ stats.conversion_rate }}%</h3>
                            <p>Conversion Rate</p>
                        </div>
                    </div>
                </div>

                <!-- Tabs -->
                <b-tabs v-model="activeTab" content-class="mt-3">
                    <!-- Sales Report -->
                    <b-tab title="Sales" active>
                        <div class="report-section">
                            <h5>Sales Overview</h5>
                            <div class="chart-placeholder mb-4">
                                <!-- Chart would go here -->
                                <p class="text-center text-muted p-5">Revenue Chart Placeholder</p>
                            </div>

                            <b-table responsive="sm" :items="salesData" :fields="salesFields" v-if="salesData.length > 0">
                                <template #cell(date)="row">
                                    {{ formatDate(row.item.date) }}
                                </template>
                                <template #cell(revenue)="row">
                                    {{ formatCurrency(row.item.revenue) }}
                                </template>
                                <template #cell(profit)="row">
                                    {{ formatCurrency(row.item.profit) }}
                                </template>
                            </b-table>
                        </div>
                    </b-tab>

                    <!-- Products Report -->
                    <b-tab title="Products">
                        <div class="report-section">
                            <h5>Top Performing Products</h5>
                            <b-table responsive="sm" :items="topProducts" :fields="productFields" v-if="topProducts.length > 0">
                                <template #cell(product)="row">
                                    <div class="d-flex align-items-center">
                                        <img :src="row.item.image || '/placeholder.jpg'" width="40" height="40" class="me-2" style="object-fit: cover; border-radius: 4px;" />
                                        {{ row.item.name }}
                                    </div>
                                </template>
                                <template #cell(revenue)="row">
                                    {{ formatCurrency(row.item.revenue) }}
                                </template>
                            </b-table>
                        </div>
                    </b-tab>

                    <!-- Customers Report -->
                    <b-tab title="Customers">
                        <div class="report-section">
                            <h5>Customer Insights</h5>
                            <div class="row mb-4">
                                <div class="col-md-4">
                                    <div class="stat-card small">
                                        <h4>{{ customerStats.new_customers }}</h4>
                                        <p>New Customers</p>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="stat-card small">
                                        <h4>{{ customerStats.returning_customers }}</h4>
                                        <p>Returning Customers</p>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="stat-card small">
                                        <h4>{{ formatCurrency(customerStats.avg_lifetime_value) }}</h4>
                                        <p>Avg Lifetime Value</p>
                                    </div>
                                </div>
                            </div>

                            <h5>Top Customers</h5>
                            <b-table responsive="sm" :items="topCustomers" :fields="customerFields" v-if="topCustomers.length > 0">
                                <template #cell(total_spent)="row">
                                    {{ formatCurrency(row.item.total_spent) }}
                                </template>
                            </b-table>
                        </div>
                    </b-tab>

                    <!-- Inventory Report -->
                    <b-tab title="Inventory">
                        <div class="report-section">
                            <h5>Inventory Status</h5>
                            <div class="row mb-4">
                                <div class="col-md-3">
                                    <div class="stat-card small">
                                        <h4>{{ inventoryStats.total_products }}</h4>
                                        <p>Total Products</p>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="stat-card small warning">
                                        <h4>{{ inventoryStats.low_stock }}</h4>
                                        <p>Low Stock</p>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="stat-card small danger">
                                        <h4>{{ inventoryStats.out_of_stock }}</h4>
                                        <p>Out of Stock</p>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="stat-card small">
                                        <h4>{{ formatCurrency(inventoryStats.inventory_value) }}</h4>
                                        <p>Total Value</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </b-tab>

                    <!-- Saved Reports -->
                    <b-tab title="Saved Reports">
                        <div class="listing_tab_and_actions mb-3">
                            <div class="listing_actions">
                                <div class="d-flex"></div>
                                <div class="buttonGrid">
                                    <b-button class="fillBTN" @click="saveCurrentReport">Save Report</b-button>
                                </div>
                            </div>
                        </div>

                        <b-table responsive="sm" :items="savedReports" :fields="savedReportFields" v-if="savedReports.length > 0">
                            <template #cell(name)="row">
                                <a href="#" @click.prevent="loadSavedReport(row.item)">{{ row.item.name }}</a>
                            </template>
                            <template #cell(created_at)="row">
                                {{ formatDate(row.item.created_at) }}
                            </template>
                            <template #cell(actions)="row">
                                <b-button size="sm" variant="outline-danger" @click="deleteSavedReport(row.item.id)">Delete</b-button>
                            </template>
                        </b-table>

                        <div v-else class="text-center p-5">
                            <p>No saved reports. Save custom reports for quick access.</p>
                        </div>
                    </b-tab>

                    <!-- Audit Logs -->
                    <b-tab title="Audit Logs">
                        <div class="listing_tab_and_actions mb-3">
                            <div class="listing_actions">
                                <div class="d-flex gap-2">
                                    <v-select v-model="auditActionFilter" :options="auditActions"
                                        placeholder="Filter by action" :clearable="true" class="action-select" />
                                </div>
                            </div>
                        </div>

                        <b-table responsive="sm" :items="auditLogs" :fields="auditFields" v-if="auditLogs.length > 0">
                            <template #cell(user)="row">
                                {{ row.item.user?.name || 'System' }}
                            </template>
                            <template #cell(action)="row">
                                <b-badge :variant="getAuditActionVariant(row.item.action)">{{ row.item.action }}</b-badge>
                            </template>
                            <template #cell(created_at)="row">
                                {{ formatDateTime(row.item.created_at) }}
                            </template>
                            <template #cell(actions)="row">
                                <b-button size="sm" variant="outline-primary" @click="viewAuditDetail(row.item)">Details</b-button>
                            </template>
                        </b-table>

                        <div v-else class="text-center p-5">
                            <p>No audit logs found.</p>
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
const dateRange = ref('last_30_days')
const dateRanges = [
    { label: 'Today', value: 'today' },
    { label: 'Yesterday', value: 'yesterday' },
    { label: 'Last 7 Days', value: 'last_7_days' },
    { label: 'Last 30 Days', value: 'last_30_days' },
    { label: 'This Month', value: 'this_month' },
    { label: 'Last Month', value: 'last_month' },
    { label: 'This Year', value: 'this_year' }
]

// Stats
const stats = ref({
    total_revenue: 0,
    revenue_change: 0,
    total_orders: 0,
    orders_change: 0,
    avg_order_value: 0,
    conversion_rate: 0
})

// Sales data
const salesData = ref([])
const salesFields = [
    { key: 'date', label: 'Date' },
    { key: 'orders', label: 'Orders' },
    { key: 'revenue', label: 'Revenue' },
    { key: 'profit', label: 'Profit' }
]

// Products
const topProducts = ref([])
const productFields = [
    { key: 'product', label: 'Product' },
    { key: 'units_sold', label: 'Units Sold' },
    { key: 'revenue', label: 'Revenue' },
    { key: 'stock', label: 'Current Stock' }
]

// Customers
const customerStats = ref({
    new_customers: 0,
    returning_customers: 0,
    avg_lifetime_value: 0
})
const topCustomers = ref([])
const customerFields = [
    { key: 'name', label: 'Customer' },
    { key: 'email', label: 'Email' },
    { key: 'orders', label: 'Orders' },
    { key: 'total_spent', label: 'Total Spent' }
]

// Inventory
const inventoryStats = ref({
    total_products: 0,
    low_stock: 0,
    out_of_stock: 0,
    inventory_value: 0
})

// Saved Reports
const savedReports = ref([])
const savedReportFields = [
    { key: 'name', label: 'Report Name' },
    { key: 'type', label: 'Type' },
    { key: 'created_at', label: 'Created' },
    { key: 'actions', label: 'Actions' }
]

// Audit
const auditLogs = ref([])
const auditActionFilter = ref(null)
const auditActions = ['create', 'update', 'delete', 'login', 'logout', 'export']
const auditFields = [
    { key: 'user', label: 'User' },
    { key: 'action', label: 'Action' },
    { key: 'entity_type', label: 'Entity' },
    { key: 'description', label: 'Description' },
    { key: 'created_at', label: 'Time' },
    { key: 'actions', label: '' }
]

// Fetch functions
const fetchDashboard = async () => {
    try {
        const response = await axiosEmployee.get('/admin/reports/dashboard', {
            params: { date_range: dateRange.value }
        })
        if (response.data.success) {
            const data = response.data.data
            stats.value = data.stats || stats.value
            salesData.value = data.sales || []
        }
    } catch (error) {
        console.error('Error fetching dashboard:', error)
    }
}

const fetchTopProducts = async () => {
    try {
        const response = await axiosEmployee.get('/admin/reports/products-performance', {
            params: { date_range: dateRange.value }
        })
        if (response.data.success) {
            topProducts.value = response.data.data.products || []
        }
    } catch (error) {
        console.error('Error fetching products:', error)
    }
}

const fetchCustomers = async () => {
    try {
        const response = await axiosEmployee.get('/admin/reports/customers-analysis', {
            params: { date_range: dateRange.value }
        })
        if (response.data.success) {
            customerStats.value = response.data.data.stats || customerStats.value
            topCustomers.value = response.data.data.top_customers || []
        }
    } catch (error) {
        console.error('Error fetching customers:', error)
    }
}

const fetchInventory = async () => {
    try {
        const response = await axiosEmployee.get('/admin/reports/inventory-status')
        if (response.data.success) {
            inventoryStats.value = response.data.data.stats || inventoryStats.value
        }
    } catch (error) {
        console.error('Error fetching inventory:', error)
    }
}

const fetchSavedReports = async () => {
    try {
        const response = await axiosEmployee.get('/admin/reports/saved')
        if (response.data.success) {
            savedReports.value = response.data.data.data || []
        }
    } catch (error) {
        console.error('Error fetching saved reports:', error)
    }
}

const fetchAuditLogs = async () => {
    try {
        const response = await axiosEmployee.get('/admin/audit/', {
            params: { action: auditActionFilter.value }
        })
        if (response.data.success) {
            auditLogs.value = response.data.data.data || []
        }
    } catch (error) {
        console.error('Error fetching audit logs:', error)
    }
}

// Helper functions
const formatCurrency = (amount) => {
    return new Intl.NumberFormat('en-IN', { style: 'currency', currency: 'INR' }).format(amount || 0)
}

const formatDate = (date) => {
    return moment(date).format('DD MMM YYYY')
}

const formatDateTime = (date) => {
    return moment(date).format('DD MMM YYYY HH:mm')
}

const getAuditActionVariant = (action) => {
    return { create: 'success', update: 'info', delete: 'danger', login: 'primary', logout: 'secondary', export: 'warning' }[action] || 'secondary'
}

// Actions
const exportReport = () => {
    toast.info('Export report - implement as needed')
}

const saveCurrentReport = () => {
    const name = prompt('Enter report name:')
    if (!name) return
    toast.info('Save report - implement as needed')
}

const loadSavedReport = (report) => {
    toast.info('Load report: ' + report.name)
}

const deleteSavedReport = async (id) => {
    if (!confirm('Delete this saved report?')) return
    try {
        await axiosEmployee.delete(`/admin/reports/saved/${id}`)
        toast.success('Report deleted')
        fetchSavedReports()
    } catch (error) {
        toast.error('Failed to delete report')
    }
}

const viewAuditDetail = (log) => {
    toast.info('View audit details')
}

// Watchers
watch(dateRange, () => {
    fetchDashboard()
    fetchTopProducts()
    fetchCustomers()
})

watch(auditActionFilter, fetchAuditLogs)

// Lifecycle
onMounted(() => {
    fetchDashboard()
    fetchTopProducts()
    fetchCustomers()
    fetchInventory()
    fetchSavedReports()
    fetchAuditLogs()
})
</script>

<style scoped>
.stat-card {
    background: #fff;
    border-radius: 8px;
    padding: 20px;
    text-align: center;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}
.stat-card h3 {
    font-size: 1.8rem;
    margin-bottom: 5px;
    color: #333;
}
.stat-card h4 {
    font-size: 1.4rem;
    margin-bottom: 5px;
    color: #333;
}
.stat-card p {
    margin: 0;
    color: #666;
}
.stat-card.small {
    padding: 15px;
}
.stat-card.warning h4 {
    color: #ffc107;
}
.stat-card.danger h4 {
    color: #dc3545;
}
.report-section {
    background: #fff;
    border-radius: 8px;
    padding: 20px;
}
.report-section h5 {
    margin-bottom: 20px;
}
.chart-placeholder {
    background: #f8f9fa;
    border-radius: 8px;
    min-height: 200px;
}
.date-select {
    min-width: 180px;
}
.action-select {
    min-width: 200px;
}
</style>
