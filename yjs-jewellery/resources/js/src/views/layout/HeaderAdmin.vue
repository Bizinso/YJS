<template>
  <header
    class="app-header px-3 py-2 headerBase"
  >
    <div class="gap-2 justronHeader">
      <img
        src="../assets/images/header/menu.svg"
        class="notifications mobVisible"
        @click="toggleSidebar" 
      >
      {{ $route.meta.title || "Dashboard" }}
    </div>
    <!-- rest of your header template unchanged -->
    <div class="notificationBox">
      <img
        src="../assets/images/header/notification.svg"
        class="notifications"
      />
      <b-dropdown
        right
        :text="userName"
        variant="primary"
        class="notificationDD"
      >
        <b-dropdown-item @click="profile">Profile</b-dropdown-item>
        <b-dropdown-item v-if="isEmployeeLoggedIn" @click="logout">Logout</b-dropdown-item>
      </b-dropdown>

      <b-modal
        id="logoutModal"
        v-model="showLogoutModal"
        title="Confirm Logout"
        title-class="fontBox"
        hide-footer
      >
        <div class="d-block text-left">
          <p class="mb-3">Are you sure you want to logout?</p>
          <div class="buttonGrid">
            <b-button class="GlobalfillBTN" @click="confirmLogout">Yes</b-button>
            <b-button class="GlobaltransBTN" @click="cancelLogout">No</b-button>
          </div>
        </div>
      </b-modal>
    </div>
  </header>
</template>

<script setup>
import { isEmployeeLoggedIn, employeeData, setEmployeeLogout } from '@/stores/authEmployee'
import { ref, onMounted, inject ,computed } from "vue";
import { useRouter } from "vue-router";
import axiosEmployee from '@axiosEmployee';
import {  updateAbilities } from "../../ability";

const userName = computed(() => 
  `${employeeData.value?.first_name ?? ''} ${employeeData.value?.last_name ?? ''}`.trim() || 'User'
);
const showLogoutModal = ref(false);
const router = useRouter();

const menuCollapseStatus = inject("menuCollapseStatus");  // <-- inject here

function toggleSidebar() {
  menuCollapseStatus.value = !menuCollapseStatus.value;   // <-- toggle on click
}


function profile() {
  router.push({ name: 'admin.profile' })
}

function logout() {
  showLogoutModal.value = true;
}

function cancelLogout() {
  showLogoutModal.value = false;
}

const confirmLogout = async () => {
  try {
    await axiosEmployee.post('/logout')
  } catch (error) {
    console.error('Logout failed:', error)
  } finally {
    localStorage.removeItem('employee_token')
    localStorage.removeItem('employee_data')
    updateAbilities([]);
    delete axiosEmployee.defaults.headers.common['Authorization']
    setEmployeeLogout()
    router.push({ name: 'adminLogin' })
  }
}
</script>
