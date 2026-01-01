<template>
    <div class="listing_screen global_table_liting px-3">
        <h2 class="masterHeading">Tag/Label</h2>
        <!-- Search and Actions -->
        <div class="listing_tab_and_actions mb-3">

            <div class="listing_actions">
                <div class="d-flex">
                    <div class="listing_search">
                        <img src="../../../assets/img/header/search.svg" class="listing_search_icon" alt="search" />
                        <b-form-input v-model="search.globalSearch" @input="searchFilter"
                            placeholder="Search Here..." />
                    </div>
                    <b-button title="filter" class="btn_listing_action" @click="toggleFilterSidebar">
                        <img src="../../../assets/img/filter.svg" alt="filter" /> Filter
                    </b-button>
                </div>
                <div class="buttonGrid">
                    <b-button class="fillBTN" v-if="$can('do', 'access_admin.masters.tags')" @click="openAddSidebar">Add</b-button>

                    <b-button type="button" class="transBTN" @click="handleBackClick">Back</b-button>
                </div>
            </div>
            <!-- Wrapper for background overlay -->
            <div :class="{ parentBackground: sidebarstatus.filter }">
                <div class="filter_sidebar sidebar_main" :class="{ filter_active: sidebarstatus.filter }">
                    <div class="sidebar_toolbox woBorder">
                        <h6>Filter</h6>
                        <div class="sidebar_toolbox_right_side">
                            <CloseIcon @click="resetFilter" />
                        </div>
                    </div>

                    <div class="sidebar_form">
                        <b-form>
                            <div class="px-4 py-3 column_sidebar">
                                <label>Tag Name</label>
                                <b-form-group>
                                    <b-form-input v-model="search.name" placeholder="Search by Tag Name..." trim />
                                </b-form-group>
                                <label>Description</label>
                                <b-form-group>
                                    <b-form-input v-model="search.description" placeholder="Search by Description..."
                                        trim />
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
                        <CloseIcon @click="sidebarImport = !sidebarImport" />
                    </div>
                    <div class="sidebar_form">
                        <b-form>
                            <div class="px-4 py-3 column_sidebar">
                                <b-form-group>
                                    <div class="image-upload-wrapper text-center">
                                        <!-- Hidden File Input -->
                                        <input type="file" ref="bulkFileInput" @change="handleBulkFileSelect"
                                            accept=".csv, application/vnd.openxmlformats-officedocument.spreadsheetml.sheet, application/vnd.ms-excel"
                                            class="d-none" />

                                        <!-- If file is selected -->
                                        <div v-if="bulkFile" class="thumbnail-grid selected-file noRpaoo">
                                            <div
                                                class="file-info d-flex justify-content-between align-items-center p-2 border rounded gap-2">
                                                <img src="../../../assets/img/xcl.svg" alt="Upload file" class="iconBox" />
                                                <div class="centerPiece">
                                                    <div class="innerNames">
                                                        <h3>{{ bulkFile.name }}</h3>
                                                        <span>{{ (bulkFile.size / 1024).toFixed(2) }} KB</span>
                                                    </div>
                                                </div>
                                                <!-- Remove button -->
                                                <button class="btn btn-sm btn-outline-danger removeBox"
                                                    @click="removeBulkFile">
                                                    ✕
                                                </button>
                                            </div>
                                        </div>

                                        <!-- If no file is selected -->
                                        <div v-else class="thumbnail-container upload-placeholder"
                                            @click="triggerFileSelect">
                                            <img src="../../../assets/img/uploadfile.png" alt="Upload file" class="w-100" />
                                        </div>
                                    </div>
                                </b-form-group>
                                <div class="mt-3">
                                    <a :href="sampleExcelLink" download="Sample_Template.xlsx" class="sampleLink">
                                        Click here to download the sample Excel file.
                                    </a>
                                </div>
                            </div>
                            <div class="sidebarbtn_group">
                                <div class="buttonGrid">
                                    <b-button type="submit" class="fillBTN" @click="searchFilter">Upload</b-button>
                                    <b-button class="transBTN" @click="sidebarImport = !sidebarImport">Cancel</b-button>
                                </div>
                            </div>
                        </b-form>
                    </div>
                </div>
            </div>
        </div>

        <div class="filter_selected px-4" v-if="inqFilterStatus">
            <span class="selected_filter_item_icon me-2"><i class="fa-solid fa-sliders"></i></span>
            <template v-for="(value, key) in search" :key="key">
                <span v-if="value !== null && value !== ''" class="selected_filter_item">
                    {{ value }}
                    <i class="fa-solid fa-xmark" @click="() => { search[key] = null; searchFilter() }"></i>
                </span>
            </template>
        </div>

        <div v-if="loading" class="text-center p-5">
            <b-spinner variant="#404054" label="Loading..."></b-spinner>
            <p class="mt-2">Loading tag...</p>
        </div>

        <div v-else>

            <b-table responsive="sm" :items="paginatedData" :fields="fields" @sort-changed="onSortChanged"
                v-if="$can('do', 'list_admin.masters.tags') && paginatedData.length > 0">
                <template #head(name)>
                    <span @click="changeSorting('name')">
                        Name <i :class="getSortIcon('name')"></i>
                    </span>
                </template>
                <template #cell(name)="data">
                    <span>{{ formattedTitle(data.item.name) }}</span>
                </template>

                <template #head(description)>
                    <span @click="changeSorting('description')">
                        Description <i :class="getSortIcon('description')"></i>
                    </span>
                </template>
                <template #cell(description)="data">
                    {{ formattedTitle(data.item.description) }}
                </template>
                <template #head(status)>
                    <span @click="changeSorting('status')">
                        Status <i :class="getSortIcon('status')"></i>
                    </span>
                </template>
                <template #cell(status)="data">
                    <!-- <span
          :class="data.item.status === 'A' ? 'text-success' : 'text-danger'"
        >
          {{ data.item.status === "A" ? "Active" : "Inactive" }}
        </span> -->
                    <i class="fa-solid fa-toggle-on ms-2" :class="[
                        data.item.status === 'A' ? 'text-success' : 'text-danger',
                        data.item.status !== 'A' ? 'flipped-toggle' : '',
                    ]" @click="openChangeStatusModal(data.item)" style="cursor: pointer; font-size: 1.1rem"></i>
                </template>
                <template #cell(actions)="data">


                    <b-dropdown right text="⋮" variant="link" no-caret toggle-class="p-0">
                        <b-dropdown-item v-if="$can('do', 'edit_admin.masters.tags')"
                            @click="openEditSidebar(data.item)">Edit</b-dropdown-item>

                        <b-dropdown-item v-if="$can('do', 'delete_admin.masters.tags')"
                            @click="openDeleteModal(data.item.id)">Delete</b-dropdown-item>
                    </b-dropdown>

                </template>
            </b-table>


            <tagNoData v-if="inqFilterStatus && paginatedData.length === 0 || !inqFilterStatus && sidebarstatus.filter" heading="No Records Found"
                subheading="Try adjusting your filters or search criteria." :showButton="false" />

            <tagNoData v-else-if="paginatedData.length === 0" heading="No Tag Created Yet"
                subheading="You haven't added any tag yet. Add your first sub tag to get started."
                :showButton="$can('do', 'add_admin.masters.tags')" buttonText="Add Tag" @button-clicked="openAddSidebar" />

            <div class="tablecounter" v-if="paginatedData.length > 0">
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

        <!-- Parent wrapper for overlay background -->
        <div :class="{ parentBackground: sidebarstatus.add }">
            <!-- Sidebar content -->
            <div class="assigned_sidebar sidebar_main" :class="{ assigned_active: sidebarstatus.add }">
                <div class="sidebar_toolbox woBorder">
                    <h6>{{ isEditMode ? "Update Tag" : "Add Tag" }}</h6>
                    <div class="sidebar_toolbox_right_side">
                        <CloseIcon @click="closeSidebar" />
                    </div>
                </div>

                <div class="sidebar_form">
                    <b-form @submit.prevent="handleFormSubmit">
                        <div class="p-3 column_sidebar">
                            <!-- Name Input -->
                            <div class="mb-2">
                                <b-form-group>
                                    <label class="required">Name</label>
                                    <b-form-input v-model="formData.name" placeholder="Enter Name"
                                        @input="removeError('name')" />
                                    <div class="text-danger" v-if="hasErrors('name')">
                                        {{ getErrors("name") }}
                                    </div>
                                </b-form-group>
                            </div>

                            <div class="mb-2">
                                <label class="required">Description</label>
                                <b-form-textarea v-model="formData.description" @input="removeError('description')"
                                    placeholder="Enter Description" rows="3" />
                                <div class="text-danger" v-if="hasErrors('description')">
                                    {{ getErrors("description") }}
                                </div>
                            </div>

                            <div class="mb-2">
                                <label class="required">Status</label>
                                <v-select v-model="formData.status" :options="statusOptions" label="label"
                                    :reduce="(val) => val.value" :clearable="true" placeholder="Select Status"
                                    @update:modelValue="removeError('status')" class="multiDrop" />
                                <div class="text-danger" v-if="hasErrors('status')">
                                    {{ getErrors("status") }}
                                </div>
                            </div>
                        </div>

                        <div class="sidebarbtn_group">
                            <div class="buttonGrid">
                                <b-button type="submit" class="fillBTN" :disabled="isSubmitting">
                                    <b-spinner small v-if="isSubmitting" class="me-1"></b-spinner>
                                    {{
                                        isSubmitting
                                            ? isEditMode
                                                ? "Updating..."
                                                : "Submitting..."
                                            : isEditMode
                                    ? "Update"
                                    : "Submit"
                                    }}
                                </b-button>
                                <b-button class="transBTN" @click="closeSidebar">
                                    Cancel
                                </b-button>
                            </div>
                        </div>
                    </b-form>
                </div>
            </div>
        </div>

        <!-- Delete Modal -->
        <b-modal id="delete-modal" v-model="deleteModal" hide-header hide-footer centered size="md" class="text-center">
            <h6>Are you sure you want to delete this item?</h6>

            <b-button class="btn_secondary_border me-2" @click="deleteModal = false">Cancel</b-button>
            <b-button class="btn_primary" @click="confirmDelete">Delete</b-button>
        </b-modal>
        <!-- Status Modal -->
        <b-modal v-model="showStatusModal" hide-header hide-footer centered size="md" class="customModal"
            title="Change Status">
            <div class="contentFrame">
                Are you sure you want to change the status of
                <strong>{{ selectedItem?.name }}</strong>?
            </div>

            <div class="footerFrame buttonGrid">
                <b-button class="transBTN" @click="showStatusModal = false">Cancel</b-button>
                <b-button class="fillBTN" @click="confirmChangeStatus">Yes, Change</b-button>
            </div>
        </b-modal>
    </div>
</template>



<script setup>
import { ref, computed, onMounted, watch, defineEmits } from "vue";
import axiosEmployee from '@axiosEmployee';
import vSelect from "vue-select";
import "vue-select/dist/vue-select.css";
import CloseIcon from "../../../assets/img/icons/Close.vue";
import { saveAs } from "file-saver";
import { toast } from "vue3-toastify";
import "vue3-toastify/dist/index.css";
import { useRoute, useRouter } from "vue-router";
import tagNoData from "../../../components/noData.vue";


const router = useRouter();
const route = useRoute();
const encodeBase64 = (data) => {
    if (data === undefined || data === null) {
        return "";
    }
    return btoa(data.toString());
};
const currentPage = ref(1);
const perPage = ref(10);
const perPageOptions = [10, 25, 50, 100];

const loading = ref(true);

const paginatedData = ref([]);

const sortBy = ref("");
const sortDesc = ref(false);
const isEditMode = ref(false);
const deleteModal = ref(false);
const deleteId = ref(null);
const totalrows = ref(0);
const isSubmitting = ref(false);

const search = ref({
    name: null,
    description: null,
    globalSearch: null,
});

const formData = ref({
    id: null,
    name: "",
    description: "",
});
const statusOptions = [
    { label: "Active", value: "A" },
    { label: "InActive", value: "I" },
];
const errors = ref({});

const sidebarstatus = ref({
    add: false,
    filter: false,
});

const fields = [
    { key: "name", label: "Name", sortable: true },
    { key: "description", label: "Description", sortable: true },
    { key: "status", label: "Status", sortable: true },
    { key: "actions", label: "Actions" },
];
const formattedTitle = (text) => {
    if (text) {
        return text.charAt(0).toUpperCase() + text.slice(1);
    }
    return text;
};



const fetchTagData = () => {
    loading.value = true;
    return axiosEmployee
        .get(`tag/`, {
            params: {
                name: search.value.name,
                description: search.value.description,
                globalSearch: search.value.globalSearch,
                page: currentPage.value,
                perPage: perPage.value,
                sortBy: sortBy.value,
                sortDesc: sortDesc.value,
            },
        })
        .then((response) => {
            paginatedData.value = response.data.data;
            totalrows.value =
                response.data.data.length > 0
                    ? response.data.data[0].total_row_count
                    : 0;
            loading.value = false;
        }).catch((error) => {
            loading.value = false;
            console.error("Error fetching data:", error);
        });
};

const rangeStart = computed(() =>
    totalrows.value === 0 ? 0 : (currentPage.value - 1) * perPage.value + 1
);
const rangeEnd = computed(() =>
    currentPage.value * perPage.value >= totalrows.value
        ? totalrows.value
        : currentPage.value * perPage.value
);

const changePage = (pageNumber) => {
    if (
        pageNumber < 1 ||
        pageNumber > totalPages.value ||
        pageNumber === currentPage.value
    )
        return;
    currentPage.value = pageNumber;
    fetchTagData();
};

const searchFilter = () => {
    currentPage.value = 1;
    fetchTagData().then(() => {
        sidebarstatus.value.filter = false;
    });
};

const resetFilter = () => {
    search.value = {
        name: null,
        description: null,
        globalSearch: null,
    };
    fetchTagData();
    sidebarstatus.value.filter = false;
    router.replace({
        query: {
            ...route.query,
            action: undefined,
            id: undefined,
        },
    });
};

const toggleFilterSidebar = () => {
    sidebarstatus.value.filter = !sidebarstatus.value.filter;
    if (sidebarstatus.value.filter) {
        sidebarstatus.value.add = false;
    }
};

const openAddSidebar = () => {
    isEditMode.value = false;
    formData.value = {
        id: null,
        name: "",
        description: "",
    };
    errors.value = {};
    sidebarstatus.value.add = true;
    sidebarstatus.value.filter = false;
    router.replace({
        query: {
            ...route.query,
            action: "add",
        },
    });
};

const openEditSidebar = (item) => {
    isEditMode.value = true;
    formData.value = { ...item };
    errors.value = {};
    sidebarstatus.value.add = true;
    sidebarstatus.value.filter = false;
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
    router.replace({
        query: {
            ...route.query,
            action: undefined,
            id: undefined,
        },
    });
};

const openDeleteModal = (id) => {
    deleteId.value = id;
    deleteModal.value = true;
};

const confirmDelete = async () => {
    try {
        const response = await axiosEmployee.delete(`tag/${deleteId.value}`);

        toast(response.data.message || "Deleted successfully!", {
            type: "success",
            autoClose: 1000,
        });
        deleteModal.value = false;
        fetchTagData();
    } catch (error) {
        toast("Error deleting item!", {
            type: "error",
            autoClose: 1000,
        });
    }
};
const handleFormSubmit = async () => {
    isSubmitting.value = true;
    errors.value = {};

    try {
        if (isEditMode.value) {
            await axiosEmployee.put(`tag/${formData.value.id}`, formData.value);
            sidebarstatus.value.add = false;
            closeSidebar();

            setTimeout(() => {
                toast("Updated successfully!", {
                    type: "success",
                    autoClose: 1000,
                });
            }, 300); // delay to ensure sidebar is gone
        } else {
            await axiosEmployee.post("tag", formData.value);
            sidebarstatus.value.add = false;
            closeSidebar();

            setTimeout(() => {
                toast("Added successfully!", {
                    type: "success",
                    autoClose: 1000,
                });
            }, 300);
        }

        fetchTagData();
    } catch (error) {
        // errors.value = err.response?.data?.errors || {};
        if (error.response && error.response.status === 422) {
            errors.value = error.response.data.errors;
        }

    } finally {
        isSubmitting.value = false;
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
            `/tags/${selectedItem.value.id}/change-status`
        );
        if (response.data.status === "success") {
            toast.success(response.data.message || "Status updated successfully!");
            fetchTagData();
        } else {
            toast.error(response.data.message || "Failed to update status");
        }
    } catch (error) {
        toast.error(error.response?.data?.message || "Failed to update status");
    } finally {
        showStatusModal.value = false;
    }
};
const sidebarImport = ref(false);
const sampleExcelLink = ref("/files/sample-template.xlsx");
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
    bulkFileInput.value.value = ""; // Reset input
};

const triggerFileSelect = () => {
    bulkFileInput.value.click();
};

const hasErrors = (field) => !!errors.value[field];
const getErrors = (field) => errors.value[field]?.[0] || "";
const removeError = (field) => delete errors.value[field];

const resetPage = () => {
    currentPage.value = 1;
    fetchTagData();
};
onMounted(() => {
    fetchTagData();
    const query = route.query;
    if (query.action === "add") {
        openAddSidebar();
    } else if (query.action === "edit" && query.id) {
        const decodedId = atob(query.id);
        const row = paginatedData.value.find((i) => i.id == decodedId);
        if (row) openEditSidebar(row);
    }
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
    fetchTagData();
};

const getSortIcon = (field) => {
    if (sortBy.value === field) {
        return sortDesc.value ? "fas fa-sort-down" : "fas fa-sort-up";
    }
    return "fas fa-sort";
};
const filterName = ref("");

const exportTag = async () => {
    try {
        const response = await axiosEmployee.post(
            "export-tag",
            {
                name: filterName.value,
            },
            {
                responseType: "blob",
            }
        );

        const blob = new Blob([response.data], {
            type: "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
        });
        saveAs(blob, "Tag.xlsx");
    } catch (error) {
        //
    }
};
const withPopper = (dropdownList, component, { width }) => {
    dropdownList.style.width = width;
    const popper = createPopper(component.$refs.toggle, dropdownList, {
        placement: "bottom",
        modifiers: [
            {
                name: "offset",
                options: {
                    offset: [0, -1],
                },
            },
            {
                name: "toggleClass",
                enabled: true,
                phase: "write",
                fn({ state }) {
                    component.$el.classList.toggle("drop-up", state.placement === "top");
                },
            },
        ],
    });
    return () => popper.destroy();
};
watch(perPage, () => {
    resetPage();
});

const totalPages = computed(() => {
    return Math.ceil(totalrows.value / perPage.value);
});

const emit = defineEmits(["triggerBackToMaster"]);
const handleBackClick = () => {
    router.replace({ name: "admin.settings", query: { tab: "ProductManagement" } });
};

const paginationRange = computed(() => {
    const total = totalPages.value;
    const current = currentPage.value;
    const delta = 2;
    const range = [];
    let l = 0;

    for (let i = 1; i <= total; i++) {
        if (
            i === 1 ||
            i === total ||
            (i >= current - delta && i <= current + delta)
        ) {
            range.push({ number: i, key: i, isEllipsis: false });
            l = i;
        } else if (l && i - l > 1) {
            range.push({ number: "...", key: `ellipsis-${i}`, isEllipsis: true });
            l = i;
        }
    }

    return range;
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
</script>
<style scoped>
.flipped-toggle {
    transform: rotateY(180deg);
}
</style>