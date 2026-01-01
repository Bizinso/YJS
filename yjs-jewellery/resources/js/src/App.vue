<template>
  <div v-if="layout === 'full'">
    <Header />
    <router-view />
    <Footer />
  </div>

  <div v-else-if="layout === 'adminFull'">
    <router-view />
  </div>

  <div v-else class="DefaultLayout">
    <Sidebar />

    <div
      class="right_layout_screen"
      :class="[menuCollapseStatus ? 'right_layout_collapsed' : '']"
    >
      <HeaderAdmin />
      <router-view />
      <FooterAdmin />
    </div>
  </div>
</template>


<script setup>
import { ref, provide, watch, defineAsyncComponent, onMounted } from "vue";
import { useRoute } from "vue-router";

import Header from "./views/layout/Header.vue";
import Footer from "./views/layout/Footer.vue";
import HeaderAdmin from "./views/layout/HeaderAdmin.vue";
import FooterAdmin from "./views/layout/FooterAdmin.vue";
// import Sidebar from "./views/layout/Sidebar.vue";
const Sidebar = defineAsyncComponent(() =>
  import("./views/layout/Sidebar.vue")
);

const layout = ref(null);
const route = useRoute();

const menuCollapseStatus = ref(false);
provide("menuCollapseStatus", menuCollapseStatus);

watch(
  () => route.meta.layout,
  (newLayout) => {
    layout.value = newLayout;
  },
  { immediate: true }
);

</script>
