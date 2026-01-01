<template>
  <div class="listing_screen global_table_liting">
    <b-tabs v-model="activeTabmaster" class="masterTabs">

      <b-tab title="Masters" :active="activeTabmaster === 0">


        <div class="verticalTabs">
          <b-tabs class="masterTabs" content-class="masterTabContent" v-model="tabActiveIndex.key">
            <b-tab v-if="$can('do', 'access_admin.org')" title="Organization Structure"
              @click="() => (activeTab = 'OrganizationStructure')">
              <div v-if="!OrganizationStructure" class="organizationBoxes">
                <div v-for="(box, index) in OrganizationStructureList" :key="index" class="highlightedBox"
                  @click="onMasterClick(box)">
                  <h2>{{ box }}</h2>
                  <p class="countinner">{{ orgCounts[box] ?? 0 }}</p>
                </div>
              </div>

              <div v-else class="contentBox">
                <div v-if="
                  $can('do', 'access_admin.org.branches') && OrganizationStructure === 'Branch'
                ">
                  <BranchSlot @triggerBackToMaster="goBackOrganizationStructure" />
                </div>
                <div v-if="
                  $can('do', 'access_admin.org.departments') && OrganizationStructure === 'Department'
                ">
                  <DepartmentSlot @triggerBackToMaster="goBackOrganizationStructure" />
                </div>
                <div v-else-if="
                  $can('do', 'access_admin.org.roles') && OrganizationStructure === 'Role'
                ">
                  <RoleSlot @triggerBackToMaster="goBackOrganizationStructure" />
                </div>
                <div v-else-if="
                  $can('do', 'access_admin.org.employees') && OrganizationStructure === 'Employee'
                ">
                  <EmployeeSlot @triggerBackToMaster="goBackOrganizationStructure" />
                </div>
              </div>
            </b-tab>
            <b-tab v-if="$can('do', 'access_admin.masters')" title="Product Master"
              @click="() => (activeTab = 'ProductManagement')">
              <div v-if="!ProductManagement" class="organizationBoxes">
                <div v-for="(box, index) in ProductManagementList" :key="index" v-show="showProductManagementBox(box)"
                  class="highlightedBox" @click="onMasterClick(box)">
                  <h2>{{ box }}</h2>
                  <p class="countinner">{{ productCounts[box] ?? 0 }}</p>
                </div>
              </div>
              <div v-else class="contentBox">
                <div v-if="
                  $can('do', 'access_admin.masters.categories') && ProductManagement === 'Category'
                ">
                  <Category @triggerBackToMaster="goBackProductManagement" />
                </div>
                <div v-else-if="
                  $can('do', 'access_admin.masters.subcategories') &&
                  ProductManagement === 'Sub Category'
                ">
                  <SubCategory @triggerBackToMaster="goBackProductManagement" />
                </div>
                <div v-else-if="
                $can('do', 'access_admin.masters.tags') && ProductManagement === 'Tag/Labels'
              ">
                <TagLabels @triggerBackToMaster="goBackProductManagement" />
              </div>
             <div v-else-if="
                $can('do', 'access_admin.masters.materialtypes') &&
                ProductManagement === 'Material/Metal Type'
              ">
                <MaterialMetalType @triggerBackToMaster="goBackProductManagement" />
              </div>
              <div v-else-if="
                $can('do', 'access_admin.masters.attributes') &&
                ProductManagement === 'Attributes'
              ">
                <Attributes @triggerBackToMaster="goBackProductManagement" />
              </div>
              <div v-else-if="
                $can('do', 'access_admin.masters.charges') &&
                ProductManagement === 'Additional Charges '
              ">
                <AdditionalCharges @triggerBackToMaster="goBackProductManagement" />
              </div>
              <div v-else-if="$can('do', 'access_admin.masters.tax') && ProductManagement === 'Tax'">
                <Tax @triggerBackToMaster="goBackProductManagement" />
              </div>
             
              <div v-else-if="
                $can('do', 'access_admin.masters.producttype') &&
                ProductManagement === 'Product Type'
              ">
                <ProductType @triggerBackToMaster="goBackProductManagement" />
              </div> 
              </div>
            </b-tab>
          </b-tabs>
        </div>
      </b-tab>
      <b-tab title="Logs" :active="activeTabmaster === 1" lazy>
        <!-- Only render LogsSettings when this tab is active -->
        <LogsSettings />
      </b-tab>
    </b-tabs>
  </div>
</template>
<script setup>
import { ref, watch, onMounted, getCurrentInstance, computed } from "vue";
import { useRoute, useRouter } from "vue-router";
import axiosEmployee from '@axiosEmployee';

import BranchSlot from "./org-structure/Branch.vue";
import DepartmentSlot from "./org-structure/Department.vue";
import RoleSlot from "./org-structure/Role.vue";
import EmployeeSlot from "./org-structure/Employee.vue";

import Category from "./masters/Category.vue";
import SubCategory from "./masters/SubCategory.vue";
import TagLabels from "./masters/Tags.vue";
import MaterialMetalType from "./masters/MaterialType.vue";
import Attributes from "./masters/Attributes.vue";
import AdditionalCharges from "./masters/AdditionalCharges.vue";
import Tax from "./masters/Tax.vue";
import ProductType from "./masters/ProductType.vue";

import LogsSettings from "./logs/logsSettings.vue";

const { appContext } = getCurrentInstance();
const $can = appContext.config.globalProperties.$can;

const emit = defineEmits(["updateMaster"]);
const tabTitles = ["OrganizationStructure", "logs"];
const activeMaster = ref(null);

const props = defineProps({
  initialMaster: String,
});

const route = useRoute();
const router = useRouter();

const activeTabmaster = ref(0);
const activeTab = ref(route.query.tab || "OrganizationStructure");

const OrganizationStructureList = ["Branch", "Department", "Role", "Employee"];
const ProductManagementList = ["Category", "Sub Category", "Tag/Labels", "Material/Metal Type", "Attributes", "Gemstone Rate", "Additional Charges ", "Tax", "Product Type"];

const OrganizationStructure = ref(null);
const ProductManagement = ref(null);
const activeTabsData = ref([
  { key: 0, name: 'OrganizationStructure', permission: 'access_admin.org' },
  { key: 1, name: 'ProductManagement', permission: 'access_admin.masters' },
]);


const goBackOrganizationStructure = () => {
  OrganizationStructure.value = null;
};

function onMasterClick(masterName) {

  switch (activeTab.value) {
    case "OrganizationStructure":
      OrganizationStructure.value = masterName;
      break;
    case "ProductManagement":
      ProductManagement.value = masterName;
      break;
  }


  router.push({
    query: {
      ...route.query,
      tab: activeTab.value,
      master: masterName,
    },
  });

  emit("updateMaster", masterName);
}

function setMasterForTab(tab, master) {
  // Always set master, even if it's null
  switch (tab) {
    case "OrganizationStructure":
      OrganizationStructure.value = master || null;
      break;
    case "ProductManagement":
      ProductManagement.value = master || null;
      break;
  }
}

// Init master before mount
if (route.query.master != null) {
  setMasterForTab(activeTab.value, route.query.master);
}

let setActivateTab = (tabname) => {
  activeTabsData.value.forEach(element => {
    if (element.name == tabname) {
      tabActiveIndex.value = {
        key: element.key,
        name: tabname
      }
    }
  });
}


const visibleTabs = computed(() => {
  return activeTabsData.value
    .filter(tab => $can('do', tab.permission))
    .map((tab, index) => ({
      ...tab,
      key: index // new index in visible order
    }));
});


function setFirstVisibleTab() {
  if (visibleTabs.value.length > 0) {
    tabActiveIndex.value = { ...visibleTabs.value[0] };
  }
}

let tabActiveIndex = ref({
  key: 0,
  name: 'OrganizationStructure'
})


// Dynamic counts for boxes
const orgCounts = ref({});
const productCounts = ref({});

async function fetchOrgCounts() {
  try {
    const res = await axiosEmployee.get("/master-counts");
    const counts = res.data.data.organization_structure;
    orgCounts.value["Branch"] = counts.Branch ?? 0;
    orgCounts.value["Department"] = counts.Department ?? 0;
    orgCounts.value["Role"] = counts.Role ?? 0;
    orgCounts.value["Employee"] = counts.Employee ?? 0;
  } catch (e) {
    // fallback to 0 on error
  }
}

async function fetchProductCounts() {
  try {
    const res = await axiosEmployee.get("/master-counts");
    const counts = res.data.data.product_management;

    if (showProductManagementBox("Category"))
      productCounts.value["Category"] = counts.Category ?? 0;
    if (showProductManagementBox("Sub Category"))
      productCounts.value["Sub Category"] = counts["Sub Category"] ?? 0;
    if (showProductManagementBox("Tag/Labels"))
      productCounts.value["Tag/Labels"] = counts["Tag/Labels"] ?? 0;
    if (showProductManagementBox("Material/Metal Type"))
      productCounts.value["Material/Metal Type"] = counts["Material/Metal Type"] ?? 0;
    if (showProductManagementBox("Attributes"))
      productCounts.value["Attributes"] = counts.Attributes ?? 0;
    if (showProductManagementBox("Gemstone Rate"))
      productCounts.value["Gemstone Rate"] = counts["Gemstone Rate"] ?? 0;
    if (showProductManagementBox("Additional Charges "))
      productCounts.value["Additional Charges "] = counts["Additional Charges "] ?? 0;
    if (showProductManagementBox("Tax"))
      productCounts.value["Tax"] = counts.Tax ?? 0;
    if (showProductManagementBox("Product Type"))
      productCounts.value["Product Type"] = counts["Product Type"] ?? 0;
  } catch (e) {
    // fallback to 0 on error
  }
}
const showProductManagementBox = (box) => {
  switch (box) {
    case "Category":
      return $can("do", "access_admin.masters.categories");
    case "Sub Category":
      return $can("do", "access_admin.masters.subcategories");
    case "Tag/Labels":
      return $can("do", "access_admin.masters.tags");
    case "Material/Metal Type":
      return $can("do", "access_admin.masters.materialtypes");
    case "Attributes":
      return $can("do", "access_admin.masters.attributes");
    case "Additional Charges ":
      return $can("do", "access_admin.masters.charges");
    case "Tax":
      return $can("do", "access_admin.masters.tax");
    case "Product Type":
       return $can("do", "access_admin.masters.producttype");
    default:
      return false;
  }
};

function fetchCountsForActiveTab(tab) {
  if (tab === 'OrganizationStructure') fetchOrgCounts();
  if (tab === 'ProductManagement') fetchProductCounts();
}

onMounted(() => {
  // ✅ Sync top-level tab from URL
  if (route.query.tab === 'logs') {
    activeTabmaster.value = 1;
  } else {
    activeTabmaster.value = 0;
  }

  activeTabsData.value = visibleTabs.value;

  if (route.query.tab) {
    activeTab.value = route.query.tab;
    setActivateTab(route.query.tab);

    if (route.query.master) {
      setMasterForTab(route.query.tab, route.query.master);
    }
  } else {
    setFirstVisibleTab();
  }

  fetchCountsForActiveTab(activeTab.value);

  if (!route.query.tab) {
    router.replace({ name: "admin.settings", query: { tab: "OrganizationStructure" } });
  }
});

watch(activeTabmaster, (newIndex) => {
  const tabName = tabTitles[newIndex] || "OrganizationStructure";
  const newQuery = { ...route.query, tab: tabName };

  console.log("Tab changed:", tabName); // ← NOW YOU WILL SEE THIS

  if (tabName === "OrganizationStructure") {
    delete newQuery.log;
    if (activeMaster.value) {
      newQuery.master = activeMaster.value;
    }
  } else if (tabName === "logs") {
    delete newQuery.master;
  }

  router.replace({ query: newQuery });

  if (tabName !== "OrganizationStructure") activeMaster.value = null;
});

watch(
  () => route.query,
  (query) => {
    const tab = query.tab || "OrganizationStructure";
    activeTab.value = tab;
    setMasterForTab(tab, query.master || null);
    fetchCountsForActiveTab(tab);
  }
);

watch(activeTab, (newTab) => {
  setActivateTab(newTab);
  const newQuery = { ...route.query, tab: newTab };
  delete newQuery.master;
  delete newQuery.action;
  delete newQuery.id;

  router.replace({ query: newQuery });
  //router.push({ name: "setting", query: newQuery });

  OrganizationStructure.value = null;
  ProductManagement.value = null;


  // lazy fetch counts for active tab only
  fetchCountsForActiveTab(newTab);
});


</script>
