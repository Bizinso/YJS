<template>
    <div class="addresses-page">
        <div class="container">
            <div class="page-header">
                <div>
                    <h1>Address Book</h1>
                    <p>Manage your delivery addresses</p>
                </div>
                <button class="btn-primary" @click="openAddModal">
                    <i class="bi-plus-lg"></i> Add New Address
                </button>
            </div>

            <div v-if="loading" class="loading-state">
                <div class="spinner"></div>
                <p>Loading addresses...</p>
            </div>

            <div v-else-if="addresses.length === 0" class="empty-state">
                <i class="bi-geo-alt"></i>
                <h3>No addresses saved</h3>
                <p>Add your first delivery address</p>
                <button class="btn-primary" @click="openAddModal">Add Address</button>
            </div>

            <div v-else class="addresses-grid">
                <div v-for="address in addresses" :key="address.id" class="address-card" :class="{ default: address.is_default }">
                    <div class="address-header">
                        <span class="address-type" :class="address.type">{{ address.type }}</span>
                        <span v-if="address.is_default" class="default-badge">Default</span>
                    </div>
                    <div class="address-body">
                        <h3>{{ address.name }}</h3>
                        <p>{{ address.address_line_1 }}</p>
                        <p v-if="address.address_line_2">{{ address.address_line_2 }}</p>
                        <p>{{ address.city }}, {{ address.state }} - {{ address.pincode }}</p>
                        <p class="phone">Phone: {{ address.phone }}</p>
                    </div>
                    <div class="address-actions">
                        <button class="action-btn" @click="editAddress(address)">
                            <i class="bi-pencil"></i> Edit
                        </button>
                        <button v-if="!address.is_default" class="action-btn" @click="setDefault(address)">
                            <i class="bi-check-circle"></i> Set Default
                        </button>
                        <button class="action-btn delete" @click="confirmDelete(address)">
                            <i class="bi-trash"></i> Delete
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add/Edit Address Modal -->
        <div v-if="showModal" class="modal-overlay" @click.self="closeModal">
            <div class="modal-content">
                <div class="modal-header">
                    <h3>{{ editingAddress ? 'Edit Address' : 'Add New Address' }}</h3>
                    <button class="close-btn" @click="closeModal">&times;</button>
                </div>
                <form @submit.prevent="saveAddress" class="address-form">
                    <div class="form-group">
                        <label>Full Name *</label>
                        <input type="text" v-model="form.name" required placeholder="Enter full name" />
                    </div>
                    <div class="form-group">
                        <label>Phone Number *</label>
                        <div class="phone-input">
                            <span class="country-code">+91</span>
                            <input type="tel" v-model="form.phone" maxlength="10" required placeholder="10-digit phone" />
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Address Line 1 *</label>
                        <input type="text" v-model="form.address_line_1" required placeholder="House/Flat No., Building Name" />
                    </div>
                    <div class="form-group">
                        <label>Address Line 2</label>
                        <input type="text" v-model="form.address_line_2" placeholder="Street, Area (Optional)" />
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>City *</label>
                            <input type="text" v-model="form.city" required placeholder="City" />
                        </div>
                        <div class="form-group">
                            <label>State *</label>
                            <select v-model="form.state" required>
                                <option value="">Select State</option>
                                <option v-for="state in states" :key="state" :value="state">{{ state }}</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Pincode *</label>
                            <input type="text" v-model="form.pincode" maxlength="6" required placeholder="6-digit pincode" />
                        </div>
                        <div class="form-group">
                            <label>Address Type</label>
                            <div class="type-options">
                                <label class="type-option" :class="{ active: form.type === 'home' }">
                                    <input type="radio" v-model="form.type" value="home" />
                                    <i class="bi-house"></i> Home
                                </label>
                                <label class="type-option" :class="{ active: form.type === 'office' }">
                                    <input type="radio" v-model="form.type" value="office" />
                                    <i class="bi-building"></i> Office
                                </label>
                                <label class="type-option" :class="{ active: form.type === 'other' }">
                                    <input type="radio" v-model="form.type" value="other" />
                                    <i class="bi-geo-alt"></i> Other
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" v-model="form.is_default" />
                            <span>Set as default address</span>
                        </label>
                    </div>
                    <div class="modal-actions">
                        <button type="button" class="btn-secondary" @click="closeModal">Cancel</button>
                        <button type="submit" class="btn-primary" :disabled="saving">
                            {{ saving ? 'Saving...' : 'Save Address' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'

const addresses = ref([])
const loading = ref(true)
const showModal = ref(false)
const editingAddress = ref(null)
const saving = ref(false)

const states = [
    'Andhra Pradesh', 'Arunachal Pradesh', 'Assam', 'Bihar', 'Chhattisgarh',
    'Goa', 'Gujarat', 'Haryana', 'Himachal Pradesh', 'Jharkhand', 'Karnataka',
    'Kerala', 'Madhya Pradesh', 'Maharashtra', 'Manipur', 'Meghalaya', 'Mizoram',
    'Nagaland', 'Odisha', 'Punjab', 'Rajasthan', 'Sikkim', 'Tamil Nadu',
    'Telangana', 'Tripura', 'Uttar Pradesh', 'Uttarakhand', 'West Bengal',
    'Delhi', 'Jammu & Kashmir', 'Ladakh'
]

const defaultForm = {
    name: '',
    phone: '',
    address_line_1: '',
    address_line_2: '',
    city: '',
    state: '',
    pincode: '',
    type: 'home',
    is_default: false
}

const form = ref({ ...defaultForm })

const getAuthHeaders = () => {
    const token = localStorage.getItem('token')
    return { Authorization: `Bearer ${token}` }
}

const fetchAddresses = async () => {
    loading.value = true
    try {
        const response = await axios.get('/api/customer/addresses', {
            headers: getAuthHeaders()
        })
        addresses.value = response.data.data || response.data || []
    } catch (error) {
        console.error('Failed to fetch addresses:', error)
    } finally {
        loading.value = false
    }
}

const openAddModal = () => {
    editingAddress.value = null
    form.value = { ...defaultForm }
    showModal.value = true
}

const editAddress = (address) => {
    editingAddress.value = address
    form.value = { ...address }
    showModal.value = true
}

const closeModal = () => {
    showModal.value = false
    editingAddress.value = null
    form.value = { ...defaultForm }
}

const saveAddress = async () => {
    saving.value = true
    try {
        if (editingAddress.value) {
            const response = await axios.put(`/api/customer/addresses/${editingAddress.value.id}`, form.value, {
                headers: getAuthHeaders()
            })
            const index = addresses.value.findIndex(a => a.id === editingAddress.value.id)
            if (index !== -1) {
                addresses.value[index] = response.data.data || response.data
            }
        } else {
            const response = await axios.post('/api/customer/addresses', form.value, {
                headers: getAuthHeaders()
            })
            addresses.value.push(response.data.data || response.data)
        }

        if (form.value.is_default) {
            addresses.value.forEach(a => {
                if (a.id !== (editingAddress.value?.id || addresses.value[addresses.value.length - 1]?.id)) {
                    a.is_default = false
                }
            })
        }

        closeModal()
    } catch (error) {
        alert(error.response?.data?.message || 'Failed to save address')
    } finally {
        saving.value = false
    }
}

const setDefault = async (address) => {
    try {
        await axios.put(`/api/customer/addresses/${address.id}/default`, {}, {
            headers: getAuthHeaders()
        })
        addresses.value.forEach(a => {
            a.is_default = a.id === address.id
        })
    } catch (error) {
        alert('Failed to set default address')
    }
}

const confirmDelete = (address) => {
    if (confirm('Are you sure you want to delete this address?')) {
        deleteAddress(address)
    }
}

const deleteAddress = async (address) => {
    try {
        await axios.delete(`/api/customer/addresses/${address.id}`, {
            headers: getAuthHeaders()
        })
        addresses.value = addresses.value.filter(a => a.id !== address.id)
    } catch (error) {
        alert('Failed to delete address')
    }
}

onMounted(() => {
    fetchAddresses()
})
</script>

<style scoped>
.addresses-page {
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
    display: flex;
    justify-content: space-between;
    align-items: center;
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

.btn-primary {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 12px 24px;
    background: linear-gradient(135deg, #b8860b 0%, #daa520 100%);
    color: #fff;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
}

.loading-state {
    text-align: center;
    padding: 60px 20px;
}

.spinner {
    width: 40px;
    height: 40px;
    border: 3px solid #e0e0e0;
    border-top-color: #b8860b;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
    margin: 0 auto 15px;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

.empty-state {
    text-align: center;
    padding: 60px 20px;
    background: #fff;
    border-radius: 12px;
}

.empty-state i {
    font-size: 64px;
    color: #ddd;
    margin-bottom: 20px;
}

.empty-state h3 {
    color: #333;
    margin-bottom: 10px;
}

.empty-state p {
    color: #666;
    margin-bottom: 20px;
}

.addresses-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 20px;
}

.address-card {
    background: #fff;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    border: 2px solid transparent;
    transition: all 0.3s;
}

.address-card.default {
    border-color: #b8860b;
}

.address-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}

.address-type {
    padding: 4px 12px;
    border-radius: 15px;
    font-size: 12px;
    font-weight: 500;
    text-transform: capitalize;
}

.address-type.home {
    background: #e3f2fd;
    color: #1565c0;
}

.address-type.office {
    background: #e8f5e9;
    color: #2e7d32;
}

.address-type.other {
    background: #f3e5f5;
    color: #7b1fa2;
}

.default-badge {
    padding: 4px 10px;
    background: linear-gradient(135deg, #b8860b 0%, #daa520 100%);
    color: #fff;
    border-radius: 15px;
    font-size: 11px;
    font-weight: 600;
}

.address-body {
    margin-bottom: 15px;
}

.address-body h3 {
    font-size: 16px;
    color: #1a1a2e;
    margin-bottom: 10px;
}

.address-body p {
    color: #666;
    line-height: 1.6;
    font-size: 14px;
}

.address-body .phone {
    margin-top: 10px;
    font-weight: 500;
    color: #333;
}

.address-actions {
    display: flex;
    gap: 10px;
    padding-top: 15px;
    border-top: 1px solid #eee;
}

.action-btn {
    display: flex;
    align-items: center;
    gap: 5px;
    padding: 8px 12px;
    background: #f8f9fa;
    border: 1px solid #e0e0e0;
    border-radius: 6px;
    cursor: pointer;
    font-size: 13px;
    color: #333;
    transition: all 0.3s;
}

.action-btn:hover {
    background: #e9ecef;
}

.action-btn.delete {
    color: #dc3545;
}

.action-btn.delete:hover {
    background: #f8d7da;
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
    padding: 20px;
}

.modal-content {
    background: #fff;
    border-radius: 12px;
    width: 100%;
    max-width: 500px;
    max-height: 90vh;
    overflow-y: auto;
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px 25px;
    border-bottom: 1px solid #eee;
    position: sticky;
    top: 0;
    background: #fff;
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

.address-form {
    padding: 25px;
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

.type-options {
    display: flex;
    gap: 10px;
}

.type-option {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 10px;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    cursor: pointer;
    font-size: 13px;
    transition: all 0.3s;
}

.type-option input {
    display: none;
}

.type-option.active {
    border-color: #b8860b;
    background: rgba(184, 134, 11, 0.1);
    color: #b8860b;
}

.checkbox-label {
    display: flex;
    align-items: center;
    gap: 10px;
    cursor: pointer;
}

.checkbox-label input {
    width: 18px;
    height: 18px;
    accent-color: #b8860b;
}

.modal-actions {
    display: flex;
    gap: 15px;
    justify-content: flex-end;
    padding-top: 15px;
    border-top: 1px solid #eee;
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

.btn-primary:disabled {
    opacity: 0.7;
    cursor: not-allowed;
}

@media (max-width: 768px) {
    .page-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 15px;
    }

    .form-row {
        grid-template-columns: 1fr;
    }

    .type-options {
        flex-wrap: wrap;
    }
}
</style>
