<template>
    <div>
        <div class="nodataFrame">
            <!-- <img src="../assets/img/nodata.png" class="nodataImage" alt="search" />
            <p class="nodataHeading">No Products Created Yet</p>
            <p class="nodataSubHeading">You haven’t added any products yet. Add your first product to get started.</p>
            <div class="buttonGrid">
                <b-button class="fillBTN" @click="openAddSidebar">Add</b-button>
            </div> -->
             <img src="../assets/img/nodata.png" class="nodataImage" alt="search" />
            <p class="nodataHeading">{{ heading }}</p>
            <p class="nodataSubHeading">{{ subheading }}</p>
            <div class="buttonGrid" v-if="showButton">
            <b-button class="fillBTN" @click="onButtonClick">{{ buttonText }}</b-button>
            </div>
        </div>
    </div>
</template>

<script>
import imgbbs from '../assets/images/mis/productDemo.png'
export default {
    name: 'noData',
  props: {
    heading: {
      type: String,
      default: 'No Data Available'
    },
    subheading: {
      type: String,
      default: 'There are no items to display at this time.'
    },
    altText: {
      type: String,
      default: 'No data'
    },
    showButton: {
      type: Boolean,
      default: false
    },
    buttonText: {
      type: String,
      default: 'Add New'
    }
  },
    data() {
        return {
            currentPage: 1,
            perPage: 10,
            selectAllChecked: false,
            selectedItems: [],
            fields: [
                { key: 'selectAll', label: '', sortable: false },
                { key: 'image', label: 'Image', sortable: false },
                { key: 'name', label: 'Name', sortable: true },
                { key: 'description', label: 'Description', sortable: true },
                { key: 'status', label: 'Status', sortable: true },
                { key: 'actions', label: 'Actions', sortable: false }
            ],
            allData: [], // will be loaded dynamically
        };
    },
    computed: {
        totalPages() {
            return Math.ceil(this.allData.length / this.perPage) || 1;
        },
        paginatedData() {
            const start = (this.currentPage - 1) * this.perPage;
            const end = start + this.perPage;
            return this.allData.slice(start, end);
        },
        paginationRange() {
            const total = this.totalPages;
            const current = this.currentPage;
            const delta = 2; // how many pages around current to show

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
        },
        startItem() {
            if (this.allData.length === 0) return 0;
            return (this.currentPage - 1) * this.perPage + 1;
        },
        endItem() {
            return Math.min(this.currentPage * this.perPage, this.allData.length);
        }
    },
    methods: {
   onButtonClick() {
      this.$emit('button-clicked');
    },
        changePage(page) {
            if (page < 1 || page > this.totalPages) return;
            this.currentPage = page;
            this.selectAllChecked = false;
            this.selectedItems = [];
        },
        toggleSelectAll() {
            if (this.selectAllChecked) {
                this.selectedItems = this.paginatedData.map(item => item.id);
            } else {
                this.selectedItems = [];
            }
        },
        changeSorting(column) {
            // Implement sorting logic here if needed
        },
        getSortIcon(column) {
            return 'fa fa-sort'; // Replace with actual sort icon logic
        },
        openEditSidebar(item) {
            console.log('Edit', item);
        },
        openDeleteModal(id) {
            console.log('Delete', id);
        },
        openInventoryHistory(item) {
            console.log('Inventory History', item);
        },
        manageVariants(item) {
            console.log('Manage Variants', item);
        },
        exportProduct(item) {
            console.log('Export', item);
        },
        duplicateProduct(item) {
            console.log('Duplicate', item);
        },
        // Simulate async data loading, replace with axios or your fetch method
        fetchData() {
            // Example: Replace with real API call
            const data = [];
            for (let i = 1; i <= 120; i++) {
                data.push({
                    id: i,
                    name: `Product ${i}`,
                    description: `Description of product ${i}`,
                    status: `Active`,
                    image: imgbbs
                });
            }
            this.allData = data;
        }
    },
    created() {
        this.fetchData();
    }
};
</script>
