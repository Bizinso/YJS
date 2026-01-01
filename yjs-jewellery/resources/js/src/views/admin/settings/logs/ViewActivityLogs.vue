<template>
  <div class="activity-view px-4 py-3">
    <h4 class="mb-4">Activity Log Details</h4>

    <b-overlay :show="loading">
      <template #overlay>
        <span class="loadersdots"></span>
      </template>

      <div v-if="log">
        <table class="table table-bordered">
          <tbody>
            <tr>
              <th>User Name</th>
              <td>{{ log.user_name }}</td>
            </tr>

            <tr>
              <th>Description</th>
              <td>{{ log.description }}</td>
            </tr>

            <tr>
              <th>Log Name</th>
              <td>{{ log.log_name }}</td>
            </tr>

            <tr>
              <th>Event</th>
              <td>{{ log.event }}</td>
            </tr>

            <tr>
              <th>IP Address</th>
              <td>{{ log.ip_address }}</td>
            </tr>

            <tr>
              <th>Device/Browser</th>
              <td>{{ log.device_browser }}</td>
            </tr>

            <tr>
              <th>Created At</th>
              <td>{{ formatDate(log.created_at) }}</td>
            </tr>
          </tbody>
        </table>

        <!-- Show Old vs New Data -->
        <h5 class="mt-4 mb-2">Change Summary</h5>

        <div class="row">
          <div class="col-md-6">
            <h6><b>New Data</b></h6>
            <pre>{{ newDataFormatted }}</pre>
          </div>

          <div class="col-md-6">
            <h6><b>Old Data</b></h6>
            <pre>{{ oldDataFormatted }}</pre>
          </div>
        </div>
      </div>

      <div v-else>
        <p>No details found.</p>
      </div>
    </b-overlay>
  </div>
</template>

<script setup>
import { ref, onMounted } from "vue";
import axiosEmployee from "@axiosEmployee";
import { useRoute } from "vue-router";

const route = useRoute();
const loading = ref(false);
const log = ref(null);
const newDataFormatted = ref('');
const oldDataFormatted = ref('');

onMounted(() => {
  fetchDetails();
});

const fetchDetails = () => {
  loading.value = true;

  axiosEmployee
    .get(`/getactivitydetail/${route.params.id}`)
    .then((response) => {
      loading.value = false;

      log.value = response.data.data;

      newDataFormatted.value = JSON.stringify(response.data.data.new ?? {}, null, 2);
      oldDataFormatted.value = JSON.stringify(response.data.data.old ?? {}, null, 2);
    })
    .catch(() => {
      loading.value = false;
    });
};

// SAFE Date Formatting
const formatDate = (dateString) => {
  if (!dateString) return "-";

  const date = new Date(dateString);
  if (isNaN(date)) return "-"; // prevents Invalid time value error

  const options = { year: "numeric", month: "short", day: "numeric" };
  let formattedDate = new Intl.DateTimeFormat("en-GB", options).format(date);

  let hours = date.getHours();
  const minutes = date.getMinutes();
  let ampm = hours >= 12 ? "PM" : "AM";

  if (hours > 12) hours -= 12;
  if (hours === 0) hours = 12;

  const formattedMinutes = minutes < 10 ? `0${minutes}` : minutes;

  return `${formattedDate} ${hours}:${formattedMinutes} ${ampm}`;
};
</script>

<style>
pre {
  background: #f8f9fa;
  padding: 10px;
  border-radius: 6px;
  border: 1px solid #ddd;
  max-height: 350px;
  overflow-y: auto;
}
</style>
