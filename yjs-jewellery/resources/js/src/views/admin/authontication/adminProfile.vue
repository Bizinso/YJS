<template>
  <div class="listing_screen global_table_liting">    
    <b-tabs v-model="activeTab" class="masterTabs" content-class="masterTabContent">
      <b-tab title="General">
        <GeneralTab :userId="userId" />
      </b-tab>
      <b-tab title="Address">
        <AddressTab :userId="userId"  @cancel="activeTab = 0" />
      </b-tab>
      <b-tab title="Change Password">
        <ChangePasswordTab :userId="userId" />
      </b-tab>
    </b-tabs>
  </div>
</template>

<script setup>  
import { ref, onMounted } from 'vue'
import GeneralTab from './GeneralTab.vue'
import AddressTab from './AddressTab.vue'
import ChangePasswordTab from './ChangePasswordTab.vue'

const activeTab = ref(0)
const userId = ref(null)

onMounted(() => {

  const userDataRaw = JSON.parse(localStorage.getItem(`employee_data`) || "{}");
  if (userDataRaw) {

    try {
      const userData = userDataRaw;
      userId.value = userData.id || null
    } catch (e) {
     
    }
  }
})

</script>
