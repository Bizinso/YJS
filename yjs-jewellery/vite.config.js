import { defineConfig } from 'vite'
import laravel from 'laravel-vite-plugin'
import vue from '@vitejs/plugin-vue'
import path from 'path'

export default defineConfig({
  plugins: [
    vue(),
    laravel({
      input: ['resources/css/app.css', 'resources/js/app.js'],
      refresh: true,
    }),
  ],
  resolve: {
    alias: {
      // Base alias for your source folder
      '@': path.resolve(__dirname, 'resources/js/src'),

      // Common axios instance (if any)
      '@axios': path.resolve(__dirname, 'resources/js/src/axios.js'),

      // Separate axios instances for different logins
      '@axiosEmployee': path.resolve(__dirname, 'resources/js/src/plugins/axiosEmployee.js'),
      '@axiosCustomer': path.resolve(__dirname, 'resources/js/src/plugins/axiosCustomer.js'),
      '@axiosPartner': path.resolve(__dirname, 'resources/js/src/plugins/axiosPartner.js'),
    },
  },
})
