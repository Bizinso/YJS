<template>

    <div class="listing_screen global_table_liting">
        <div class="masterTabs">
        <div class="masterTabContent">
            <div v-if="showList">


                <div class="listing_tab_and_actions mb-3">
                    <div class="listing_actions">
                        <div class="d-flex">
                            <div class="listing_search">
                                <img src="../../assets/img/header/search.svg" class="listing_search_icon" alt="search" />
                                <b-form-input v-model="search.globalSearch" @input="searchFilter"
                                    placeholder="Search Here..." />
                            </div>
                            <b-button title="filter" class="btn_listing_action"
                                @click="sidebarstatus.filter = !sidebarstatus.filter">
                                <img src="../../assets/img/filter.svg" alt="filter" /> Filter
                            </b-button>
                        </div>
                        <div class="buttonGrid">
                            <b-button v-if="$can('do', 'add_admin.products')" class="fillBTN"
                                @click="addNew">Add</b-button>
                            <b-button-group class="buttonGrid">
                                <b-button v-if="$can('do', 'import_admin.products')" class="transBTN"
                                    v-b-tooltip.hover title="Bulk Upload" @click="sidebarImport = !sidebarImport"><img
                                        src="../../assets/img/upload.svg" width="16px" alt="filter" /></b-button>
                                <b-button v-if="$can('do', 'export_admin.products')" class="transBTN"
                                    v-b-tooltip.hover title="Download Data" @click="exportProduct()"><img
                                        src="../../assets/img/download.svg" width="16px" alt="filter" /></b-button>
                            </b-button-group>

                  
                            <b-button class="fillBTN" @click="openDeleteModal()" :disabled="selectedItems.length < 2">
                                Delete
                            </b-button>

                        </div>
                    </div>

                    <div :class="{ parentBackground: sidebarstatus.filter }">
                        <div class="filter_sidebar sidebar_main" :class="[sidebarstatus.filter ? 'filter_active' : '']">
                            <div class="sidebar_toolbox p-3">
                                <h6>Filter</h6>
                                <CloseIcon @click="resetFilter" />
                            </div>
                            <div class="sidebar_form">
                                <b-form>
                                    <div class="px-4 py-3 column_sidebar">
                                        <label>Product Name</label>
                                        <b-form-group>
                                            <b-form-input v-model="search.product_name_filter"
                                                placeholder="Enter Product Name..." trim />
                                        </b-form-group>

                                        <b-form-group label="Product Type" label-for="name">
                                            <v-select v-model="search.product_type_id" :options="ProductTypeOptions"
                                                :reduce="(val) => val.value" label="label" :clearable="true"
                                                placeholder="Select Product Type" />
                                        </b-form-group>

                                        <b-form-group label="Category" label-for="name">
                                            <v-select v-model="search.category_id" @update:modelValue="onCategoryChange"
                                                :options="CategoryOptions" :reduce="(val) => val.value" label="label"
                                                :clearable="true" placeholder="Select Category" />
                                        </b-form-group>

                                        <b-form-group label="Sub Category" label-for="name">
                                            <v-select v-model="search.sub_category_id" :options="SubCategoryOptions"
                                                :reduce="(val) => val.value" label="label" :clearable="true"
                                                placeholder="Select Category" />
                                        </b-form-group>

                                        <!-- Inside the filter sidebar form -->
                                        <b-form-group label="Visibility" label-for="visibility">
                                            <v-select v-model="search.visibility_filter" :options="visibilityOptions"
                                                :reduce="(val) => val.value" label="label" :clearable="true"
                                                placeholder="Select Visibility" />
                                        </b-form-group>

                                        <b-form-group label-for="created_date" class="DateField">
                                            <label>Created Date</label>

                                            <VueDatePicker v-model="search.created_date" :enable-time-picker="false"
                                                placeholder="Select Date" auto-apply class="breack" />

                                        </b-form-group>

                                        <b-form-group label="Status" label-for="status">
                                            <v-select v-model="search.status_filter" :options="statusOptionsFilter"
                                                :reduce="(val) => val.value" label="label" :clearable="true"
                                                placeholder="Select Status" />
                                        </b-form-group>

                                    </div>
                                    <div class="sidebarbtn_group">
                                        <b-button type="submit" class="btn_primary me-2"
                                            @click="searchFilter">Apply</b-button>
                                        <b-button class="btn_secondary_border" @click="resetFilter">Reset</b-button>
                                    </div>
                                </b-form>
                            </div>
                        </div>
                    </div>

                    <div :class="{ parentBackground: sidebarImport }">
                        <div class="filter_sidebar sidebar_main" :class="[sidebarImport ? 'filter_active' : '']">
                            <div class="sidebar_toolbox woBorder">
                                <h6>Import File</h6>
                                <CloseIcon @click="closeImportSidebar" />
                            </div>
                            <div class="sidebar_form">
                                <b-form @submit.prevent="uploadFile">
                                    <div class="px-4 py-3 column_sidebar">


                                        <b-form-group>
                                            <div class="image-upload-wrapper text-center">
                                                <!-- Hidden file input -->
                                                <input type="file" ref="bulkFileInput" @change="handleBulkFileSelect"
                                                    accept=".csv, application/vnd.openxmlformats-officedocument.spreadsheetml.sheet, application/vnd.ms-excel"
                                                    class="d-none" />

                                                <!-- Display when a file is selected -->
                                                <div v-if="bulkFile" class="thumbnail-grid selected-file noRpaoo">
                                                    <div
                                                        class="file-info d-flex justify-content-between align-items-center p-2 border rounded gap-2">
                                                        <img src="../../assets/img/xcl.svg" alt="Upload file"
                                                            class="iconBox" />
                                                        <div class="centerPiece">
                                                            <div class="innerNames">
                                                                <h3>{{ bulkFile.name }}</h3>
                                                                <span>{{ (bulkFile.size / 1024).toFixed(2) }} KB</span>
                                                            </div>
                                                        </div>
                                                        <button class="btn btn-sm btn-outline-danger removeBox"
                                                            @click="removeBulkFile">✕</button>
                                                    </div>
                                                </div>

                                                <!-- Placeholder for upload when no file -->
                                                <div v-else class="thumbnail-container upload-placeholder"
                                                    @click="triggerFileSelect">
                                                    <img src="../../assets/img/uploadfile.png" alt="Upload file"
                                                        class="w-100" />
                                                </div>
                                            </div>
                                        </b-form-group>

                                        <div class="mt-3">
                                            <a href="#" @click.prevent="downloadTemplate" class="sampleLink">
                                                Click here to download the sample Excel file.
                                            </a>
                                        </div>
                                    </div>

                                    <div class="sidebarbtn_group">
                                        <div class="buttonGrid">
                                            <b-button type="submit" class="fillBTN"
                                                :disabled="!bulkFile">Upload</b-button>
                                            <b-button class="transBTN" @click="closeImportSidebar">Cancel</b-button>
                                        </div>
                                    </div>
                                </b-form>
                            </div>
                        </div>
                    </div>
                </div>



                <div class="filter_sidebar sidebar_main"
                    :class="[sidebarstatus.inventoryHistory ? 'filter_active' : '']">
                    <div class="sidebar_toolbox p-3">
                        <h6>Inventory History</h6>
                        <CloseIcon @click="sidebarstatus.inventoryHistory = false" />
                    </div>
                    <div class="sidebar_form">
                        <div class="px-4 py-3">
                            <div v-if="!currentInventoryProduct" class="text-center">
                                <b-spinner variant="#404054" label="Loading..."></b-spinner>
                            </div>

                            <div v-else class="inventory-summary">
                                <div class="d-flex justify-content-between">
                                    <span>Total Stock:</span>
                                    <strong>{{ computedTotalStock }}</strong>
                                </div>
                                <div class="d-flex justify-content-between mt-3">
                                    <span>Last Update Stock:</span>
                                    <strong>{{ formatDate(currentInventoryProduct.updated_at) }}</strong>
                                </div>
                                <div class="d-flex justify-content-between mt-3">
                                    <span>Status:</span>
                                    <strong>{{ currentInventoryProduct.status || 'N/A' }}</strong>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>


                <div v-if="loading" class="text-center p-5">
                    <b-spinner variant="#404054" label="Loading..."></b-spinner>
                    <p class="mt-2">Loading products...</p>
                </div>

                <div v-else>

                    <div class="filter_selected px-4" v-if="inqFilterStatus">
                        <span class="selected_filter_item_icon me-2"><i class="fa-solid fa-sliders"></i></span>

                        <span class="selected_filter_item" v-if="search.product_name_filter != null">{{
                            getProductName(search.product_name_filter) }} <i class="fa-solid fa-xmark"
                                @click="() => { search.product_name_filter = null; searchFilter() }"></i></span>

                        <span class="selected_filter_item" v-if="search.product_type_id != null">{{
                            getfilterProductType(search.product_type_id) }} <i class="fa-solid fa-xmark"
                                @click="() => { search.product_type_id = null; searchFilter() }"></i></span>

                        <span class="selected_filter_item" v-if="search.category_id != null">{{
                            getfilterCategory(search.category_id) }} <i class="fa-solid fa-xmark"
                                @click="() => { search.category_id = null; searchFilter() }"></i></span>

                        <span class="selected_filter_item" v-if="search.sub_category_id != null">{{
                            getfilterSubCategory(search.sub_category_id) }} <i class="fa-solid fa-xmark"
                                @click="() => { search.sub_category_id = null; searchFilter() }"></i></span>
                        <span class="selected_filter_item" v-if="search.visibility_filter != null">{{
                            getfilterVisibilityFilter(search.visibility_filter) }} <i class="fa-solid fa-xmark"
                                @click="() => { search.visibility_filter = null; searchFilter() }"></i></span>
                        <span class="selected_filter_item" v-if="search.status_filter != null">{{
                            getfilterStatusFilter(search.status_filter) }} <i class="fa-solid fa-xmark"
                                @click="() => { search.status_filter = null; searchFilter() }"></i></span>
                        <span class="selected_filter_item" v-if="search.created_date != null">{{
                            formatDisplayDate(search.created_date) }} <i class="fa-solid fa-xmark"
                                @click="() => { search.created_date = null; searchFilter() }"></i></span>
                        <span class="selected_filter_item" v-if="search.globalSearch != null">{{ (search.globalSearch)
                            }} <i class="fa-solid fa-xmark"
                                @click="() => { search.globalSearch = null; searchFilter('remove') }"></i></span>
                    </div>

                    <b-table responsive="sm" :items="paginatedData" :fields="fields" @sort-changed="onSortChanged"
                        v-if="$can('do', 'list_admin.products') && paginatedData.length > 0">
                      
                        <template #head(selectAll)>
                            <b-form-checkbox v-model="selectAllChecked" aria-label="Select all" />
                        </template>

                        <template #cell(selectAll)="row">
                            <b-form-checkbox v-model="selectedItems" :value="row.item.id" aria-label="Select row" />
                        </template>

                        <template #cell(main_image)="row">
                            <b-link :to="`/admin/products/view/${encodeBase64(row.item.id)}`">
                                <img :src="row.item.main_image
                                        ? `/storage/${row.item.main_image}`
                                        : placeholderImage
                                    " alt="Product Image" style="width: 50px; height: 50px; object-fit: cover" />
                            </b-link>
                        </template>

                        <template #head(name)>
                            <span @click="changeSorting('name')" style="cursor: pointer">
                                Product Name/SKU/Product Type <i :class="getSortIcon('name')"></i>
                            </span>
                        </template>
                        <template #cell(name)="row">
                            <b-link class="table_linking"
                                :to="`/admin/products/view/${encodeBase64(row.item.id)}`">
                                {{ row.item.name }}<br />{{ row.item.sku }}/{{ row.item.product_type_name }}
                            </b-link>
                        </template>

                        <!-- Visibility header -->
                        <template #head(visibility)>
                            <span @click="changeSorting('visibility')" style="cursor: pointer">
                                Visibility <i :class="getSortIcon('visibility')"></i>
                            </span>
                        </template>

                        <template #cell(visibility)="row">
                            {{ row.item.visibility == '1' ? 'Yes' : 'No' }}
                        </template>

                        <!-- Featured header -->
                        <template #head(is_featured)>
                            <span @click="changeSorting('is_featured')" style="cursor: pointer">
                                Featured <i :class="getSortIcon('is_featured')"></i>
                            </span>
                        </template>

                       
                        <template #cell(is_featured)="row">
                            <b-form-checkbox :checked="row.item.is_featured == '1'"
                                @change="toggleFeaturedStatus(row.item)" />
                        </template>


                        <!-- Created Date header -->
                        <template #head(created_at)>
                            <span @click="changeSorting('created_at')" style="cursor: pointer">
                                Created Date <i :class="getSortIcon('created_at')"></i>
                            </span>
                        </template>

                        <template #cell(created_at)="row">
                            {{ moment(row.item.created_at).format('DD/MM/YYYY') }}
                        </template>




                        <template #head(variant_count)>
                            <span> Variant</span>
                        </template>

                        <template #cell(variant_count)="row">
                            <span v-if="row.item.variant_count > 0" @click="row.toggleDetails">
                                {{ row.item.variant_count }}
                            </span>
                            <span v-else>-</span>
                        </template>

                        <!-- For category_name -->
                        <template #head(category_name)>
                            <span @click="changeSorting('category_name')" style="cursor: pointer">
                                Category <i :class="getSortIcon('category_name')"></i>
                            </span>
                        </template>

                        <!-- For sub_category_name -->
                        <template #head(sub_category_name)>
                            <span @click="changeSorting('sub_category_name')" style="cursor: pointer">
                                Sub Category <i :class="getSortIcon('sub_category_name')"></i>
                            </span>
                        </template>

                        <!-- For status -->
                        <template #head(status)>
                            <span @click="changeSorting('status')" style="cursor: pointer">
                                Status <i :class="getSortIcon('status')"></i>
                            </span>
                        </template>

                        <template #cell(status)="data">
                            <span
                                :class="{
                                'text-success': data.item.status === 'active',
                                'text-danger': data.item.status === 'inactive',
                                'text-warning': data.item.status === 'draft',
                                }"
                                style="cursor: pointer"
                                @click="openChangeStatusModal(data.item)"
                            >
                                {{
                                data.item.status === 'active'
                                    ? 'Active'
                                    : data.item.status === 'inactive'
                                    ? 'Inactive'
                                    : 'Draft'
                                }}
                            </span>
                            </template>


                        <template #cell(actions)="row">
                            <b-dropdown right text="⋮" variant="link" no-caret toggle-class="p-0">
                                <b-dropdown-item v-if="$can('do', 'edit_admin.products')"
                                    @click="editProduct(row.item)">Edit</b-dropdown-item>

                                <b-dropdown-item v-if="$can('do', 'delete_admin.products')"
                                    @click="openDeleteModal(row.item.id)">Delete</b-dropdown-item>
                                <b-dropdown-item v-if="$can('do', 'view_admin.products')"
                                    :to="`/admin/products/view/${encodeBase64(row.item.id)}`">Product
                                    Details</b-dropdown-item>
    
                                <b-dropdown-item @click="duplicateProduct(row.item)">Duplicate Product</b-dropdown-item>
                            </b-dropdown>
                        </template>

                        <template #row-details="row">
                            <div v-if="row.item.variant_count > 0">
                                <b-card>
                                    <b-table responsive="sm" small :items="parseVariants(row.item.variants)"
                                        :fields="variantFields">
                                        <template #cell(variant_name)="row">
                                            <span>
                                                {{ row.item.variant_name.replace(/"/g, '') }}
                                            </span>
                                        </template>
                                        <template #cell(sku)="row">
                                            <span>
                                                {{ row.item.sku.replace(/"/g, '') }}
                                            </span>
                                        </template>
                                        <template #cell(status)="row">
                                            <span>
                                                {{
                                                    { '"active"': 'Active', '"inactive"': 'Inactive', '"draft"': 'Draft' }[row.item.status]
                                                || row.item.status.replace(/"/g, '')
                                                }}
                                            </span>
                                        </template>


                                    </b-table>

                                    <b-button size="sm" variant="outline-secondary" @click="row.toggleDetails">Hide
                                        Details</b-button>
                                </b-card>
                            </div>
                        </template>
                    </b-table>

                    <productNoData v-if="inqFilterStatus && paginatedData.length === 0 || !inqFilterStatus && sidebarstatus.filter" heading="No Records Found"
                        subheading="Try adjusting your filters or search criteria." :showButton="false" />

                    <productNoData v-else-if="paginatedData.length === 0" heading="No Product Created Yet"
                        subheading="You haven't added any products yet. Add your first product to get started."
                        :showButton="$can('do', 'add_admin.products')" buttonText="Add" @button-clicked="addNew" />



                    <b-modal id="delete-modal" class="text-center" v-model="deleteModal" hide-header hide-footer
                        size="md" centered>
                        <h6 class="mb-3">
                            {{ deleteType === 'single'
                                ? 'Are you sure you want to delete this product?'
                                : `Are you sure you want to delete ${selectedItems.length} selected products?` }}
                        </h6>
                        <b-button class="btn_secondary_border me-2" @click="deleteModal = false">
                            Cancel
                        </b-button>
                        <b-button class="btn_primary" @click="confirmDelete">
                            Delete
                        </b-button>
                    </b-modal>



                    <div class="tablecounter" v-if="paginatedData.length > 0">
                        <div class="show_entries">
                            <span class="mr-2 count">Show</span>
                            <v-select append-to-body :calculate-position="withPopper" @update:modelValue="resetPage"
                                v-model="perPage" :options="perPageOptions" :clearable="false" class="mr-2" />
                            <span class="count">entries</span>
                        </div>


                        <div class="count">
                            Showing {{ startItem }} to {{ endItem }} of {{ allData.length }}
                        </div>

                        <nav aria-label="Page navigation" class="paginarton">
                            <ul class="pagination">
                                <!-- Previous button -->
                                <li class="page-item" :class="{ disabled: currentPage === 1 }"
                                    @click="changePage(currentPage - 1)">
                                    <a class="page-link" href="#" tabindex="-1">Previous</a>
                                </li>

                                <!-- Page numbers -->
                                <li v-for="page in paginationRange" :key="page.key" class="page-item" :class="{
                                    active: page.number === currentPage,
                                    disabled: page.isEllipsis,
                                }" @click="!page.isEllipsis && changePage(page.number)" style="cursor: pointer">
                                    <a class="page-link" href="#">
                                        {{ page.isEllipsis ? "..." : page.number }}
                                    </a>
                                </li>

                                <!-- Next button -->
                                <li class="page-item" :class="{ disabled: currentPage === totalPages }"
                                    @click="changePage(currentPage + 1)">
                                    <a class="page-link" href="#">Next</a>
                                </li>
                            </ul>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
        </div>
        <b-modal v-model="showStatusModal" hide-header hide-footer centered size="md" class="customModal"
            title="Change Status">
            <div class="contentFrame">
                Are you sure you want to change the status ?
            </div>

            <div class="contentFrame">
                <p class="mb-2">Select new status:</p>

                <v-select v-model="selectedStatus" :options="statusOptionsFilter" label="label"
                    :reduce="(option) => option.value" class="mb-1 multiDrop" @update:modelValue="statusError = ''" />

                <!-- 🔴 Error message under input -->
                <small v-if="statusError" class="text-danger">{{ statusError }}</small>
            </div>

            <div class="footerFrame buttonGrid">
                <b-button class="transBTN" @click="showStatusModal = false">
                    Cancel
                </b-button>
                <b-button class="fillBTN" @click="confirmChangeStatus">
                    Yes, Change
                </b-button>
            </div>
        </b-modal>

    </div>

</template>

<script setup>
import { ref, computed,onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import axiosEmployee from '@axiosEmployee';
import "vue-select/dist/vue-select.css"
import CloseIcon from "../../assets/img/icons/Close.vue"
import moment from "moment"
import { saveAs } from 'file-saver';
import VueDatePicker from "@vuepic/vue-datepicker";
import "@vuepic/vue-datepicker/dist/main.css";
import { createPopper } from '@popperjs/core';
import { toast } from "vue3-toastify";
import productNoData from "../../components/noData.vue";
import placeholderImageURL from '../../assets/img/icons/placeholder.jpg';
const router = useRouter()
const showList = ref(true)
const showStatusModal = ref(false);
const selectedItem = ref(null);
const selectedStatus = ref(false);

const allData = ref([])
const currentPage = ref(1)
const perPage = ref(10)
const selectedItems = ref([])
const placeholderImage = placeholderImageURL


const fields = [
    { key: "selectAll", label: "", sortable: false },
    { key: "main_image", label: "Image", sortable: false },
    { key: "name", label: "Product Name", sortable: true },
    { key: "category_name", label: "Category Name", sortable: true },
    { key: "sub_category_name", label: "Sub Category Name", sortable: true },
    { key: "visibility", label: "Visibility", sortable: true },
    { key: "is_featured", label: "Featured", sortable: true },
    { key: "created_at", label: "Created Date", sortable: true },
    { key: "status", label: "Status", sortable: true },
    { key: "variant_count", label: "Variant", sortable: true },
    { key: "actions", label: "Actions", sortable: false },
]

const variantFields = [
    { key: 'variant_name', label: 'Variant Name' },
    { key: 'metal_weight', label: 'Metal Wt' },
    { key: 'base_price', label: 'Base Price' },
    { key: 'sku', label: 'SKU' },
    { key: 'status', label: 'Status' },
    { key: 'product_price', label: 'Final Price' }
]

// Add to your refs
const visibilityOptions = ref([
    { label: 'Yes', value: 1 },
    { label: 'No', value: 0 }
])


const statusOptionsFilter = ref([
    { label: 'Active', value: 'active' },
    { label: 'Inactive', value: 'inactive' },
    { label: 'Draft', value: 'draft' }

])


// Filter and search
const search = ref({
    product_type_id: null,
    category_id: null,
    globalSearch: null,
    sub_category_id: null,
    product_name_filter: null,
    visibility_filter: null,
    created_date: null,
    status_filter: null,

})


const withPopper = (dropdownList, component, { width }) => {
    dropdownList.style.width = width;
    const popper = createPopper(component.$refs.toggle, dropdownList, {
        placement: 'bottom',
        modifiers: [
            {
                name: 'offset',
                options: {
                    offset: [0, -1],
                },
            },
            {
                name: 'toggleClass',
                enabled: true,
                phase: 'write',
                fn({ state }) {
                    component.$el.classList.toggle(
                        'drop-up',
                        state.placement === 'top'
                    )
                },
            },
        ],
    })
    return () => popper.destroy()
};

const sidebarstatus = ref({
    filter: false,
    shadow: false,
    add: false,
    assigned: false,
    inventoryHistory: false
})

const sidebarImport = ref(false)
const sampleExcelLink = ref("/files/sample-template.xlsx");



// Options for dropdowns
const CategoryOptions = ref([])
const SubCategoryOptions = ref([])
const ProductTypeOptions = ref([])

// Computed properties

const perPageOptions = [10, 25, 50, 100];

const totalPages = computed(() => Math.ceil(allData.value.length / perPage.value) || 1)

const paginatedData = computed(() => {
    const start = (currentPage.value - 1) * perPage.value
    return allData.value.slice(start, start + perPage.value)
})



const paginationRange = computed(() => {
    const total = totalPages.value
    const current = currentPage.value
    const delta = 2
    const range = []
    let l = 0

    for (let i = 1; i <= total; i++) {
        if (
            i === 1 ||
            i === total ||
            (i >= current - delta && i <= current + delta)
        ) {
            range.push({ number: i, key: i, isEllipsis: false })
            l = i
        } else if (l && i - l > 1) {
            range.push({ number: "...", key: `ellipsis-${i}`, isEllipsis: true })
            l = i
        }
    }

    return range
})

const startItem = computed(() => {
    if (allData.value.length === 0) return 0
    return (currentPage.value - 1) * perPage.value + 1
})

const endItem = computed(() =>
    Math.min(currentPage.value * perPage.value, allData.value.length)
)

// Sorting
const sortBy = ref("")
const sortDesc = ref(false)

const resetPage = () => {
    currentPage.value = 1; // Reset to first page
    fetchProducts();
};

// Methods
const loading = ref(true);

const fetchProducts = async () => {
    loading.value = true;
    try {
        const response = await axiosEmployee.get("/products", {
            params: {
                sortBy: sortBy.value,
                sortDesc: sortDesc.value,
                product_type_id: search.value.product_type_id,
                category_id: search.value.category_id,
                sub_category_id: search.value.sub_category_id,
                globalSearch: search.value.globalSearch,
                product_name_filter: search.value.product_name_filter,
                visibility_filter: search.value.visibility_filter,
                status_filter: search.value.status_filter,

                created_date: search.value.created_date,

            },
        })
        allData.value = response.data.data
        emit("updateCount", allData.value.length);

        loading.value = false;
    } catch (error) {
        loading.value = false;
        console.error("Error fetching products:", error)
    }
}


const formatDisplayDate = (date) => {
    if (!date) return '';

    // Handle both Date objects and string dates
    const dateObj = typeof date === 'string' ? new Date(date) : date;

    // Format as DD/MM/YYYY
    const day = String(dateObj.getDate()).padStart(2, '0');
    const month = String(dateObj.getMonth() + 1).padStart(2, '0');
    const year = dateObj.getFullYear();

    return `${day}/${month}/${year}`;
};

const searchFilter = () => {
    currentPage.value = 1
    fetchProducts()
    sidebarstatus.value.filter = false
}

const resetFilter = () => {
    search.value = {
        name: null,
        product_type_id: null,
        category_id: null,
        sub_category_id: null,
        globalSearch: null,
        product_name_filter: null,
        visibility_filter: null,
        status_filter: null,
        created_date: null
    }
    fetchProducts()
    sidebarstatus.value.filter = false
}

const changePage = (page) => {
    if (page < 1 || page > totalPages.value) return
    currentPage.value = page
    selectAllChecked.value = false
    selectedItems.value = []
}

const emit = defineEmits(["updateCount"]);


const selectAllChecked = computed({
    get() {
        return paginatedData.value.length > 0 &&
            paginatedData.value.every(item => selectedItems.value.includes(item.id));
    },
    set(value) {
        if (value) {
            selectedItems.value = paginatedData.value.map(item => item.id);
        } else {
            selectedItems.value = selectedItems.value.filter(
                id => !paginatedData.value.find(item => item.id === id)
            );
        }
    }
});
const openChangeStatusModal = (item) => {
    selectedItem.value = item;
    selectedStatus.value = item.status;
    showStatusModal.value = true;
};
const statusError = ref("");


const confirmChangeStatus = async () => {
    // 🔍 Validation before API call
    if (!selectedStatus.value) {
        statusError.value = "Please select a status before updating.";
        return; // stop here if blank
    }

    statusError.value = ""; // clear error if valid

    try {
        const response = await axiosEmployee.post(
            `products/${selectedItem.value.id}/change-status`,
            { status: selectedStatus.value }
        );

        if (response.data.status === "success") {
            toast.success(response.data.message || "Status updated successfully!");
            fetchProducts();
        } else {
            toast.error(response.data.message || "Failed to update status");
        }
    } catch (error) {
        toast.error(error.response?.data?.message || "Failed to update status");
    } finally {
        showStatusModal.value = false;
    }
};

const changeSorting = (field) => {
    if (sortBy.value === field) {
        sortDesc.value = !sortDesc.value
    } else {
        sortBy.value = field
        sortDesc.value = false
    }
    fetchProducts()
}

const getSortIcon = (field) => {
    if (sortBy.value === field) {
        return sortDesc.value ? "fas fa-sort-down" : "fas fa-sort-up"
    }
    return "fas fa-sort"
}

const onSortChanged = (ctx) => {
    sortBy.value = ctx.sortBy
    sortDesc.value = ctx.sortDesc
}


// For bulk file upload in the sidebar
const bulkFile = ref(null);
const bulkFileInput = ref(null);

const handleBulkFileSelect = (event) => {
    const file = event.target.files[0];
    if (file) {
        bulkFile.value = file;
    }
};

const removeBulkFile = () => {
    bulkFile.value = null;
    // Reset the file input so that if the same file is selected again, the change event will fire
    if (bulkFileInput.value) {
        bulkFileInput.value.value = "";
    }
};

const triggerFileSelect = () => {
    if (bulkFileInput.value) {
        bulkFileInput.value.click();
    }
};

const closeImportSidebar = () => {
    sidebarImport.value = false;
    // Also reset the bulk file and the input
    bulkFile.value = null;
    if (bulkFileInput.value) {
        bulkFileInput.value.value = "";
    }
};

const downloadTemplate = async () => {
    try {
        const response = await axiosEmployee.get('/download-import-template', {
            responseType: 'blob'
        });

        const url = window.URL.createObjectURL(new Blob([response.data]));
        const link = document.createElement('a');
        link.href = url;
        link.setAttribute('download', 'product_import_template.xlsx');
        document.body.appendChild(link);
        link.click();
        link.remove();
    } catch (error) {
        console.error('Template download error:', error);
    }
};

const uploadFile = async () => {
    if (!bulkFile.value) return;

    const formData = new FormData();
    formData.append('file', bulkFile.value);

    try {
        const response = await axiosEmployee.post('/import-product', formData, {
            headers: {
                'Content-Type': 'multipart/form-data'
            }
        });

        // Close the sidebar and reset
        closeImportSidebar();

        // Refresh product list
        fetchProducts();

        // Show success message
        alert('Products imported successfully!');

    } catch (error) {
        console.error('Import error:', error);
        alert('Import failed: ' + (error.response?.data?.message || error.message));
    }
};





// export product

const exportProduct = async (item = null) => {
    try {
        const params = {
            // Always pass current filters
            product_type_id: search.value.product_type_id,
            category_id: search.value.category_id,
            globalSearch: search.value.globalSearch,
            product_name_filter: search.value.product_name_filter,
        };

        if (item) {
            // For single product
            params.id = encodeBase64(item.id);
        }

        const response = await axiosEmployee.get("/export-product", {
            params,
            responseType: "blob",
        });

        const blob = new Blob([response.data], {
            type: "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
        });

        const fileName = item
            ? `product-${item.sku}.xlsx`
            : `products-${new Date().toISOString().slice(0, 10)}.xlsx`;

        saveAs(blob, fileName);
    } catch (error) {
        console.error("Export error:", error);
        // Add error notification here
    }
};



const addNew = () => {
    router.push('/admin/products/add')
}

const editProduct = (item) => {
    router.push(`/admin/products/edit/${encodeBase64(item.id)}`)
}

const encodeBase64 = (data) => {
    if (data === undefined || data === null) {
        return "";
    }
    return btoa(data.toString());
};




const deleteModal = ref(false);
const deleteId = ref(null);
const deleteType = ref('single'); // 'single' or 'multiple'

const openDeleteModal = (id = null) => {
    if (id) {
        deleteId.value = id;
        deleteType.value = 'single';
    } else {
        deleteType.value = 'multiple';
    }
    deleteModal.value = true;
};

const confirmDelete = () => {
    let idsToDelete = [];

    if (deleteType.value === 'single') {
        idsToDelete = [deleteId.value];
    } else {
        idsToDelete = [...selectedItems.value];
    }

    if (idsToDelete.length === 0) {
        deleteModal.value = false;
        return;
    }else{
        deleteModal.value = true;
    }

    axiosEmployee
        .post('/products/bulk-delete', { ids: idsToDelete })
        .then(() => {
            deleteModal.value = false;
            deleteId.value = null;
            selectedItems.value = [];
            selectAllChecked.value = false;
            fetchProducts();
        })
        .catch((error) => console.error("Error deleting products:", error));
};


// Inventory History Sidebar 

const currentInventoryProduct = ref(null);

const openInventoryHistory = (item) => {

    currentInventoryProduct.value = item;
    sidebarstatus.value.inventoryHistory = true;
};

const formatDate = (date) => {
    return moment(date).format('DD/MM/YYYY');
};


const computedTotalStock = computed(() => {
    if (!currentInventoryProduct.value) return 0;

    const product = currentInventoryProduct.value;

    // For simple products without variants
    if (!product.variant_options || product.product_type_name !== 'Configurable') {
        return product.total_stock || 0;
    }

    try {
        // Parse variant options JSON
        const variants = JSON.parse(product.variant_options);

        // Sum total_stock from all variants
        return variants.reduce((total, variant) => {
            const stock = Number(variant.total_stock) || 0;
            return total + stock;
        }, 0);

    } catch (error) {
        console.error('Error parsing variant options:', error);
        return product.total_stock || 0;
    }
});



const toggleFeaturedStatus = async (product) => {
    try {
        const newStatus = product.is_featured == '1' ? '0' : '1';

        const response = await axiosEmployee.post(`/products/${encodeBase64(product.id)}/toggle-featured`, {
            is_featured: newStatus,
        });

        product.is_featured = newStatus;

        // ✅ Show appropriate toast
        setTimeout(() => {
            if (newStatus === '1') {
                toast.success("Product is now featured and will appear in the featured section!", {
                    autoClose: 1000,
                });
            } else {
                toast.success("Product removed from featured list successfully.", {
                    autoClose: 1000,
                });
            }
        }, 300);

    } catch (error) {
        console.error("Error toggling featured status:", error);

        toast.error("Something went wrong while updating featured status!", {
            autoClose: 1500,
        });
    }
};









watch(sidebarstatus.value, async (newstatus, oldstatus) => {
    if (newstatus.filter || newstatus.add) {
        sidebarstatus.value.shadow = true;
    }
    else {
        sidebarstatus.value.shadow = false;
    }
});





const duplicateProduct = async (product) => {
    try {
        const response = await axiosEmployee.post(`/products/${encodeBase64(product.id)}/duplicate`);

        if (response.data.success) {
            // Refresh product list
            fetchProducts();
            // Show success notification
            showNotification('Product duplicated successfully!');
        } else {
            showNotification('Failed to duplicate product', 'error');
        }
    } catch (error) {
        console.error('Duplication error:', error);
        showNotification('Error duplicating product', 'error');
    }
};

// Helper function for notifications (you need to implement this)
const showNotification = (message, type = 'success') => {
    // Use your preferred notification system
    // Example: this.$toast[type](message);
};




const parseVariants = (variants) => {
    try {
        return JSON.parse(variants || '[]')
    } catch (e) {
        console.error('Invalid variants JSON', e)
        return []
    }
}

const getfilterProductType = (id) => {
    let filtername = null
    ProductTypeOptions.value.forEach((elm) => {
        if (elm.value == id) {
            filtername = elm.label
        }
    })
    return filtername
}

const getfilterCategory = (id) => {
    let filtername = null
    CategoryOptions.value.forEach((elm) => {
        if (elm.value == id) {
            filtername = elm.label
        }
    })
    return filtername
}
const getProductName = (id) => {
    let filtername = id
    return filtername
}


const getfilterSubCategory = (id) => {
    let filtername = null
    SubCategoryOptions.value.forEach((elm) => {
        if (elm.value == id) {
            filtername = elm.label
        }
    })
    return filtername
}

const getfilterVisibilityFilter = (id) => {
    let filtername = null
    visibilityOptions.value.forEach((elm) => {
        if (elm.value == id) {
            filtername = elm.label
        }
    })
    return filtername
}

const getfilterStatusFilter = (id) => {
    let filtername = null
    statusOptionsFilter.value.forEach((elm) => {
        if (elm.value == id) {
            filtername = elm.label
        }
    })
    return filtername
}



// Watchers
const inqFilterStatus = ref(false);
watch(() => search.value, (newValue) => {
    inqFilterStatus.value = false;

    for (const key in newValue) {
        const val = newValue[key];

        if (Array.isArray(val)) {
            if (val.length > 0) {
                inqFilterStatus.value = true;
                break;
            }
        } else if (val !== null && String(val).trim() !== '') {
            inqFilterStatus.value = true;
            break;
        }
    }
}, { deep: true });

watch(sidebarstatus.value, (newstatus) => {
    if (newstatus.filter || newstatus.add) {
        sidebarstatus.value.shadow = true
    }
    else {
        sidebarstatus.value.shadow = false
    }
})

// Lifecycle hooks
onMounted(async () => {
    await Promise.all([
        axiosEmployee.get("/CategoryOptions").then((response) => {
            CategoryOptions.value = response.data
        }),
        axiosEmployee.get("/ProductTypeOptions").then((response) => {
            ProductTypeOptions.value = response.data
        })
    ])

    fetchProducts()
})

function onCategoryChange(value) {

    search.category_id = value;
    search.sub_category_id = null; // Reset subcategory

    if (value) {
        axiosEmployee.get(`/SubCategoryOptions/${value}`).then((response) => {
            SubCategoryOptions.value = response.data;
        });
    } else {
        SubCategoryOptions.value = [];
    }
}

watch(() => search.category_id, (newVal) => {
    onCategoryChange(newVal)
})


</script>
