<template>
    <div class="listing_screen global_table_liting">
        <div class="masterTabs">
            <div class="masterTabContent">
                <!-- Dashboard Stats -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="stat-card">
                            <h3>{{ stats.total_members }}</h3>
                            <p>Total Members</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <h3>{{ formatNumber(stats.total_points_issued) }}</h3>
                            <p>Points Issued</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <h3>{{ formatNumber(stats.total_points_redeemed) }}</h3>
                            <p>Points Redeemed</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card warning" v-if="stats.points_expiring_soon > 0">
                            <h3>{{ formatNumber(stats.points_expiring_soon) }}</h3>
                            <p>Expiring This Month</p>
                        </div>
                        <div class="stat-card success" v-else>
                            <h3>0</h3>
                            <p>Expiring This Month</p>
                        </div>
                    </div>
                </div>

                <!-- Tabs -->
                <b-tabs v-model="activeTab" content-class="mt-3">
                    <!-- Users Tab -->
                    <b-tab title="Loyalty Members" active>
                        <div class="listing_tab_and_actions mb-3">
                            <div class="listing_actions">
                                <div class="d-flex gap-2">
                                    <div class="listing_search">
                                        <b-form-input v-model="userSearch" @input="fetchUsers" placeholder="Search members..." />
                                    </div>
                                    <v-select v-model="tierFilter" :options="tierOptions" placeholder="Filter by tier"
                                        :clearable="true" class="tier-select" :reduce="t => t.value" label="label" />
                                </div>
                                <div class="buttonGrid">
                                    <b-button class="transBTN" @click="processExpiredPoints">Process Expired Points</b-button>
                                </div>
                            </div>
                        </div>

                        <b-table responsive="sm" :items="users" :fields="userFields" v-if="users.length > 0">
                            <template #cell(name)="row">
                                <div>
                                    <strong>{{ row.item.name }}</strong>
                                    <small class="d-block text-muted">{{ row.item.email }}</small>
                                </div>
                            </template>
                            <template #cell(tier)="row">
                                <b-badge :variant="getTierVariant(row.item.loyalty_tier)">
                                    {{ row.item.loyalty_tier || 'Bronze' }}
                                </b-badge>
                            </template>
                            <template #cell(points_balance)="row">
                                <strong>{{ formatNumber(row.item.loyalty_points || 0) }}</strong>
                            </template>
                            <template #cell(lifetime_points)="row">
                                {{ formatNumber(row.item.total_points_earned || 0) }}
                            </template>
                            <template #cell(actions)="row">
                                <b-dropdown right text="" variant="link" no-caret toggle-class="p-0">
                                    <b-dropdown-item @click="viewUserDetails(row.item)">View History</b-dropdown-item>
                                    <b-dropdown-item @click="openAdjustModal(row.item)">Adjust Points</b-dropdown-item>
                                </b-dropdown>
                            </template>
                        </b-table>

                        <div v-else class="text-center p-5">
                            <p>No loyalty members found.</p>
                        </div>

                        <b-pagination v-if="userPagination.total > userPagination.per_page"
                            v-model="userPagination.current_page"
                            :total-rows="userPagination.total"
                            :per-page="userPagination.per_page"
                            @change="fetchUsers"
                            align="center"
                        />
                    </b-tab>

                    <!-- Tiers Tab -->
                    <b-tab title="Loyalty Tiers">
                        <div class="listing_tab_and_actions mb-3">
                            <div class="listing_actions">
                                <div></div>
                                <div class="buttonGrid">
                                    <b-button class="fillBTN" @click="openTierModal()">Add Tier</b-button>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div v-for="tier in tiers" :key="tier.id" class="col-md-6 col-lg-3 mb-4">
                                <div :class="['tier-card', `tier-${tier.name.toLowerCase()}`]">
                                    <div class="tier-header">
                                        <h4>{{ tier.name }}</h4>
                                        <b-dropdown right text="" variant="link" no-caret toggle-class="p-0 text-white">
                                            <b-dropdown-item @click="openTierModal(tier)">Edit</b-dropdown-item>
                                            <b-dropdown-item @click="deleteTier(tier.id)" v-if="tier.name !== 'Bronze'">Delete</b-dropdown-item>
                                        </b-dropdown>
                                    </div>
                                    <div class="tier-body">
                                        <p class="tier-threshold">
                                            <span>Min Points:</span>
                                            <strong>{{ formatNumber(tier.min_points) }}</strong>
                                        </p>
                                        <p class="tier-multiplier">
                                            <span>Multiplier:</span>
                                            <strong>{{ tier.multiplier }}x</strong>
                                        </p>
                                        <div class="tier-benefits" v-if="tier.benefits">
                                            <small>Benefits:</small>
                                            <ul>
                                                <li v-for="(benefit, idx) in parseBenefits(tier.benefits)" :key="idx">
                                                    {{ benefit }}
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div v-if="tiers.length === 0" class="text-center p-5">
                            <p>No loyalty tiers configured. Add your first tier to get started.</p>
                        </div>
                    </b-tab>

                    <!-- Transactions Tab -->
                    <b-tab title="Point Transactions">
                        <div class="listing_tab_and_actions mb-3">
                            <div class="listing_actions">
                                <div class="d-flex gap-2">
                                    <v-select v-model="txnTypeFilter" :options="txnTypes"
                                        placeholder="Filter by type" :clearable="true" class="type-select" />
                                    <b-form-input type="date" v-model="txnDateFrom" @change="fetchTransactions" />
                                    <b-form-input type="date" v-model="txnDateTo" @change="fetchTransactions" />
                                </div>
                                <div class="buttonGrid">
                                    <b-button class="transBTN" @click="exportTransactions">Export CSV</b-button>
                                </div>
                            </div>
                        </div>

                        <b-table responsive="sm" :items="transactions" :fields="txnFields" v-if="transactions.length > 0">
                            <template #cell(customer)="row">
                                {{ row.item.customer?.name || 'N/A' }}
                            </template>
                            <template #cell(type)="row">
                                <b-badge :variant="getTxnTypeVariant(row.item.type)">{{ row.item.type }}</b-badge>
                            </template>
                            <template #cell(points)="row">
                                <span :class="row.item.points > 0 ? 'text-success' : 'text-danger'">
                                    {{ row.item.points > 0 ? '+' : '' }}{{ formatNumber(row.item.points) }}
                                </span>
                            </template>
                            <template #cell(created_at)="row">
                                {{ formatDate(row.item.created_at) }}
                            </template>
                        </b-table>

                        <div v-else class="text-center p-5">
                            <p>No transactions found.</p>
                        </div>

                        <b-pagination v-if="txnPagination.total > txnPagination.per_page"
                            v-model="txnPagination.current_page"
                            :total-rows="txnPagination.total"
                            :per-page="txnPagination.per_page"
                            @change="fetchTransactions"
                            align="center"
                        />
                    </b-tab>
                </b-tabs>
            </div>
        </div>

        <!-- Adjust Points Modal -->
        <b-modal v-model="showAdjustModal" title="Adjust Loyalty Points" size="md" @ok="adjustPoints" ok-title="Adjust">
            <b-form @submit.prevent="adjustPoints">
                <div class="mb-3 text-center">
                    <h5>{{ selectedUser?.name }}</h5>
                    <p class="text-muted mb-0">Current Balance: <strong>{{ formatNumber(selectedUser?.loyalty_points || 0) }}</strong></p>
                </div>
                <b-form-group label="Adjustment Type *" label-for="adjust-type">
                    <v-select id="adjust-type" v-model="adjustForm.type" :options="adjustTypes"
                        :reduce="t => t.value" label="label" placeholder="Select type" />
                </b-form-group>
                <b-form-group label="Points *" label-for="adjust-points">
                    <b-form-input id="adjust-points" v-model="adjustForm.points" type="number" min="1" required placeholder="Enter points" />
                </b-form-group>
                <b-form-group label="Reason *" label-for="adjust-reason">
                    <b-form-textarea id="adjust-reason" v-model="adjustForm.reason" rows="2" required placeholder="Enter reason for adjustment" />
                </b-form-group>
            </b-form>
        </b-modal>

        <!-- Tier Modal -->
        <b-modal v-model="showTierModal" :title="editingTier ? 'Edit Tier' : 'Add Tier'" size="lg" @ok="saveTier" ok-title="Save">
            <b-form @submit.prevent="saveTier">
                <div class="row">
                    <div class="col-md-6">
                        <b-form-group label="Tier Name *" label-for="tier-name">
                            <b-form-input id="tier-name" v-model="tierForm.name" required placeholder="e.g., Gold" />
                        </b-form-group>
                    </div>
                    <div class="col-md-6">
                        <b-form-group label="Minimum Points *" label-for="tier-min">
                            <b-form-input id="tier-min" v-model="tierForm.min_points" type="number" min="0" required placeholder="0" />
                        </b-form-group>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <b-form-group label="Points Multiplier *" label-for="tier-mult">
                            <b-form-input id="tier-mult" v-model="tierForm.multiplier" type="number" step="0.1" min="1" required placeholder="1.0" />
                        </b-form-group>
                    </div>
                    <div class="col-md-6">
                        <b-form-group label="Discount Percentage" label-for="tier-discount">
                            <b-form-input id="tier-discount" v-model="tierForm.discount_percentage" type="number" min="0" max="100" placeholder="0" />
                        </b-form-group>
                    </div>
                </div>
                <b-form-group label="Benefits (one per line)" label-for="tier-benefits">
                    <b-form-textarea id="tier-benefits" v-model="tierForm.benefits_text" rows="4"
                        placeholder="Free shipping on orders above ₹5000&#10;Priority customer support&#10;Early access to sales" />
                </b-form-group>
                <div class="row mt-2">
                    <div class="col-md-6">
                        <b-form-checkbox v-model="tierForm.is_active">Active</b-form-checkbox>
                    </div>
                </div>
            </b-form>
        </b-modal>

        <!-- User History Modal -->
        <b-modal v-model="showHistoryModal" :title="'Point History - ' + (selectedUser?.name || '')" size="xl" hide-footer>
            <b-table responsive="sm" :items="userHistory" :fields="historyFields" v-if="userHistory.length > 0">
                <template #cell(type)="row">
                    <b-badge :variant="getTxnTypeVariant(row.item.type)">{{ row.item.type }}</b-badge>
                </template>
                <template #cell(points)="row">
                    <span :class="row.item.points > 0 ? 'text-success' : 'text-danger'">
                        {{ row.item.points > 0 ? '+' : '' }}{{ formatNumber(row.item.points) }}
                    </span>
                </template>
                <template #cell(created_at)="row">
                    {{ formatDate(row.item.created_at) }}
                </template>
            </b-table>
            <div v-else class="text-center p-3">
                <p>No transaction history found.</p>
            </div>
        </b-modal>
    </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue'
import axiosEmployee from '@axiosEmployee'
import { toast } from 'vue3-toastify'

// State
const activeTab = ref(0)
const stats = ref({
    total_members: 0,
    total_points_issued: 0,
    total_points_redeemed: 0,
    points_expiring_soon: 0
})

// Users
const users = ref([])
const userSearch = ref('')
const tierFilter = ref(null)
const userFields = [
    { key: 'name', label: 'Member', sortable: true },
    { key: 'tier', label: 'Tier' },
    { key: 'points_balance', label: 'Balance' },
    { key: 'lifetime_points', label: 'Lifetime' },
    { key: 'actions', label: 'Actions' }
]
const userPagination = ref({ current_page: 1, per_page: 20, total: 0 })

// Tiers
const tiers = ref([])
const tierOptions = ref([])

// Transactions
const transactions = ref([])
const txnTypeFilter = ref(null)
const txnDateFrom = ref('')
const txnDateTo = ref('')
const txnTypes = ['earned', 'redeemed', 'adjusted', 'expired', 'bonus']
const txnFields = [
    { key: 'customer', label: 'Customer' },
    { key: 'type', label: 'Type' },
    { key: 'points', label: 'Points' },
    { key: 'description', label: 'Description' },
    { key: 'created_at', label: 'Date' }
]
const txnPagination = ref({ current_page: 1, per_page: 20, total: 0 })

// Modal State
const showAdjustModal = ref(false)
const showTierModal = ref(false)
const showHistoryModal = ref(false)
const selectedUser = ref(null)
const editingTier = ref(null)
const userHistory = ref([])

// Forms
const adjustForm = ref({
    type: 'add',
    points: '',
    reason: ''
})

const adjustTypes = [
    { value: 'add', label: 'Add Points' },
    { value: 'deduct', label: 'Deduct Points' }
]

const tierForm = ref({
    name: '',
    min_points: 0,
    multiplier: 1.0,
    discount_percentage: 0,
    benefits_text: '',
    is_active: true
})

const historyFields = [
    { key: 'type', label: 'Type' },
    { key: 'points', label: 'Points' },
    { key: 'balance_after', label: 'Balance After' },
    { key: 'description', label: 'Description' },
    { key: 'created_at', label: 'Date' }
]

// Fetch functions
const fetchStatistics = async () => {
    try {
        const response = await axiosEmployee.get('/admin/loyalty/statistics')
        if (response.data.success) {
            stats.value = response.data.data
        }
    } catch (error) {
        console.error('Error fetching statistics:', error)
    }
}

const fetchUsers = async () => {
    try {
        const response = await axiosEmployee.get('/admin/loyalty/users', {
            params: {
                search: userSearch.value,
                tier: tierFilter.value,
                page: userPagination.value.current_page
            }
        })
        if (response.data.success) {
            users.value = response.data.data.data || []
            userPagination.value.total = response.data.data.total || 0
        }
    } catch (error) {
        console.error('Error fetching users:', error)
    }
}

const fetchTiers = async () => {
    try {
        const response = await axiosEmployee.get('/admin/loyalty/tiers')
        if (response.data.success) {
            tiers.value = response.data.data || []
            tierOptions.value = tiers.value.map(t => ({ value: t.name, label: t.name }))
        }
    } catch (error) {
        console.error('Error fetching tiers:', error)
    }
}

const fetchTransactions = async () => {
    try {
        const response = await axiosEmployee.get('/admin/loyalty/transactions', {
            params: {
                type: txnTypeFilter.value,
                from: txnDateFrom.value,
                to: txnDateTo.value,
                page: txnPagination.value.current_page
            }
        })
        if (response.data.success) {
            transactions.value = response.data.data.data || []
            txnPagination.value.total = response.data.data.total || 0
        }
    } catch (error) {
        console.error('Error fetching transactions:', error)
    }
}

// Helper functions
const formatNumber = (num) => {
    return Number(num || 0).toLocaleString()
}

const formatDate = (date) => {
    if (!date) return '-'
    return new Date(date).toLocaleDateString('en-IN', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    })
}

const getTierVariant = (tier) => {
    const variants = {
        Bronze: 'secondary',
        Silver: 'light',
        Gold: 'warning',
        Platinum: 'info',
        Diamond: 'primary'
    }
    return variants[tier] || 'secondary'
}

const getTxnTypeVariant = (type) => {
    const variants = {
        earned: 'success',
        redeemed: 'primary',
        adjusted: 'info',
        expired: 'danger',
        bonus: 'warning'
    }
    return variants[type] || 'secondary'
}

const parseBenefits = (benefits) => {
    if (!benefits) return []
    if (Array.isArray(benefits)) return benefits
    try {
        return JSON.parse(benefits)
    } catch {
        return benefits.split('\n').filter(b => b.trim())
    }
}

// Actions
const openAdjustModal = (user) => {
    selectedUser.value = user
    adjustForm.value = { type: 'add', points: '', reason: '' }
    showAdjustModal.value = true
}

const adjustPoints = async () => {
    if (!adjustForm.value.points || !adjustForm.value.reason) {
        toast.error('Please fill all required fields')
        return
    }

    try {
        const points = adjustForm.value.type === 'deduct'
            ? -Math.abs(adjustForm.value.points)
            : Math.abs(adjustForm.value.points)

        await axiosEmployee.post(`/admin/loyalty/users/${selectedUser.value.id}/adjust`, {
            points,
            reason: adjustForm.value.reason
        })
        toast.success('Points adjusted successfully')
        showAdjustModal.value = false
        fetchUsers()
        fetchStatistics()
    } catch (error) {
        toast.error(error.response?.data?.message || 'Failed to adjust points')
    }
}

const viewUserDetails = async (user) => {
    selectedUser.value = user
    try {
        const response = await axiosEmployee.get(`/admin/loyalty/users/${user.id}`)
        if (response.data.success) {
            userHistory.value = response.data.data.transactions || []
        }
        showHistoryModal.value = true
    } catch (error) {
        toast.error('Failed to load user history')
    }
}

const openTierModal = (tier = null) => {
    if (tier) {
        editingTier.value = tier
        tierForm.value = {
            name: tier.name,
            min_points: tier.min_points,
            multiplier: tier.multiplier,
            discount_percentage: tier.discount_percentage || 0,
            benefits_text: Array.isArray(tier.benefits) ? tier.benefits.join('\n') : tier.benefits || '',
            is_active: tier.is_active
        }
    } else {
        editingTier.value = null
        tierForm.value = {
            name: '',
            min_points: 0,
            multiplier: 1.0,
            discount_percentage: 0,
            benefits_text: '',
            is_active: true
        }
    }
    showTierModal.value = true
}

const saveTier = async () => {
    try {
        const data = {
            ...tierForm.value,
            benefits: tierForm.value.benefits_text.split('\n').filter(b => b.trim())
        }
        delete data.benefits_text

        if (editingTier.value) {
            await axiosEmployee.put(`/admin/loyalty/tiers/${editingTier.value.id}`, data)
            toast.success('Tier updated')
        } else {
            await axiosEmployee.post('/admin/loyalty/tiers', data)
            toast.success('Tier created')
        }
        showTierModal.value = false
        fetchTiers()
    } catch (error) {
        toast.error(error.response?.data?.message || 'Failed to save tier')
    }
}

const deleteTier = async (id) => {
    if (!confirm('Are you sure you want to delete this tier?')) return
    try {
        await axiosEmployee.delete(`/admin/loyalty/tiers/${id}`)
        toast.success('Tier deleted')
        fetchTiers()
    } catch (error) {
        toast.error(error.response?.data?.message || 'Failed to delete tier')
    }
}

const processExpiredPoints = async () => {
    if (!confirm('This will expire all points past their expiry date. Continue?')) return
    try {
        await axiosEmployee.post('/admin/loyalty/process-expiry')
        toast.success('Expired points processed')
        fetchStatistics()
        fetchUsers()
    } catch (error) {
        toast.error(error.response?.data?.message || 'Failed to process expired points')
    }
}

const exportTransactions = () => {
    toast.info('Export feature - implement as needed')
}

// Watchers
watch(tierFilter, fetchUsers)
watch(txnTypeFilter, fetchTransactions)

// Lifecycle
onMounted(() => {
    fetchStatistics()
    fetchUsers()
    fetchTiers()
    fetchTransactions()
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
.tier-select, .type-select {
    min-width: 180px;
}

/* Tier Cards */
.tier-card {
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}
.tier-header {
    padding: 15px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.tier-header h4 {
    margin: 0;
    color: #fff;
}
.tier-body {
    background: #fff;
    padding: 15px;
}
.tier-threshold, .tier-multiplier {
    display: flex;
    justify-content: space-between;
    margin-bottom: 8px;
}
.tier-benefits {
    margin-top: 10px;
    padding-top: 10px;
    border-top: 1px solid #eee;
}
.tier-benefits ul {
    margin: 5px 0 0;
    padding-left: 20px;
    font-size: 0.9em;
}
.tier-benefits li {
    margin-bottom: 3px;
}

/* Tier Colors */
.tier-bronze .tier-header { background: linear-gradient(135deg, #cd7f32, #b87333); }
.tier-silver .tier-header { background: linear-gradient(135deg, #c0c0c0, #a8a8a8); }
.tier-gold .tier-header { background: linear-gradient(135deg, #ffd700, #daa520); }
.tier-platinum .tier-header { background: linear-gradient(135deg, #e5e4e2, #a0a0a0); }
.tier-diamond .tier-header { background: linear-gradient(135deg, #b9f2ff, #0abab5); }
</style>
