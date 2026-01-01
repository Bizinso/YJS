<template>
  <div class="general_profile_screen">
    <div class="general_profile_form w-100">
      <b-form class="formMaster disasshat py-0">
        <b-form-group label="Old Password" class="w-100">
          <b-input-group class="input-group-merge">
            <b-form-input :type="showOldPassword ? 'text' : 'password'" v-model="userpassword.current_password"
              id="current_password" @input="RemoveError('current_password')" :state="errors.length > 0 ? false : null"
              placeholder="Enter Old Password" autocomplete="off" />

            <b-input-group-append is-text>
              <button type="button" @click="toggleOldPasswordView" class="toggle-password-btn">
                <i :class="showOldPassword ? 'fa fa-eye-slash' : 'fa fa-eye'"></i>
              </button>
            </b-input-group-append>

          </b-input-group>
          <small class="text-danger">{{ errors[0] }}</small>
          <div class="text-danger" v-if="hasErrors('current_password')">
            {{ getErrors("current_password") }}
          </div>
        </b-form-group>
        <b-form-group label="New Password" class="w-100">
          <b-input-group class="input-group-merge">

            <b-form-input :type="showNewPassword ? 'text' : 'password'" v-model="userpassword.new_password"
              id="new_password" @input="RemoveError('new_password')" :state="errors.length > 0 ? false : null"
              placeholder="Enter New Password" autocomplete="off" />
            <b-input-group-append is-text>
              <button type="button" @click="toggleNewPasswordView" class="toggle-password-btn">
                <i :class="showNewPassword ? 'fa fa-eye-slash' : 'fa fa-eye'"></i>
              </button>
            </b-input-group-append>
          </b-input-group>
          <small class="text-danger">{{ errors[0] }}</small>
          <div class="text-danger" v-if="hasErrors('new_password')">
            {{ getErrors("new_password") }}
          </div>
        </b-form-group>

        <b-form-group label="New Confirm Password" class="w-100">
          <b-input-group class="input-group-merge">
            <b-form-input :type="showConfirmPassword ? 'text' : 'password'"
              v-model="userpassword.new_password_confirmation" id="new_password_confirmation"
              @input="RemoveError('new_password_confirmation')" :state="errors.length > 0 ? false : null"
              placeholder="Enter Confirm New Password" autocomplete="off" />
            <b-input-group-append is-text>
              <button type="button" @click="toggleConfirmPasswordView" class="toggle-password-btn">
                <i :class="showConfirmPassword ? 'fa fa-eye-slash' : 'fa fa-eye'"></i>
              </button>
            </b-input-group-append>
          </b-input-group>
          <small class="text-danger">{{ errors[0] }}</small>
          <div class="text-danger" v-if="hasErrors('new_password_confirmation')">
            {{ getErrors("new_password_confirmation") }}
          </div>
        </b-form-group>
      </b-form>
      <b-button type="submit" class="btn_primary" @click="changePassword">Change Password</b-button>
    </div>
  </div>
</template>

<script>
import axiosEmployee from '@axiosEmployee';
import { ref } from 'vue';
import { useRouter } from "vue-router";
import { toast } from "vue3-toastify";
import "vue3-toastify/dist/index.css";

axiosEmployee.defaults.withCredentials = true;

export default {
  data() {
    return {

      showOldPassword: false,
      showNewPassword: false,
      showConfirmPassword: false,
      errors: {},
    };
  },
  methods: {

    RemoveError(field) {
      if (this.errors[field]) {
        delete this.errors[field];
      }
    },
    hasErrors(fieldName) {
      return this.errors && this.errors[fieldName];
    },
    getErrors(fieldName) {
      return this.errors[fieldName] ? this.errors[fieldName][0] : '';
    },
    toggleOldPasswordView() {
      this.showOldPassword = !this.showOldPassword;
    },
    toggleNewPasswordView() {
      this.showNewPassword = !this.showNewPassword;
    },
    toggleConfirmPasswordView() {
      this.showConfirmPassword = !this.showConfirmPassword;
    },
  },
  setup() {
    const userpassword = ref({
      current_password: '',
      new_password: '',
      new_password_confirmation: '',
    });
    const errors = ref([]);
    const router = useRouter();
    const changePassword = () => {
  
      axiosEmployee.post("/change-password", userpassword.value)
        .then((response) => {
           toast("Password Updated successfully!", {
              type: "success",
              autoClose: 5000, 
              pauseOnHover: true,
            });
            setTimeout(() => {
              Logout();
            }, 1500);
        })
        .catch(error => {
          if (error.response && error.response.data.code === 422) {
            errors.value = error.response.data.errors;
          }
        });
  
    };
    function Logout() {
      const token = localStorage.getItem(`employee_token`);
      axiosEmployee
        .post(
          "/logout",
          {},
          {
            headers: {
              Authorization: `Bearer ${token}`,
            },
          }
        )
        .then(() => {
          const keysToRemove = [
            `employee_token`,
            `employee_data`,
            `employee_ability`
          ];
          keysToRemove.forEach((key) => localStorage.removeItem(key));
          updateAbilities([]);
          router.push("/login");
        })
        .catch((error) => {
          console.error("Logout error:", error);
        });
    }

    return {
      userpassword,
      changePassword,
      errors,
      Logout,
    };
  }
};

</script>
<style scoped>
.small-card {
  max-width: 600px;
  margin: auto;
  padding: 20px;
}

.toggle-password-btn {
  border: none;
  background: transparent;
  cursor: pointer;
  padding: 0.5rem;
}
</style>