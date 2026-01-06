<template>
  <div class="user-management">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h4 class="mb-1">User Management</h4>
        <p class="text-muted mb-0">Manage admin users, roles, and permissions</p>
      </div>
      <div class="d-flex gap-2">
        <b-button variant="outline-primary" @click="showActivityLog = true">
          <i class="bi bi-clock-history me-1"></i> Activity Log
        </b-button>
        <b-button variant="primary" @click="showCreateUserModal = true">
          <i class="bi bi-plus me-1"></i> Add User
        </b-button>
      </div>
    </div>

    <!-- Tabs -->
    <b-card>
      <b-tabs v-model="activeTab">
        <!-- Users Tab -->
        <b-tab title="Users">
          <div class="mt-3">
            <!-- Filters -->
            <div class="row mb-3">
              <div class="col-md-4">
                <b-form-input v-model="userFilters.search" placeholder="Search users..." @input="debounceSearch" />
              </div>
              <div class="col-md-2">
                <b-form-select v-model="userFilters.role" :options="roleFilterOptions" @change="loadUsers" />
              </div>
              <div class="col-md-2">
                <b-form-select v-model="userFilters.status" :options="statusFilterOptions" @change="loadUsers" />
              </div>
            </div>

            <!-- Users Table -->
            <b-table
              :items="users"
              :fields="userFields"
              :busy="loadingUsers"
              responsive
              hover
              show-empty
            >
              <template #cell(name)="data">
                <div class="d-flex align-items-center">
                  <div class="avatar me-2" :style="{ backgroundColor: getAvatarColor(data.item.name) }">
                    {{ getInitials(data.item.name) }}
                  </div>
                  <div>
                    <p class="mb-0 fw-bold">{{ data.value }}</p>
                    <small class="text-muted">{{ data.item.email }}</small>
                  </div>
                </div>
              </template>
              <template #cell(roles)="data">
                <b-badge
                  v-for="role in data.value"
                  :key="role.id"
                  variant="primary"
                  class="me-1"
                >
                  {{ role.name }}
                </b-badge>
              </template>
              <template #cell(status)="data">
                <b-badge :variant="data.value === 'active' ? 'success' : 'danger'">
                  {{ data.value }}
                </b-badge>
              </template>
              <template #cell(last_login)="data">
                {{ data.value ? formatDateTime(data.value) : 'Never' }}
              </template>
              <template #cell(actions)="data">
                <b-dropdown variant="link" no-caret>
                  <template #button-content>
                    <i class="bi bi-three-dots-vertical"></i>
                  </template>
                  <b-dropdown-item @click="editUser(data.item)">
                    <i class="bi bi-pencil me-2"></i> Edit
                  </b-dropdown-item>
                  <b-dropdown-item @click="viewUserActivity(data.item)">
                    <i class="bi bi-clock-history me-2"></i> Activity
                  </b-dropdown-item>
                  <b-dropdown-item @click="resetPassword(data.item)">
                    <i class="bi bi-key me-2"></i> Reset Password
                  </b-dropdown-item>
                  <b-dropdown-divider />
                  <b-dropdown-item
                    v-if="data.item.status === 'active'"
                    @click="deactivateUser(data.item)"
                    class="text-warning"
                  >
                    <i class="bi bi-pause-circle me-2"></i> Deactivate
                  </b-dropdown-item>
                  <b-dropdown-item
                    v-else
                    @click="activateUser(data.item)"
                    class="text-success"
                  >
                    <i class="bi bi-play-circle me-2"></i> Activate
                  </b-dropdown-item>
                  <b-dropdown-item @click="deleteUser(data.item)" class="text-danger">
                    <i class="bi bi-trash me-2"></i> Delete
                  </b-dropdown-item>
                </b-dropdown>
              </template>
            </b-table>

            <b-pagination
              v-model="usersPagination.currentPage"
              :total-rows="usersPagination.total"
              :per-page="usersPagination.perPage"
              @change="loadUsers"
            />
          </div>
        </b-tab>

        <!-- Roles Tab -->
        <b-tab title="Roles">
          <div class="mt-3">
            <div class="d-flex justify-content-end mb-3">
              <b-button variant="primary" @click="showCreateRoleModal = true">
                <i class="bi bi-plus me-1"></i> Add Role
              </b-button>
            </div>

            <div class="row">
              <div class="col-md-4" v-for="role in roles" :key="role.id">
                <b-card class="role-card mb-3">
                  <div class="d-flex justify-content-between align-items-start">
                    <div>
                      <h5 class="mb-1">{{ role.name }}</h5>
                      <p class="text-muted small mb-2">{{ role.description }}</p>
                      <p class="mb-0">
                        <span class="text-muted">{{ role.users_count }} users</span>
                        <span class="text-muted ms-3">{{ role.permissions_count }} permissions</span>
                      </p>
                    </div>
                    <b-dropdown variant="link" no-caret>
                      <template #button-content>
                        <i class="bi bi-three-dots-vertical"></i>
                      </template>
                      <b-dropdown-item @click="editRole(role)">
                        <i class="bi bi-pencil me-2"></i> Edit
                      </b-dropdown-item>
                      <b-dropdown-item @click="viewRolePermissions(role)">
                        <i class="bi bi-shield me-2"></i> Permissions
                      </b-dropdown-item>
                      <b-dropdown-item @click="duplicateRole(role)">
                        <i class="bi bi-files me-2"></i> Duplicate
                      </b-dropdown-item>
                      <b-dropdown-divider />
                      <b-dropdown-item
                        v-if="!role.is_system"
                        @click="deleteRole(role)"
                        class="text-danger"
                      >
                        <i class="bi bi-trash me-2"></i> Delete
                      </b-dropdown-item>
                    </b-dropdown>
                  </div>
                </b-card>
              </div>
            </div>
          </div>
        </b-tab>

        <!-- Permissions Tab -->
        <b-tab title="Permissions">
          <div class="mt-3">
            <div class="row mb-3">
              <div class="col-md-4">
                <b-form-input v-model="permissionSearch" placeholder="Search permissions..." />
              </div>
            </div>

            <div v-for="(permissions, module) in groupedPermissions" :key="module" class="mb-4">
              <h6 class="text-uppercase text-muted mb-3">{{ module }}</h6>
              <div class="row">
                <div class="col-md-3" v-for="permission in permissions" :key="permission.id">
                  <div class="permission-item p-2 border rounded mb-2">
                    <div class="d-flex justify-content-between align-items-center">
                      <div>
                        <p class="mb-0 small fw-bold">{{ permission.name }}</p>
                        <small class="text-muted">{{ permission.description }}</small>
                      </div>
                      <b-badge variant="secondary">{{ permission.roles_count }}</b-badge>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </b-tab>

        <!-- Login History Tab -->
        <b-tab title="Login History">
          <div class="mt-3">
            <div class="row mb-3">
              <div class="col-md-3">
                <b-form-input v-model="loginFilters.search" placeholder="Search user..." @input="debounceLoginSearch" />
              </div>
              <div class="col-md-2">
                <b-form-select v-model="loginFilters.status" :options="loginStatusOptions" @change="loadLoginHistory" />
              </div>
              <div class="col-md-2">
                <b-form-input type="date" v-model="loginFilters.date" @change="loadLoginHistory" />
              </div>
            </div>

            <b-table
              :items="loginHistory"
              :fields="loginFields"
              :busy="loadingLoginHistory"
              responsive
              hover
              show-empty
            >
              <template #cell(user)="data">
                <div class="d-flex align-items-center">
                  <div class="avatar me-2 small" :style="{ backgroundColor: getAvatarColor(data.value.name) }">
                    {{ getInitials(data.value.name) }}
                  </div>
                  <span>{{ data.value.name }}</span>
                </div>
              </template>
              <template #cell(status)="data">
                <b-badge :variant="data.value === 'success' ? 'success' : 'danger'">
                  {{ data.value }}
                </b-badge>
              </template>
              <template #cell(created_at)="data">
                {{ formatDateTime(data.value) }}
              </template>
            </b-table>

            <b-pagination
              v-model="loginPagination.currentPage"
              :total-rows="loginPagination.total"
              :per-page="loginPagination.perPage"
              @change="loadLoginHistory"
            />
          </div>
        </b-tab>
      </b-tabs>
    </b-card>

    <!-- Create/Edit User Modal -->
    <b-modal
      v-model="showCreateUserModal"
      :title="editingUser ? 'Edit User' : 'Create User'"
      size="lg"
      @ok="saveUser"
    >
      <b-form>
        <div class="row">
          <div class="col-md-6">
            <b-form-group label="Name" class="mb-3">
              <b-form-input v-model="userForm.name" required />
            </b-form-group>
          </div>
          <div class="col-md-6">
            <b-form-group label="Email" class="mb-3">
              <b-form-input type="email" v-model="userForm.email" required />
            </b-form-group>
          </div>
        </div>
        <div class="row">
          <div class="col-md-6">
            <b-form-group label="Phone" class="mb-3">
              <b-form-input v-model="userForm.phone" />
            </b-form-group>
          </div>
          <div class="col-md-6">
            <b-form-group label="Department" class="mb-3">
              <b-form-select v-model="userForm.department_id" :options="departmentOptions" />
            </b-form-group>
          </div>
        </div>
        <div class="row" v-if="!editingUser">
          <div class="col-md-6">
            <b-form-group label="Password" class="mb-3">
              <b-form-input type="password" v-model="userForm.password" required />
            </b-form-group>
          </div>
          <div class="col-md-6">
            <b-form-group label="Confirm Password" class="mb-3">
              <b-form-input type="password" v-model="userForm.password_confirmation" required />
            </b-form-group>
          </div>
        </div>
        <b-form-group label="Roles" class="mb-3">
          <div class="d-flex flex-wrap gap-2">
            <b-form-checkbox
              v-for="role in roles"
              :key="role.id"
              v-model="userForm.roles"
              :value="role.id"
            >
              {{ role.name }}
            </b-form-checkbox>
          </div>
        </b-form-group>
        <b-form-checkbox v-model="userForm.send_invitation" class="mb-3">
          Send invitation email
        </b-form-checkbox>
      </b-form>
    </b-modal>

    <!-- Create/Edit Role Modal -->
    <b-modal
      v-model="showCreateRoleModal"
      :title="editingRole ? 'Edit Role' : 'Create Role'"
      size="lg"
      @ok="saveRole"
    >
      <b-form>
        <b-form-group label="Role Name" class="mb-3">
          <b-form-input v-model="roleForm.name" required />
        </b-form-group>
        <b-form-group label="Description" class="mb-3">
          <b-form-textarea v-model="roleForm.description" rows="2" />
        </b-form-group>
        <b-form-group label="Permissions" class="mb-3">
          <div v-for="(permissions, module) in groupedPermissions" :key="module" class="mb-3">
            <div class="d-flex align-items-center mb-2">
              <b-form-checkbox
                :checked="isModuleFullySelected(module)"
                :indeterminate="isModulePartiallySelected(module)"
                @change="toggleModulePermissions(module, $event)"
              >
                <strong>{{ module }}</strong>
              </b-form-checkbox>
            </div>
            <div class="ms-4 row">
              <div class="col-md-4" v-for="permission in permissions" :key="permission.id">
                <b-form-checkbox
                  v-model="roleForm.permissions"
                  :value="permission.id"
                >
                  {{ permission.name }}
                </b-form-checkbox>
              </div>
            </div>
          </div>
        </b-form-group>
      </b-form>
    </b-modal>

    <!-- Reset Password Modal -->
    <b-modal v-model="showResetPasswordModal" title="Reset Password" @ok="confirmResetPassword">
      <b-form>
        <b-form-group label="New Password" class="mb-3">
          <b-form-input type="password" v-model="resetPasswordForm.password" required />
        </b-form-group>
        <b-form-group label="Confirm Password" class="mb-3">
          <b-form-input type="password" v-model="resetPasswordForm.password_confirmation" required />
        </b-form-group>
        <b-form-checkbox v-model="resetPasswordForm.force_change">
          Force password change on next login
        </b-form-checkbox>
        <b-form-checkbox v-model="resetPasswordForm.send_email" class="mt-2">
          Send password reset email
        </b-form-checkbox>
      </b-form>
    </b-modal>

    <!-- Activity Log Modal -->
    <b-modal v-model="showActivityLog" title="User Activity Log" size="xl" hide-footer>
      <div class="row mb-3">
        <div class="col-md-4">
          <b-form-input v-model="activityFilters.search" placeholder="Search activity..." />
        </div>
        <div class="col-md-3">
          <b-form-select v-model="activityFilters.action" :options="actionOptions" />
        </div>
        <div class="col-md-2">
          <b-form-input type="date" v-model="activityFilters.date" />
        </div>
        <div class="col-md-2">
          <b-button variant="primary" @click="loadActivityLog">Filter</b-button>
        </div>
      </div>

      <b-table
        :items="activityLog"
        :fields="activityFields"
        :busy="loadingActivity"
        responsive
        hover
        show-empty
      >
        <template #cell(user)="data">
          <div class="d-flex align-items-center">
            <div class="avatar me-2 small" :style="{ backgroundColor: getAvatarColor(data.value.name) }">
              {{ getInitials(data.value.name) }}
            </div>
            <span>{{ data.value.name }}</span>
          </div>
        </template>
        <template #cell(action)="data">
          <b-badge :variant="getActionVariant(data.value)">{{ data.value }}</b-badge>
        </template>
        <template #cell(created_at)="data">
          {{ formatDateTime(data.value) }}
        </template>
        <template #cell(details)="data">
          <b-button size="sm" variant="link" @click="viewActivityDetails(data.item)">
            View Details
          </b-button>
        </template>
      </b-table>

      <b-pagination
        v-model="activityPagination.currentPage"
        :total-rows="activityPagination.total"
        :per-page="activityPagination.perPage"
        @change="loadActivityLog"
      />
    </b-modal>

    <!-- User Activity Modal -->
    <b-modal v-model="showUserActivityModal" title="User Activity" size="lg" hide-footer>
      <div v-if="selectedUserActivity">
        <div class="d-flex align-items-center mb-4">
          <div class="avatar me-3" :style="{ backgroundColor: getAvatarColor(selectedUserActivity.name) }">
            {{ getInitials(selectedUserActivity.name) }}
          </div>
          <div>
            <h5 class="mb-0">{{ selectedUserActivity.name }}</h5>
            <p class="text-muted mb-0">{{ selectedUserActivity.email }}</p>
          </div>
        </div>

        <div class="row mb-4">
          <div class="col-md-4">
            <div class="p-3 bg-light rounded text-center">
              <h4 class="mb-1">{{ selectedUserActivity.total_logins }}</h4>
              <p class="text-muted small mb-0">Total Logins</p>
            </div>
          </div>
          <div class="col-md-4">
            <div class="p-3 bg-light rounded text-center">
              <h4 class="mb-1">{{ selectedUserActivity.actions_today }}</h4>
              <p class="text-muted small mb-0">Actions Today</p>
            </div>
          </div>
          <div class="col-md-4">
            <div class="p-3 bg-light rounded text-center">
              <h4 class="mb-1">{{ formatDateTime(selectedUserActivity.last_login) }}</h4>
              <p class="text-muted small mb-0">Last Login</p>
            </div>
          </div>
        </div>

        <h6 class="mb-3">Recent Activity</h6>
        <div class="activity-timeline">
          <div
            v-for="activity in selectedUserActivity.recent_activities"
            :key="activity.id"
            class="activity-item d-flex mb-3"
          >
            <div class="activity-icon me-3">
              <i :class="getActivityIcon(activity.action)" class="text-primary"></i>
            </div>
            <div>
              <p class="mb-0">{{ activity.description }}</p>
              <small class="text-muted">{{ formatDateTime(activity.created_at) }}</small>
            </div>
          </div>
        </div>
      </div>
    </b-modal>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import axios from 'axios'
import { debounce } from 'lodash'

// State
const activeTab = ref(0)
const loadingUsers = ref(false)
const loadingLoginHistory = ref(false)
const loadingActivity = ref(false)

const users = ref([])
const roles = ref([])
const permissions = ref([])
const loginHistory = ref([])
const activityLog = ref([])

const editingUser = ref(null)
const editingRole = ref(null)
const selectedUserActivity = ref(null)
const permissionSearch = ref('')

// Filters
const userFilters = reactive({
  search: '',
  role: '',
  status: ''
})

const loginFilters = reactive({
  search: '',
  status: '',
  date: ''
})

const activityFilters = reactive({
  search: '',
  action: '',
  date: ''
})

// Pagination
const usersPagination = reactive({
  currentPage: 1,
  total: 0,
  perPage: 20
})

const loginPagination = reactive({
  currentPage: 1,
  total: 0,
  perPage: 20
})

const activityPagination = reactive({
  currentPage: 1,
  total: 0,
  perPage: 20
})

// Forms
const userForm = reactive({
  name: '',
  email: '',
  phone: '',
  department_id: null,
  password: '',
  password_confirmation: '',
  roles: [],
  send_invitation: true
})

const roleForm = reactive({
  name: '',
  description: '',
  permissions: []
})

const resetPasswordForm = reactive({
  password: '',
  password_confirmation: '',
  force_change: true,
  send_email: true
})

// Modals
const showCreateUserModal = ref(false)
const showCreateRoleModal = ref(false)
const showResetPasswordModal = ref(false)
const showActivityLog = ref(false)
const showUserActivityModal = ref(false)

// Options
const roleFilterOptions = computed(() => [
  { value: '', text: 'All Roles' },
  ...roles.value.map(r => ({ value: r.id, text: r.name }))
])

const statusFilterOptions = [
  { value: '', text: 'All Statuses' },
  { value: 'active', text: 'Active' },
  { value: 'inactive', text: 'Inactive' }
]

const loginStatusOptions = [
  { value: '', text: 'All Statuses' },
  { value: 'success', text: 'Success' },
  { value: 'failed', text: 'Failed' }
]

const actionOptions = [
  { value: '', text: 'All Actions' },
  { value: 'create', text: 'Create' },
  { value: 'update', text: 'Update' },
  { value: 'delete', text: 'Delete' },
  { value: 'login', text: 'Login' },
  { value: 'logout', text: 'Logout' }
]

const departmentOptions = ref([])

// Computed
const groupedPermissions = computed(() => {
  const grouped = {}
  const filtered = permissions.value.filter(p =>
    !permissionSearch.value ||
    p.name.toLowerCase().includes(permissionSearch.value.toLowerCase()) ||
    p.module.toLowerCase().includes(permissionSearch.value.toLowerCase())
  )
  filtered.forEach(permission => {
    if (!grouped[permission.module]) {
      grouped[permission.module] = []
    }
    grouped[permission.module].push(permission)
  })
  return grouped
})

// Table Fields
const userFields = [
  { key: 'name', label: 'User' },
  { key: 'roles', label: 'Roles' },
  { key: 'department', label: 'Department' },
  { key: 'status', label: 'Status' },
  { key: 'last_login', label: 'Last Login' },
  { key: 'actions', label: 'Actions' }
]

const loginFields = [
  { key: 'user', label: 'User' },
  { key: 'ip_address', label: 'IP Address' },
  { key: 'user_agent', label: 'Browser' },
  { key: 'location', label: 'Location' },
  { key: 'status', label: 'Status' },
  { key: 'created_at', label: 'Time' }
]

const activityFields = [
  { key: 'user', label: 'User' },
  { key: 'action', label: 'Action' },
  { key: 'model', label: 'Resource' },
  { key: 'description', label: 'Description' },
  { key: 'created_at', label: 'Time' },
  { key: 'details', label: '' }
]

// Methods
const getInitials = (name) => {
  if (!name) return '?'
  return name.split(' ').map(n => n.charAt(0).toUpperCase()).slice(0, 2).join('')
}

const getAvatarColor = (name) => {
  if (!name) return '#6c757d'
  const colors = ['#007bff', '#6610f2', '#e83e8c', '#fd7e14', '#20c997', '#17a2b8']
  const index = name.charCodeAt(0) % colors.length
  return colors[index]
}

const formatDateTime = (date) => {
  if (!date) return '-'
  return new Date(date).toLocaleString('en-IN')
}

const getActionVariant = (action) => {
  const variants = {
    create: 'success',
    update: 'info',
    delete: 'danger',
    login: 'primary',
    logout: 'secondary'
  }
  return variants[action] || 'secondary'
}

const getActivityIcon = (action) => {
  const icons = {
    create: 'bi bi-plus-circle',
    update: 'bi bi-pencil',
    delete: 'bi bi-trash',
    login: 'bi bi-box-arrow-in-right',
    logout: 'bi bi-box-arrow-right'
  }
  return icons[action] || 'bi bi-circle'
}

const isModuleFullySelected = (module) => {
  const modulePermissions = groupedPermissions.value[module] || []
  return modulePermissions.every(p => roleForm.permissions.includes(p.id))
}

const isModulePartiallySelected = (module) => {
  const modulePermissions = groupedPermissions.value[module] || []
  const selected = modulePermissions.filter(p => roleForm.permissions.includes(p.id))
  return selected.length > 0 && selected.length < modulePermissions.length
}

const toggleModulePermissions = (module, checked) => {
  const modulePermissions = groupedPermissions.value[module] || []
  const permissionIds = modulePermissions.map(p => p.id)

  if (checked) {
    roleForm.permissions = [...new Set([...roleForm.permissions, ...permissionIds])]
  } else {
    roleForm.permissions = roleForm.permissions.filter(id => !permissionIds.includes(id))
  }
}

const loadUsers = async () => {
  loadingUsers.value = true
  try {
    const params = {
      ...userFilters,
      page: usersPagination.currentPage,
      per_page: usersPagination.perPage
    }
    const response = await axios.get('/api/admin/users', { params })
    if (response.data.success) {
      users.value = response.data.data.data
      usersPagination.total = response.data.data.total
    }
  } catch (error) {
    console.error('Failed to load users:', error)
  } finally {
    loadingUsers.value = false
  }
}

const loadRoles = async () => {
  try {
    const response = await axios.get('/api/admin/roles')
    if (response.data.success) {
      roles.value = response.data.data
    }
  } catch (error) {
    console.error('Failed to load roles:', error)
  }
}

const loadPermissions = async () => {
  try {
    const response = await axios.get('/api/admin/permissions')
    if (response.data.success) {
      permissions.value = response.data.data
    }
  } catch (error) {
    console.error('Failed to load permissions:', error)
  }
}

const loadLoginHistory = async () => {
  loadingLoginHistory.value = true
  try {
    const params = {
      ...loginFilters,
      page: loginPagination.currentPage,
      per_page: loginPagination.perPage
    }
    const response = await axios.get('/api/admin/users/login-history', { params })
    if (response.data.success) {
      loginHistory.value = response.data.data.data
      loginPagination.total = response.data.data.total
    }
  } catch (error) {
    console.error('Failed to load login history:', error)
  } finally {
    loadingLoginHistory.value = false
  }
}

const loadActivityLog = async () => {
  loadingActivity.value = true
  try {
    const params = {
      ...activityFilters,
      page: activityPagination.currentPage,
      per_page: activityPagination.perPage
    }
    const response = await axios.get('/api/admin/users/activity-log', { params })
    if (response.data.success) {
      activityLog.value = response.data.data.data
      activityPagination.total = response.data.data.total
    }
  } catch (error) {
    console.error('Failed to load activity log:', error)
  } finally {
    loadingActivity.value = false
  }
}

const loadDepartments = async () => {
  try {
    const response = await axios.get('/api/admin/departments')
    if (response.data.success) {
      departmentOptions.value = [
        { value: null, text: 'Select Department' },
        ...response.data.data.map(d => ({ value: d.id, text: d.name }))
      ]
    }
  } catch (error) {
    console.error('Failed to load departments:', error)
  }
}

const debounceSearch = debounce(() => {
  usersPagination.currentPage = 1
  loadUsers()
}, 300)

const debounceLoginSearch = debounce(() => {
  loginPagination.currentPage = 1
  loadLoginHistory()
}, 300)

const editUser = (user) => {
  editingUser.value = user
  userForm.name = user.name
  userForm.email = user.email
  userForm.phone = user.phone
  userForm.department_id = user.department_id
  userForm.roles = user.roles.map(r => r.id)
  userForm.send_invitation = false
  showCreateUserModal.value = true
}

const resetUserForm = () => {
  editingUser.value = null
  userForm.name = ''
  userForm.email = ''
  userForm.phone = ''
  userForm.department_id = null
  userForm.password = ''
  userForm.password_confirmation = ''
  userForm.roles = []
  userForm.send_invitation = true
}

const saveUser = async () => {
  try {
    const data = { ...userForm }
    let response
    if (editingUser.value) {
      response = await axios.put(`/api/admin/users/${editingUser.value.id}`, data)
    } else {
      response = await axios.post('/api/admin/users', data)
    }
    if (response.data.success) {
      showCreateUserModal.value = false
      resetUserForm()
      loadUsers()
    }
  } catch (error) {
    console.error('Failed to save user:', error)
  }
}

const viewUserActivity = async (user) => {
  try {
    const response = await axios.get(`/api/admin/users/${user.id}/activity`)
    if (response.data.success) {
      selectedUserActivity.value = response.data.data
      showUserActivityModal.value = true
    }
  } catch (error) {
    console.error('Failed to load user activity:', error)
  }
}

const resetPassword = (user) => {
  editingUser.value = user
  resetPasswordForm.password = ''
  resetPasswordForm.password_confirmation = ''
  resetPasswordForm.force_change = true
  resetPasswordForm.send_email = true
  showResetPasswordModal.value = true
}

const confirmResetPassword = async () => {
  try {
    const response = await axios.post(
      `/api/admin/users/${editingUser.value.id}/reset-password`,
      resetPasswordForm
    )
    if (response.data.success) {
      showResetPasswordModal.value = false
      alert('Password reset successfully')
    }
  } catch (error) {
    console.error('Failed to reset password:', error)
  }
}

const activateUser = async (user) => {
  if (!confirm('Are you sure you want to activate this user?')) return
  try {
    const response = await axios.post(`/api/admin/users/${user.id}/activate`)
    if (response.data.success) {
      loadUsers()
    }
  } catch (error) {
    console.error('Failed to activate user:', error)
  }
}

const deactivateUser = async (user) => {
  if (!confirm('Are you sure you want to deactivate this user?')) return
  try {
    const response = await axios.post(`/api/admin/users/${user.id}/deactivate`)
    if (response.data.success) {
      loadUsers()
    }
  } catch (error) {
    console.error('Failed to deactivate user:', error)
  }
}

const deleteUser = async (user) => {
  if (!confirm('Are you sure you want to delete this user? This action cannot be undone.')) return
  try {
    const response = await axios.delete(`/api/admin/users/${user.id}`)
    if (response.data.success) {
      loadUsers()
    }
  } catch (error) {
    console.error('Failed to delete user:', error)
  }
}

const editRole = (role) => {
  editingRole.value = role
  roleForm.name = role.name
  roleForm.description = role.description
  roleForm.permissions = role.permissions.map(p => p.id)
  showCreateRoleModal.value = true
}

const resetRoleForm = () => {
  editingRole.value = null
  roleForm.name = ''
  roleForm.description = ''
  roleForm.permissions = []
}

const saveRole = async () => {
  try {
    let response
    if (editingRole.value) {
      response = await axios.put(`/api/admin/roles/${editingRole.value.id}`, roleForm)
    } else {
      response = await axios.post('/api/admin/roles', roleForm)
    }
    if (response.data.success) {
      showCreateRoleModal.value = false
      resetRoleForm()
      loadRoles()
    }
  } catch (error) {
    console.error('Failed to save role:', error)
  }
}

const viewRolePermissions = (role) => {
  editRole(role)
}

const duplicateRole = async (role) => {
  roleForm.name = `${role.name} (Copy)`
  roleForm.description = role.description
  roleForm.permissions = role.permissions.map(p => p.id)
  editingRole.value = null
  showCreateRoleModal.value = true
}

const deleteRole = async (role) => {
  if (!confirm('Are you sure you want to delete this role? Users with this role will lose these permissions.')) return
  try {
    const response = await axios.delete(`/api/admin/roles/${role.id}`)
    if (response.data.success) {
      loadRoles()
    }
  } catch (error) {
    console.error('Failed to delete role:', error)
  }
}

const viewActivityDetails = (activity) => {
  // Open activity details modal
}

// Lifecycle
onMounted(() => {
  loadUsers()
  loadRoles()
  loadPermissions()
  loadLoginHistory()
  loadDepartments()
})
</script>

<style scoped>
.avatar {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-weight: bold;
}
.avatar.small {
  width: 28px;
  height: 28px;
  font-size: 0.75rem;
}
.role-card {
  transition: transform 0.2s, box-shadow 0.2s;
}
.role-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}
.permission-item {
  transition: background-color 0.2s;
}
.permission-item:hover {
  background-color: #f8f9fa;
}
.activity-icon {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  background-color: #e3f2fd;
  display: flex;
  align-items: center;
  justify-content: center;
}
</style>
