<template>
  <div v-if="route.name !== 'login'" class="sidebar_screen" :class="[menuCollapseStatus ? 'sidebar_collapsed' : '']">
    <div class="sidebar_logo">
      <h4 v-if="menuCollapseStatus" class="navLogo" @click="menuCollapseStatus = !menuCollapseStatus">
        <img src="../assets/images/yjs.png" class="smallLogoBundle" alt="logoSidebar" />
      </h4>
      <h4 v-else class="navLogo" @click="menuCollapseStatus = !menuCollapseStatus">
        <img src="../assets/images/yjs_logo.png" class="largeLogoBundle" alt="logoSidebar" />
      </h4>
      <i class="fa-solid fa-down-left-and-up-right-to-center" v-if="!menuCollapseStatus"
        @click="menuCollapseStatus = !menuCollapseStatus"></i>
    </div>
    <div class="sidebar_listing p-2">
      <ul>
        <li v-for="menu in parentMenus" :key="menu?.id"  v-show="menu && menu.slug && $can('do', `access_${menu.slug}`)">
          <!-- Make sure menu & slug exist -->
          <!-- <template v-if="menu && menu.slug && $can('do', `access_${menu.slug}`)"> -->
            <router-link
              :to="{ name: menu.slug }"
              class="linkingFrame"
              :class="{ activeLink: isActiveMenu(menu) }"
            >
              <i :class="menu.icon" class="notactive"></i>
              <i :class="menu.icon" class="active"></i>
              <span>{{ menu.title }}</span>
            </router-link>
          <!-- </template> -->
        </li>
      </ul>



    </div>
  </div>
</template>

<script setup>
import { inject, watch, ref, onMounted, computed } from "vue";
import { useRoute, useRouter } from "vue-router";
import axiosEmployee from '@axiosEmployee';

const isActiveMenu = (menu) => {
  if (route.name === menu.slug) {
    return true
  }

  if (route.meta?.parent === menu.title) {
    return true
  }

  if (
    route.name &&
    menu.slug &&
    route.name.startsWith(menu.slug)
  ) {
    return true
  }

  return false
}
const route = useRoute();
const router = useRouter();

const menuCollapseStatus = inject("menuCollapseStatus");  // <-- injected
const menus = ref([]);
const activeMenu = ref(null);



async function loadMenus() {
  try {
    const response = await axiosEmployee.get("menus");
    menus.value = response.data.data; // ← match your API response
  } catch (error) {
    console.error("Menu load failed", error);
  }
}

const parentMenus = computed(() => {
  return menus.value.filter(m => m.parent_id === null);
});



if (window.innerWidth < 767) {
  menuCollapseStatus.value = true;
}


onMounted(() => {
  loadMenus();
});

watch(
  () => route.name,
  () => {
    if (window.innerWidth < 767) {
      menuCollapseStatus.value = true;
    }
  }
);
</script>
