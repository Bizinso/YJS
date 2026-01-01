<template>
  <div class="yjs_product" v-if="!loading">
    <!-- Breadcrumbs -->
    <!-- <div class="global_breadcrumbs">
      <div class="container">
        <ul class="breadcrumb">
          <li>
            <a :href="isPartnerLoggedIn ? '/partner/products' : '/products'">
              Our Products
            </a>
          </li>

          <li><a href="#">{{ product?.category || '' }}</a></li>
          <li><a href="#">{{ product?.subcategory || '' }}</a></li>
          <li>{{ product?.name || '' }}</li>
        </ul>
      </div>
    </div> -->

    <div class="breadbox container">
        <nav class="breadcrumb  global_breadcrumbs postts">
          <a href="/" class="crumb">Home</a>
          <!-- <span class="sep">›</span>
          <router-link :to="{ name: 'products' }" class="crumb">Our Products</router-link> -->
          <span class="sep">›</span>
          <router-link :to="{ name: 'products' }" class="crumb">{{ product?.category || '' }}</router-link>
          <span class="sep">›</span>
          <!-- Subcategory Commented -->
          <router-link :to="{ name: 'products' }" class="crumb">{{ product?.subcategory || '' }}</router-link>
          <span class="sep">›</span>
          <span class="crumb active">{{ product?.name || '' }}</span>
        </nav>
      </div>

    <div class="container productDetails">
      <b-row>
        <!-- Left Column: Images -->
        <!-- <b-col md="5">
          <div class="main-image-wrapper position-relative">
            <img ref="mainImg" :src="images[currentIndex]?.src" :alt="images[currentIndex]?.alt || 'product image'"
              class="img-fluid main-image" @mousemove="onMouseMove" @mouseenter="onMouseEnter"
              @mouseleave="onMouseLeave" @click="openModal" style="cursor: zoom-in;" />
            <div v-if="showLens" class="zoom-window" :style="zoomWindowStyle" aria-hidden="true"></div>
          </div>

          <div v-if="isModalOpen" class="zoom-modal" @click.self="closeModal" role="dialog" aria-modal="true">
            <button class="btn-closed position-absolute top-3 end-3" @click="closeModal"
              aria-label="Close">Close</button>
            <div class="zoom-modal-body d-flex align-items-center justify-content-center">
              <img :src="images[currentIndex]?.src" :alt="images[currentIndex]?.alt" class="img-fluid zoomed-image" />
            </div>
          </div>

          <div class="d-flex gap-2 thumbnails-wrapper">
            <button v-for="(img, i) in images" :key="i" class="thumb-btn btn p-0 border-0"
              :class="{ active: i === currentIndex }" @click="setIndex(i)" :title="img.alt || `View ${i + 1}`">
              <img :src="img.thumb" :alt="img.alt || `thumb ${i + 1}`" class="img-thumbnail"
                style="width:80px; height:80px; object-fit:cover;" />
            </button>
          </div>
        </b-col> -->

        <b-col md="5">
          <div class="zoom-container">
            <!-- MAIN PREVIEW -->
            <div
              v-if="selectedMedia"
              class="main-preview position-relative"
              @mousemove="selectedMedia.type === 'image' && handleZoom"
              @mouseleave="hideZoom"
            >
              <!-- IMAGE -->
              <!-- <img
                v-if="selectedMedia.type === 'image'"
                ref="imageRef"
                :src="selectedMedia.url"
                class="img-fluid preview-image"
                loading="lazy"
                alt="Product Image"
              /> -->
              <div
                v-if="selectedMedia.type === 'image'"
                class="image-wrapper"
                @mousemove="handleZoom"
                @mouseenter="zoomVisible = true"
                @mouseleave="resetZoom"
              >
                <img
                  :src="selectedMedia.url"
                  class="img-fluid preview-image"
                  ref="imageRef"
                />
              </div>

              <!-- LOCAL VIDEO -->
              <video
                v-else-if="selectedMedia.type === 'video' && isVideoFile(selectedMedia.url)"
                :src="selectedMedia.url"
                controls
                class="img-fluid preview-image"
              />

              <!-- EMBED VIDEO -->
              <iframe
                v-else
                :src="getEmbedUrl(selectedMedia.url)"
                frameborder="0"
                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                allowfullscreen
                class="img-fluid preview-image"
              ></iframe>

              <!-- NAV -->
              <button class="nav-btn prev" @click.stop="prevMedia">‹</button>
              <button class="nav-btn next" @click.stop="nextMedia">›</button>
            </div>

            <!-- ZOOM RESULT (DESKTOP ONLY) -->
            <div
              v-if="zoomVisible && selectedMedia?.type === 'image'"
              class="zoom-result"
              :style="zoomResultStyle"
            ></div>
          </div>

          <!-- THUMBNAILS -->
          <div class="mt-3 overflow-auto">
            <div class="d-flex flex-nowrap gap-2">
              <div
                v-for="(media, index) in mediaList"
                :key="index"
                class="thumbnail p-1 flex-shrink-0"
                :class="{ activeImage: index === currentIndex }"
                @click="selectMedia(index)"
              >
                <img
                  v-if="media.type === 'image'"
                  :src="media.url"
                  loading="lazy"
                />

                <div v-else class="video-thumb">
                  <video
                    v-if="isVideoFile(media.url)"
                    :src="media.url"
                    muted
                  ></video>

                  <iframe
                    v-else
                    :src="getEmbedUrl(media.url)"
                    frameborder="0"
                  ></iframe>

                  <span class="play-icon">▶</span>
                </div>
              </div>
            </div>
          </div>
          <div class="deskVisible">
         
            <hr class="hrDivider">
            <h4 class="smallHeading">Available Offers</h4>
            <div class="offers">
              <div class="internalOffers">
                <img src="../assets/images/static/product/offer1.png" alt="ring1" class="offerIcon" />
                <h3 class="offersHeading">10% off on your first purchase</h3>
                <h4 class="offercode">Use code: WELCOME10</h4>
              </div>
              <div class="internalOffers">
                <img src="../assets/images/static/product/offer2.png" alt="ring1" class="offerIcon" />
                <h3 class="offersHeading">Free shipping on orders above ₹2,000</h3>
              </div>
              <div class="internalOffers">
                <img src="../assets/images/static/product/offer3.png" alt="ring1" class="offerIcon" />
                <h3 class="offersHeading">Complimentary gift packaging</h3>
              </div>
            </div>
            <hr class="hrDivider">
            <h4 class="smallHeading">Assurance</h4>
            <div class="offers">
              <div class="internalOffers assu">
                <img src="../assets/images/static/product/assurance1.png" alt="ring1" class="offerIcon" />
                <h3 class="offersHeading">BIS Hallmarked</h3>
              </div>
              <div class="internalOffers assu">
                <img src="../assets/images/static/product/assurance2.png" alt="ring1" class="offerIcon" />
                <h3 class="offersHeading">BIS Hallmarked</h3>
              </div>
              <div class="internalOffers assu">
                <img src="../assets/images/static/product/assurance3.png" alt="ring1" class="offerIcon" />
                <h3 class="offersHeading">BIS Hallmarked</h3>
              </div>
            </div>
             
          </div>
        </b-col>

        <!-- RIGHT : ZOOM PREVIEW -->
        <b-col md="7">
          <!-- <div v-show="zoomVisible" class="zoom-result" :style="zoomResultStyle"></div> -->


          <h5 class="productIndicator">{{ product?.subcategory || '' }}</h5>
          <h2 class="productNamedds" >{{ product?.name || '' }}</h2>
          <h3 class="productNamedds" v-if="isPartnerLoggedIn">{{ product?.sku || '' }}</h3>
          <hr class="hrDivider">

          <!-- <hr class="hrDivider" v-if="!isPartnerLoggedIn"> -->

          <div class="product-info" v-if="!isPartnerLoggedIn">
            <div class="pricingBox">
              <div class="tagLinks">
                <h4 class="mrpTag">M.R.P.</h4>
                <h4 class="linkToBreakup" @click="toggleFilterSidebar">
                  Price Breakup
                </h4>
              </div>
              <!-- Product Price Display -->
              <div class="mainPrice">
                <!-- Final Price -->




                <h4 class="productPrice">
                  ₹ {{ product?.final_price || '-' }}
                </h4>

                <!-- Original Price (striked) -->
                <!-- <h4 v-if="product.final_price && product.final_amount_with_tax < product.subtotal_with_tax"
                  class="productPriceBeforeDiscount">
                  ₹{{ Math.floor(product.subtotal_with_tax) ? Math.floor(product.subtotal_with_tax).toLocaleString() : '0' }}
                </h4> -->

                <small>(Inclusive of tax)</small>
              </div>

              <!-- <div v-if="product.applied_offers && product.applied_offers.length" class="appliedOffers">
                <small>Applied Offers:</small>
                <ul>
                  <li v-for="(offer, index) in product.applied_offers" :key="index" class="negativeValue">
                    {{ offer.title }}
                    <span v-if="offer.coupon_code">({{ offer.coupon_code }})</span>
                    -₹{{ parseFloat(Math.floor(offer.applied_discount)).toLocaleString() }}
                  </li>
                </ul>
              </div> -->

              <div :class="{ parentBackground: sidebarstatus.filter }">
                <div class="filter_sidebar sidebar_main" :class="{ filter_active: sidebarstatus.filter }">

                  <div class="sidebar_toolbox woBorder">
                    <h6>Price Breakup</h6>
                    <div class="sidebar_toolbox_right_side">
                      <img src="../assets/img/icons/close.svg" @click="sidebarstatus.filter = false" />
                    </div>
                  </div>

                  <div class="sidebar_price">
                    <!-- Base Price -->
                    <div class="priceBreackout">
                      <h3 class="priceValues">Base Price</h3>
                      <h4 class="priceValues">₹{{ Math.floor(product.base_price).toLocaleString() }}</h4>
                    </div>

                    <!-- Making Charges -->
                    <div v-for="charge in product.charges" :key="charge">
                      <div
                        v-if="charge.charges != null && charge.charges !== '' && charge.calculated_amount && Number(charge.calculated_amount) !== 0">
                        <div class="priceBreackout">
                          <h3 class="priceValues">{{ charge.charges }} ({{ charge.primary_cost }})</h3>
                          <h4 class="priceValues">₹{{ Math.floor(Number(charge.calculated_amount || 0)).toLocaleString()
                            }}</h4>
                        </div>

                      </div>
                    </div>

                    <!-- Total Before Discount -->
                    <div class="priceBreackout">
                      <h3 class="priceValues boldOne">Sub Total (Before Discount)</h3>
                      <h4 class="priceValues">₹{{ Math.floor(product.subtotal).toLocaleString() }}</h4>
                    </div>
                    <!-- Applied Offers -->
                    <div class="priceBreackout" v-for="(offer, index) in product.offers" :key="'offer-' + index">
                      <h3 class="priceValues negativeValue">{{ offer.title }}
                        <span v-if="offer.coupon_code">({{ offer.coupon_code }})</span>
                      </h3>
                      <h4 class="priceValues negativeValue">-₹{{ parseFloat(Math.floor(offer.discount)).toLocaleString()
                      }}</h4>
                    </div>

                    <!-- Discounted Price -->
                    <div class="priceBreackout finalPrice">
                      <h3 class="priceValues boldOne">Discounted Amount</h3>
                      <h4 class="priceValues boldOne">₹{{ parseFloat(Math.floor(product.subtotal)).toLocaleString()
                      }}</h4>
                    </div>

                    <!-- Taxes -->
                    <div v-for="(tax, index) in product.taxes" :key="index">
                      <div v-if="
                        tax.tax_application != null &&
                        tax.tax_application !== '' &&
                        tax.amount &&
                        Number(tax.amount) !== 0
                      ">
                        <div class="priceBreackout">
                          <h3 class="priceValues">
                            {{ tax.tax_application }} ({{ tax.value }}%) ({{ tax.primary_cost }})
                          </h3>
                          <h4 class="priceValues">₹{{ Number(Math.floor(tax.amount) || 0).toLocaleString() }}</h4>
                        </div>
                      </div>
                    </div>



                    <!-- Final Price -->
                    <div class="priceBreackout finalPrice">
                      <h3 class="priceValues boldOne">Final Price </h3>
                      <h4 class="priceValues boldOne">₹{{
                        parseFloat(Math.floor(product.final_price)).toLocaleString() }}
                      </h4>
                    </div>
                  </div>

                </div>
              </div>


            </div>
          </div>

          <hr class="hrDivider" v-if="!isPartnerLoggedIn">

          <h4 v-if="product?.height || product?.width || product?.length" class="smallHeading">
            Size, Fit & Weight
          </h4>

          <ul v-if="product?.height || product?.width || product?.length" class="measureDetails">
            <li>
              <span>Height:</span> {{ product?.height || '-' }} cm,
              <span>Width:</span> {{ product?.width || '-' }} cm,
              <span>Length:</span> {{ product?.length || '-' }} cm
            </li>
            <li>Weight: {{ product?.weight || '-' }} g</li>
          </ul>

          <hr v-if="product?.height || product?.width || product?.length" class="hrDivider" />


          <!-- Description -->
          <h4 class="smallHeading">Product Description</h4>
          <p class="productDesc"> {{ product?.description && product.description !== 'null' ? product.description : '-'
            }}</p>
          <hr class="hrDivider">

          <!-- Quantity -->
          <h4 class="smallHeading">Quantity</h4>
          <div class="d-flex align-items-center">
            <button class="btn" type="button" @click="decreaseQuantity">−</button>
            <input type="text" class="form-control text-center mx-2 boxQuantity" v-model.number="quantity" disabled
              min="1" />
            <button class="btn" type="button" @click="increaseQuantity">+</button>
          </div>
          <hr class="hrDivider">


          <div v-for="attr in product.variant_attributes" :key="attr.id" class="mb-3">
            <!-- Visual Swatch -->
            <b-form-group v-if="attr.data_type === 'Visual Swatch'" :label="attr.name" class="multiDrop">
              <div class="d-flex flex-wrap">
                <div v-for="color in uniqueOptions(attr.options)" :key="color.sku"
                  :style="{ backgroundColor: color.label.toLowerCase() }" class="swatch"
                  :class="{ active: Swatch[attr.id]?.label === color.label }"
                  @click="handleAttributeSelect(attr.id, color)"></div>
              </div>
              <small v-if="Swatch[attr.id]" class="text-muted">
                Selected: {{ Swatch[attr.id].label }}
              </small>
            </b-form-group>

            <!-- Text Swatch -->
            <b-form-group v-else-if="attr.data_type === 'Text Swatch'" :label="attr.name" class="multiDrop">
              <div class="d-flex flex-wrap">
                <button v-for="option in uniqueOptions(attr.options)" :key="option.sku" type="button"
                  class="btn btn-outline-secondary m-1 swatchText"
                  :class="{ active: Swatch[attr.id]?.label === option.label }"
                  @click="handleAttributeSelect(attr.id, option)">
                  {{ option.label }}
                </button>
              </div>
              <small v-if="Swatch[attr.id]" class="text-muted">
                Selected: {{ Swatch[attr.id].label }}
              </small>
            </b-form-group>

            <!-- Dropdown -->
            <b-form-group v-else-if="attr.data_type === 'Dropdown'" :label="attr.name" class="multiDrop">
              <v-select :options="uniqueOptions(attr.options).map(o => ({ label: o.label, value: o }))"
                v-model="Swatch[attr.id]" placeholder="Select option"
                @update:modelValue="val => handleAttributeSelect(attr.id, val.value)" />
              <small v-if="Swatch[attr.id]" class="text-muted">
                Selected: {{ Swatch[attr.id].label }}
              </small>
            </b-form-group>
          </div>
          <!-- Inquiry Form -->
          <!-- PARTNER -->
          <template v-if="isPartnerLoggedIn">
            <button class="inquireNow" type="button" @click="inquiryShow = !inquiryShow">
              Inquire Now
            </button>
          </template>

          <!-- CUSTOMER / NOT LOGGED IN -->
          <template v-else>
            <button class="inquireSubmit" type="button" @click="inquiryShow = !inquiryShow">
              Buy Now
            </button>

            <button class="inquireNow" type="button" @click="inquiryShow = !inquiryShow">
              Add to Cart
            </button>
          </template>

          <form class="magisto" v-if="inquiryShow">
            <b-form-group id="input-group-1" label="Enter Your Name" label-for="input-1" label-class="mb-0">
              <b-form-input id="input-1" type="text" required></b-form-input>
            </b-form-group>

            <div class="d-flex gap-2">
              <b-form-group id="input-group-3" label="Country Code:" label-for="input-3" class="w-25">
                <b-form-select id="input-3" :options="contries" required placeholder="Select"></b-form-select>
              </b-form-group>
              <b-form-group class="w-75" id="input-group-1" label="Contact Number" label-for="input-1"
                label-class="mb-0">
                <b-form-input id="input-1" type="text" required></b-form-input>
              </b-form-group>
            </div>

            <button class="inquireSubmit" type="button">Submit</button>
          </form>

          <!-- Price -->
          <!-- <hr class="hrDivider"> -->





          <!-- <h4 class="smallHeading">Price</h4>
          <p>₹ {{ product?.final_price || '-' }}</p>
          <div class="tagLinks">

            <h4 class="linkToBreakup" @click="toggleFilterSidebar">
              Price Breakup
            </h4>
          </div> -->
          <!-- Price Breakup Sidebar -->

          <!-- Offers & Assurance (static for now) -->
          <div class="mobVisible">
         
            <hr class="hrDivider">
            <h4 class="smallHeading">Available Offers</h4>
            <div class="offers">
              <div class="internalOffers">
                <img src="../assets/images/static/product/offer1.png" alt="ring1" class="offerIcon" />
                <h3 class="offersHeading">10% off on your first purchase</h3>
                <h4 class="offercode">Use code: WELCOME10</h4>
              </div>
              <div class="internalOffers">
                <img src="../assets/images/static/product/offer2.png" alt="ring1" class="offerIcon" />
                <h3 class="offersHeading">Free shipping on orders above ₹2,000</h3>
              </div>
              <div class="internalOffers">
                <img src="../assets/images/static/product/offer3.png" alt="ring1" class="offerIcon" />
                <h3 class="offersHeading">Complimentary gift packaging</h3>
              </div>
            </div>
            <hr class="hrDivider">
            <h4 class="smallHeading">Assurance</h4>
            <div class="offers">
              <div class="internalOffers assu">
                <img src="../assets/images/static/product/assurance1.png" alt="ring1" class="offerIcon" />
                <h3 class="offersHeading">BIS Hallmarked</h3>
              </div>
              <div class="internalOffers assu">
                <img src="../assets/images/static/product/assurance2.png" alt="ring1" class="offerIcon" />
                <h3 class="offersHeading">BIS Hallmarked</h3>
              </div>
              <div class="internalOffers assu">
                <img src="../assets/images/static/product/assurance3.png" alt="ring1" class="offerIcon" />
                <h3 class="offersHeading">BIS Hallmarked</h3>
              </div>
            </div>
             
          </div>
        </b-col>
      </b-row>

      <!-- Related Products Carousel (static for now) -->
      <h2 class="globalHeading mb-0 mt-5">Related Products</h2>
      <div class="best_seller_slider">
        <Carousel v-bind="bestsellerconfig">

          <!-- 🔥 Dynamic items from API -->
          <Slide v-for="item in relatedProducts" :key="item.id">

            <!-- Entire product area becomes clickable -->
            <router-link :to="`/product/${encodeBase64(item.id)}`" class="text-decoration-none">

              <div class="best_seller_carosal_item">
                <img :src="`/storage/${item.main_image}`" :alt="item.name" />

                <div class="best_sellers_toolbox">
                  <i class="fa-regular fa-heart"></i>
                  <i class="fa-regular fa-eye"></i>
                </div>
              </div>

              <p class="mt-2">{{ item.name }} </p>

              <span v-if="item.isInStock" class="text-success">In Stock</span>
              <span v-else class="text-danger">Out of Stock</span>

            </router-link>

          </Slide>


          <template #addons>
            <Navigation />
          </template>
        </Carousel>
      </div>


      <!-- Product Reviews (static for now) -->
      <h2 class="globalHeading mb-0 mt-5">Product Rating & Review</h2>
      <div class="reviews">
        <div class="internalReviews" v-for="i in 3" :key="i">
          <h3 class="username">Anjali S - 4.9 <img class="starIcon" src="../assets/images/static/product/Star.svg"
              alt="star" /></h3>
          <h3 class="reviewDate">12/3/2025</h3>
          <h5 class="reviewContent">"Beautiful bracelet! Looks even better in person. I wear it daily and it hasn't
            tarnished
            at all."</h5>
        </div>
        <!-- <a class="routingall" href="#">View All</a> -->
      </div>
    </div>
  </div>

  <!-- Loading state -->
  <div v-else class="text-center p-5 full100H">
    Loading product...
  </div>



</template>

<script setup>
import { ref, computed, onMounted, watch, reactive } from "vue";
import { isCustomerLoggedIn } from '@/stores/authCustomer'
import { isPartnerLoggedIn } from '@/stores/authPartner'
import axiosPartner from '@axiosPartner';
import axiosCustomer from '@axiosCustomer';
import 'vue3-carousel/carousel.css';
import { Carousel, Slide, Navigation } from 'vue3-carousel';
import { useRoute } from 'vue-router';
import { useRouter } from "vue-router";

const route = useRoute();


const router = useRouter();

const product = ref(null);
const loading = ref(true);
const quantity = ref(1);
const inquiryShow = ref(false);
const currentIndex = ref(0);
const mainImg = ref(null);
const showLens = ref(false);
const bgPos = ref("0% 0%");
const isModalOpen = ref(false);
const zoomScale = 3;
const relatedProducts = ref([]);
const Swatch = reactive({})
const is_related_product = ref(true);
const sidebarstatus = ref({ filter: false });

const contries = ref([
  { text: 'India' }, { text: 'USA' }, { text: 'DUBAI' },
  { text: 'BANGLADESH' }, { text: 'CANADA' }, { text: 'UAE' },
]);


const images = ref([]);


const encodeBase64 = (data) => {
  if (data === undefined || data === null) {
    return "";
  }
  return btoa(data.toString());
};

const toggleFilterSidebar = () => {
  sidebarstatus.value.filter = !sidebarstatus.value.filter;
};

const fetchProduct = async () => {
  try {
    const encodedId = route.params.id || route.query.id
    const api = isPartnerLoggedIn.value ? axiosPartner : axiosCustomer

    const { data } = await api.get(`/product/${encodedId}`)
    product.value = data.data

    /* ================= BUILD MEDIA LIST ================= */

    const media = []

    // MAIN IMAGE (FULL URL)
    if (product.value.main_image) {
      media.push({
        type: "image",
        url: product.value.main_image,
      })
    }

    // OTHER IMAGES (if ever added later)
    if (Array.isArray(product.value.other_images)) {
      product.value.other_images.forEach(img => {
        if (img) {
          media.push({
            type: "image",
            url: img.startsWith("http") ? img : `/storage/${img}`,
          })
        }
      })
    }

    // OTHER VIDEOS (ARRAY OF MP4)
    if (Array.isArray(product.value.other_videos)) {
      product.value.other_videos.forEach(video => {
        if (video) {
          media.push({
            type: "video",
            url: video,
          })
        }
      })
    }

    mediaList.value = media
    currentIndex.value = 0

    await fetchRelatedProducts()
  } catch (error) {
    console.error("Error loading product", error)
  } finally {
    loading.value = false
  }
}


const fetchRelatedProducts = async () => {
  try {
    const api = isPartnerLoggedIn.value ? axiosPartner : axiosCustomer

    const res = await api.post('/products/relatedProducts', {
      product_ids: [product.value.id],
    })
    if (res.data.data && res.data.data.length > 0) {
      relatedProducts.value = res.data.data;
      is_related_product.value = true;
    } else {
      // const bestSellerRes = await axiosCustomer.get(`customer/productlisting/bestsellers`);
      // relatedProducts.value = bestSellerRes.data.data || [];
      // is_related_product.value = false;
    }
    relatedProducts.value = relatedProducts.value.map((item) => {
      return {
        ...item,
        isInStock: item.available_stock > 0,
      };
    });

  } catch (error) {
    console.error("Failed to fetch related products", error);
    relatedProducts.value = [];
    is_related_product.value = false;
  }
};

const handleAttributeSelect = (attrId, option) => {
  // ✅ Safe set in reactive Swatch
  Swatch[attrId] = option;

  if (option.product_id) {
    const encodedId = btoa(option.product_id);
    // Update URL without page reload
    router.replace({ path: `/product/${encodedId}` });

    // Fetch new product
    fetchProduct();
  }
};

// Quantity controls
const increaseQuantity = () => quantity.value++;
const decreaseQuantity = () => { if (quantity.value > 1) quantity.value--; };

// Image zoom
function setIndex(i) { currentIndex.value = i; }
function onMouseEnter(e) { showLens.value = true; updateZoom(e); }
function onMouseLeave() { showLens.value = false; }
function onMouseMove(e) { updateZoom(e); }
function updateZoom(e) {
  const imgEl = mainImg.value;
  if (!imgEl) return;
  const rect = imgEl.getBoundingClientRect();
  const x = e.clientX - rect.left;
  const y = e.clientY - rect.top;
  const px = Math.max(0, Math.min(x / rect.width, 1)) * 100;
  const py = Math.max(0, Math.min(y / rect.height, 1)) * 100;
  bgPos.value = `${px}% ${py}%`;
}
const zoomWindowStyle = computed(() => {
  const imgEl = mainImg.value;
  if (!imgEl || !images.value.length) return {};
  return {
    width: "100%",
    height: "100%",
    right: "-100%",
    top: "0px",
    backgroundImage: `url(${images.value[currentIndex.value].src})`,
    backgroundRepeat: "no-repeat",
    backgroundSize: `${imgEl.naturalWidth * zoomScale}px ${imgEl.naturalHeight * zoomScale}px`,
    backgroundPosition: bgPos.value,
  };
});
function openModal() { isModalOpen.value = true; document.body.style.overflow = "hidden"; }
function closeModal() { isModalOpen.value = false; document.body.style.overflow = ""; }

const uniqueOptions = (options) => {
  const seen = new Set();
  return options.filter(o => {
    if (seen.has(o.label)) return false;
    seen.add(o.label);
    return true;
  });
};
onMounted(fetchProduct);

const bestsellerconfig = {
  breakpoints: {
    400: { itemsToShow: 2, gap: 10, height: 300, snapAlign: 'center' },
    1000: { itemsToShow: 6, gap: 10, height: 300, snapAlign: 'start' }
  }
};

watch(
  () => route.params.id,
  () => {
    fetchProduct();
  }
);





const mediaList = ref([])
const zoomVisible = ref(false)
const zoomResultStyle = ref({})
const imageRef = ref(null)

/* ================= COMPUTED ================= */

const selectedMedia = computed(() => mediaList.value[currentIndex.value])

/* ================= MEDIA HELPERS ================= */

const isVideoFile = url => /\.(mp4|webm|ogg)$/i.test(url)

const getEmbedUrl = url => {
  if (!url) return ""
  if (url.includes("youtube") || url.includes("youtu.be")) {
    const id = url.split("v=")[1] || url.split("/").pop()
    return `https://www.youtube.com/embed/${id}`
  }
  if (url.includes("vimeo")) {
    return `https://player.vimeo.com/video/${url.split("/").pop()}`
  }
  return url
}

const selectMedia = i => {
  currentIndex.value = i
  zoomVisible.value = false
}

const nextMedia = () => {
  currentIndex.value =
    currentIndex.value < mediaList.value.length - 1
      ? currentIndex.value + 1
      : 0
}

const prevMedia = () => {
  currentIndex.value =
    currentIndex.value > 0
      ? currentIndex.value - 1
      : mediaList.value.length - 1
}

/* ================= DESKTOP ZOOM ================= */

const handleZoom = e => {
  const img = imageRef.value
  if (!img) return

  zoomVisible.value = true

  const rect = img.getBoundingClientRect()
  const x = ((e.clientX - rect.left) / rect.width) * 100
  const y = ((e.clientY - rect.top) / rect.height) * 100

  zoomResultStyle.value = {
    backgroundImage: `url(${selectedMedia.value.url})`,
    backgroundPosition: `${x}% ${y}%`,
    backgroundSize: "200%",
  }
}

const hideZoom = () => {
  zoomVisible.value = false
}

</script>

<style>
.swatchBox {
  border: 1px solid #ccc;
  padding: 6px 12px;
  border-radius: 4px;
  cursor: pointer;
}

.swatchBox.active {
  border: 2px solid #000;
  font-weight: bold;
}

.main-image {
  transition: transform 0.2s ease;
}

.thumbnail.activeImage {
  border: 2px solid #000;
}

.play-icon {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  font-size: 18px;
  color: #fff;
  pointer-events: none;
}

.nav-btn {
  position: absolute;
  top: 50%;
  transform: translateY(-50%);
  width: 36px;
  height: 36px;
  border: none;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.85);
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.2);
  font-size: 22px;
  line-height: 1;
  cursor: pointer;
  z-index: 5;
}

.nav-btn.prev {
  left: 10px;
}

.nav-btn.next {
  right: 10px;
}

.nav-btn:hover {
  background: #fff;
}

.zoom-container {
  position: relative;
}

.preview-image {
  width: 100%;
  height: 400px;
  object-fit: contain;
}

.zoom-result {
  position: absolute;
  top: 0;
  left: 105%;
  width: 400px;
  height: 400px;
  background-repeat: no-repeat;
  border: 1px solid #ddd;
  background-color: #fff !important;
  z-index: 999;
}

.thumbnail {
  width: 100px;
  height: 80px;
  border: 1px solid #ccc;
  cursor: pointer;
}

.thumbnail img,
.thumbnail video,
.thumbnail iframe {
  width: 100%;
  height: 100%;
  object-fit: contain;
}

.thumbnail.activeImage {
  border: 2px solid #000;
}

.video-thumb {
  position: relative;
}

.play-icon {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  font-size: 18px;
  color: #fff;
  pointer-events: none;
}

.nav-btn {
  position: absolute;
  top: 50%;
  transform: translateY(-50%);
  width: 36px;
  height: 36px;
  border-radius: 50%;
  border: none;
  background: rgba(255, 255, 255, 0.8);
  font-size: 22px;
  cursor: pointer;
}

.nav-btn.prev {
  left: 10px;
}

.nav-btn.next {
  right: 10px;
}

.preview-image {
  touch-action: pinch-zoom;
}
</style>