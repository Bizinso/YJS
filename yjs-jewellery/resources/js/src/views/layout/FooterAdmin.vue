<template>
  <div class="footer_screen px-3 py-1">
    <span>© {{ currentYear }} Bizinso Pvt. Ltd.</span>
  </div>
</template>

<script setup>
import { ref } from 'vue';

const currentYear = new Date().getFullYear();
</script>
