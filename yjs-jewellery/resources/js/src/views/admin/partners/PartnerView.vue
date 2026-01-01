<template>
  <div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
  <h3>Partner Details</h3>

  <b-button variant="secondary" @click="goBack">
    ← Back
  </b-button>
</div>


    <b-card v-if="loading" class="text-center">
      Loading...
    </b-card>

    <b-alert variant="danger" v-if="error" show>
      {{ error }}
    </b-alert>

    <b-card v-if="partner">
      <!-- User Info -->
      <h5 class="mb-3">User Information</h5>
      <b-row>
        <b-col md="6"><strong>Name:</strong> {{ partner.user.first_name }} {{ partner.user.last_name }}</b-col>
        <b-col md="6"><strong>Email:</strong> {{ partner.user.email }}</b-col>
        <b-col md="6"><strong>Mobile:</strong> {{ partner.user.phone }}</b-col>
        <b-col md="6"><strong>Status:</strong> {{ partner.status }}</b-col>
      </b-row>

      <hr />

      <!-- Business Info -->
      <h5 class="mb-3">Business Information</h5>
      <b-row>
        <b-col md="6"><strong>Business Name:</strong> {{ partner.business_name }}</b-col>
        <b-col md="6"><strong>Business Type:</strong> {{ partner.business_type }}</b-col>
        <b-col md="6"><strong>GST No:</strong> {{ partner.gst_number || '-' }}</b-col>
        <b-col md="6"><strong>Phone:</strong> {{ partner.phone_number }}</b-col>
        <b-col md="12"><strong>Address:</strong> {{ partner.address }}</b-col>
        <b-col md="6"><strong>City:</strong> {{ partner.city }}</b-col>
        <b-col md="6"><strong>State:</strong> {{ partner.state }}</b-col>
      </b-row>

      <hr />

      <!-- Actions -->
      <!-- <div class="text-end">
        <b-button variant="danger" @click="showRejectModal = true">
          Reject Partner
        </b-button>
      </div> -->
    </b-card>

    <!-- Reject Modal -->
    <!-- <RejectPartnerModal
      v-model="showRejectModal"
      :partner="partner"
      @reject="handleReject"
    /> -->
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axiosEmployee from '@axiosEmployee'
import { useRoute } from 'vue-router'
import { useRouter } from 'vue-router'

const router = useRouter()

// import RejectPartnerModal from '@/components/RejectPartnerModal.vue'

const route = useRoute()
const partner = ref(null)
const loading = ref(false)
const error = ref('')
const showRejectModal = ref(false)

const fetchPartner = async () => {
  loading.value = true
  try {
    const res = await axiosEmployee.get(`/partners/${route.params.id}`)
    partner.value = res.data.data
  } catch (e) {
    error.value = 'Partner data load nahi ho paaya'
  } finally {
    loading.value = false
  }
}

const handleReject = async (reason) => {
  console.log('Rejected reason:', reason)

  // Yahan reject API call kar sakte ho
  // await axios.post(`/api/partners/${partner.value.id}/reject`, { reason })

  partner.value.status = 'rejected'
}
const goBack = () => {
  router.push('/admin/partners')
}


onMounted(fetchPartner)
</script>
