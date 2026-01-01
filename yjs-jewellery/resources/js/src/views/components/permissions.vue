<template>
  <div class="container">
    <table class="table permissionsFrame formMaster">
      <thead class="thead-dark">
        <tr>
          <th>User Roles</th>
          <th v-for="(columnheading, index) in tableHeads" :key="index" >{{columnheading.displayName}}</th>
        </tr>
      </thead>
      <tbody>
        <template v-for="role in roles" :key="role.id">
          <!-- Role Row -->
          <tr class="table-primary">
            <td @click="toggleCollapse(role)" style="cursor: pointer;">
              <strong class="textile">{{ role.role }} <span>{{ role.users.length }}</span></strong>
              <span>
                <img src="../assets/img/expand_more.svg" v-if="role.collapsed" class="chevronDownIcon" alt="">
                <img src="../assets/img/expand_less.svg" v-else class="chevronDownIcon" alt="">
              </span>
            </td>
            <td v-for="(columnheading, index) in tableHeads" :key="index" >
              <input
                type="checkbox"
                class="form-check-input"
                :checked="isAllChecked(role, columnheading.paramName)"
                @change="toggleRole(role, columnheading.paramName)"
              />
            </td>
          </tr>

          <!-- Users Row -->
          <tr v-if="!role.collapsed" class="innerchild" v-for="user in role.users" :key="user.id">
            <td style="padding-left: 30px;">{{ user.name }}</td>
            <td><input type="checkbox" class="form-check-input" v-model="user.permissions.dashboard" /></td>
            <td><input type="checkbox" class="form-check-input" v-model="user.permissions.products" /></td>
            <td><input type="checkbox" class="form-check-input" v-model="user.permissions.orders" /></td>
            <td><input type="checkbox" class="form-check-input" v-model="user.permissions.pricing" /></td>
            <td><input type="checkbox" class="form-check-input" v-model="user.permissions.customers" /></td>
            <td><input type="checkbox" class="form-check-input" v-model="user.permissions.shipping" /></td>
          </tr>
        </template>
      </tbody>
    </table>
  </div>
</template>

<script>
export default {
  data() {
    return {
        tableHeads:[
            {
                displayName:'Dashboard',
                paramName:'dashboard'
            },
            {
                displayName:'Product Management',
                paramName:'products'
            },
            {
                displayName:'Orders & Invoicing',
                paramName:'orders'
            },
            {
                displayName:'Customers',
                paramName:'customers'
            },
        ],
      roles: [
        {
          id: 1,
          role: "Admin",
          collapsed: true,
          users: [
            {
              id: 11,
              name: "Alice Admin",
              permissions: { dashboard: false, products: false }
            },
            {
              id: 12,
              name: "Bob Admin",
              permissions: { dashboard: false, products: false }
            }
          ]
        },
        {
          id: 2,
          role: "Sales Manager",
          collapsed: true,
          users: Array.from({ length: 10 }, (_, i) => ({
            id: 20 + i + 1,
            name: `Sales Manager ${i + 1}`,
            permissions: { dashboard: false, products: false }
          }))
        },
        {
          id: 3,
          role: "Store Manager",
          collapsed: true,
          users: Array.from({ length: 4 }, (_, i) => ({
            id: 40 + i + 1,
            name: `Store Manager ${i + 1}`,
            permissions: { dashboard: false, products: false }
          }))
        }
      ]
    };
  },
  methods: {
    toggleCollapse(role) {
      role.collapsed = !role.collapsed;
    },
    isAllChecked(role, key) {
      return role.users.every(user => user.permissions[key]);
    },
    toggleRole(role, key) {
      const allChecked = this.isAllChecked(role, key);
      role.users.forEach(user => {
        user.permissions[key] = !allChecked;
      });
    },
    toggleAll(key) {
      const allChecked = this.roles.every(role =>
        role.users.every(user => user.permissions[key])
      );
      this.roles.forEach(role =>
        role.users.forEach(user => {
          user.permissions[key] = !allChecked;
        })
      );
    }
  }
};
</script>

<style scoped>
table th,
table td {
  vertical-align: middle;
}
</style>
