<template>
  <div class="yjs_product">
    <div v-for="(category, index) in categories" :key="category.id">
      <!-- Category Header -->
      <div :class="['boxHeader', index % 2 === 0 ? 'OrangeBox' : 'BlueBox']">
        <div class="container cardBox">
          <div class="infoBase">
            <h2 class="globalHeading">{{ index + 1 }}. {{ category.name.toUpperCase() }}</h2>
            <p class="basicDetails">{{ category.description }}</p>
            <div class="linkingBTN">
              <b-link :to="{
                name: 'productsSelected',
                params: { categoryname: category.slug }
              }" class="buttonSelector">
                View all products
              </b-link>
              <button class="buttonSelector">
                <img :src="index % 2 === 0 ? downloadImg : downloadWhiteImg" alt="Download" class="iconBox">
                Download Brochure
              </button>
            </div>
          </div>
          <!-- <img :src="getCategoryBanner(category.name)" alt="banner" class="namingBanner" /> -->
        </div>
      </div>

      <!-- Products List -->
      <div class="container">
        <div class="productlist">
          <b-link v-for="product in filteredProducts(category.products)" :key="product.id"
            :to="{ name: 'partnerproductsDetails', params: { id: encodeBase64(product.id) } }" class="productItem">
            <div class="productImage">
              <img :src="getProductImage(product)" :alt="product.name" class="banner" />
              <div class="productIcons">
                <i class="fa-regular fa-heart"></i>
                <i class="fa-regular fa-eye"></i>
              </div>
            </div>
            <p class="productName">{{ product.name }}</p>
          </b-link>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axiosPartner from '@axiosPartner';
import downloadImg from '../assets/images/static/product/download.svg';
import downloadWhiteImg from '../assets/images/static/product/downloadwhite.svg';
const categories = ref([])

const encodeBase64 = (data) => {
  if (data === undefined || data === null) {
    return "";
  }
  return btoa(data.toString());
};

onMounted(async () => {
  window.scrollTo({
      top: 0,
      behavior: "smooth"
    });
  try {
    const { data } = await axiosPartner.get("/products")
    categories.value = data.data
  } catch (e) {
    console.error("Failed to load products", e)
  }
})

// Methods
const getCategoryBanner = (categoryName) => {
  const banners = {
    'Ring': '../../assets/images/static/product/ringsBanner.png',
    'Kada': '../../assets/images/static/product/kadasBanner.png',
    'Bangles': '../../assets/images/static/product/banglesBanner.png',
  }
  return banners[categoryName] || '../../assets/images/static/product/defaultBanner.png'
}

const getProductImage = (product) => {
  const tags = JSON.parse(product.tags_id || '[]')
  if (tags.includes('Configurable')) {
    return product.main_image ? `/storage/product_variants/${product.main_image.split('/').pop()}` : '../assets/images/static/product/defaultProduct.png'
  }
  return product.main_image ? `/storage/products/${product.main_image.split('/').pop()}` : '../assets/images/static/product/defaultProduct.png'
}

// Filter products to include only Simple, Ready-made, Configurable
const filteredProducts = (products) => {
  return products.filter(product => {
    const tags = JSON.parse(product.tags_id || '[]')
    return tags.some(tag => ['Simple', 'Ready-made', 'Configurable'].includes(tag))
  })
}
</script>

<style scoped>
/* Keep your existing styles */
</style>
