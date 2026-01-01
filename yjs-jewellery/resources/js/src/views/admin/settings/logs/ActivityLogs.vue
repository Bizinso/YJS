<template>
  <div class="listing_screen global_table_liting">
    <div class="listing_tab_and_actions mb-3">
      <div class="listing_actions">
        <div class="d-flex">
          <div class="listing_search">
            <img src="../../../assets/img/header/search.svg" class="listing_search_icon" alt="search" />
            <b-form-input v-model="ActivityFilter.globalSearch" @input="ActivityFilterDataSubmit"
              placeholder="Search Here..." />
          </div>
          <b-button title="filter" class="btn_listing_action" @click="sidebarstatus.filter = !sidebarstatus.filter">
            <img src="../../../assets/img/filter.svg" alt="filter" /> Filter
          </b-button>
        </div>

      </div>


    </div>


    <div class="backdrop_shadow" v-if="sidebarstatus.shadow"></div>
    <div class="filter_sidebar sidebar_main" :class="[sidebarstatus.filter ? 'filter_active' : '']">
      <div class="sidebar_toolbox p-3">
        <h6>Filter</h6>
        <div class="sidebar_toolbox_right_side">
          <CloseIcon @click="ResetFilter" />
        </div>
      </div>
      <div class="sidebar_form">
        <b-form>
          <div class="px-4 py-3 column_sidebar" id="filter_frm_sidebar">
            <label for="ticket-name">User</label>
            <b-form-group label-for="ticket-type">
              <v-select label="label" :reduce="(val) => val.value" value="value" placeholder="Select User" trim
                v-model="ActivityFilter.name" :options="assigneeOptions" />
            </b-form-group>
            <label for="ticket-name">Description</label>
            <b-form-group label-for="ticket-name">
              <b-form-input id="title" v-model="ActivityFilter.description" placeholder="Enter Description" trim />
            </b-form-group>
            <label for="ticket-name">Log Name</label>
            <b-form-group label-for="ticket-type">
              <v-select :reduce="(val) => val.log_name" @open="vSelectOpen" value="log_name" label="log_name"
                placeholder="Select Log Name" trim v-model="ActivityFilter.log_name" :options="logname" />
            </b-form-group>
            <b-form-group>
              <label for="start_date">Start Date</label>
              <VueDatePicker v-model="ActivityFilter.created" :enable-time-picker="false" placeholder="Select Date"
                class="breack" auto-apply></VueDatePicker>
            </b-form-group>
          </div>
          <div class="sidebarbtn_group">
            <b-button type="submit" @click="ActivityFilterDataSubmit" class="btn_primary me-2">Apply</b-button>
            <b-button class="btn_secondary_border" @click="ResetFilter">Reset</b-button>
          </div>
        </b-form>
      </div>
    </div>

    <div class="filter_selected px-4" v-if="inqFilterStatus">
      <span class="selected_filter_item_icon me-2"><i class="fa-solid fa-sliders"></i></span>
      <span class="selected_filter_item"
        v-if="ActivityFilter.globalSearch != null && ActivityFilter.globalSearch != ''">{{ ActivityFilter.globalSearch
        }} <i class="fa-solid fa-xmark"
          @click="() => { ActivityFilter.globalSearch = null; ActivityFilterDataSubmit() }"></i></span>
      <span class="selected_filter_item" v-if="ActivityFilter.name != null">{{ getfiltername(ActivityFilter.name) }} <i
          class="fa-solid fa-xmark"
          @click="() => { ActivityFilter.name = null; ActivityFilterDataSubmit() }"></i></span>
      <span class="selected_filter_item" v-if="ActivityFilter.description != null">{{ ActivityFilter.description }} <i
          class="fa-solid fa-xmark"
          @click="() => { ActivityFilter.description = null; ActivityFilterDataSubmit() }"></i></span>
      <span class="selected_filter_item" v-if="ActivityFilter.log_name != null">{{ ActivityFilter.log_name }} <i
          class="fa-solid fa-xmark"
          @click="() => { ActivityFilter.log_name = null; ActivityFilterDataSubmit() }"></i></span>
      <span class="selected_filter_item" v-if="ActivityFilter.created != null">{{ formatDateOnly(ActivityFilter.created)
      }} <i class="fa-solid fa-xmark"
          @click="() => { ActivityFilter.created = null; ActivityFilterDataSubmit() }"></i></span>
    </div>
    <div>
      <b-overlay :show="loader" rounded="lg" opacity="0.8" class="loader_section" no-wrap>
        <template #overlay>
          <span class="loadersdots"></span>
        </template>
        <div class="table_listing" v-if="fetchTeam.length > 0">
          <b-table responsive="sm" ref="refTeamListTable" :items="fetchTeam" :fields="fields"
            @sort-changed="onSortChanged" class="mb-2 staticTable" show-empty empty-text="No matching records found">
            <template #cell(created)="data">
              <span>{{ formatDate(data.value) }}</span>
            </template>
            <template #head(user_name)="data">
              <span @click="changeSorting('user_name')">
                User Name
                <i :class="getSortIcon('user_name')"></i>
              </span>
            </template>
            <template #cell(user_name)="data">
              <b-link v-if="$can('do', 'view_admin.org.employees')"
                :to="`/admin/activity/view/${encodeBase64(data.item.id)}`">
                {{ data.item.user_name }}
              </b-link>
              <span v-else>
                {{ data.item.user_name }}
              </span>
            </template>
            <template #head(ip_address)="data">
              <span @click="changeSorting('ip_address')">
                IP Address
                <i :class="getSortIcon('ip_address')"></i>
              </span>
            </template>
            <template #head(device_browser)="data">
              <span @click="changeSorting('device_browser')">
                Device/Browser
                <i :class="getSortIcon('device_browser')"></i>
              </span>
            </template>
            <template #head(created)="data">
              <span @click="changeSorting('created')">
                Date & Time
                <i :class="getSortIcon('created')"></i>
              </span>
            </template>
            <template #head(description)="data">
              <span @click="changeSorting('description')">
                Description
                <i :class="getSortIcon('description')"></i>
              </span>
            </template>
            <template #head(log_name)="data">
              <span @click="changeSorting('log_name')">
                Log Name
                <i :class="getSortIcon('log_name')"></i>
              </span>
            </template>
            <template #cell(event)="data">
              <span class="align-text-top text-capitalize" style="color:blue; cursor:pointer"
                @click="openActivityModal(data.item.properties)">
                {{ data.item.event }}
              </span>
            </template>
          </b-table>

          <div class="pagination_screen">
            <div class="show_entries">
              <span class="mr-2 showing_pagination_result">Show</span>
              <v-select append-to-body :calculate-position="withPopper" v-model="perPage" @update:modelValue="resetPage"
                :options="perPageOptions" :clearable="false" class="mr-2" />
              <span class="showing_pagination_result">entries</span>
            </div>
            <div class="showing_pagination_result">
              Showing {{ rangeStart }} to {{ rangeEnd }} of {{ totalrows }}
              entries
            </div>
            <div class="pagination_listing">
              <b-pagination v-if="totalrows > 0" v-model="currentPage" :total-rows="totalrows" :per-page="perPage"
                @input="refetchData" pills first-number last-number prev-class="prev-item" next-class="next-item">
                <template #prev-text>
                  <i class="fa-solid fa-angle-left"></i>
                </template>
                <template #next-text>
                  <i class="fa-solid fa-angle-right"></i>
                </template>
              </b-pagination>
            </div>
          </div>
        </div>
        <div v-else>


          <activityLogNoData
            v-if="inqFilterStatus && fetchTeam.length === 0 || !inqFilterStatus && sidebarstatus.filter"
            heading="No Records Found" subheading="Try adjusting your filters or search criteria."
            :showButton="false" />
        </div>
      </b-overlay>
    </div>


    <b-modal v-model="showVerificationModal" centered cancel-variant="outline-secondary" hide-footer>
      <div class="align-text-top text-capitalize myDescription" ref="modelDescription">
        <div class="row">
          <div class="col">
            <div v-if="NewModelDescription !== undefined">
              <p class="font-weight-bold mb-2">New Data</p>
              <div class="table-responsive">
                <table class="table table-bordered">
                  <tbody v-if="OldModelDescription !== undefined">
                    <tr v-for="(value, key) in sortDescriptionKeys(NewModelDescription)" :key="key">
                      <td :class="{ highlighted: isDifferent(key) }">{{ key }}</td>
                      <td :class="{ highlighted: isDifferent(key) }">
                        {{
                          key === 'Deadline'
                            ? formatDateFormat(value)
                            : key === 'created_at' || key === 'updated_at' ||
                              key === 'Created At' || key === 'Updated At' ||
                              key === 'added_at'
                              ? formatDate(value)
                              : key === 'deleted_at'
                                ? (value === null ? '' : formatDate(value))
                                : key === 'status'
                                  ? (value === 'A' ? 'Active' : value === 'I' ? 'Inactive' : '')
                                  : value
                        }}
                      </td>
                    </tr>
                  </tbody>
                  <tbody v-if="OldModelDescription === undefined">
                    <tr v-for="(value, key) in sortDescriptionKeys(NewModelDescription)" :key="key">
                      <td>{{ key }}</td>
                      <td>
                        {{
                          key === 'Deadline'
                            ? formatDateFormat(value)
                            : key === 'created_at' || key === 'updated_at' ||
                              key === 'Created At' || key === 'Updated At' ||
                              key === 'added_at'
                              ? formatDate(value)
                              : key === 'deleted_at'
                                ? (value === null ? '' : formatDate(value))
                                : key === 'status'
                                  ? (value === 'A' ? 'Active' : value === 'I' ? 'Inactive' : '')
                                  : value
                        }}
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
          <div class="col">
            <div v-if="OldModelDescription !== undefined">
              <p class="font-weight-bold mb-2">Old Data</p>
              <div class="table-responsive">
                <table class="table table-bordered">
                  <tbody>
                    <tr v-for="(value, key) in sortDescriptionKeys(OldModelDescription)" :key="key">
                      <td :class="{ highlighted: isDifferent(key) }">{{ key }}</td>
                      <td :class="{ highlighted: isDifferent(key) }">
                        {{
                          key === 'Deadline'
                            ? formatDateFormat(value)
                            : key === 'created_at' || key === 'updated_at' ||
                              key === 'Created At' || key === 'Updated At' ||
                              key === 'added_at'
                              ? formatDate(value)
                              : key === 'deleted_at'
                                ? (value === null ? '' : formatDate(value))
                                : key === 'status'
                                  ? (value === 'A' ? 'Active' : value === 'I' ? 'Inactive' : '')
                                  : value
                        }}
                      </td>

                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>
    </b-modal>
  </div>
</template>
<script setup>
import "vue-select/dist/vue-select.css";
import vSelect from "vue-select";
import { ref, watch, onMounted, computed } from "vue";
import CloseIcon from "../../../assets/img/icons/Close.vue"
import activityLogNoData from "../../../components/noData.vue";
import axiosEmployee from '@axiosEmployee';
import { createPopper } from '@popperjs/core';
import VueDatePicker from "@vuepic/vue-datepicker";
import "@vuepic/vue-datepicker/dist/main.css";
const formatDateOnly = (dateString) => {
  if (!dateString) return '';
  const date = new Date(dateString);
  if (isNaN(date)) return '';

  const options = {
    year: 'numeric',
    month: 'short', // Jan, Feb, Mar ...
    day: 'numeric'
  };

  return new Intl.DateTimeFormat('en-GB', options).format(date);
};
const refTeamListTable = ref(null);
const fetchTeam = ref([]);
const currentPage = ref(1);
const perPage = ref(10);
const sortBy = ref('');
const sortDesc = ref(false);
const totalrows = ref(0);
const perPageOptions = [10, 50, 75, 100];
const NewModelDescription = ref('');
const OldModelDescription = ref('');
const showVerificationModal = ref(false);
const sortDescriptionKeys = (description) => {
  const priorityOrder = ['Property Id', 'Inquiry Id', 'Client Id'];
  const sorted = {};

  if (!description || typeof description !== 'object') return {};
  priorityOrder.forEach((key) => {
    if (key in description) {
      sorted[key] = description[key];
    }
  });
  for (const key in description) {
    if (!priorityOrder.includes(key)) {
      sorted[key] = description[key];
    }
  }

  return sorted;
}; const formatDateFormat = (dateStr) => {
  if (!dateStr) return '';
  const date = new Date(dateStr);
  return new Intl.DateTimeFormat('en-GB', {
    day: '2-digit',
    month: 'long',
    year: 'numeric',
  }).format(date);
};
const withPopper = (dropdownList, component, { width }) => {
  dropdownList.style.width = width;
  const popper = createPopper(component.$refs.toggle, dropdownList, {
    placement: 'bottom',
    modifiers: [
      {
        name: 'offset',
        options: {
          offset: [0, -1],
        },
      },
      {
        name: 'toggleClass',
        enabled: true,
        phase: 'write',
        fn({ state }) {
          component.$el.classList.toggle(
            'drop-up',
            state.placement === 'top'
          )
        },
      },
    ],
  })
  return () => popper.destroy()
};

const ActivityFilter = ref({
  log_name: null,
  description: null,
  name: null,
  globalSearch: null,
  created: null,

});

axiosEmployee.get("/alluser").then((response) => {
  assigneeOptions.value = response.data.alluser;
});
axiosEmployee.get('/getlogname').then(response => {
  logname.value = response.data.logname;
})
const logname = ref([]);
const assigneeOptions = ref([]);


const fields = [

  { key: 'user_name', label: 'User Name', sortable: true },
  { key: 'created', label: 'Date & Time', sortable: true },
  { key: 'log_name', label: 'Log name', sortable: true },
  { key: 'description', label: 'Description', sortable: true },
  { key: 'ip_address', label: 'IP Address', sortable: true },
  { key: 'device_browser', label: 'Device/Browser', sortable: true },
  { key: 'event', label: 'Event', sortable: true },

];

const openActivityModal = (data) => {
  let dataDescription = JSON.parse(data)
  OldModelDescription.value = dataDescription.old
  NewModelDescription.value = dataDescription.attributes
  showVerificationModal.value = true
}

onMounted(() => {
  refetchData();
});
const formatDate = (dateString) => {
  const date = new Date(dateString);
  const options = { year: 'numeric', month: 'short', day: 'numeric' };
  let formattedDate = new Intl.DateTimeFormat('en-GB', options).format(date);
  let hours = date.getHours();
  const minutes = date.getMinutes();
  let ampm = 'AM';
  if (hours >= 12) {
    ampm = 'PM';
    if (hours > 12) hours -= 12;
  } else {
    if (hours === 0) hours = 12;
    ampm = 'AM';
  }
  const formattedMinutes = minutes < 10 ? `0${minutes}` : minutes;
  return `${formattedDate} ${hours}:${formattedMinutes} ${ampm}`;
};


const rangeStart = computed(() =>
  totalrows.value === 0 ? 0 : (currentPage.value - 1) * perPage.value + 1
);
const rangeEnd = computed(() =>
  currentPage.value * perPage.value >= totalrows.value
    ? totalrows.value
    : currentPage.value * perPage.value
);

const isDifferent = (key) => {
  return NewModelDescription.value[key] !== OldModelDescription.value[key];
}; const getDescriptionClick = (data) => {
  showVerificationModal.value = true;
  let dataDescription = JSON.parse(data);
  OldModelDescription.value = dataDescription.old;
  NewModelDescription.value = dataDescription.attributes;

};

// Function to format the value displayed in the modal tables
const loader = ref(false);


const onSortChanged = (ctx) => {
  currentPage.value = 1;
  sortBy.value = ctx.sortBy;
  sortDesc.value = ctx.sortDesc;
};
const changeSorting = (field) => {
  currentPage.value = 1;
  if (sortBy.value === field) {
    sortDesc.value = !sortDesc.value;
  } else {
    sortBy.value = field;
    sortDesc.value = false;
  }
  refetchData(); // Assuming fetchNoOfData is defined elsewhere
};


const getSortIcon = (field) => {
  if (sortBy.value === field) {
    return sortDesc.value ? "fas fa-sort-down" : "fas fa-sort-up";
  }
  return "fas fa-sort";
};

const ActivityFilterDataSubmit = () => {
  currentPage.value = 1;
  refetchData();
  sidebarstatus.value.filter = false;
};


const resetPage = () => {
  currentPage.value = 1;
  refetchData();
};

const getfiltername = (id) => {
  let filtername = null;
  assigneeOptions.value.forEach((elm) => {
    if (elm.value == id) {
      filtername = elm.label;
    }
  });
  return filtername;
}


const refetchData = () => {
  loader.value = true;
  axiosEmployee
    .get('/getactivity', {
      params: {
        ...ActivityFilter.value,
        page: currentPage.value,
        perPage: perPage.value,
        sortBy: sortBy.value,
        sortDesc: sortDesc.value,
      },
    })
    .then((response) => {
      loader.value = false;
      const loginLogsData = response.data.data;
      fetchTeam.value = Array.isArray(loginLogsData.data) ? loginLogsData.data : [];
      totalrows.value = loginLogsData.total;

    })
    .catch((error) => {
      loader.value = false;
    });
};
const sidebarstatus = ref({
  shadow: false,
  filter: false,
  add: false,
  view: false
});


const resetModal = () => {
  OldModelDescription.value = '';
  NewModelDescription.value = '';
};

const ResetFilter = () => {
  sidebarstatus.value.filter = false;
  currentPage.value = 1;
  ActivityFilter.value = {
    log_name: null,
    description: null,
    name: null,
    globalSearch: null,
    created: null

  };
  refetchData();
};
watch([currentPage, perPage], () => {
  refetchData();
});
watch(sidebarstatus.value, async (newstatus, oldstatus) => {
  if (newstatus.filter) {
    sidebarstatus.value.shadow = true;
  } else {
    sidebarstatus.value.shadow = false;
  }
});

const inqFilterStatus = ref(false);
watch(() => ActivityFilter.value, (newValue) => {
  inqFilterStatus.value = false;
  for (const key in newValue) {
    if (Array.isArray(newValue[key])) {
      if (newValue[key].length != 0) {
        inqFilterStatus.value = true;
      }
    }
    else {
      if (newValue[key] != null && newValue[key] != "") {
        inqFilterStatus.value = true;
      }
    }

  }

}, { deep: true });

const vSelectOpen = () => {
  let nestedElement = document.getElementById("filter_frm_sidebar");
  setTimeout(function () { nestedElement.scrollTo(0, nestedElement.scrollHeight) }, 100);
}
const encodeBase64 = (data) => {
  return btoa(data);
};
</script>
<style>
.highlighted {
  background-color: #28DEFC !important;
}
</style>
