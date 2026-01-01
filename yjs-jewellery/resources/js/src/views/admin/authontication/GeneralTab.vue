<template>
    <div class="general_profile_screen">
        <div class="general_profile_img">
            <div class="user_profile_uploader mb-3">

                <b-img :src="imagePreview || profileImage" class="profile-avatar" alt="User Avatar" />
            </div>

            <div class="text-center">
                <input type="file" ref="fileInput" @change="onFileChange" id="profile_image" accept=".jpg, .png, .gif"
                    style="display: none" />
                <b-button class="btn_secondary_border" @click="triggerFileUpload">Upload Image <i
                        class="fa-solid fa-arrow-up-from-bracket ms-1"></i></b-button>
            </div>

            <div class="theProfileInfo">
                <p class="userName">{{ formattedTitle(options.first_name) }} {{ formattedTitle(options.last_name) }}</p>
                <p class="userCode"><span>Employee Code: </span>{{ options.employee_code }}</p>
            </div>

            <div class="text-danger" v-if="hasErrors('profile_image')">
                {{ getErrors('profile_image') }}
            </div>
            <div class="profile_short_info mt-4">
                <p><span>Email: </span>{{ options.email }}</p>
                <p><span>Phone Number: </span>{{ options.phone }}</p>
                <p><span>Role: </span>{{ options.role }}</p>

            </div>
        </div>

        <div class="general_profile_form">
            <b-form @submit.prevent="saveGeneralInfo">
                <b-row class="formMaster py-0">

                    <b-col md="6">
                        <b-form-group>
                            <label class="form-label required">First Name</label>
                            <b-form-input id="name" v-model="options.first_name" @input="RemoveError('first_name')"
                                :state="errors.length > 0 ? false : null" placeholder="Enter First Name"
                                autocomplete="off" />

                            <div class="text-danger" v-if="hasErrors('first_name')">
                                {{ getErrors('first_name') }}
                            </div>
                        </b-form-group>
                    </b-col>
                    <b-col md="6">
                        <b-form-group>
                            <label class="form-label required">Last Name</label>
                            <b-form-input id="name" v-model="options.last_name" @input="RemoveError('last_name')"
                                :state="errors.length > 0 ? false : null" placeholder="Enter Last Name"
                                autocomplete="off" />
                            <div class="text-danger" v-if="hasErrors('last_name')">
                                {{ getErrors('last_name') }}
                            </div>
                        </b-form-group>
                    </b-col>
                    <b-col md="6">
                        <b-form-group>
                            <label class="form-label required">Phone Number</label>
                            <b-form-input id="phone" v-model="options.phone" @input="RemoveError('phone')"
                                :state="errors.length > 0 ? false : null" placeholder="Enter phone Number"
                                autocomplete="off" />
                            <div class="text-danger" v-if="hasErrors('phone')">
                                {{ getErrors('phone') }}
                            </div>
                        </b-form-group>
                    </b-col>

                    <b-col md="12">
                        <label for="">Email</label>
                        <b-form-group>
                            <b-form-input v-model="options.email" placeholder="Enter Email" readonly />
                        </b-form-group>
                    </b-col>

                </b-row>
                <b-button type="submit" class="btn_primary me-2">Save</b-button>
                <b-button class="btn_secondary_border" to="/admin/dashboard">Cancel</b-button>
            </b-form>
        </div>
    </div>
</template>

<script setup>
import {  employeeData } from '@/stores/authEmployee'
import { ref, onMounted, computed } from 'vue'
import axiosEmployee from '@axiosEmployee';
import { toast } from "vue3-toastify";
import "vue3-toastify/dist/index.css";

defineProps({
    userId: {
        type: String,
        required: true,
    },
});

const options = ref({
    id: "",
    email: "",
    phone: "",
    profile_image: "",
    first_name: "",
    last_name: ""
});
const selectedFile = ref(null);
const imagePreview = ref(null);
const errors = ref({});

const fileInput = ref(null);

const formattedTitle = (text) => {
    if (text) {
        return text.charAt(0).toUpperCase() + text.slice(1); // Capitalize the first letter
    }
    return text;
};

onMounted(() => {
    getUserProfile();
})

const profileImage = ref("/default-avatar.png");

const getUserProfile = () => {
    axiosEmployee
        .get(`/profile`)
        .then((response) => {
            options.value = response.data.data;
            const profileData = response.data?.data;
            profileImage.value = profileData?.profile_image
                ? `/storage/${profileData.profile_image}`
                : "/default-avatar.png";

        })
        .catch((error) => {
            profileImage.value = "/default-avatar.png";

        });
};

const onFileChange = (event) => {
    selectedFile.value = event.target.files[0];
    if (selectedFile.value) {
        imagePreview.value = URL.createObjectURL(selectedFile.value);
    }
    RemoveError('profile_image');
};

const triggerFileUpload = () => {
    fileInput.value.click();
};

const RemoveError = (field) => {
    if (errors.value[field]) {
        delete errors.value[field];
    }
};

const hasErrors = (fieldName) => {
    return errors.value && errors.value[fieldName];
};

const getErrors = (fieldName) => {
    return errors.value[fieldName] ? errors.value[fieldName][0] : '';
};

const saveGeneralInfo = () => {
    const formData = new FormData();
    formData.append("id", options.value.id);
    formData.append("first_name", options.value.first_name);
    formData.append("last_name", options.value.last_name);
    formData.append("email", options.value.email);
    formData.append("phone", options.value.phone);

    if (selectedFile.value) {
        formData.append("profile_image", selectedFile.value);
    }

    axiosEmployee
        .post(`/profile/${encodeBase64(options.value.id)}`, formData, {
            headers: {
                "Content-Type": "multipart/form-data",
            },
        })
        .then((response) => {
            options.value = { ...options.value, ...response.data.data };
            selectedFile.value = null;
            imagePreview.value = null;
            const profileData = response.data?.data;
            
            localStorage.setItem('employee_data', JSON.stringify(profileData))

            employeeData.value = profileData;

            profileImage.value = profileData?.profile_image
                ? `/storage/${profileData.profile_image}`
                : "/default-avatar.png";
            window.dispatchEvent(new Event("profileUpdated"));
            toast("Updated successfully!", {
                type: "success",
                autoClose: 1000,
            });

        })
        .catch((error) => {
            if (error.response && error.response.data.code === 422) {
                errors.value = error.response.data.errors;
                if (errors.value.profile_image) {
                    errors.value.profile_image = ["(Allowed JPG, JPEG, or PNG. Max size of 800kB)"];
                }
            }
        });
};

const encodeBase64 = (data) => {
    return btoa(data);
};

</script>
