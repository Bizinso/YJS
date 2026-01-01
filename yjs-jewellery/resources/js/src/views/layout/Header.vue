<template>
  <div class="yjs_header">
    <div class="header_marquee">
      <div class="marquee_effect">
        <!-- <vue-marquee-slider id="marquee-header-slider" :speed="30000" autoWidth :space="100">
        <p>Event Participation Name & Date</p>
        <p>Event Participation Name & Date</p>
        <p>Event Participation Name & Date</p>
        <p>Event Participation Name & Date</p>
        <p>Event Participation Name & Date</p>
        <p>Event Participation Name & Date</p>
        <p>Event Participation Name & Date</p>
      </vue-marquee-slider> -->
      </div>
    </div>

    <!-- 🔹 Logo + Search + Icons -->
    <div class="header_logo_cart py-2">
      <div class="container">
        <div class="logo_cart_divider">
          <!-- Hamburger + Logo -->
          <div class="internallogo">
            <div class="mobframe">

              <button class="hamburger-btn me-2 d-lg-none" @click="toggleSidebar">
                <i class="fa-solid fa-bars"></i>
              </button>
              <b-link :to="{ name: 'home' }">
                <img src="../assets/images/yjs_logo.png" class="header_logo_yjs deskShow" alt="yjs_logo" />
                <img src="../assets/images/yjs.png" class="header_logo_yjs mobShow" alt="yjs_logo" />
              </b-link>

            </div>
            <ul class="header_icons mobFlexShow align-items-center mb-0">
              <!-- CART -->
              <li class="icon-wrapper"  v-if="!isPartnerLoggedIn" @click="goToCart">
                <div class="position-relative">
                  <i class="fa-solid fa-cart-shopping"></i>
                  <span class="badgeWrapper" v-if="cartCount > 0">{{ cartCount }}</span>
                </div>
              </li>

              <!-- WISHLIST -->
              <li class="icon-wrapper"  v-if="!isPartnerLoggedIn">
                <div class="position-relative">
                  <i class="fa-solid fa-heart"></i>
                  <span class="badgeWrapper" v-if="wishlistCount > 0">{{ wishlistCount }}</span>
                </div>
              </li>

              <!-- LOGIN -->
              <li v-if="!isCustomerLoggedIn && !isPartnerLoggedIn" @click="openLogin">
                <i class="fa-solid fa-user"></i>
              </li>

              <!-- ACCOUNT -->
              <li v-else>
                <i class="fa-solid fa-user"></i>
              </li>

              <!-- LOGOUT -->
              <li v-if="isCustomerLoggedIn || isPartnerLoggedIn" @click="logout" class="logout-btn">
                <i class="fa-solid fa-right-from-bracket"></i>
              </li>
            </ul>

          </div>

          <!-- Search (mobile: full width below) -->
          <div class="search_wrapper">
            <i class="fa-solid fa-magnifying-glass search_header_placeholder"></i>
            <b-form-input class="header_search_input" placeholder="Search 'Necklace'"></b-form-input>
          </div>


          <!-- Icons -->
          <ul class="header_icons deskFlexShow align-items-center mb-0">
            <li class="icon-wrapper"  v-if="!isPartnerLoggedIn" @click="goToCart">
              <div class="position-relative">
                <i class="fa-solid fa-cart-shopping"></i>
                <span class="badgeWrapper" v-if="cartCount > 0">{{ cartCount }}</span>
              </div>
              <span>CART</span>
            </li>

            <li class="icon-wrapper"  v-if="!isPartnerLoggedIn">
              <div class="position-relative">
                <i class="fa-solid fa-heart"></i>
                <span class="badgeWrapper" v-if="wishlistCount > 0">{{ wishlistCount }}</span>
              </div>
              <span>WISHLIST</span>
            </li>


            <li v-if="!isCustomerLoggedIn && !isPartnerLoggedIn" @click="openLogin">
              <i class="fa-solid fa-user"></i>
              <span>LOGIN</span>
            </li>

            <li v-else>
              <i class="fa-solid fa-user"></i>
              <span>ACCOUNT</span>
            </li>

            <li v-if="isCustomerLoggedIn || isPartnerLoggedIn" @click="logout" class="logout-btn">
              <i class="fa-solid fa-right-from-bracket"></i>
              <span>Logout</span>
            </li>
          </ul>
        </div>
      </div>
    </div>

    <!-- 🔹 Navigation (Desktop only) -->
    <div class="header_navigation d-none d-lg-block">
      <div class="container">
        <div class="navigation_divider d-flex justify-content-between align-items-center">
          <div class="navigation_list">
            <ul class="d-flex mb-0">
              <li><b-link :to="{ name: 'about' }">About Us</b-link></li>
              <li><b-link @click.prevent="goToProducts">Our Products</b-link></li>
              <!-- <li><b-link>Our Partners</b-link></li> -->
              <!-- <li><b-link>Shop Online</b-link></li> -->
              <li><b-link :to="{ name: 'collections' }">Collections</b-link></li>
              <li><b-link :to="{ name: 'contact' }">Contact Us</b-link></li>
            </ul>
          </div>
          <div class="navigation_other d-flex align-items-center">
            <p class="mb-0 me-2">Gold Rate: 1,37,145.00</p>
            <span class="me-3">+0.25% <i class="fa-solid fa-caret-up"></i></span>
            <b-button class="btn_gprimary">Events</b-button>
          </div>
        </div>
      </div>
    </div>

    <!-- 🔹 Sidebar (Mobile Navigation) -->
    <transition name="slide">
      <div v-if="isSidebarOpen" class="mobile_sidebar">
        <div class="sidebar_header d-flex justify-content-between align-items-center">
          <b-link :to="{ name: 'home' }">
            <img src="../assets/images/yjs_logo.png" class="header_logo_yjs w-75" alt="yjs_logo" />
          </b-link>

          <button class="close-btn" @click="toggleSidebar">
            <i class="fa-solid fa-xmark"></i>
          </button>
        </div>
        <ul @click="toggleSidebar">
          <li><b-link :to="{ name: 'about' }">About Us</b-link></li>
          <li><b-link @click.prevent="goToProducts">Our Products</b-link></li>
          <!-- <li><b-link>Our Partners</b-link></li> -->
          <!-- <li><b-link>Shop Online</b-link></li> -->
          <li><b-link :to="{ name: 'collections' }">Collections</b-link></li>
          <li><b-link :to="{ name: 'contact' }">Contact Us</b-link></li>
        </ul>
      </div>
    </transition>

    <div v-if="isSidebarOpen" class="sidebar_overlay" @click="toggleSidebar"></div>


    <b-modal v-model="showModal" hide-header hide-footer centered size="md" class="customModal">
      <div class="contentFrame loginFrames">

        <!-- CLOSE -->
        <b-button class="CloseBTN" @click="closeModal">
          <img src="../assets/images/close.svg" />
        </b-button>

        <div v-if="!showOTPForm">
          <h3 class="formHeading">Login to Your Account</h3>

          <div class="d-flex justify-content-between gap-3 roleSelect">

            <div :class="['roleBox', selectedRole === 'customer' ? 'activeBox' : '']"
              @click="selectedRole = 'customer'">
              <input type="radio" name="role" value="customer" v-model="selectedRole" class="radioInputBox" />
              <img src="../assets/images/customer.svg" class="roleImg" />
              <p>Customer</p>
            </div>

            <div :class="['roleBox', selectedRole === 'partner' ? 'activeBox' : '']" @click="selectedRole = 'partner'">
              <input type="radio" name="role" value="partner" v-model="selectedRole" class="radioInputBox" />
              <img src="../assets/images/partner.svg" class="roleImg" />
              <p>Partner</p>
            </div>
          </div>

          <!-- EMAIL / MOBILE FIELD -->
          <b-form-group class="leg" label="Email Address Or Mobile Number">
            <b-form-input v-model="formData.identifier" placeholder="Enter email or mobile" />
          </b-form-group>


          <small v-if="formData.errorMessage" class="text-danger">
            {{ formData.errorMessage }}
          </small>

          <!-- SEND OTP BUTTON -->
          <b-button class="buyNowBtn w-100 mt-3" :disabled="!identifierValid" @click="sendOTP">Send OTP</b-button>
          <b-link href="javascript:void(0)" class="w-100 partnerLink mt-3" v-if="selectedRole === 'partner'"
            @click="goToRegisterPartner">Register New Partner</b-link>
        </div>

        <!-- ======================= STEP 2: OTP SCREEN ======================= -->
        <div v-else>
          <h3 class="formHeading">Enter OTP</h3>
          <h4 class="formSubHeading">OTP is sent to your entered details</h4>
          <h3 class="infoBox">{{ formData.identifier }} <img src="../assets/images/pen.svg" class="penImg"
              @click="goBack" /></h3>

          <div class="d-flex justify-content-center gap-2 mt-3">
            <b-form-input v-for="(digit, index) in formData.otp" :key="index" v-model="formData.otp[index]"
              maxlength="1" class="otpField text-center otpBox" inputmode="numeric" pattern="[0-9]*"
              @update:modelValue="handleOtpInput($event, index)" @keydown="handleOtpKeydown($event, index)" />
          </div>

          <button class="buyNowBtn w-100 mt-4" @click="verifyOTP">Verify OTP</button>

          <p class="didnt">Didn't Receive the OTP?</p>
          <button class="buttonLikeLink mb-2" @click="goBack">Resend OTP</button>
        </div>
      </div>
    </b-modal>
    <b-modal id="logoutModal" v-model="showLogoutModal" title="Confirm Logout" title-class="fontBox" hide-footer>
      <div class="d-block text-left">
        <p class="mb-3">Are you sure you want to logout?</p>
        <div class="buttonGrid">
          <b-button class="GlobalfillBTN" @click="confirmLogout">Yes</b-button>
          <b-button class="GlobaltransBTN" @click="cancelLogout">No</b-button>
        </div>
      </div>
    </b-modal>
  </div>
</template>

<style scoped>
/* ===== General Header Styling ===== */
/* .yjs_header {
background-color: #fff;
border-bottom: 1px solid #eee;
} */


/* ===== Logo & Search ===== */
.logo_cart_divider {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

/* .header_logo_yjs {
height: 50px;
} */
.search_wrapper {
  position: relative;
  flex: 1;
}

.search_header_placeholder {
  position: absolute;
  top: 50%;
  left: 10px;
  transform: translateY(-50%);
  color: #999;
}

.header_search_input {
  padding-left: 35px;
  border-radius: 25px;
}

/* ===== Icons ===== */
/* .header_icons li {
list-style: none;
margin-left: 15px;
text-align: center;
}
.header_icons li i {
font-size: 18px;
}
.header_icons li span {
display: block;
font-size: 12px;
} */

/* ===== Hamburger ===== */
.hamburger-btn {
  background: none;
  border: none;
  font-size: 24px;
}

/* ===== Sidebar ===== */
.mobile_sidebar {
  position: fixed;
  top: 0;
  left: 0;
  width: 260px;
  height: 100%;
  background: white;
  box-shadow: 2px 0 8px rgba(0, 0, 0, 0.15);
  z-index: 9999;
  /* padding: 0 20px; */
}

.sidebar_overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0, 0, 0, 0.45);
  z-index: 9998;
  backdrop-filter: blur(2px);
  transition: opacity 0.3s ease;
}

.sidebar_header {
  border-bottom: 1px solid #ddd;
  /* padding-bottom: 10px;
margin-bottom: 15px; */
}

.mobile_sidebar ul {
  padding: 0;
  margin: 0;
  list-style: none;
}

.mobile_sidebar ul li {
  padding: 10px;
  border-bottom: 1px solid #f1f1f1;
}

.mobile_sidebar ul li a {
  color: #333;
  text-decoration: none;
}

.close-btn {
  background: none;
  border: none;
  margin-right: 20px;
  font-size: 22px;
}

/* ===== Animation ===== */
.slide-enter-active,
.slide-leave-active {
  transition: all 0.3s ease;
}

.slide-enter-from,
.slide-leave-to {
  transform: translateX(-100%);
  opacity: 0;
}

.roleSelect .roleBox {
  flex: 1;
  padding: 14px;
  border: 2px solid #ddd;
  border-radius: 10px;
  text-align: center;
  cursor: pointer;
  transition: 0.25s;
}


.otpBox {
  width: 3rem;
  height: 3.2rem;
  font-size: 22px;
  border-radius: 8px;
  background: #fff;
  /* black background */
  color: #404054;
  /* white digits */
  border: 1px solid #404054;
}

/* ===== Responsive ===== */
@media (max-width: 992px) {
  .header_logo_cart .logo_cart_divider {
    flex-direction: column;
    align-items: stretch;
  }

  .search_wrapper {
    width: 100%;
    margin-top: 10px;
  }

  .yjs_header .header_logo_cart .logo_cart_divider ul {
    display: flex;
    gap: 10px;
    padding: 0px;
    margin: 0px;
  }

  .header_icons {
    justify-content: space-around;
    margin-top: 10px;
    padding-top: 8px;
  }

  .header_navigation {
    display: none !important;
  }
}

.icon-wrapper {
  position: relative;
}

.icon-badge {
  position: absolute;
  top: -6px;
  right: -10px;
  background: #e53935;
  color: #fff;
  font-size: 11px;
  padding: 2px 6px;
  border-radius: 50%;
  min-width: 18px;
  text-align: center;
  display: none;
}
</style>

<script setup>
import { ref, computed, nextTick, watch, onMounted } from "vue";
import { useRouter, useRoute } from "vue-router";
import { toast } from "vue3-toastify";
import axiosCustomer from "@axiosCustomer";
import axiosPartner from "@axiosPartner";
import { syncLocalDataToBackend } from "../../utils/localStorageCart";

import {
  isCustomerLoggedIn,
  setCustomerLogout,
  setCustomerLogin
} from "@/stores/authCustomer";

import {
  isPartnerLoggedIn,
  setPartnerLogout,
  setPartnerLogin
} from "@/stores/authPartner";


/* ===================== ROUTER ===================== */
const router = useRouter();
const route = useRoute();

/* ===================== MODAL STATE ===================== */
const showModal = ref(false);
const showOTPForm = ref(false);
const selectedRole = ref("customer");
const cartCount = ref(0);
const wishlistCount = ref(0);
const formData = ref({
  identifier: "",
  otp: ["", "", "", "", "", ""],
  errorMessage: ""
});

const goToRegisterPartner = async () => {
  closeModal()
  await nextTick()
  router.push({ name: 'RegisterPartner' })
}

/* ===================== HELPERS ===================== */
const resetAuthState = () => {
  showOTPForm.value = false;
  formData.value = {
    identifier: "",
    otp: ["", "", "", "", "", ""],
    errorMessage: ""
  };
};

const openLogin = () => {
  showModal.value = true;
};

const closeModal = () => {
  showModal.value = false;
};

/* ===================== WATCHERS ===================== */

/* Clear everything when modal opens */
watch(showModal, (val) => {
  if (val) resetAuthState();
});

/* Clear errors when switching role */
watch(selectedRole, () => {
  resetAuthState();
});

/* Clear error when typing identifier */
watch(
  () => formData.value.identifier,
  () => {
    formData.value.errorMessage = "";
  }
);

/* Clear error when OTP changes */
watch(
  () => formData.value.otp,
  () => {
    formData.value.errorMessage = "";
  },
  { deep: true }
);

/* ===================== VALIDATION ===================== */
const identifierValid = computed(() => {
  const value = formData.value.identifier.trim();
  const phoneValid = /^\d{10}$/.test(value);
  const emailValid =
    /^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$/.test(value);
  return phoneValid || emailValid;
});

/* ===================== SEND OTP ===================== */
const sendOTP = async () => {
  formData.value.errorMessage = "";

  if (!identifierValid.value) {
    formData.value.errorMessage = "Enter a valid Email or Mobile number";
    return;
  }

  const payload = {
    identifier: formData.value.identifier,
    role: selectedRole.value
  };

  try {
    const api =
      selectedRole.value === "customer" ? axiosCustomer : axiosPartner;

    await api.post("/send-otp", payload);

    showOTPForm.value = true;
    formData.value.otp = ["", "", "", "", "", ""];

    nextTick(() => {
      document.querySelector(".otpField")?.focus();
    });
  } catch (err) {
    formData.value.errorMessage =
      err.response?.data?.message || "Failed to send OTP.";
  }
};

/* ===================== VERIFY OTP ===================== */
const verifyOTP = async () => {
  const finalOTP = formData.value.otp.join("");

  if (finalOTP.length !== 6) {
    toast("Enter valid 6-digit OTP", {
      type: "error",
      autoClose: 1000,
    });
    formData.value.errorMessage = "Enter valid 6-digit OTP";
    return;
  }

  const payload = {
    identifier: formData.value.identifier,
    otp: finalOTP,
    role: selectedRole.value
  };

  try {
    let response;

    if (selectedRole.value === "customer") {
      response = await axiosCustomer.post("/verify-otp", payload);

      const { token, user } = response.data;
      setCustomerLogin(token, user);
      localStorage.setItem("customer_token", token);
      localStorage.setItem("customer_data", JSON.stringify(user));
      axiosCustomer.defaults.headers.common.Authorization = `Bearer ${token}`;
      isCustomerLoggedIn.value = true;

      await new Promise(resolve => setTimeout(resolve, 100));

      // Sync localStorage cart/wishlist to backend
      try {
        console.log('Starting sync of localStorage data...');
        const syncResult = await syncLocalDataToBackend(axiosCustomer);
        console.log('Sync result:', syncResult);

        if (syncResult.totalSynced > 0) {
          toast.success(`Synced ${syncResult.totalSynced} item(s) to your account!`, {
            autoClose: 3000,
          });
        } else {
          console.log('No items to sync or sync completed with 0 items');
        }
      } catch (syncError) {
        console.error('Error syncing localStorage data:', syncError);
        console.error('Sync error details:', syncError.response?.data || syncError.message);
        // Don't block login if sync fails, but log the error
        toast.error('Failed to sync cart/wishlist items. Please try again.', {
          autoClose: 3000,
        });
      }
      window.dispatchEvent(new Event("user-logged-in"));
      toast("Customer login successful!", {
        type: "success",
        autoClose: 1000,
      });
    } else {
      response = await axiosPartner.post("/verify-otp", payload);

      const { token, user } = response.data;
      setPartnerLogin(token, user);
      localStorage.setItem("partner_token", token);
      localStorage.setItem("partner_data", JSON.stringify(user));
      axiosPartner.defaults.headers.common.Authorization = `Bearer ${token}`;
      isPartnerLoggedIn.value = true;

      toast("Partner login successful!", {
        type: "success",
        autoClose: 1000,
      });

      if (route.name === "products") {
        router.push({ name: "partnerProducts" });
      }
    }

    closeModal();
  } catch (err) {
    toast(
      err.response?.data?.message || "OTP verification failed!",
      {
        type: "error",
        autoClose: 1000,
      }
    );
  }
};

/* ===================== OTP INPUT HANDLING ===================== */
const handleOtpInput = (value, index) => {
  formData.value.otp[index] = value.replace(/\D/g, "");
  if (value && index < 5) {
    nextTick(() => {
      document.querySelectorAll(".otpField")[index + 1]?.focus();
    });
  }
};

const handleOtpKeydown = (event, index) => {
  if (event.key === "Backspace") {
    if (!formData.value.otp[index] && index > 0) {
      nextTick(() => {
        document.querySelectorAll(".otpField")[index - 1]?.focus();
      });
    }
  }
};

const goBack = () => {
  showOTPForm.value = false;
  formData.value.otp = ["", "", "", "", "", ""];
};

/* ===================== SIDEBAR ===================== */
const isSidebarOpen = ref(false);
const toggleSidebar = () => (isSidebarOpen.value = !isSidebarOpen.value);

/* ===================== LOGOUT ===================== */
const showLogoutModal = ref(false);

const logout = () => (showLogoutModal.value = true);

const confirmLogout = async () => {
  try {
    let loggedOut = false;

    // CUSTOMER LOGOUT
    if (isCustomerLoggedIn.value) {
      await axiosCustomer.post("/logout");

      localStorage.removeItem("customer_token");
      localStorage.removeItem("customer_data");

      delete axiosCustomer.defaults.headers.common["Authorization"];

      setCustomerLogout();
      updateCartAndWishlistBadges();
      loggedOut = true;
    }

    // PARTNER LOGOUT
    if (isPartnerLoggedIn.value) {
      await axiosPartner.post("/logout");

      localStorage.removeItem("partner_token");
      localStorage.removeItem("partner_data");

      delete axiosPartner.defaults.headers.common["Authorization"];
      setPartnerLogout();

      loggedOut = true;
    }
    toast("Logged out successfully!", {
      type: "success",
      autoClose: 1000,
    });
    setTimeout(() => {

      router.push({ name: "home" });
    }, 1000);

  } catch (e) {
    toast("Logout failed. Please try again.", {
      type: "error",
      autoClose: 1500,
    });
    console.error("Logout failed", e);
  } finally {
    showLogoutModal.value = false;
    resetAuthState();
  }
};

const goToProducts = () => {
  router.push({
    name: isPartnerLoggedIn.value ? "partnerProducts" : "products"
  });
};

window.addEventListener('cart-updated', () => {
  updateCartAndWishlistBadges();
});

window.addEventListener('user-logged-in', () => {
  updateCartAndWishlistBadges();
});

function updateCartAndWishlistBadges() {
  const token = localStorage.getItem("customer_token");

  // Guest user
  if (!token) {
    cartCount.value = getLocalCartCount();
    return;
  }

  // Logged-in user
  axiosCustomer
    .get("/cartCount")
    .then(response => {
      cartCount.value = response.data?.count ?? 0;
    })
    .catch(() => {
      cartCount.value = getLocalCartCount();
    });

}


function getLocalCartCount() {
  try {
    const cart = JSON.parse(localStorage.getItem('localCart') || '[]');
    return cart.reduce((total, item) => total + (item.quantity || 1), 0);
  } catch {
    return 0;
  }
}

function cancelLogout() {
  showLogoutModal.value = false;
}

const goToCart = () => {

  if (!isCustomerLoggedIn.value) {
    openLogin();
    return;
  }
  router.push({ name: 'cart' });
};


onMounted(() => {
  updateCartAndWishlistBadges(); // your existing function
});
</script>
