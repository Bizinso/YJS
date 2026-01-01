<template>
  <div class="listing_screen global_table_liting">
    <b-tabs
      v-model="activeTab"
      class="masterTabs"
      content-class="masterTabContent"
    >
      <b-tab title="Permission Based">
        <PermissionBasedAccess />
      </b-tab>
    </b-tabs>
  </div>
</template>

<script setup>
import { ref, onMounted } from "vue";
import { useRoute } from "vue-router";
import PermissionBasedAccess from "./PermissionBasedAccess.vue";

const activeTab = ref(0);
const route = useRoute();
const switchTab = (tab) => {
  if (tab === 'invoice') {
    activeTab.value = 1;
  }
};

onMounted(() => {
  if (route.query.tab === "invoice") {
    activeTab.value = 1;
  }
});
</script>
