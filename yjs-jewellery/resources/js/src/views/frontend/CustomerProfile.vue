<template>
    <div class="profile-page">
        <div class="container">
            <div class="page-header">
                <h1>My Profile</h1>
                <p>Manage your account details</p>
            </div>

            <div class="profile-grid">
                <!-- Profile Card -->
                <div class="profile-card main-card">
                    <div class="card-header">
                        <h2>Personal Information</h2>
                        <button v-if="!editing" class="btn-edit" @click="editing = true">
                            <i class="bi-pencil"></i> Edit
                        </button>
                    </div>

                    <div class="profile-avatar">
                        <div class="avatar">
                            <img v-if="profile.avatar" :src="profile.avatar" alt="Profile" />
                            <span v-else>{{ getInitials() }}</span>
                        </div>
                        <button v-if="editing" class="change-avatar" @click="triggerFileUpload">
                            <i class="bi-camera"></i> Change Photo
                        </button>
                        <input type="file" ref="fileInput" @change="handleAvatarUpload" accept="image/*" hidden />
                    </div>

                    <form v-if="editing" @submit.prevent="saveProfile" class="profile-form">
                        <div class="form-row">
                            <div class="form-group">
                                <label>First Name</label>
                                <input type="text" v-model="form.first_name" required />
                            </div>
                            <div class="form-group">
                                <label>Last Name</label>
                                <input type="text" v-model="form.last_name" required />
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Email Address</label>
                            <input type="email" v-model="form.email" required />
                        </div>
                        <div class="form-group">
                            <label>Phone Number</label>
                            <div class="phone-input">
                                <span class="country-code">+91</span>
                                <input type="tel" v-model="form.phone" maxlength="10" required />
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Date of Birth</label>
                                <input type="date" v-model="form.dob" />
                            </div>
                            <div class="form-group">
                                <label>Gender</label>
                                <select v-model="form.gender">
                                    <option value="">Select</option>
                                    <option value="male">Male</option>
                                    <option value="female">Female</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Anniversary Date (Optional)</label>
                            <input type="date" v-model="form.anniversary" />
                        </div>

                        <div class="form-actions">
                            <button type="button" class="btn-secondary" @click="cancelEdit">Cancel</button>
                            <button type="submit" class="btn-primary" :disabled="saving">
                                {{ saving ? 'Saving...' : 'Save Changes' }}
                            </button>
                        </div>
                    </form>

                    <div v-else class="profile-info">
                        <div class="info-row">
                            <span class="label">Name</span>
                            <span class="value">{{ profile.first_name }} {{ profile.last_name }}</span>
                        </div>
                        <div class="info-row">
                            <span class="label">Email</span>
                            <span class="value">{{ profile.email }}</span>
                        </div>
                        <div class="info-row">
                            <span class="label">Phone</span>
                            <span class="value">+91 {{ profile.phone }}</span>
                        </div>
                        <div class="info-row" v-if="profile.dob">
                            <span class="label">Date of Birth</span>
                            <span class="value">{{ formatDate(profile.dob) }}</span>
                        </div>
                        <div class="info-row" v-if="profile.gender">
                            <span class="label">Gender</span>
                            <span class="value">{{ capitalize(profile.gender) }}</span>
                        </div>
                        <div class="info-row" v-if="profile.anniversary">
                            <span class="label">Anniversary</span>
                            <span class="value">{{ formatDate(profile.anniversary) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Quick Links -->
                <div class="side-cards">
                    <!-- Change Password -->
                    <div class="profile-card">
                        <h3>Security</h3>
                        <div class="security-options">
                            <button class="security-btn" @click="showPasswordModal = true">
                                <i class="bi-key"></i>
                                <div>
                                    <span>Change Password</span>
                                    <small>Update your password</small>
                                </div>
                                <i class="bi-chevron-right"></i>
                            </button>
                            <button class="security-btn" @click="showPhoneModal = true">
                                <i class="bi-phone"></i>
                                <div>
                                    <span>Update Phone</span>
                                    <small>Change phone number</small>
                                </div>
                                <i class="bi-chevron-right"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Preferences -->
                    <div class="profile-card">
                        <h3>Preferences</h3>
                        <div class="preference-options">
                            <label class="preference-item">
                                <div>
                                    <span>Email Notifications</span>
                                    <small>Receive order updates via email</small>
                                </div>
                                <input type="checkbox" v-model="preferences.email_notifications" @change="updatePreferences" />
                            </label>
                            <label class="preference-item">
                                <div>
                                    <span>SMS Notifications</span>
                                    <small>Receive order updates via SMS</small>
                                </div>
                                <input type="checkbox" v-model="preferences.sms_notifications" @change="updatePreferences" />
                            </label>
                            <label class="preference-item">
                                <div>
                                    <span>Promotional Emails</span>
                                    <small>Receive offers and promotions</small>
                                </div>
                                <input type="checkbox" v-model="preferences.promotional_emails" @change="updatePreferences" />
                            </label>
                        </div>
                    </div>

                    <!-- Account Actions -->
                    <div class="profile-card danger-zone">
                        <h3>Account</h3>
                        <button class="btn-danger-outline" @click="confirmDeleteAccount">
                            <i class="bi-trash"></i> Delete Account
                        </button>
                        <p class="warning-text">This action cannot be undone.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Change Password Modal -->
        <div v-if="showPasswordModal" class="modal-overlay" @click.self="showPasswordModal = false">
            <div class="modal-content">
                <div class="modal-header">
                    <h3>Change Password</h3>
                    <button class="close-btn" @click="showPasswordModal = false">&times;</button>
                </div>
                <form @submit.prevent="changePassword" class="modal-form">
                    <div class="form-group">
                        <label>Current Password</label>
                        <input type="password" v-model="passwordForm.current_password" required />
                    </div>
                    <div class="form-group">
                        <label>New Password</label>
                        <input type="password" v-model="passwordForm.new_password" required minlength="8" />
                    </div>
                    <div class="form-group">
                        <label>Confirm New Password</label>
                        <input type="password" v-model="passwordForm.confirm_password" required />
                    </div>
                    <div class="modal-actions">
                        <button type="button" class="btn-secondary" @click="showPasswordModal = false">Cancel</button>
                        <button type="submit" class="btn-primary" :disabled="changingPassword">
                            {{ changingPassword ? 'Updating...' : 'Update Password' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Update Phone Modal -->
        <div v-if="showPhoneModal" class="modal-overlay" @click.self="showPhoneModal = false">
            <div class="modal-content">
                <div class="modal-header">
                    <h3>Update Phone Number</h3>
                    <button class="close-btn" @click="showPhoneModal = false">&times;</button>
                </div>
                <form @submit.prevent="updatePhone" class="modal-form">
                    <div v-if="phoneStep === 1">
                        <div class="form-group">
                            <label>New Phone Number</label>
                            <div class="phone-input">
                                <span class="country-code">+91</span>
                                <input type="tel" v-model="phoneForm.phone" maxlength="10" required />
                            </div>
                        </div>
                        <button type="submit" class="btn-primary" :disabled="sendingOtp">
                            {{ sendingOtp ? 'Sending OTP...' : 'Send OTP' }}
                        </button>
                    </div>
                    <div v-else>
                        <div class="form-group">
                            <label>Enter OTP sent to +91 {{ phoneForm.phone }}</label>
                            <input type="text" v-model="phoneForm.otp" maxlength="6" required />
                        </div>
                        <div class="modal-actions">
                            <button type="button" class="btn-secondary" @click="phoneStep = 1">Change Number</button>
                            <button type="submit" class="btn-primary" :disabled="verifyingOtp">
                                {{ verifyingOtp ? 'Verifying...' : 'Verify & Update' }}
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'

const profile = ref({})
const form = ref({})
const editing = ref(false)
const saving = ref(false)
const fileInput = ref(null)

const showPasswordModal = ref(false)
const showPhoneModal = ref(false)
const changingPassword = ref(false)
const sendingOtp = ref(false)
const verifyingOtp = ref(false)
const phoneStep = ref(1)

const passwordForm = ref({
    current_password: '',
    new_password: '',
    confirm_password: ''
})

const phoneForm = ref({
    phone: '',
    otp: ''
})

const preferences = ref({
    email_notifications: true,
    sms_notifications: true,
    promotional_emails: false
})

const getAuthHeaders = () => {
    const token = localStorage.getItem('token')
    return { Authorization: `Bearer ${token}` }
}

const fetchProfile = async () => {
    try {
        const response = await axios.get('/api/customer/profile', {
            headers: getAuthHeaders()
        })
        profile.value = response.data.data || response.data
        form.value = { ...profile.value }
        if (response.data.preferences) {
            preferences.value = { ...preferences.value, ...response.data.preferences }
        }
    } catch (error) {
        console.error('Failed to fetch profile:', error)
    }
}

const saveProfile = async () => {
    saving.value = true
    try {
        const response = await axios.put('/api/customer/profile', form.value, {
            headers: getAuthHeaders()
        })
        profile.value = response.data.data || response.data
        editing.value = false
        localStorage.setItem('customer', JSON.stringify(profile.value))
    } catch (error) {
        alert(error.response?.data?.message || 'Failed to update profile')
    } finally {
        saving.value = false
    }
}

const cancelEdit = () => {
    form.value = { ...profile.value }
    editing.value = false
}

const triggerFileUpload = () => {
    fileInput.value?.click()
}

const handleAvatarUpload = async (event) => {
    const file = event.target.files[0]
    if (!file) return

    const formData = new FormData()
    formData.append('avatar', file)

    try {
        const response = await axios.post('/api/customer/profile/avatar', formData, {
            headers: {
                ...getAuthHeaders(),
                'Content-Type': 'multipart/form-data'
            }
        })
        profile.value.avatar = response.data.avatar
        form.value.avatar = response.data.avatar
    } catch (error) {
        alert('Failed to upload avatar')
    }
}

const changePassword = async () => {
    if (passwordForm.value.new_password !== passwordForm.value.confirm_password) {
        alert('Passwords do not match')
        return
    }

    changingPassword.value = true
    try {
        await axios.post('/api/customer/password/change', passwordForm.value, {
            headers: getAuthHeaders()
        })
        alert('Password updated successfully')
        showPasswordModal.value = false
        passwordForm.value = { current_password: '', new_password: '', confirm_password: '' }
    } catch (error) {
        alert(error.response?.data?.message || 'Failed to change password')
    } finally {
        changingPassword.value = false
    }
}

const updatePhone = async () => {
    if (phoneStep.value === 1) {
        sendingOtp.value = true
        try {
            await axios.post('/api/customer/phone/send-otp', {
                phone: phoneForm.value.phone
            }, { headers: getAuthHeaders() })
            phoneStep.value = 2
        } catch (error) {
            alert(error.response?.data?.message || 'Failed to send OTP')
        } finally {
            sendingOtp.value = false
        }
    } else {
        verifyingOtp.value = true
        try {
            await axios.post('/api/customer/phone/verify-otp', phoneForm.value, {
                headers: getAuthHeaders()
            })
            profile.value.phone = phoneForm.value.phone
            showPhoneModal.value = false
            phoneStep.value = 1
            phoneForm.value = { phone: '', otp: '' }
            alert('Phone number updated successfully')
        } catch (error) {
            alert(error.response?.data?.message || 'Invalid OTP')
        } finally {
            verifyingOtp.value = false
        }
    }
}

const updatePreferences = async () => {
    try {
        await axios.put('/api/customer/preferences', preferences.value, {
            headers: getAuthHeaders()
        })
    } catch (error) {
        console.error('Failed to update preferences:', error)
    }
}

const confirmDeleteAccount = () => {
    if (confirm('Are you sure you want to delete your account? This action cannot be undone.')) {
        deleteAccount()
    }
}

const deleteAccount = async () => {
    try {
        await axios.delete('/api/customer/account', {
            headers: getAuthHeaders()
        })
        localStorage.removeItem('token')
        localStorage.removeItem('customer')
        window.location.href = '/'
    } catch (error) {
        alert(error.response?.data?.message || 'Failed to delete account')
    }
}

const getInitials = () => {
    const first = profile.value.first_name?.[0] || ''
    const last = profile.value.last_name?.[0] || ''
    return (first + last).toUpperCase() || 'U'
}

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('en-IN', {
        day: 'numeric',
        month: 'long',
        year: 'numeric'
    })
}

const capitalize = (str) => {
    return str?.charAt(0).toUpperCase() + str?.slice(1)
}

onMounted(() => {
    fetchProfile()
})
</script>

<style scoped>
.profile-page {
    min-height: 100vh;
    background: #f8f9fa;
    padding: 30px 0;
}

.container {
    max-width: 1000px;
    margin: 0 auto;
    padding: 0 20px;
}

.page-header {
    margin-bottom: 30px;
}

.page-header h1 {
    font-size: 28px;
    color: #1a1a2e;
    margin-bottom: 8px;
}

.page-header p {
    color: #666;
}

.profile-grid {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 25px;
}

.profile-card {
    background: #fff;
    border-radius: 12px;
    padding: 25px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}

.card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 25px;
}

.card-header h2 {
    font-size: 18px;
    color: #1a1a2e;
}

.btn-edit {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 8px 16px;
    background: #f0f0f0;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    color: #333;
    font-weight: 500;
}

.btn-edit:hover {
    background: #e0e0e0;
}

.profile-avatar {
    display: flex;
    flex-direction: column;
    align-items: center;
    margin-bottom: 25px;
}

.avatar {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    background: linear-gradient(135deg, #b8860b 0%, #daa520 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 36px;
    font-weight: 600;
    color: #fff;
    overflow: hidden;
}

.avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.change-avatar {
    margin-top: 12px;
    padding: 8px 16px;
    background: transparent;
    border: 1px solid #b8860b;
    border-radius: 6px;
    color: #b8860b;
    cursor: pointer;
    font-size: 13px;
    display: flex;
    align-items: center;
    gap: 6px;
}

.profile-form {
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
    font-size: 14px;
}

.form-group input,
.form-group select {
    padding: 12px 15px;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    font-size: 15px;
}

.form-group input:focus,
.form-group select:focus {
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
    padding: 12px;
    background: #f5f5f5;
    border-right: 2px solid #e0e0e0;
    font-weight: 500;
}

.phone-input input {
    border: none;
    flex: 1;
}

.form-actions {
    display: flex;
    gap: 15px;
    justify-content: flex-end;
    padding-top: 15px;
    border-top: 1px solid #eee;
}

.btn-primary {
    padding: 12px 24px;
    background: linear-gradient(135deg, #b8860b 0%, #daa520 100%);
    color: #fff;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
}

.btn-primary:disabled {
    opacity: 0.7;
    cursor: not-allowed;
}

.btn-secondary {
    padding: 12px 24px;
    background: #f5f5f5;
    color: #333;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
}

.profile-info {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.info-row {
    display: flex;
    justify-content: space-between;
    padding: 12px 0;
    border-bottom: 1px solid #f0f0f0;
}

.info-row:last-child {
    border-bottom: none;
}

.info-row .label {
    color: #666;
    font-size: 14px;
}

.info-row .value {
    color: #333;
    font-weight: 500;
}

.side-cards {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.side-cards h3 {
    font-size: 16px;
    color: #1a1a2e;
    margin-bottom: 15px;
}

.security-options {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.security-btn {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 15px;
    background: #f8f9fa;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    cursor: pointer;
    text-align: left;
    transition: all 0.3s;
}

.security-btn:hover {
    border-color: #b8860b;
    background: #fff;
}

.security-btn > i:first-child {
    font-size: 20px;
    color: #b8860b;
}

.security-btn > i:last-child {
    margin-left: auto;
    color: #999;
}

.security-btn div {
    display: flex;
    flex-direction: column;
}

.security-btn span {
    font-weight: 500;
    color: #333;
}

.security-btn small {
    font-size: 12px;
    color: #666;
}

.preference-options {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.preference-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    cursor: pointer;
}

.preference-item div {
    display: flex;
    flex-direction: column;
}

.preference-item span {
    font-weight: 500;
    color: #333;
    font-size: 14px;
}

.preference-item small {
    font-size: 12px;
    color: #666;
}

.preference-item input[type="checkbox"] {
    width: 20px;
    height: 20px;
    accent-color: #b8860b;
}

.danger-zone {
    border: 1px solid #f8d7da;
}

.btn-danger-outline {
    width: 100%;
    padding: 12px;
    background: transparent;
    border: 1px solid #dc3545;
    color: #dc3545;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 500;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.btn-danger-outline:hover {
    background: #dc3545;
    color: #fff;
}

.warning-text {
    font-size: 12px;
    color: #999;
    text-align: center;
    margin-top: 10px;
}

.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
}

.modal-content {
    background: #fff;
    border-radius: 12px;
    width: 100%;
    max-width: 420px;
    margin: 20px;
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px 25px;
    border-bottom: 1px solid #eee;
}

.modal-header h3 {
    font-size: 18px;
    color: #1a1a2e;
}

.close-btn {
    background: none;
    border: none;
    font-size: 24px;
    color: #999;
    cursor: pointer;
}

.modal-form {
    padding: 25px;
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.modal-actions {
    display: flex;
    gap: 15px;
    justify-content: flex-end;
}

@media (max-width: 768px) {
    .profile-grid {
        grid-template-columns: 1fr;
    }

    .form-row {
        grid-template-columns: 1fr;
    }
}
</style>
