<template>
    <div class="listing_screen global_table_liting">
        <div class="masterTabs">
            <div class="masterTabContent">
                <!-- Dashboard Stats -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="stat-card">
                            <h3>{{ stats.active_zones }}</h3>
                            <p>Active Tax Zones</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <h3>{{ stats.active_rules }}</h3>
                            <p>Active Tax Rules</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <h3>{{ stats.pending_exemptions }}</h3>
                            <p>Pending Exemptions</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <h3>{{ stats.hsn_codes }}</h3>
                            <p>HSN Codes</p>
                        </div>
                    </div>
                </div>

                <!-- Tabs -->
                <b-tabs v-model="activeTab" content-class="mt-3">
                    <!-- Tax Zones Tab -->
                    <b-tab title="Tax Zones" active>
                        <div class="listing_tab_and_actions mb-3">
                            <div class="listing_actions">
                                <div class="d-flex">
                                    <div class="listing_search">
                                        <b-form-input v-model="zoneSearch" @input="fetchZones" placeholder="Search zones..." />
                                    </div>
                                </div>
                                <div class="buttonGrid">
                                    <b-button class="fillBTN" @click="openZoneModal()">Add Zone</b-button>
                                </div>
                            </div>
                        </div>

                        <b-table responsive="sm" :items="zones" :fields="zoneFields" v-if="zones.length > 0">
                            <template #cell(is_default)="row">
                                <b-badge v-if="row.item.is_default" variant="primary">Default</b-badge>
                            </template>
                            <template #cell(is_active)="row">
                                <b-badge :variant="row.item.is_active ? 'success' : 'secondary'">
                                    {{ row.item.is_active ? 'Active' : 'Inactive' }}
                                </b-badge>
                            </template>
                            <template #cell(actions)="row">
                                <b-dropdown right text="" variant="link" no-caret toggle-class="p-0">
                                    <b-dropdown-item @click="openZoneModal(row.item)">Edit</b-dropdown-item>
                                    <b-dropdown-item @click="deleteZone(row.item.id)">Delete</b-dropdown-item>
                                </b-dropdown>
                            </template>
                        </b-table>

                        <div v-else class="text-center p-5">
                            <p>No tax zones found. Add your first zone to get started.</p>
                        </div>
                    </b-tab>

                    <!-- Tax Rules Tab -->
                    <b-tab title="Tax Rules">
                        <div class="listing_tab_and_actions mb-3">
                            <div class="listing_actions">
                                <div class="d-flex gap-2">
                                    <v-select v-model="ruleTypeFilter" :options="taxTypes"
                                        placeholder="Filter by type" :clearable="true" class="type-select" />
                                </div>
                                <div class="buttonGrid">
                                    <b-button class="fillBTN" @click="openRuleModal()">Add Rule</b-button>
                                </div>
                            </div>
                        </div>

                        <b-table responsive="sm" :items="rules" :fields="ruleFields" v-if="rules.length > 0">
                            <template #cell(tax_type)="row">
                                <b-badge :variant="getTaxTypeVariant(row.item.tax_type)">{{ row.item.tax_type.toUpperCase() }}</b-badge>
                            </template>
                            <template #cell(rate)="row">
                                {{ row.item.rate }}%
                            </template>
                            <template #cell(apply_to)="row">
                                {{ row.item.apply_to }}
                            </template>
                            <template #cell(is_active)="row">
                                <b-badge :variant="row.item.is_active ? 'success' : 'secondary'">
                                    {{ row.item.is_active ? 'Active' : 'Inactive' }}
                                </b-badge>
                            </template>
                            <template #cell(actions)="row">
                                <b-dropdown right text="" variant="link" no-caret toggle-class="p-0">
                                    <b-dropdown-item @click="viewRuleHistory(row.item)">Rate History</b-dropdown-item>
                                    <b-dropdown-item @click="openRuleModal(row.item)">Edit</b-dropdown-item>
                                    <b-dropdown-item @click="deleteRule(row.item.id)">Delete</b-dropdown-item>
                                </b-dropdown>
                            </template>
                        </b-table>

                        <div v-else class="text-center p-5">
                            <p>No tax rules found.</p>
                        </div>
                    </b-tab>

                    <!-- Tax Exemptions Tab -->
                    <b-tab title="Exemptions">
                        <div class="listing_tab_and_actions mb-3">
                            <div class="listing_actions">
                                <div class="d-flex gap-2">
                                    <v-select v-model="exemptionStatusFilter" :options="exemptionStatuses"
                                        placeholder="Filter by status" :clearable="true" class="status-select" />
                                </div>
                                <div class="buttonGrid">
                                    <b-button class="fillBTN" @click="openExemptionModal()">Add Exemption</b-button>
                                </div>
                            </div>
                        </div>

                        <b-table responsive="sm" :items="exemptions" :fields="exemptionFields" v-if="exemptions.length > 0">
                            <template #cell(exemption_type)="row">
                                <b-badge variant="info">{{ row.item.exemption_type }}</b-badge>
                            </template>
                            <template #cell(customer)="row">
                                {{ row.item.customer?.name || '-' }}
                            </template>
                            <template #cell(status)="row">
                                <b-badge :variant="getExemptionStatusVariant(row.item.status)">{{ row.item.status }}</b-badge>
                            </template>
                            <template #cell(actions)="row">
                                <b-dropdown right text="" variant="link" no-caret toggle-class="p-0">
                                    <b-dropdown-item @click="viewExemption(row.item)">View Details</b-dropdown-item>
                                    <b-dropdown-item v-if="row.item.status === 'pending'" @click="approveExemption(row.item.id)">Approve</b-dropdown-item>
                                    <b-dropdown-item v-if="row.item.status === 'pending'" @click="rejectExemption(row.item.id)">Reject</b-dropdown-item>
                                    <b-dropdown-item @click="deleteExemption(row.item.id)">Delete</b-dropdown-item>
                                </b-dropdown>
                            </template>
                        </b-table>

                        <div v-else class="text-center p-5">
                            <p>No tax exemptions found.</p>
                        </div>
                    </b-tab>

                    <!-- HSN Codes Tab -->
                    <b-tab title="HSN Codes">
                        <div class="listing_tab_and_actions mb-3">
                            <div class="listing_actions">
                                <div class="d-flex">
                                    <div class="listing_search">
                                        <b-form-input v-model="hsnSearch" @input="fetchHsnCodes" placeholder="Search HSN codes..." />
                                    </div>
                                </div>
                                <div class="buttonGrid">
                                    <b-button class="transBTN" @click="importHsnCodes">Import</b-button>
                                    <b-button class="fillBTN" @click="openHsnModal()">Add HSN Code</b-button>
                                </div>
                            </div>
                        </div>

                        <b-table responsive="sm" :items="hsnCodes" :fields="hsnFields" v-if="hsnCodes.length > 0">
                            <template #cell(code)="row">
                                <strong>{{ row.item.code }}</strong>
                            </template>
                            <template #cell(gst_rate)="row">
                                {{ row.item.gst_rate }}%
                            </template>
                            <template #cell(type)="row">
                                <b-badge :variant="row.item.type === 'goods' ? 'primary' : 'info'">{{ row.item.type }}</b-badge>
                            </template>
                            <template #cell(is_active)="row">
                                <b-badge :variant="row.item.is_active ? 'success' : 'secondary'">
                                    {{ row.item.is_active ? 'Active' : 'Inactive' }}
                                </b-badge>
                            </template>
                            <template #cell(actions)="row">
                                <b-dropdown right text="" variant="link" no-caret toggle-class="p-0">
                                    <b-dropdown-item @click="openHsnModal(row.item)">Edit</b-dropdown-item>
                                    <b-dropdown-item @click="deleteHsnCode(row.item.id)">Delete</b-dropdown-item>
                                </b-dropdown>
                            </template>
                        </b-table>

                        <div v-else class="text-center p-5">
                            <p>No HSN codes found. Add HSN codes for GST compliance.</p>
                        </div>
                    </b-tab>
                </b-tabs>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import axiosEmployee from '@axiosEmployee'
import { toast } from 'vue3-toastify'

// State
const activeTab = ref(0)
const stats = ref({
    active_zones: 0,
    active_rules: 0,
    pending_exemptions: 0,
    hsn_codes: 0
})

// Zones
const zones = ref([])
const zoneSearch = ref('')
const zoneFields = [
    { key: 'name', label: 'Name', sortable: true },
    { key: 'code', label: 'Code' },
    { key: 'priority', label: 'Priority' },
    { key: 'is_default', label: 'Default' },
    { key: 'is_active', label: 'Status' },
    { key: 'actions', label: 'Actions' }
]

// Rules
const rules = ref([])
const ruleTypeFilter = ref(null)
const taxTypes = ['gst', 'igst', 'cgst_sgst', 'vat', 'custom']
const ruleFields = [
    { key: 'name', label: 'Name', sortable: true },
    { key: 'code', label: 'Code' },
    { key: 'tax_type', label: 'Type' },
    { key: 'rate', label: 'Rate' },
    { key: 'apply_to', label: 'Applies To' },
    { key: 'is_active', label: 'Status' },
    { key: 'actions', label: 'Actions' }
]

// Exemptions
const exemptions = ref([])
const exemptionStatusFilter = ref(null)
const exemptionStatuses = ['pending', 'approved', 'rejected', 'expired']
const exemptionFields = [
    { key: 'certificate_number', label: 'Certificate #' },
    { key: 'exemption_type', label: 'Type' },
    { key: 'customer', label: 'Customer' },
    { key: 'reason', label: 'Reason' },
    { key: 'valid_from', label: 'Valid From' },
    { key: 'status', label: 'Status' },
    { key: 'actions', label: 'Actions' }
]

// HSN Codes
const hsnCodes = ref([])
const hsnSearch = ref('')
const hsnFields = [
    { key: 'code', label: 'HSN Code' },
    { key: 'description', label: 'Description' },
    { key: 'gst_rate', label: 'GST Rate' },
    { key: 'type', label: 'Type' },
    { key: 'is_active', label: 'Status' },
    { key: 'actions', label: 'Actions' }
]

// Fetch functions
const fetchDashboard = async () => {
    try {
        const response = await axiosEmployee.get('/admin/tax/dashboard')
        if (response.data.success) {
            stats.value = response.data.data.stats
        }
    } catch (error) {
        console.error('Error fetching dashboard:', error)
    }
}

const fetchZones = async () => {
    try {
        const response = await axiosEmployee.get('/admin/tax/zones')
        if (response.data.success) {
            zones.value = response.data.data.data || []
        }
    } catch (error) {
        console.error('Error fetching zones:', error)
    }
}

const fetchRules = async () => {
    try {
        const response = await axiosEmployee.get('/admin/tax/rules', {
            params: { tax_type: ruleTypeFilter.value }
        })
        if (response.data.success) {
            rules.value = response.data.data.data || []
        }
    } catch (error) {
        console.error('Error fetching rules:', error)
    }
}

const fetchExemptions = async () => {
    try {
        const response = await axiosEmployee.get('/admin/tax/exemptions', {
            params: { status: exemptionStatusFilter.value }
        })
        if (response.data.success) {
            exemptions.value = response.data.data.data || []
        }
    } catch (error) {
        console.error('Error fetching exemptions:', error)
    }
}

const fetchHsnCodes = async () => {
    try {
        const response = await axiosEmployee.get('/admin/tax/hsn', {
            params: { search: hsnSearch.value }
        })
        if (response.data.success) {
            hsnCodes.value = response.data.data.data || []
        }
    } catch (error) {
        console.error('Error fetching HSN codes:', error)
    }
}

// Helper functions
const getTaxTypeVariant = (type) => {
    return { gst: 'success', igst: 'primary', cgst_sgst: 'info', vat: 'warning', custom: 'secondary' }[type] || 'secondary'
}

const getExemptionStatusVariant = (status) => {
    return { pending: 'warning', approved: 'success', rejected: 'danger', expired: 'secondary' }[status] || 'secondary'
}

// Actions
const openZoneModal = (zone = null) => {
    toast.info('Zone modal - implement as needed')
}

const deleteZone = async (id) => {
    if (!confirm('Are you sure you want to delete this zone?')) return
    try {
        await axiosEmployee.delete(`/admin/tax/zones/${id}`)
        toast.success('Zone deleted')
        fetchZones()
        fetchDashboard()
    } catch (error) {
        toast.error(error.response?.data?.message || 'Failed to delete')
    }
}

const openRuleModal = (rule = null) => {
    toast.info('Rule modal - implement as needed')
}

const viewRuleHistory = async (rule) => {
    toast.info('View history for ' + rule.name)
}

const deleteRule = async (id) => {
    if (!confirm('Are you sure you want to delete this rule?')) return
    try {
        await axiosEmployee.delete(`/admin/tax/rules/${id}`)
        toast.success('Rule deleted')
        fetchRules()
        fetchDashboard()
    } catch (error) {
        toast.error(error.response?.data?.message || 'Failed to delete')
    }
}

const openExemptionModal = (exemption = null) => {
    toast.info('Exemption modal - implement as needed')
}

const viewExemption = (exemption) => {
    toast.info('View exemption details')
}

const approveExemption = async (id) => {
    try {
        await axiosEmployee.post(`/admin/tax/exemptions/${id}/approve`)
        toast.success('Exemption approved')
        fetchExemptions()
        fetchDashboard()
    } catch (error) {
        toast.error(error.response?.data?.message || 'Failed to approve')
    }
}

const rejectExemption = async (id) => {
    const reason = prompt('Enter rejection reason:')
    if (!reason) return
    try {
        await axiosEmployee.post(`/admin/tax/exemptions/${id}/reject`, { reason })
        toast.success('Exemption rejected')
        fetchExemptions()
    } catch (error) {
        toast.error(error.response?.data?.message || 'Failed to reject')
    }
}

const deleteExemption = async (id) => {
    if (!confirm('Are you sure you want to delete this exemption?')) return
    try {
        await axiosEmployee.delete(`/admin/tax/exemptions/${id}`)
        toast.success('Exemption deleted')
        fetchExemptions()
    } catch (error) {
        toast.error(error.response?.data?.message || 'Failed to delete')
    }
}

const openHsnModal = (hsn = null) => {
    toast.info('HSN modal - implement as needed')
}

const importHsnCodes = () => {
    toast.info('Import HSN codes - implement as needed')
}

const deleteHsnCode = async (id) => {
    if (!confirm('Are you sure you want to delete this HSN code?')) return
    try {
        await axiosEmployee.delete(`/admin/tax/hsn/${id}`)
        toast.success('HSN code deleted')
        fetchHsnCodes()
        fetchDashboard()
    } catch (error) {
        toast.error(error.response?.data?.message || 'Failed to delete')
    }
}

// Watchers
watch(ruleTypeFilter, fetchRules)
watch(exemptionStatusFilter, fetchExemptions)

// Lifecycle
onMounted(() => {
    fetchDashboard()
    fetchZones()
    fetchRules()
    fetchExemptions()
    fetchHsnCodes()
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
.type-select, .status-select {
    min-width: 200px;
}
</style>
