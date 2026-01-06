<template>
    <div class="profile-page">
        <div class="container">
            <div class="page-header">
                <h1>Business Profile</h1>
                <p>Manage your business details and settings</p>
            </div>

            <div class="profile-grid">
                <!-- Business Info Card -->
                <div class="profile-card main-card">
                    <div class="card-header">
                        <h2>Business Information</h2>
                        <button v-if="!editing" class="btn-edit" @click="startEditing">
                            <i class="bi-pencil"></i> Edit
                        </button>
                    </div>

                    <form v-if="editing" @submit.prevent="saveProfile" class="profile-form">
                        <div class="form-group">
                            <label>Business Name *</label>
                            <input type="text" v-model="form.business_name" required />
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Contact Person *</label>
                                <input type="text" v-model="form.contact_person" required />
                            </div>
                            <div class="form-group">
                                <label>Designation</label>
                                <input type="text" v-model="form.designation" />
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Email *</label>
                                <input type="email" v-model="form.email" required />
                            </div>
                            <div class="form-group">
                                <label>Phone *</label>
                                <input type="tel" v-model="form.phone" required />
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Business Address *</label>
                            <textarea v-model="form.address" rows="3" required></textarea>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>City *</label>
                                <input type="text" v-model="form.city" required />
                            </div>
                            <div class="form-group">
                                <label>State *</label>
                                <select v-model="form.state" required>
                                    <option value="">Select State</option>
                                    <option v-for="state in states" :key="state" :value="state">{{ state }}</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Pincode *</label>
                                <input type="text" v-model="form.pincode" maxlength="6" required />
                            </div>
                        </div>
                        <div class="form-group">
                            <label>GST Number</label>
                            <input type="text" v-model="form.gst_number" placeholder="22AAAAA0000A1Z5" />
                        </div>
                        <div class="form-group">
                            <label>PAN Number</label>
                            <input type="text" v-model="form.pan_number" placeholder="AAAAA0000A" />
                        </div>
                        <div class="form-group">
                            <label>Website</label>
                            <input type="url" v-model="form.website" placeholder="https://www.example.com" />
                        </div>

                        <div class="form-actions">
                            <button type="button" class="btn-secondary" @click="cancelEditing">Cancel</button>
                            <button type="submit" class="btn-primary" :disabled="saving">
                                {{ saving ? 'Saving...' : 'Save Changes' }}
                            </button>
                        </div>
                    </form>

                    <div v-else class="profile-info">
                        <div class="business-header">
                            <div class="business-logo">
                                <i class="bi-building"></i>
                            </div>
                            <div class="business-title">
                                <h3>{{ profile.business_name }}</h3>
                                <span class="tier-badge" :class="profile.tier?.toLowerCase()">
                                    {{ profile.tier || 'Standard' }} Partner
                                </span>
                            </div>
                        </div>

                        <div class="info-grid">
                            <div class="info-item">
                                <span class="info-label">Contact Person</span>
                                <span class="info-value">{{ profile.contact_person }}</span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Designation</span>
                                <span class="info-value">{{ profile.designation || '-' }}</span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Email</span>
                                <span class="info-value">{{ profile.email }}</span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Phone</span>
                                <span class="info-value">{{ profile.phone }}</span>
                            </div>
                            <div class="info-item full-width">
                                <span class="info-label">Address</span>
                                <span class="info-value">
                                    {{ profile.address }}<br>
                                    {{ profile.city }}, {{ profile.state }} - {{ profile.pincode }}
                                </span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">GST Number</span>
                                <span class="info-value">{{ profile.gst_number || '-' }}</span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">PAN Number</span>
                                <span class="info-value">{{ profile.pan_number || '-' }}</span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Website</span>
                                <span class="info-value">
                                    <a v-if="profile.website" :href="profile.website" target="_blank">{{ profile.website }}</a>
                                    <span v-else>-</span>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Side Cards -->
                <div class="side-column">
                    <!-- Account Status -->
                    <div class="profile-card">
                        <h3>Account Status</h3>
                        <div class="status-info">
                            <div class="status-item">
                                <span class="status-label">Status</span>
                                <span :class="['status-badge', profile.status]">{{ formatStatus(profile.status) }}</span>
                            </div>
                            <div class="status-item">
                                <span class="status-label">Tier</span>
                                <span class="status-value">{{ profile.tier || 'Standard' }}</span>
                            </div>
                            <div class="status-item">
                                <span class="status-label">Member Since</span>
                                <span class="status-value">{{ formatDate(profile.created_at) }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Security Settings -->
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
                        </div>
                    </div>

                    <!-- Documents -->
                    <div class="profile-card">
                        <h3>Documents</h3>
                        <div class="documents-list">
                            <div class="document-item" v-for="doc in documents" :key="doc.type">
                                <i class="bi-file-earmark-pdf"></i>
                                <div class="document-info">
                                    <span>{{ doc.name }}</span>
                                    <small :class="doc.status">{{ doc.status_text }}</small>
                                </div>
                                <button v-if="doc.url" class="btn-icon" @click="viewDocument(doc)">
                                    <i class="bi-eye"></i>
                                </button>
                                <button v-else class="btn-icon upload" @click="uploadDocument(doc.type)">
                                    <i class="bi-upload"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Logout -->
                    <div class="profile-card">
                        <button class="btn-logout" @click="logout">
                            <i class="bi-box-arrow-right"></i> Logout
                        </button>
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
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'

const router = useRouter()

const profile = ref({})
const form = ref({})
const editing = ref(false)
const saving = ref(false)
const showPasswordModal = ref(false)
const changingPassword = ref(false)

const passwordForm = ref({
    current_password: '',
    new_password: '',
    confirm_password: ''
})

const documents = ref([
    { type: 'gst_certificate', name: 'GST Certificate', status: 'pending', status_text: 'Not uploaded', url: null },
    { type: 'pan_card', name: 'PAN Card', status: 'pending', status_text: 'Not uploaded', url: null },
    { type: 'business_license', name: 'Business License', status: 'pending', status_text: 'Not uploaded', url: null }
])

const states = [
    'Andhra Pradesh', 'Arunachal Pradesh', 'Assam', 'Bihar', 'Chhattisgarh',
    'Goa', 'Gujarat', 'Haryana', 'Himachal Pradesh', 'Jharkhand', 'Karnataka',
    'Kerala', 'Madhya Pradesh', 'Maharashtra', 'Manipur', 'Meghalaya', 'Mizoram',
    'Nagaland', 'Odisha', 'Punjab', 'Rajasthan', 'Sikkim', 'Tamil Nadu',
    'Telangana', 'Tripura', 'Uttar Pradesh', 'Uttarakhand', 'West Bengal',
    'Delhi', 'Jammu & Kashmir', 'Ladakh'
]

const getAuthHeaders = () => {
    const token = localStorage.getItem('partner_token')
    return { Authorization: `Bearer ${token}` }
}

const fetchProfile = async () => {
    try {
        const response = await axios.get('/api/partner/profile', {
            headers: getAuthHeaders()
        })
        profile.value = response.data.data || response.data
        form.value = { ...profile.value }

        // Update documents status
        if (profile.value.documents) {
            documents.value.forEach(doc => {
                const uploaded = profile.value.documents.find(d => d.type === doc.type)
                if (uploaded) {
                    doc.status = uploaded.verified ? 'verified' : 'pending'
                    doc.status_text = uploaded.verified ? 'Verified' : 'Pending verification'
                    doc.url = uploaded.url
                }
            })
        }
    } catch (error) {
        console.error('Failed to fetch profile:', error)
    }
}

const startEditing = () => {
    form.value = { ...profile.value }
    editing.value = true
}

const cancelEditing = () => {
    editing.value = false
    form.value = { ...profile.value }
}

const saveProfile = async () => {
    saving.value = true
    try {
        const response = await axios.put('/api/partner/profile', form.value, {
            headers: getAuthHeaders()
        })
        profile.value = response.data.data || response.data
        editing.value = false
        localStorage.setItem('partner', JSON.stringify(profile.value))
    } catch (error) {
        alert(error.response?.data?.message || 'Failed to update profile')
    } finally {
        saving.value = false
    }
}

const changePassword = async () => {
    if (passwordForm.value.new_password !== passwordForm.value.confirm_password) {
        alert('Passwords do not match')
        return
    }

    changingPassword.value = true
    try {
        await axios.post('/api/partner/password/change', passwordForm.value, {
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

const viewDocument = (doc) => {
    window.open(doc.url, '_blank')
}

const uploadDocument = async (type) => {
    // Create file input and trigger upload
    const input = document.createElement('input')
    input.type = 'file'
    input.accept = '.pdf,.jpg,.jpeg,.png'
    input.onchange = async (e) => {
        const file = e.target.files[0]
        if (!file) return

        const formData = new FormData()
        formData.append('document', file)
        formData.append('type', type)

        try {
            await axios.post('/api/partner/documents', formData, {
                headers: {
                    ...getAuthHeaders(),
                    'Content-Type': 'multipart/form-data'
                }
            })
            fetchProfile()
        } catch (error) {
            alert('Failed to upload document')
        }
    }
    input.click()
}

const formatStatus = (status) => {
    const statusMap = {
        active: 'Active',
        pending: 'Pending Approval',
        suspended: 'Suspended'
    }
    return statusMap[status] || status
}

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('en-IN', {
        day: 'numeric',
        month: 'long',
        year: 'numeric'
    })
}

const logout = () => {
    localStorage.removeItem('partner_token')
    localStorage.removeItem('partner')
    router.push('/partner/login')
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
    max-width: 1100px;
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
    padding-bottom: 15px;
    border-bottom: 1px solid #eee;
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

.profile-form {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.form-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
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
.form-group select,
.form-group textarea {
    padding: 12px 15px;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    font-size: 15px;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
    outline: none;
    border-color: #1a1a2e;
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
    background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
    color: #fff;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
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

.business-header {
    display: flex;
    align-items: center;
    gap: 20px;
    margin-bottom: 25px;
}

.business-logo {
    width: 70px;
    height: 70px;
    background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 30px;
    color: #fff;
}

.business-title h3 {
    font-size: 20px;
    color: #1a1a2e;
    margin-bottom: 5px;
}

.tier-badge {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 15px;
    font-size: 12px;
    font-weight: 600;
}

.tier-badge.bronze { background: #ffe0b2; color: #e65100; }
.tier-badge.silver { background: #e0e0e0; color: #424242; }
.tier-badge.gold { background: #fff8e1; color: #ff8f00; }
.tier-badge.platinum { background: #e3f2fd; color: #1565c0; }
.tier-badge.standard { background: #f5f5f5; color: #666; }

.info-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.info-item {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.info-item.full-width {
    grid-column: 1 / -1;
}

.info-label {
    font-size: 12px;
    color: #666;
    text-transform: uppercase;
}

.info-value {
    font-size: 15px;
    color: #333;
}

.info-value a {
    color: #007bff;
    text-decoration: none;
}

.side-column {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.side-column h3 {
    font-size: 16px;
    color: #1a1a2e;
    margin-bottom: 15px;
}

.status-info {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.status-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.status-label {
    color: #666;
    font-size: 14px;
}

.status-value {
    font-weight: 500;
    color: #333;
}

.status-badge {
    padding: 4px 12px;
    border-radius: 15px;
    font-size: 12px;
    font-weight: 500;
}

.status-badge.active { background: #d4edda; color: #155724; }
.status-badge.pending { background: #fff3cd; color: #856404; }
.status-badge.suspended { background: #f8d7da; color: #721c24; }

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
    width: 100%;
}

.security-btn:hover {
    border-color: #1a1a2e;
}

.security-btn > i:first-child {
    font-size: 20px;
    color: #1a1a2e;
}

.security-btn > i:last-child {
    margin-left: auto;
    color: #999;
}

.security-btn div {
    flex: 1;
}

.security-btn span {
    display: block;
    font-weight: 500;
    color: #333;
}

.security-btn small {
    font-size: 12px;
    color: #666;
}

.documents-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.document-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px;
    background: #f8f9fa;
    border-radius: 8px;
}

.document-item > i:first-child {
    font-size: 24px;
    color: #dc3545;
}

.document-info {
    flex: 1;
}

.document-info span {
    display: block;
    font-weight: 500;
    font-size: 14px;
}

.document-info small {
    font-size: 12px;
}

.document-info small.verified { color: #28a745; }
.document-info small.pending { color: #666; }

.btn-icon {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    border: 1px solid #e0e0e0;
    background: #fff;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
}

.btn-icon.upload {
    border-color: #007bff;
    color: #007bff;
}

.btn-logout {
    width: 100%;
    padding: 14px;
    background: transparent;
    border: 2px solid #dc3545;
    color: #dc3545;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.btn-logout:hover {
    background: #dc3545;
    color: #fff;
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

    .info-grid {
        grid-template-columns: 1fr;
    }
}
</style>
