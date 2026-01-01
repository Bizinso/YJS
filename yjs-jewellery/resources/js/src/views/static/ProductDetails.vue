<template>
  <div class="yjs_product">
    <div class="global_breadcrumbs">
      <div class="container">
        <ul class="breadcrumb">
          <li><a href="/products">Our Products</a></li>
          <li><a href="/productsSelected">Earring</a></li>
          <li>EPRO-001</li>
        </ul>
      </div>
    </div>
    <div class="container">
      <b-row>
        <b-col md="5">
          <div class="main-image-wrapper position-relative">
            <img ref="mainImg" :src="images[currentIndex].src" :alt="images[currentIndex].alt || 'product image'"
              class="img-fluid main-image" @mousemove="onMouseMove" @mouseenter="onMouseEnter"
              @mouseleave="onMouseLeave" @click="openModal" style="cursor: zoom-in;" />
            <div v-if="showLens" class="zoom-window" :style="zoomWindowStyle" aria-hidden="true"></div>
          </div>

          <div v-if="isModalOpen" class="zoom-modal" @click.self="closeModal" role="dialog" aria-modal="true">
            <button class="btn-closed position-absolute top-3 end-3" @click="closeModal" aria-label="Close">Close</button>
            <div class="zoom-modal-body d-flex align-items-center justify-content-center">
              <img :src="images[currentIndex].src" :alt="images[currentIndex].alt" class="img-fluid zoomed-image" />
            </div>
          </div>

          <div class="d-flex gap-2 thumbnails-wrapper">
            <button v-for="(img, i) in images" :key="i" class="thumb-btn btn p-0 border-0"
              :class="{ active: i === currentIndex }" @click="setIndex(i)" :aria-pressed="i === currentIndex"
              :title="img.alt || `View ${i + 1}`">
              <img :src="img.thumb || img.src" :alt="img.alt || `thumb ${i + 1}`" class="img-thumbnail"
                style="width:80px; height:80px; object-fit:cover;" loading="lazy" />
            </button>
          </div>
        </b-col>
        <b-col md="7">
          <h5 class="productIndicator">Earrings</h5>
          <h2 class="productNamedd">EPRO-001</h2>
          <hr class="hrDivider">
          <h4 class="smallHeading">Size, Fit & Weight</h4>
          <ul class="measureDetails">
            <li><span>Height:</span> 6.2 cm, <span>Width:</span> 3.2 cm,</li>
            <li>Designed with a secure push-back screw closure, they ensure comfort and elegance for festive and traditional wear.</li>
            <li><span>Weight:</span> 28 g per pair <span class="text-danger">(±1 gram approx. weight.)</span></li>
          </ul>
          <hr class="hrDivider">
          <h4 class="smallHeading">Product Description</h4>
          <p class="productDesc">These handcrafted temple-inspired gold earrings feature a divine motif adorned with ruby-red stones. Clusters of lustrous pearls cascade beautifully, adding grace and movement. A perfect blend of tradition and elegance, ideal for weddings and festive occasions.</p>
          <hr class="hrDivider">
          <h4 class="smallHeading">Quantity</h4>
          <div class="d-flex align-items-center">
            <button 
              class="btn"
              type="button"
              @click="decreaseQuantity"
            >
              −
            </button>

            <input
              type="text"
              class="form-control text-center mx-2 boxQuantity"
              v-model.number="quantity"
              disabled
              min="1"
            />

            <button 
              class="btn"
              type="button"
              @click="increaseQuantity"
            >
              +
            </button>
          </div>

          <button class="inquireNow" type="button" @click="inquiryShow = !inquiryShow">
            Inquire Now
          </button>

          <form class="magisto" v-if="inquiryShow">
            <b-form-group
              id="input-group-1"
              label="Enter Your Name"
              label-for="input-1"
              label-class="mb-0"
            >
              <b-form-input
                id="input-1"
                type="email"
                required
              ></b-form-input>
            </b-form-group>

            <div class="d-flex gap-2">
              <b-form-group id="input-group-3" label="Country Code:" label-for="input-3" class="w-25">
              <b-form-select
                id="input-3"
                :options="contries"
                required
                placeholder="Select"
              ></b-form-select>
            </b-form-group>
            <b-form-group class="w-75"
              id="input-group-1"
              label="Contact Number"
              label-for="input-1"
              label-class="mb-0"
            >
              <b-form-input
                id="input-1"
                type="email"
                required
              ></b-form-input>
            </b-form-group>
            </div>
            
            <button class="inquireSubmit" type="button">
              Submit
            </button>
          </form>
          
          <hr class="hrDivider">
          <h4 class="smallHeading">Available Offers</h4>
          <div class="offers">
            <div class="internalOffers">
              <img src="../assets/images/static/product/offer1.png" alt="ring1" class="offerIcon" />
              <h3 class="offersHeading">10% off on your first   purchase</h3>
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
        </b-col>
      </b-row>

      
      <h2 class="globalHeading mb-0 mt-5">Related Products</h2>
      <div class="best_seller_slider">
          <Carousel v-bind="bestsellerconfig">
            <Slide>
              <div class="best_seller_carosal_item">
                <img src="../assets/images/best_sellers/bs1.png" alt="bs1" />
                <div class="best_sellers_toolbox">
                  <i class="fa-regular fa-heart"></i>
                  <i class="fa-regular fa-eye"></i>
                </div>
              </div>
              <p class="mt-2">Antique Choker</p>
            </Slide>
            <Slide>
              <div class="best_seller_carosal_item">
                <img src="../assets/images/best_sellers/bs2.png" alt="bs2" />
                <div class="best_sellers_toolbox">
                  <i class="fa-regular fa-heart"></i>
                  <i class="fa-regular fa-eye"></i>
                </div>
              </div>
              <p class="mt-2">Antique Choker</p>
            </Slide>
            <Slide>
              <div class="best_seller_carosal_item">
                <img src="../assets/images/best_sellers/bs3.png" alt="bs3" />
                <div class="best_sellers_toolbox">
                  <i class="fa-regular fa-heart"></i>
                  <i class="fa-regular fa-eye"></i>
                </div>
              </div>
              <p class="mt-2">Antique Choker</p>
            </Slide>
            <Slide>
              <div class="best_seller_carosal_item">
                <img src="../assets/images/best_sellers/bs1.png" alt="bs1" />
                <div class="best_sellers_toolbox">
                  <i class="fa-regular fa-heart"></i>
                  <i class="fa-regular fa-eye"></i>
                </div>
              </div>
              <p class="mt-2">Antique Choker</p>
            </Slide>
            <Slide>
              <div class="best_seller_carosal_item">
                <img src="../assets/images/best_sellers/bs2.png" alt="bs2" />
                <div class="best_sellers_toolbox">
                  <i class="fa-regular fa-heart"></i>
                  <i class="fa-regular fa-eye"></i>
                </div>
              </div>
              <p class="mt-2">Antique Choker</p>
            </Slide>
            <Slide>
              <div class="best_seller_carosal_item">
                <img src="../assets/images/best_sellers/bs3.png" alt="bs3" />
                <div class="best_sellers_toolbox">
                  <i class="fa-regular fa-heart"></i>
                  <i class="fa-regular fa-eye"></i>
                </div>
              </div>
              <p class="mt-2">Antique Choker</p>
            </Slide>
            <Slide>
              <div class="best_seller_carosal_item">
                <img src="../assets/images/best_sellers/bs3.png" alt="bs3" />
                <div class="best_sellers_toolbox">
                  <i class="fa-regular fa-heart"></i>
                  <i class="fa-regular fa-eye"></i>
                </div>
              </div>
              <p class="mt-2">Antique Choker</p>
            </Slide>
            <Slide>
              <div class="best_seller_carosal_item">
                <img src="../assets/images/best_sellers/bs1.png" alt="bs1" />
                <div class="best_sellers_toolbox">
                  <i class="fa-regular fa-heart"></i>
                  <i class="fa-regular fa-eye"></i>
                </div>
              </div>
              <p class="mt-2">Antique Choker</p>
            </Slide>
            <Slide>
              <div class="best_seller_carosal_item">
                <img src="../assets/images/best_sellers/bs2.png" alt="bs2" />
                <div class="best_sellers_toolbox">
                  <i class="fa-regular fa-heart"></i>
                  <i class="fa-regular fa-eye"></i>
                </div>
              </div>
              <p class="mt-2">Antique Choker</p>
            </Slide>
            <Slide>
              <div class="best_seller_carosal_item">
                <img src="../assets/images/best_sellers/bs3.png" alt="bs3" />
                <div class="best_sellers_toolbox">
                  <i class="fa-regular fa-heart"></i>
                  <i class="fa-regular fa-eye"></i>
                </div>
              </div>
              <p class="mt-2">Antique Choker</p>
            </Slide>
            <Slide>
              <div class="best_seller_carosal_item">
                <img src="../assets/images/best_sellers/bs3.png" alt="bs3" />
                <div class="best_sellers_toolbox">
                  <i class="fa-regular fa-heart"></i>
                  <i class="fa-regular fa-eye"></i>
                </div>
              </div>
              <p class="mt-2">Antique Choker</p>
            </Slide>
            <Slide>
              <div class="best_seller_carosal_item">
                <img src="../assets/images/best_sellers/bs1.png" alt="bs1" />
                <div class="best_sellers_toolbox">
                  <i class="fa-regular fa-heart"></i>
                  <i class="fa-regular fa-eye"></i>
                </div>
              </div>
              <p class="mt-2">Antique Choker</p>
            </Slide>
            <Slide>
              <div class="best_seller_carosal_item">
                <img src="../assets/images/best_sellers/bs2.png" alt="bs2" />
                <div class="best_sellers_toolbox">
                  <i class="fa-regular fa-heart"></i>
                  <i class="fa-regular fa-eye"></i>
                </div>
              </div>
              <p class="mt-2">Antique Choker</p>
            </Slide>
            <Slide>
              <div class="best_seller_carosal_item">
                <img src="../assets/images/best_sellers/bs3.png" alt="bs3" />
                <div class="best_sellers_toolbox">
                  <i class="fa-regular fa-heart"></i>
                  <i class="fa-regular fa-eye"></i>
                </div>
              </div>
              <p class="mt-2">Antique Choker</p>
            </Slide>
            
            <template #addons>
              <Navigation />
            </template>
          </Carousel>
        </div>

        
      <h2 class="globalHeading mb-0 mt-5">Product Rating & Review</h2>
      <div class="reviews">
        <div class="internalReviews">
          <h3 class="username">Anjali S - 4.9 <img class="starIcon" src="../assets/images/static/product/Star.svg" alt="star" /></h3>
          <h3 class="reviewDate">12/3/2025</h3>
          <h5 class="reviewContent">"Beautiful bracelet! Looks even better in person. I wear it daily and it hasn't tarnished at all."</h5>
        </div>
        <div class="internalReviews">
          <h3 class="username">Anjali S - 4.9 <img class="starIcon" src="../assets/images/static/product/Star.svg" alt="star" /></h3>
          <h3 class="reviewDate">12/3/2025</h3>
          <h5 class="reviewContent">"Beautiful bracelet! Looks even better in person. I wear it daily and it hasn't tarnished at all."</h5>
        </div>
        <div class="internalReviews">
          <h3 class="username">Anjali S - 4.9 <img class="starIcon" src="../assets/images/static/product/Star.svg" alt="star" /></h3>
          <h3 class="reviewDate">12/3/2025</h3>
          <h5 class="reviewContent">"Beautiful bracelet! Looks even better in person. I wear it daily and it hasn't tarnished at all."</h5>
        </div>
        <!-- <a class="routingall" href="#">View All</a> -->
      </div>
    </div>






  </div>
</template>

<script setup>
import { ref, computed, watch } from "vue";
import 'vue3-carousel/carousel.css';
import { Carousel, Slide, Pagination, Navigation } from 'vue3-carousel';

// Relative imports (works without alias)
import img1 from "../assets/images/static/product/earrings1.png";
import img2 from "../assets/images/static/product/earrings2.png";
import img3 from "../assets/images/static/product/earrings3.png";
import img4 from "../assets/images/static/product/earrings4.png";
import img5 from "../assets/images/static/product/earrings4.png";


const bestsellerconfig = {
  breakpoints:{
      400: {
        itemsToShow: 2,
        gap: 10,
        height: 300,
        snapAlign: 'center',
      },
      1000: {
        itemsToShow: 6,
        gap: 10,
        height: 300,
        snapAlign: 'start',
      }
    }
}


const quantity = ref(1);
const inquiryShow = ref(false);
const increaseQuantity = () => {
  quantity.value++;
};

const decreaseQuantity = () => {
  if (quantity.value > 1) {
    quantity.value--;
  }
};
const contries = ref([
  {text:'India'},
  {text:'USA'},
  {text:'DUBAI'},
  {text:'BANGLADESH'},
  {text:'CANADA'},
  {text:'UAE'},
])
const images = ref([
  { src: img1, thumb: img1, alt: "Front view" },
  { src: img2, thumb: img2, alt: "Back view" },
  { src: img3, thumb: img3, alt: "Side view" },
  { src: img4, thumb: img4, alt: "Close-up view" },
  { src: img5, thumb: img5, alt: "Close-up view" },
]);

const currentIndex = ref(0);
const mainImg = ref(null);

const showLens = ref(false);
const bgPos = ref("0% 0%");
const isModalOpen = ref(false);
const zoomScale = 3;
const zoomWindowSize = 500;

function setIndex(i) {
  if (i >= 0 && i < images.value.length) {
    currentIndex.value = i;
  }
}

function onMouseEnter(e) {
  showLens.value = true;
  updateZoom(e);
}
function onMouseLeave() {
  showLens.value = false;
}
function onMouseMove(e) {
  updateZoom(e);
}

function updateZoom(e) {
  const imgEl = mainImg.value;
  if (!imgEl) return;

  const rect = imgEl.getBoundingClientRect();
  const x = e.clientX - rect.left;
  const y = e.clientY - rect.top;
  const clampedX = Math.max(0, Math.min(x, rect.width));
  const clampedY = Math.max(0, Math.min(y, rect.height));
  const px = (clampedX / rect.width) * 100;
  const py = (clampedY / rect.height) * 100;
  bgPos.value = `${px}% ${py}%`;
}

const zoomWindowStyle = computed(() => {
  const imgEl = mainImg.value;
  if (!imgEl) return {};
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

function openModal() {
  isModalOpen.value = true;
  document.body.style.overflow = "hidden";
}
function closeModal() {
  isModalOpen.value = false;
  document.body.style.overflow = "";
}
</script>
<style scoped>
/* Layout adjustments */
.ecom-image-component {
  --thumb-size: 64px;
}

/* Thumbnails column */
.thumbnails-col {
  max-width: 84px;
}

.thumbnails-wrapper {
  overflow-x: auto;
  padding-bottom: 4px;
}
.thumbnails-wrapper {
  display: flex;
  flex-wrap: nowrap;      /* keeps items on one line */
  overflow-x: auto;       /* enables horizontal scroll */
  overflow-y: hidden;     /* hides vertical scrollbar */
  gap: 0.5rem;            /* space between buttons */
  padding-bottom: 5px;    /* avoid scrollbar overlap */
  scroll-behavior: smooth; /* optional smooth scroll */
}

.thumb-btn {
  flex: 0 0 auto; /* prevent shrinking */
}

.thumbnails-wrapper::-webkit-scrollbar {
  height: 6px; /* scrollbar height */
}

.thumbnails-wrapper::-webkit-scrollbar-thumb {
  background: #ccc;
  border-radius: 10px;
}

.thumbnails-wrapper::-webkit-scrollbar-thumb:hover {
  background: #999;
}


@media (min-width: 768px) {
  .thumbnails-wrapper {
    overflow-x: visible;
  }
}

/* Thumbnail buttons */
.thumb-btn.active {
  outline: 2px solid var(--bs-primary);
  border-radius: 6px;
}

/* Main image wrapper */
.main-image-wrapper {
  min-height: 450px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #000;
  border-radius: 8px;
  margin-bottom: 10px;
}

/* Main product image */
.main-image {
  max-height: 480px;
  width: 250px;
  object-fit: contain;
  border-radius: 6px;
  box-shadow: 0 6px 16px rgba(0, 0, 0, 0.08);
}

/* Zoom window */
.zoom-window {
  position: absolute;
  z-index: 30;
  border-radius: 6px;
  border: 1px solid rgba(0, 0, 0, 0.12);
  box-shadow: 0 8px 22px rgba(0, 0, 0, 0.12);
  background-color: white;
  background-repeat: no-repeat;
  pointer-events: none;
}

/* Zoom modal */
.zoom-modal {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.85);
  z-index: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1.25rem;
}

.zoom-modal-body {
  width: 50vh;
  max-width: 1200px;
  max-height: 95vh;
  overflow: auto;
}

.zoomed-image {
  display: block;
  max-width: 100%;
  max-height: 95vh;
  border-radius: 6px;
}

.zoom-modal .btn-closed {
  right: 1rem;
  top: 1rem;
  color: white;
  position: absolute;
  top: 50%;
  right: 20px;
  text-align: right;
  width: auto;
  background: transparent;
  border: none;
}

/* Responsive tweaks */
@media (max-width: 767px) {
  .thumbnails-col {
    max-width: none;
  }

  .thumbnails-wrapper {
    flex-direction: row;
  }

  .zoom-window {
    display: none;
    /* hide hover zoom on mobile */
  }
}
</style>