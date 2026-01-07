<script setup>
import { ref } from 'vue'
import axios from 'axios'
import { useRouter } from 'vue-router'

const router = useRouter()
const email = ref('')
const loading = ref(false)
const error = ref('')
const success = ref(false)

const validateEmail = (email) => {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)
}

const handleSubmit = async () => {
    error.value = ''

    if (!email.value) {
        error.value = 'Email is required'
        return
    }

    if (!validateEmail(email.value)) {
        error.value = 'Please enter a valid email address'
        return
    }

    loading.value = true
    try {
        await axios.post('/api/employee/forgot-password', { email: email.value })
        success.value = true
    } catch (err) {
        error.value = err.response?.data?.message || 'Failed to send reset email. Please try again.'
    } finally {
        loading.value = false
    }
}
</script>
<template>
    <div class="admin_login">
        <img src="../../assets/images/yjs_logo.png" class="authontication_logo_img" alt="yjs_logo">
        <div class="admin_login_avtar_img nisforget">
            <img src="../../assets/images/admin/forgetPassword.svg" class="authontication_avtar_img" alt="admin_forgot_password_avtar">
        </div>
        <div class="admin_login_form">
            <h3>Forgot Password?</h3>
            <h5 class="mb-4">Send email to Reset Password</h5>

            <div v-if="success" class="alert alert-success mb-3">
                <i class="fa-solid fa-check-circle me-2"></i>
                Password reset link has been sent to your email. Please check your inbox.
            </div>

            <div v-if="error" class="alert alert-danger mb-3">
                <i class="fa-solid fa-exclamation-circle me-2"></i>
                {{ error }}
            </div>

            <b-form v-if="!success" @submit.prevent="handleSubmit">
                <label for="email">Email Id</label>
                <b-input-group class="mb-2">
                    <template #prepend>
                        <span class="label_prepend border-right-0"><i class="fa-regular fa-user"></i></span>
                    </template>
                    <b-form-input
                        id="email"
                        v-model="email"
                        type="email"
                        placeholder="admin@mail.com"
                        class="form_input_prepend p-2"
                        :disabled="loading"
                        required
                    ></b-form-input>
                </b-input-group>
                <b-button
                    class="w-100 mt-2"
                    variant="primary"
                    type="submit"
                    :disabled="loading"
                >
                    <span v-if="loading">
                        <b-spinner small class="me-2"></b-spinner>
                        Sending...
                    </span>
                    <span v-else>Send Reset Mail</span>
                </b-button>
                <div class="text-center mt-3">
                    <b-link class="link_to_back" to="/admin/login"><i class="fa-solid fa-arrow-left me-1"></i> Back to login</b-link>
                </div>
            </b-form>

            <div v-else class="text-center mt-3">
                <b-link class="link_to_back" to="/admin/login"><i class="fa-solid fa-arrow-left me-1"></i> Back to login</b-link>
            </div>
        </div>
    </div>
</template>