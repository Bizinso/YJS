<template>
    <div class="listing_screen global_table_liting">

        <h2 class="masterHeading">Employee</h2>
        <div class="listing_tab_and_actions mb-3">
            <div class="listing_actions">
                <div class="d-flex">
                    <div class="listing_search">
                        <img src="../../../assets/img/header/search.svg" class="listing_search_icon" alt="search" />
                        <b-form-input v-model="search.globalSearch" @input="searchFilter"
                            placeholder="Search Here..." />
                    </div>
                    <b-button title="filter" class="btn_listing_action"
                        @click="sidebarstatus.filter = !sidebarstatus.filter">
                        <img src="../../../assets/img/filter.svg" alt="filter" /> Filter
                    </b-button>
                </div>
                <div class="buttonGrid">
                    <b-button class="fillBTN" @click="openAddSidebar"
                        v-if="$can('do', 'add_admin.org.employees')">Add</b-button>
                    <b-button class="transBTN" v-b-tooltip.hover title="Bulk Upload"
                        @click="sidebarImport = !sidebarImport">
                        <img src="../../../assets/img/upload.svg" width="16px" alt="filter" /></b-button>
                    <b-button class="transBTN" v-b-tooltip.hover title="Download Data" @click="exportEmployee()">
                        <img src="../../../assets/img/download.svg" width="16px" alt="filter" /></b-button>
                    <b-button type="button" class="transBTN" @click="handleBackClick">Back</b-button>
                </div>
            </div>


            <div :class="{ parentBackground: sidebarstatus.filter }">
                <!-- Sidebar container -->
                <div class="filter_sidebar sidebar_main" :class="{ filter_active: sidebarstatus.filter }">
                    <div class="sidebar_toolbox woBorder">
                        <h6>Filter</h6>
                        <div class="sidebar_toolbox_right_side">
                            <CloseIcon @click="resetFilter" />
                        </div>
                    </div>

                    <div class="sidebar_form">
                        <b-form>
                            <div class="px-4 py-3 column_sidebar" id="filter_frm_sidebar">
                                <label>Employee Name </label>
                                <b-form-group>
                                    <v-select v-model="search.name" :options="EmployeeOption"
                                        :reduce="(val) => val.label" label="label" :clearable="true"
                                        placeholder="Search Employee Name..."></v-select>
                                </b-form-group>

                                <label>Email</label>
                                <b-form-group>
                                    <b-form-input v-model="search.email" placeholder="Search Email..." trim />
                                </b-form-group>

                                <label>Department </label>
                                <b-form-group>
                                    <v-select v-model="search.departmnt" @open="vSelectOpen"
                                        :options="DepartmentEmpOption" :reduce="(val) => val.value" label="label"
                                        :clearable="true" placeholder="Search Department Name..." multiple></v-select>
                                </b-form-group>

                                <label>Role </label>
                                <b-form-group>
                                    <v-select v-model="search.rolee" @open="vSelectOpen" :options="RoleEmpOption"
                                        :reduce="(val) => val.value" label="label" :clearable="true"
                                        placeholder="Search Role Name..." multiple></v-select>
                                </b-form-group>
                                <label>Employee Code </label>
                                <b-form-group>
                                    <v-select v-model="search.empCode" @open="vSelectOpen" :options="EmployeeCode"
                                        value="value" label="label" :reduce="(val) => val.label" multiple
                                        placeholder="Select Employee Code"></v-select>
                                </b-form-group>

                                <label>Reporting To </label>
                                <b-form-group>
                                    <v-select v-model="search.reporting_to" @open="vSelectOpen"
                                        :options="ReportingToOptionsFilter" :reduce="(val) => val.value" label="label"
                                        :clearable="true" placeholder="Search Reporting To..."></v-select>
                                </b-form-group>

                            </div>

                            <div class="sidebarbtn_group">
                                <div class="buttonGrid">
                                    <b-button type="submit" @click="searchFilter" class="fillBTN">Apply</b-button>
                                    <b-button class="transBTN" @click="resetFilter">Reset</b-button>
                                </div>
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
                                                <img src="../../../assets/img/xcl.svg" alt="Upload file"
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
                                            <img src="../../../assets/img/uploadfile.png" alt="Upload file"
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
                                    <b-button type="submit" class="fillBTN" :disabled="!bulkFile">Upload</b-button>
                                    <b-button class="transBTN" @click="closeImportSidebar">Cancel</b-button>
                                </div>
                            </div>
                        </b-form>
                    </div>
                </div>
            </div>
        </div>



        <div v-if="loading" class="text-center p-5">
            <b-spinner variant="#404054" label="Loading..."></b-spinner>
            <p class="mt-2">Loading employees...</p>
        </div>

        <div v-else>


            <div class="filter_selected px-4" v-if="inqFilterStatus">
                <span class="selected_filter_item_icon me-2"><i class="fa-solid fa-sliders"></i></span>
                <span class="selected_filter_item" v-if="search.name != null">{{ (search.name) }} <i
                        class="fa-solid fa-xmark" @click="() => { search.name = null; searchFilter() }"></i></span>

                <span class="selected_filter_item" v-if="search.globalSearch != null">{{ (search.globalSearch) }} <i
                        class="fa-solid fa-xmark"
                        @click="() => { search.globalSearch = null; searchFilter() }"></i></span>

                <span class="selected_filter_item" v-if="search.email != null">{{ (search.email) }} <i
                        class="fa-solid fa-xmark" @click="() => { search.email = null; searchFilter() }"></i></span>
                <span v-for="deptId in search.departmnt" :key="deptId" class="selected_filter_item">
                    {{ getfilterdepartment(deptId) }}
                    <i class="fa-solid fa-xmark" @click="() => {
                        search.departmnt = search.departmnt.filter(id => id !== deptId);
                        searchFilter();
                    }"></i>
                </span>
                <span class="selected_filter_item" v-if="search.empCode != null">{{ search.empCode }} <i
                        class="fa-solid fa-xmark" @click="() => { search.empCode = null; searchFilter() }"></i></span>

                <span class="selected_filter_item" v-if="search.reporting_to != null">{{
                    getfilterReportingTo(search.reporting_to)
                }} <i class="fa-solid fa-xmark"
                        @click="() => { search.reporting_to = null; searchFilter() }"></i></span>

                <span class="selected_filter_item" v-if="search.rolee != null">{{ getfilterRole(search.rolee) }} <i
                        class="fa-solid fa-xmark" @click="() => { search.rolee = null; searchFilter() }"></i></span>

            </div>


            <div v-if="totalrows > 0">
                <b-table responsive="sm" :items="selectedRoleData" :fields="fields" @sort-changed="onSortChanged"
                    v-if="$can('do', 'list_admin.org.employees')">
                    <template #head(employee_code)="data">
                        <span @click="changeSorting('employee_code')">
                            Employee Code
                            <i :class="getSortIcon('employee_code')"></i>
                        </span>
                    </template>

                    <template #cell(employee_code)="data">
                        <b-link v-if="$can('do', 'view_admin.org.employees')"
                            :to="`/admin/employees/view/${encodeBase64(data.item.id)}`">
                            {{ data.item.employee_code }}
                        </b-link>
                        <span v-else>
                            {{ data.item.employee_code }}
                        </span>
                    </template>

                    <template #head(first_name)="data">
                        <span @click="changeSorting('first_name')">
                            First Name
                            <i :class="getSortIcon('first_name')"></i>
                        </span>
                    </template>
                    <template #cell(first_name)="data">
                        <span>{{ formattedTitle(data.item.first_name) }}</span>
                    </template>
                    <template #head(last_name)="data">
                        <span @click="changeSorting('last_name')">
                            Last Name
                            <i :class="getSortIcon('last_name')"></i>
                        </span>
                    </template>
                    <template #cell(last_name)="data">
                        <span>{{ formattedTitle(data.item.last_name) }}</span>
                    </template>


                    <template #head(role)="data">
                        <span @click="changeSorting('role')">
                            Role
                            <i :class="getSortIcon('role')"></i>
                        </span>
                    </template>
                    <template #cell(role)="data">
                        <span>{{ formattedTitle(data.item.role) }}</span>
                    </template>

                    <template #head(department)="data">
                        <span @click="changeSorting('department')">
                            Department
                            <i :class="getSortIcon('department')"></i>
                        </span>
                    </template>

                    <template #cell(department)="data">
                        <span>{{ formattedTitle(data.item.department) }}</span>
                    </template>


                    <template #head(status)>
                        <span @click="changeSorting('status')">
                            Status <i :class="getSortIcon('status')"></i>
                        </span>
                    </template>
                    <template #cell(status)="data">

                        <i class="fa-solid fa-toggle-on pedroting" :class="data.item.status === 'A'
                            ? 'text-success'
                            : 'text-danger'
                            " @click="openChangeStatusModal(data.item)"></i>
                    </template>


                    <template #cell(actions)="data">

                        <b-dropdown right text="⋮" variant="link" no-caret toggle-class="p-0">
                            <b-dropdown-item @click="openEditSidebar(data.item)"
                                v-if="$can('do', 'edit_admin.org.employees')">Edit</b-dropdown-item>
                            <b-dropdown-item :to="`/admin/employees/view/${encodeBase64(data.item.id)}`"
                                v-if="$can('do', 'view_admin.org.employees')">View</b-dropdown-item>
                            <b-dropdown-item @click="openDeleteModal(data.item.id)"
                                v-if="$can('do', 'delete_admin.org.employees')">Delete</b-dropdown-item>
                        </b-dropdown>

                    </template>
                </b-table>

                <div class="tablecounter">
                    <div class="show_entries">
                        <span class="mr-2 count">Show</span>
                        <v-select append-to-body :calculate-position="withPopper" @update:modelValue="resetPage"
                            v-model="perPage" :options="perPageOptions" :clearable="false" class="mr-2" />
                        <span class="count">entries</span>
                    </div>
                    <div class="count">
                        Showing {{ rangeStart }} to {{ rangeEnd }} of {{ totalrows }}
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
            <employeeNoData v-if="selectedRoleData.length === 0 && totalrows === 0 "
                :heading="inqFilterStatus ? 'No Records Found' : 'No Employee Created Yet'"
                :subheading="inqFilterStatus ? 'Try adjusting your filters or search criteria.' : 'You haven\'t added any employees yet. Add your first employee to get started.'"
                :showButton="!inqFilterStatus && $can('do', 'add_admin.org.employees')"
                :buttonText="!inqFilterStatus ? 'Add' : ''" @button-clicked="openAddSidebar" />
               
        </div>

    </div>


    <!-- Sidebar for Add/Edit -->
    <div :class="{ parentBackground: sidebarstatus.assigned }">
        <!-- Sidebar content -->
        <div class="assigned_sidebar sidebar_main" :class="{ assigned_active: sidebarstatus.assigned }">
            <div class="sidebar_toolbox woBorder">
                <h6>{{ isEditMode ? "Edit Employee" : "Add Employee" }}</h6>
                <div class="sidebar_toolbox_right_side">
                    <CloseIcon @click="closeSidebar" />
                </div>
            </div>

            <div class="sidebar_form">
                <b-form @submit.prevent="handleFormSubmit">
                    <div class="p-3 column_sidebar" id="edit_frm_sidebar">
                        <!-- First Name -->
                        <b-form-group label-for="first-name">
                            <label class="required">First Name</label>
                            <b-form-input id="first-name" v-model="formData.first_name"
                                @input="RemoveError('first_name')" placeholder="Enter First Name" trim onkeypress="return (
                (event.charCode >= 65 && event.charCode <= 90) ||  
                (event.charCode >= 97 && event.charCode <= 122) ||
                event.charCode === 39 ||  // Apostrophe (')
                event.charCode === 45     // Hyphen (-)
            )" />
                            <small class="text-danger">{{ errors[0] }}</small>
                            <div class="text-danger" v-if="hasErrors('first_name')">
                                {{ getErrors("first_name") }}
                            </div>
                        </b-form-group>

                        <!-- Last Name -->
                        <b-form-group label-for="last-name">
                            <label class="required">Last Name</label>
                            <b-form-input id="last-name" v-model="formData.last_name" @input="RemoveError('last_name')"
                                placeholder="Enter Last Name" trim
                                onkeypress="return (event.charCode > 64 && event.charCode < 91) || (event.charCode > 96 && event.charCode < 123)" />
                            <small class="text-danger">{{ errors[0] }}</small>
                            <div class="text-danger" v-if="hasErrors('last_name')">
                                {{ getErrors("last_name") }}
                            </div>
                        </b-form-group>

                        <!-- Email -->
                        <b-form-group label-for="email">
                            <label class="required">Email</label>
                            <b-form-input id="email" type="email" v-model="formData.email" @input="RemoveError('email')"
                                placeholder="Enter Email" trim />
                            <small class="text-danger">{{ errors[0] }}</small>
                            <div class="text-danger" v-if="hasErrors('email')">
                                {{ getErrors("email") }}
                            </div>
                        </b-form-group>

                        <!-- Phone Number -->
                        <b-form-group label-for="phone">
                            <label class="required">Phone Number</label>
                            <b-form-input id="phone" v-model="formData.phone" @input="RemoveError('phone')"
                                placeholder="Enter Phone Number" :maxlength="10"
                                onkeypress="return (event.charCode !=8 && event.charCode ==0 || (event.charCode >= 48 && event.charCode <= 57))" />
                            <small class="text-danger">{{ errors[0] }}</small>
                            <div class="text-danger" v-if="hasErrors('phone')">
                                {{ getErrors("phone") }}
                            </div>
                        </b-form-group>

                         <b-form-group label-for="branch">
                            <label class="required">Branch</label>
                            <v-select v-model="formData.branch_id" @open="vSelectEdit"
                                @update:modelValue="handleBranchChange" :options="BranchOptions"
                                @input="RemoveError('branch_id')" :reduce="(val) => val.value" label="label"
                                :clearable="true" placeholder="Select Branch" />
                            <small class="text-danger">{{ errors[0] }}</small>
                            <div class="text-danger" v-if="hasErrors('branch_id')">
                                {{ getErrors("branch_id") }}
                            </div>
                        </b-form-group>
                        <!-- Department Selection -->
                        <b-form-group label-for="department">
                            <label class="required">Department</label>
                            <v-select v-model="formData.department_id" @open="vSelectEdit"
                                @update:modelValue="handleDepartmentChange" :options="DepartmentOptions"
                                @input="RemoveError('department_id')" :reduce="(val) => val.value" label="label"
                                :clearable="true" placeholder="Select Department" />
                            <small class="text-danger">{{ errors[0] }}</small>
                            <div class="text-danger" v-if="hasErrors('department_id')">
                                {{ getErrors("department_id") }}
                            </div>
                        </b-form-group>

                        <b-form-group label-for="role">
                            <label class="required">Role</label>

                            <v-select v-model="formData.role_id" @open="vSelectEdit" :options="RoleOptions"
                                :reduce="(val) => val.value" label="label" :clearable="true"
                                @input="RemoveError('role_id')" placeholder="Select Role" />
                            <small class="text-danger">{{ errors[0] }}</small>
                            <div class="text-danger" v-if="hasErrors('role_id')">
                                {{ getErrors("role_id") }}
                            </div>
                        </b-form-group>

                        <b-form-group label-for="reporting_head">
                            <label>Reporting To</label>

                            <v-select v-model="formData.reporting_head" @open="vSelectEdit"
                                :options="filteredReportingToOptions" :reduce="(val) => val.value" label="label"
                                :clearable="true" placeholder="Select Reporting To" />
                            <small class="text-danger">{{ errors[0] }}</small>
                            <div class="text-danger" v-if="hasErrors('reporting_head')">
                                {{ getErrors("reporting_head") }}
                            </div>

                        </b-form-group>

                    </div>

                    <!-- Form Actions -->
                    <div class="sidebarbtn_group">
                        <div class="buttonGrid">
                            <b-button type="submit" class="fillBTN">
                                {{ isEditMode ? "Update" : "Add" }}
                            </b-button>
                            <b-button class="transBTN" @click="closeSidebar">
                                Cancel
                            </b-button>
                        </div>
                    </div>
                </b-form>
            </div>
            <b-modal id="delete-modal" class="text-center" v-model="deleteModal" hide-header hide-footer size="md"
                centered>
                <h6 class="mb-3">Are you sure you want to delete this employee?</h6>
                <b-button class="btn_secondary_border me-2" @click="deleteModal = false">
                    Cancel
                </b-button>
                <b-button class="btn_primary" @click="confirmDelete">
                    Delete
                </b-button>
            </b-modal>


            <b-modal id="delete-modal" class="text-center" v-model="showStatusModal" hide-header hide-footer size="md"
                centered>
                <h6 class="mb-3">Are you sure you want to change the status of
                    <strong>{{ selectedItem?.name }}</strong>?
                </h6>
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
    </div>
</template>

<script setup>
import axiosEmployee from '@axiosEmployee';
import { ref, computed, watch, onMounted, useTemplateRef, defineEmits } from "vue";
import { useRoute, useRouter } from "vue-router";
import vSelect from "vue-select";
import "vue-select/dist/vue-select.css";

import "vue3-toastify/dist/index.css";
import CloseIcon from "../../../assets/img/icons/Close.vue"
import { createPopper } from '@popperjs/core';
import "@vuepic/vue-datepicker/dist/main.css";
import { toast } from "vue3-toastify";
import { saveAs } from "file-saver";
import employeeNoData from "../../../components/noData.vue";



const RoleOptions = ref([]);

const EmployeeOption = ref([]);
const EmployeeCode = ref([]);
const DepartmentEmpOption = ref([]);
const RoleEmpOption = ref([]);

const BranchOptions = ref([]);
const DepartmentOptions = ref([]);
const ReportingToOptions = ref([]);
const ReportingToOptionsFilter = ref([]);
const sortBy = ref(null);
const sortDesc = ref(false);
const loading = ref(true);



const emit = defineEmits(['triggerBackToMaster']);
const handleBackClick = () => {
    router.replace({ name: "admin.settings", query: { tab: "OrganizationStructure" } });
};

const filteredReportingToOptions = computed(() =>
    (isEditMode.value && currentItem.value.id)
        ? ReportingToOptions.value.filter(emp => emp.value !== currentItem.value.id)
        : ReportingToOptions.value
);

const formatDate = (date) => {
    return date.toISOString().split("T")[0]; // "YYYY-MM-DD"
};
const minDate = new Date("1900-01-01"); // Replace with your desired minimum date
// Maximum date is today's date (no future date should be allowed)
const maxDate = computed(() => {
    const today = new Date();
    today.setHours(0, 0, 0, 0); // Remove time part to avoid timezone issues
    today.setFullYear(today.getFullYear() - 18);
    return today;
});


const minJoinDate = computed(() => {
    return formatDate(new Date("1900-01-01"));
});

// Latest allowed joining date = today (no future dates allowed)
const maxJoinDate = computed(() => {
    const today = new Date();
    today.setHours(0, 0, 0, 0); // strip time
    return formatDate(today);
});

const encodeBase64 = (data) => {
    if (data === undefined || data === null) {
        return "";
    }
    return btoa(data.toString());
};

const fetchDepartmentOptions = () => {
    axiosEmployee.get("/DepartmentsOption").then((response) => {
        DepartmentOptions.value = response.data.data;
    });
};

const fetchRolesByDepartment = (departmentId) => {
    axiosEmployee.get(`/RolesByDepartment/${departmentId}`).then((response) => {
        RoleOptions.value = response.data.data;
    });
};




const handleDepartmentChange = (val) => {
    formData.value.department_id = val;
    RemoveError('department_id');
    formData.value.role_id = null;
    fetchRolesByDepartment(val);
};

onMounted(() => {
    fetchDepartmentOptions();
    fetchNoOfData();
    fetchEmployeeOptions();
    fetchEmployeeCode();
    fetchReportingTo();

    axiosEmployee.get("/BranchesOption").then((response) => {
        BranchOptions.value = response.data.data;
    });

    axiosEmployee.get("/DepartmentEmpOption").then((response) => {
        DepartmentEmpOption.value = response.data.data;
    });
    axiosEmployee.get("/RoleEmpOption").then((response) => {
        RoleEmpOption.value = response.data.data;
    });
});


const onSortChanged = (ctx) => {
    currentPage.value = 1;
    sortBy.value = ctx.sortBy;
    sortDesc.value = ctx.sortDesc;
};

const changeSorting = (field) => {
    currentPage.value = 1;
    if (sortBy.value === field) {
        sortDesc.value = !sortDesc.value;
    } else {
        sortBy.value = field;
        sortDesc.value = false;
    }
    fetchNoOfData(); // Assuming fetchNoOfData is defined elsewhere
};

const getSortIcon = (field) => {
    if (sortBy.value === field) {
        return sortDesc.value ? "fas fa-sort-down" : "fas fa-sort-up";
    }
    return "fas fa-sort";
};



// Reactive state for the sidebar
const sidebarstatus = ref({
    shadow: false,
    assigned: false,
    filter: false,
});

// Function to close the sidebar
const closeSidebar = () => {
    sidebarstatus.value.assigned = false;
    formData.value = {
        first_name: "",
        last_name: "",
        email: "",
        phone: "",
        role_id: null,
        department_id: null,
        reporting_head: null,
    };
    errors.value = [];
    router.replace({
        query: {
            ...route.query,
            action: undefined,
            id: undefined,
        },
    });
};

// Search filter reactive state
const search = ref({
    name: null,
    globalSearch: null,
    empCode: null,
    departmnt: [],
    rolee: null,
    email: null,
    reporting_to: null,
});

const statusOption = ref([
    { label: 'Active', value: 'A' },
    { label: 'Inactive', value: 'I' }
]);

const route = useRoute();
const router = useRouter();

// Data listing
const selectedRoleData = ref([]);

// Form mode and data
const isEditMode = ref(false);
const formData = ref({
    first_name: null,
    last_name: null,
    email: null,
    phone_number: null,
    role_id: null,
    department_id: null,
    reporting_head: null,
});



const currentItem = ref(null);

// Pagination setup
const perPageOptions = [10, 25, 50, 100];
const totalrows = ref(0);
const perPage = ref(10);
const currentPage = ref(1);

const rangeStart = computed(() =>
    totalrows.value === 0 ? 0 : (currentPage.value - 1) * perPage.value + 1
);
const rangeEnd = computed(() =>
    currentPage.value * perPage.value >= totalrows.value
        ? totalrows.value
        : currentPage.value * perPage.value
);

const resetPage = () => {
    currentPage.value = 1; // Reset to first page
};

const changePage = (pageNumber) => {
    if (pageNumber < 1 || pageNumber > totalPages.value || pageNumber === currentPage.value) return;
    currentPage.value = pageNumber;
};

const totalPages = computed(() => {
    return Math.ceil(totalrows.value / perPage.value);
});

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

const fetchNoOfData = () => {
    loading.value = true;
    axiosEmployee
        .get(`/employees/`, {
            params: {
                searchname: search.value.name,
                searchstatus: search.value.status,
                searchempCode: search.value.empCode,
                departmnt: search.value.departmnt,
                rolee: search.value.rolee,
                email: search.value.email,
                reporting_to: search.value.reporting_to,

                page: currentPage.value,
                perPage: perPage.value,
                sortBy: sortBy.value,
                sortDesc: sortDesc.value,
                globalSearch: search.value.globalSearch,

            },
        })
        .then((response) => {
            selectedRoleData.value = response.data.data;
            totalrows.value = response.data.total;
            loading.value = false;
            if (selectedRoleData.value.length === 0 && totalrows.value > 0 && currentPage.value > 1) {
                currentPage.value -= 1;
                fetchNoOfData(); // Fetch data for the previous page
            }
        }).catch((error) => {
            loading.value = false;
            console.error("Error fetching data:", error);
        });;;
};

const searchFilter = () => {
    currentPage.value = 1;
    fetchNoOfData();
    sidebarstatus.value.filter = false;
};

const resetFilter = () => {
    sidebarstatus.value.filter = false;
    search.value = {
        name: null,
        globalSearch: null,
        empCode: null,
        departmnt: [],
        rolee: null,
        email: null,
        reporting_to: null,
    };

    currentPage.value = 1;
    fetchNoOfData();
};

const resetSearch = () => {
    fetchNoOfData();
};

const openAddSidebar = () => {
    isEditMode.value = false;
    formData.value = {
        first_name: "",
        last_name: "",
        email: "",
        phone_number: "",
        role_id: null,
        department_id: null,
        reporting_head: null,
    };
    currentItem.value = null;
    errors.value = [];
    sidebarstatus.value.assigned = true;
    router.replace({
        query: {
            ...route.query,
            action: "add",
        },
    });

};

const openEditSidebar = (item) => {
    fetchRolesByDepartment(item.department_id);
    formData.value = { ...item };
    currentItem.value = item;
    errors.value = [];
    sidebarstatus.value.assigned = true;
    isEditMode.value = true;
    const encodedId = btoa(item.id);
    router.replace({
        query: {
            ...route.query,
            action: "edit",
            id: encodedId,
        },
    });
};



const fetchEmployeeOptions = async () => {
    try {
        const response = await axiosEmployee.get("/EmployeeOption");
        EmployeeOption.value = response.data.data;
    } catch (error) {
        console.error("Error fetching employee options:", error);
    }
};

const fetchEmployeeCode = async () => {
    try {
        const response = await axiosEmployee.get("/EmployeeCode");
        EmployeeCode.value = response.data.data;
    } catch (error) {
        console.error("Error fetching employee code:", error);
    }
};

const fetchReportingTo = async () => {
    try {
        const response = await axiosEmployee.get("/ReportingToOptions");
        ReportingToOptions.value = response.data.data;
        ReportingToOptionsFilter.value = response.data.data;
    } catch (error) {
        console.error("Error fetching reporting options:", error);
    }
};



const handleFormSubmit = async () => {
    errors.value = {};

    try {
        let res;

        if (isEditMode.value) {
            res = await axiosEmployee.put(`/employees/${currentItem.value.id}`, formData.value);
        } else {
            res = await axiosEmployee.post("/employees", formData.value);
            await fetchEmployeeOptions();
            await fetchEmployeeCode();
            await fetchReportingTo();
        }

        closeSidebar();
        setTimeout(() => {
        toast(res.data.message, {
            type: "success",
            autoClose: 1000,
        });
         }, 300);

        fetchNoOfData();
       
    } catch (error) {
        if (error.response && error.response.status === 422) {
            errors.value = error.response.data.errors;
        } else {
            toast("Something went wrong. Please try again.", {
                type: "error",
                autoClose: 1000,
            });
        }
    }
};


const showStatusModal = ref(false);
const selectedItem = ref(null);

const openChangeStatusModal = (item) => {
    selectedItem.value = item;
    showStatusModal.value = true;
};

const confirmChangeStatus = async () => {
    try {
        const response = await axiosEmployee.post(
            `/employeeStatus/${selectedItem.value.id}/change-status`
        );
        console.log(response.data.status)
        if (response.data.status == "success") {

            toast("Status updated successfully!", {
                type: "success",
                autoClose: 1000,
            });


            fetchNoOfData();
        } else {
            toast("Failed to update status!", {
                type: "error",
                autoClose: 1000,
            });
        }
    } catch (error) {
        toast("Failed to update status!", {
            type: "error",
            autoClose: 1000,
        });
    } finally {
        showStatusModal.value = false;
    }
};





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
const sidebarImport = ref(false)

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
        const response = await axiosEmployee.get('/download-import-template-employee', {
            responseType: 'blob'
        });

        const url = window.URL.createObjectURL(new Blob([response.data]));
        const link = document.createElement('a');
        link.href = url;
        link.setAttribute('download', 'employee_import_template.xlsx');
        document.body.appendChild(link);
        link.click();
        link.remove();
    } catch (error) {
        console.error('Template download error:', error);
    }
};

const uploadFile = async () => {
    if (!bulkFile.value) {
        toast("Please select a file to upload", {
            type: "error",
            autoClose: 3000,
        });
        return;
    }

    // Check file extension
    const validExtensions = ['.xlsx', '.xls', '.csv'];
    const fileExt = bulkFile.value.name.split('.').pop().toLowerCase();

    if (!validExtensions.includes(`.${fileExt}`)) {
        toast("Invalid file format. Please upload Excel or CSV files only", {
            type: "error",
            autoClose: 3000,
        });
        return;
    }

    const formData = new FormData();
    formData.append('file', bulkFile.value);

    try {
        const response = await axiosEmployee.post('/import-employee', formData, {
            headers: {
                'Content-Type': 'multipart/form-data'
            }
        });

        if (response.data.error === "No valid data found in the file") {
            toast("The file contains no valid employee data", {
                type: "error",
                autoClose: 3000,
            });
        } else {
            closeImportSidebar();
            fetchNoOfData();
            toast(response.data.message || "Employees imported successfully!", {
                type: "success",
                autoClose: 3000,
            });
        }
    } catch (error) {
        let errorMsg = error.response?.data?.error || error.message;

        // Handle validation errors
        if (error.response?.data?.errors) {
            errorMsg = "Validation errors in file:";
            error.response.data.errors.forEach(err => {
                errorMsg += `\nRow ${err.row}: ${err.errors.join(', ')}`;
            });
        }

        toast(`Import failed: ${errorMsg}`, {
            type: "error",
            autoClose: 5000,
        });
    }
};



const exportEmployee = async () => {

    try {
        const response = await axiosEmployee.get("/export-employees", {
            responseType: "blob",
        });

        const blob = new Blob([response.data], {
            type: "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
        });

        const fileName = `employees-${new Date().toISOString().slice(0, 10)}.xlsx`;
        saveAs(blob, fileName);
    } catch (error) {
        console.error("Export failed:", error);
    }
};


const RemoveError = (errorName) => {
    errors.value[errorName] = " ";
};
const hasErrors = (fieldName) => {
    return fieldName in errors.value;
};
const getErrors = (fieldName) => {
    return errors.value[fieldName][0];
};
const errors = ref([]);

const deleteModal = ref(false);
const deleteId = ref(null);
const statusId = ref(null);


const currentStatus = ref('');



const openDeleteModal = (id) => {
    deleteId.value = id;
    deleteModal.value = true;
};




const confirmDelete = async () => {
    if (!deleteId.value) return;
    try {
        const response = await axiosEmployee.delete(`employees/${deleteId.value}`);
        deleteModal.value = false;
        deleteId.value = null;
        toast(response.data.message || "Deleted successfully!", {
            type: "success",
            autoClose: 1000,
        });
        fetchNoOfData();
        fetchReportingTo();
        fetchEmployeeOptions();
    } catch (error) {
        toast("Error deleting item!", {
            type: "error",
            autoClose: 1000,
        });
    }
};
const refs = useTemplateRef("refPermissionModal");



const fields = [
    { key: "employee_code", label: "Employee Code", sortable: true },
    { key: "first_name", label: "First Name", sortable: true },
    { key: "last_name", label: "Last Name", sortable: true },
    { key: "department", label: "Department", sortable: true },
    { key: "status", label: "Status", sortable: true },
    { key: "actions", label: "Actions" }
];

const formattedTitle = (text) => {
    if (text) {
        return text.charAt(0).toUpperCase() + text.slice(1); // Capitalize the first letter
    }
    return text;
};

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

const getfilterdepartment = (id) => {
    return DepartmentEmpOption.value.find(elm => elm.value == id)?.label || null;
};

const getfilterReportingTo = (id) => {
    let filtername = null;
    ReportingToOptionsFilter.value.forEach((elm) => {
        if (elm.value == id) {
            filtername = elm.label;
        }
    });
    return filtername;
}

const getfilterRole = (id) => {
    let filtername = null;
    RoleEmpOption.value.forEach((elm) => {
        if (elm.value == id) {
            filtername = elm.label;
        }
    });
    return filtername;
}

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


watch(sidebarstatus.value, async (newstatus, oldstatus) => {
    if (newstatus.filter || newstatus.add || newstatus.view || newstatus.assigned || newstatus.notes || newstatus.status) {
        sidebarstatus.value.shadow = true;
    }
    else {
        sidebarstatus.value.shadow = false;
    }
});

watch(() => formData.value.role_id, () => {
    RemoveError('role_id');
});

watch(() => formData.value.department_id, () => {
    RemoveError('department_id');
});

watch(() => formData.value.reporting_head, () => {
    RemoveError('reporting_head');
});


watch([currentPage, perPage], fetchNoOfData);


</script>
