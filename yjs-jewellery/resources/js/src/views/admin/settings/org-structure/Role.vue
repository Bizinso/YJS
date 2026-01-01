<template>
    <div class="listing_screen global_table_liting">



        <h2 class="masterHeading">Role</h2>
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
                    <b-button class="fillBTN" @click="openAddSidebar" v-if="$can('do', 'add_admin.org.roles')">Add</b-button>
                    <b-button class="transBTN" v-b-tooltip.hover title="Bulk Upload"
                        @click="sidebarImport = !sidebarImport">
                        <img src="../../../assets/img/upload.svg" width="16px" alt="filter" /></b-button>

                    <b-button class="transBTN" v-b-tooltip.hover title="Download Data" @click="exportRole()">
                        <img src="../../../assets/img/download.svg" width="16px" alt="filter" /></b-button>
                    <b-button type="button" class="transBTN" @click="handleBackClick">Back</b-button>
                </div>
            </div>


            <!-- Wrapper with overlay background -->
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
                        <b-form @submit.prevent="searchFilter">
                            <div class="px-4 py-3 column_sidebar">

                                <b-form-group label="Department" label-for="department">
                                    <v-select v-model="search.department" :options="DepartmentOptions"
                                        :reduce="(val) => val.label" label="label" :clearable="true"
                                        placeholder="Search Department Name..."></v-select>
                                </b-form-group>

                                <b-form-group label="Role" label-for="role">
                                    <v-select v-model="search.name" :options="RoleOption" :reduce="(val) => val.label"
                                        label="label" :clearable="true" placeholder="Search Role Name..."></v-select>
                                </b-form-group>



                            </div>

                            <div class="sidebarbtn_group">
                                <div class="buttonGrid">
                                    <b-button type="submit" class="fillBTN">Apply</b-button>
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
            <p class="mt-2">Loading roles...</p>
        </div>
        <div v-else>

            <div class="filter_selected px-4" v-if="inqFilterStatus">
                <span class="selected_filter_item_icon me-2"><i class="fa-solid fa-sliders"></i></span>
                <span class="selected_filter_item" v-if="search.department != null">{{ search.department }} <i
                        class="fa-solid fa-xmark"
                        @click="() => { search.department = null; search.name = null; searchFilter() }"></i></span>
                <span class="selected_filter_item" v-if="search.name != null">{{ (search.name) }} <i
                        class="fa-solid fa-xmark" @click="() => { search.name = null; searchFilter() }"></i></span>

                <span class="selected_filter_item" v-if="search.globalSearch != null">{{ (search.globalSearch) }} <i
                        class="fa-solid fa-xmark"
                        @click="() => { search.globalSearch = null; searchFilter() }"></i></span>


            </div>

            <div v-if="totalrows > 0">
                <b-table responsive="sm" :items="selectedRoleData" :fields="fields" @sort-changed="onSortChanged" v-if="$can('do', 'list_admin.org.roles')">
                    <template #head(name)="data">
                        <span @click="changeSorting('name')">
                            Role Name
                            <i :class="getSortIcon('name')"></i>
                        </span>
                    </template>
                    <template #cell(name)="data">
                        <span>{{ formattedTitle(data.item.name) }}</span>
                    </template>

                    <template #head(department)="data">
                        <span @click="changeSorting('department')">
                            Department
                            <i :class="getSortIcon('department')"></i>
                        </span>
                    </template>

                    <template #cell(department)="data">
                        {{ formattedTitle(data.item.department) }}
                    </template>





                    <template #cell(actions)="data">

                        <b-dropdown right text="⋮" variant="link" no-caret toggle-class="p-0">
                            <b-dropdown-item 
                                @click="openEditSidebar(data.item)" v-if="$can('do', 'edit_admin.org.roles')">Edit</b-dropdown-item>

                            <b-dropdown-item 
                                @click="openDeleteModal(data.item.id)" v-if="$can('do', 'delete_admin.org.roles')">Delete</b-dropdown-item>
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

            <roleNoData v-if="selectedRoleData.length === 0 && totalrows === 0"
                :heading="inqFilterStatus ? 'No Records Found' : 'No Role Created Yet'"
                :subheading="inqFilterStatus ? 'Try adjusting your filters or search criteria.' : 'You haven\'t added any roles yet. Add your first role to get started.'"
                :showButton="!inqFilterStatus && $can('do', 'add_role')" :buttonText="!inqFilterStatus ? 'Add' : ''"
                @button-clicked="openAddSidebar" />
        </div>

        <!-- Background overlay -->
        <div :class="{ parentBackground: sidebarstatus.add }">
            <!-- Sidebar -->
            <div class="assigned_sidebar sidebar_main" :class="{ assigned_active: sidebarstatus.add }">
                <div class="sidebar_toolbox woBorder">
                    <h6>{{ isEditMode ? 'Update Role' : 'Add Role' }}</h6>
                    <div class="sidebar_toolbox_right_side">
                        <CloseIcon @click="closeSidebar" />
                    </div>
                </div>

                <div class="sidebar_form">

                    <b-form @submit.prevent="handleFormSubmit">
                        <div class="px-4 py-3 column_sidebar">

                            <b-form-group label-for="department">
                                <label class="required">Department</label>
                                <v-select v-model="formData.department_id" :options="DepartmentOptions"
                                    :reduce="(val) => val.value" label="label" :clearable="true"
                                    placeholder="Select Department" />
                                <small class="text-danger">{{ errors[0] }}</small>
                                <div class="text-danger" v-if="hasErrors('department_id')">
                                    {{ getErrors("department_id") }}
                                </div>
                            </b-form-group>

                            <b-form-group label-for="name">
                                <label class="required">Role Name</label>
                                <b-form-input id="name" v-model="formData.name" @input="RemoveError('name')"
                                    placeholder="Enter Role Name" autocomplete="off"></b-form-input>
                                <small class="text-danger">{{ errors[0] }}</small>
                                <div class="text-danger" v-if="hasErrors('name')">
                                    {{ getErrors("name") }}
                                </div>
                            </b-form-group>

                        </div>
                        <div class="sidebarbtn_group">
                            <div class="buttonGrid">
                                <b-button type="submit" class="fillBTN">{{ isEditMode ? "Update" : "Add" }}</b-button>
                                <b-button class="transBTN" @click="closeSidebar">
                                    Cancel
                                </b-button>
                            </div>
                        </div>
                    </b-form>

                </div>
            </div>
        </div>


        <b-modal id="delete-modal" class="text-center" v-model="deleteModal" hide-header hide-footer size="md" centered>
            <h6 class="mb-3">Are you sure you want to delete this Role?</h6>
            <p class="text-danger mb-3">This will also delete all related employees!</p>
            <b-button class="btn_secondary_border me-2" @click="deleteModal = false">
                Cancel
            </b-button>
            <b-button class="btn_primary" @click="confirmDelete">
                Delete
            </b-button>
        </b-modal>

    </div>
</template>

<script setup>
import axiosEmployee from '@axiosEmployee';
import { ref, computed, watch, onMounted, defineEmits } from "vue";
import { useRoute, useRouter } from "vue-router";
import vSelect from "vue-select";
import "vue-select/dist/vue-select.css";
import { toast } from "vue3-toastify";
import "vue3-toastify/dist/index.css";
import CloseIcon from "../../../../assets/img/icons/Close.vue"
import { createPopper } from '@popperjs/core';
import { saveAs } from "file-saver";
import roleNoData from "../../../components/noData.vue";


const RoleOption = ref([]);
const DepartmentOptions = ref([]);
const emit = defineEmits(['triggerBackToMaster']);
const handleBackClick = () => {
    router.replace({ name: "admin.settings", query: { tab: "OrganizationStructure" } });
};
onMounted(() => {
    fetchNoOfData();
    axiosEmployee.get("/RoleDepartmentOptions").then((response) => {
        DepartmentOptions.value = response.data.data;
    });
    axiosEmployee.get("/RolesOption").then((response) => {
        RoleOption.value = response.data.data;
    });
});

const sortBy = ref(null);
const sortDesc = ref(false);

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


const search = ref({
    name: null,
    globalSearch: null,
    department: null,
});
const route = useRoute();
const router = useRouter();


const selectedRoleData = ref([]);


const isEditMode = ref(false);
const formData = ref({ name: null, department_id: null });
const currentItem = ref(null);

const perPageOptions = [10, 25, 50, 100];
const rangeStart = computed(() =>
    totalrows.value === 0 ? 0 : (currentPage.value - 1) * perPage.value + 1
);
const rangeEnd = computed(() =>
    currentPage.value * perPage.value >= totalrows.value
        ? totalrows.value
        : currentPage.value * perPage.value
);
const totalrows = ref(0);
const perPage = ref(10);
const currentPage = ref(1);
const deleteModal = ref(false);
const loading = ref(true);


const resetPage = () => {
    currentPage.value = 1; // Reset to first page
    fetchNoOfData();
};

const fetchNoOfData = () => {
    loading.value = true;
    axiosEmployee
        .get(`/roles/`, {
            params: {
                name: search.value.name,
                department: search.value.department,
                globalSearch: search.value.globalSearch,
                page: currentPage.value,
                perPage: perPage.value,
                sortBy: sortBy.value,
                sortDesc: sortDesc.value,
            },
        })
        .then((response) => {
            selectedRoleData.value = response.data.data;
            totalrows.value = response.data.total;
            loading.value = false;

            // If current page has no data but there are records, go to previous page
            if (selectedRoleData.value.length === 0 && totalrows.value > 0 && currentPage.value > 1) {
                currentPage.value -= 1;
                fetchNoOfData(); // Fetch data for the previous page
            }
        }).catch((error) => {
            loading.value = false;
            console.error("Error fetching data:", error);
        });
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
        department: null,
    };

    fetchNoOfData();
    router.replace({
        query: {
            ...route.query,
            action: undefined,
            id: undefined,
        },
    });



};


const openAddSidebar = () => {
    sidebarstatus.value.add = true;
    isEditMode.value = false;
    formData.value = { name: "", department_id: "" };
    errors.value = [];
    router.replace({
        query: {
            ...route.query,
            action: "add",
        },
    });
};

const openEditSidebar = (item) => {
    sidebarstatus.value.add = true;
    isEditMode.value = true;
    console.log(item, 'item123');
    formData.value = { ...item };
    console.log(formData.value, 'formData.value');
    currentItem.value = item;
    errors.value = [];
    const encodedId = btoa(item.id);
    router.replace({
        query: {
            ...route.query,
            action: "edit",
            id: encodedId,
        },
    });
};


const closeSidebar = () => {
    sidebarstatus.value.add = false;
    formData.value = { name: "", department_id: "" };
    errors.value = [];
    router.replace({
        query: {
            ...route.query,
            action: undefined,
            id: undefined,
        },
    });

};



const handleFormSubmit = () => {

    if (isEditMode.value) {
        axiosEmployee
            .put(`/roles/${currentItem.value.id}`, formData.value)
            .then(() => {

                sidebarstatus.value.add = false;
                closeSidebar();
                setTimeout(() => {
                    toast("Updated successfully!", {
                        type: "success",
                        autoClose: 1000,
                    });
                }, 300);

                fetchNoOfData();
                // sidebarstatus.value.add = false;

            })
            .catch((error) => {
                if (error.response.data.code == 422) {
                    errors.value = error.response.data.errors;
                }
            });
    } else {
        axiosEmployee
            .post(`/roles`, formData.value)
            .then(() => {

                sidebarstatus.value.add = false;
                closeSidebar();
                setTimeout(() => {
                    toast("Added successfully!", {
                        type: "success",
                        autoClose: 1000,
                    });
                }, 300);

                fetchNoOfData();
                // sidebarstatus.value.add = false;

            })
            .catch((error) => {

                if (error.response.data.code == 422) {
                    errors.value = error.response.data.errors;
                }
            });
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
        const response = await axiosEmployee.get('/download-import-template-role', {
            responseType: 'blob'
        });

        const url = window.URL.createObjectURL(new Blob([response.data]));
        const link = document.createElement('a');
        link.href = url;
        link.setAttribute('download', 'role_import_template.xlsx');
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
        const response = await axiosEmployee.post('/import-role', formData, {
            headers: {
                'Content-Type': 'multipart/form-data'
            }
        });

        if (response.data.error === "No valid data found in the file") {
            toast("The file contains no valid role data", {
                type: "error",
                autoClose: 3000,
            });
        } else {
            closeImportSidebar();
            fetchNoOfData();
            toast(response.data.message || "Roles imported successfully!", {
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




const exportRole = async () => {

    try {
        const response = await axiosEmployee.get("/export-roles", {
            responseType: "blob",
        });

        const blob = new Blob([response.data], {
            type: "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
        });

        const fileName = `roles-${new Date().toISOString().slice(0, 10)}.xlsx`;
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


const deleteId = ref(null);
const openDeleteModal = (id) => {
    deleteId.value = id;
    deleteModal.value = true;
};


const confirmDelete = async () => {
    if (!deleteId.value) return;
    try {
        const response = await axiosEmployee.delete(`roles/${deleteId.value}`);
        deleteModal.value = false;
        deleteId.value = null;
        toast(response.data.message || "Deleted successfully!", {
            type: "success",
            autoClose: 1000,
        });
        fetchNoOfData();
    } catch (error) {
        toast("Error deleting item!", {
            type: "error",
            autoClose: 1000,
        });
    }
};
const fields = [
    { key: "name", label: "Role Name", sortable: true },
    { key: "department", label: "Department", sortable: true },
    { key: "actions", label: "Actions" },
];

const sidebarstatus = ref({
    shadow: false,
    add: false,
    assigned: false
});

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

watch(() => search.value.department, (newDepartment) => {

    search.value.name = null;

    if (!newDepartment) {
        axiosEmployee.get("/RolesOption").then((response) => {
            RoleOption.value = response.data.data;
        });
        return;
    }

    axiosEmployee
        .get('/RolesOption', {
            params: { department_id: newDepartment.value || newDepartment },
        })
        .then((res) => {
            RoleOption.value = res.data.data;
        });
});



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

// fetchNoOfData();
watch([currentPage, perPage], fetchNoOfData);

watch(() => formData.value.department_id, () => {
    RemoveError('department_id');
});

watch(sidebarstatus.value, async (newstatus, oldstatus) => {
    if (newstatus.filter || newstatus.add) {
        sidebarstatus.value.shadow = true;
    }
    else {
        sidebarstatus.value.shadow = false;
    }
});
</script>