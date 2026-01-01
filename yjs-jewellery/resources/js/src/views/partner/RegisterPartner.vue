<script setup>
import { reactive, ref, computed ,watch ,onMounted} from 'vue'
import { useRouter } from 'vue-router'
import axiosCustomer from "@axiosCustomer";
import axiosPartner from "@axiosPartner";


const router = useRouter()

const form = reactive({
  business_name: '',
  contact_person: '',
  mobile: '',
  email: '',
  gst_no: '',
  business_type: null,
  other_business_type: '',
  address: '',
  city: '',
  state: '',
  country_id: 101 // Fixed for India
})

const validationErrors = ref({})
const isLoading = ref(false)
const successMessage = ref('')
const stateOptions = ref([])
const cityOptions = ref([])
const loadingStates = ref(false)
const loadingCities = ref(false)

const BusinessTypeOptions = [
  { label: 'Proprietorship', value: 'proprietorship' },
  { label: 'Partnership', value: 'partnership' },
  { label: 'Private Limited', value: 'private_limited' },
  { label: 'LLP', value: 'llp' },
  { label: 'Other', value: 'other' },
]

// Fetch states on component mount
onMounted(() => {
  fetchStates()
})

const encodeBase64 = (data) => {
  if (data === undefined || data === null) {
    return "";
  }
  return btoa(data.toString());
};

// Fetch states based on country_id (101 for India)
const fetchStates = async () => {
  loadingStates.value = true
  try {
    const response = await axiosPartner.get(`/states/${encodeBase64(form.country_id)}`)
    stateOptions.value = response.data.data.map(state => ({
      label: state.label,
      value: state.value
    }))
  } catch (error) {
    console.error('Error fetching states:', error)
  } finally {
    loadingStates.value = false
  }
}

// Fetch cities when state changes
watch(
  () => form.state,
  async (newStateId) => {
    if (newStateId) {
      await fetchCities(newStateId)
    } else {
      cityOptions.value = []
      form.city = ''
    }
    removeError('state')
  }
)

const fetchCities = async (stateId) => {
  loadingCities.value = true
  try {
    const response = await axiosPartner.get(`/cities/${encodeBase64(stateId)}`)
    cityOptions.value = response.data.data.map(city => ({
      label: city.label,
      value: city.value
    }))
  } catch (error) {
    console.error('Error fetching cities:', error)
  } finally {
    loadingCities.value = false
  }
}

const hasErrors = (field) => {
  return (
    validationErrors.value &&
    validationErrors.value[field] &&
    validationErrors.value[field].length > 0
  )
}

const getErrors = (field) => {
  return validationErrors.value[field]?.[0] || ''
}

const removeError = (field) => {
  if (validationErrors.value[field]) {
    delete validationErrors.value[field]
  }
}

const submitForm = async () => {
  validationErrors.value = {}
  successMessage.value = ''
  isLoading.value = true

  try {
    // Find state and city names from their IDs
    const stateName = stateOptions.value.find(s => s.value === form.state)?.label || form.state
    const cityName = cityOptions.value.find(c => c.value === form.city)?.label || form.city

    const formData = {
      ...form,
      business_type: form.business_type === 'other' ? form.other_business_type : form.business_type,
      // state: stateName,
      // city: cityName
    }

    const response = await axiosPartner.post('/partner-register', formData)
    
    successMessage.value = 'Partner registered successfully!'
    
    // Reset form
    Object.keys(form).forEach(key => {
      if (key !== 'country_id') {
        form[key] = ''
      }
    })
    form.business_type = null
    stateOptions.value = []
    cityOptions.value = []
    
    // Refetch states after reset
    await fetchStates()
    
    // Optional: Redirect after success
    setTimeout(() => {
      router.push('/partners')
    }, 3000)
    
  } catch (error) {
    if (error.response?.status === 422) {
      validationErrors.value = error.response.data.errors
    } else {
      console.error('Registration failed:', error)
      validationErrors.value = {
        general: ['An error occurred. Please try again.']
      }
    }
  } finally {
    isLoading.value = false
  }
}

// Watch for business_type changes
watch(
  () => form.business_type,
  (val) => {
    if (val !== 'other') {
      form.other_business_type = ''
    }
    removeError('business_type')
  }
)
</script>

<template>
  <div class="yjs_product">
    <div class="global_breadcrumbs">
      <div class="container">
        <ul class="breadcrumb">
          <li><a href="/">Home</a></li>
          <li>Register Partner</li>
        </ul>
      </div>
    </div>
    
    <div class="boxHeader">
      <div class="container cardBox">
        <div class="infoBase">
          <h2 class="globalHeading">Register Partner</h2>
          <p class="basicDetails">Sign up your business to collaborate with us and start offering your services through our platform.</p>
        </div>
      </div>
    </div>

    <div class="registerForm container">
      <div v-if="successMessage" class="alert alert-success">
        {{ successMessage }}
      </div>
      
      <b-form @submit.prevent="submitForm">
        <!-- Business Name -->
        <b-form-group
          class="formElements"
          id="input-group-business-name"
          label="Business Name"
          label-for="business-name"
          label-class="required"
        >
          <b-form-input
            id="business-name"
            v-model="form.business_name"
            placeholder="Enter Business Name"
            autocomplete="off"
            :class="{ 'is-invalid': hasErrors('business_name') }"
            @input="removeError('business_name')"
          />
          <div class="text-danger" v-if="hasErrors('business_name')">
            {{ getErrors('business_name') }}
          </div>
        </b-form-group>

        <!-- Contact Person -->
        <b-form-group
          class="formElements"
          id="input-group-contact-person"
          label="Contact Person"
          label-for="contact-person"
          label-class="required"
        >
          <b-form-input
            id="contact-person"
            v-model="form.contact_person"
            placeholder="Enter Contact Person Name"
            autocomplete="off"
            :class="{ 'is-invalid': hasErrors('contact_person') }"
            @input="removeError('contact_person')"
          />
          <div class="text-danger" v-if="hasErrors('contact_person')">
            {{ getErrors('contact_person') }}
          </div>
        </b-form-group>

        <!-- Mobile -->
        <b-form-group
          class="formElements"
          id="input-group-mobile"
          label="Mobile"
          label-for="mobile"
          label-class="required"
        >
          <b-form-input
            id="mobile"
            v-model="form.mobile"
            placeholder="Enter Mobile Number"
            type="tel"
            maxlength="10"
            autocomplete="off"
            :class="{ 'is-invalid': hasErrors('mobile') }"
            @input="removeError('mobile')"
          />
          <div class="text-danger" v-if="hasErrors('mobile')">
            {{ getErrors('mobile') }}
          </div>
        </b-form-group>

        <!-- Email -->
        <b-form-group
          class="formElements"
          id="input-group-email"
          label="Email"
          label-for="email"
          label-class="required"
        >
          <b-form-input
            id="email"
            v-model="form.email"
            placeholder="Enter Email Address"
            type="email"
            autocomplete="off"
            :class="{ 'is-invalid': hasErrors('email') }"
            @input="removeError('email')"
          />
          <div class="text-danger" v-if="hasErrors('email')">
            {{ getErrors('email') }}
          </div>
        </b-form-group>

        <!-- GST No -->
        <b-form-group
          class="formElements"
          id="input-group-gst"
          label="GST No."
          label-for="gst"
        >
          <b-form-input
            id="gst"
            v-model="form.gst_no"
            placeholder="Enter GST Number"
            autocomplete="off"
            :class="{ 'is-invalid': hasErrors('gst_no') }"
            @input="removeError('gst_no')"
          />
          <div class="text-danger" v-if="hasErrors('gst_no')">
            {{ getErrors('gst_no') }}
          </div>
        </b-form-group>

        <!-- Address -->
        <b-form-group 
          class="formElements" 
          label="Address" 
          label-for="address"
          label-class="required"
        >
          <b-form-textarea
            v-model="form.address"
            placeholder="Enter Business Address..."
            rows="2"
            id="address"
            max-rows="6"
            :class="{ 'is-invalid': hasErrors('address') }"
            @input="removeError('address')"
          />
          <div class="text-danger" v-if="hasErrors('address')">
            {{ getErrors('address') }}
          </div>
        </b-form-group>

        <!-- State -->
        <!-- <b-form-group
          class="formElements multiDrop"
          id="input-group-state"
          label-class="required"
        >
          <template #label>
            <p class="required-label">State</p>
          </template>
          <v-select
            v-model="form.state"
            :options="stateOptions"
            :reduce="val => val.value"
            label="label"
            :clearable="true"
            placeholder="Select State"
            :loading="loadingStates"
            :class="{ 'is-invalid v-select-invalid': hasErrors('state') }"
            @input="removeError('state')"
          />
          <div class="text-danger" v-if="hasErrors('state')">
            {{ getErrors('state') }}
          </div>
        </b-form-group> -->

        <!-- City -->
        <!-- <b-form-group
          class="formElements multiDrop"
          id="input-group-city"
          label-class="required"
        >
          <template #label>
            <p class="required-label">City</p>
          </template>
          <v-select
            v-model="form.city"
            :options="cityOptions"
            :reduce="val => val.value"
            label="label"
            :clearable="true"
            placeholder="Select City"
            :disabled="!form.state"
            :loading="loadingCities"
            :class="{ 'is-invalid v-select-invalid': hasErrors('city') }"
            @input="removeError('city')"
          />
          <div class="text-danger" v-if="hasErrors('city')">
            {{ getErrors('city') }}
          </div>
        </b-form-group> -->

        <!-- Business Type -->
        <b-form-group
          class="formElements multiDrop"
          id="input-group-business-type"
          label-class="required"
        >
          <template #label>
            <p class="required-label">Business Type</p>
          </template>
          <v-select
            v-model="form.business_type"
            :options="BusinessTypeOptions"
            :reduce="val => val.value"
            label="label"
            :clearable="true"
            placeholder="Select Business Type"
            :class="{ 'is-invalid v-select-invalid': hasErrors('business_type') }"
            @input="removeError('business_type')"
          />
          <div class="text-danger" v-if="hasErrors('business_type')">
            {{ getErrors('business_type') }}
          </div>
        </b-form-group>

        <!-- Other Business Type (if selected 'Other') -->
        <b-form-group
          v-if="form.business_type === 'other'"
          class="formElements"
          label="Specify Business Type"
          label-for="other-business-type"
          label-class="required"
        >
          <b-form-input
            id="other-business-type"
            v-model="form.other_business_type"
            placeholder="Enter Business Type"
            autocomplete="off"
            :class="{ 'is-invalid': hasErrors('other_business_type') }"
            @input="removeError('other_business_type')"
          />
          <div class="text-danger" v-if="hasErrors('other_business_type')">
            {{ getErrors('other_business_type') }}
          </div>
        </b-form-group>

        <!-- Submit Button -->
        <div class="w-100 mb-5">
          <b-button 
            type="submit" 
            variant="primary" 
            :disabled="isLoading"
          >
            <span v-if="isLoading">
              <b-spinner small></b-spinner> Processing...
            </span>
            <span v-else>Submit</span>
          </b-button>
        </div>
      </b-form>
    </div>
  </div>
</template>

<style scoped>
.formElements {
  margin-bottom: 1.5rem;
}

.required-label:after {
  content: " *";
  /* color: #dc3545; */
}



.is-invalid {
  border-color: #dc3545;
}

/* Special styling for v-select invalid state */
.v-select-invalid :deep(.vs__dropdown-toggle) {
  border-color: #dc3545 !important;
}

.alert {
  margin-bottom: 20px;
  padding: 15px;
  border-radius: 5px;
}

.alert-success {
  background-color: #d4edda;
  border-color: #c3e6cb;
  color: #155724;
}

:deep(.vs__dropdown-toggle) {
  padding: 0.375rem 0.75rem;
  font-size: 1rem;
  line-height: 1.5;
  border: 1px solid #ced4da;
  border-radius: 0.25rem;
  min-height: calc(1.5em + 0.75rem + 2px);
}

:deep(.vs__search) {
  margin: 0;
  padding: 0;
}

:deep(.vs__selected) {
  margin: 0;
  padding: 0;
}
</style>