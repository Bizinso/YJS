<template>
  <div>
    <!-- User Selector -->
    <div class="mb-6">
      <b-form-group class="multiDrop">
        <label class="font-semibold mb-2 block">Select Employee:</label>
        <v-select
        v-model="selectedUser"
        :options="users"
        label="label"
        placeholder="Select User"
        @update:modelValue="onUserChange"
        />
      </b-form-group>
    </div>

    <!-- Permissions Table -->
    <div class="maniator">
      <table class="min-w-full border-collapse">
        <thead class="bg-gray-100 border-b ssakso">
          <tr>
            <th class="text-left px-3 py-2  w-25">Modules</th>
            <th v-for="head in tableHeads" :key="head.paramName" class="text-center px-2 py-2">
              {{ head.displayName }}
            </th>
          </tr>
        </thead>

        <tbody>
          <template v-for="menu in permissionsData" :key="menu.id">
            <MenuRow
              :menu="menu"
              :level="0"
              :selectedPermissions="selectedPermissions"
              :togglePermission="togglePermission"
              :tableHeads="tableHeads"
              :is-menu-fully-checked="isMenuFullyChecked"
            />
          </template>
        </tbody>
      </table>
    </div>

     <div class="mt-4 text-right">
      <button class="GlobalfillBTN text-white" @click="submitPermissions">Save Permissions</button>
    </div>
  </div>
</template>

<script setup>
import { ref, defineComponent, onMounted, h } from "vue";
import axiosEmployee from '@axiosEmployee';
import vSelect from "vue-select";
import { toast } from "vue3-toastify";
import "vue3-toastify/dist/index.css";

/* ------------------------
   State
------------------------ */
const selectedUser = ref(null);
const users = ref([]);
const permissionsData = ref([]);
const selectedPermissions = ref([]);

/* ------------------------
   Table Headers - Updated to match API permission slugs
------------------------ */
const tableHeads = ref([
  { displayName: "Access", paramName: "access" },
  { displayName: "View", paramName: "view" },
  { displayName: "List", paramName: "list" },
  { displayName: "Add", paramName: "add" },
  { displayName: "Edit", paramName: "edit" },
  { displayName: "Delete", paramName: "delete" },
  { displayName: "Export", paramName: "export" },
  { displayName: "Import", paramName: "import" },
]);

/* ------------------------
   Helper Functions
------------------------ */
const togglePermission = (permId) => {
  const idx = selectedPermissions.value.indexOf(permId);
  if (idx > -1) {
    selectedPermissions.value.splice(idx, 1);
  } else {
    selectedPermissions.value.push(permId);
  }
};

const collectPermissionIds = (menu) => {
  let ids = [];
  if (menu.permissions) {
    ids = [...menu.permissions.map((p) => p.id)];
  }
  if (menu.children) {
    menu.children.forEach((child) => {
      ids = [...ids, ...collectPermissionIds(child)];
    });
  }
  return ids;
};

const isMenuFullyChecked = (menu) => {
  const allIds = collectPermissionIds(menu);
  return allIds.length > 0 && allIds.every((id) => selectedPermissions.value.includes(id));
};

const getPermissionForColumn = (permissions, columnParam) => {
  if (!permissions) return null;
  return permissions.find(perm => {
    // Extract the action from slug (e.g., "access_dashboard" -> "access")
    const slugParts = perm.slug.split('_');
    const action = slugParts[0];
    return action === columnParam;
  });
};

const hasChildren = (menu) => {
  return menu.children && menu.children.length > 0;
};

/* ------------------------
   API Calls
------------------------ */
const fetchPermissions = async () => {
  try {
    const { data } = await axiosEmployee.get("permissions");
    // Add isOpen property for toggling and ensure children exists
    permissionsData.value = data.menus.map((m) => ({ 
      ...m, 
      isOpen: false,
      children: m.children || []
    }));
    users.value = data.users;
  } catch (error) {
    console.error("Failed to load permissions:", error);
    toast.error("Failed to load permissions");
  }
};

const fetchUserPermissions = async (userId) => {
  try {
    const encodedId = btoa(userId);
    const res = await axiosEmployee.get(`getPermissionFromUserId/${encodedId}`);
    selectedPermissions.value = res.data.responseData || [];
  } catch (error) {
    console.error("Failed to load user permissions:", error);
    toast.error("Failed to load user permissions");
  }
};

const onUserChange = (user) => {
  if (user && user.value) {
    fetchUserPermissions(user.value);
  } else {
    selectedPermissions.value = [];
  }
};

const submitPermissions = async () => {
  if (!selectedUser.value) {
    toast.warning("Please select a user first");
    return;
  }
  try {
    await axiosEmployee.post("permissions", {
      selected_value: selectedPermissions.value,
      user: selectedUser.value.value,
    });
    toast.success("Permissions updated successfully!");
  } catch (error) {
    console.error("Failed to update permissions:", error);
    toast.error("Failed to update permissions");
  }
};

onMounted(fetchPermissions);

/* ------------------------
   Recursive MenuRow Component - Using Render Function
------------------------ */
const MenuRow = defineComponent({
  name: "MenuRow",
  props: {
    menu: Object,
    level: Number,
    selectedPermissions: Array,
    togglePermission: Function,
    tableHeads: Array,
    isMenuFullyChecked: Function
  },
  setup(props) {
    const toggleExpand = () => {
      props.menu.isOpen = !props.menu.isOpen;
    };

    const toggleMenuCheckbox = (e) => {
      const ids = collectPermissionIds(props.menu);
      if (e.target.checked) {
        // Add all missing IDs
        ids.forEach((id) => {
          if (!props.selectedPermissions.includes(id)) {
            props.selectedPermissions.push(id);
          }
        });
      } else {
        // Remove all IDs that are in this menu
        props.selectedPermissions.splice(
          0,
          props.selectedPermissions.length,
          ...props.selectedPermissions.filter((id) => !ids.includes(id))
        );
      }
    };

    const getPermissionForColumn = (permissions, columnParam) => {
      if (!permissions) return null;
      return permissions.find(perm => {
        const slugParts = perm.slug.split('_');
        const action = slugParts[0];
        return action === columnParam;
      });
    };

    const hasChildren = (menu) => {
      return menu.children && menu.children.length > 0;
    };

    return {
      toggleExpand,
      toggleMenuCheckbox,
      getPermissionForColumn,
      hasChildren,
    };
  },
  render() {
    const { menu, level, selectedPermissions, tableHeads, togglePermission, isMenuFullyChecked } = this;
    const { toggleExpand, toggleMenuCheckbox, getPermissionForColumn, hasChildren } = this;

    // Check if this menu has children
    const menuHasChildren = hasChildren(menu);

    // Create the main row
    const row = h('tr', { class: 'border-b hover:bg-gray-50' }, [
      // Menu name cell
      h('td', { class: 'px-3 py-2 w-25' }, [
        h('div', { 
          class: 'd-flex justify-content-between',
          style: { paddingLeft: (level * 20) + 'px' }
        }, [
          // Expand/collapse button (only show if has children)
          
          
          h('div',
            { class: 'menuTicker' },[
          h('input', {
            type: 'checkbox',
            checked: isMenuFullyChecked(menu),
            onChange: toggleMenuCheckbox,
            class: 'mr-2'
          }),
          
          h('span', { class: 'font-medium ' }, menu.name)]),
          ...(menuHasChildren ? [
            h('span', {
              class: 'cursor-pointer text-gray-500 select-none fontBig',
              onClick: toggleExpand
            }, menu.isOpen ? '▾' : '▸')
          ] : [h('span', { class: 'inline-block w-6' })])
        ])
      ]),
      
      // Permission cells for each table head
      ...tableHeads.map(head => {
        // Only show permission checkboxes for menus without children
        if (menuHasChildren) {
          return h('td', { class: 'text-center px-2 py-2' });
        } else {
          const permission = getPermissionForColumn(menu.permissions, head.paramName);
          return h('td', { class: 'text-center px-2 py-2' }, [
            permission ? h('input', {
              type: 'checkbox',
              checked: selectedPermissions.includes(permission.id),
              onChange: () => togglePermission(permission.id),
              class: 'mx-auto'
            }) : null
          ]);
        }
      })
    ]);

    // Create child rows if menu is open and has children
    const childRows = [];
    if (menuHasChildren && menu.isOpen) {
      menu.children.forEach(child => {
        childRows.push(h(MenuRow, {
          key: child.id,
          menu: child,
          level: level + 1,
          selectedPermissions: selectedPermissions,
          togglePermission: togglePermission,
          tableHeads: tableHeads,
          isMenuFullyChecked: isMenuFullyChecked
        }));
      });
    }

    // Return the row and its children
    return [row, ...childRows];
  }
});
</script>

<style scoped>
.v-select {
  width: 300px;
}
table th,
table td {
  border-right: 1px solid #e5e7eb;
}
table th:last-child,
table td:last-child {
  border-right: none;
}
</style>