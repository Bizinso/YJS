<template>
  <ProductForm 
    :isEditing="false"
    :productId="''"
    @submitted="handleSubmit"
    @cancel="goBack"
  />
</template>

<script setup>
import { useRouter } from 'vue-router'
import ProductForm from './ProductForm.vue'

const router = useRouter()

const handleSubmit = () => {
  router.push('/admin/products')
}

const goBack = () => {
  router.push('/admin/products')
}
</script>