<template>
    <div class="listing_screen global_table_liting">
        <div class="masterTabs">
            <div class="masterTabContent">
                <!-- Dashboard Stats -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="stat-card">
                            <h3>{{ stats.total_warehouses }}</h3>
                            <p>Total Warehouses</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <h3>{{ stats.pending_transfers }}</h3>
                            <p>Pending Transfers</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <h3>{{ stats.active_counts }}</h3>
                            <p>Active Counts</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card warning" v-if="stats.critical_alerts > 0">
                            <h3>{{ stats.critical_alerts }}</h3>
                            <p>Critical Alerts</p>
                        </div>
                        <div class="stat-card success" v-else>
                            <h3>0</h3>
                            <p>Critical Alerts</p>
                        </div>
                    </div>
                </div>

                <!-- Tabs -->
                <b-tabs v-model="activeTab" content-class="mt-3">
                    <!-- Warehouses Tab -->
                    <b-tab title="Warehouses" active>
                        <div class="listing_tab_and_actions mb-3">
                            <div class="listing_actions">
                                <div class="d-flex">
                                    <div class="listing_search">
                                        <img src="../../assets/img/header/search.svg" class="listing_search_icon" alt="search" />
                                        <b-form-input v-model="warehouseSearch" @input="fetchWarehouses" placeholder="Search warehouses..." />
                                    </div>
                                </div>
                                <div class="buttonGrid">
                                    <b-button class="fillBTN" @click="openWarehouseModal()">Add Warehouse</b-button>
                                </div>
                            </div>
                        </div>

                        <b-table responsive="sm" :items="warehouses" :fields="warehouseFields" v-if="warehouses.length > 0">
                            <template #cell(type)="row">
                                <b-badge :variant="getTypeVariant(row.item.type)">{{ row.item.type }}</b-badge>
                            </template>
                            <template #cell(is_active)="row">
                                <b-badge :variant="row.item.is_active ? 'success' : 'secondary'">
                                    {{ row.item.is_active ? 'Active' : 'Inactive' }}
                                </b-badge>
                            </template>
                            <template #cell(actions)="row">
                                <b-dropdown right text="" variant="link" no-caret toggle-class="p-0">
                                    <b-dropdown-item @click="viewWarehouseStock(row.item)">View Stock</b-dropdown-item>
                                    <b-dropdown-item @click="openWarehouseModal(row.item)">Edit</b-dropdown-item>
                                    <b-dropdown-item @click="deleteWarehouse(row.item.id)">Delete</b-dropdown-item>
                                </b-dropdown>
                            </template>
                        </b-table>

                        <div v-else class="text-center p-5">
                            <p>No warehouses found. Add your first warehouse to get started.</p>
                        </div>
                    </b-tab>

                    <!-- Stock Transfers Tab -->
                    <b-tab title="Stock Transfers">
                        <div class="listing_tab_and_actions mb-3">
                            <div class="listing_actions">
                                <div class="d-flex gap-2">
                                    <v-select v-model="transferStatusFilter" :options="transferStatuses"
                                        placeholder="Filter by status" :clearable="true" class="status-select" />
                                </div>
                                <div class="buttonGrid">
                                    <b-button class="fillBTN" @click="openTransferModal()">New Transfer</b-button>
                                </div>
                            </div>
                        </div>

                        <b-table responsive="sm" :items="transfers" :fields="transferFields" v-if="transfers.length > 0">
                            <template #cell(transfer_number)="row">
                                <a href="#" @click.prevent="viewTransfer(row.item)">{{ row.item.transfer_number }}</a>
                            </template>
                            <template #cell(from_warehouse)="row">
                                {{ row.item.from_warehouse?.name }}
                            </template>
                            <template #cell(to_warehouse)="row">
                                {{ row.item.to_warehouse?.name }}
                            </template>
                            <template #cell(status)="row">
                                <b-badge :variant="getTransferStatusVariant(row.item.status)">{{ row.item.status }}</b-badge>
                            </template>
                            <template #cell(actions)="row">
                                <b-dropdown right text="" variant="link" no-caret toggle-class="p-0">
                                    <b-dropdown-item @click="viewTransfer(row.item)">View Details</b-dropdown-item>
                                    <b-dropdown-item v-if="row.item.status === 'draft'" @click="approveTransfer(row.item.id)">Approve</b-dropdown-item>
                                    <b-dropdown-item v-if="row.item.status === 'pending'" @click="shipTransfer(row.item.id)">Ship</b-dropdown-item>
                                    <b-dropdown-item v-if="row.item.status === 'in_transit'" @click="receiveTransfer(row.item.id)">Receive</b-dropdown-item>
                                    <b-dropdown-item v-if="['draft', 'pending'].includes(row.item.status)" @click="cancelTransfer(row.item.id)">Cancel</b-dropdown-item>
                                </b-dropdown>
                            </template>
                        </b-table>

                        <div v-else class="text-center p-5">
                            <p>No stock transfers found.</p>
                        </div>
                    </b-tab>

                    <!-- Inventory Counts Tab -->
                    <b-tab title="Inventory Counts">
                        <div class="listing_tab_and_actions mb-3">
                            <div class="listing_actions">
                                <div class="d-flex gap-2">
                                    <v-select v-model="countStatusFilter" :options="countStatuses"
                                        placeholder="Filter by status" :clearable="true" class="status-select" />
                                </div>
                                <div class="buttonGrid">
                                    <b-button class="fillBTN" @click="openCountModal()">New Count</b-button>
                                </div>
                            </div>
                        </div>

                        <b-table responsive="sm" :items="counts" :fields="countFields" v-if="counts.length > 0">
                            <template #cell(count_number)="row">
                                <a href="#" @click.prevent="viewCount(row.item)">{{ row.item.count_number }}</a>
                            </template>
                            <template #cell(warehouse)="row">
                                {{ row.item.warehouse?.name }}
                            </template>
                            <template #cell(progress)="row">
                                <b-progress :value="row.item.progress_percent" :max="100" show-progress />
                            </template>
                            <template #cell(status)="row">
                                <b-badge :variant="getCountStatusVariant(row.item.status)">{{ row.item.status }}</b-badge>
                            </template>
                            <template #cell(actions)="row">
                                <b-dropdown right text="" variant="link" no-caret toggle-class="p-0">
                                    <b-dropdown-item @click="viewCount(row.item)">View Details</b-dropdown-item>
                                    <b-dropdown-item v-if="row.item.status === 'draft'" @click="startCount(row.item.id)">Start</b-dropdown-item>
                                    <b-dropdown-item v-if="row.item.status === 'in_progress'" @click="completeCount(row.item.id)">Complete</b-dropdown-item>
                                    <b-dropdown-item v-if="['draft', 'in_progress'].includes(row.item.status)" @click="cancelCount(row.item.id)">Cancel</b-dropdown-item>
                                </b-dropdown>
                            </template>
                        </b-table>

                        <div v-else class="text-center p-5">
                            <p>No inventory counts found.</p>
                        </div>
                    </b-tab>

                    <!-- Alerts Tab -->
                    <b-tab title="Stock Alerts">
                        <div class="listing_tab_and_actions mb-3">
                            <div class="listing_actions">
                                <div class="d-flex gap-2">
                                    <v-select v-model="alertTypeFilter" :options="alertTypes"
                                        placeholder="Filter by type" :clearable="true" class="status-select" />
                                </div>
                                <div class="buttonGrid">
                                    <b-button class="transBTN" @click="acknowledgeAllAlerts" :disabled="!hasActiveAlerts">
                                        Acknowledge All
                                    </b-button>
                                </div>
                            </div>
                        </div>

                        <b-table responsive="sm" :items="alerts" :fields="alertFields" v-if="alerts.length > 0">
                            <template #cell(product)="row">
                                {{ row.item.product?.product_title }}
                            </template>
                            <template #cell(warehouse)="row">
                                {{ row.item.warehouse?.name || 'All' }}
                            </template>
                            <template #cell(alert_type)="row">
                                <b-badge :variant="getAlertTypeVariant(row.item.alert_type)">{{ row.item.alert_label }}</b-badge>
                            </template>
                            <template #cell(status)="row">
                                <b-badge :variant="row.item.status === 'active' ? 'danger' : 'secondary'">{{ row.item.status }}</b-badge>
                            </template>
                            <template #cell(actions)="row">
                                <b-button v-if="row.item.status === 'active'" size="sm" variant="outline-primary" @click="acknowledgeAlert(row.item.id)">
                                    Acknowledge
                                </b-button>
                            </template>
                        </b-table>

                        <div v-else class="text-center p-5">
                            <p>No stock alerts.</p>
                        </div>
                    </b-tab>
                </b-tabs>
            </div>
        </div>

        <!-- Warehouse Modal -->
        <b-modal v-model="showWarehouseModal" :title="editingWarehouse ? 'Edit Warehouse' : 'Add Warehouse'" size="lg" @ok="saveWarehouse" ok-title="Save">
            <b-form @submit.prevent="saveWarehouse">
                <div class="row">
                    <div class="col-md-6">
                        <b-form-group label="Name *" label-for="warehouse-name">
                            <b-form-input id="warehouse-name" v-model="warehouseForm.name" required placeholder="Enter warehouse name" />
                        </b-form-group>
                    </div>
                    <div class="col-md-6">
                        <b-form-group label="Code *" label-for="warehouse-code">
                            <b-form-input id="warehouse-code" v-model="warehouseForm.code" required placeholder="e.g., WH-MUM-001" />
                        </b-form-group>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <b-form-group label="Type *" label-for="warehouse-type">
                            <v-select id="warehouse-type" v-model="warehouseForm.type" :options="warehouseTypes" :reduce="t => t.value" label="label" placeholder="Select type" />
                        </b-form-group>
                    </div>
                    <div class="col-md-6">
                        <b-form-group label="Priority" label-for="warehouse-priority">
                            <b-form-input id="warehouse-priority" v-model="warehouseForm.priority" type="number" min="1" placeholder="1" />
                        </b-form-group>
                    </div>
                </div>
                <b-form-group label="Description" label-for="warehouse-desc">
                    <b-form-textarea id="warehouse-desc" v-model="warehouseForm.description" rows="2" placeholder="Optional description" />
                </b-form-group>
                <hr>
                <h6>Address</h6>
                <div class="row">
                    <div class="col-md-12">
                        <b-form-group label="Address Line 1 *" label-for="address1">
                            <b-form-input id="address1" v-model="warehouseForm.address_line1" required placeholder="Street address" />
                        </b-form-group>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <b-form-group label="Address Line 2" label-for="address2">
                            <b-form-input id="address2" v-model="warehouseForm.address_line2" placeholder="Apartment, suite, etc." />
                        </b-form-group>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <b-form-group label="City *" label-for="city">
                            <b-form-input id="city" v-model="warehouseForm.city" required placeholder="City" />
                        </b-form-group>
                    </div>
                    <div class="col-md-6">
                        <b-form-group label="State *" label-for="state">
                            <b-form-input id="state" v-model="warehouseForm.state" required placeholder="State" />
                        </b-form-group>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <b-form-group label="Pincode *" label-for="pincode">
                            <b-form-input id="pincode" v-model="warehouseForm.pincode" required placeholder="PIN Code" />
                        </b-form-group>
                    </div>
                    <div class="col-md-6">
                        <b-form-group label="Country" label-for="country">
                            <b-form-input id="country" v-model="warehouseForm.country" placeholder="IN" />
                        </b-form-group>
                    </div>
                </div>
                <hr>
                <h6>Contact</h6>
                <div class="row">
                    <div class="col-md-6">
                        <b-form-group label="Phone" label-for="phone">
                            <b-form-input id="phone" v-model="warehouseForm.phone" placeholder="+91-XX-XXXXXXXX" />
                        </b-form-group>
                    </div>
                    <div class="col-md-6">
                        <b-form-group label="Email" label-for="email">
                            <b-form-input id="email" v-model="warehouseForm.email" type="email" placeholder="warehouse@example.com" />
                        </b-form-group>
                    </div>
                </div>
                <hr>
                <h6>Settings</h6>
                <div class="row">
                    <div class="col-md-4">
                        <b-form-checkbox v-model="warehouseForm.is_active">Active</b-form-checkbox>
                    </div>
                    <div class="col-md-4">
                        <b-form-checkbox v-model="warehouseForm.is_default">Default Warehouse</b-form-checkbox>
                    </div>
                    <div class="col-md-4">
                        <b-form-checkbox v-model="warehouseForm.accepts_returns">Accepts Returns</b-form-checkbox>
                    </div>
                </div>
                <div class="row mt-2">
                    <div class="col-md-4">
                        <b-form-checkbox v-model="warehouseForm.allows_pickup">Allows Pickup</b-form-checkbox>
                    </div>
                </div>
            </b-form>
        </b-modal>

        <!-- Stock Transfer Modal -->
        <b-modal v-model="showTransferModal" title="New Stock Transfer" size="lg" @ok="saveTransfer" ok-title="Create Transfer">
            <b-form @submit.prevent="saveTransfer">
                <div class="row">
                    <div class="col-md-6">
                        <b-form-group label="From Warehouse *" label-for="from-warehouse">
                            <v-select id="from-warehouse" v-model="transferForm.from_warehouse_id" :options="warehouses" :reduce="w => w.id" label="name" placeholder="Select source warehouse" />
                        </b-form-group>
                    </div>
                    <div class="col-md-6">
                        <b-form-group label="To Warehouse *" label-for="to-warehouse">
                            <v-select id="to-warehouse" v-model="transferForm.to_warehouse_id" :options="warehouses" :reduce="w => w.id" label="name" placeholder="Select destination warehouse" />
                        </b-form-group>
                    </div>
                </div>
                <b-form-group label="Notes" label-for="transfer-notes">
                    <b-form-textarea id="transfer-notes" v-model="transferForm.notes" rows="2" placeholder="Optional notes" />
                </b-form-group>
                <hr>
                <h6>Items to Transfer</h6>
                <div v-for="(item, index) in transferForm.items" :key="index" class="row align-items-end mb-2">
                    <div class="col-md-6">
                        <b-form-group label="Product" :label-for="'product-' + index">
                            <b-form-input :id="'product-' + index" v-model="item.product_id" type="number" placeholder="Product ID" />
                        </b-form-group>
                    </div>
                    <div class="col-md-4">
                        <b-form-group label="Quantity" :label-for="'qty-' + index">
                            <b-form-input :id="'qty-' + index" v-model="item.quantity" type="number" min="1" placeholder="Qty" />
                        </b-form-group>
                    </div>
                    <div class="col-md-2">
                        <b-button variant="outline-danger" size="sm" @click="removeTransferItem(index)" v-if="transferForm.items.length > 1">Remove</b-button>
                    </div>
                </div>
                <b-button variant="outline-primary" size="sm" @click="addTransferItem">+ Add Item</b-button>
            </b-form>
        </b-modal>

        <!-- Inventory Count Modal -->
        <b-modal v-model="showCountModal" title="New Inventory Count" size="md" @ok="saveCount" ok-title="Create Count">
            <b-form @submit.prevent="saveCount">
                <b-form-group label="Warehouse *" label-for="count-warehouse">
                    <v-select id="count-warehouse" v-model="countForm.warehouse_id" :options="warehouses" :reduce="w => w.id" label="name" placeholder="Select warehouse" />
                </b-form-group>
                <b-form-group label="Count Type *" label-for="count-type">
                    <v-select id="count-type" v-model="countForm.type" :options="countTypeOptions" :reduce="t => t.value" label="label" placeholder="Select type" />
                </b-form-group>
                <b-form-group label="Notes" label-for="count-notes">
                    <b-form-textarea id="count-notes" v-model="countForm.notes" rows="2" placeholder="Optional notes" />
                </b-form-group>
            </b-form>
        </b-modal>

        <!-- Stock View Modal -->
        <b-modal v-model="showStockModal" :title="'Stock - ' + (selectedWarehouse?.name || '')" size="xl" hide-footer>
            <div class="mb-3">
                <b-form-input v-model="stockSearch" @input="fetchWarehouseStock" placeholder="Search products..." />
            </div>
            <b-table responsive="sm" :items="warehouseStock" :fields="stockFields" v-if="warehouseStock.length > 0">
                <template #cell(product)="row">
                    {{ row.item.product?.name || 'N/A' }}
                </template>
                <template #cell(status)="row">
                    <b-badge :variant="getStockStatusVariant(row.item.quantity, row.item.low_stock_threshold)">
                        {{ getStockStatus(row.item.quantity, row.item.low_stock_threshold) }}
                    </b-badge>
                </template>
            </b-table>
            <div v-else class="text-center p-3">
                <p>No stock records found.</p>
            </div>
        </b-modal>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import axiosEmployee from '@axiosEmployee'
import { toast } from 'vue3-toastify'

// State
const activeTab = ref(0)
const stats = ref({
    total_warehouses: 0,
    pending_transfers: 0,
    active_counts: 0,
    critical_alerts: 0
})

// Warehouses
const warehouses = ref([])
const warehouseSearch = ref('')
const warehouseFields = [
    { key: 'name', label: 'Name', sortable: true },
    { key: 'code', label: 'Code' },
    { key: 'type', label: 'Type' },
    { key: 'city', label: 'City' },
    { key: 'is_active', label: 'Status' },
    { key: 'actions', label: 'Actions' }
]

// Transfers
const transfers = ref([])
const transferStatusFilter = ref(null)
const transferStatuses = ['draft', 'pending', 'in_transit', 'received', 'cancelled']
const transferFields = [
    { key: 'transfer_number', label: 'Transfer #' },
    { key: 'from_warehouse', label: 'From' },
    { key: 'to_warehouse', label: 'To' },
    { key: 'status', label: 'Status' },
    { key: 'created_at', label: 'Date' },
    { key: 'actions', label: 'Actions' }
]

// Counts
const counts = ref([])
const countStatusFilter = ref(null)
const countStatuses = ['draft', 'in_progress', 'completed', 'cancelled']
const countFields = [
    { key: 'count_number', label: 'Count #' },
    { key: 'warehouse', label: 'Warehouse' },
    { key: 'type', label: 'Type' },
    { key: 'progress', label: 'Progress' },
    { key: 'status', label: 'Status' },
    { key: 'actions', label: 'Actions' }
]

// Alerts
const alerts = ref([])
const alertTypeFilter = ref(null)
const alertTypes = ['low_stock', 'out_of_stock', 'overstock', 'expiring']
const alertFields = [
    { key: 'product', label: 'Product' },
    { key: 'warehouse', label: 'Warehouse' },
    { key: 'alert_type', label: 'Type' },
    { key: 'current_quantity', label: 'Qty' },
    { key: 'status', label: 'Status' },
    { key: 'actions', label: 'Actions' }
]

const hasActiveAlerts = computed(() => alerts.value.some(a => a.status === 'active'))

// Modal State
const showWarehouseModal = ref(false)
const showTransferModal = ref(false)
const showCountModal = ref(false)
const showStockModal = ref(false)
const editingWarehouse = ref(null)
const selectedWarehouse = ref(null)
const warehouseStock = ref([])
const stockSearch = ref('')

// Form Data
const warehouseForm = ref({
    name: '',
    code: '',
    type: 'warehouse',
    description: '',
    address_line1: '',
    address_line2: '',
    city: '',
    state: '',
    pincode: '',
    country: 'IN',
    phone: '',
    email: '',
    is_active: true,
    is_default: false,
    accepts_returns: true,
    allows_pickup: false,
    priority: 1
})

const transferForm = ref({
    from_warehouse_id: null,
    to_warehouse_id: null,
    notes: '',
    items: [{ product_id: '', quantity: 1 }]
})

const countForm = ref({
    warehouse_id: null,
    type: 'full',
    notes: ''
})

// Options
const warehouseTypes = [
    { value: 'warehouse', label: 'Warehouse' },
    { value: 'store', label: 'Store' },
    { value: 'fulfillment_center', label: 'Fulfillment Center' }
]

const countTypeOptions = [
    { value: 'full', label: 'Full Count' },
    { value: 'cycle', label: 'Cycle Count' },
    { value: 'spot', label: 'Spot Check' }
]

const stockFields = [
    { key: 'product', label: 'Product' },
    { key: 'sku', label: 'SKU' },
    { key: 'quantity', label: 'Qty' },
    { key: 'reserved_quantity', label: 'Reserved' },
    { key: 'available_quantity', label: 'Available' },
    { key: 'status', label: 'Status' }
]

// Fetch functions
const fetchDashboard = async () => {
    try {
        const response = await axiosEmployee.get('/admin/warehouse/dashboard')
        if (response.data.success) {
            stats.value = response.data.data.stats
        }
    } catch (error) {
        console.error('Error fetching dashboard:', error)
    }
}

const fetchWarehouses = async () => {
    try {
        const response = await axiosEmployee.get('/admin/warehouse/', {
            params: { search: warehouseSearch.value }
        })
        if (response.data.success) {
            warehouses.value = response.data.data.data || []
        }
    } catch (error) {
        console.error('Error fetching warehouses:', error)
    }
}

const fetchTransfers = async () => {
    try {
        const response = await axiosEmployee.get('/admin/transfers/', {
            params: { status: transferStatusFilter.value }
        })
        if (response.data.success) {
            transfers.value = response.data.data.data || []
        }
    } catch (error) {
        console.error('Error fetching transfers:', error)
    }
}

const fetchCounts = async () => {
    try {
        const response = await axiosEmployee.get('/admin/inventory-counts/', {
            params: { status: countStatusFilter.value }
        })
        if (response.data.success) {
            counts.value = response.data.data.data || []
        }
    } catch (error) {
        console.error('Error fetching counts:', error)
    }
}

const fetchAlerts = async () => {
    try {
        const response = await axiosEmployee.get('/admin/stock-alerts/', {
            params: { alert_type: alertTypeFilter.value }
        })
        if (response.data.success) {
            alerts.value = response.data.data.data || []
        }
    } catch (error) {
        console.error('Error fetching alerts:', error)
    }
}

// Helper functions
const getTypeVariant = (type) => {
    return { warehouse: 'primary', store: 'info', fulfillment_center: 'success' }[type] || 'secondary'
}

const getTransferStatusVariant = (status) => {
    return { draft: 'secondary', pending: 'warning', in_transit: 'info', received: 'success', cancelled: 'danger' }[status] || 'secondary'
}

const getCountStatusVariant = (status) => {
    return { draft: 'secondary', in_progress: 'warning', completed: 'success', cancelled: 'danger' }[status] || 'secondary'
}

const getAlertTypeVariant = (type) => {
    return { out_of_stock: 'danger', low_stock: 'warning', overstock: 'info', expiring: 'warning' }[type] || 'secondary'
}

// Actions
const resetWarehouseForm = () => {
    warehouseForm.value = {
        name: '',
        code: '',
        type: 'warehouse',
        description: '',
        address_line1: '',
        address_line2: '',
        city: '',
        state: '',
        pincode: '',
        country: 'IN',
        phone: '',
        email: '',
        is_active: true,
        is_default: false,
        accepts_returns: true,
        allows_pickup: false,
        priority: 1
    }
}

const openWarehouseModal = (warehouse = null) => {
    if (warehouse) {
        editingWarehouse.value = warehouse
        warehouseForm.value = { ...warehouse }
    } else {
        editingWarehouse.value = null
        resetWarehouseForm()
    }
    showWarehouseModal.value = true
}

const saveWarehouse = async () => {
    try {
        if (editingWarehouse.value) {
            await axiosEmployee.put(`/admin/warehouse/${editingWarehouse.value.id}`, warehouseForm.value)
            toast.success('Warehouse updated successfully')
        } else {
            await axiosEmployee.post('/admin/warehouse', warehouseForm.value)
            toast.success('Warehouse created successfully')
        }
        showWarehouseModal.value = false
        fetchWarehouses()
        fetchDashboard()
    } catch (error) {
        toast.error(error.response?.data?.message || 'Failed to save warehouse')
    }
}

const viewWarehouseStock = async (warehouse) => {
    selectedWarehouse.value = warehouse
    stockSearch.value = ''
    showStockModal.value = true
    await fetchWarehouseStock()
}

const fetchWarehouseStock = async () => {
    if (!selectedWarehouse.value) return
    try {
        const response = await axiosEmployee.get(`/admin/warehouse/${selectedWarehouse.value.id}/stock`, {
            params: { search: stockSearch.value }
        })
        if (response.data.success) {
            warehouseStock.value = response.data.data.data || response.data.data || []
        }
    } catch (error) {
        console.error('Error fetching stock:', error)
        warehouseStock.value = []
    }
}

const getStockStatus = (qty, threshold) => {
    if (qty === 0) return 'Out of Stock'
    if (qty <= threshold) return 'Low Stock'
    return 'In Stock'
}

const getStockStatusVariant = (qty, threshold) => {
    if (qty === 0) return 'danger'
    if (qty <= threshold) return 'warning'
    return 'success'
}

const deleteWarehouse = async (id) => {
    if (!confirm('Are you sure you want to delete this warehouse?')) return
    try {
        await axiosEmployee.delete(`/admin/warehouse/${id}`)
        toast.success('Warehouse deleted')
        fetchWarehouses()
        fetchDashboard()
    } catch (error) {
        toast.error(error.response?.data?.message || 'Failed to delete')
    }
}

const openTransferModal = () => {
    transferForm.value = {
        from_warehouse_id: null,
        to_warehouse_id: null,
        notes: '',
        items: [{ product_id: '', quantity: 1 }]
    }
    showTransferModal.value = true
}

const saveTransfer = async () => {
    try {
        await axiosEmployee.post('/admin/transfers', transferForm.value)
        toast.success('Transfer created successfully')
        showTransferModal.value = false
        fetchTransfers()
        fetchDashboard()
    } catch (error) {
        toast.error(error.response?.data?.message || 'Failed to create transfer')
    }
}

const addTransferItem = () => {
    transferForm.value.items.push({ product_id: '', quantity: 1 })
}

const removeTransferItem = (index) => {
    transferForm.value.items.splice(index, 1)
}

const viewTransfer = (transfer) => {
    toast.info('View transfer ' + transfer.transfer_number)
}

const approveTransfer = async (id) => {
    try {
        await axiosEmployee.post(`/admin/transfers/${id}/approve`)
        toast.success('Transfer approved')
        fetchTransfers()
    } catch (error) {
        toast.error(error.response?.data?.message || 'Failed to approve')
    }
}

const shipTransfer = async (id) => {
    try {
        await axiosEmployee.post(`/admin/transfers/${id}/ship`)
        toast.success('Transfer shipped')
        fetchTransfers()
    } catch (error) {
        toast.error(error.response?.data?.message || 'Failed to ship')
    }
}

const receiveTransfer = async (id) => {
    try {
        await axiosEmployee.post(`/admin/transfers/${id}/receive`)
        toast.success('Transfer received')
        fetchTransfers()
        fetchDashboard()
    } catch (error) {
        toast.error(error.response?.data?.message || 'Failed to receive')
    }
}

const cancelTransfer = async (id) => {
    if (!confirm('Are you sure you want to cancel this transfer?')) return
    try {
        await axiosEmployee.post(`/admin/transfers/${id}/cancel`)
        toast.success('Transfer cancelled')
        fetchTransfers()
        fetchDashboard()
    } catch (error) {
        toast.error(error.response?.data?.message || 'Failed to cancel')
    }
}

const openCountModal = () => {
    countForm.value = {
        warehouse_id: null,
        type: 'full',
        notes: ''
    }
    showCountModal.value = true
}

const saveCount = async () => {
    try {
        await axiosEmployee.post('/admin/inventory-counts', countForm.value)
        toast.success('Inventory count created successfully')
        showCountModal.value = false
        fetchCounts()
        fetchDashboard()
    } catch (error) {
        toast.error(error.response?.data?.message || 'Failed to create count')
    }
}

const viewCount = (count) => {
    toast.info('View count ' + count.count_number)
}

const startCount = async (id) => {
    try {
        await axiosEmployee.post(`/admin/inventory-counts/${id}/start`)
        toast.success('Count started')
        fetchCounts()
        fetchDashboard()
    } catch (error) {
        toast.error(error.response?.data?.message || 'Failed to start')
    }
}

const completeCount = async (id) => {
    try {
        await axiosEmployee.post(`/admin/inventory-counts/${id}/complete`)
        toast.success('Count completed')
        fetchCounts()
        fetchDashboard()
    } catch (error) {
        toast.error(error.response?.data?.message || 'Failed to complete')
    }
}

const cancelCount = async (id) => {
    if (!confirm('Are you sure you want to cancel this count?')) return
    try {
        await axiosEmployee.post(`/admin/inventory-counts/${id}/cancel`)
        toast.success('Count cancelled')
        fetchCounts()
        fetchDashboard()
    } catch (error) {
        toast.error(error.response?.data?.message || 'Failed to cancel')
    }
}

const acknowledgeAlert = async (id) => {
    try {
        await axiosEmployee.post(`/admin/stock-alerts/${id}/acknowledge`)
        toast.success('Alert acknowledged')
        fetchAlerts()
        fetchDashboard()
    } catch (error) {
        toast.error(error.response?.data?.message || 'Failed to acknowledge')
    }
}

const acknowledgeAllAlerts = async () => {
    const activeIds = alerts.value.filter(a => a.status === 'active').map(a => a.id)
    if (activeIds.length === 0) return
    try {
        await axiosEmployee.post('/admin/stock-alerts/bulk-acknowledge', { alert_ids: activeIds })
        toast.success('All alerts acknowledged')
        fetchAlerts()
        fetchDashboard()
    } catch (error) {
        toast.error(error.response?.data?.message || 'Failed to acknowledge')
    }
}

// Watchers
watch(transferStatusFilter, fetchTransfers)
watch(countStatusFilter, fetchCounts)
watch(alertTypeFilter, fetchAlerts)

// Lifecycle
onMounted(() => {
    fetchDashboard()
    fetchWarehouses()
    fetchTransfers()
    fetchCounts()
    fetchAlerts()
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
    font-size: 2rem;
    margin-bottom: 5px;
    color: #333;
}
.stat-card p {
    margin: 0;
    color: #666;
}
.stat-card.warning h3 {
    color: #dc3545;
}
.stat-card.success h3 {
    color: #28a745;
}
.status-select {
    min-width: 200px;
}
</style>
