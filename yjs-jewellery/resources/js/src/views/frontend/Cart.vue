<template>
  <div>


    <div class="cartHeadingBox">
      <nav class="breadcrumb postts">
        <a href="/" class="crumb">Home</a>
        <span class="sep">›</span>
        <span class="crumb active">Cart</span>
      </nav>
      <h2 class="text-black">Cart</h2>
    </div>
    <div class="nodata" v-if="itemLength">
      <div class="noCartData">
        <img src="../assets/img/noDataCart.svg" alt="div 1" />
        <h5>Your cart is empty.</h5>
        <p>Looks like you haven’t added any jewelry to your cart yet.</p>
      </div>
    </div>

    <div class="container py-5 cartDetails" v-else>
      <div class="row mb-4">
        <div class="col-12 col-md-8">
          <div class="cartBoxHeading">
            <h2>item</h2>
          </div>

          <span class="dividers"></span>

          <div class="cartItems" v-for="item in cartItems" :key="item.id">
            <div class="itemImage">
              <div class="productImg" @click="goToProductDetails(item.product)" style="cursor: pointer">
                <img :src="item.product.main_image
                  ? `/storage/${item.product.main_image}`
                  : fallbackImage
                  " alt="Product Image" />
              </div>
            </div>
            <div class="itemDetails">
              <div class="itemNamePrice">
                <h2>{{ item.product.name }}
                  <span v-if="item.is_free_product == 1"> ( Free Product )</span>
                </h2>
                <h3>₹{{ (Math.floor(item.product.base_price || 0) * (item.quantity || 1)).toLocaleString() }}</h3>
              </div>
              <div v-if="item.applied_offers && item.applied_offers.length" class="appliedOffers">
                <h5>Applied Offers:</h5>
                <ul>
                  <li v-for="offer in item.applied_offers" :key="offer.id">
                    <template
                      v-if="offer.type && (offer.type.toUpperCase() === 'BOGO' || offer.type.toUpperCase() === 'FREEBIE')">
                      {{ offer.title }}

                      <!-- <span v-else-if="offer.discount && parseFloat(offer.discount) > 0">
                        : -₹{{ Math.floor(offer.discount).toLocaleString() }}
                      </span> -->
                    </template>

                    <template v-else>
                      {{ offer.title }}
                      <!-- <span v-if="offer.discount && parseFloat(offer.discount) > 0">
                        : -₹{{ Math.floor(offer.discount).toLocaleString() }}
                      </span> -->
                    </template>
                  </li>
                </ul>
              </div>
              <div class="itemCount" v-if="item.is_free_product == 0">
                <div class="d-flex align-items-center gap-3">
                  <!-- Decrease -->
                  <button class="quantityPlusBtn" @click="decreaseQty(item)" :disabled="item.quantity <= 1">
                    -
                  </button>

                  <!-- Quantity input -->
                  <input type="text" class="bracketBoc text-center" style="width: 60px" v-model.number="item.quantity"
                    @change="item.quantity > 0 ? updateCartQuantity(item) : removeFromCart(item)" min="0" readonly />

                  <!-- Increase -->
                  <button class="quantityPlusBtn" :disabled="item.quantity >= item.product.remaining_stock"
                    @click="increaseQty(item)">
                    +
                  </button>
                </div>

                <!-- INLINE ERROR MESSAGE -->
                <p v-if="errorMessage" class="text-danger mt-2 fw-bold">
                  {{ errorMessage }}
                </p>
                <!-- Stock messages -->
                <p v-if="item.product.remaining_stock === 0" class="text-danger small fw-bold">
                  Out of stock
                </p>

                <p v-else-if="item.product.remaining_stock <= item.product.low_stock" class="text-danger small">
                  Only {{ item.product.remaining_stock }} left in stock!
                </p>

              </div>

              <div class="itemAction">
                <div class="itemShareWish">
                  <WishlistButton :productId="item.product.id" />
                  <h2 class="textDivider"><span>|</span></h2>
                  <h2 @click="openShare(item.product)" style="cursor: pointer">
                    <img src="../assets/img/share.svg" alt="div 1" />
                    <span>Share</span>
                  </h2>
                  <h2 class="textDivider"><span>|</span></h2>
                </div>
                <h2 class="itemDelete" @click="openDeleteModal(item)">
                  <img src="../assets/img/delete.svg" alt="div 1" />
                  <span>Delete</span>
                </h2>
              </div>
            </div>
          </div>

          <span class="dividers"></span>

          <div class="itemSubtotal">
            <h5 class="itemSubtotalTitle">Item Subtotal:</h5>
            <h5 class="itemSubtotalAMT">₹{{ Math.round(cartSubtotal).toLocaleString('en-IN') }}</h5>
          </div>
        </div>
        <div class="col-12 col-md-4 sideCartDetails">
          <button class="viewCoupons" @click="sidebarstatus.filter = true">
            Apply Coupons
            <img src="../assets/img/coupon.svg" alt="div 1" />
          </button>


          <div class="cartSummary">
            <div class="priceSector">
              <h5>Cart Summary</h5>
              <span class="dividers"></span>

              <div class="priceBreackout">
                <h3 class="priceValues">Subtotal:</h3>
                <h4 class="priceValues boldOne">₹{{ Math.round(cartSubtotal).toLocaleString('en-IN') }} </h4>
              </div>
              <div class="priceBreackout" v-if="totalCharges > 0">
                <h3 class="priceValues">Charges:</h3>
                <h4 class="priceValues boldOne">₹{{ Math.round(totalCharges).toLocaleString('en-IN') }} </h4>
              </div>

              <div class="priceBreackout" v-if="totalDiscount > 0">
                <h3 class="priceValues ">Discount:</h3>
                <h4 class="priceValues boldOne  negativeValue">- ₹{{ Math.round(totalDiscount).toLocaleString('en-IN')
                }}
                </h4>
              </div>


              <div class="priceBreackout" v-if="totalTaxes > 0">
                <h3 class="priceValues mb-0 ">Tax:</h3>
                <h4 class="priceValues boldOne mb-0">₹{{ Math.round(totalTaxes).toLocaleString('en-IN') }}
                </h4>
              </div>

              <span class="dividers dotted"></span>

              <div class="priceBreackout">
                <h3 class="priceValues boldOne">Total:</h3>
                <h4 class="priceValues boldOne">₹{{ Math.round(grandTotal).toLocaleString('en-IN') }} </h4>
              </div>
              <ul class="delDate">
                <li>Estimated Delivery: 3-5 business days</li>
              </ul>

              <div class="d-grid gap-2">
                <button class="buyNowBtn" @click="handleCheckout">
                  Proceed To Checkout
                </button>
                <button class="addCartBtn mb-0" @click="continueShopping">
                  Continue Shopping
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <RelatedProducts :productIds="productIDS" />


    <b-modal id="delete-modal" v-model="showDeleteModal" hide-header hide-footer centered size="md" class="text-center">
      <h6>Are you sure you want to remove this item from your cart?</h6>

      <b-button class="btn_secondary_border me-2" @click="showDeleteModal = false">Cancel</b-button>
      <b-button class="btn_primary" @click="confirmDelete">Delete</b-button>
    </b-modal>


  </div>
</template>


<style>
.carousel__track {
  gap: 16px;
}

.carousel__prev {
  left: -5%;
}

.carousel__next {
  right: -5%;
}

.quantityPlusBtn {
  width: 32px;
  height: 32px;
  border: none;
  border-radius: 6px;
  background-color: #B44536;
  color: #fff;
  font-size: 20px;
  font-weight: bold;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
}

.quantityBtn:hover {
  background-color: hsl(7, 60%, 45%);
}

.quantityInput {
  width: 50px;
  height: 32px;
  text-align: center;
  border: 1px solid #ccc;
  border-radius: 6px;
  font-size: 16px;
}

.shareIcon {
  width: 40px;
  height: 40px;
  cursor: pointer;
  transition: transform 0.2s;
}

.shareIcon:hover {
  transform: scale(1.2);
}

.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 2000;
}

.modal-content {
  background: white;
  padding: 20px;
}

.modal-content ul {
  list-style: none;
  padding: 0;
}

.modal-content li {
  padding: 8px;
  border: 1px solid #ccc;
  margin-bottom: 6px;
  cursor: pointer;
  border-radius: 6px;
}

.modal-content li:hover {
  background: #f5f5f5;
}
</style>
<script setup>
import { ref, onMounted } from "vue";
import axiosCustomer from "@axiosCustomer";

const cartItems = ref([]);
const cartSubtotal = ref(0);
const carttotal = ref(0);
const totalCharges = ref(0);
const totalTaxes = ref(0);
const grandTotal = ref(0);

const fetchCart = async () => {
  const res = await axiosCustomer.get(`/cart`);
  cartItems.value = res.data.data;

  cartSubtotal.value = Number(res.data.cart_subtotal || 0);
  carttotal.value = Number(res.data.carttotal || 0);
  totalCharges.value = Number(res.data.total_charges || 0);
  totalTaxes.value = Number(res.data.total_taxes || 0);
  grandTotal.value = carttotal.value;

};

onMounted(fetchCart);
</script>
