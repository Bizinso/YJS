
<template>
  <div class="yjs_product">

    <!-- ================= Breadcrumb ================= -->
    <div class="global_breadcrumbs">
      <div class="container">
        <ul class="breadcrumb">
          <li>
            <b-link to="/partner/products">Our Products</b-link>
          </li>
          <li>{{ category?.name || '-' }}</li>
        </ul>
      </div>
    </div>

    <!-- ================= Category Header ================= -->
    <div class="boxHeader OrangeBox" v-if="category">
      <div class="container cardBox">
        <div class="infoBase">
          <h2 class="globalHeading">
            01. {{ category.name?.toUpperCase() }}
          </h2>

          <p class="basicDetails">
            {{ category.description || 'Explore our premium collection.' }}
          </p>

          <div class="linkingBTN">
            
            <button class="buttonSelector">
              <img
                src="../assets/images/static/product/download.svg"
                alt="Download"
                class="iconBox"
              />
              Download Brochure
            </button>
          </div>
        </div>

        <!-- Optional Static Banner -->
        <!-- <img
          src="../assets/images/static/product/earringsBanner.png"
          alt="category-banner"
          class="namingBanner"
        /> -->
      </div>
    </div>

    <!-- ================= Product Listing ================= -->
    <div class="container">

      <!-- Loader -->
      <div v-if="loading" class="text-center py-5">
        Loading products...
      </div>

      <!-- Empty State -->
      <div v-else-if="!products.length" class="text-center py-5">
        No products found.
      </div>

      <!-- Products -->
      <div v-else class="productlist">

        <b-link
          v-for="product in products"
          :key="product.id"
          :to="{
            name: 'partnerproductsDetails',
            params: { id: encodeBase64(product.id) }
          }"
          class="productItem"
        >
          <div class="productImage">
            <img
              :src="product.main_image
                ? `/storage/${product.main_image}`
                : '../assets/images/static/product/default.png'"
              :alt="product.name"
              class="banner"
            />

            <div class="productIcons">
              <i class="fa-regular fa-heart"></i>
              <i class="fa-regular fa-eye"></i>
            </div>
          </div>

          <p class="productName">
            {{ product.sku }}
          </p>
        </b-link>

      </div>
    </div>

  </div>
</template>

<script setup>
import { useRoute } from 'vue-router'
import { onMounted, ref, watch } from 'vue'
import axiosPartner from '@axiosPartner'

const route = useRoute()

const categorySlug = ref(route.params.categoryname)
const category = ref({})
const products = ref([])
const loading = ref(false)


const encodeBase64 = (data) => {
  if (data === undefined || data === null) {
    return "";
  }
  return btoa(data.toString());
};

const fetchCategoryProducts = async () => {
  if (!categorySlug.value) return

  loading.value = true
  try {
    const { data } = await axiosPartner.get(
      `/category/${categorySlug.value}`
    )

    category.value = data.category || {}
    products.value = data.products || []
  } catch (error) {
    console.error('Failed to fetch category products', error)
  } finally {
    loading.value = false
  }
}

onMounted(fetchCategoryProducts)
onMounted(() => {
  window.scrollTo({
      top: 0,
      behavior: "smooth"
    });
});
// 🔁 Re-fetch when route param changes
watch(
  () => route.params.categoryname,
  (newSlug, oldSlug) => {
    if (newSlug && newSlug !== oldSlug) {
      categorySlug.value = newSlug
      fetchCategoryProducts()
    }
  }
)
</script>
