<template>
  <div class="listing_screen global_table_liting">
    <h2 class="masterHeading">Metal Type</h2>
    <!-- Search and Actions -->
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
            @click="toggleFilterSidebar"
          >
            <img src="../../../assets/img/filter.svg" alt="filter" /> Filter
          </b-button>
        </div>
        <div class="buttonGrid">
          <b-button
            class="fillBTN"
            v-if="$can('do', 'add_admin.masters.materialtypes')"
            @click="openAddSidebar"
            >Add</b-button
          >
          <b-button type="button" class="transBTN" @click="handleBackClick"
            >Back</b-button
          >
        </div>
      </div>

      <!-- Filter Sidebar -->
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
            <b-form @submit.prevent="searchFilter">
              <div class="px-4 py-3 column_sidebar">
                <label>Name</label>
                <b-form-group>
                  <b-form-input
                    v-model="search.name"
                    placeholder="Search by Name..."
                    trim
                  />
                </b-form-group>
                <label>Purity</label>
                <b-form-group>
                  <b-form-input
                    v-model="search.purity_id"
                    placeholder="Search by Purity..."
                    trim
                  />
                </b-form-group>
               
                <label>Price Per Gram (₹)</label>
                <b-form-group>
                  <b-form-input
                    v-model="search.price_per_gram"
                    placeholder="Search by Price Per Gram (₹)..."
                    onkeypress="return (event.charCode !=8 && event.charCode ==0 || (event.charCode >= 48 && event.charCode <= 57))" 
                    trim
                  />
                </b-form-group>
               
              </div>
              <div class="sidebarbtn_group">
                <div class="buttonGrid">
                  <b-button type="submit" class="fillBTN">Apply</b-button>
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

    <!-- Table Listing -->
    <div class="filter_selected px-4" v-if="inqFilterStatus">
      <span class="selected_filter_item_icon me-2"
        ><i class="fa-solid fa-sliders"></i
      ></span>
      <template v-for="(value, key) in search" :key="key">
        <span
          v-if="value !== null && value !== ''"
          class="selected_filter_item"
        >
          {{ value }}
          <i
            class="fa-solid fa-xmark"
            @click="
              () => {
                search[key] = null;
                searchFilter();
              }
            "
          ></i>
        </span>
      </template>
    </div>

     <div v-if="loading" class="text-center p-5">
      <b-spinner variant="#404054" label="Loading..."></b-spinner>
      <p class="mt-2">Loading metal type...</p>
    </div>

     <div v-else>


    <b-table
      responsive="sm"
      :items="paginatedData"
      :fields="fields"
      @sort-changed="onSortChanged" 
      v-if="$can('do', 'list_admin.masters.materialtypes') && paginatedData.length > 0"   
    >
      <!-- Table columns and cells -->
      <template #head(metal_name)>
        <span @click="changeSorting('metal_name')">
          Metal Name <i :class="getSortIcon('metal_name')"></i>
        </span>
      </template>
      <template #cell(metal_name)="data">
        <span>{{ formattedTitle(data.item.metal_name) }}</span>
      </template>

      <template #head(purity_id)>
        <span @click="changeSorting('purity_id')">
          Purity <i :class="getSortIcon('purity_id')"></i>
        </span>
      </template>
      <template #cell(purity_id)="data">
        <span>{{ data.item.purity_id??'-' }}</span>
      </template>

      <template #head(color)>
        <span @click="changeSorting('color')">
          Color <i :class="getSortIcon('color')"></i>
        </span>
      </template>
      <template #cell(color)="data">
        <span>{{ formattedTitle(data.item.color) }}</span>
      </template>

      <template #head(price_per_gram)>
        <span @click="changeSorting('price_per_gram')">
          Price Per Gram (₹)
          <i :class="getSortIcon('price_per_gram')"></i>
        </span>
      </template>
      <template #cell(price_per_gram)="data">
        <span>{{ data.item.price_per_gram }}</span>
      </template>

      <template #head(status)>
        <span @click="changeSorting('status')">
          Status <i :class="getSortIcon('status')"></i>
        </span>
      </template>
      <template #cell(status)="data">
        <i
          class="fa-solid fa-toggle-on ms-2"
          :class="[
            data.item.status === 'active' ? 'text-success' : 'text-danger',
            data.item.status !== 'active' ? 'flipped-toggle' : '',
          ]"
          @click="openChangeStatusModal(data.item)"
          style="cursor: pointer; font-size: 1.1rem"
        ></i>
      </template>

      <template #head(description)>
        <span @click="changeSorting('description')">
          Description <i :class="getSortIcon('description')"></i>
        </span>
      </template>
      <template #cell(description)="data">
        {{ formattedTitle(data.item.description) }}
      </template>

       <template #head(updated_at)>
        <span @click="changeSorting('updated_at')">
          Last Updated Date <i :class="getSortIcon('updated_at')"></i>
        </span>
      </template>

       <template #cell(updated_at)="data">
        {{ formatDateTime(data.item.updated_at) }}
      </template>

      <template #cell(actions)="data">
        <b-dropdown right text="⋮" variant="link" no-caret toggle-class="p-0">
          <b-dropdown-item
            v-if="$can('do', 'edit_admin.masters.materialtypes')"
            @click="openEditSidebar(data.item)"
            >Edit</b-dropdown-item
          >
          <b-dropdown-item 
           v-if="$can('do', 'view_admin.masters.materialtypes')"
          :to="`/admin/metal-type/view/${encodeBase64(data.item.id)}`"
            >View</b-dropdown-item
          >
          <b-dropdown-item
            v-if="$can('do', 'delete_admin.masters.materialtypes')"
            @click="openDeleteModal(data.item.id)"
            >Delete</b-dropdown-item
          >
        </b-dropdown>
      </template>
    </b-table>


         <metalNoData
            v-if="inqFilterStatus && paginatedData.length === 0 || !inqFilterStatus && sidebarstatus.filter"
            heading="No Records Found"
            subheading="Try adjusting your filters or search criteria."
            :showButton="false"
          />

          <metalNoData
            v-else-if="paginatedData.length === 0"
            heading="No Metal Type Yet"
            subheading="You haven't added any metal type yet. Add your first metal type to get started."
            :showButton="$can('do', 'add_admin.masters.materialtypes')"
            buttonText="Add Metal Type"
            @button-clicked="openAddSidebar"
          />
    <!-- Pagination -->
    <div class="tablecounter" v-if="paginatedData.length > 0">
      <div class="show_entries">
        <span class="mr-2 count">Show</span>
        <v-select
          append-to-body
          :calculate-position="withPopper"
          @update:modelValue="resetPage"
          v-model="perPage"
          :options="perPageOptions"
          :clearable="false"
          class="mr-2"
        />
        <span class="count">entries</span>
      </div>
      <div class="count">
        Showing {{ rangeStart }} to {{ rangeEnd }} of {{ totalrows }}
      </div>

      <nav aria-label="Page navigation" class="paginarton">
        <ul class="pagination">
          <li
            class="page-item"
            :class="{ disabled: currentPage === 1 }"
            @click="changePage(currentPage - 1)"
          >
            <a class="page-link" href="#" tabindex="-1">Previous</a>
          </li>

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
   

    <!-- Add/Edit Sidebar -->
    <div :class="{ parentBackground: sidebarstatus.add }">
      <div
        class="assigned_sidebar sidebar_main"
        :class="{ assigned_active: sidebarstatus.add }"
      >
        <div class="sidebar_toolbox woBorder">
          <h6>{{ isEditMode ? "Update Metal Type" : "Add Metal Type" }}</h6>
          <div class="sidebar_toolbox_right_side">
            <CloseIcon @click="closeSidebar" />
          </div>
        </div>

        <div class="sidebar_form">
          <b-form @submit.prevent="handleFormSubmit">
            <div class="p-3 column_sidebar">
              <!-- Metal Name Input with Add Button -->
              <b-form-group>
                <label class="required">Metal Name</label>
                <div class="d-flex align-items-center">
                  <v-select
                    v-model="formData.metal_name_id"
                    :options="getMetalNameOptions"
                    :reduce="(val) => val.id"
                    label="name"
                    :clearable="true"
                    placeholder="Select Metal Name"
                    @update:modelValue="removeError('metal_name_id')"
                    class="flex-grow-1"
                  />
                  <b-button
                    variant="primary"
                    class="addBTNAdditional p-0"
                    @click="openAddMetalNameModal"
                  >
                    <i class="fas fa-plus mr-1"></i>
                  </b-button>
                </div>
                <div class="text-danger" v-if="hasErrors('metal_name_id')">
                  {{ getErrors("metal_name_id") }}
                </div>
              </b-form-group>

              <b-form-group>
                <label class="required">Purity</label>
                <div class="d-flex align-items-center">
                  <v-select
                    v-model="formData.purity_id"
                    :options="getPurityOptions"
                    :reduce="(val) => val.id"
                    label="name"
                    :clearable="true"
                    placeholder="Select Purity"
                    @update:modelValue="removeError('purity_id')"
                    class="flex-grow-1 multiDrop"
                  />
                  <b-button
                    variant="primary"
                    class="addBTNAdditional p-0"
                    @click="openAddPurityModal"
                  >
                    <i class="fas fa-plus mr-1"></i>
                  </b-button>
                </div>
                <div class="text-danger" v-if="hasErrors('purity_id')">
                  {{ getErrors("purity_id") }}
                </div>
              </b-form-group>

              <b-form-group>
                <label class="required">Price per Gram (₹)</label>
                 
                <b-form-input
                  type="number"
                  v-model="formData.price_per_gram"
                  placeholder="Enter Price per Gram (₹)"
                  @input="validatePricePerGram"
                  min="0"
                  step="0.01"
                />
                <div class="text-danger" v-if="hasErrors('price_per_gram')">
                  {{ getErrors("price_per_gram") }}
                </div>
              </b-form-group>
              
              <b-form-group>
                <label>Description</label>
                <b-form-textarea
                  v-model="formData.description"
                  @input="removeError('description')"
                  placeholder="Enter Description"
                  rows="3"
                />
                <div class="text-danger" v-if="hasErrors('description')">
                  {{ getErrors("description") }}
                </div>
              </b-form-group>
            </div>

            <div class="sidebarbtn_group">
              <div class="buttonGrid">
                <b-button
                  type="submit"
                  class="fillBTN"
                  :disabled="isSubmitting"
                >
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
    <b-modal
      id="delete-modal"
      v-model="deleteModal"
      hide-header
      hide-footer
      centered
      size="md"
      class="text-center"
    >
      <h6>Are you sure you want to delete this Metal Type?</h6>
      <b-button class="btn_secondary_border me-2" @click="deleteModal = false"
        >Cancel</b-button
      >
      <b-button class="btn_primary" @click="confirmDelete">Delete</b-button>
    </b-modal>

    <!-- Add Metal Name Modal -->
    <b-modal
      v-model="showAddMetalNameModal"
      :title="isMetalNameEditMode ? 'Edit Metal Name' : 'Add New Metal Name'"
      hide-footer
    >
      <b-form-group>
        <label class="required">Metal Name</label>
        <b-form-input
          v-model="newMetalName.name"
          placeholder="Enter Metal Name (e.g., Gold, Silver, Platinum)"
          @input="removeMetalNameError('name')"
        />
        <div class="text-danger" v-if="hasMetalNameErrors('name')">
          {{ getMetalNameErrors("name") }}
        </div>
      </b-form-group>

      <div class="footerFrame buttonGrid">
        <b-button class="transBTN" @click="showAddMetalNameModal = false"
          >Cancel</b-button
        >
        <b-button
          class="fillBTN"
          @click="isMetalNameEditMode ? updateMetalName() : submitNewMetalName()"
        >
          {{ isMetalNameEditMode ? "Update" : "Submit" }}
        </b-button>
      </div>

      <hr />
      <h5>Metal Names</h5>
      <div class="global_table_liting tempring">
      <b-table :items="getMetalNameOptions" :fields="metalNameFields" small>
        <template #cell(actions)="row">
          <b-dropdown right text="⋮" variant="link" no-caret toggle-class="p-0">
            <b-dropdown-item
              v-if="$can('do', 'edit_admin.masters.materialtypes')"
              @click="editMetalName(row.item)"
              >Edit</b-dropdown-item
            >
            <b-dropdown-item
              v-if="$can('do', 'delete_admin.masters.materialtypes')"
               @click="openDeleteMetalNameModal(row.item)"
              >Delete</b-dropdown-item
            >
          </b-dropdown>

        </template>
      </b-table>
      
      </div>
    </b-modal>

    <!-- Add Purity Modal -->
    <b-modal
      v-model="showAddPurityModal"
      :title="isPurityEditMode ? 'Edit Purity' : 'Add New Purity'"
      hide-footer
    >
      <b-form-group>
        <label class="required">Karat Value</label>
        <b-form-input
          v-model="newPurity.name"
          @input="removePurityError('name')"
          placeholder="Enter Karat Value"
          @update:modelValue="removePurityError('name')"
        />
        <div class="text-danger" v-if="hasPurityErrors('name')">
          {{ getPurityErrors("name") }}
        </div>
      </b-form-group>

      <b-form-group>
        <label class="required">Purity%</label>
        <b-form-input
          v-model="newPurity.percentage"
          type="number"
          step="0.01"
          placeholder="Enter Purity%"
          @input="removePurityError('percentage')"
        />
        <div class="text-danger" v-if="hasPurityErrors('percentage')">
          {{ getPurityErrors("percentage") }}
        </div>
      </b-form-group>


      <div class="footerFrame buttonGrid">
        <b-button class="transBTN" @click="showAddPurityModal = false"
          >Cancel</b-button
        >
        <b-button
          class="fillBTN"
          @click="isPurityEditMode ? updatePurity() : submitNewPurity()"
        >
          {{ isPurityEditMode ? "Update" : "Submit" }}
        </b-button>
      </div>

      <hr />
      <h5>Purities</h5>
      
      <div class="global_table_liting tempring">
      <b-table :items="getPurityOptions" :fields="purityFields" small>
        <template #cell(actions)="row">
          <b-dropdown right text="⋮" variant="link" no-caret toggle-class="p-0">
            <b-dropdown-item
              v-if="$can('do', 'edit_admin.masters.materialtypes')"
              @click="editPurity(row.item)"
              >Edit</b-dropdown-item
            >
            <b-dropdown-item
              v-if="$can('do', 'delete_admin.masters.materialtypes')"
              @click="openPurityDataDeleteModal(row.item.id)"
              >Delete</b-dropdown-item
            >
          </b-dropdown>

        </template>
      </b-table>
      </div>
    </b-modal>

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
        <b-button class="transBTN" @click="showStatusModal = false"
          >Cancel</b-button
        >
        <b-button class="fillBTN" @click="confirmChangeStatus"
          >Yes, Change</b-button
        >
      </div>
    </b-modal>
    <b-modal
      id="delete-modal"
      v-model="deletePurityDataModal"
      hide-header
      hide-footer
      centered
      size="md"
    >
      <h6 class="mb-3">Are you sure you want to delete this item?</h6>
      <b-button
        class="btn_secondary_border me-2"
        @click="deletePurityDataModal = false"
      >
        Cancel
      </b-button>
      <b-button class="btn_primary" @click="confirmDeleteData">
        Delete
      </b-button>
    </b-modal>


     <!-- Delete Metal Name Modal -->
    <b-modal
      id="delete-metalname-modal"
      v-model="deleteMetalNameModal"
      hide-header
      hide-footer
      centered
      size="md"
    >
      <h6 class="mb-3">Are you sure you want to delete this Metal Name?</h6>
      <b-button class="btn_secondary_border me-2" @click="deleteMetalNameModal = false"
        >Cancel</b-button
      >
      <b-button class="btn_primary" @click="confirmDeleteMetalName">Delete</b-button>
    </b-modal>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch, reactive, defineEmits } from "vue";
import axiosEmployee from '@axiosEmployee';
import vSelect from "vue-select";
import "vue-select/dist/vue-select.css";
import CloseIcon from "../../../assets/img/icons/Close.vue";
import { toast } from "vue3-toastify";
import "vue3-toastify/dist/index.css";
import { useRoute, useRouter } from "vue-router";
import { nextTick } from 'vue';
import metalNoData from "../../../components/noData.vue";



const router = useRouter();
const route = useRoute();

const encodeBase64 = (data) => {
  if (data === undefined || data === null) {
    return "";
  }
  return btoa(data.toString());
};

// Metal Name Management
const isMetalNameEditMode = ref(false);
const editingMetalNameId = ref(null);
const showAddMetalNameModal = ref(false);
const getMetalNameOptions = ref([]);
const metalNameFields = [
  { key: "name", label: "Metal Name", sortable: true },
  { key: "actions", label: "Actions" }
];
const newMetalName = reactive({
  name: "",
  description: ""
});
const metalNameErrors = ref({});

// Purity Management
const isPurityEditMode = ref(false);
const editingPurityId = ref(null);
const showAddPurityModal = ref(false);
const getPurityOptions = ref([]);
const purityFields = [
  { key: "name", label: "Karat Value", sortable: true },
  { key: "percentage", label: "Percentage", sortable: true },
  { key: "actions", label: "Actions" }
];
const newPurity = reactive({
  name: "",
  percentage: "",
  description: ""
});
const purityErrors = ref({});

// Main component state
const currentPage = ref(1);
const perPage = ref(10);
const perPageOptions = [10, 25, 50, 100];
const isLoading = ref(false);
const loading = ref(true);
const paginatedData = ref([]);
const sortBy = ref("");
const sortDesc = ref(false);
const isEditMode = ref(false);
const deleteModal = ref(false);
const deleteId = ref(null);
const isSubmitting = ref(false);
const totalrows = ref(0);
const search = ref({
  name: "",
  purity_id: "",
  color: "",
  price_per_gram: "",
  globalSearch: "",
  description: "",
});
const formData = ref({
  id: null,
  metal_name_id: "",
  purity_id: "",
  color: "",
  density: "",
  price_per_gram: "",
  status: "active",
  description: "",
});
const errors = ref({});
const showStatusModal = ref(false);
const selectedItem = ref(null);
const sidebarstatus = ref({
  add: false,
  filter: false,
});
const fields = [
  { key: "metal_name", label: "Metal Name", sortable: true },
  { key: "purity_id", label: "Purity", sortable: true },
  { key: "price_per_gram", label: "Price per Gram (₹)", sortable: true },
  { key: "updated_at", label: "Last Updated Date", sortable: true },	
  { key: "actions", label: "Actions" },
];


const validatePricePerGram = () => {
  removeError('price_per_gram');
  
  // Ensure value is always a positive number
  if (formData.value.price_per_gram < 0) {
    formData.value.price_per_gram = 0;
  }
  
  // Round to 2 decimal places to prevent too many decimals
  if (formData.value.price_per_gram !== '') {
    formData.value.price_per_gram = parseFloat(formData.value.price_per_gram).toFixed(2);
  }
};

// Metal Name Methods
const openAddMetalNameModal = () => {
  resetMetalNameForm();
  isMetalNameEditMode.value = false;
  showAddMetalNameModal.value = true;
};

const resetMetalNameForm = () => {
  newMetalName.name = "";
  newMetalName.description = "";
  editingMetalNameId.value = null;
  metalNameErrors.value = {};
};

const submitNewMetalName = async () => {
  metalNameErrors.value = {};

  try {
    const response = await axiosEmployee.post("/metal-name", {
      name: newMetalName.name,
      description: newMetalName.description,
    });

    const addedMetalName = response.data.data;
    getMetalNameOptions.value.push(addedMetalName);
    // formData.value.metal_name_id = addedMetalName.id;

    nextTick(() => {
      formData.value.metal_name_id = addedMetalName.id;
    });

    resetMetalNameForm();
    showAddMetalNameModal.value = false;
    toast.success(response.data.message || "Metal name added successfully!");
  } catch (err) {
    if (err.response?.status === 422) {
      metalNameErrors.value = err.response.data.errors || {};
    } else {
      console.error(err);
      toast.error("Failed to add metal name");
    }
  }
};

const editMetalName = (item) => {
  isMetalNameEditMode.value = true;
  editingMetalNameId.value = item.id;

  newMetalName.name = item.name || "";
  newMetalName.description = item.description || "";
  showAddMetalNameModal.value = true;
};

const updateMetalName = async () => {
  metalNameErrors.value = {};

  try {
    const response = await axiosEmployee.put(`/metal-name/${editingMetalNameId.value}`, {
      name: newMetalName.name,
      description: newMetalName.description,
    });

    const updatedMetalName = response.data.data;

    const index = getMetalNameOptions.value.findIndex(
      (m) => m.id === editingMetalNameId.value
    );
    if (index !== -1) {
      getMetalNameOptions.value[index] = updatedMetalName;
    }

    resetMetalNameForm();
    isMetalNameEditMode.value = false;
    showAddMetalNameModal.value = false;
    toast.success(response.data.message || "Metal name updated successfully!");
  } catch (err) {
    if (err.response?.status === 422) {
      metalNameErrors.value = err.response.data.errors || {};
    } else {
      console.error(err);
      toast.error("Failed to update metal name");
    }
  }
};

const hasMetalNameErrors = (field) => !!metalNameErrors.value[field];
const getMetalNameErrors = (field) => metalNameErrors.value[field]?.[0] || "";
const removeMetalNameError = (field) => delete metalNameErrors.value[field];


// Purity Methods
const openAddPurityModal = () => {
  resetPurityForm();
  isPurityEditMode.value = false;
  showAddPurityModal.value = true;
};

const resetPurityForm = () => {
  newPurity.name = "";
  newPurity.percentage = "";
  newPurity.description = "";
  editingPurityId.value = null;
  purityErrors.value = {};
};

const submitNewPurity = async () => {
  purityErrors.value = {};

  try {
    const response = await axiosEmployee.post("/purity", {
      name: newPurity.name,
      percentage: newPurity.percentage,
      description: newPurity.description,
    });

    const addedPurity = response.data.data;
    getPurityOptions.value.push(addedPurity);
    // formData.value.purity_id = addedPurity.id;
     nextTick(() => {
      formData.value.purity_id = addedPurity.id;
    });

    resetPurityForm();
    showAddPurityModal.value = false;
    toast.success(response.data.message || "Purity added successfully!");
  } catch (err) {
    if (err.response?.status === 422) {
      purityErrors.value = err.response.data.errors || {};
    } else {
      console.error(err);
      toast.error("Failed to add purity");
    }
  }
};

const editPurity = (item) => {
  isPurityEditMode.value = true;
  editingPurityId.value = item.id;

  newPurity.name = item.name || "";
  newPurity.percentage = parseFloat(item.percentage) || "";
  newPurity.description = item.description || "";
  showAddPurityModal.value = true;
};

const updatePurity = async () => {
  purityErrors.value = {};

  try {
    const response = await axiosEmployee.put(`/purity/${editingPurityId.value}`, {
      name: newPurity.name,
      percentage: newPurity.percentage,
      description: newPurity.description,
    });

    const updatedPurity = response.data.data;

    const index = getPurityOptions.value.findIndex(
      (p) => p.id === editingPurityId.value
    );
    if (index !== -1) {
      getPurityOptions.value[index] = updatedPurity;
    }

    resetPurityForm();
    isPurityEditMode.value = false;
    showAddPurityModal.value = false;
    toast.success(response.data.message || "Purity updated successfully!");
  } catch (err) {
    if (err.response?.status === 422) {
      purityErrors.value = err.response.data.errors || {};
    } else {
      console.error(err);
      toast.error("Failed to update purity");
    }
  }
};

const hasPurityErrors = (field) => !!purityErrors.value[field];
const getPurityErrors = (field) => purityErrors.value[field]?.[0] || "";
const removePurityError = (field) => delete purityErrors.value[field];

// Main Component Methods
const formattedTitle = (text) => {
  if (text) {
    return text.charAt(0).toUpperCase() + text.slice(1);
  }
  return text;
};

const formatDateTime = (dateString) => {
  if (!dateString) return 'N/A';
  const date = new Date(dateString);
  if (isNaN(date.getTime())) return 'N/A';
  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, '0'); // Months 0-11 hote hain
  const day = String(date.getDate()).padStart(2, '0');
  
  return `${year}/${month}/${day}`;
};

const fetchMetalType = () => {
  loading.value = true;
  return axiosEmployee
    .get(`metal-type/`, {
      params: {
        name: search.value.name,
        purity_id: search.value.purity_id,
        color: search.value.color,
        price_per_gram: search.value.price_per_gram,
        globalSearch: search.value.globalSearch,
        description: search.value.description,
        page: currentPage.value,
        perPage: perPage.value,
        sortBy: sortBy.value,
        sortDesc: sortDesc.value,
      },
    })
    .then((response) => {
      paginatedData.value = response.data.data;
    //  totalrows.value =
     totalrows.value = response.data.total;


    loading.value = false;
    }).catch((error) => {
      loading.value = false;
      console.error("Error fetching data:", error);
    });
};

const fetchMetalNames = () => {
  axiosEmployee.get("getMetalNameOptions").then((response) => {
    getMetalNameOptions.value = response.data.data;
  });
};

const fetchPurity = () => {
  axiosEmployee.get("getPurityOptions").then((response) => {
    getPurityOptions.value = response.data.data;
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
  fetchMetalType();
};

const searchFilter = () => {
  currentPage.value = 1;
  fetchMetalType().then(() => {
    sidebarstatus.value.filter = false;
  });
};

const resetFilter = () => {
  search.value = {
    name: "",
    purity_id: "",
    color: "",
    price_per_gram: "",
    globalSearch: "",
    description: "",
  };
  fetchMetalType();
  sidebarstatus.value.filter = false;
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
    metal_name_id: "",
    // name: "",
    purity_id: "",
    color: "",
    density: "",
    price_per_gram: "",
    status: "active",
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
  formData.value = {
    ...item,
    metal_name_id: item.metal_name_id || "",
    purity_id: getPurityOptions.value.find((p) => p.name === item.purity_id)?.id || null,
  };
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

const bulkFileInput = ref(null);

const triggerFileSelect = () => {
  bulkFileInput.value.click();
};

const deletePurityDataModal = ref(false);
const deletePurityDataId = ref(null);

const openPurityDataDeleteModal = (id) => {
  showAddPurityModal.value = false;

  setTimeout(() => {
    deletePurityDataId.value = id;
    deletePurityDataModal.value = true;
  }, 300);
};

const confirmDeleteData = async () => {
  try {
    const response = await axiosEmployee.delete(`purity/${deletePurityDataId.value}`);

    getPurityOptions.value = getPurityOptions.value.filter(
      (p) => p.id !== deletePurityDataId.value
    );

    toast(response.data.message || "Deleted successfully!", {
      type: "success",
      autoClose: 1000,
    });
    deletePurityDataModal.value = false;
  } catch (error) {
    toast("Error deleting item!", {
      type: "error",
      autoClose: 1000,
    });
  }
};

const openDeleteModal = (id) => {
  deleteId.value = id;
  deleteModal.value = true;
};

const confirmDelete = async () => {
  try {
    const response = await axiosEmployee.delete(`metal-type/${deleteId.value}`);

    toast(response.data.message || "Deleted successfully!", {
      type: "success",
      autoClose: 1000,
    });
    deleteModal.value = false;
    fetchMetalType();
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
      await axiosEmployee.put(`metal-type/${formData.value.id}`, formData.value);
      sidebarstatus.value.add = false;
      closeSidebar();
      setTimeout(() => {
        toast("Updated successfully!", {
          type: "success",
          autoClose: 1000,
        });
      }, 300);
    } else {
      await axiosEmployee.post("metal-type", formData.value);
      sidebarstatus.value.add = false;
      closeSidebar();
      setTimeout(() => {
        toast("Added successfully!", {
          type: "success",
          autoClose: 1000,
        });
      }, 300);
    }
    fetchMetalType();
  } catch (error) {
    if (error.response && error.response.status === 422) {
      errors.value = error.response.data.errors;
    }
  } finally {
    isSubmitting.value = false;
  }
};

const openChangeStatusModal = (item) => {
  selectedItem.value = item;
  showStatusModal.value = true;
};

const confirmChangeStatus = async () => {
  try {
    const response = await axiosEmployee.post(
      `metal-type/${selectedItem.value.id}/change-status`
    );
    if (response.data.status === "success") {
      toast.success(response.data.message || "Status updated successfully!");
      fetchMetalType();
    } else {
      toast.error(response.data.message || "Failed to update status");
    }
  } catch (error) {
    toast.error(error.response?.data?.message || "Failed to update status");
  } finally {
    showStatusModal.value = false;
  }
};

const hasErrors = (field) => !!errors.value[field];
const getErrors = (field) => errors.value[field]?.[0] || "";
const removeError = (field) => delete errors.value[field];

const resetPage = () => {
  currentPage.value = 1;
  fetchMetalType();
};

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
  fetchMetalType();
};

const getSortIcon = (field) => {
  if (sortBy.value === field) {
    return sortDesc.value ? "fas fa-sort-down" : "fas fa-sort-up";
  }
  return "fas fa-sort";
};


const totalPages = computed(() =>
  Math.ceil(totalrows.value / perPage.value)
);


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

// Metal Name Management - Add these variables
const deleteMetalNameModal = ref(false);
const metalNameToDelete = ref(null);

// Metal Name Methods - Add these methods


const openDeleteMetalNameModal = (item) => {
  showAddMetalNameModal.value = false;
  setTimeout(() => {
    metalNameToDelete.value = item;
     deleteMetalNameModal.value = true;
  }, 300);
};

const confirmDeleteMetalName = async () => {
  try {
    const response = await axiosEmployee.delete(`metal-name/${metalNameToDelete.value.id}`);
    
    toast(response.data.message || "Metal name deleted successfully!", {
      type: "success",
      autoClose: 1000,
    });
    
    // Remove from local options
    const index = getMetalNameOptions.value.findIndex(
      m => m.id === metalNameToDelete.value.id
    );
    if (index !== -1) {
      getMetalNameOptions.value.splice(index, 1);
    }
    
    // If the deleted metal name was selected in the form, clear it
    if (formData.value.metal_name_id === metalNameToDelete.value.id) {
      formData.value.metal_name_id = "";
    }
    
    deleteMetalNameModal.value = false;
    metalNameToDelete.value = null;

    fetchMetalType();
    
  } catch (error) {
    if (error.response?.status === 422) {
      toast.error(error.response.data.error || "Cannot delete metal name. It is being used in metal types.");
    } else {
      toast.error("Error deleting metal name!");
    }
  }
};


const inqFilterStatus = ref(false);
watch(
  () => search.value,
  (newValue) => {
    inqFilterStatus.value = false;

    for (const key in newValue) {
      const val = newValue[key];

      if (Array.isArray(val)) {
        if (val.length > 0) {
          inqFilterStatus.value = true;
          break;
        }
      } else if (val !== null && String(val).trim() !== "") {
        inqFilterStatus.value = true;
        break;
      }
    }
  },
  { deep: true }
);

onMounted(() => {
  fetchMetalType();
  fetchMetalNames();
  fetchPurity();
  const query = route.query;
  if (query.action === "add") {
    openAddSidebar();
  } else if (query.action === "edit" && query.id) {
    const decodedId = atob(query.id);
    const row = paginatedData.value.find((i) => i.id == decodedId);
    if (row) openEditSidebar(row);
  }
});

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

watch(perPage, () => {
  resetPage();
});
</script>

<style scoped>
.switch {
  position: relative;
  display: inline-block;
  width: 50px;
  height: 28px;
}
.switch input {
  opacity: 0;
  width: 0;
  height: 0;
}
.slider {
  position: absolute;
  cursor: pointer;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: #ccc;
  transition: 0.4s;
  border-radius: 28px;
}
.slider:before {
  position: absolute;
  content: "";
  height: 22px;
  width: 22px;
  left: 4px;
  bottom: 3px;
  background-color: white;
  transition: 0.4s;
  border-radius: 50%;
}
input:checked + .slider {
  background-color: #0d6efd;
}
input:checked + .slider:before {
  transform: translateX(22px);
}
.flipped-toggle {
  transform: rotateY(180deg);
}

.addBTNAdditional {
  margin-left: 10px;
  padding: 0.375rem 0.75rem;
  border-radius: 0.25rem;
}

</style>
<style>
.tempring td:last-child, .tempring th:last-child{
  width: auto !important;
}
</style>