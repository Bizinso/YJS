<template>
    <div class="general_profile_screen">
        <div class="general_profile_form w-100">
            <b-form @submit.prevent="saveGeneralInfo" class="formMaster disasshat py-0">
                <b-row class=" w-100">
                    <b-col md="12">
                        <b-form-group>
                            <label for="address" class="required">Address</label>
                            <b-form-textarea id="address" v-model="form.address" placeholder="Enter Address..." rows="3"
                                @input="onAddressInput" />

                            <small class="text-danger">{{ errors[0] }}</small>
                            <div class="text-danger" v-if="hasErrors('address')">
                                {{ getErrors("address") }}
                            </div>
                        </b-form-group>
                    </b-col>

                    <b-col md="6">
                        <b-form-group>
                            <label for="address" class="required">Country Name</label>
                            <v-select v-model="form.country" :options="countryOptions" label="label"
                                :reduce="val => val.value" placeholder="Select Country" :clearable="true"
                                @input="RemoveError('country')" />
                            <small class="text-danger">{{ errors[0] }}</small>
                            <div class="text-danger" v-if="hasErrors('country')">
                                {{ getErrors("country") }}
                            </div>
                        </b-form-group>
                    </b-col>

                    <b-col md="6">
                        <b-form-group>
                            <label class="required">State</label>
                            <v-select v-model="form.state" :options="stateOptions" label="label"
                                :reduce="val => val.value" placeholder="Select State" :clearable="true"
                                @input="RemoveError('state')" />
                            <small class="text-danger">{{ errors[0] }}</small>
                            <div class="text-danger" v-if="hasErrors('state')">
                                {{ getErrors("state") }}
                            </div>
                        </b-form-group>
                    </b-col>

                    <b-col md="6">
                        <b-form-group>
                            <label class="required">City</label>
                            <v-select v-model="form.city" :options="cityOptions" label="label"
                                :reduce="val => val.value" placeholder="Select City" :clearable="true"
                                @input="RemoveError('city')" />
                            <small class="text-danger">{{ errors[0] }}</small>
                            <div class="text-danger" v-if="hasErrors('city')">
                                {{ getErrors("city") }}
                            </div>
                        </b-form-group>
                    </b-col>

                    <b-col md="6">
                        <b-form-group>
                            <label class="required">Pincode</label>
                            <b-form-input v-model="form.pincode" placeholder="Enter Pincode"
                                @input="RemoveError('pincode')" />
                            <small class="text-danger">{{ errors[0] }}</small>
                            <div class="text-danger" v-if="hasErrors('pincode')">
                                {{ getErrors("pincode") }}
                            </div>
                        </b-form-group>
                    </b-col>
                </b-row>

            </b-form>
            <b-button @click="saveGeneralInfo" class="btn_primary me-2 mt-2">Save</b-button>
            <b-button type="button" class="btn_secondary_border mt-2" @click="handleCancel">
                Cancel
            </b-button>
        </div>
    </div>
</template>

<script setup>
import { ref, watch, onMounted } from 'vue'
import axiosEmployee from '@axiosEmployee';
import vSelect from 'vue-select'
import 'vue-select/dist/vue-select.css'
import { toast } from "vue3-toastify";
import "vue3-toastify/dist/index.css";
import { defineEmits } from 'vue'

const props = defineProps({
    userId: {
        type: String,
        required: true,
    },
})

const emit = defineEmits(['cancel'])

const handleCancel = () => {
    loadAddress()
    emit('cancel')
}

const form = ref({
    id: '',
    address: '',
    country: null,
    state: null,
    city: null,
    pincode: '',
})

const countryOptions = ref([])
const stateOptions = ref([])
const cityOptions = ref([])

const fetchStates = (countryId) => {
    if (!countryId) {
        stateOptions.value = []
        form.value.state = null
        cityOptions.value = []
        form.value.city = null
        return
    }

    axiosEmployee.get(`/states/${encodeBase64(countryId)}`).then(res => {
        stateOptions.value = res.data.data
    })
}

const fetchCities = (stateId) => {
    if (!stateId) {
        cityOptions.value = []
        form.value.city = null
        return
    }
    axiosEmployee.get(`/cities/${encodeBase64(stateId)}`).then(res => {
        cityOptions.value = res.data.data
    })
}

// Watch country change and fetch states
watch(() => form.value.country, (newVal, oldVal) => {
    if (newVal !== oldVal) {
        fetchStates(newVal)
        form.value.state = null
        form.value.city = null
        cityOptions.value = []
    }
})

// Watch state change and fetch cities
watch(() => form.value.state, (newVal, oldVal) => {
    if (newVal !== oldVal) {
        fetchCities(newVal)
        form.value.city = null
    }
})

const loadAddress = () => {
    axiosEmployee.get(`/address/${encodeBase64(props.userId)}`).then(res => {
        const data = res.data.data

        form.value.id = data.id
        form.value.address = data.address_line || ''
        form.value.country = data.country_id || null
        form.value.pincode = data.postal_code || ''

        if (data.country_id) {
            axiosEmployee.get(`/states/${encodeBase64(data.country_id)}`).then(stateRes => {
                stateOptions.value = stateRes.data.data

                form.value.state = data.state ? Number(data.state) : null

                if (data.state) {
                    axiosEmployee.get(`/cities/${encodeBase64(data.state)}`).then(cityRes => {
                        cityOptions.value = cityRes.data.data

                        form.value.city = data.city ? Number(data.city) : null
                    })
                }
            })
        }
    })
}



const RemoveError = (errorName) => {
    errors.value[errorName] = " ";
};
const hasErrors = (fieldName) => {
    return fieldName in errors.value;
};
const getErrors = (fieldName) => {
    return errors.value[fieldName][0];
};

const errors = ref({})

const saveGeneralInfo = () => {
    axiosEmployee.post(`/address`, {
        user_id: props.userId,
        address: form.value.address,
        country: form.value.country,
        state: form.value.state,
        city: form.value.city,
        pincode: form.value.pincode,
    })
        .then((response) => {
            toast("Address updated successfully!", {
                type: "success",
                autoClose: 1000,
            });
            errors.value = {} // clear errors on success
        })
        .catch((error) => {
            if (error.response && error.response.status === 422) {
                errors.value = error.response.data.errors
            }
        });
}

const onAddressInput = (value) => {
  const cleaned = value.replace(/[^a-zA-Z0-9\s,.\-\/#]/g, '');
  form.address = cleaned;
  RemoveError('address');
};



onMounted(() => {
    axiosEmployee.get('/countries').then(res => {
        countryOptions.value = res.data.data
    })

})
const encodeBase64 = (data) => {
    return btoa(data);
};
watch(() => props.userId, (newVal) => {
    if (newVal) {
        loadAddress()
    }
})

watch(() => form.value.country, () => {
    RemoveError('country');
});
watch(() => form.value.state, () => {
    RemoveError('state');
});
watch(() => form.value.city, () => {
    RemoveError('city');
});
</script>
