<template>
    <div>
        <div class="p-3">
            <div class="listing_tab_and_actions mb-3">
                <div class="listing_actions">
                    <div class="d-flex">
                        <div class="listing_search">
                            <img src="../assets/img/header/search.svg" class="listing_search_icon" alt="search" />
                            <b-form-input @input="searchFilter" placeholder="Search Here..." />
                        </div>
                        <b-button title="filter" class="btn_listing_action"
                            @click="sidebarstatus.filter = !sidebarstatus.filter">
                            <img src="../assets/img/filter.svg" alt="filter" /> Filter
                        </b-button>
                    </div>
                    <div class="buttonGrid">
                        <b-button class="transBTN" @click="openAddSidebar"><img src="../assets/img/filter.svg" alt="filter" />Export</b-button>
                        <b-button class="fillBTN" @click="openAddSidebar">Add</b-button>
                    </div>

                </div>

            </div>
            <b-table responsive="sm"  :items="paginatedData" :fields="fields" class="multiDrop">
                <template #head(selectAll)>
                    <b-form-checkbox v-model="selectAllChecked" @change="toggleSelectAll" aria-label="Select all" />
                </template>
                <template #cell(selectAll)="row">
                    <b-form-checkbox v-model="selectedItems" :value="row.item.id" aria-label="Select row" />
                </template>
                <template #cell(image)="row">
                    <img :src="row.item.image" alt="Product Image"
                        style="width: 50px; height: 50px; object-fit: cover;" />
                </template>
                <template #head(name)>
                    <span @click="changeSorting('name')" style="cursor: pointer;">
                        Name <i :class="getSortIcon('name')"></i>
                    </span>
                </template>
                <template #cell(name)="row">
                    {{ row.item.name }}
                </template>

                
                <template #cell(metalWeight)="row">
                    <b-form-input
                        id="input-2"
                        placeholder="Enter name"
                        required
                        type="number"
                    ></b-form-input>
                </template>

                
                <template #cell(gemstoneWeight)="row">
                    <v-select :options="foods" placeholder="Select an option"></v-select>
                </template>

                
                <template #head(description)>
                    <span @click="changeSorting('description')" style="cursor: pointer;">
                        Description <i :class="getSortIcon('description')"></i>
                    </span>
                </template>
                <template #cell(description)="row">
                    {{ row.item.description }}
                </template>
                
                <template #cell(price)="row">
                     <b-form-input
                        id="input-2"
                        placeholder="Enter name"
                        required
                        disabled
                    ></b-form-input>
                </template>

                <template #cell(status)="row">
                    <b-badge class="masterBadge">{{ row.item.status }}</b-badge>
                </template>

                <!-- Actions Column -->
                <template #cell(actions)="row">
                    <b-dropdown right text="⋮" variant="link" no-caret toggle-class="p-0">
                        <b-dropdown-item @click="openEditSidebar(row.item)">Edit</b-dropdown-item>
                        <b-dropdown-item @click="openDeleteModal(row.item.id)">Delete</b-dropdown-item>
                        <b-dropdown-item :to="`/product-type/${row.item.id}`">Product Details</b-dropdown-item>
                        <b-dropdown-item @click="openInventoryHistory(row.item)">Inventory History</b-dropdown-item>
                        <b-dropdown-item @click="manageVariants(row.item)">Manage Variants</b-dropdown-item>
                        <b-dropdown-item @click="exportProduct(row.item)">Export Product Info</b-dropdown-item>
                        <b-dropdown-item @click="duplicateProduct(row.item)">Duplicate Product</b-dropdown-item>
                    </b-dropdown>
                </template>
            </b-table>

            <!-- Custom Pagination Control -->
            <div class="tablecounter">

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
                        <li v-for="page in paginationRange" :key="page.key" class="page-item"
                            :class="{ active: page.number === currentPage, disabled: page.isEllipsis }"
                            @click="!page.isEllipsis && changePage(page.number)" style="cursor: pointer;">
                            <a class="page-link" href="#">
                                {{ page.isEllipsis ? '...' : page.number }}
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
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import imgbbs from '../assets/images/mis/productDemo.png';


const currentPage = ref(1);
const perPage = ref(10);
const selectAllChecked = ref(false);
const selectedItems = ref([]);
const allData = ref([]);

const fields = [
  { key: 'selectAll', label: '', sortable: false },
  { key: 'image', label: 'Image', sortable: false },
  { key: 'name', label: 'Name', sortable: true },
  { key: 'metalWeight', label: 'Metal Weight', sortable: true },
  { key: 'gemstoneWeight', label: 'Gemstone Weight', sortable: true },
  { key: 'description', label: 'Description', sortable: true },
  { key: 'price', label: 'Price', sortable: true },
  { key: 'status', label: 'Status', sortable: true },
  { key: 'actions', label: 'Actions', sortable: false }
];
const foods = [
  { text: 'Select One', value: null },
  'Carrots',
  'Beans',
  'Tomatoes',
  'Corn'
]
// Computed properties
const totalPages = computed(() =>
  Math.ceil(allData.value.length / perPage.value) || 1
);

const paginatedData = computed(() => {
  const start = (currentPage.value - 1) * perPage.value;
  const end = start + perPage.value;
  return allData.value.slice(start, end);
});

const paginationRange = computed(() => {
  const total = totalPages.value;
  const current = currentPage.value;
  const delta = 2;
  const range = [];
  let l;

  for (let i = 1; i <= total; i++) {
    if (
      i === 1 ||
      i === total ||
      (i >= current - delta && i <= current + delta)
    ) {
      range.push({ number: i, key: i, isEllipsis: false });
      l = i;
    } else if (l && i - l > 1) {
      range.push({ number: '...', key: `ellipsis-${i}`, isEllipsis: true });
      l = i;
    }
  }

  return range;
});

const startItem = computed(() =>
  allData.value.length === 0 ? 0 : (currentPage.value - 1) * perPage.value + 1
);

const endItem = computed(() =>
  Math.min(currentPage.value * perPage.value, allData.value.length)
);

// Methods
function changePage(page) {
  if (page < 1 || page > totalPages.value) return;
  currentPage.value = page;
  selectAllChecked.value = false;
  selectedItems.value = [];
}

function toggleSelectAll() {
  if (selectAllChecked.value) {
    selectedItems.value = paginatedData.value.map(item => item.id);
  } else {
    selectedItems.value = [];
  }
}

function changeSorting(column) {
  // Sorting logic can be added here
}

function getSortIcon(column) {
  return 'fa fa-sort';
}

function openEditSidebar(item) {
  console.log('Edit', item);
}

function openDeleteModal(id) {
  console.log('Delete', id);
}

function openInventoryHistory(item) {
  console.log('Inventory History', item);
}

function manageVariants(item) {
  console.log('Manage Variants', item);
}

function exportProduct(item) {
  console.log('Export', item);
}

function duplicateProduct(item) {
  console.log('Duplicate', item);
}

function fetchData() {
  const data = [];
  for (let i = 1; i <= 120; i++) {
    data.push({
      id: i,
      name: `Product ${i}`,
      metalWeight: `5.8 gm`,
      gemstoneWeight: `1.5 Carats`,
      price: `₹35,240`,
      description: `Description of product ${i}`,
      status: `Active`,
      image: imgbbs
    });
  }
  allData.value = data;
}

// Lifecycle hook
onMounted(() => {
  fetchData();
});
</script>
