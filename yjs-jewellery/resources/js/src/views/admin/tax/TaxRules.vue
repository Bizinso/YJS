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

        <!-- Zone Modal -->
        <b-modal v-model="showZoneModal" :title="editingZone ? 'Edit Tax Zone' : 'Add Tax Zone'" size="lg" @ok="saveZone" ok-title="Save">
            <b-form @submit.prevent="saveZone">
                <div class="row">
                    <div class="col-md-6">
                        <b-form-group label="Name *" label-for="zone-name">
                            <b-form-input id="zone-name" v-model="zoneForm.name" required placeholder="e.g., Maharashtra" />
                        </b-form-group>
                    </div>
                    <div class="col-md-6">
                        <b-form-group label="Code *" label-for="zone-code">
                            <b-form-input id="zone-code" v-model="zoneForm.code" required placeholder="e.g., MH" />
                        </b-form-group>
                    </div>
                </div>
                <b-form-group label="Description" label-for="zone-desc">
                    <b-form-textarea id="zone-desc" v-model="zoneForm.description" rows="2" placeholder="Optional description" />
                </b-form-group>
                <div class="row">
                    <div class="col-md-6">
                        <b-form-group label="Countries (comma-separated)" label-for="zone-countries">
                            <b-form-input id="zone-countries" v-model="zoneForm.countries_text" placeholder="IN, US" />
                        </b-form-group>
                    </div>
                    <div class="col-md-6">
                        <b-form-group label="States (comma-separated)" label-for="zone-states">
                            <b-form-input id="zone-states" v-model="zoneForm.states_text" placeholder="MH, GJ, KA" />
                        </b-form-group>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <b-form-group label="Priority" label-for="zone-priority">
                            <b-form-input id="zone-priority" v-model="zoneForm.priority" type="number" min="1" placeholder="1" />
                        </b-form-group>
                    </div>
                </div>
                <div class="row mt-2">
                    <div class="col-md-4">
                        <b-form-checkbox v-model="zoneForm.is_active">Active</b-form-checkbox>
                    </div>
                    <div class="col-md-4">
                        <b-form-checkbox v-model="zoneForm.is_default">Default Zone</b-form-checkbox>
                    </div>
                </div>
            </b-form>
        </b-modal>

        <!-- Rule Modal -->
        <b-modal v-model="showRuleModal" :title="editingRule ? 'Edit Tax Rule' : 'Add Tax Rule'" size="lg" @ok="saveRule" ok-title="Save">
            <b-form @submit.prevent="saveRule">
                <div class="row">
                    <div class="col-md-6">
                        <b-form-group label="Name *" label-for="rule-name">
                            <b-form-input id="rule-name" v-model="ruleForm.name" required placeholder="e.g., GST 3% - Gold" />
                        </b-form-group>
                    </div>
                    <div class="col-md-6">
                        <b-form-group label="Code *" label-for="rule-code">
                            <b-form-input id="rule-code" v-model="ruleForm.code" required placeholder="e.g., GST-GOLD-3" />
                        </b-form-group>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <b-form-group label="Tax Zone *" label-for="rule-zone">
                            <v-select id="rule-zone" v-model="ruleForm.tax_zone_id" :options="zones" :reduce="z => z.id" label="name" placeholder="Select zone" />
                        </b-form-group>
                    </div>
                    <div class="col-md-6">
                        <b-form-group label="Tax Type *" label-for="rule-type">
                            <v-select id="rule-type" v-model="ruleForm.tax_type" :options="taxTypeOptions" :reduce="t => t.value" label="label" placeholder="Select type" />
                        </b-form-group>
                    </div>
                </div>
                <b-form-group label="Description" label-for="rule-desc">
                    <b-form-textarea id="rule-desc" v-model="ruleForm.description" rows="2" placeholder="Optional description" />
                </b-form-group>
                <hr>
                <h6>Tax Rates</h6>
                <div class="row">
                    <div class="col-md-3">
                        <b-form-group label="Total Rate (%)" label-for="rate">
                            <b-form-input id="rate" v-model="ruleForm.rate" type="number" step="0.01" min="0" placeholder="3.00" />
                        </b-form-group>
                    </div>
                    <div class="col-md-3">
                        <b-form-group label="CGST (%)" label-for="cgst">
                            <b-form-input id="cgst" v-model="ruleForm.cgst_rate" type="number" step="0.01" min="0" placeholder="1.50" />
                        </b-form-group>
                    </div>
                    <div class="col-md-3">
                        <b-form-group label="SGST (%)" label-for="sgst">
                            <b-form-input id="sgst" v-model="ruleForm.sgst_rate" type="number" step="0.01" min="0" placeholder="1.50" />
                        </b-form-group>
                    </div>
                    <div class="col-md-3">
                        <b-form-group label="IGST (%)" label-for="igst">
                            <b-form-input id="igst" v-model="ruleForm.igst_rate" type="number" step="0.01" min="0" placeholder="3.00" />
                        </b-form-group>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <b-form-group label="Apply To" label-for="apply-to">
                            <v-select id="apply-to" v-model="ruleForm.apply_to" :options="applyToOptions" :reduce="a => a.value" label="label" placeholder="Select" />
                        </b-form-group>
                    </div>
                    <div class="col-md-6">
                        <b-form-group label="Calculation Type" label-for="calc-type">
                            <v-select id="calc-type" v-model="ruleForm.calculation_type" :options="calculationTypes" :reduce="c => c.value" label="label" placeholder="Select" />
                        </b-form-group>
                    </div>
                </div>
                <div class="row mt-2">
                    <div class="col-md-4">
                        <b-form-checkbox v-model="ruleForm.is_active">Active</b-form-checkbox>
                    </div>
                    <div class="col-md-4">
                        <b-form-checkbox v-model="ruleForm.is_compound">Compound Tax</b-form-checkbox>
                    </div>
                    <div class="col-md-4">
                        <b-form-checkbox v-model="ruleForm.is_inclusive">Tax Inclusive</b-form-checkbox>
                    </div>
                </div>
            </b-form>
        </b-modal>

        <!-- HSN Modal -->
        <b-modal v-model="showHsnModal" :title="editingHsn ? 'Edit HSN Code' : 'Add HSN Code'" size="lg" @ok="saveHsn" ok-title="Save">
            <b-form @submit.prevent="saveHsn">
                <div class="row">
                    <div class="col-md-6">
                        <b-form-group label="HSN Code *" label-for="hsn-code">
                            <b-form-input id="hsn-code" v-model="hsnForm.code" required placeholder="e.g., 7113" />
                        </b-form-group>
                    </div>
                    <div class="col-md-6">
                        <b-form-group label="Type *" label-for="hsn-type">
                            <v-select id="hsn-type" v-model="hsnForm.type" :options="hsnTypeOptions" :reduce="t => t.value" label="label" placeholder="Select type" />
                        </b-form-group>
                    </div>
                </div>
                <b-form-group label="Description *" label-for="hsn-desc">
                    <b-form-textarea id="hsn-desc" v-model="hsnForm.description" rows="2" required placeholder="Description of goods/services" />
                </b-form-group>
                <hr>
                <h6>GST Rates</h6>
                <div class="row">
                    <div class="col-md-3">
                        <b-form-group label="GST Rate (%)" label-for="hsn-gst">
                            <b-form-input id="hsn-gst" v-model="hsnForm.gst_rate" type="number" step="0.01" min="0" placeholder="3.00" />
                        </b-form-group>
                    </div>
                    <div class="col-md-3">
                        <b-form-group label="CGST (%)" label-for="hsn-cgst">
                            <b-form-input id="hsn-cgst" v-model="hsnForm.cgst_rate" type="number" step="0.01" min="0" placeholder="1.50" />
                        </b-form-group>
                    </div>
                    <div class="col-md-3">
                        <b-form-group label="SGST (%)" label-for="hsn-sgst">
                            <b-form-input id="hsn-sgst" v-model="hsnForm.sgst_rate" type="number" step="0.01" min="0" placeholder="1.50" />
                        </b-form-group>
                    </div>
                    <div class="col-md-3">
                        <b-form-group label="IGST (%)" label-for="hsn-igst">
                            <b-form-input id="hsn-igst" v-model="hsnForm.igst_rate" type="number" step="0.01" min="0" placeholder="3.00" />
                        </b-form-group>
                    </div>
                </div>
                <div class="row mt-2">
                    <div class="col-md-4">
                        <b-form-checkbox v-model="hsnForm.is_active">Active</b-form-checkbox>
                    </div>
                </div>
            </b-form>
        </b-modal>

        <!-- Exemption Modal -->
        <b-modal v-model="showExemptionModal" :title="editingExemption ? 'Edit Tax Exemption' : 'Add Tax Exemption'" size="lg" @ok="saveExemption" ok-title="Save">
            <b-form @submit.prevent="saveExemption">
                <div class="row">
                    <div class="col-md-6">
                        <b-form-group label="Certificate Number *" label-for="cert-num">
                            <b-form-input id="cert-num" v-model="exemptionForm.certificate_number" required placeholder="Certificate number" />
                        </b-form-group>
                    </div>
                    <div class="col-md-6">
                        <b-form-group label="Exemption Type *" label-for="exempt-type">
                            <v-select id="exempt-type" v-model="exemptionForm.exemption_type" :options="exemptionTypeOptions" :reduce="t => t.value" label="label" placeholder="Select type" />
                        </b-form-group>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <b-form-group label="Customer ID" label-for="customer-id">
                            <b-form-input id="customer-id" v-model="exemptionForm.customer_id" type="number" placeholder="Customer ID (optional)" />
                        </b-form-group>
                    </div>
                    <div class="col-md-6">
                        <b-form-group label="Exemption Percentage (%)" label-for="exempt-pct">
                            <b-form-input id="exempt-pct" v-model="exemptionForm.exemption_percentage" type="number" step="0.01" min="0" max="100" placeholder="100" />
                        </b-form-group>
                    </div>
                </div>
                <b-form-group label="Reason *" label-for="exempt-reason">
                    <b-form-textarea id="exempt-reason" v-model="exemptionForm.reason" rows="2" required placeholder="Reason for exemption" />
                </b-form-group>
                <div class="row">
                    <div class="col-md-6">
                        <b-form-group label="Valid From *" label-for="valid-from">
                            <b-form-input id="valid-from" v-model="exemptionForm.valid_from" type="date" required />
                        </b-form-group>
                    </div>
                    <div class="col-md-6">
                        <b-form-group label="Valid Until" label-for="valid-until">
                            <b-form-input id="valid-until" v-model="exemptionForm.valid_until" type="date" />
                        </b-form-group>
                    </div>
                </div>
            </b-form>
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

// Modal State
const showZoneModal = ref(false)
const showRuleModal = ref(false)
const showHsnModal = ref(false)
const showExemptionModal = ref(false)
const editingZone = ref(null)
const editingRule = ref(null)
const editingHsn = ref(null)
const editingExemption = ref(null)

// Form Data
const zoneForm = ref({
    name: '', code: '', description: '', countries_text: '', states_text: '',
    is_active: true, is_default: false, priority: 1
})

const ruleForm = ref({
    name: '', code: '', description: '', tax_zone_id: null, tax_type: 'gst',
    rate: 3.00, cgst_rate: 1.50, sgst_rate: 1.50, igst_rate: 3.00,
    apply_to: 'all', calculation_type: 'percentage',
    is_active: true, is_compound: false, is_inclusive: false
})

const hsnForm = ref({
    code: '', description: '', type: 'goods',
    gst_rate: 3.00, cgst_rate: 1.50, sgst_rate: 1.50, igst_rate: 3.00, is_active: true
})

const exemptionForm = ref({
    certificate_number: '', exemption_type: 'full', customer_id: null,
    exemption_percentage: 100, reason: '', valid_from: '', valid_until: ''
})

// Options
const taxTypeOptions = [
    { value: 'gst', label: 'GST' },
    { value: 'igst', label: 'IGST' },
    { value: 'cgst_sgst', label: 'CGST + SGST' },
    { value: 'vat', label: 'VAT' },
    { value: 'custom', label: 'Custom' }
]

const applyToOptions = [
    { value: 'all', label: 'All Products' },
    { value: 'category', label: 'Specific Categories' },
    { value: 'product', label: 'Specific Products' }
]

const calculationTypes = [
    { value: 'percentage', label: 'Percentage' },
    { value: 'fixed', label: 'Fixed Amount' }
]

const hsnTypeOptions = [
    { value: 'goods', label: 'Goods' },
    { value: 'services', label: 'Services' }
]

const exemptionTypeOptions = [
    { value: 'full', label: 'Full Exemption' },
    { value: 'partial', label: 'Partial Exemption' },
    { value: 'category', label: 'Category-based' }
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

// Actions - Zone
const openZoneModal = (zone = null) => {
    if (zone) {
        editingZone.value = zone
        zoneForm.value = {
            ...zone,
            countries_text: Array.isArray(zone.countries) ? zone.countries.join(', ') : '',
            states_text: Array.isArray(zone.states) ? zone.states.join(', ') : ''
        }
    } else {
        editingZone.value = null
        zoneForm.value = { name: '', code: '', description: '', countries_text: '', states_text: '', is_active: true, is_default: false, priority: 1 }
    }
    showZoneModal.value = true
}

const saveZone = async () => {
    try {
        const data = {
            ...zoneForm.value,
            countries: zoneForm.value.countries_text?.split(',').map(s => s.trim()).filter(Boolean) || [],
            states: zoneForm.value.states_text?.split(',').map(s => s.trim()).filter(Boolean) || []
        }
        delete data.countries_text
        delete data.states_text

        if (editingZone.value) {
            await axiosEmployee.put(`/admin/tax/zones/${editingZone.value.id}`, data)
            toast.success('Zone updated')
        } else {
            await axiosEmployee.post('/admin/tax/zones', data)
            toast.success('Zone created')
        }
        showZoneModal.value = false
        fetchZones()
        fetchDashboard()
    } catch (error) {
        toast.error(error.response?.data?.message || 'Failed to save zone')
    }
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

// Actions - Rule
const openRuleModal = (rule = null) => {
    if (rule) {
        editingRule.value = rule
        ruleForm.value = { ...rule }
    } else {
        editingRule.value = null
        ruleForm.value = {
            name: '', code: '', description: '', tax_zone_id: null, tax_type: 'gst',
            rate: 3.00, cgst_rate: 1.50, sgst_rate: 1.50, igst_rate: 3.00,
            apply_to: 'all', calculation_type: 'percentage',
            is_active: true, is_compound: false, is_inclusive: false
        }
    }
    showRuleModal.value = true
}

const saveRule = async () => {
    try {
        if (editingRule.value) {
            await axiosEmployee.put(`/admin/tax/rules/${editingRule.value.id}`, ruleForm.value)
            toast.success('Rule updated')
        } else {
            await axiosEmployee.post('/admin/tax/rules', ruleForm.value)
            toast.success('Rule created')
        }
        showRuleModal.value = false
        fetchRules()
        fetchDashboard()
    } catch (error) {
        toast.error(error.response?.data?.message || 'Failed to save rule')
    }
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

// Actions - Exemption
const openExemptionModal = (exemption = null) => {
    if (exemption) {
        editingExemption.value = exemption
        exemptionForm.value = { ...exemption }
    } else {
        editingExemption.value = null
        exemptionForm.value = {
            certificate_number: '', exemption_type: 'full', customer_id: null,
            exemption_percentage: 100, reason: '', valid_from: '', valid_until: ''
        }
    }
    showExemptionModal.value = true
}

const saveExemption = async () => {
    try {
        if (editingExemption.value) {
            await axiosEmployee.put(`/admin/tax/exemptions/${editingExemption.value.id}`, exemptionForm.value)
            toast.success('Exemption updated')
        } else {
            await axiosEmployee.post('/admin/tax/exemptions', exemptionForm.value)
            toast.success('Exemption created')
        }
        showExemptionModal.value = false
        fetchExemptions()
        fetchDashboard()
    } catch (error) {
        toast.error(error.response?.data?.message || 'Failed to save exemption')
    }
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

// Actions - HSN
const openHsnModal = (hsn = null) => {
    if (hsn) {
        editingHsn.value = hsn
        hsnForm.value = { ...hsn }
    } else {
        editingHsn.value = null
        hsnForm.value = {
            code: '', description: '', type: 'goods',
            gst_rate: 3.00, cgst_rate: 1.50, sgst_rate: 1.50, igst_rate: 3.00, is_active: true
        }
    }
    showHsnModal.value = true
}

const saveHsn = async () => {
    try {
        if (editingHsn.value) {
            await axiosEmployee.put(`/admin/tax/hsn/${editingHsn.value.id}`, hsnForm.value)
            toast.success('HSN code updated')
        } else {
            await axiosEmployee.post('/admin/tax/hsn', hsnForm.value)
            toast.success('HSN code created')
        }
        showHsnModal.value = false
        fetchHsnCodes()
        fetchDashboard()
    } catch (error) {
        toast.error(error.response?.data?.message || 'Failed to save HSN code')
    }
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
