<template>
  <div class="listing_screen global_table_liting">
    <div class="listing_tab_and_actions mb-3 w-100">
  <div class="p-3 w-100">
    <div class="d-flex justify-content-start w-100">
        <b-button
         @click="handleBackClick"
        class="GlobaltransBTN mb-3"
        >
        ← Back to Employee Listing
      </b-button>
    </div>
    <div class="viewFrame">
    
        <div v-if="loading" class="text-center p-5">
        <b-spinner variant="#404054" label="Loading..."></b-spinner>
      </div>

      <!-- Basic Information -->
      <div v-else>
      <div class="cardBox">
        <div class="cardHeading">
          <h2>Basic Information</h2>
        </div>
        <div class="detailsSection">
          <div class="innercolumn">
            <span class="boldHead"><p>Employee Code:</p></span>
            <span class="valueHead"><p>{{ employee.employee_code || 'N/A' }}</p></span>
          </div>
          <div class="innercolumn">
            <span class="boldHead"><p>Name:</p></span>
            <span class="valueHead"><p>{{ employee.first_name }} {{ employee.last_name }}</p></span>
          </div>
          <div class="innercolumn">
            <span class="boldHead"><p>Email:</p></span>
            <span class="valueHead"><p>{{ employee.email || 'N/A' }}</p></span>
          </div>
          <div class="innercolumn">
            <span class="boldHead"><p>Phone Number:</p></span>
            <span class="valueHead"><p>{{ employee.phone || 'N/A' }}</p></span>
          </div>
      

   
          <div class="innercolumn">
            <span class="boldHead"><p>Department:</p></span>
            <span class="valueHead"><p>{{ employee.department_name || 'N/A' }}</p></span>
          </div>
          <div class="innercolumn">
            <span class="boldHead"><p>Role:</p></span>
            <span class="valueHead"><p>{{ employee.role_name || 'N/A' }}</p></span>
          </div>
          <div class="innercolumn">
            <span class="boldHead"><p>Reporting Head:</p></span>
            <span class="valueHead"><p>{{ (employee.reporting_head_name) }}</p></span>
          </div>
  
          </div>
      </div>
       </div>

    </div>
  </div>
  </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axiosEmployee from '@axiosEmployee';

const route = useRoute();
const router = useRouter();
const employee = ref({});
const loading = ref(true);

const fetchEmployeeDetails = async () => {
  try {
    const response = await axiosEmployee.get(`/employees/${route.params.id}`);
    employee.value = response.data.data;
  } catch (error) {
    console.error('Error fetching employee details:', error);
  } finally {
    loading.value = false;
  }
};



const handleBackClick = () => {
  router.push({
    path: '/admin/settings',
    query: {
      tab: 'OrganizationStructure',
      master: 'Employee'
    }
  });
};



onMounted(() => {
  fetchEmployeeDetails();
});
</script>
