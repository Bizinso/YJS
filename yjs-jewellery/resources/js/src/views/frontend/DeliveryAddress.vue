<template>
  <div class="address-page" :class="{ 'processing-payment': isPaymentSuccessProcessing }">

    <div class="left-section">
      <h2 class="mainHeading">Delivery Address</h2>

      <div v-if="addresses.length === 0" class="no-address">
        No Default Address available!
      </div>

      <div v-else class="address-list">
        <div v-for="address in addresses" :key="address.id" class="address-item">
          <input 
            type="radio" 
            class="mmhsj" 
            :value="address.id" 
            v-model="selectedAddressId" 
            @change="getDeliveryCharges()" 
          />
          <label>
            <strong>{{ address.first_name }} {{ address.last_name }}</strong><br />
            {{ address.billing_address_line1 }},  
            {{ address.billing_landmark }}, 
            {{ address.city }}, {{ address.state }} - {{ address.billing_postal_code }}
          </label>
        </div>
      </div>

      <button class="GlobalfillBTN" @click="billingAddress = !billingAddress">
        Add New Address
      </button>

      <div v-if="billingAddress">
        <b-form>
  <b-row>

    <!-- Type of Address -->
    <b-col md="12">
      <b-form-group
        class="formElements multiDrop"
        label-class="required"
        label="Type Of Address"
      >
        <v-select
          placeholder="Select Address Type"
          class="multiDrop"
        />
      </b-form-group>
    </b-col>

    <!-- Salutation -->
    <b-col md="4">
      <b-form-group
        class="formElements multiDrop"
        label="Salutation"
      >
        <v-select
          placeholder="Select Salutation"
          class="multiDrop"
        />
      </b-form-group>
    </b-col>

    <!-- First Name -->
    <b-col md="4">
      <b-form-group
        class="formElements"
        label="First Name"
        label-class="required"
      >
        <b-form-input
          type="text"
          placeholder="Enter First Name"
        />
      </b-form-group>
    </b-col>

    <!-- Last Name -->
    <b-col md="4">
      <b-form-group
        class="formElements"
        label="Last Name"
        label-class="required"
      >
        <b-form-input
          type="text"
          placeholder="Enter Last Name"
        />
      </b-form-group>
    </b-col>

    <!-- Email -->
    <b-col md="6">
      <b-form-group
        class="formElements"
        label="Email"
        label-class="required"
      >
        <b-form-input
          type="email"
          placeholder="Enter Email"
        />
      </b-form-group>
    </b-col>

    <!-- Phone Number -->
    <b-col md="6">
      <b-form-group
        class="formElements"
        label="Phone Number"
        label-class="required"
      >
        <b-form-input
          type="tel"
          maxlength="10"
          placeholder="Enter Phone Number"
        />
      </b-form-group>
    </b-col>

    <!-- Address Line 1 -->
    <b-col md="12">
      <b-form-group
        class="formElements"
        label="Address Line 1"
        label-class="required"
      >
        <b-form-input
          type="text"
          placeholder="Enter Address Line 1"
        />
      </b-form-group>
    </b-col>

    <!-- Address Line 2 -->
    <b-col md="12">
      <b-form-group
        class="formElements"
        label="Address Line 2"
      >
        <b-form-input
          type="text"
          placeholder="Enter Address Line 2"
        />
      </b-form-group>
    </b-col>

    <!-- Landmark -->
    <b-col md="6">
      <b-form-group
        class="formElements"
        label="Landmark"
      >
        <b-form-input
          type="text"
          placeholder="Enter Landmark"
        />
      </b-form-group>
    </b-col>

    <!-- Pincode -->
    <b-col md="6">
      <b-form-group
        class="formElements"
        label="Pincode"
        label-class="required"
      >
        <b-form-input
          type="text"
          maxlength="6"
          placeholder="Enter Pincode"
        />
      </b-form-group>
    </b-col>

    <!-- Country -->
    <b-col md="6">
      <b-form-group
        class="formElements multiDrop"
        label="Country"
        label-class="required"
      >
        <v-select
          placeholder="Select Country"
          class="multiDrop"
        />
      </b-form-group>
    </b-col>

    <!-- State -->
    <b-col md="6">
      <b-form-group
        class="formElements multiDrop"
        label="State"
      >
        <v-select
          placeholder="Select State"
          class="multiDrop"
        />
      </b-form-group>
    </b-col>

    <!-- City -->
    <b-col md="6">
      <b-form-group
        class="formElements multiDrop"
        label="City"
      >
        <v-select
          placeholder="Select City"
          class="multiDrop"
        />
      </b-form-group>
    </b-col>

    <!-- Submit Button -->
    <b-col md="12" class="mt-3">
      <b-button type="submit" variant="primary">
        Submit Address
      </b-button>
    </b-col>

  </b-row>
</b-form>

      </div>

      <div class="remark-box">
        <label>Special Remark <span>(If Any)</span></label>
        <textarea 
        
         rows="3" class="d-block w-100 p-2"></textarea>
      </div>
    </div>

    <div class="right-section">
      <h3 class="mainHeading">
        Order Summary <span>({{ cartItems.length }} Items)</span>
      </h3>

      <table class="summary-table">
        <thead>
          <tr>
            <th style="text-align: left">Item</th>
            <th style="text-align: center">Qty</th>
            <th style="text-align: right">Price</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(item, index) in cartItems" :key="index">
            <td>
              {{ item.product?.name || "N/A" }}
              <span v-if="item.is_free_product == 1" class="free-label"> (Free Product)</span>
            </td>
            <td class="text-center">{{ item.quantity || 0 }}</td>
            <td class="text-right">
              ₹ {{ Math.round((item.base_price || 0) * (item.quantity || 1)).toLocaleString('en-IN') }}
            </td>
          </tr>

          <tr>
            <td colspan="2">Subtotal</td>
            <td class="text-right">
              ₹ {{ Math.round(carttotal || 0).toLocaleString('en-IN') }}
            </td>
          </tr>

          <tr>
            <td colspan="2">Total Charges</td>
            <td class="text-right">
              ₹ {{ Math.round(cartSummaryData.total_charges || 0).toLocaleString('en-IN') }}
            </td>
          </tr>

          <tr>
            <td colspan="2">Delivery Charges</td>
            <td class="text-right">
              ₹ {{ Math.round(cartSummaryData.delivery_charges || 0).toLocaleString('en-IN') }}
            </td>
          </tr>

          <tr v-if="(cartSummaryData.cart_total_discount || totalDiscount) > 0" class="offer-row">
            <td class="negativeValue" colspan="2">Discounts / Offers</td>
            <td class="text-right negativeValue">
              - ₹ {{ Math.round(cartSummaryData.cart_total_discount || totalDiscount || 0).toLocaleString('en-IN') }}
            </td>
          </tr>

          <tr>
            <td colspan="2">Taxes</td>
            <td class="text-right">
              ₹ {{ Math.round(cartSummaryData.total_taxes || 0).toLocaleString('en-IN') }}
            </td>
          </tr>

          <tr class="total-row">
            <td colspan="2"><strong>Total (Inclusive of tax)</strong></td>
            <td class="text-right" style="white-space: nowrap;">
              <strong>₹ {{ Math.round(grandTotal || 0).toLocaleString('en-IN') }}</strong>
            </td>
          </tr>
        </tbody>
      </table>

      <div v-if="paymentErrorMessage" class="payment-error-message">
        <strong>⚠️ Payment Failed:</strong> {{ paymentErrorMessage }}
      </div>

      <button 
        class="btn place-order-btn" 
        @click="goToOrderSuccess"        
        :disabled="isPaymentProcessing || isOrderPlaced"
        >
        <!-- @click="payWithRazorpay" -->
        {{ isPaymentProcessing ? 'Processing Payment...' : isOrderPlaced ? 'Order Placed' : 'Place Order' }}
      </button>
    </div>

  </div>
</template>

<script setup>
import { ref, onMounted, computed } from "vue";
import { useRouter } from "vue-router";
import { toast } from "vue3-toastify";
import "vue3-toastify/dist/index.css";

const router = useRouter();

const typeoption = ["Home", "Office", "Other"];
// State
const addresses = ref([]);
const selectedAddressId = ref(null);
const specialRemark = ref("");
const isLoading = ref(false);
const loadingMessage = ref("Loading...");
const isPaymentProcessing = ref(false);
const isOrderPlaced = ref(false);
const paymentErrorMessage = ref("");
const isPaymentSuccessProcessing = ref(false);
const cartItems = ref([]);
const billingAddress= ref(false)
const cartSummaryData = ref({
  cart_total_discount: 0,
  total_charges: 0,
  total_taxes: 0,
  delivery_charges: 0,
  grand_total: 0,
  cart_subtotal: 0,
  courier_id: null,
  courier_name: null,
  delivery_date: null,
  courier_charges: 0
});

// Computed
const carttotal = computed(() =>
  cartItems.value.reduce((sum, item) =>
    sum + parseFloat(item.base_price || 0) * (item.quantity || 1), 0)
);

const totalCharges = computed(() =>
  cartItems.value.reduce((sum, item) =>
    item.is_free_product ? sum : sum + parseFloat(item.charges_total || 0), 0)
);

const totalTaxes = computed(() =>
  cartItems.value.reduce((sum, item) =>
    item.is_free_product ? sum : sum + parseFloat(item.taxes_total || 0), 0)
);

const totalDiscount = computed(() =>
  cartItems.value.reduce((sum, item) =>
    sum + parseFloat(item.total_discount || 0), 0)
);

const grandTotal = computed(() => {
  const subtotal = carttotal.value;
  const charges = parseFloat(cartSummaryData.value.total_charges || totalCharges.value || 0);
  const taxes = parseFloat(cartSummaryData.value.total_taxes || totalTaxes.value || 0);
  const delivery = parseFloat(cartSummaryData.value.delivery_charges || 0);
  const discount = parseFloat(cartSummaryData.value.cart_total_discount || totalDiscount.value || 0);
  const total = subtotal + charges + taxes + delivery - discount;
  return total < 0 ? 0 : Math.round(total);
});

// Helper Functions
const setLoading = (show, message = "Loading...") => {
  isLoading.value = show;
  loadingMessage.value = message;
};

const resetPaymentState = () => {
  isPaymentProcessing.value = false;
  isPaymentSuccessProcessing.value = false;
  isLoading.value = false;
  isOrderPlaced.value = false;
};

const calculateMetalGemstonePrice = (item) => {
  const product = item.product || {};
  const quantity = parseInt(item.quantity) || 1;
  const metalWeight = parseFloat(product.metal_weight || 0);
  const metalRatePerGram = parseFloat(product.metal_rate_per_gram || 0);
  const metalPrice = metalRatePerGram > 0 ? (metalRatePerGram * metalWeight) * quantity : 0;
  const gemstoneWeight = parseFloat(product.gemstone_weight || 0);
  const gemstoneRatePerCarat = parseFloat(product.gemstone_rate_per_carat || 0);
  const gemstonePrice = gemstoneRatePerCarat > 0 ? (gemstoneRatePerCarat * gemstoneWeight) * quantity : 0;
  
  return {
    metal_weight: metalWeight,
    metal_price: Math.round(metalPrice * 100) / 100,
    gemstone_weight: gemstoneWeight,
    gemstone_price: Math.round(gemstonePrice * 100) / 100,
  };
};

// Reusable Payment Logging Function
const logPayment = async (data) => {
  try {
    await axios.post("/razorpay/log-payment", {
      razorpay_order_id: data.razorpay_order_id || null,
      razorpay_payment_id: data.razorpay_payment_id || null,
      razorpay_signature: data.razorpay_signature || null,
      payment_status: data.payment_status || 'pending',
      amount: data.amount || Math.round(grandTotal.value),
      currency: data.currency || 'INR',
      payment_method: data.payment_method || null,
      error_message: data.error_message || null,
      error_code: data.error_code || null,
      request_payload: data.request_payload || null,
      response_payload: data.response_payload || null,
      verification_response: data.verification_response || null,
      payment_details: data.payment_details || null,
      remarks: data.remarks || null,
    });
  } catch (err) {
    console.error('Failed to log payment:', err);
  }
};

// API Functions
const fetchAddresses = async () => {
  try {
    setLoading(true, "Loading addresses...");
    const res = await axios.get("/getAddress");
    addresses.value = res.data.data || [];
    if (addresses.value.length) {
      // Select default address first, otherwise select first address
      const defaultAddress = addresses.value.find(addr => addr.is_default_billing);
      selectedAddressId.value = defaultAddress ? defaultAddress.id : addresses.value[0].id;
      await getDeliveryCharges();
    } else {
      setLoading(false);
    }
  } catch (err) {
    console.error(err);
    setLoading(false);
    toast.error("Failed to load addresses");
  }
};

const getDeliveryCharges = async () => {
  if (!selectedAddressId.value) {
    setLoading(false);
    return;
  }
  try {
    setLoading(true, "Calculating delivery charges...");
    await axios.get(`/getDeliveryCharges/${selectedAddressId.value}`);
    await fetchCartSummary();
  } catch (err) {
    console.error(err);
    setLoading(false);
    toast.error("Failed to calculate delivery charges");
  }
};

const fetchCartSummary = async () => {
  try {
    setLoading(true, "Calculating order summary...");
    
    const buyNowItem = localStorage.getItem("buy_now_item");
    let res;

    if (buyNowItem) {
      const item = JSON.parse(buyNowItem);
      res = await axios.get("/getCartData", {
        params: {
          buynow: true,
          product_id: item.product_id,
          quantity: item.quantity,
        }
      });
    } else {
      res = await axios.get("/getCartData");
    }
    
    if (!res?.data) {
      throw new Error("Invalid response from server");
    }
    
    const items = res.data.items || [];
    cartItems.value = items.filter((item) => item.product !== null);

    if (buyNowItem) {
      const buyNowData = JSON.parse(buyNowItem);
      cartItems.value = cartItems.value.filter(item => item.product?.id === buyNowData.product_id);
      if (cartItems.value.length > 0) {
        cartItems.value[0].quantity = parseInt(buyNowData.quantity) || 1;
      }
    } else {
      cartItems.value.forEach(item => {
        item.quantity = parseInt(item.quantity) || 1;
      });
    }

    if (cartItems.value.length === 0 && !buyNowItem) {
      toast.warning("Your cart is empty. Please add items to cart.");
      setLoading(false);
      return;
    }

    cartSummaryData.value = {
      cart_total_discount: parseFloat(res.data.cart_total_discount || 0),
      total_charges: parseFloat(res.data.total_charges || 0),
      total_taxes: parseFloat(res.data.total_taxes || 0),
      delivery_charges: parseFloat(res.data.delivery_charges || 0),
      grand_total: parseFloat(res.data.grand_total || 0),
      courier_id: res.data.courier_id,
      courier_name: res.data.courier_name,
      courier_charges: parseFloat(res.data.courier_charges || 0),
      delivery_date: res.data.delivery_date,
      cart_subtotal: parseFloat(res.data.cart_subtotal || 0),
      subtotal: carttotal.value,
    };
    
    setLoading(false);
  } catch (err) {
    console.error("Cart summary error:", err);
    setLoading(false);
    toast.error("Failed to load cart summary: " + (err.response?.data?.message || err.message || "Unknown error"));
    
    cartItems.value = [];
    cartSummaryData.value = {
      cart_total_discount: 0,
      total_charges: 0,
      total_taxes: 0,
      delivery_charges: 0,
      grand_total: 0,
      cart_subtotal: 0,
      courier_id: null,
      courier_name: null,
      delivery_date: null,
      courier_charges: 0
    };
  }
};


// Razorpay Payment Handler
const payWithRazorpay = async () => {
  if (isPaymentProcessing.value || isOrderPlaced.value) return;

  if (!selectedAddressId.value) {
    toast.error("Please select a delivery address");
    return;
  }

  if (cartItems.value.length === 0) {
    toast.error("Your cart is empty. Please add items to cart.");
    return;
  }

  try {
    isPaymentProcessing.value = true;
    paymentErrorMessage.value = "";
    const amount = Math.round(grandTotal.value);

    const { data } = await axios.post("/razorpay/order", { amount });

    const options = {
      key: data.razorpay_key,
      amount: data.amount,
      currency: data.currency,
      name: "My Shop",
      description: "Order Payment",
      order_id: data.order_id,
      handler: handlePaymentSuccess,
      prefill: {
        name: "John Doe",
        email: "john@example.com",
        contact: "9999999999",
      },
       modal: {
            ondismiss: async function () {
              isPaymentProcessing.value = false;
              isOrderPlaced.value = false;
              toast.warning("Payment was not completed. Please try again.");
              },
            },
      theme: { color: "#528FF0" },
    };

    const rzp = new window.Razorpay(options);
    rzp.on('payment.failed', handlePaymentFailed);
    rzp.on('payment.cancelled', handlePaymentCancelled);
    rzp.open();
  } catch (err) {
    isPaymentProcessing.value = false;
    await logPayment({
      payment_status: 'failed',
      amount: Math.round(grandTotal.value),
      currency: 'INR',
      error_message: err.response?.data?.message || err.message || "Failed to start payment",
      error_code: err.response?.status || null,
      request_payload: { amount: Math.round(grandTotal.value) },
      remarks: 'Failed to initialize payment'
    });
    toast.error("Failed to start payment");
  }
};

const goToOrderSuccess = () => {
  router.push("/order-success");
};
// Payment Success Handler
const handlePaymentSuccess = async function (response) {
  let preventRefresh = null;
  
  try {
    if (isOrderPlaced.value) {
      toast.info("Order already placed. Redirecting...");
      router.push("/products/all");
      return;
    }

    isPaymentSuccessProcessing.value = true;
    setLoading(true, "Please wait, payment successful. Do not refresh the page...");
    
    preventRefresh = (e) => {
      e.preventDefault();
      e.returnValue = "Payment is being processed. Please do not refresh or close this page.";
      return e.returnValue;
    };
    window.addEventListener("beforeunload", preventRefresh);

    const amount = Math.round(grandTotal.value);
    const verifyRes = await axios.post("/razorpay/verify", {
      ...response,
      amount,
      currency: 'INR',
    });

    if (verifyRes.data.status === "success") {
      isOrderPlaced.value = true;
      setLoading(true, "Payment verified! Placing your order. Please wait...");

      if (!cartSummaryData.value.delivery_date) {
        resetPaymentState();
        window.removeEventListener("beforeunload", preventRefresh);
        toast.error("Delivery date is not available. Please select address again.");
        return;
      }
      
      const buyNowItem = localStorage.getItem("buy_now_item");
      const payload = {
        billing_address_id: selectedAddressId.value,
        shipping_address_id: selectedAddressId.value,
        special_remark: specialRemark.value,
        items: cartItems.value.map((item) => {
          const metalGemstoneData = calculateMetalGemstonePrice(item);
          return {
            product_id: item.product.id,
            quantity: item.quantity,
            applied_offers: item.applied_offers,
            offer_ids: (item.offers || []).map((o) => o.id),
            base_price: item.base_price || item.product?.base_price || 0,
            charges_total: item.charges_total || 0,
            taxes_total: item.taxes_total || 0,
            cart_total: item.cart_total || 0,
            item_total_discount: item.item_total_discount || 0,
            final_price_per_unit: item.final_price_per_unit || item.base_price || 0,
            metal_weight: metalGemstoneData.metal_weight,
            metal_price: metalGemstoneData.metal_price,
            gemstone_weight: metalGemstoneData.gemstone_weight,
            gemstone_price: metalGemstoneData.gemstone_price,
          };
        }),
        cart_total_discount: cartSummaryData.value.cart_total_discount,
        total_charges: cartSummaryData.value.total_charges,
        total_taxes: cartSummaryData.value.total_taxes,
        delivery_charges: cartSummaryData.value.delivery_charges,
        courier_id: cartSummaryData.value.courier_id,
        courier_name: cartSummaryData.value.courier_name,
        courier_charges: cartSummaryData.value.courier_charges,
        delivery_date: cartSummaryData.value.delivery_date,
        order_subtotal: carttotal.value,
        razorpay_order_id: response.razorpay_order_id,
        razorpay_payment_id: response.razorpay_payment_id,
        razorpay_signature: response.razorpay_signature,
        response_payload: response,
        payment_method: verifyRes.data.payment_method,
        payment_details: verifyRes.data.payment_details,
        verification_response: verifyRes.data,
        grand_total: amount,
        buyNowItem: buyNowItem,
      };

      await axios.post("orders/place", payload);
      
      setLoading(true, "Order placed successfully! Redirecting...");
      toast.success("Order placed successfully! 🎉", { autoClose: 2000 });
      localStorage.removeItem("buy_now_item");
      
      window.removeEventListener("beforeunload", preventRefresh);
      
      setTimeout(() => {
        router.push("/products/all");
      }, 1500);
    } else {
      resetPaymentState();
      window.removeEventListener("beforeunload", preventRefresh);
      
      const errorMsg = verifyRes.data.message || 'Payment verification failed. Please try again.';
      paymentErrorMessage.value = errorMsg;
      
      await logPayment({
        ...response,
        payment_status: 'failed',
        amount,
        currency: 'INR',
        error_message: errorMsg,
        response_payload: response,
        verification_response: verifyRes.data,
        remarks: 'Payment verification failed - order not created'
      });
      
      toast.error(errorMsg, { autoClose: 5000 });
    }
  } catch (err) {
    resetPaymentState();
    
    if (preventRefresh) {
      window.removeEventListener("beforeunload", preventRefresh);
    }
    
    const errorMsg = err.response?.data?.message || err.message || "Payment verification error. Please try again.";
    paymentErrorMessage.value = errorMsg;
    
    await logPayment({
      razorpay_order_id: response?.razorpay_order_id || null,
      razorpay_payment_id: response?.razorpay_payment_id || null,
      razorpay_signature: response?.razorpay_signature || null,
      payment_status: 'failed',
      amount: Math.round(grandTotal.value),
      currency: 'INR',
      error_message: errorMsg,
      error_code: err.response?.status || null,
      response_payload: response || null,
      verification_response: err.response?.data || {},
      remarks: 'Payment verification exception'
    });
    
    toast.error(errorMsg, { autoClose: 5000 });
  }
};

// Payment Failed Handler
const handlePaymentFailed = async function (response) {
  isPaymentProcessing.value = false;
  isOrderPlaced.value = false;
  
  const errorMsg = response.error?.description || response.error?.reason || 'Payment failed. Please try again.';
  paymentErrorMessage.value = errorMsg;
  
  await logPayment({
    razorpay_order_id: response.razorpay_order_id || null,
    razorpay_payment_id: response.razorpay_payment_id || null,
    razorpay_signature: response.razorpay_signature || null,
    payment_status: 'failed',
    amount: Math.round(grandTotal.value),
    currency: 'INR',
    error_message: errorMsg,
    error_code: response.error?.code || null,
    response_payload: response,
    payment_details: response,
    remarks: 'Payment failed from Razorpay'
  });
  
  toast.error(errorMsg, { autoClose: 5000 });
};

// Payment Cancelled Handler
const handlePaymentCancelled = async function (response) {
  isPaymentProcessing.value = false;
  isOrderPlaced.value = false;
  paymentErrorMessage.value = "";
  
  await logPayment({
    razorpay_order_id: response.razorpay_order_id || null,
    razorpay_payment_id: response.razorpay_payment_id || null,
    razorpay_signature: response.razorpay_signature || null,
    payment_status: 'cancelled',
    amount: Math.round(grandTotal.value),
    currency: 'INR',
    response_payload: response,
    payment_details: response,
    remarks: 'Payment cancelled by user'
  });
  
  toast.info("Payment cancelled.", { autoClose: 3000 });
};

onMounted(() => {
  fetchAddresses();
  fetchCartSummary();
});
</script>

<style lang="scss">
.address-page {
  display: flex;
  gap: 40px;
  padding: 30px;
  background: #ffd58d4a;
  flex-wrap: wrap;

  .left-section {
    flex: 1;
    min-width: 300px;
  }

  .right-section {
    flex: 1;
    min-width: 300px;
    background: white;
    padding: 20px;
    border: 1px solid #eee;
    border-radius: 8px;
  }

  .address-item {
    margin-bottom: 15px;
    padding: 10px;
    border-bottom: 1px solid #ccc;
    background: white;
    display: flex;
    align-items: flex-start;
    gap: 10px;
    margin: 10px 0 !important;
    border-radius: 8px;
  }

  .mmhsj {
    margin-top: 5px !important;
  }

  .btn {
    padding: 10px 16px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
  }

  .add-btn {
    background-color: #404054;
    color: white;
    margin-top: 10px;
  }

  .place-order-btn {
    border: 1px solid #404054;
    color: #404054;
    background: white;
    width: 100%;
    margin-top: 20px;
    transition: all 0.3s ease;
    cursor: pointer;

    &:hover:not(:disabled) {
      background: #404054;
      color: white;
    }

    &:disabled {
      opacity: 0.6;
      cursor: not-allowed;
      background: #f5f5f5;
      color: #999;
      border-color: #ddd;
    }
  }

  .remark-box {
    margin-top: 20px;
  }

  .summary-table {
    width: 100%;
    border-collapse: collapse;

    td {
      padding: 10px 0;
      border-bottom: 1px solid #eee;
    }
  }

  .total-row td {
    font-size: 18px;
  }

  .text-right {
    text-align: right;
  }

  .offer-row td {
    color: green;
  }

  .mainHeading {
    color: #000;
    text-align: left;
    font-family: "Gordita", sans-serif;
    font-size: 20px;
    font-style: normal;
    font-weight: 500;
    line-height: 160%;
    letter-spacing: 0.4px;
    margin: 0 0 16px 0;
  }

  .free-label {
    color: #28a745;
    font-weight: 600;
    font-size: 0.85rem;
  }

  .payment-error-message {
    background-color: #fee;
    border: 2px solid #fcc;
    border-radius: 8px;
    padding: 12px 16px;
    margin-top: 20px;
    margin-bottom: 15px;
    color: #c33;
    display: flex;
    justify-content: space-between;
    align-items: center;
    animation: slideIn 0.3s ease-out;

    strong {
      margin-right: 8px;
    }
  }

  .processing-payment {
    pointer-events: none;
    opacity: 0.7;
    user-select: none;
  }
}

@keyframes slideIn {
  from {
    opacity: 0;
    transform: translateY(-10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}
</style>
