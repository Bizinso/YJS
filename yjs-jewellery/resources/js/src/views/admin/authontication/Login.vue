<template>
  <div class="admin_login">
    <img src="../../assets/images/yjs_logo.png" class="authontication_logo_img" alt="yjs_logo" />
    <div class="admin_login_avtar_img">
      <img src="../../assets/images/admin/loginImage.svg" class="authontication_avtar_img"
        alt="admin_login_avtar" />
    </div>

    <div class="admin_login_form">
      <h3>Log In</h3>
      <h5 class="mb-4">to your Admin panel</h5>

      <b-form @submit.prevent="handleLogin">
        <label>Email Id</label>
        <b-input-group class="mb-2">
          <template #prepend>
            <span class="label_prepend border-right-0">
              <i class="fa-regular fa-user"></i>
            </span>
          </template>
          <b-form-input v-model="email" placeholder=" Enter email id" class="form_input_prepend p-1" />
        </b-input-group>
        <p v-if="emailError" class="text-danger small mt-n2 mb-2">{{ emailError }}</p>
        <label>Password</label>
        <b-input-group class="mb-1 position-relative">

          <template #prepend>
            <span class="label_prepend border-right-0">
              <i class="fa-solid fa-key"></i>
            </span>
          </template>

          <!-- PASSWORD INPUT -->
          <b-form-input :type="showPassword ? 'text' : 'password'" v-model="password" placeholder="Enter password"
            class="form_input_prepend p-2" />

          <!-- TOGGLE ICON -->
          <template #append>
            <span class="label_append border-left-0" @click="showPassword = !showPassword" style="cursor:pointer;">
              <i :class="showPassword ? 'fa fa-eye-slash' : 'fa fa-eye'"></i>
            </span>
          </template>
        </b-input-group>
        <p v-if="passwordError" class="text-danger small mt-n2 mb-2">{{ passwordError }}</p>
        <div class="d-flex justify-content-between align-items-center mt-1 mb-2">
          <b-form-checkbox v-model="rememberMe">
            Remember Me
          </b-form-checkbox>

          <b-link class="forgot_password" to="/admin/forgot-password">
            Forgot Password?
          </b-link>
        </div>


        <b-button class="w-100 mt-2" variant="primary" :disabled="loading" type="submit">
          {{ loading ? 'Logging in...' : 'Login' }}
        </b-button>

        
        <p v-if="error" class="text-danger small mb-2 magniteError">{{ error }}</p>
      </b-form>
    </div>
  </div>
</template>
<script setup>
import { ref } from 'vue'
import axios from 'axios'
import { useRouter } from 'vue-router'
import { BForm, BFormInput, BInputGroup, BButton, BLink } from 'bootstrap-vue-3'
import { setEmployeeLogin } from '@/stores/authEmployee'
import { updateAbilities } from "../../../ability";
import { toast } from "vue3-toastify";
import "vue3-toastify/dist/index.css";

const router = useRouter()

const email = ref('')
const password = ref('')
const loading = ref(false)
const error = ref('')
const emailError = ref('')
const passwordError = ref('')
const showPassword = ref(false)
const rememberMe = ref(false)

const handleLogin = async () => {
  error.value = ''
  loading.value = true

  try {
    const res = await axios.post('/api/employee/login', {
      email: email.value,
      password: password.value,
      remember: rememberMe.value,
    })

    const token = res.data.token
    const employee = res.data.employee
    setEmployeeLogin(token, employee)
    localStorage.setItem('employee_token', token) // ✅ keep naming consistent
    localStorage.setItem('employee_data', JSON.stringify(employee))
    const abilityData = [
      { action: "Read", subject: "Auth" },
      ...(employee.ability ?? []),
    ];
    localStorage.setItem(`user_ability`, JSON.stringify(abilityData));
    updateAbilities(abilityData);
    axios.defaults.headers.common['Authorization'] = `Bearer ${token}`
    setTimeout(() => {
      toast("Logged in successfully!", {
        type: "success",
        autoClose: 1000,
      });
    }, 300);
    router.push({ name: 'admin.dashboard' })
  }  catch (err) {
  const response = err.response?.data || {}
  const errors = response.errors || {}

  emailError.value = errors.email?.[0] || ''
  passwordError.value = errors.password?.[0] || ''

  // ✅ Show global error ONLY if no field errors exist
  if (!emailError.value && !passwordError.value) {
    error.value = response.message || 'Invalid credentials'
  } else {
    error.value = ''
  }
} finally {
  loading.value = false
}

}
</script>
<style>
.label_append {
  padding: 8px 12px;
  background: #fff;
  border: 1px solid #ced4da;
  border-left: 0;
  display: flex;
  align-items: center;
  font-size: 14px;
}
</style>