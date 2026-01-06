<template>
    <div class="auth-page">
        <div class="auth-container">
            <div class="auth-card">
                <div class="auth-header">
                    <img src="/images/logo.png" alt="YJS Jewellers" class="auth-logo" />
                    <h2>Create Account</h2>
                    <p>Join YJS Jewellers today</p>
                </div>

                <form @submit.prevent="handleRegister" class="auth-form">
                    <!-- Step 1: Basic Info -->
                    <div v-if="step === 1" class="form-step">
                        <div class="form-row">
                            <div class="form-group">
                                <label>First Name *</label>
                                <input type="text" v-model="form.first_name"
                                    placeholder="Enter first name" required />
                            </div>
                            <div class="form-group">
                                <label>Last Name *</label>
                                <input type="text" v-model="form.last_name"
                                    placeholder="Enter last name" required />
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Email Address *</label>
                            <input type="email" v-model="form.email"
                                placeholder="Enter email address" required />
                        </div>
                        <div class="form-group">
                            <label>Phone Number *</label>
                            <div class="phone-input">
                                <span class="country-code">+91</span>
                                <input type="tel" v-model="form.phone"
                                    placeholder="Enter 10-digit phone"
                                    maxlength="10" required />
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Gender</label>
                            <div class="gender-options">
                                <label class="radio-option">
                                    <input type="radio" v-model="form.gender" value="male" />
                                    <span>Male</span>
                                </label>
                                <label class="radio-option">
                                    <input type="radio" v-model="form.gender" value="female" />
                                    <span>Female</span>
                                </label>
                                <label class="radio-option">
                                    <input type="radio" v-model="form.gender" value="other" />
                                    <span>Other</span>
                                </label>
                            </div>
                        </div>
                        <button type="button" class="btn-primary" @click="sendOtp" :disabled="loading">
                            <span v-if="loading" class="spinner"></span>
                            {{ loading ? 'Sending OTP...' : 'Continue' }}
                        </button>
                    </div>

                    <!-- Step 2: OTP Verification -->
                    <div v-if="step === 2" class="form-step">
                        <div class="otp-info">
                            <p>We've sent a verification code to</p>
                            <strong>{{ form.phone }}</strong>
                            <button type="button" class="btn-link" @click="step = 1">Change</button>
                        </div>
                        <div class="form-group">
                            <label>Enter OTP</label>
                            <div class="otp-inputs">
                                <input v-for="(digit, index) in 6" :key="index"
                                    type="text" maxlength="1"
                                    v-model="otpDigits[index]"
                                    @input="handleOtpInput(index, $event)"
                                    @keydown="handleOtpKeydown(index, $event)"
                                    :ref="el => otpRefs[index] = el"
                                    class="otp-input" />
                            </div>
                            <span v-if="errors.otp" class="error-text">{{ errors.otp }}</span>
                        </div>
                        <div class="resend-otp">
                            <span v-if="resendTimer > 0">Resend OTP in {{ resendTimer }}s</span>
                            <button v-else type="button" class="btn-link" @click="resendOtp">Resend OTP</button>
                        </div>
                        <button type="submit" class="btn-primary" :disabled="loading || otpDigits.join('').length < 6">
                            <span v-if="loading" class="spinner"></span>
                            {{ loading ? 'Verifying...' : 'Verify & Create Account' }}
                        </button>
                    </div>

                    <!-- Step 3: Set Password (Optional) -->
                    <div v-if="step === 3" class="form-step">
                        <div class="success-icon">
                            <i class="bi-check-circle-fill"></i>
                        </div>
                        <h3>Account Created!</h3>
                        <p>Would you like to set a password for faster login?</p>

                        <div class="form-group">
                            <label>Password</label>
                            <div class="password-input">
                                <input :type="showPassword ? 'text' : 'password'"
                                    v-model="form.password"
                                    placeholder="Create password (min 8 characters)" />
                                <button type="button" class="toggle-password" @click="showPassword = !showPassword">
                                    <i :class="showPassword ? 'bi-eye-slash' : 'bi-eye'"></i>
                                </button>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Confirm Password</label>
                            <input type="password" v-model="form.password_confirmation"
                                placeholder="Confirm password" />
                        </div>
                        <div class="btn-group">
                            <button type="button" class="btn-secondary" @click="skipPassword">
                                Skip for now
                            </button>
                            <button type="button" class="btn-primary" @click="setPassword" :disabled="loading">
                                Set Password
                            </button>
                        </div>
                    </div>

                    <span v-if="errors.general" class="error-text text-center">{{ errors.general }}</span>
                </form>

                <div class="auth-footer" v-if="step === 1">
                    <p>Already have an account? <router-link to="/login">Login</router-link></p>
                </div>

                <div class="terms-text" v-if="step === 1">
                    <p>By creating an account, you agree to our
                        <a href="/terms">Terms of Service</a> and
                        <a href="/privacy">Privacy Policy</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'

const router = useRouter()

const step = ref(1)
const loading = ref(false)
const showPassword = ref(false)
const resendTimer = ref(0)
const errors = ref({})
const otpDigits = ref(['', '', '', '', '', ''])
const otpRefs = ref([])
let timerInterval = null

const form = ref({
    first_name: '',
    last_name: '',
    email: '',
    phone: '',
    gender: '',
    password: '',
    password_confirmation: ''
})

const startResendTimer = () => {
    resendTimer.value = 30
    timerInterval = setInterval(() => {
        resendTimer.value--
        if (resendTimer.value <= 0) clearInterval(timerInterval)
    }, 1000)
}

const sendOtp = async () => {
    if (!form.value.first_name || !form.value.last_name || !form.value.email || !form.value.phone) {
        errors.value.general = 'Please fill all required fields'
        return
    }

    errors.value = {}
    loading.value = true

    try {
        const response = await axios.post('/api/customer/send-otp', {
            identifier: form.value.phone,
            name: form.value.first_name + ' ' + form.value.last_name,
            email: form.value.email
        })
        if (response.data.success) {
            step.value = 2
            startResendTimer()
        }
    } catch (error) {
        errors.value.general = error.response?.data?.message || 'Failed to send OTP'
    } finally {
        loading.value = false
    }
}

const handleRegister = async () => {
    errors.value = {}
    loading.value = true

    try {
        const otp = otpDigits.value.join('')
        const response = await axios.post('/api/customer/verify-otp', {
            identifier: form.value.phone,
            otp: otp,
            name: form.value.first_name + ' ' + form.value.last_name,
            email: form.value.email,
            gender: form.value.gender
        })
        if (response.data.success) {
            localStorage.setItem('token', response.data.token)
            localStorage.setItem('customer', JSON.stringify(response.data.customer))
            step.value = 3
        }
    } catch (error) {
        errors.value.otp = error.response?.data?.message || 'Invalid OTP'
    } finally {
        loading.value = false
    }
}

const resendOtp = async () => {
    loading.value = true
    try {
        await axios.post('/api/customer/send-otp', {
            identifier: form.value.phone
        })
        otpDigits.value = ['', '', '', '', '', '']
        startResendTimer()
    } catch (error) {
        errors.value.otp = error.response?.data?.message || 'Failed to resend OTP'
    } finally {
        loading.value = false
    }
}

const setPassword = async () => {
    if (form.value.password !== form.value.password_confirmation) {
        errors.value.general = 'Passwords do not match'
        return
    }
    if (form.value.password.length < 8) {
        errors.value.general = 'Password must be at least 8 characters'
        return
    }

    loading.value = true
    try {
        const token = localStorage.getItem('token')
        await axios.post('/api/customer/password/set', {
            password: form.value.password,
            password_confirmation: form.value.password_confirmation
        }, {
            headers: { Authorization: `Bearer ${token}` }
        })
        router.push('/dashboard')
    } catch (error) {
        errors.value.general = error.response?.data?.message || 'Failed to set password'
    } finally {
        loading.value = false
    }
}

const skipPassword = () => {
    router.push('/dashboard')
}

const handleOtpInput = (index, event) => {
    const value = event.target.value
    if (value && index < 5) {
        otpRefs.value[index + 1]?.focus()
    }
}

const handleOtpKeydown = (index, event) => {
    if (event.key === 'Backspace' && !otpDigits.value[index] && index > 0) {
        otpRefs.value[index - 1]?.focus()
    }
}

onUnmounted(() => {
    if (timerInterval) clearInterval(timerInterval)
})
</script>

<style scoped>
.auth-page {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #f5f5f5 0%, #e8e8e8 100%);
    padding: 20px;
}

.auth-container {
    width: 100%;
    max-width: 480px;
}

.auth-card {
    background: #fff;
    border-radius: 16px;
    padding: 40px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.1);
}

.auth-header {
    text-align: center;
    margin-bottom: 30px;
}

.auth-logo {
    height: 50px;
    margin-bottom: 20px;
}

.auth-header h2 {
    color: #1a1a2e;
    margin-bottom: 8px;
}

.auth-header p {
    color: #666;
}

.auth-form {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.form-group label {
    font-weight: 500;
    color: #333;
}

.form-group input {
    padding: 14px 16px;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    font-size: 16px;
    transition: border-color 0.3s;
}

.form-group input:focus {
    outline: none;
    border-color: #b8860b;
}

.phone-input {
    display: flex;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    overflow: hidden;
}

.phone-input .country-code {
    padding: 14px 12px;
    background: #f5f5f5;
    border-right: 2px solid #e0e0e0;
    font-weight: 500;
}

.phone-input input {
    border: none;
    flex: 1;
}

.gender-options {
    display: flex;
    gap: 20px;
}

.radio-option {
    display: flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
}

.password-input {
    position: relative;
}

.password-input input {
    width: 100%;
    padding-right: 50px;
}

.toggle-password {
    position: absolute;
    right: 15px;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    cursor: pointer;
    color: #666;
}

.btn-primary {
    padding: 14px;
    background: linear-gradient(135deg, #b8860b 0%, #daa520 100%);
    color: #fff;
    border: none;
    border-radius: 8px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: transform 0.3s, box-shadow 0.3s;
}

.btn-primary:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(184, 134, 11, 0.4);
}

.btn-primary:disabled {
    opacity: 0.7;
    cursor: not-allowed;
}

.btn-secondary {
    padding: 14px;
    background: #f5f5f5;
    color: #333;
    border: none;
    border-radius: 8px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
}

.btn-group {
    display: flex;
    gap: 15px;
}

.btn-group button {
    flex: 1;
}

.btn-link {
    background: none;
    border: none;
    color: #b8860b;
    cursor: pointer;
    text-decoration: underline;
}

.otp-info {
    text-align: center;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 8px;
}

.otp-info p {
    margin-bottom: 5px;
    color: #666;
}

.otp-inputs {
    display: flex;
    gap: 10px;
    justify-content: center;
}

.otp-input {
    width: 45px;
    height: 50px;
    text-align: center;
    font-size: 20px;
    font-weight: 600;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
}

.otp-input:focus {
    outline: none;
    border-color: #b8860b;
}

.resend-otp {
    text-align: center;
    font-size: 14px;
    color: #666;
}

.success-icon {
    text-align: center;
    font-size: 60px;
    color: #28a745;
    margin-bottom: 15px;
}

.form-step h3 {
    text-align: center;
    margin-bottom: 10px;
}

.form-step > p {
    text-align: center;
    color: #666;
    margin-bottom: 20px;
}

.error-text {
    color: #dc3545;
    font-size: 13px;
}

.text-center {
    text-align: center;
}

.auth-footer {
    text-align: center;
    margin-top: 25px;
    padding-top: 25px;
    border-top: 1px solid #e0e0e0;
}

.auth-footer a {
    color: #b8860b;
    font-weight: 600;
    text-decoration: none;
}

.terms-text {
    margin-top: 20px;
    text-align: center;
    font-size: 13px;
    color: #666;
}

.terms-text a {
    color: #b8860b;
}

.spinner {
    display: inline-block;
    width: 16px;
    height: 16px;
    border: 2px solid #fff;
    border-top-color: transparent;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
    margin-right: 8px;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}
</style>
