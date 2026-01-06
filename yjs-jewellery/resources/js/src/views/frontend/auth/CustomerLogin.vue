<template>
    <div class="auth-page">
        <div class="auth-container">
            <div class="auth-card">
                <div class="auth-header">
                    <img src="/images/logo.png" alt="YJS Jewellers" class="auth-logo" />
                    <h2>Welcome Back</h2>
                    <p>Login to your account</p>
                </div>

                <!-- Login Method Tabs -->
                <div class="auth-tabs">
                    <button :class="['tab-btn', loginMethod === 'otp' ? 'active' : '']" @click="loginMethod = 'otp'">
                        OTP Login
                    </button>
                    <button :class="['tab-btn', loginMethod === 'password' ? 'active' : '']" @click="loginMethod = 'password'">
                        Password Login
                    </button>
                </div>

                <!-- OTP Login -->
                <form v-if="loginMethod === 'otp'" @submit.prevent="handleOtpLogin" class="auth-form">
                    <div v-if="!otpSent" class="form-step">
                        <div class="form-group">
                            <label>Email or Phone Number</label>
                            <input type="text" v-model="otpForm.identifier"
                                placeholder="Enter email or phone" required
                                :class="{ 'error': errors.identifier }" />
                            <span v-if="errors.identifier" class="error-text">{{ errors.identifier }}</span>
                        </div>
                        <button type="submit" class="btn-primary" :disabled="loading">
                            <span v-if="loading" class="spinner"></span>
                            {{ loading ? 'Sending...' : 'Send OTP' }}
                        </button>
                    </div>

                    <div v-else class="form-step">
                        <div class="otp-sent-info">
                            <p>OTP sent to <strong>{{ maskedIdentifier }}</strong></p>
                            <button type="button" class="btn-link" @click="otpSent = false">Change</button>
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
                            {{ loading ? 'Verifying...' : 'Verify & Login' }}
                        </button>
                    </div>
                </form>

                <!-- Password Login -->
                <form v-else @submit.prevent="handlePasswordLogin" class="auth-form">
                    <div class="form-group">
                        <label>Email or Phone Number</label>
                        <input type="text" v-model="passwordForm.identifier"
                            placeholder="Enter email or phone" required />
                    </div>
                    <div class="form-group">
                        <label>Password</label>
                        <div class="password-input">
                            <input :type="showPassword ? 'text' : 'password'"
                                v-model="passwordForm.password"
                                placeholder="Enter password" required />
                            <button type="button" class="toggle-password" @click="showPassword = !showPassword">
                                <i :class="showPassword ? 'bi-eye-slash' : 'bi-eye'"></i>
                            </button>
                        </div>
                    </div>
                    <div class="form-options">
                        <label class="remember-me">
                            <input type="checkbox" v-model="passwordForm.remember" />
                            <span>Remember me</span>
                        </label>
                        <router-link to="/forgot-password" class="forgot-link">Forgot Password?</router-link>
                    </div>
                    <button type="submit" class="btn-primary" :disabled="loading">
                        <span v-if="loading" class="spinner"></span>
                        {{ loading ? 'Logging in...' : 'Login' }}
                    </button>
                </form>

                <div class="auth-footer">
                    <p>Don't have an account? <router-link to="/register">Register</router-link></p>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'

const router = useRouter()

const loginMethod = ref('otp')
const loading = ref(false)
const showPassword = ref(false)
const otpSent = ref(false)
const resendTimer = ref(0)
const errors = ref({})
const otpDigits = ref(['', '', '', '', '', ''])
const otpRefs = ref([])
let timerInterval = null

const otpForm = ref({
    identifier: ''
})

const passwordForm = ref({
    identifier: '',
    password: '',
    remember: false
})

const maskedIdentifier = computed(() => {
    const id = otpForm.value.identifier
    if (id.includes('@')) {
        const [name, domain] = id.split('@')
        return name.substring(0, 2) + '***@' + domain
    }
    return id.substring(0, 2) + '****' + id.substring(id.length - 2)
})

const startResendTimer = () => {
    resendTimer.value = 30
    timerInterval = setInterval(() => {
        resendTimer.value--
        if (resendTimer.value <= 0) clearInterval(timerInterval)
    }, 1000)
}

const handleOtpLogin = async () => {
    errors.value = {}
    loading.value = true

    try {
        if (!otpSent.value) {
            const response = await axios.post('/api/customer/send-otp', {
                identifier: otpForm.value.identifier
            })
            if (response.data.success) {
                otpSent.value = true
                startResendTimer()
            }
        } else {
            const otp = otpDigits.value.join('')
            const response = await axios.post('/api/customer/verify-otp', {
                identifier: otpForm.value.identifier,
                otp: otp
            })
            if (response.data.success) {
                localStorage.setItem('token', response.data.token)
                localStorage.setItem('customer', JSON.stringify(response.data.customer))
                router.push('/dashboard')
            }
        }
    } catch (error) {
        const message = error.response?.data?.message || 'Something went wrong'
        if (otpSent.value) {
            errors.value.otp = message
        } else {
            errors.value.identifier = message
        }
    } finally {
        loading.value = false
    }
}

const handlePasswordLogin = async () => {
    errors.value = {}
    loading.value = true

    try {
        const response = await axios.post('/api/customer/login-password', passwordForm.value)
        if (response.data.success) {
            localStorage.setItem('token', response.data.token)
            localStorage.setItem('customer', JSON.stringify(response.data.customer))
            router.push('/dashboard')
        }
    } catch (error) {
        errors.value.general = error.response?.data?.message || 'Invalid credentials'
    } finally {
        loading.value = false
    }
}

const resendOtp = async () => {
    loading.value = true
    try {
        await axios.post('/api/customer/send-otp', {
            identifier: otpForm.value.identifier
        })
        startResendTimer()
    } catch (error) {
        errors.value.otp = error.response?.data?.message || 'Failed to resend OTP'
    } finally {
        loading.value = false
    }
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
    max-width: 420px;
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

.auth-tabs {
    display: flex;
    gap: 10px;
    margin-bottom: 25px;
}

.tab-btn {
    flex: 1;
    padding: 12px;
    border: 2px solid #e0e0e0;
    background: #fff;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 500;
    transition: all 0.3s;
}

.tab-btn.active {
    border-color: #b8860b;
    background: linear-gradient(135deg, #b8860b 0%, #daa520 100%);
    color: #fff;
}

.auth-form {
    display: flex;
    flex-direction: column;
    gap: 20px;
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

.form-group input.error {
    border-color: #dc3545;
}

.error-text {
    color: #dc3545;
    font-size: 13px;
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

.form-options {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.remember-me {
    display: flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
}

.forgot-link {
    color: #b8860b;
    text-decoration: none;
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

.btn-link {
    background: none;
    border: none;
    color: #b8860b;
    cursor: pointer;
    text-decoration: underline;
}

.otp-sent-info {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px;
    background: #f8f9fa;
    border-radius: 8px;
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
