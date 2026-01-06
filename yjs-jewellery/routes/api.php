<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\CommonController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\TagController;
use App\Http\Controllers\MetalTypeController;
use App\Http\Controllers\MetalNameController;
use App\Http\Controllers\PurityController;
use App\Http\Controllers\AttributeController;
use App\Http\Controllers\AdditionalChargesController;
use App\Http\Controllers\TaxController;
use App\Http\Controllers\ProductTypeController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\PartnerController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\Customer\CustomerProfileController;
use App\Http\Controllers\Customer\CustomerOrderController;
use App\Http\Controllers\WishlistController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\Customer\CustomerPasswordController;
use App\Http\Controllers\Customer\CheckoutController;
use App\Http\Controllers\Partner\PartnerProfileController;
use App\Http\Controllers\Partner\PartnerPasswordController;
use App\Http\Controllers\Partner\PartnerOrderController;
use App\Http\Controllers\Partner\PartnerDashboardController;


/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/
Route::prefix('employee')->group(function () {
    Route::post('/login', [AuthController::class, 'employeelogin']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'employeelogout']);
        Route::get('/menus', [CommonController::class, 'adminmenus']);
        Route::get('/profile', [AuthController::class, 'adminprofile']);
        Route::post('/profile/{id}', [AuthController::class, 'updateadminprofile']);
        Route::get('/countries', [CommonController::class, 'getCountries']);
        Route::get('/states/{country_id}', [CommonController::class, 'getStates']);
        Route::get('/cities/{state_id}', [CommonController::class, 'getCities']);
        Route::post('/address', [EmployeeController::class, 'addAddress']);
        Route::get('/address/{id}', [EmployeeController::class, 'getAddress']);
        Route::post('/change-password', [EmployeeController::class, 'changePassword']);
        Route::apiResource('allBranches', BranchController::class);
        Route::apiResource('allDepartments', DepartmentController::class);
        Route::apiResource('roles', RoleController ::class);
        Route::apiResource('employees', EmployeeController::class);
        Route::get('/master-counts', [CommonController::class, 'getAllCounts']);
        Route::get('/DepartmentsOption', [DepartmentController::class, 'departmentOption']);
        Route::get('/BranchesOption', [BranchController::class, 'branchOption']);
        Route::get('/RoleDepartmentOptions', [RoleController::class, 'roleDepartmentOptions']);
        Route::get('/RolesOption', [RoleController::class, 'roleOptions']);
        Route::get('/EmployeeOption', [EmployeeController::class, 'employeeOption']);
        Route::get('/EmployeeCode', [EmployeeController::class, 'employeeCode']);
        Route::get('/ReportingToOptions', [EmployeeController::class, 'reportingToOption']);
        Route::get('/DepartmentEmpOption', [EmployeeController::class, 'departmentEmpOption']);
        Route::get('/RoleEmpOption', [EmployeeController::class, 'roleEmpOption']);
        Route::post('employeeStatus/{id}/change-status', [EmployeeController::class, 'changeStatus']);
        Route::get('/RolesByDepartment/{id}', [EmployeeController::class, 'roleOptions']);
        Route::resource('/permissions', PermissionController::class);
        Route::get('/getPermissionFromUserId/{id}', [PermissionController::class, 'getPermissionFromUserId']);
        Route::get('/getactivity', [ActivityLogController::class, 'index']);
        Route::get('/getactivitydetail/{id}', [ActivityLogController::class, 'getActivityDetail']);
        Route::get('/alluser', [ActivityLogController::class, 'getUser']);
        Route::get('/getlogname', [ActivityLogController::class, 'getLogName']);
        Route::resource('category', CategoryController::class);
        Route::post('category/{id}/change-status', [CategoryController::class, 'changeStatus']);
        Route::get('subCategoryOptions', [CategoryController::class, 'subCategoryOptions']);
        Route::get('/sub-categories', [CategoryController::class, 'subCategoryIndex']);
        Route::resource('tag', TagController::class);
        Route::post('tags/{id}/change-status', [TagController::class, 'changeStatus']);
        Route::get('getMetalNameOptions', [MetalNameController::class, 'index']);
        Route::get('getPurityOptions', [MetalTypeController::class, 'getPurityOptions']);
        Route::resource('metal-type', MetalTypeController::class);
        Route::post('metal-name', [MetalNameController::class, 'store']);
        Route::put('metal-name/{id}', [MetalNameController::class, 'update']);
        Route::delete('metal-name/{id}', [MetalNameController::class, 'destroy']);
        Route::resource('purity', PurityController::class);
        Route::resource('attribute', AttributeController::class);
        Route::get('attributeDataTypeOption', [AttributeController::class, 'attributeDataTypeOption']);
        Route::post('attribute/{id}/change-status', [AttributeController::class, 'changeStatus']);
        Route::resource('additional-charges', AdditionalChargesController::class);
        Route::get('additionalChargesTypesOptions', [AdditionalChargesController::class, 'additionalChargesTypesOptions']);
        Route::resource('tax', TaxController::class);
        Route::resource('product-type', ProductTypeController::class);
        Route::post('product-type/{id}/change-status', [ProductTypeController::class, 'changeStatus']);
        Route::get('/CategoryOptions', [ProductController::class, 'getCategoryOptions']);
        Route::get('/ProductTypeOptions', [ProductController::class, 'getProductTypeOptions']);
        Route::get('/TagOptions', [ProductController::class, 'getTagOptions']);
        Route::get('/MaterialTypeOptions', [ProductController::class, 'getMaterialTypeOptions']);
        Route::get('/VariantAttributeOption', [ProductController::class, 'getVariantAttributeOption']);
        Route::get('/products/related', [ProductController::class, 'getRelatedProducts']);
        Route::apiResource('products', ProductController::class);
        Route::get('/tax-master-options', [ProductController::class, 'getTaxMasterOptions']);
        Route::get('/charge-applications-options', [ProductController::class, 'getChargeApplicationsOptions']);
        Route::get('/generateProcessId', [ProductController::class, 'generateProcessId']);
        Route::get('/SubCategoryOptions/{categoryId}', [ProductController::class, 'getSubCategoryOptions']);
        Route::get('/fetchSku', [ProductController::class, 'fetchSku']);
        Route::post('/products/validate-tab/{tabIndex}', [ProductController::class, 'validateTab']);
        Route::get('/PurityOptions/{metalTypeId}', [ProductController::class, 'getPurityOptions']);
        Route::get('/generateBasePrice', [ProductController::class, 'generateBasePrice']);
        Route::get('products/{id}/edit', [ProductController::class, 'edit']);
        Route::get('/products/charges/{product}', [ProductController::class, 'getCharges']);
        Route::get('/products/taxes/{product}', [ProductController::class, 'getTaxes']);
        Route::get('/products/{product}/media', [ProductController::class, 'getMedia']);
        Route::get('/products/{product}/variant-media', [ProductController::class, 'getVariantMedia']);
        Route::post('/products/bulk-delete', [ProductController::class, 'bulkDelete']);
        Route::post('/products/{product}/duplicate', [ProductController::class, 'duplicate']);
        Route::post('products/{id}/change-status', [ProductController::class, 'changeProductStatus']);
        Route::post('/products/{id}/toggle-featured', [ProductController::class, 'toggleFeatured']);
        Route::get('/VariantOptions', [ProductController::class, 'getVariantOptions']);
        Route::get('/VariantOptionsFetchTable', [ProductController::class, 'getVariantOptionsFetchTable']);
        Route::get('/partner-options', [CommonController::class, 'getallPartners']);
        Route::get('partners', [PartnerController::class, 'index']);
        Route::post('partners/change-status', [PartnerController::class, 'changeStatus']);
        Route::post('partners/bulk-delete', [PartnerController::class, 'bulkDelete']);
        Route::get('partners/{id}', [PartnerController::class, 'show']);
        Route::put('partners/{id}', [PartnerController::class, 'update']);
        Route::get('customerlist/', [CustomerController::class, 'index']);
        Route::get('/statistics', [CustomerController::class, 'statistics']);
        Route::get('/customer/view/{id}', [CustomerController::class, 'show']);
        Route::post('customer/store/', [CustomerController::class, 'store']);
        Route::put('/customer/update{id}', [CustomerController::class, 'update']);
        Route::post('/customers/change-status', [CustomerController::class, 'changeStatus']);
        Route::post('/bulk-delete', [CustomerController::class, 'bulkDelete']);
    });
});

Route::prefix('customer')->group(function () {
    Route::post('/send-otp', [AuthController::class, 'sendOTP']);
    Route::post('/verify-otp', [AuthController::class, 'verifyOTP']);
    Route::get('/products', [ProductController::class, 'productListing']);
    Route::get('/filter/getCategoryOptions', [CategoryController::class, 'getCategoryOptions']);
    Route::get('/filter/getPurityOptions', [MetalTypeController::class, 'getPurityOptions']);
    Route::get('/filter/Occasion', [CategoryController::class, 'Occasion']);
    Route::get('/filter/CategoryOptions', [ProductController::class, 'getCategoryOptions']);
    Route::get('/filter/SubCategoryOptions/{categoryId}', [ProductController::class, 'getSubCategoryOptions']);

    Route::get('/product/{id}', [ProductController::class, 'viewProduct']);
    Route::post('/products/relatedProducts', [ProductController::class, 'relatedProducts']);

    // Public review routes
    Route::get('/products/{productId}/reviews', [ReviewController::class, 'productReviews']);

    // Public password routes
    Route::post('/forgot-password', [CustomerPasswordController::class, 'forgotPassword']);
    Route::post('/verify-reset-otp', [CustomerPasswordController::class, 'verifyResetOtp']);
    Route::post('/reset-password', [CustomerPasswordController::class, 'resetPassword']);
    Route::post('/login-password', [CustomerPasswordController::class, 'loginWithPassword']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'customerlogout']);

        // Cart routes
        Route::get('/cart', [CartController::class, 'index']);
        Route::post('/cart', [CartController::class, 'store']);
        Route::put('/cart/{id}', [CartController::class, 'update']);
        Route::delete('/cart/{id}', [CartController::class, 'destroy']);
        Route::delete('/cart', [CartController::class, 'clear']);
        Route::post('/cart/sync', [CartController::class, 'syncFromLocalStorage']);
        Route::get('/cart/count', [CartController::class, 'cartCount']);

        // Checkout routes
        Route::get('/checkout/summary', [CheckoutController::class, 'getSummary']);
        Route::post('/checkout/serviceability', [CheckoutController::class, 'checkServiceability']);
        Route::post('/checkout/validate', [CheckoutController::class, 'validateCart']);
        Route::post('/checkout/coupon', [CheckoutController::class, 'applyCoupon']);
        Route::delete('/checkout/coupon', [CheckoutController::class, 'removeCoupon']);
        Route::post('/checkout/order', [CheckoutController::class, 'createOrder']);

        // Offer routes
        Route::get('/offers/applicable', [App\Http\Controllers\OffersController::class, 'getApplicable']);
        Route::post('/offers/apply', [App\Http\Controllers\OffersController::class, 'apply']);
        Route::delete('/offers/remove', [App\Http\Controllers\OffersController::class, 'remove']);
        Route::post('/offers/validate-coupon', [App\Http\Controllers\OffersController::class, 'validateCoupon']);

        // Profile routes
        Route::get('/profile', [CustomerProfileController::class, 'getProfile']);
        Route::put('/profile', [CustomerProfileController::class, 'updateProfile']);
        Route::post('/profile/avatar', [CustomerProfileController::class, 'uploadAvatar']);
        Route::delete('/profile/avatar', [CustomerProfileController::class, 'removeAvatar']);

        // Address routes
        Route::get('/addresses', [CustomerProfileController::class, 'getAddresses']);
        Route::get('/addresses/{id}', [CustomerProfileController::class, 'getAddress']);
        Route::post('/addresses', [CustomerProfileController::class, 'storeAddress']);
        Route::put('/addresses/{id}', [CustomerProfileController::class, 'updateAddress']);
        Route::delete('/addresses/{id}', [CustomerProfileController::class, 'deleteAddress']);
        Route::post('/addresses/{id}/default', [CustomerProfileController::class, 'setDefaultAddress']);

        // Order routes
        Route::get('/orders', [CustomerOrderController::class, 'index']);
        Route::get('/orders/statistics', [CustomerOrderController::class, 'statistics']);
        Route::get('/orders/{id}', [CustomerOrderController::class, 'show']);
        Route::post('/orders/{id}/cancel', [CustomerOrderController::class, 'cancel']);
        Route::get('/orders/{id}/tracking', [CustomerOrderController::class, 'tracking']);

        // Wishlist routes
        Route::get('/wishlists', [WishlistController::class, 'index']);
        Route::post('/wishlists', [WishlistController::class, 'store']);
        Route::delete('/wishlists/{id}', [WishlistController::class, 'destroy']);
        Route::post('/wishlists/toggle', [WishlistController::class, 'toggle']);
        Route::post('/wishlists/sync', [WishlistController::class, 'sync']);
        Route::post('/wishlists/check', [WishlistController::class, 'check']);
        Route::get('/wishlists/count', [WishlistController::class, 'count']);
        Route::delete('/wishlists', [WishlistController::class, 'clear']);
        Route::post('/wishlists/{id}/move-to-cart', [WishlistController::class, 'moveToCart']);

        // Review routes
        Route::get('/reviews', [ReviewController::class, 'myReviews']);
        Route::get('/reviews/pending', [ReviewController::class, 'pendingReviews']);
        Route::get('/reviews/{id}', [ReviewController::class, 'show']);
        Route::post('/reviews', [ReviewController::class, 'store']);
        Route::put('/reviews/{id}', [ReviewController::class, 'update']);
        Route::delete('/reviews/{id}', [ReviewController::class, 'destroy']);
        Route::get('/products/{productId}/can-review', [ReviewController::class, 'canReview']);

        // Password routes (authenticated)
        Route::get('/password/has-password', [CustomerPasswordController::class, 'hasPassword']);
        Route::post('/password/set', [CustomerPasswordController::class, 'setPassword']);

        // Payment routes
        Route::post('/orders/{order}/payment', [App\Http\Controllers\OrderPaymentController::class, 'createPayment']);
        Route::post('/payment/verify', [App\Http\Controllers\OrderPaymentController::class, 'verifyPayment']);
        Route::get('/orders/{order}/payment-status', [App\Http\Controllers\OrderPaymentController::class, 'getStatus']);
        Route::post('/orders/{order}/retry-payment', [App\Http\Controllers\OrderPaymentController::class, 'retryPayment']);

        // Shipping routes
        Route::get('/shipping/serviceability', [App\Http\Controllers\ShippingController::class, 'checkServiceability']);
        Route::get('/orders/{order}/ship-tracking', [App\Http\Controllers\ShippingController::class, 'track']);
        Route::post('/password/change', [CustomerPasswordController::class, 'changePassword']);

        // Notification routes
        Route::get('/notifications', [App\Http\Controllers\NotificationController::class, 'index']);
        Route::get('/notifications/unread-count', [App\Http\Controllers\NotificationController::class, 'unreadCount']);
        Route::post('/notifications/{id}/read', [App\Http\Controllers\NotificationController::class, 'markAsRead']);
        Route::post('/notifications/read-all', [App\Http\Controllers\NotificationController::class, 'markAllAsRead']);
        Route::delete('/notifications/{id}', [App\Http\Controllers\NotificationController::class, 'destroy']);
        Route::delete('/notifications', [App\Http\Controllers\NotificationController::class, 'clearAll']);

        // Invoice routes
        Route::get('/orders/{order}/invoice', [App\Http\Controllers\InvoiceController::class, 'getInvoice']);
        Route::get('/orders/{order}/invoice/html', [App\Http\Controllers\InvoiceController::class, 'getInvoiceHtml']);
        Route::get('/orders/{order}/invoice/download', [App\Http\Controllers\InvoiceController::class, 'downloadInvoice']);

        // Advanced Offer routes (using /promotions prefix to avoid conflict with existing /offers routes)
        Route::prefix('promotions')->group(function () {
            Route::get('/applicable', [App\Http\Controllers\Customer\CustomerOfferController::class, 'getApplicableOffers']);
            Route::post('/validate-coupon', [App\Http\Controllers\Customer\CustomerOfferController::class, 'validateCoupon']);
            Route::get('/flash-sales', [App\Http\Controllers\Customer\CustomerOfferController::class, 'getFlashSales']);
            Route::get('/active', [App\Http\Controllers\Customer\CustomerOfferController::class, 'getActivePromotions']);
            Route::get('/products-with-offers', [App\Http\Controllers\Customer\CustomerOfferController::class, 'getProductsWithOffers']);
            Route::get('/{offerId}', [App\Http\Controllers\Customer\CustomerOfferController::class, 'getOfferDetails']);
        });

        // Loyalty routes
        Route::prefix('loyalty')->group(function () {
            Route::get('/dashboard', [App\Http\Controllers\Customer\CustomerLoyaltyController::class, 'getDashboard']);
            Route::get('/balance', [App\Http\Controllers\Customer\CustomerLoyaltyController::class, 'getBalance']);
            Route::get('/history', [App\Http\Controllers\Customer\CustomerLoyaltyController::class, 'getHistory']);
            Route::post('/calculate', [App\Http\Controllers\Customer\CustomerLoyaltyController::class, 'calculatePoints']);
            Route::post('/preview-redemption', [App\Http\Controllers\Customer\CustomerLoyaltyController::class, 'previewRedemption']);
            Route::get('/tiers', [App\Http\Controllers\Customer\CustomerLoyaltyController::class, 'getTierBenefits']);
            Route::get('/expiring', [App\Http\Controllers\Customer\CustomerLoyaltyController::class, 'getExpiringPoints']);
            Route::get('/leaderboard', [App\Http\Controllers\Customer\CustomerLoyaltyController::class, 'getLeaderboard']);
        });

        // Referral routes
        Route::prefix('referral')->group(function () {
            Route::get('/dashboard', [App\Http\Controllers\Customer\CustomerReferralController::class, 'getDashboard']);
            Route::get('/code', [App\Http\Controllers\Customer\CustomerReferralController::class, 'getReferralCode']);
            Route::post('/validate', [App\Http\Controllers\Customer\CustomerReferralController::class, 'validateCode']);
            Route::post('/apply', [App\Http\Controllers\Customer\CustomerReferralController::class, 'applyCode']);
            Route::get('/discount', [App\Http\Controllers\Customer\CustomerReferralController::class, 'getRefereeDiscount']);
            Route::post('/calculate-discount', [App\Http\Controllers\Customer\CustomerReferralController::class, 'calculateDiscount']);
            Route::get('/share', [App\Http\Controllers\Customer\CustomerReferralController::class, 'getShareContent']);
        });

        // Return Request routes
        Route::prefix('returns')->group(function () {
            Route::get('/policy', [App\Http\Controllers\Customer\ReturnController::class, 'getPolicy']);
            Route::get('/', [App\Http\Controllers\Customer\ReturnController::class, 'index']);
            Route::post('/', [App\Http\Controllers\Customer\ReturnController::class, 'store']);
            Route::get('/eligibility/{orderId}', [App\Http\Controllers\Customer\ReturnController::class, 'checkEligibility']);
            Route::get('/{id}', [App\Http\Controllers\Customer\ReturnController::class, 'show']);
            Route::post('/{id}/cancel', [App\Http\Controllers\Customer\ReturnController::class, 'cancel']);
            Route::get('/{id}/tracking', [App\Http\Controllers\Customer\ReturnController::class, 'tracking']);
        });

        // Exchange Request routes
        Route::prefix('exchanges')->group(function () {
            Route::get('/policy', [App\Http\Controllers\Customer\ExchangeController::class, 'getPolicy']);
            Route::get('/', [App\Http\Controllers\Customer\ExchangeController::class, 'index']);
            Route::post('/', [App\Http\Controllers\Customer\ExchangeController::class, 'store']);
            Route::get('/eligibility/{orderId}', [App\Http\Controllers\Customer\ExchangeController::class, 'checkEligibility']);
            Route::get('/options/{productId}', [App\Http\Controllers\Customer\ExchangeController::class, 'getExchangeOptions']);
            Route::get('/{id}', [App\Http\Controllers\Customer\ExchangeController::class, 'show']);
            Route::post('/{id}/cancel', [App\Http\Controllers\Customer\ExchangeController::class, 'cancel']);
            Route::get('/{id}/tracking', [App\Http\Controllers\Customer\ExchangeController::class, 'tracking']);
        });

        // Cancellation Request routes
        Route::prefix('cancellations')->group(function () {
            Route::get('/policy', [App\Http\Controllers\Customer\CancellationController::class, 'getPolicy']);
            Route::get('/', [App\Http\Controllers\Customer\CancellationController::class, 'index']);
            Route::post('/', [App\Http\Controllers\Customer\CancellationController::class, 'store']);
            Route::get('/eligibility/{orderId}', [App\Http\Controllers\Customer\CancellationController::class, 'checkEligibility']);
            Route::get('/{id}', [App\Http\Controllers\Customer\CancellationController::class, 'show']);
            Route::get('/{id}/tracking', [App\Http\Controllers\Customer\CancellationController::class, 'tracking']);
        });
    });
});

Route::prefix('partner')->group(function () {
    // Public routes
    Route::post('/send-otp', [AuthController::class, 'sendOTP']);
    Route::post('/verify-otp', [AuthController::class, 'verifyOTP']);
    Route::post('/register', [PartnerController::class, 'store']);
    Route::get('/states/{country_id}', [CommonController::class, 'getStates']);
    Route::get('/cities/{state_id}', [CommonController::class, 'getCities']);

    // Public password routes
    Route::post('/forgot-password', [PartnerPasswordController::class, 'forgotPassword']);
    Route::post('/verify-reset-otp', [PartnerPasswordController::class, 'verifyResetOtp']);
    Route::post('/reset-password', [PartnerPasswordController::class, 'resetPassword']);
    Route::post('/login-password', [PartnerPasswordController::class, 'loginWithPassword']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'partnerlogout']);

        // Profile routes
        Route::get('/profile', [PartnerProfileController::class, 'getProfile']);
        Route::put('/profile', [PartnerProfileController::class, 'updateProfile']);
        Route::post('/profile/avatar', [PartnerProfileController::class, 'uploadAvatar']);
        Route::delete('/profile/avatar', [PartnerProfileController::class, 'removeAvatar']);
        Route::get('/profile/approval-status', [PartnerProfileController::class, 'getApprovalStatus']);

        // Password routes
        Route::get('/password/has-password', [PartnerPasswordController::class, 'hasPassword']);
        Route::post('/password/set', [PartnerPasswordController::class, 'setPassword']);
        Route::post('/password/change', [PartnerPasswordController::class, 'changePassword']);

        // Product routes
        Route::get('/products', [ProductController::class, 'partnerproductListing']);
        Route::get('/product/{id}', [ProductController::class, 'viewProduct']);
        Route::post('/products/relatedProducts', [ProductController::class, 'relatedProducts']);
        Route::get('/category/{name}', [CategoryController::class, 'getcategoryproducts']);

        // Order routes
        Route::get('/orders', [PartnerOrderController::class, 'index']);
        Route::get('/orders/statistics', [PartnerOrderController::class, 'statistics']);
        Route::get('/orders/{id}', [PartnerOrderController::class, 'show']);
        Route::post('/orders/{id}/cancel', [PartnerOrderController::class, 'cancel']);
        Route::get('/orders/{id}/tracking', [PartnerOrderController::class, 'tracking']);
        Route::post('/orders/{id}/reorder', [PartnerOrderController::class, 'reorder']);

        // Dashboard routes
        Route::get('/dashboard', [PartnerDashboardController::class, 'index']);
        Route::get('/dashboard/order-analytics', [PartnerDashboardController::class, 'orderAnalytics']);
        Route::get('/dashboard/spending-analytics', [PartnerDashboardController::class, 'spendingAnalytics']);
        Route::get('/dashboard/frequent-products', [PartnerDashboardController::class, 'frequentProducts']);

        // B2B Inquiry routes (Bulk Order Requests)
        Route::prefix('inquiries')->group(function () {
            Route::get('/', [App\Http\Controllers\Partner\PartnerInquiryController::class, 'index']);
            Route::get('/statistics', [App\Http\Controllers\Partner\PartnerInquiryController::class, 'statistics']);
            Route::post('/', [App\Http\Controllers\Partner\PartnerInquiryController::class, 'store']);
            Route::get('/{id}', [App\Http\Controllers\Partner\PartnerInquiryController::class, 'show']);
            Route::put('/{id}', [App\Http\Controllers\Partner\PartnerInquiryController::class, 'update']);
            Route::post('/{id}/items', [App\Http\Controllers\Partner\PartnerInquiryController::class, 'addItem']);
            Route::delete('/{id}/items/{itemId}', [App\Http\Controllers\Partner\PartnerInquiryController::class, 'removeItem']);
            Route::post('/{id}/accept-quote', [App\Http\Controllers\Partner\PartnerInquiryController::class, 'acceptQuote']);
            Route::post('/{id}/reject-quote', [App\Http\Controllers\Partner\PartnerInquiryController::class, 'rejectQuote']);
            Route::post('/{id}/cancel', [App\Http\Controllers\Partner\PartnerInquiryController::class, 'cancel']);
            Route::post('/{id}/message', [App\Http\Controllers\Partner\PartnerInquiryController::class, 'sendMessage']);
            Route::get('/{id}/tracking', [App\Http\Controllers\Partner\PartnerInquiryController::class, 'tracking']);
        });
    });
});






/*
|--------------------------------------------------------------------------
| Webhook Routes (No Authentication)
|--------------------------------------------------------------------------
*/
Route::prefix('webhooks')->group(function () {
    Route::post('/razorpay', [App\Http\Controllers\WebhookController::class, 'razorpay']);
    Route::post('/shiprocket', [App\Http\Controllers\WebhookController::class, 'shiprocket']);
});

/*
|--------------------------------------------------------------------------
| Legacy Customer Payment & Shipping Routes (moved to sanctum group above)
|--------------------------------------------------------------------------
*/
// Note: Payment, Shipping, and Offers routes have been moved to the
// auth:sanctum middleware group for consistency with other customer routes.

/*
|--------------------------------------------------------------------------
| Employee Shipping Management Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:employee'])->prefix('employee/orders/{order}')->group(function () {
    Route::post('/ship', [App\Http\Controllers\ShippingController::class, 'pushToShiprocket']);
    Route::post('/generate-awb', [App\Http\Controllers\ShippingController::class, 'generateAWB']);
    Route::post('/schedule-pickup', [App\Http\Controllers\ShippingController::class, 'schedulePickup']);
    Route::get('/label', [App\Http\Controllers\ShippingController::class, 'getLabel']);
    Route::post('/cancel-shipment', [App\Http\Controllers\ShippingController::class, 'cancelShipment']);
    Route::post('/sync-tracking', [App\Http\Controllers\ShippingController::class, 'syncTracking']);
});

/*
|--------------------------------------------------------------------------
| Admin Order & Inventory Management Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum'])->prefix('admin')->group(function () {
    // Dashboard & Reports
    Route::get('/dashboard', [App\Http\Controllers\Admin\AdminDashboardController::class, 'overview']);
    Route::get('/reports/sales', [App\Http\Controllers\Admin\AdminDashboardController::class, 'salesReport']);
    Route::get('/reports/top-products', [App\Http\Controllers\Admin\AdminDashboardController::class, 'topProducts']);
    Route::get('/reports/top-customers', [App\Http\Controllers\Admin\AdminDashboardController::class, 'topCustomers']);
    Route::get('/reports/revenue-trends', [App\Http\Controllers\Admin\AdminDashboardController::class, 'revenueTrends']);
    Route::get('/reports/export', [App\Http\Controllers\Admin\AdminDashboardController::class, 'exportReport']);
    Route::get('/recent-orders', [App\Http\Controllers\Admin\AdminDashboardController::class, 'recentOrders']);
    Route::get('/recent-activities', [App\Http\Controllers\Admin\AdminDashboardController::class, 'recentActivities']);

    // Order Management
    Route::get('/orders', [App\Http\Controllers\Admin\AdminOrderController::class, 'index']);
    Route::get('/orders/statistics', [App\Http\Controllers\Admin\AdminOrderController::class, 'statistics']);
    Route::get('/orders/export', [App\Http\Controllers\Admin\AdminOrderController::class, 'export']);
    Route::get('/orders/sla-breaches', [App\Http\Controllers\Admin\AdminOrderController::class, 'slaBreaches']);
    Route::get('/orders/hold-reasons', [App\Http\Controllers\Admin\AdminOrderController::class, 'holdReasons']);
    Route::get('/orders/sla-config', [App\Http\Controllers\Admin\AdminOrderController::class, 'slaConfig']);
    Route::put('/orders/sla-config', [App\Http\Controllers\Admin\AdminOrderController::class, 'updateSlaConfig']);
    Route::post('/orders/bulk-status', [App\Http\Controllers\Admin\AdminOrderController::class, 'bulkStatus']);
    Route::get('/orders/{id}', [App\Http\Controllers\Admin\AdminOrderController::class, 'show']);
    Route::put('/orders/{id}/status', [App\Http\Controllers\Admin\AdminOrderController::class, 'updateStatus']);
    Route::post('/orders/{id}/refund', [App\Http\Controllers\Admin\AdminOrderController::class, 'processRefund']);
    Route::post('/orders/{id}/note', [App\Http\Controllers\Admin\AdminOrderController::class, 'addNote']);
    Route::get('/orders/{id}/timeline', [App\Http\Controllers\Admin\AdminOrderController::class, 'timeline']);
    Route::post('/orders/{id}/fulfill-partial', [App\Http\Controllers\Admin\AdminOrderController::class, 'fulfillPartial']);
    Route::post('/orders/{id}/split', [App\Http\Controllers\Admin\AdminOrderController::class, 'splitShipment']);
    Route::post('/orders/{id}/hold', [App\Http\Controllers\Admin\AdminOrderController::class, 'hold']);
    Route::post('/orders/{id}/release', [App\Http\Controllers\Admin\AdminOrderController::class, 'release']);
    Route::post('/orders/{id}/override', [App\Http\Controllers\Admin\AdminOrderController::class, 'override']);
    Route::get('/orders/{id}/shipments', [App\Http\Controllers\Admin\AdminOrderController::class, 'shipments']);
    Route::put('/orders/{id}/shipments/{shipmentId}/status', [App\Http\Controllers\Admin\AdminOrderController::class, 'updateShipmentStatus']);
    Route::get('/orders/{id}/fulfillments', [App\Http\Controllers\Admin\AdminOrderController::class, 'fulfillments']);

    // Inventory Management
    Route::get('/inventory', [App\Http\Controllers\Admin\AdminInventoryController::class, 'index']);
    Route::get('/inventory/summary', [App\Http\Controllers\Admin\AdminInventoryController::class, 'summary']);
    Route::get('/inventory/low-stock', [App\Http\Controllers\Admin\AdminInventoryController::class, 'lowStock']);
    Route::get('/inventory/out-of-stock', [App\Http\Controllers\Admin\AdminInventoryController::class, 'outOfStock']);
    Route::post('/inventory/{productId}/adjust', [App\Http\Controllers\Admin\AdminInventoryController::class, 'adjustStock']);
    Route::put('/inventory/{productId}/stock', [App\Http\Controllers\Admin\AdminInventoryController::class, 'setStock']);
    Route::post('/inventory/bulk-update', [App\Http\Controllers\Admin\AdminInventoryController::class, 'bulkUpdate']);
    Route::get('/inventory/{productId}/history', [App\Http\Controllers\Admin\AdminInventoryController::class, 'stockHistory']);

    // Offer Management
    Route::get('/offers', [App\Http\Controllers\Admin\AdminOfferController::class, 'index']);
    Route::get('/offers/summary', [App\Http\Controllers\Admin\AdminOfferController::class, 'summary']);
    Route::get('/offers/types', [App\Http\Controllers\Admin\AdminOfferController::class, 'getOfferTypes']);
    Route::post('/offers', [App\Http\Controllers\Admin\AdminOfferController::class, 'store']);
    Route::get('/offers/{id}', [App\Http\Controllers\Admin\AdminOfferController::class, 'show']);
    Route::put('/offers/{id}', [App\Http\Controllers\Admin\AdminOfferController::class, 'update']);
    Route::delete('/offers/{id}', [App\Http\Controllers\Admin\AdminOfferController::class, 'destroy']);
    Route::post('/offers/{id}/activate', [App\Http\Controllers\Admin\AdminOfferController::class, 'activate']);
    Route::post('/offers/{id}/deactivate', [App\Http\Controllers\Admin\AdminOfferController::class, 'deactivate']);
    Route::get('/offers/{id}/usage', [App\Http\Controllers\Admin\AdminOfferController::class, 'usage']);
    Route::post('/offers/bulk-status', [App\Http\Controllers\Admin\AdminOfferController::class, 'bulkUpdateStatus']);

    // Shipping Management
    Route::get('/shipping/dashboard', [App\Http\Controllers\Admin\AdminShippingController::class, 'dashboard']);
    Route::get('/shipping/pending', [App\Http\Controllers\Admin\AdminShippingController::class, 'pendingShipments']);
    Route::get('/shipping/shipped', [App\Http\Controllers\Admin\AdminShippingController::class, 'shippedOrders']);
    Route::get('/shipping/orders/{order}', [App\Http\Controllers\Admin\AdminShippingController::class, 'orderDetails']);
    Route::post('/shipping/serviceability', [App\Http\Controllers\Admin\AdminShippingController::class, 'checkServiceability']);
    Route::post('/shipping/bulk-push', [App\Http\Controllers\Admin\AdminShippingController::class, 'bulkPushToShiprocket']);
    Route::post('/shipping/bulk-awb', [App\Http\Controllers\Admin\AdminShippingController::class, 'bulkGenerateAWB']);
    Route::post('/shipping/bulk-pickup', [App\Http\Controllers\Admin\AdminShippingController::class, 'bulkSchedulePickup']);
    Route::post('/shipping/bulk-sync', [App\Http\Controllers\Admin\AdminShippingController::class, 'bulkSyncTracking']);
    Route::post('/shipping/bulk-labels', [App\Http\Controllers\Admin\AdminShippingController::class, 'bulkGetLabels']);

    // Invoice Management
    Route::get('/orders/{order}/invoice', [App\Http\Controllers\InvoiceController::class, 'adminGetInvoice']);
    Route::get('/orders/{order}/invoice/download', [App\Http\Controllers\InvoiceController::class, 'adminDownloadInvoice']);
    Route::post('/invoices/bulk', [App\Http\Controllers\InvoiceController::class, 'bulkGetInvoices']);

    // Advanced Offer Management
    Route::prefix('advanced-offers')->group(function () {
        Route::get('/types', [App\Http\Controllers\Admin\AdminAdvancedOfferController::class, 'getOfferTypes']);
        Route::post('/', [App\Http\Controllers\Admin\AdminAdvancedOfferController::class, 'create']);
        Route::put('/{offer}', [App\Http\Controllers\Admin\AdminAdvancedOfferController::class, 'update']);
        Route::get('/{offer}/analytics', [App\Http\Controllers\Admin\AdminAdvancedOfferController::class, 'getAnalytics']);
        Route::post('/{offer}/duplicate', [App\Http\Controllers\Admin\AdminAdvancedOfferController::class, 'duplicate']);
        Route::post('/bulk-status', [App\Http\Controllers\Admin\AdminAdvancedOfferController::class, 'bulkUpdateStatus']);

        // Specialized offer creation
        Route::post('/flash-sale', [App\Http\Controllers\Admin\AdminAdvancedOfferController::class, 'createFlashSale']);
        Route::post('/combo', [App\Http\Controllers\Admin\AdminAdvancedOfferController::class, 'createCombo']);
        Route::post('/bogo', [App\Http\Controllers\Admin\AdminAdvancedOfferController::class, 'createBogo']);
        Route::post('/tiered', [App\Http\Controllers\Admin\AdminAdvancedOfferController::class, 'createTieredDiscount']);
    });

    // Loyalty Management
    Route::prefix('loyalty')->group(function () {
        Route::get('/statistics', [App\Http\Controllers\Admin\AdminLoyaltyController::class, 'statistics']);
        Route::get('/users', [App\Http\Controllers\Admin\AdminLoyaltyController::class, 'users']);
        Route::get('/users/{userId}', [App\Http\Controllers\Admin\AdminLoyaltyController::class, 'userDetails']);
        Route::post('/users/{userId}/adjust', [App\Http\Controllers\Admin\AdminLoyaltyController::class, 'adjustPoints']);
        Route::get('/tiers', [App\Http\Controllers\Admin\AdminLoyaltyController::class, 'tiers']);
        Route::post('/tiers', [App\Http\Controllers\Admin\AdminLoyaltyController::class, 'createTier']);
        Route::put('/tiers/{tier}', [App\Http\Controllers\Admin\AdminLoyaltyController::class, 'updateTier']);
        Route::post('/process-expiry', [App\Http\Controllers\Admin\AdminLoyaltyController::class, 'processExpiredPoints']);
    });

    // Referral Management
    Route::prefix('referrals')->group(function () {
        Route::get('/statistics', [App\Http\Controllers\Admin\AdminReferralController::class, 'statistics']);
        Route::get('/', [App\Http\Controllers\Admin\AdminReferralController::class, 'index']);
        Route::get('/{referral}', [App\Http\Controllers\Admin\AdminReferralController::class, 'show']);
        Route::post('/{referral}/cancel', [App\Http\Controllers\Admin\AdminReferralController::class, 'cancel']);
        Route::post('/expire-pending', [App\Http\Controllers\Admin\AdminReferralController::class, 'expirePending']);
    });

    // Partner Inquiry Management (B2B)
    Route::prefix('partner-inquiries')->group(function () {
        Route::get('/dashboard', [App\Http\Controllers\Admin\AdminPartnerInquiryController::class, 'dashboard']);
        Route::get('/', [App\Http\Controllers\Admin\AdminPartnerInquiryController::class, 'index']);
        Route::get('/{id}', [App\Http\Controllers\Admin\AdminPartnerInquiryController::class, 'show']);
        Route::post('/{id}/start-review', [App\Http\Controllers\Admin\AdminPartnerInquiryController::class, 'startReview']);
        Route::post('/{id}/quote', [App\Http\Controllers\Admin\AdminPartnerInquiryController::class, 'provideQuote']);
        Route::post('/{id}/reject', [App\Http\Controllers\Admin\AdminPartnerInquiryController::class, 'reject']);
        Route::put('/{id}/status', [App\Http\Controllers\Admin\AdminPartnerInquiryController::class, 'updateStatus']);
        Route::post('/{id}/payment', [App\Http\Controllers\Admin\AdminPartnerInquiryController::class, 'recordPayment']);
        Route::put('/{id}/items/{itemId}/fulfillment', [App\Http\Controllers\Admin\AdminPartnerInquiryController::class, 'updateItemFulfillment']);
        Route::post('/{id}/message', [App\Http\Controllers\Admin\AdminPartnerInquiryController::class, 'sendMessage']);
        Route::post('/{id}/tracking', [App\Http\Controllers\Admin\AdminPartnerInquiryController::class, 'addTrackingUpdate']);
        Route::put('/{id}/notes', [App\Http\Controllers\Admin\AdminPartnerInquiryController::class, 'updateNotes']);
        Route::post('/bulk-status', [App\Http\Controllers\Admin\AdminPartnerInquiryController::class, 'bulkUpdateStatus']);
    });

    // Return Policy Settings
    Route::prefix('return-policy')->group(function () {
        Route::get('/', [App\Http\Controllers\Admin\AdminReturnPolicyController::class, 'index']);
        Route::put('/', [App\Http\Controllers\Admin\AdminReturnPolicyController::class, 'update']);
        Route::get('/default-reasons/{type}', [App\Http\Controllers\Admin\AdminReturnPolicyController::class, 'getDefaultReasons']);
        Route::post('/reset', [App\Http\Controllers\Admin\AdminReturnPolicyController::class, 'reset']);
    });

    // Return Request Management
    Route::prefix('returns')->group(function () {
        Route::get('/dashboard', [App\Http\Controllers\Admin\AdminReturnController::class, 'dashboard']);
        Route::get('/', [App\Http\Controllers\Admin\AdminReturnController::class, 'index']);
        Route::get('/export', [App\Http\Controllers\Admin\AdminReturnController::class, 'export']);
        Route::get('/{id}', [App\Http\Controllers\Admin\AdminReturnController::class, 'show']);
        Route::post('/{id}/start-review', [App\Http\Controllers\Admin\AdminReturnController::class, 'startReview']);
        Route::post('/{id}/approve', [App\Http\Controllers\Admin\AdminReturnController::class, 'approve']);
        Route::post('/{id}/reject', [App\Http\Controllers\Admin\AdminReturnController::class, 'reject']);
        Route::post('/{id}/schedule-pickup', [App\Http\Controllers\Admin\AdminReturnController::class, 'schedulePickup']);
        Route::post('/{id}/mark-picked-up', [App\Http\Controllers\Admin\AdminReturnController::class, 'markPickedUp']);
        Route::post('/{id}/mark-received', [App\Http\Controllers\Admin\AdminReturnController::class, 'markReceived']);
        Route::post('/{id}/inspection', [App\Http\Controllers\Admin\AdminReturnController::class, 'recordInspection']);
        Route::post('/{id}/initiate-refund', [App\Http\Controllers\Admin\AdminReturnController::class, 'initiateRefund']);
        Route::post('/{id}/complete-refund', [App\Http\Controllers\Admin\AdminReturnController::class, 'completeRefund']);
        Route::put('/{id}/notes', [App\Http\Controllers\Admin\AdminReturnController::class, 'updateNotes']);
        Route::post('/bulk-status', [App\Http\Controllers\Admin\AdminReturnController::class, 'bulkUpdateStatus']);
    });

    // Exchange Request Management
    Route::prefix('exchanges')->group(function () {
        Route::get('/dashboard', [App\Http\Controllers\Admin\AdminExchangeController::class, 'dashboard']);
        Route::get('/', [App\Http\Controllers\Admin\AdminExchangeController::class, 'index']);
        Route::get('/export', [App\Http\Controllers\Admin\AdminExchangeController::class, 'export']);
        Route::get('/{id}', [App\Http\Controllers\Admin\AdminExchangeController::class, 'show']);
        Route::post('/{id}/start-review', [App\Http\Controllers\Admin\AdminExchangeController::class, 'startReview']);
        Route::post('/{id}/approve', [App\Http\Controllers\Admin\AdminExchangeController::class, 'approve']);
        Route::post('/{id}/reject', [App\Http\Controllers\Admin\AdminExchangeController::class, 'reject']);
        Route::put('/{id}/status', [App\Http\Controllers\Admin\AdminExchangeController::class, 'updateStatus']);
        Route::post('/{id}/mark-return-received', [App\Http\Controllers\Admin\AdminExchangeController::class, 'markReturnReceived']);
        Route::post('/{id}/process', [App\Http\Controllers\Admin\AdminExchangeController::class, 'processExchange']);
        Route::post('/{id}/ship', [App\Http\Controllers\Admin\AdminExchangeController::class, 'ship']);
        Route::post('/{id}/mark-delivered', [App\Http\Controllers\Admin\AdminExchangeController::class, 'markDelivered']);
        Route::post('/{id}/payment-adjustment', [App\Http\Controllers\Admin\AdminExchangeController::class, 'recordPaymentAdjustment']);
        Route::put('/{id}/notes', [App\Http\Controllers\Admin\AdminExchangeController::class, 'updateNotes']);
    });

    // Cancellation Request Management
    Route::prefix('cancellations')->group(function () {
        Route::get('/dashboard', [App\Http\Controllers\Admin\AdminCancellationController::class, 'dashboard']);
        Route::get('/', [App\Http\Controllers\Admin\AdminCancellationController::class, 'index']);
        Route::get('/export', [App\Http\Controllers\Admin\AdminCancellationController::class, 'export']);
        Route::get('/{id}', [App\Http\Controllers\Admin\AdminCancellationController::class, 'show']);
        Route::post('/{id}/start-review', [App\Http\Controllers\Admin\AdminCancellationController::class, 'startReview']);
        Route::post('/{id}/approve', [App\Http\Controllers\Admin\AdminCancellationController::class, 'approve']);
        Route::post('/{id}/reject', [App\Http\Controllers\Admin\AdminCancellationController::class, 'reject']);
        Route::post('/{id}/initiate-refund', [App\Http\Controllers\Admin\AdminCancellationController::class, 'initiateRefund']);
        Route::post('/{id}/complete-refund', [App\Http\Controllers\Admin\AdminCancellationController::class, 'completeRefund']);
        Route::put('/{id}/notes', [App\Http\Controllers\Admin\AdminCancellationController::class, 'updateNotes']);
        Route::post('/bulk-status', [App\Http\Controllers\Admin\AdminCancellationController::class, 'bulkUpdateStatus']);
    });
});
