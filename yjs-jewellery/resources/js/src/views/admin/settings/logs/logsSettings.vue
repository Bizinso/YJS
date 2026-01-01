<template>
  <div class="verticalTabs">
    <b-tabs
      v-model="logTab"
      class="masterTabs"
      content-class="masterTabContent"
    >
      <b-tab
        v-if="$can('do', 'access_admin.logs.activity')"
        title="Activity Log"
        :active="logTab === 0"
      >
        <div class="contentBox">
          <ActivityLogs @triggerBackToMaster="goBackOrderInvoicing" />
        </div>
      </b-tab>
      <!-- <b-tab
        v-if="$can('do', 'access_login_logs')"
        title="Login Log"
        :active="logTab === 1"
      >
        <div class="contentBox">
          <LoginLog @triggerBackToMaster="goBackPricingDiscounts" />
        </div>
      </b-tab> -->
    </b-tabs>
  </div>
</template>

<script setup>
import { ref, watch, onMounted } from "vue";
import { useRoute, useRouter } from "vue-router";

const route = useRoute();
const router = useRouter();

import ActivityLogs from "./ActivityLogs.vue";

// import LoginLog from "../LoginLogs/LoginLog.vue";

const OrderInvoicing = ref(null);
const logNames = ["activity", "login"];
const logTab = ref(0);

const goBackOrderInvoicing = () => {
  OrderInvoicing.value = null;
};



onMounted(() => {
  const logQuery = route.query.log;
  if (logQuery && logNames.includes(logQuery)) {
    logTab.value = logNames.indexOf(logQuery);
  } else {
    logTab.value = 0;
  }
});

watch(logTab, (newIndex) => {
  const newQuery = { ...route.query, log: logNames[newIndex] || logNames[0] };
  router.replace({ query: newQuery });
});
</script>
