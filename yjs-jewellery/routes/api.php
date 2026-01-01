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

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'customerlogout']);
        Route::get('/cart', [CartController::class, 'index']);
        Route::post('/cart', [CartController::class, 'store']);
        Route::post('/cart/sync', [CartController::class, 'syncFromLocalStorage']);
        Route::get('/cartCount', [CartController::class, 'cartCount']);
    });
});

Route::prefix('partner')->group(function () {
    Route::post('/send-otp', [AuthController::class, 'sendOTP']);
    Route::post('/verify-otp', [AuthController::class, 'verifyOTP']);
    Route::post('partner-register', [PartnerController::class, 'store']);
    Route::get('/states/{country_id}', [CommonController::class, 'getStates']);
    Route::get('/cities/{state_id}', [CommonController::class, 'getCities']);

    
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'partnerlogout']);
        Route::get('/products', [ProductController::class, 'partnerproductListing']);
        Route::get('/product/{id}', [ProductController::class, 'viewProduct']);
        Route::post('/products/relatedProducts', [ProductController::class, 'relatedProducts']);
        Route::get('/category/{name}', [CategoryController::class, 'getcategoryproducts']);
        
    });
});





