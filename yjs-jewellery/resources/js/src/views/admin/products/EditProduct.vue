<template>
  <ProductForm 
    :isEditing="true"
    :productId="decodedProductId.toString()"
    @submitted="handleSubmit"
    @cancel="goBack"
  />

</template>

<script setup>
import {  computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import ProductForm from './ProductForm.vue'

const router = useRouter()
const route = useRoute()

const decodedProductId = computed(() => {
  try {

    const encodedId = route.params.id

    const decodedString = atob(encodedId)
    
    return decodedString
    
  } catch (error) {
    router.push('/admin/products')
    return null
  }
})

const handleSubmit = () => {
  router.push('/admin/products')
}

const goBack = () => {
  router.push('/admin/products')
}
</script>