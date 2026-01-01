<template>
  <div class="listing_screen global_table_liting fixHeight">
    <h2 class="masterHeading">Sub Category</h2>
    <div class="listing_tab_and_actions mb-3">
      <div class="listing_actions">
        <div class="d-flex">
          <div class="listing_search">
            <img
              src="../../../assets/img/header/search.svg"
              class="listing_search_icon"
              alt="search"
            />
            <b-form-input
              v-model="search.globalSearch"
              @input="searchFilter"
              placeholder="Search Here..."
            />
          </div>
          <b-button
            title="filter"
            class="btn_listing_action"
            @click="sidebarstatus.filter = !sidebarstatus.filter"
          >
            <img src="../../../assets/img/filter.svg" alt="filter" /> Filter
          </b-button>
        </div>
        <div class="buttonGrid">
          <b-button class="fillBTN"  v-if="$can('do', 'add_admin.masters.subcategories')" @click="openAddSidebar"
            >Add Sub Category</b-button
          >
          <b-button type="button" class="transBTN" @click="handleBackClick"
            >Back</b-button
          >
        </div>
      </div>

      <!-- Wrapper for background overlay -->
      <div :class="{ parentBackground: sidebarstatus.filter }">
        <div
          class="filter_sidebar sidebar_main"
          :class="{ filter_active: sidebarstatus.filter }"
        >
          <div class="sidebar_toolbox woBorder">
            <h6>Filter</h6>
            <div class="sidebar_toolbox_right_side">
              <CloseIcon @click="resetFilter" />
            </div>
          </div>

          <div class="sidebar_form">
            <b-form>
              <div class="px-4 py-3 column_sidebar">
                <label>Sub Category Name</label>
                <b-form-group>
                  <b-form-input
                    v-model="search.name"
                    placeholder="Search by Sub Category Name..."
                    
                  />
                </b-form-group>
                <label>Parent Category</label>
                <b-form-group>
                  <b-form-input
                    v-model="search.parent_name"
                    placeholder="Search by Parent Category..."
                   
                  />
                </b-form-group>
                <label>Description</label>
                <b-form-group>
                  <b-form-input
                    v-model="search.description"
                    placeholder="Search by Description..."
                  
                  />
                </b-form-group>
              </div>
              <div class="sidebarbtn_group">
                <div class="buttonGrid">
                  <b-button type="submit" @click="searchFilter" class="fillBTN"
                    >Apply</b-button
                  >
                  <b-button class="transBTN" @click="resetFilter"
                    >Reset</b-button
                  >
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
        <span 
          v-if="value !== null && value !== ''" 
          class="selected_filter_item"
        >
          {{ value }}
          <i 
            class="fa-solid fa-xmark" 
            @click="() => { search[key] = null; searchFilter() }"
          ></i>
        </span>
      </template>
    </div>

     <div v-if="loading" class="text-center p-5">
      <b-spinner variant="#404054" label="Loading..."></b-spinner>
      <p class="mt-2">Loading sub category...</p>
    </div>

<div v-else>

    <b-table responsive="sm" 
      :items="paginatedData"
      :fields="fields"
      @sort-changed="onSortChanged"
       v-if="$can('do', 'list_admin.masters.subcategories') && paginatedData.length > 0"
    >
      <template #head(name)="data">
        <span @click="changeSorting('name')">
          Sub Category
          <i :class="getSortIcon('name')"></i>
        </span>
      </template>
      <template #cell(name)="data">
        <span>{{ formattedTitle(data.item.name) }}</span>
      </template>
      <template #head(description)="data">
        <span @click="changeSorting('description')">
          Description
          <i :class="getSortIcon('description')"></i>
        </span>
      </template>
      <template #cell(description)="data">
        <span>{{ formattedTitle(data.item.description) }}</span>
      </template>
      <template #head(parent_category)="data">
        <span @click="changeSorting('parent_category')">
          Parent Category
          <i :class="getSortIcon('parent_category')"></i>
        </span>
      </template>
      <template #cell(parent_category)="data">
        <span>{{ formattedTitle(data.item.parent_category) }}</span>
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
        <i
          class="fa-solid fa-toggle-on ms-2"
          :class="[
            data.item.status === 'A' ? 'text-success' : 'text-danger',
            data.item.status !== 'A' ? 'flipped-toggle' : '',
          ]"
          @click="openChangeStatusModal(data.item)"
          style="cursor: pointer; font-size: 1.1rem"
        ></i>
      </template>

      <template #cell(actions)="data">
   
        <b-dropdown right text="⋮" variant="link" no-caret toggle-class="p-0">
          <b-dropdown-item v-if="$can('do', 'edit_admin.masters.subcategories')" @click="openEditSidebar(data.item)"
            >Edit</b-dropdown-item
          >
          <!-- <b-dropdown-item :to="`/category/${encodeBase64(data.item.id)}`"
            >View</b-dropdown-item
          > -->
          <b-dropdown-item v-if="$can('do', 'delete_admin.masters.subcategories')" @click="openDeleteModal(data.item.id)"
            >Delete</b-dropdown-item
          >
        </b-dropdown>
      </template>
    </b-table>


         <subCategoryNoData
            v-if="inqFilterStatus && paginatedData.length === 0 || !inqFilterStatus && sidebarstatus.filter"
            heading="No Records Found"
            subheading="Try adjusting your filters or search criteria."
            :showButton="false"
          />

          <subCategoryNoData
            v-else-if="paginatedData.length === 0"
            heading="No Sub Category Created Yet"
            subheading="You haven't added any sub category yet. Add your first sub category to get started."
            :showButton="$can('do', 'add_admin.masters.subcategories')"
            buttonText="Add Sub Category"
            @button-clicked="openAddSidebar"
          />


    <div class="tablecounter" v-if="paginatedData.length > 0">
       <div class="show_entries">
        <span class="mr-2 count">Show</span>
        <v-select append-to-body :calculate-position="withPopper" @update:modelValue="resetPage" v-model="perPage" :options="perPageOptions" :clearable="false" class="mr-2" />
        <span class="count">entries</span>
      </div>
      <div class="count">
        Showing {{ rangeStart }} to {{ rangeEnd }} of {{ totalrows }}
      </div>

      <nav aria-label="Page navigation" class="paginarton">
        <ul class="pagination">
          <!-- Previous button -->
          <li
            class="page-item"
            :class="{ disabled: currentPage === 1 }"
            @click="changePage(currentPage - 1)"
          >
            <a class="page-link" href="#" tabindex="-1">Previous</a>
          </li>

          <!-- Page numbers -->
          <li
            v-for="page in paginationRange"
            :key="page.key"
            class="page-item"
            :class="{
              active: page.number === currentPage,
              disabled: page.isEllipsis,
            }"
            @click="!page.isEllipsis && changePage(page.number)"
            style="cursor: pointer"
          >
            <a class="page-link" href="#">
              {{ page.isEllipsis ? "..." : page.number }}
            </a>
          </li>

          <!-- Next button -->
          <li
            class="page-item"
            :class="{ disabled: currentPage === totalPages }"
            @click="changePage(currentPage + 1)"
          >
            <a class="page-link" href="#">Next</a>
          </li>
        </ul>
      </nav>
    </div>
</div>


    <b-modal
      id="delete-modal"
      class="text-center"
      v-model="deleteModal"
      hide-header
      hide-footer
      size="md"
      centered
    >
      <h6 class="mb-3">Are you sure you want to delete this item?</h6>
      <b-button class="btn_secondary_border me-2" @click="deleteModal = false">
        Cancel
      </b-button>
      <b-button class="btn_primary" @click="confirmDelete"> Delete </b-button>
    </b-modal>

    <!-- Parent wrapper for overlay background -->
    <div :class="{ parentBackground: sidebarstatus.add }">
      <!-- Sidebar content -->
      <div
        class="assigned_sidebar sidebar_main"
        :class="{ assigned_active: sidebarstatus.add }"
      >
        <div class="sidebar_toolbox woBorder">
          <h6>
            {{ isEditMode ? "Update Sub Category" : "Add Sub Category" }}
          </h6>
          <div class="sidebar_toolbox_right_side">
            <CloseIcon @click="closeSidebar" />
          </div>
        </div>

        <div class="sidebar_form">
          <b-form @submit.prevent="handleFormSubmit">
            <div class="px-4 py-3 column_sidebar">
              <div class="mb-2">
                <label class="required"
                  >Parent Category</label
                >
                <v-select
                  v-model="formData.parent_id"
                  :options="subCategoryOptions"
                  :reduce="(val) => val.id"
                  label="name"
                  :clearable="true"
                  placeholder="Select Category"
                  class="multiDrop"
                  @update:modelValue="RemoveError('parent_id')"
                />
                <div class="text-danger" v-if="hasErrors('parent_id')">
                  {{ getErrors("parent_id") }}
                </div>
              </div>
              <div class="mb-2">
                <label class="required"
                  >Sub Category Name</label
                >
                <b-form-input
                  id="name"
                  v-model="formData.name"
                  @input="RemoveError('name')"
                  placeholder="Enter Category Name"
                  autocomplete="off"
                />
                <small class="text-danger">{{ errors[0] }}</small>
                <div class="text-danger" v-if="hasErrors('name')">
                  {{ getErrors("name") }}
                </div>
              </div>
              
              <div class="mb-2">
                <label class="required">Description</label>
                <b-form-textarea
                  v-model="formData.description"
                  @input="RemoveError('description')"
                  placeholder="Enter Description"
                  rows="3"
                />
                <div class="text-danger" v-if="hasErrors('description')">
                  {{ getErrors("description") }}
                </div>
              </div>

              <div class="mb-2">
                <label class="required">Status</label>
                <v-select
                  v-model="formData.status"
                  :options="statusOptions"
                  label="label"
                  :reduce="(val) => val.value"
                  :clearable="true"
                  placeholder="Select Status"
                  @update:modelValue="RemoveError('status')"
                  class="multiDrop"
                />
                <div class="text-danger" v-if="hasErrors('status')">
                  {{ getErrors("status") }}
                </div>
              </div>
            </div>

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
      </div>
    </div>
    <b-modal
      v-model="showStatusModal"
      hide-header
      hide-footer
      centered
      size="md"
      class="customModal"
      title="Change Status"
    >
      <div class="contentFrame">
        Are you sure you want to change the status of
        <strong>{{ selectedItem?.name }}</strong
        >?
      </div>

      <div class="footerFrame buttonGrid">
        <b-button class="transBTN"  @click="showStatusModal = false"
          >Cancel</b-button
        >
        <b-button class="fillBTN" @click="confirmChangeStatus"
          >Yes, Change</b-button
        >
      </div>
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
import CloseIcon from "../../../assets/img/icons/Close.vue";
import { createPopper } from "@popperjs/core";
import subCategoryNoData from "../../../components/noData.vue";


const router = useRouter();

const emit = defineEmits(["triggerBackToMaster"]);
const handleBackClick = () => {
    router.replace({ name: "admin.settings", query: { tab: "ProductManagement" } });
};

const subCategoryOptions = ref([]);

onMounted(() => {
  fetchSubCategory();
  fetchSubCategoryOptions();
});
const sortBy = ref(null);
const sortDesc = ref(false);
const statusOptions = [
  { label: "Active", value: "A" },
  { label: "InActive", value: "I" },
];
const fetchSubCategoryOptions = () => {
  axiosEmployee.get("subCategoryOptions").then((response) => {
    subCategoryOptions.value = response.data.data;
  });
};
const onSortChanged = (ctx) => {
  sortBy.value = ctx.sortBy;
  sortDesc.value = ctx.sortDesc;
};

const changeSorting = (field) => {
  if (sortBy.value === field) {
    sortDesc.value = !sortDesc.value;
  } else {
    sortBy.value = field;
    sortDesc.value = false;
  }
  fetchSubCategory();
};

const getSortIcon = (field) => {
  if (sortBy.value === field) {
    return sortDesc.value ? "fas fa-sort-down" : "fas fa-sort-up";
  }
  return "fas fa-sort";
};

const optionMessage = ref(null);
const search = ref({
  name: null,
  parent_name: null,
  description: null,
  globalSearch: null,
});
const route = useRoute();

const paginatedData = ref([]);
const loading = ref(true);

const isEditMode = ref(false);
const formData = ref({
  name: "",
  parent_id: "",
  description: "",
  status: "",
});
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
const deleteId = ref(null);

const openDeleteModal = (id) => {
  deleteId.value = id;
  deleteModal.value = true;
};

const changePage = (pageNumber) => {
  if (
    pageNumber < 1 ||
    pageNumber > totalPages.value ||
    pageNumber === currentPage.value
  )
    return;
  currentPage.value = pageNumber;
};

const totalPages = computed(() => {
  return Math.ceil(totalrows.value / perPage.value);
});

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

const searchFilter = () => {
  currentPage.value = 1;
  fetchSubCategory();
  sidebarstatus.value.filter = false;
};

const resetPage = () => {
  currentPage.value = 1; // Reset to first page
  fetchSubCategory();
  // const container = document.querySelector(".masterTabContent");
  // window.scrollTo({
  //     top: 0,
  //     behavior: "smooth"
  //   });
};

const fetchSubCategory = () => {
    loading.value = true;
  axiosEmployee
    .get(`/sub-categories/`, {
      params: {
        name: search.value.name,
        parent_name: search.value.parent_name,
        description: search.value.description,
        globalSearch: search.value.globalSearch,
        page: currentPage.value,
        perPage: perPage.value,
        sortBy: sortBy.value,
        sortDesc: sortDesc.value,
      },
    })
    .then((response) => {
      paginatedData.value = response.data;
      totalrows.value =
        response.data.length > 0 ? response.data[0].total_row_count : 0;
          loading.value = false;
    }).catch((error) => {
      loading.value = false;
      console.error("Error fetching data:", error);
    });
};

const resetFilter = () => {
  search.value = {
    name: null,
    parent_name: null,
    description: null,
    globalSearch: null,
  };

  fetchSubCategory();

  sidebarstatus.value.filter = false;
};

const resetSearch = () => {
  fetchSubCategory();
};

const openAddSidebar = () => {
  sidebarstatus.value.add = true;
  isEditMode.value = false;
  formData.value = { name: "" };
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

  const selectedParent = subCategoryOptions.value.find(
    (cat) => cat.name === item.parent_category
  );

  formData.value = {
    name: item.name,
    parent_id: selectedParent?.id || "",
    description: item.description,
    status: item.status,
  };

  currentItem.value = item;

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
  formData.value = {
    name: null,
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

const handleFormSubmit = () => {
  errors.value = {};

  if (!formData.value.name || formData.value.name.trim() === "") {
    errors.value.name = ["Sub Category Name is required."];
  }

  if (!formData.value.parent_id) {
    errors.value.parent_id = ["Parent Category is required."];
  }

  if (Object.keys(errors.value).length > 0) {
    return;
  }

  if (isEditMode.value) {
    axiosEmployee
      .put(`/category/${currentItem.value.id}`, formData.value)
      .then(() => {
        sidebarstatus.value.add = false;
        closeSidebar();
        setTimeout(() => {
        toast("Updated successfully!", {
          type: "success",
          autoClose: 1000,
          });
        }, 300);
     
        fetchSubCategory();
      
      })
      .catch((error) => {
        if (error.response?.data?.code === 422) {
          errors.value = error.response.data.errors;
        } else {
          toast.error("Failed to update sub-category.");
        }
      });
  } else {
    axiosEmployee
      .post(`/category`, formData.value)
      .then(() => {
       sidebarstatus.value.add = false;
       closeSidebar();
       setTimeout(() => {
        toast("Added successfully!", {
          type: "success",
          autoClose: 1000,
        });
      }, 300);
      
        fetchSubCategory();
     
      })
      .catch((error) => {
        if (error.response?.data?.code === 422) {
          errors.value = error.response.data.errors;
        } else {
          toast.error("Failed to add sub-category.");
        }
      });
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
      `/category/${selectedItem.value.id}/change-status`
    );
    if (response.data.status === "success") {
      toast.success(response.data.message || "Status updated successfully!");
      fetchSubCategory();
    } else {
      toast.error(response.data.message || "Failed to update status");
    }
  } catch (error) {
    toast.error(error.response?.data?.message || "Failed to update status");
  } finally {
    showStatusModal.value = false;
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

const confirmDelete = async () => {
  try {
    const response = await axiosEmployee.delete(`category/${deleteId.value}`);

    toast(response.data.message || "Deleted successfully!", {
      type: "success",
      autoClose: 1000,
    });
    deleteModal.value = false;
    fetchSubCategory();
  } catch (error) {
    toast("Error deleting item!", {
      type: "error",
      autoClose: 1000,
    });
  }
};

const fields = [
  { key: "name", label: "Sub Category Name", sortable: true },
  { key: "description", label: "Description", sortable: true },
  { key: "parent_category", label: "Parent Category ", sortable: true },
  { key: "status", label: "Status", sortable: true },
  { key: "actions", label: "Actions" },
];

const formattedTitle = (text) => {
  if (text) {
    return text.charAt(0).toUpperCase() + text.slice(1); // Capitalize the first letter
  }
  return text;
};
const sidebarstatus = ref({
  shadow: false,
  add: false,
  assigned: false,
});

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
watch(sidebarstatus.value, async (newstatus, oldstatus) => {
  if (newstatus.filter || newstatus.add) {
    sidebarstatus.value.shadow = true;
  } else {
    sidebarstatus.value.shadow = false;
  }
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

watch([currentPage, perPage], fetchSubCategory);
</script>
<style scoped>
.flipped-toggle {
  transform: rotateY(180deg);
}
</style>