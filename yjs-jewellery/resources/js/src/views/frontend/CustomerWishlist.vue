<template>
    <div class="wishlist-page">
        <div class="container">
            <div class="page-header">
                <div>
                    <h1>My Wishlist</h1>
                    <p>{{ wishlist.length }} item(s) saved</p>
                </div>
                <button v-if="wishlist.length > 0" class="btn-outline" @click="clearWishlist">
                    <i class="bi-trash"></i> Clear All
                </button>
            </div>

            <div v-if="loading" class="loading-state">
                <div class="spinner"></div>
                <p>Loading wishlist...</p>
            </div>

            <div v-else-if="wishlist.length === 0" class="empty-state">
                <i class="bi-heart"></i>
                <h3>Your wishlist is empty</h3>
                <p>Save items you love to your wishlist and buy them later!</p>
                <router-link to="/products" class="btn-primary">Browse Products</router-link>
            </div>

            <div v-else class="wishlist-grid">
                <div v-for="item in wishlist" :key="item.id" class="wishlist-card">
                    <button class="remove-btn" @click="removeFromWishlist(item)">
                        <i class="bi-x-lg"></i>
                    </button>

                    <router-link :to="`/products/${item.product?.slug}`" class="product-image">
                        <img :src="item.product?.image || '/images/placeholder.png'" :alt="item.product?.name" />
                        <span v-if="!item.product?.in_stock" class="out-of-stock">Out of Stock</span>
                    </router-link>

                    <div class="product-details">
                        <router-link :to="`/products/${item.product?.slug}`" class="product-name">
                            {{ item.product?.name }}
                        </router-link>
                        <p class="product-category">{{ item.product?.category?.name }}</p>

                        <div class="price-section">
                            <span class="current-price">₹{{ formatPrice(item.product?.selling_price || item.product?.price) }}</span>
                            <span v-if="item.product?.mrp > item.product?.selling_price" class="original-price">
                                ₹{{ formatPrice(item.product?.mrp) }}
                            </span>
                            <span v-if="getDiscount(item.product)" class="discount">
                                {{ getDiscount(item.product) }}% off
                            </span>
                        </div>

                        <div class="card-actions">
                            <button v-if="item.product?.in_stock" class="btn-primary" @click="addToCart(item)">
                                <i class="bi-cart-plus"></i> Add to Cart
                            </button>
                            <button v-else class="btn-secondary" @click="notifyMe(item)">
                                <i class="bi-bell"></i> Notify Me
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'

const router = useRouter()
const wishlist = ref([])
const loading = ref(true)

const getAuthHeaders = () => {
    const token = localStorage.getItem('token')
    return { Authorization: `Bearer ${token}` }
}

const fetchWishlist = async () => {
    loading.value = true
    try {
        const response = await axios.get('/api/customer/wishlist', {
            headers: getAuthHeaders()
        })
        wishlist.value = response.data.data || response.data || []
    } catch (error) {
        console.error('Failed to fetch wishlist:', error)
    } finally {
        loading.value = false
    }
}

const removeFromWishlist = async (item) => {
    try {
        await axios.delete(`/api/customer/wishlist/${item.id}`, {
            headers: getAuthHeaders()
        })
        wishlist.value = wishlist.value.filter(w => w.id !== item.id)
    } catch (error) {
        alert('Failed to remove item from wishlist')
    }
}

const clearWishlist = async () => {
    if (!confirm('Are you sure you want to clear your wishlist?')) return

    try {
        await axios.delete('/api/customer/wishlist', {
            headers: getAuthHeaders()
        })
        wishlist.value = []
    } catch (error) {
        alert('Failed to clear wishlist')
    }
}

const addToCart = async (item) => {
    try {
        await axios.post('/api/customer/cart', {
            product_id: item.product?.id,
            quantity: 1
        }, {
            headers: getAuthHeaders()
        })
        alert('Item added to cart!')
    } catch (error) {
        alert(error.response?.data?.message || 'Failed to add to cart')
    }
}

const notifyMe = async (item) => {
    try {
        await axios.post('/api/customer/notify-stock', {
            product_id: item.product?.id
        }, {
            headers: getAuthHeaders()
        })
        alert('We will notify you when this item is back in stock!')
    } catch (error) {
        alert('Failed to set notification')
    }
}

const formatPrice = (price) => {
    return parseFloat(price).toLocaleString('en-IN')
}

const getDiscount = (product) => {
    if (!product?.mrp || !product?.selling_price) return 0
    if (product.mrp <= product.selling_price) return 0
    return Math.round(((product.mrp - product.selling_price) / product.mrp) * 100)
}

onMounted(() => {
    fetchWishlist()
})
</script>

<style scoped>
.wishlist-page {
    min-height: 100vh;
    background: #f8f9fa;
    padding: 30px 0;
}

.container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
}

.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
}

.page-header h1 {
    font-size: 28px;
    color: #1a1a2e;
    margin-bottom: 8px;
}

.page-header p {
    color: #666;
}

.btn-outline {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    background: transparent;
    border: 2px solid #dc3545;
    color: #dc3545;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 500;
    transition: all 0.3s;
}

.btn-outline:hover {
    background: #dc3545;
    color: #fff;
}

.loading-state {
    text-align: center;
    padding: 60px 20px;
}

.spinner {
    width: 40px;
    height: 40px;
    border: 3px solid #e0e0e0;
    border-top-color: #b8860b;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
    margin: 0 auto 15px;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

.empty-state {
    text-align: center;
    padding: 60px 20px;
    background: #fff;
    border-radius: 12px;
}

.empty-state i {
    font-size: 64px;
    color: #ddd;
    margin-bottom: 20px;
}

.empty-state h3 {
    color: #333;
    margin-bottom: 10px;
}

.empty-state p {
    color: #666;
    margin-bottom: 20px;
}

.wishlist-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 25px;
}

.wishlist-card {
    background: #fff;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    position: relative;
    transition: transform 0.3s, box-shadow 0.3s;
}

.wishlist-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.1);
}

.remove-btn {
    position: absolute;
    top: 10px;
    right: 10px;
    width: 32px;
    height: 32px;
    background: #fff;
    border: none;
    border-radius: 50%;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 2px 8px rgba(0,0,0,0.15);
    z-index: 10;
    transition: all 0.3s;
}

.remove-btn:hover {
    background: #dc3545;
    color: #fff;
}

.product-image {
    display: block;
    position: relative;
    height: 250px;
    overflow: hidden;
}

.product-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s;
}

.wishlist-card:hover .product-image img {
    transform: scale(1.05);
}

.out-of-stock {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: rgba(0,0,0,0.7);
    color: #fff;
    padding: 8px 16px;
    border-radius: 4px;
    font-size: 14px;
    font-weight: 500;
}

.product-details {
    padding: 20px;
}

.product-name {
    display: block;
    font-size: 16px;
    font-weight: 600;
    color: #1a1a2e;
    text-decoration: none;
    margin-bottom: 5px;
    line-height: 1.4;
}

.product-name:hover {
    color: #b8860b;
}

.product-category {
    font-size: 13px;
    color: #666;
    margin-bottom: 12px;
}

.price-section {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 15px;
    flex-wrap: wrap;
}

.current-price {
    font-size: 20px;
    font-weight: 700;
    color: #1a1a2e;
}

.original-price {
    font-size: 14px;
    color: #999;
    text-decoration: line-through;
}

.discount {
    font-size: 12px;
    font-weight: 600;
    color: #28a745;
    background: #e8f5e9;
    padding: 3px 8px;
    border-radius: 4px;
}

.card-actions {
    display: flex;
    gap: 10px;
}

.btn-primary {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 12px 20px;
    background: linear-gradient(135deg, #b8860b 0%, #daa520 100%);
    color: #fff;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    text-decoration: none;
    transition: all 0.3s;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(184, 134, 11, 0.4);
}

.btn-secondary {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 12px 20px;
    background: #f5f5f5;
    color: #333;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
}

.btn-secondary:hover {
    background: #e9ecef;
}

@media (max-width: 768px) {
    .page-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 15px;
    }

    .wishlist-grid {
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    }
}
</style>
