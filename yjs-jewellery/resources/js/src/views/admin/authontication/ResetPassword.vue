<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'
import { useRouter, useRoute } from 'vue-router'

const router = useRouter()
const route = useRoute()

const password = ref('')
const confirmPassword = ref('')
const showPassword = ref(false)
const showConfirmPassword = ref(false)
const loading = ref(false)
const error = ref('')
const success = ref(false)
const tokenValid = ref(true)
const validating = ref(true)

const token = route.query.token || ''
const email = route.query.email || ''

const validateToken = async () => {
    if (!token || !email) {
        tokenValid.value = false
        validating.value = false
        return
    }

    try {
        await axios.post('/api/employee/validate-reset-token', { token, email })
        tokenValid.value = true
    } catch (err) {
        tokenValid.value = false
        error.value = 'This reset link has expired or is invalid. Please request a new one.'
    } finally {
        validating.value = false
    }
}

const validatePassword = (pwd) => {
    if (pwd.length < 8) {
        return 'Password must be at least 8 characters long'
    }
    if (!/[A-Z]/.test(pwd)) {
        return 'Password must contain at least one uppercase letter'
    }
    if (!/[a-z]/.test(pwd)) {
        return 'Password must contain at least one lowercase letter'
    }
    if (!/[0-9]/.test(pwd)) {
        return 'Password must contain at least one number'
    }
    return null
}

const handleSubmit = async () => {
    error.value = ''

    if (!password.value) {
        error.value = 'Password is required'
        return
    }

    const passwordError = validatePassword(password.value)
    if (passwordError) {
        error.value = passwordError
        return
    }

    if (password.value !== confirmPassword.value) {
        error.value = 'Passwords do not match'
        return
    }

    loading.value = true
    try {
        await axios.post('/api/employee/reset-password', {
            token,
            email,
            password: password.value,
            password_confirmation: confirmPassword.value
        })
        success.value = true
        setTimeout(() => {
            router.push('/admin/login')
        }, 3000)
    } catch (err) {
        error.value = err.response?.data?.message || 'Failed to reset password. Please try again.'
    } finally {
        loading.value = false
    }
}

const togglePassword = () => {
    showPassword.value = !showPassword.value
}

const toggleConfirmPassword = () => {
    showConfirmPassword.value = !showConfirmPassword.value
}

onMounted(() => {
    validateToken()
})
</script>
<template>
    <div class="admin_login">
        <img src="../../assets/images/yjs_logo.png" class="authontication_logo_img" alt="yjs_logo">
        <div class="admin_login_avtar_img">
            <img src="../../assets/images/admin/admin_login_avtar.png" class="authontication_avtar_img" alt="admin_login_avtar">
        </div>
        <div class="admin_login_form">
            <h3>Reset Password</h3>
            <h5 class="mb-4">Set your new Password</h5>

            <div v-if="validating" class="text-center py-4">
                <b-spinner variant="primary"></b-spinner>
                <p class="mt-2 text-muted">Validating reset link...</p>
            </div>

            <div v-else-if="!tokenValid" class="alert alert-danger mb-3">
                <i class="fa-solid fa-exclamation-circle me-2"></i>
                {{ error || 'Invalid or expired reset link. Please request a new password reset.' }}
                <div class="mt-3">
                    <b-link class="link_to_back" to="/admin/forgot-password">
                        <i class="fa-solid fa-arrow-left me-1"></i> Request new reset link
                    </b-link>
                </div>
            </div>

            <div v-else-if="success" class="alert alert-success mb-3">
                <i class="fa-solid fa-check-circle me-2"></i>
                Password reset successfully! Redirecting to login...
            </div>

            <template v-else>
                <div v-if="error" class="alert alert-danger mb-3">
                    <i class="fa-solid fa-exclamation-circle me-2"></i>
                    {{ error }}
                </div>

                <b-form @submit.prevent="handleSubmit">
                    <label for="password">New Password</label>
                    <b-input-group class="mb-2">
                        <template #prepend>
                            <span class="label_prepend border-right-0"><i class="fa-solid fa-key"></i></span>
                        </template>
                        <b-form-input
                            id="password"
                            v-model="password"
                            :type="showPassword ? 'text' : 'password'"
                            placeholder="Enter new password"
                            class="form_input_prepend"
                            :disabled="loading"
                            required
                        ></b-form-input>
                        <template #append>
                            <b-button variant="c" class="border_input_light_append" @click="togglePassword">
                                <i :class="showPassword ? 'fa-solid fa-eye-slash' : 'fa-regular fa-eye'"></i>
                            </b-button>
                        </template>
                    </b-input-group>
                    <small class="text-muted mb-3 d-block">
                        Must be 8+ characters with uppercase, lowercase, and number
                    </small>

                    <label for="confirmPassword">Confirm Password</label>
                    <b-input-group class="mb-1">
                        <template #prepend>
                            <span class="label_prepend border-right-0"><i class="fa-solid fa-key"></i></span>
                        </template>
                        <b-form-input
                            id="confirmPassword"
                            v-model="confirmPassword"
                            :type="showConfirmPassword ? 'text' : 'password'"
                            placeholder="Confirm new password"
                            class="form_input_prepend"
                            :disabled="loading"
                            required
                        ></b-form-input>
                        <template #append>
                            <b-button variant="c" class="border_input_light_append" @click="toggleConfirmPassword">
                                <i :class="showConfirmPassword ? 'fa-solid fa-eye-slash' : 'fa-regular fa-eye'"></i>
                            </b-button>
                        </template>
                    </b-input-group>

                    <b-button
                        class="w-100 mt-3"
                        variant="primary"
                        type="submit"
                        :disabled="loading"
                    >
                        <span v-if="loading">
                            <b-spinner small class="me-2"></b-spinner>
                            Resetting...
                        </span>
                        <span v-else>Set New Password</span>
                    </b-button>
                    <div class="text-center mt-3">
                        <b-link class="link_to_back" to="/admin/login"><i class="fa-solid fa-arrow-left me-1"></i> Back to login</b-link>
                    </div>
                </b-form>
            </template>
        </div>
    </div>
</template>