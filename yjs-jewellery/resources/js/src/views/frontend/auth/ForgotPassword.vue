<template>
    <div class="auth-page">
        <div class="auth-container">
            <div class="auth-card">
                <div class="auth-header">
                    <img src="/images/logo.png" alt="YJS Jewellers" class="auth-logo" />
                    <h2>Reset Password</h2>
                    <p>We'll help you recover your account</p>
                </div>

                <form @submit.prevent="handleSubmit" class="auth-form">
                    <!-- Step 1: Enter Identifier -->
                    <div v-if="step === 1" class="form-step">
                        <div class="form-group">
                            <label>Email or Phone Number</label>
                            <input type="text" v-model="form.identifier"
                                placeholder="Enter registered email or phone" required />
                            <span v-if="errors.identifier" class="error-text">{{ errors.identifier }}</span>
                        </div>
                        <button type="submit" class="btn-primary" :disabled="loading">
                            <span v-if="loading" class="spinner"></span>
                            {{ loading ? 'Sending...' : 'Send Reset OTP' }}
                        </button>
                    </div>

                    <!-- Step 2: Verify OTP -->
                    <div v-if="step === 2" class="form-step">
                        <div class="otp-info">
                            <p>Enter the OTP sent to</p>
                            <strong>{{ maskedIdentifier }}</strong>
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
                            {{ loading ? 'Verifying...' : 'Verify OTP' }}
                        </button>
                    </div>

                    <!-- Step 3: Set New Password -->
                    <div v-if="step === 3" class="form-step">
                        <div class="form-group">
                            <label>New Password</label>
                            <div class="password-input">
                                <input :type="showPassword ? 'text' : 'password'"
                                    v-model="form.password"
                                    placeholder="Enter new password (min 8 characters)" required />
                                <button type="button" class="toggle-password" @click="showPassword = !showPassword">
                                    <i :class="showPassword ? 'bi-eye-slash' : 'bi-eye'"></i>
                                </button>
                            </div>
                            <div class="password-strength">
                                <div class="strength-bar" :class="passwordStrength"></div>
                                <span>{{ passwordStrengthText }}</span>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Confirm New Password</label>
                            <input type="password" v-model="form.password_confirmation"
                                placeholder="Confirm new password" required />
                            <span v-if="errors.password" class="error-text">{{ errors.password }}</span>
                        </div>
                        <button type="submit" class="btn-primary" :disabled="loading">
                            <span v-if="loading" class="spinner"></span>
                            {{ loading ? 'Resetting...' : 'Reset Password' }}
                        </button>
                    </div>

                    <!-- Step 4: Success -->
                    <div v-if="step === 4" class="form-step success-step">
                        <div class="success-icon">
                            <i class="bi-check-circle-fill"></i>
                        </div>
                        <h3>Password Reset Successful!</h3>
                        <p>Your password has been reset successfully. You can now login with your new password.</p>
                        <router-link to="/login" class="btn-primary">Login Now</router-link>
                    </div>
                </form>

                <div class="auth-footer" v-if="step < 4">
                    <router-link to="/login">Back to Login</router-link>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onUnmounted } from 'vue'
import axios from 'axios'

const step = ref(1)
const loading = ref(false)
const showPassword = ref(false)
const resendTimer = ref(0)
const errors = ref({})
const resetToken = ref('')
const otpDigits = ref(['', '', '', '', '', ''])
const otpRefs = ref([])
let timerInterval = null

const form = ref({
    identifier: '',
    password: '',
    password_confirmation: ''
})

const maskedIdentifier = computed(() => {
    const id = form.value.identifier
    if (id.includes('@')) {
        const [name, domain] = id.split('@')
        return name.substring(0, 2) + '***@' + domain
    }
    return id.substring(0, 2) + '****' + id.substring(id.length - 2)
})

const passwordStrength = computed(() => {
    const password = form.value.password
    if (!password) return ''
    if (password.length < 6) return 'weak'
    if (password.length < 8) return 'medium'
    if (/[A-Z]/.test(password) && /[0-9]/.test(password) && password.length >= 8) return 'strong'
    return 'medium'
})

const passwordStrengthText = computed(() => {
    const strength = passwordStrength.value
    if (strength === 'weak') return 'Weak password'
    if (strength === 'medium') return 'Medium strength'
    if (strength === 'strong') return 'Strong password'
    return ''
})

const startResendTimer = () => {
    resendTimer.value = 30
    timerInterval = setInterval(() => {
        resendTimer.value--
        if (resendTimer.value <= 0) clearInterval(timerInterval)
    }, 1000)
}

const handleSubmit = async () => {
    errors.value = {}
    loading.value = true

    try {
        if (step.value === 1) {
            await axios.post('/api/customer/forgot-password', {
                identifier: form.value.identifier
            })
            step.value = 2
            startResendTimer()
        } else if (step.value === 2) {
            const otp = otpDigits.value.join('')
            const response = await axios.post('/api/customer/verify-reset-otp', {
                identifier: form.value.identifier,
                otp: otp
            })
            if (response.data.success) {
                resetToken.value = response.data.token
                step.value = 3
            }
        } else if (step.value === 3) {
            if (form.value.password !== form.value.password_confirmation) {
                errors.value.password = 'Passwords do not match'
                loading.value = false
                return
            }
            await axios.post('/api/customer/reset-password', {
                token: resetToken.value,
                password: form.value.password,
                password_confirmation: form.value.password_confirmation
            })
            step.value = 4
        }
    } catch (error) {
        const message = error.response?.data?.message || 'Something went wrong'
        if (step.value === 1) errors.value.identifier = message
        else if (step.value === 2) errors.value.otp = message
        else errors.value.password = message
    } finally {
        loading.value = false
    }
}

const resendOtp = async () => {
    loading.value = true
    try {
        await axios.post('/api/customer/forgot-password', {
            identifier: form.value.identifier
        })
        otpDigits.value = ['', '', '', '', '', '']
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
}

.form-group input:focus {
    outline: none;
    border-color: #b8860b;
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

.password-strength {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 12px;
}

.strength-bar {
    height: 4px;
    width: 100px;
    background: #e0e0e0;
    border-radius: 2px;
}

.strength-bar.weak { background: linear-gradient(90deg, #dc3545 50%, #e0e0e0 50%); }
.strength-bar.medium { background: linear-gradient(90deg, #ffc107 75%, #e0e0e0 75%); }
.strength-bar.strong { background: #28a745; }

.btn-primary {
    padding: 14px;
    background: linear-gradient(135deg, #b8860b 0%, #daa520 100%);
    color: #fff;
    border: none;
    border-radius: 8px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    text-decoration: none;
    text-align: center;
    display: block;
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

.otp-info {
    text-align: center;
    padding: 15px;
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
}

.resend-otp {
    text-align: center;
    font-size: 14px;
    color: #666;
}

.success-step {
    text-align: center;
}

.success-icon {
    font-size: 60px;
    color: #28a745;
    margin-bottom: 15px;
}

.success-step h3 {
    margin-bottom: 10px;
}

.success-step p {
    color: #666;
    margin-bottom: 20px;
}

.error-text {
    color: #dc3545;
    font-size: 13px;
}

.auth-footer {
    text-align: center;
    margin-top: 25px;
}

.auth-footer a {
    color: #b8860b;
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
