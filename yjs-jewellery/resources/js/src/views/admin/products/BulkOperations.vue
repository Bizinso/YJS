<template>
    <div class="listing_screen global_table_liting">
        <div class="masterTabs">
            <div class="masterTabContent">
                <div class="mb-4">
                    <h2>Product Bulk Operations</h2>
                    <p class="text-muted">Import, export, and bulk update products</p>
                </div>

                <!-- Tabs -->
                <b-tabs v-model="activeTab" content-class="mt-3">
                    <!-- Import Tab -->
                    <b-tab title="Import Products" active>
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title mb-4">Import Products from CSV/Excel</h5>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-4">
                                            <h6>Step 1: Download Template</h6>
                                            <p class="text-muted">Download the import template with correct column headers.</p>
                                            <b-button variant="outline-primary" @click="downloadTemplate">
                                                <i class="bi bi-download"></i> Download Template
                                            </b-button>
                                        </div>

                                        <div class="mb-4">
                                            <h6>Step 2: Upload File</h6>
                                            <p class="text-muted">Upload your filled CSV or Excel file.</p>
                                            <b-form-file v-model="importFile" accept=".csv,.xlsx,.xls"
                                                placeholder="Choose file or drop here..." drop-placeholder="Drop file here..."
                                                @change="validateImportFile" />
                                            <small v-if="importFile" class="text-success d-block mt-2">
                                                File selected: {{ importFile.name }}
                                            </small>
                                        </div>

                                        <div class="mb-4" v-if="importPreview">
                                            <h6>Step 3: Review & Import</h6>
                                            <div class="alert alert-info">
                                                <p class="mb-1"><strong>{{ importPreview.total_rows }}</strong> products found</p>
                                                <p class="mb-1 text-success" v-if="importPreview.valid_rows">{{ importPreview.valid_rows }} valid</p>
                                                <p class="mb-0 text-danger" v-if="importPreview.error_rows">{{ importPreview.error_rows }} with errors</p>
                                            </div>
                                            <b-button variant="primary" @click="processImport" :disabled="importing || !importPreview.valid_rows">
                                                <b-spinner small v-if="importing"></b-spinner>
                                                {{ importing ? 'Importing...' : 'Start Import' }}
                                            </b-button>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="import-guidelines">
                                            <h6>Import Guidelines</h6>
                                            <ul>
                                                <li>Use the provided template for correct formatting</li>
                                                <li>Required fields: SKU, Name, Price</li>
                                                <li>Categories should be separated by commas</li>
                                                <li>Images should be URLs (comma-separated for multiple)</li>
                                                <li>Maximum 1000 products per import</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Import Jobs History -->
                        <div class="card mt-4">
                            <div class="card-header">
                                <h6 class="mb-0">Recent Import Jobs</h6>
                            </div>
                            <div class="card-body p-0">
                                <b-table responsive :items="importJobs" :fields="importJobFields" v-if="importJobs.length > 0" class="mb-0">
                                    <template #cell(status)="row">
                                        <b-badge :variant="getJobStatusVariant(row.item.status)">{{ row.item.status }}</b-badge>
                                    </template>
                                    <template #cell(progress)="row">
                                        <b-progress :value="row.item.progress" :max="100" show-progress v-if="row.item.status === 'processing'" />
                                        <span v-else>{{ row.item.processed_rows }}/{{ row.item.total_rows }}</span>
                                    </template>
                                    <template #cell(created_at)="row">
                                        {{ formatDate(row.item.created_at) }}
                                    </template>
                                </b-table>
                                <div v-else class="text-center p-4 text-muted">
                                    No import jobs yet
                                </div>
                            </div>
                        </div>
                    </b-tab>

                    <!-- Export Tab -->
                    <b-tab title="Export Products">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title mb-4">Export Products</h5>

                                <div class="row">
                                    <div class="col-md-6">
                                        <b-form-group label="Export Format" label-for="export-format">
                                            <v-select id="export-format" v-model="exportForm.format" :options="exportFormats"
                                                :reduce="f => f.value" label="label" placeholder="Select format" />
                                        </b-form-group>

                                        <b-form-group label="Categories (optional)" label-for="export-categories">
                                            <v-select id="export-categories" v-model="exportForm.categories" :options="categories"
                                                :reduce="c => c.id" label="name" multiple placeholder="All categories" />
                                        </b-form-group>

                                        <b-form-group label="Status Filter" label-for="export-status">
                                            <v-select id="export-status" v-model="exportForm.status" :options="statusOptions"
                                                :reduce="s => s.value" label="label" placeholder="All statuses" />
                                        </b-form-group>

                                        <b-form-group label="Include Fields">
                                            <b-form-checkbox-group v-model="exportForm.fields">
                                                <b-form-checkbox value="basic">Basic Info</b-form-checkbox>
                                                <b-form-checkbox value="pricing">Pricing</b-form-checkbox>
                                                <b-form-checkbox value="inventory">Inventory</b-form-checkbox>
                                                <b-form-checkbox value="seo">SEO Data</b-form-checkbox>
                                                <b-form-checkbox value="images">Image URLs</b-form-checkbox>
                                            </b-form-checkbox-group>
                                        </b-form-group>

                                        <b-button variant="primary" @click="exportProducts" :disabled="exporting">
                                            <b-spinner small v-if="exporting"></b-spinner>
                                            {{ exporting ? 'Exporting...' : 'Export Products' }}
                                        </b-button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </b-tab>

                    <!-- Bulk Price Update Tab -->
                    <b-tab title="Bulk Price Update">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title mb-4">Bulk Price Update</h5>

                                <div class="row">
                                    <div class="col-md-6">
                                        <b-form-group label="Update Type *" label-for="price-type">
                                            <v-select id="price-type" v-model="priceForm.type" :options="priceUpdateTypes"
                                                :reduce="t => t.value" label="label" placeholder="Select type" />
                                        </b-form-group>

                                        <b-form-group label="Value *" label-for="price-value">
                                            <b-input-group :append="priceForm.type === 'percentage' ? '%' : '₹'">
                                                <b-form-input id="price-value" v-model="priceForm.value" type="number" step="0.01" placeholder="Enter value" />
                                            </b-input-group>
                                        </b-form-group>

                                        <b-form-group label="Apply To" label-for="price-apply">
                                            <v-select id="price-apply" v-model="priceForm.apply_to" :options="applyToOptions"
                                                :reduce="a => a.value" label="label" placeholder="Select products" />
                                        </b-form-group>

                                        <b-form-group label="Categories" label-for="price-categories" v-if="priceForm.apply_to === 'category'">
                                            <v-select id="price-categories" v-model="priceForm.categories" :options="categories"
                                                :reduce="c => c.id" label="name" multiple placeholder="Select categories" />
                                        </b-form-group>

                                        <div class="d-flex gap-2">
                                            <b-button variant="outline-primary" @click="previewPriceUpdate" :disabled="!priceForm.type || !priceForm.value">
                                                Preview Changes
                                            </b-button>
                                            <b-button variant="primary" @click="applyPriceUpdate" :disabled="!pricePreview || applyingPrice">
                                                <b-spinner small v-if="applyingPrice"></b-spinner>
                                                Apply Changes
                                            </b-button>
                                        </div>
                                    </div>

                                    <div class="col-md-6" v-if="pricePreview">
                                        <div class="price-preview">
                                            <h6>Preview</h6>
                                            <div class="alert alert-warning">
                                                <p class="mb-1"><strong>{{ pricePreview.affected_products }}</strong> products will be affected</p>
                                                <p class="mb-0">Average change: {{ pricePreview.avg_change }}</p>
                                            </div>
                                            <b-table :items="pricePreview.samples" :fields="pricePreviewFields" small responsive class="mb-0">
                                                <template #cell(old_price)="row">₹{{ formatNumber(row.item.old_price) }}</template>
                                                <template #cell(new_price)="row">₹{{ formatNumber(row.item.new_price) }}</template>
                                            </b-table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Price Update History -->
                        <div class="card mt-4">
                            <div class="card-header">
                                <h6 class="mb-0">Price Update History</h6>
                            </div>
                            <div class="card-body p-0">
                                <b-table responsive :items="priceHistory" :fields="priceHistoryFields" v-if="priceHistory.length > 0" class="mb-0">
                                    <template #cell(created_at)="row">
                                        {{ formatDate(row.item.created_at) }}
                                    </template>
                                </b-table>
                                <div v-else class="text-center p-4 text-muted">
                                    No price updates yet
                                </div>
                            </div>
                        </div>
                    </b-tab>

                    <!-- Bulk Status Update Tab -->
                    <b-tab title="Bulk Status Update">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title mb-4">Bulk Status Update</h5>

                                <div class="row">
                                    <div class="col-md-6">
                                        <b-form-group label="New Status *" label-for="bulk-status">
                                            <v-select id="bulk-status" v-model="statusForm.status" :options="productStatuses"
                                                :reduce="s => s.value" label="label" placeholder="Select status" />
                                        </b-form-group>

                                        <b-form-group label="Apply To" label-for="status-apply">
                                            <v-select id="status-apply" v-model="statusForm.apply_to" :options="applyToOptions"
                                                :reduce="a => a.value" label="label" placeholder="Select products" />
                                        </b-form-group>

                                        <b-form-group label="Categories" label-for="status-categories" v-if="statusForm.apply_to === 'category'">
                                            <v-select id="status-categories" v-model="statusForm.categories" :options="categories"
                                                :reduce="c => c.id" label="name" multiple placeholder="Select categories" />
                                        </b-form-group>

                                        <b-form-group label="Product IDs (comma-separated)" v-if="statusForm.apply_to === 'selected'">
                                            <b-form-textarea v-model="statusForm.product_ids" rows="3" placeholder="1, 2, 3, 4, 5" />
                                        </b-form-group>

                                        <b-button variant="primary" @click="applyStatusUpdate" :disabled="!statusForm.status || applyingStatus">
                                            <b-spinner small v-if="applyingStatus"></b-spinner>
                                            Update Status
                                        </b-button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </b-tab>

                    <!-- SEO Generation Tab -->
                    <b-tab title="SEO Generation">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title mb-4">Bulk SEO Generation</h5>
                                <p class="text-muted">Auto-generate SEO meta titles and descriptions for products.</p>

                                <div class="row">
                                    <div class="col-md-6">
                                        <b-form-group label="Generate For" label-for="seo-apply">
                                            <v-select id="seo-apply" v-model="seoForm.apply_to" :options="seoApplyOptions"
                                                :reduce="a => a.value" label="label" placeholder="Select products" />
                                        </b-form-group>

                                        <b-form-group label="Categories" label-for="seo-categories" v-if="seoForm.apply_to === 'category'">
                                            <v-select id="seo-categories" v-model="seoForm.categories" :options="categories"
                                                :reduce="c => c.id" label="name" multiple placeholder="Select categories" />
                                        </b-form-group>

                                        <b-form-group>
                                            <b-form-checkbox v-model="seoForm.overwrite">Overwrite existing SEO data</b-form-checkbox>
                                        </b-form-group>

                                        <b-button variant="primary" @click="generateSeo" :disabled="generatingSeo">
                                            <b-spinner small v-if="generatingSeo"></b-spinner>
                                            Generate SEO Data
                                        </b-button>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="seo-info">
                                            <h6>SEO Generation Rules</h6>
                                            <ul>
                                                <li><strong>Meta Title:</strong> Product Name - Category | YJS Jewellers</li>
                                                <li><strong>Meta Description:</strong> Auto-generated from product description</li>
                                                <li><strong>Keywords:</strong> Based on product attributes and category</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </b-tab>
                </b-tabs>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axiosEmployee from '@axiosEmployee'
import { toast } from 'vue3-toastify'

// State
const activeTab = ref(0)
const categories = ref([])

// Import
const importFile = ref(null)
const importPreview = ref(null)
const importing = ref(false)
const importJobs = ref([])
const importJobFields = [
    { key: 'id', label: 'Job ID' },
    { key: 'file_name', label: 'File' },
    { key: 'status', label: 'Status' },
    { key: 'progress', label: 'Progress' },
    { key: 'created_at', label: 'Date' }
]

// Export
const exporting = ref(false)
const exportForm = ref({
    format: 'csv',
    categories: [],
    status: '',
    fields: ['basic', 'pricing', 'inventory']
})
const exportFormats = [
    { value: 'csv', label: 'CSV' },
    { value: 'xlsx', label: 'Excel (XLSX)' },
    { value: 'json', label: 'JSON' }
]
const statusOptions = [
    { value: '', label: 'All Statuses' },
    { value: 'active', label: 'Active' },
    { value: 'inactive', label: 'Inactive' },
    { value: 'draft', label: 'Draft' }
]

// Price Update
const priceForm = ref({
    type: '',
    value: '',
    apply_to: 'all',
    categories: []
})
const pricePreview = ref(null)
const applyingPrice = ref(false)
const priceHistory = ref([])
const priceUpdateTypes = [
    { value: 'percentage_increase', label: 'Percentage Increase' },
    { value: 'percentage_decrease', label: 'Percentage Decrease' },
    { value: 'fixed_increase', label: 'Fixed Amount Increase' },
    { value: 'fixed_decrease', label: 'Fixed Amount Decrease' },
    { value: 'set_price', label: 'Set Fixed Price' }
]
const applyToOptions = [
    { value: 'all', label: 'All Products' },
    { value: 'category', label: 'By Category' },
    { value: 'selected', label: 'Selected Products' }
]
const pricePreviewFields = [
    { key: 'name', label: 'Product' },
    { key: 'old_price', label: 'Current' },
    { key: 'new_price', label: 'New' }
]
const priceHistoryFields = [
    { key: 'type', label: 'Type' },
    { key: 'value', label: 'Value' },
    { key: 'affected_products', label: 'Products' },
    { key: 'created_by', label: 'By' },
    { key: 'created_at', label: 'Date' }
]

// Status Update
const statusForm = ref({
    status: '',
    apply_to: 'all',
    categories: [],
    product_ids: ''
})
const applyingStatus = ref(false)
const productStatuses = [
    { value: 'active', label: 'Active' },
    { value: 'inactive', label: 'Inactive' },
    { value: 'draft', label: 'Draft' },
    { value: 'out_of_stock', label: 'Out of Stock' }
]

// SEO
const seoForm = ref({
    apply_to: 'missing',
    categories: [],
    overwrite: false
})
const generatingSeo = ref(false)
const seoApplyOptions = [
    { value: 'missing', label: 'Products without SEO data' },
    { value: 'all', label: 'All Products' },
    { value: 'category', label: 'By Category' }
]

// Helpers
const formatNumber = (num) => Number(num || 0).toLocaleString()

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

const getJobStatusVariant = (status) => {
    const variants = {
        pending: 'warning',
        processing: 'info',
        completed: 'success',
        failed: 'danger'
    }
    return variants[status] || 'secondary'
}

// Fetch
const fetchCategories = async () => {
    try {
        const response = await axiosEmployee.get('/category')
        if (response.data) {
            categories.value = response.data.data || response.data || []
        }
    } catch (error) {
        console.error('Error fetching categories:', error)
    }
}

const fetchImportJobs = async () => {
    try {
        const response = await axiosEmployee.get('/admin/products/bulk/import/jobs')
        if (response.data.success) {
            importJobs.value = response.data.data || []
        }
    } catch (error) {
        console.error('Error fetching import jobs:', error)
    }
}

const fetchPriceHistory = async () => {
    try {
        const response = await axiosEmployee.get('/admin/products/bulk/price/history')
        if (response.data.success) {
            priceHistory.value = response.data.data || []
        }
    } catch (error) {
        console.error('Error fetching price history:', error)
    }
}

// Actions
const downloadTemplate = async () => {
    try {
        const response = await axiosEmployee.get('/admin/products/bulk/import-template', {
            responseType: 'blob'
        })
        const url = window.URL.createObjectURL(new Blob([response.data]))
        const link = document.createElement('a')
        link.href = url
        link.setAttribute('download', 'product_import_template.csv')
        document.body.appendChild(link)
        link.click()
        link.remove()
    } catch (error) {
        toast.error('Failed to download template')
    }
}

const validateImportFile = async () => {
    if (!importFile.value) return

    const formData = new FormData()
    formData.append('file', importFile.value)

    try {
        const response = await axiosEmployee.post('/admin/products/bulk/import/upload', formData, {
            headers: { 'Content-Type': 'multipart/form-data' }
        })
        if (response.data.success) {
            importPreview.value = response.data.data
        }
    } catch (error) {
        toast.error(error.response?.data?.message || 'Failed to validate file')
        importPreview.value = null
    }
}

const processImport = async () => {
    if (!importPreview.value?.job_id) return

    importing.value = true
    try {
        const response = await axiosEmployee.post(`/admin/products/bulk/import/${importPreview.value.job_id}/process`)
        if (response.data.success) {
            toast.success('Import started successfully')
            importFile.value = null
            importPreview.value = null
            fetchImportJobs()
        }
    } catch (error) {
        toast.error(error.response?.data?.message || 'Failed to start import')
    } finally {
        importing.value = false
    }
}

const exportProducts = async () => {
    exporting.value = true
    try {
        const response = await axiosEmployee.post('/admin/products/bulk/export', exportForm.value, {
            responseType: 'blob'
        })
        const ext = exportForm.value.format === 'xlsx' ? 'xlsx' : exportForm.value.format === 'json' ? 'json' : 'csv'
        const url = window.URL.createObjectURL(new Blob([response.data]))
        const link = document.createElement('a')
        link.href = url
        link.setAttribute('download', `products_export_${Date.now()}.${ext}`)
        document.body.appendChild(link)
        link.click()
        link.remove()
        toast.success('Export completed')
    } catch (error) {
        toast.error('Failed to export products')
    } finally {
        exporting.value = false
    }
}

const previewPriceUpdate = async () => {
    try {
        const response = await axiosEmployee.post('/admin/products/bulk/price/preview', priceForm.value)
        if (response.data.success) {
            pricePreview.value = response.data.data
        }
    } catch (error) {
        toast.error(error.response?.data?.message || 'Failed to preview changes')
    }
}

const applyPriceUpdate = async () => {
    if (!confirm('Are you sure you want to apply these price changes?')) return

    applyingPrice.value = true
    try {
        const response = await axiosEmployee.post('/admin/products/bulk/price/apply', priceForm.value)
        if (response.data.success) {
            toast.success(`Price updated for ${response.data.data.affected_products} products`)
            pricePreview.value = null
            priceForm.value = { type: '', value: '', apply_to: 'all', categories: [] }
            fetchPriceHistory()
        }
    } catch (error) {
        toast.error(error.response?.data?.message || 'Failed to apply price changes')
    } finally {
        applyingPrice.value = false
    }
}

const applyStatusUpdate = async () => {
    if (!confirm('Are you sure you want to update product statuses?')) return

    applyingStatus.value = true
    try {
        const data = {
            ...statusForm.value,
            product_ids: statusForm.value.product_ids
                ? statusForm.value.product_ids.split(',').map(id => parseInt(id.trim())).filter(id => !isNaN(id))
                : []
        }
        const response = await axiosEmployee.post('/admin/products/bulk/status', data)
        if (response.data.success) {
            toast.success(`Status updated for ${response.data.data.affected_products} products`)
            statusForm.value = { status: '', apply_to: 'all', categories: [], product_ids: '' }
        }
    } catch (error) {
        toast.error(error.response?.data?.message || 'Failed to update statuses')
    } finally {
        applyingStatus.value = false
    }
}

const generateSeo = async () => {
    if (!confirm('This will generate SEO data for selected products. Continue?')) return

    generatingSeo.value = true
    try {
        const response = await axiosEmployee.post('/admin/products/bulk/seo/generate', seoForm.value)
        if (response.data.success) {
            toast.success(`SEO generated for ${response.data.data.affected_products} products`)
        }
    } catch (error) {
        toast.error(error.response?.data?.message || 'Failed to generate SEO')
    } finally {
        generatingSeo.value = false
    }
}

// Lifecycle
onMounted(() => {
    fetchCategories()
    fetchImportJobs()
    fetchPriceHistory()
})
</script>

<style scoped>
.import-guidelines, .seo-info {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
}
.import-guidelines ul, .seo-info ul {
    padding-left: 20px;
    margin-bottom: 0;
}
.import-guidelines li, .seo-info li {
    margin-bottom: 8px;
}
.price-preview {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
}
</style>
