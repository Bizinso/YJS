// Utility functions for localStorage cart and wishlist management

/**
 * Get cart items from localStorage
 */
export const getLocalCart = () => {
  try {
    const cart = localStorage.getItem('localCart');
    return cart ? JSON.parse(cart) : [];
  } catch (error) {
    console.error('Error reading local cart:', error);
    return [];
  }
};

/**
 * Save cart items to localStorage
 */
export const saveLocalCart = (cartItems) => {
  try {
    localStorage.setItem('localCart', JSON.stringify(cartItems));
    window.dispatchEvent(new Event('cart-updated'));
  } catch (error) {
    console.error('Error saving local cart:', error);
  }
};

/**
 * Add product to local cart
 */
export const addToLocalCart = (product, quantity = 1) => {
  const cart = getLocalCart();
  const productId = product.id || product.product_id;
  
  if (!productId) {
    console.error('Product ID not found:', product);
    return cart;
  }
  
  const existingIndex = cart.findIndex(item => 
    (item.product_id === productId) || (item.id === productId)
  );
  
  if (existingIndex >= 0) {
    // Update quantity if product already exists
    cart[existingIndex].quantity = (cart[existingIndex].quantity || 1) + quantity;
  } else {
    // Add new product
    cart.push({
      product_id: productId,
      quantity: quantity,
      product: product, // Store full product data for sync
      added_at: new Date().toISOString()
    });
  }
  
  saveLocalCart(cart);
  console.log('Cart updated in localStorage:', cart);
  return cart;
};

/**
 * Remove product from local cart
 */
export const removeFromLocalCart = (productId) => {
  const cart = getLocalCart();
  const filtered = cart.filter(item => item.product_id !== productId);
  saveLocalCart(filtered);
  return filtered;
};

/**
 * Get cart count from localStorage
 */
export const getLocalCartCount = () => {
  const cart = getLocalCart();
  return cart.reduce((total, item) => total + (item.quantity || 1), 0);
};

/**
 * Clear local cart
 */
export const clearLocalCart = () => {
  localStorage.removeItem('localCart');
  window.dispatchEvent(new Event('cart-updated'));
};

/**
 * Get wishlist items from localStorage
 */
export const getLocalWishlist = () => {
  try {
    const wishlist = localStorage.getItem('localWishlist');
    return wishlist ? JSON.parse(wishlist) : [];
  } catch (error) {
    console.error('Error reading local wishlist:', error);
    return [];
  }
};

/**
 * Save wishlist items to localStorage
 */
export const saveLocalWishlist = (wishlistItems) => {
  try {
    localStorage.setItem('localWishlist', JSON.stringify(wishlistItems));
    window.dispatchEvent(new Event('cart-updated'));
  } catch (error) {
    console.error('Error saving local wishlist:', error);
  }
};

/**
 * Toggle product in local wishlist
 */
export const toggleLocalWishlist = (product) => {
  const wishlist = getLocalWishlist();
  const productId = product.id || product.product_id;
  
  if (!productId) {
    console.error('Product ID not found:', product);
    return wishlist;
  }
  
  const existingIndex = wishlist.findIndex(item => 
    (item.product_id === productId) || (item.id === productId)
  );
  
  if (existingIndex >= 0) {
    // Remove from wishlist
    wishlist.splice(existingIndex, 1);
  } else {
    // Add to wishlist
    wishlist.push({
      product_id: productId,
      product: product, // Store full product data for sync
      added_at: new Date().toISOString()
    });
  }
  
  saveLocalWishlist(wishlist);
  console.log('Wishlist updated in localStorage:', wishlist);
  return wishlist;
};

/**
 * Check if product is in local wishlist
 */
export const isInLocalWishlist = (productId) => {
  const wishlist = getLocalWishlist();
  return wishlist.some(item => 
    (item.product_id === productId) || (item.id === productId)
  );
};

/**
 * Get wishlist count from localStorage
 */
export const getLocalWishlistCount = () => {
  return getLocalWishlist().length;
};

/**
 * Clear local wishlist
 */
export const clearLocalWishlist = () => {
  localStorage.removeItem('localWishlist');
  window.dispatchEvent(new Event('cart-updated'));
};

/**
 * Sync local cart to backend using bulk sync endpoint
 */
export const syncLocalCartToBackend = async (axiosInstance) => {
  const localCart = getLocalCart();
  console.log('Local cart items to sync:', localCart);
  
  if (localCart.length === 0) {
    console.log('No cart items to sync');
    return { success: true, synced: 0 };
  }
  
  try {
    // Prepare items array for bulk sync - ensure product_id is present
    const items = localCart
      .filter(item => item.product_id || item.id) // Filter out invalid items
      .map(item => ({
        product_id: item.product_id || item.id,
        quantity: item.quantity || 1
      }));
    
    if (items.length === 0) {
      console.log('No valid cart items to sync after filtering');
      return { success: true, synced: 0 };
    }
    
    console.log('Sending cart sync request with items:', items);
    
    const response = await axiosInstance.post('/cart/sync', {
      items: items
    });
    
    console.log('Cart sync response:', response.data);
    
    const synced = response.data.synced || 0;
    const errors = response.data.errors || [];
    
    // Clear local cart after successful sync
    if (synced > 0 || response.data.success) {
      clearLocalCart();
      console.log('Local cart cleared after sync');
    }
    
    return { 
      success: errors.length === 0, 
      synced, 
      errors,
      message: response.data.message 
    };
  } catch (error) {
    console.error('Error syncing cart:', error);
    console.error('Error response:', error.response?.data);
    console.error('Error status:', error.response?.status);
    return { 
      success: false, 
      synced: 0, 
      errors: [{ error: error.response?.data?.message || error.message }] 
    };
  }
};

/**
 * Sync local wishlist to backend using bulk sync endpoint
 */
export const syncLocalWishlistToBackend = async (axiosInstance) => {
  const localWishlist = getLocalWishlist();
  console.log('Local wishlist items to sync:', localWishlist);
  
  if (localWishlist.length === 0) {
    console.log('No wishlist items to sync');
    return { success: true, synced: 0 };
  }
  
  try {
    // Prepare items array for bulk sync - ensure product_id is present
    const items = localWishlist
      .filter(item => item.product_id || item.id) // Filter out invalid items
      .map(item => ({
        product_id: item.product_id || item.id
      }));
    
    if (items.length === 0) {
      console.log('No valid wishlist items to sync after filtering');
      return { success: true, synced: 0 };
    }
    
    console.log('Sending wishlist sync request with items:', items);
    
    const response = await axiosInstance.post('/wishlists/sync', {
      items: items
    });
    
    console.log('Wishlist sync response:', response.data);
    
    const synced = response.data.synced || 0;
    const errors = response.data.errors || [];
    
    // Clear local wishlist after successful sync
    if (synced > 0 || response.data.success) {
      clearLocalWishlist();
      console.log('Local wishlist cleared after sync');
    }
    
    return { 
      success: errors.length === 0, 
      synced, 
      errors,
      message: response.data.message 
    };
  } catch (error) {
    console.error('Error syncing wishlist:', error);
    console.error('Error response:', error.response?.data);
    console.error('Error status:', error.response?.status);
    return { 
      success: false, 
      synced: 0, 
      errors: [{ error: error.response?.data?.message || error.message }] 
    };
  }
};

/**
 * Sync both cart and wishlist to backend
 */
export const syncLocalDataToBackend = async (axiosInstance) => {
  const cartResult = await syncLocalCartToBackend(axiosInstance);
  const wishlistResult = await syncLocalWishlistToBackend(axiosInstance);
  
  return {
    cart: cartResult,
    wishlist: wishlistResult,
    totalSynced: cartResult.synced + wishlistResult.synced
  };
};

