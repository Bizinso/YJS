<template>
    <div class="auth-page">
        <div class="auth-container">
            <div class="auth-card">
                <div class="auth-header">
                    <img src="/images/logo.png" alt="YJS Jewellers" class="auth-logo" />
                    <h2>Partner Login</h2>
                    <p>Access your B2B partner account</p>
                </div>

                <form @submit.prevent="handleLogin" class="auth-form">
                    <div class="form-group">
                        <label>Email Address</label>
                        <input type="email" v-model="form.email" placeholder="Enter your email" required
                               :class="{ 'error': errors.email }" />
                        <span v-if="errors.email" class="error-text">{{ errors.email }}</span>
                    </div>

                    <div class="form-group">
                        <label>Password</label>
                        <div class="password-input">
                            <input :type="showPassword ? 'text' : 'password'" v-model="form.password"
                                   placeholder="Enter your password" required />
                            <button type="button" class="toggle-password" @click="showPassword = !showPassword">
                                <i :class="showPassword ? 'bi-eye-slash' : 'bi-eye'"></i>
                            </button>
                        </div>
                        <span v-if="errors.password" class="error-text">{{ errors.password }}</span>
                    </div>

                    <div class="form-options">
                        <label class="remember-me">
                            <input type="checkbox" v-model="form.remember" />
                            <span>Remember me</span>
                        </label>
                        <router-link to="/partner/forgot-password" class="forgot-link">Forgot Password?</router-link>
                    </div>

                    <span v-if="errors.general" class="error-text text-center">{{ errors.general }}</span>

                    <button type="submit" class="btn-primary" :disabled="loading">
                        <span v-if="loading" class="spinner"></span>
                        {{ loading ? 'Logging in...' : 'Login' }}
                    </button>
                </form>

                <div class="auth-divider">
                    <span>or</span>
                </div>

                <div class="auth-footer">
                    <p>Not a partner yet?</p>
                    <router-link to="/partner/register" class="btn-outline">Apply for Partnership</router-link>
                </div>

                <div class="partner-benefits">
                    <h4>Partner Benefits</h4>
                    <ul>
                        <li><i class="bi-check-circle-fill"></i> Exclusive B2B pricing</li>
                        <li><i class="bi-check-circle-fill"></i> Bulk order discounts</li>
                        <li><i class="bi-check-circle-fill"></i> Priority support</li>
                        <li><i class="bi-check-circle-fill"></i> Extended payment terms</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'

const router = useRouter()

const loading = ref(false)
const showPassword = ref(false)
const errors = ref({})

const form = ref({
    email: '',
    password: '',
    remember: false
})

const handleLogin = async () => {
    errors.value = {}
    loading.value = true

    try {
        const response = await axios.post('/api/partner/login', form.value)
        if (response.data.success) {
            localStorage.setItem('partner_token', response.data.token)
            localStorage.setItem('partner', JSON.stringify(response.data.partner))
            router.push('/partner/dashboard')
        }
    } catch (error) {
        const message = error.response?.data?.message || 'Invalid credentials'
        if (error.response?.data?.errors) {
            errors.value = error.response.data.errors
        } else {
            errors.value.general = message
        }
    } finally {
        loading.value = false
    }
}
</script>

<style scoped>
.auth-page {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
    padding: 20px;
}

.auth-container {
    width: 100%;
    max-width: 440px;
}

.auth-card {
    background: #fff;
    border-radius: 16px;
    padding: 40px;
    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
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
    border-color: #1a1a2e;
}

.form-group input.error {
    border-color: #dc3545;
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
    font-size: 14px;
}

.forgot-link {
    color: #1a1a2e;
    text-decoration: none;
    font-size: 14px;
}

.forgot-link:hover {
    text-decoration: underline;
}

.error-text {
    color: #dc3545;
    font-size: 13px;
}

.text-center {
    text-align: center;
}

.btn-primary {
    padding: 14px;
    background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
    color: #fff;
    border: none;
    border-radius: 8px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: transform 0.3s, box-shadow 0.3s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.btn-primary:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(26, 26, 46, 0.4);
}

.btn-primary:disabled {
    opacity: 0.7;
    cursor: not-allowed;
}

.auth-divider {
    display: flex;
    align-items: center;
    margin: 25px 0;
}

.auth-divider::before,
.auth-divider::after {
    content: '';
    flex: 1;
    height: 1px;
    background: #e0e0e0;
}

.auth-divider span {
    padding: 0 15px;
    color: #999;
    font-size: 14px;
}

.auth-footer {
    text-align: center;
}

.auth-footer p {
    color: #666;
    margin-bottom: 15px;
}

.btn-outline {
    display: block;
    padding: 14px;
    border: 2px solid #1a1a2e;
    background: transparent;
    color: #1a1a2e;
    border-radius: 8px;
    font-size: 16px;
    font-weight: 600;
    text-decoration: none;
    text-align: center;
    transition: all 0.3s;
}

.btn-outline:hover {
    background: #1a1a2e;
    color: #fff;
}

.partner-benefits {
    margin-top: 30px;
    padding-top: 25px;
    border-top: 1px solid #eee;
}

.partner-benefits h4 {
    font-size: 14px;
    color: #666;
    text-align: center;
    margin-bottom: 15px;
}

.partner-benefits ul {
    list-style: none;
    padding: 0;
    margin: 0;
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
}

.partner-benefits li {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
    color: #333;
}

.partner-benefits li i {
    color: #28a745;
}

.spinner {
    width: 16px;
    height: 16px;
    border: 2px solid #fff;
    border-top-color: transparent;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

@media (max-width: 480px) {
    .auth-card {
        padding: 30px 20px;
    }

    .partner-benefits ul {
        grid-template-columns: 1fr;
    }
}
</style>
