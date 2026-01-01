<script setup>
import { ref } from "vue";
//import { VueMarqueeSlider } from "vue3-marquee-slider";

import "vue3-carousel/carousel.css";
import { Carousel, Slide, Pagination, Navigation } from "vue3-carousel";


import Service from '@/views/assets/images/landing/services/Service.png'
import Service1 from '@/views/assets/images/landing/services/Service-1.png'
import Service2 from '@/views/assets/images/landing/services/Service-2.png'

import OfficeWear from '@/views/assets/images/landing/occasions/Office Wear.png'
import Anniversaries from '@/views/assets/images/landing/occasions/Anniversaries.png'
import Birthdays from '@/views/assets/images/landing/occasions/Birthdays.png'
import CasualWear from '@/views/assets/images/landing/occasions/Casual Wear.png'
import Engagements from '@/views/assets/images/landing/occasions/Engagements.png'
import Wedding from '@/views/assets/images/landing/occasions/Occasion Card.png'

import Category1 from '@/views/assets/images/landing/category/C-Card.png'
import Category2 from '@/views/assets/images/landing/category/C-Card-1.png'
import Category3 from '@/views/assets/images/landing/category/C-Card-2.png'
import Category4 from '@/views/assets/images/landing/category/C-Card-3.png'
import Category5 from '@/views/assets/images/landing/category/C-Card-4.png'
import Category6 from '@/views/assets/images/landing/category/C-Card-5.png'
// Introduce Jwellery Collection Config //
const jwelleryCollectionConfig = {
  itemsToShow: 1
}

// Introduce Jwellery Collection Config End //

// Shop By Category //
const shop_by_category_data = ref([
  {
    categoryBanner: 'sbc1.svg',
    categoryName: 'Antique Choker'
  },
  {
    categoryBanner: 'sbc2.svg',
    categoryName: 'Antique Choker'
  },
  {
    categoryBanner: 'sbc3.svg',
    categoryName: 'Antique Choker'
  },
  {
    categoryBanner: 'sbc4.svg',
    categoryName: 'Antique Choker'
  },
  {
    categoryBanner: 'sbc5.svg',
    categoryName: 'Antique Choker'
  },
  {
    categoryBanner: 'sbc6.svg',
    categoryName: 'Antique Choker'
  }
]);

// Shop By Category End //

// Category //
const git_category_config = {
  breakpoints:{
      400: {
        itemsToShow: 2,
        height: 300,
        snapAlign: 'center',
      },
      1000: {
        itemsToShow: 6,
        height: 300,
        snapAlign: 'start',
      }
    }
}

const images = Array.from({ length: 10 }, (_, index) => ({
  id: index + 1,
  url: `https://picsum.photos/seed/${Math.random()}/800/600`,
}))
const CategoryImages = [
  {
    id: 1,
    url: Category1,
  },
  {
    id: 2,
    url: Category2,
  },
  {
    id: 3,
    url: Category3,
  },
  {
    id: 4,
    url: Category4,
  },
  {
    id: 5,
    url: Category5,
  },
  {
    id: 6,
    url: Category6,
  },
]

const ServiceImages = [
  { id: 1, url: Service },
  { id: 2, url: Service1 },
  { id: 3, url: Service2 },
]

// Category End //

// best sellers //
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
// best sellers //

// Shop by Occasion //
// const shopbyoccassion = Array.from({ length: 6 }, (_, index) => ({
//   id: index + 1,
//   url: `https://picsum.photos/seed/${Math.random()}/400/200`,
// }))
const shopbyoccassion = [
  {
    id: 1,
    title: 'Office Wear',
    url: OfficeWear,
  },
  {
    id: 2,
    title: 'Anniversaries',
    url: Anniversaries,
  },
  {
    id: 3,
    title: 'Birthdays',
    url: Birthdays,
  },
  {
    id: 4,
    title: 'Casual Wear',
    url: CasualWear,
  },
  {
    id: 5,
    title: 'Engagements',
    url: Engagements,
  },
  {
    id: 6,
    title: 'Wedding',
    url: Wedding,
  },
]


// Shop by Occasion end //

// Real stories. Genuine sparkle //

const reviewConfig = {

  breakpoints:{
      400: {
        itemsToShow: 1,
        gap: 10,
        height: 300,
        snapAlign: 'center',
      },
      1000: {
        itemsToShow: 3,
        gap: 10,
        height: 300,
        snapAlign: 'start',
      }
    }
}

const reviews = ref([
  {
    name: 'Aarushi M. – Mumbai',
    review: '"The gold necklace I ordered for my wedding was absolutely stunning! It arrived on time, beautifully packed, and the quality exceeded expectations."',
    rating: '4.9'
  },
  {
    name: 'Aarushi M. – Mumbai',
    review: '"The gold necklace I ordered for my wedding was absolutely stunning! It arrived on time, beautifully packed, and the quality exceeded expectations."',
    rating: '4.9'
  },
  {
    name: 'Aarushi M. – Mumbai',
    review: '"The gold necklace I ordered for my wedding was absolutely stunning! It arrived on time, beautifully packed, and the quality exceeded expectations."',
    rating: '4.9'
  },
  {
    name: 'Aarushi M. – Mumbai',
    review: '"The gold necklace I ordered for my wedding was absolutely stunning! It arrived on time, beautifully packed, and the quality exceeded expectations."',
    rating: '4.9'
  },
  {
    name: 'Aarushi M. – Mumbai',
    review: '"The gold necklace I ordered for my wedding was absolutely stunning! It arrived on time, beautifully packed, and the quality exceeded expectations."',
    rating: '4.9'
  },
  {
    name: 'Aarushi M. – Mumbai',
    review: '"The gold necklace I ordered for my wedding was absolutely stunning! It arrived on time, beautifully packed, and the quality exceeded expectations."',
    rating: '4.9'
  }
])

// Real stories. Genuine sparkle end //

// Our Services //
// const ourservices = Array.from({ length: 3 }, (_, index) => ({
//   id: index + 1,
//   url: `https://picsum.photos/seed/${Math.random()}/800/850`,
// }))
const ourservices = [
  {
    id: 1,
    title: 'Instant Repair',
    url: Service,
  },
  {
    id: 2,
    title: 'Ear Repairing',
    url: Service1,
  },
  {
    id: 3,
    title: 'Gem Testing',
    url: Service2,
  },
]
// Our Services end //
</script>
<template>
  <div class="yjs_home">
    <!-- introduce jwellery collections -->
     <div class="introduce_jwellery_collections mb-5">
      <Carousel v-bind="jwelleryCollectionConfig">
        <Slide>
          <div class="introduce_jwellery_collection_grid">
            <div class="introduce_jwellery_collections_info">
              <h2>Introducing <br/> Our Latest Jewelry Collection</h2>
              <p>Discover timeless elegance with our newest handcrafted pieces. Elevate your style with exclusive designs!</p>
              <b-button class="btn_km mt-4">Know More <img src="./assets/images/icons/right-arrow.svg" alt="right-arrow"></b-button>
            </div>
            <div class="introduce_jwellery_collections_pic">
              <img src="./assets/images/slider/sl1.png" alt="sl1" />
            </div>
          </div>
        </Slide>
        <Slide>
          <div class="introduce_jwellery_collection_grid">
            <div class="introduce_jwellery_collections_info">
              <h2>Introducing <br/> Our Latest Jewelry Collection</h2>
              <p>Discover timeless elegance with our newest handcrafted pieces. Elevate your style with exclusive designs!</p>
              <b-button class="btn_km mt-4">Know More <img src="./assets/images/icons/right-arrow.svg" alt="right-arrow"></b-button>
            </div>
            <div class="introduce_jwellery_collections_pic">
              <img src="./assets/images/slider/sl1.png" alt="sl1" />
            </div>
          </div>
        </Slide>
        <Slide>
          <div class="introduce_jwellery_collection_grid">
            <div class="introduce_jwellery_collections_info">
              <h2>Introducing <br/> Our Latest Jewelry Collection</h2>
              <p>Discover timeless elegance with our newest handcrafted pieces. Elevate your style with exclusive designs!</p>
              <b-button class="btn_km mt-4">Know More <img src="./assets/images/icons/right-arrow.svg" alt="right-arrow"></b-button>
            </div>
            <div class="introduce_jwellery_collections_pic">
              <img src="./assets/images/slider/sl1.png" alt="sl1" />
            </div>
          </div>
        </Slide>
        <template #addons>
          <Navigation />
          <Pagination class="deskShow" />
        </template>
      </Carousel>      
     </div>
    <!-- shop by category -->
    <!-- <div class="shop_by_category py-5 mb-3">
      <h2 class="mb-4">Shop by Category</h2>
      <div class="shop_by_category_slider">
        
      </div>
    </div> -->

    <!-- category -->
     <div class="gif_category">
      <h2 class="mb-3 mt-5">Category</h2>
      <Carousel v-bind="git_category_config">
        <Slide v-for="image in CategoryImages" :key="image.id">
          <img :src="image.url" alt="slider pic" />
          <b-button class="gif_view_more_link">View More</b-button>
        </Slide>
      </Carousel>
     </div>

     <!-- The art in itself -->
      <div class="the_art_in_itself py-5 mb-5">
        <h6 class="mt-3">Rise of Antique Jewellery</h6>
        <h2>THE ART IN ITSELF</h2>
        <p>Welcome to the Yashoda Jagdish And Sons Jewellery Antique World. Experience the perfect vintage combination traditional & trending jewellery. Our Love & Passion for Unique & Distinctive Designer Antique jewellery drives us everyday for Aspiring to be more Classy & Innovative.</p>
        <b-button class="btn_km mt-4 mb-3">Know More <img src="./assets/images/icons/right-arrow.svg" alt="right-arrow"></b-button>
      </div>

      <!---Recent Launch-->
      <div class="recent_launch py-5 mb-4">
        <div class="container">
          <h2 class="mb-1">Recent Launch</h2>
          <p class="mb-4">Discover the latest additions to our exquisite jewelry collection.</p>
          <div class="recent_launch_grid rlg_first">
            <div class="recent_launch_grid_item">
              <img src="../views/assets/images/recent_launch/rl1.png" alt="rl1">
              <div class="recent_launch_grid_item_details p-2">
                <p class="mb-1">Antique Maang Tikka</p>
                <span>Shop here <img src="./assets/images/icons/right-arrow-black.svg" class="right_arrow_black" alt="right-arrow-black"></span>
              </div>
            </div>
            <div class="recent_launch_grid_item">
              <img src="../views/assets/images/recent_launch/rl2.png" alt="rl2">
              <div class="recent_launch_grid_item_details p-2">
                <p class="mb-1">Antique Maang Tikka</p>
                <span>Shop here <img src="./assets/images/icons/right-arrow-black.svg" class="right_arrow_black" alt="right-arrow-black"></span>
              </div>
            </div>
            <div class="recent_launch_grid_item">
              <img src="../views/assets/images/recent_launch/rl3.png" alt="rl3">
              <div class="recent_launch_grid_item_details p-2">
                <p class="mb-1">Antique Maang Tikka</p>
                <span>Shop here <img src="./assets/images/icons/right-arrow-black.svg" class="right_arrow_black" alt="right-arrow-black"></span>
              </div>
            </div>            
          </div>
          <div class="recent_launch_grid rlg_second mt-2">
            <div class="recent_launch_grid_item">
              <img src="../views/assets/images/recent_launch/rl4.png" alt="rl4">
              <div class="recent_launch_grid_item_details p-2">
                <p class="mb-1">Antique Maang Tikka</p>
                <span>Shop here <img src="./assets/images/icons/right-arrow-black.svg" class="right_arrow_black" alt="right-arrow-black"></span>
              </div>
            </div>
            <div class="recent_launch_grid_item">
              <img src="../views/assets/images/recent_launch/rl5.png" alt="rl5">
              <div class="recent_launch_grid_item_details p-2">
                <p class="mb-1">Antique Maang Tikka</p>
                <span>Shop here <img src="./assets/images/icons/right-arrow-black.svg" class="right_arrow_black" alt="right-arrow-black"></span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!---------- Best Sellers -------------->
      <div class="best_sellers py-5 mb-5">
        <h2>Best Sellers</h2>
        <p>Our most loved and top-rated pieces — chosen by our customers.</p>
        <div class="best_seller_slider mt-4">
          <Carousel v-bind="bestsellerconfig">
            <Slide>
              <div class="best_seller_carosal_item">
                <img src="./assets/images/best_sellers/bs1.png" alt="bs1" />
                <div class="best_sellers_toolbox">
                  <i class="fa-regular fa-heart"></i>
                  <i class="fa-regular fa-eye"></i>
                </div>
              </div>
              <p class="mt-2">Antique Choker</p>
            </Slide>
            <Slide>
              <div class="best_seller_carosal_item">
                <img src="./assets/images/best_sellers/bs2.png" alt="bs2" />
                <div class="best_sellers_toolbox">
                  <i class="fa-regular fa-heart"></i>
                  <i class="fa-regular fa-eye"></i>
                </div>
              </div>
              <p class="mt-2">Antique Choker</p>
            </Slide>
            <Slide>
              <div class="best_seller_carosal_item">
                <img src="./assets/images/best_sellers/bs3.png" alt="bs3" />
                <div class="best_sellers_toolbox">
                  <i class="fa-regular fa-heart"></i>
                  <i class="fa-regular fa-eye"></i>
                </div>
              </div>
              <p class="mt-2">Antique Choker</p>
            </Slide>
            <Slide>
              <div class="best_seller_carosal_item">
                <img src="./assets/images/best_sellers/bs1.png" alt="bs1" />
                <div class="best_sellers_toolbox">
                  <i class="fa-regular fa-heart"></i>
                  <i class="fa-regular fa-eye"></i>
                </div>
              </div>
              <p class="mt-2">Antique Choker</p>
            </Slide>
            <Slide>
              <div class="best_seller_carosal_item">
                <img src="./assets/images/best_sellers/bs2.png" alt="bs2" />
                <div class="best_sellers_toolbox">
                  <i class="fa-regular fa-heart"></i>
                  <i class="fa-regular fa-eye"></i>
                </div>
              </div>
              <p class="mt-2">Antique Choker</p>
            </Slide>
            <Slide>
              <div class="best_seller_carosal_item">
                <img src="./assets/images/best_sellers/bs3.png" alt="bs3" />
                <div class="best_sellers_toolbox">
                  <i class="fa-regular fa-heart"></i>
                  <i class="fa-regular fa-eye"></i>
                </div>
              </div>
              <p class="mt-2">Antique Choker</p>
            </Slide>
          </Carousel>
        </div>
      </div>

      <!---------- Jwells for every movement -------------->
      <div class="jwells_for_every_movement py-4">
        <div class="container">
          <div class="jwells_for_every_movement_grid">
            <div class="jwells_pic">
              <img src="./assets/images/jwels_for_every_movement.png" alt="jwels_for_every_movement">
            </div>
            <div class="jwells_description ps-5">
              <h2>Jewels for Every Moment</h2>
              <p class="text-uppercase mb-2">Celebrate every occasion with the perfect sparkle.</p>
              <p>From weddings to workdays, find jewelry that matches your mood, moment, and memories.</p>
              <b-button class="btn_km mt-4 mb-3">Know More <img src="./assets/images/icons/right-arrow.svg" alt="right-arrow"></b-button>
            </div>
          </div>
        </div>
      </div>
      <!-- Shop by Occasion -->
       <div class="shop_by_occassion py-5">
        <div class="container">
          <h2>Shop by Occasion</h2>
          <p>Curated jewelry collections to match every milestone and moment.</p>
          <div class="shop_by_occassion_grid mt-3">
            <div
                class="shop_by_occassion_grid_item"
                v-for="image in shopbyoccassion"
                :key="image.id"
              >
                <img :src="image.url" :alt="image.title">

                <div class="shop_by_occassion_grid_item_toolbox">
                  <h6>{{ image.title }}</h6>

                  <b-button class="shop_by_view_more_link mt-2">
                    Know More
                    <img src="./assets/images/icons/right-arrow.svg" alt="right-arrow">
                  </b-button>
                </div>
              </div>
          </div>
        </div>
       </div>

       <!-- Embrace the Latest in Luxury -->
        <div class="embrace_the_latest_in_luxury py-4">
          <div class="container">
            <div class="embrace_the_latest_in_luxury_grid">
              <div class="embrace_the_latest_in_luxury_description pe-4">
                <h2>Embrace the Latest in Luxury</h2>
                <p>Be the first to adorn our newest arrivals, inspired by the latest trends and crafted with timeless style. Elevate any occasion with pieces from</p>
                <b-button class="btn_km mt-4 mb-3">Know More <img src="./assets/images/icons/right-arrow.svg" alt="right-arrow"></b-button>
              </div>
              <div class="embrace_the_latest_in_luxury_pic">
                <img src="./assets/images/embrace-the-latest-in-luxury.png" alt="embrace-the-latest-in-luxury">
              </div>
            </div>
          </div>
        </div>

        <!-- Featured Products -->
         <div class="featured_products py-5">          
          <div class="container">
            <h2>Featured Products</h2>
            <p>Hand-picked pieces our customers love the most.</p>
            <div class="featured_products_grid mt-4">
              <div class="featured_products_grid_item">
                <div class="featured_products_grid_item_pic">
                  <img src="./assets/images/best_sellers/bs1.png" alt="bs1" />
                  <div class="featured_products_toolbox">
                    <i class="fa-regular fa-heart"></i>
                    <i class="fa-regular fa-eye"></i>
                  </div>
                </div>
                <p class="mt-2">Antique Choker</p>
              </div>
              <div class="featured_products_grid_item">
                <div class="featured_products_grid_item_pic">
                  <img src="./assets/images/best_sellers/bs2.png" alt="bs2" />
                  <div class="featured_products_toolbox">
                    <i class="fa-regular fa-heart"></i>
                    <i class="fa-regular fa-eye"></i>
                  </div>
                </div>
                <p class="mt-2">Antique Choker</p>
              </div>
              <div class="featured_products_grid_item">
                <div class="featured_products_grid_item_pic">
                  <img src="./assets/images/best_sellers/bs3.png" alt="bs3" />
                  <div class="featured_products_toolbox">
                    <i class="fa-regular fa-heart"></i>
                    <i class="fa-regular fa-eye"></i>
                  </div>
                </div>
                <p class="mt-2">Antique Choker</p>
              </div>
              <div class="featured_products_grid_item">
                <div class="featured_products_grid_item_pic">
                  <img src="./assets/images/best_sellers/bs1.png" alt="bs1" />
                  <div class="featured_products_toolbox">
                    <i class="fa-regular fa-heart"></i>
                    <i class="fa-regular fa-eye"></i>
                  </div>
                </div>
                <p class="mt-2">Antique Choker</p>
              </div>
              <div class="featured_products_grid_item">
                <div class="featured_products_grid_item_pic">
                  <img src="./assets/images/best_sellers/bs2.png" alt="bs2" />
                  <div class="featured_products_toolbox">
                    <i class="fa-regular fa-heart"></i>
                    <i class="fa-regular fa-eye"></i>
                  </div>
                </div>
                <p class="mt-2">Antique Choker</p>
              </div>
              <div class="featured_products_grid_item">
                <div class="featured_products_grid_item_pic">
                  <img src="./assets/images/best_sellers/bs3.png" alt="bs3" />
                  <div class="featured_products_toolbox">
                    <i class="fa-regular fa-heart"></i>
                    <i class="fa-regular fa-eye"></i>
                  </div>
                </div>
                <p class="mt-2">Antique Choker</p>
              </div>
              <div class="featured_products_grid_item">
                <div class="featured_products_grid_item_pic">
                  <img src="./assets/images/best_sellers/bs3.png" alt="bs3" />
                  <div class="featured_products_toolbox">
                    <i class="fa-regular fa-heart"></i>
                    <i class="fa-regular fa-eye"></i>
                  </div>
                </div>
                <p class="mt-2">Antique Choker</p>
              </div>
              <div class="featured_products_grid_item">
                <div class="featured_products_grid_item_pic">
                  <img src="./assets/images/best_sellers/bs3.png" alt="bs3" />
                  <div class="featured_products_toolbox">
                    <i class="fa-regular fa-heart"></i>
                    <i class="fa-regular fa-eye"></i>
                  </div>
                </div>
                <p class="mt-2">Antique Choker</p>
              </div>
            </div>
          </div>
         </div>

         <!-- Real stories. Genuine sparkle -->
          <div class="realstories_genuine_sparkle py-5 mb-3">
            <div class="container">
              <h2>Real stories. Genuine sparkle.</h2>
              <p>4.9 <i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i> <span class="ms-1">| 131 Google reviews</span> </p>
              <div class="review_slider mt-4">
                <Carousel v-bind="reviewConfig">
                  <Slide v-for="(reviewItem, reviewIndex) in reviews" :key="reviewIndex">
                    <div class="review_text">
                      <p>{{ reviewItem.review }}</p>
                    </div>
                    <div class="review_profile">
                      <img src="./assets/images/review_profile.svg" class="review_profile_pic" alt="review_profile">
                      <div class="review_profile_info ms-2">
                        <p>{{ reviewItem.name }}</p>
                        <i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i>
                      </div>
                    </div>
                  </Slide>
                  <template #addons>
                    <div class="review_title">
                      <img src="./assets/images/review_quote.svg" class="review_quote mb-2" alt="review_quote" />
                      <h3>What Our<br class="deskShow"> Customers Say</h3>
                      <div class="review_toolbar mt-4">
                        <Navigation>
                          <template #prev>
                            <span><img src="./assets/images/icons/right-arrow.svg" class="review_left_arrow" alt="left-arrow"></span>
                          </template>
                          <template #next>
                            <span><img src="./assets/images/icons/right-arrow.svg" class="review_right_arrow" alt="right-arrow"></span>
                          </template>
                        </Navigation>
                        <div class="review_pagination"><Pagination /></div>
                      </div>
                    </div>                      
                  </template>
                </Carousel>
              </div>
            </div>
          </div>

         <!-- Our Services -->
          <div class="our_services py-5">
            <div class="yjs_container">
              <h2 class="mb-4">Our Services</h2>

              <div class="our_services_grid">
                <div
                  class="our_services_grid_item"
                  v-for="image in ourservices"
                  :key="image.id"
                >
                  <img :src="image.url" :alt="image.title" />

                  <div class="our_services_grid_item_info py-3">
                    <p>{{ image.title }}</p>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Be the first to know -->
           <div class="be_the_first_to_know py-5">
            <h2 class="mb-4">Be the first to know about sales & offers</h2>
            <div class="be_the_first_to_know_subscribe">
              <b-form-input placeholder="Your Email Address"></b-form-input>
              <b-button class="btn_km ms-2 px-4">Subscribe</b-button>
            </div>
           </div>


  </div>
</template>
