import { createRouter, createWebHistory } from 'vue-router'

import Home from '../views/Home.vue'
import CollectionsIndex from '../views/collections/Collections.vue'
import DetailsIndex from '../views/collections/Details.vue'
import AboutIndex from '../views/static/About.vue'
import ContactIndex from '../views/static/Contact.vue'

import eventsIndex from '../views/static/Events.vue'
// Frontend
import ProductListing from '../views/frontend/ProductListing.vue';
import ProductsDetails from '../views/frontend/ProductDetails.vue'
// Partners
import ProductPartnerListing from '../views/partner/ProductListing.vue';
import ProductsSelectedIndex from '../views/partner/ProductSelected.vue';
import RegisterPartner from '../views/partner/RegisterPartner.vue';
// Admin pages
import AdminLogin from '../views/admin/authontication/Login.vue'
import AdminForgotPassword from '../views/admin/authontication/ForgotPassword.vue'
import AdminResetPassword from '../views/admin/authontication/ResetPassword.vue'
import AdminDashboard from '../views/admin/Dashboard.vue'
import AdminProfile from '../views/admin/authontication/adminProfile.vue'
import ViewEmployee from '../views/admin/settings/org-structure/ViewEmployee.vue'
import Orders from "../views/admin/orders/Orders.vue";
import Offers from "../views/admin/offers/Offers.vue";
import Customers from "../views/admin/customers/Customers.vue";
import CustomerView from "../views/admin/customers/CustomerView.vue";
import Partners from "../views/admin/partners/Partners.vue";
import PartnerView from "../views/admin/partners/PartnerView.vue";
import Inquiries from "../views/admin/inquiries/Inquiries.vue";
import AccessControl from "../views/admin/access/AccessControl.vue";
import Cms from "../views/admin/cms/Cms.vue";
import Blog from "../views/admin/blog/Blog.vue";

// Page Builder
import PagesList from "../views/admin/page-builder/PagesList.vue";
import PageBuilder from "../views/admin/page-builder/PageBuilder.vue";
import GlobalSectionBuilder from "../views/admin/page-builder/GlobalSectionBuilder.vue";
import FormSubmissions from "../views/admin/page-builder/FormSubmissions.vue";
import UrlRedirects from "../views/admin/page-builder/UrlRedirects.vue";
import Analytics from "../views/admin/page-builder/Analytics.vue";
import Events from "../views/admin/events/Events.vue";
import Exhibition from "../views/admin/exhibition/Exhibition.vue";
import Brochures from "../views/admin/brochures/Brochures.vue";
import Feedback from "../views/admin/feedback/Feedback.vue";

import Settings from "../views/admin/settings/Settings.vue";

// Org Structure
import Branch from "../views/admin/settings/org-structure/Branch.vue";
import Department from "../views/admin/settings/org-structure/Department.vue";
import Role from "../views/admin/settings/org-structure/Role.vue";

// Masters
import Masters from "../views/admin/settings/masters/Masters.vue";
import Category from "../views/admin/settings/masters/Category.vue";
import SubCategory from "../views/admin/settings/masters/SubCategory.vue";
import Tags from "../views/admin/settings/masters/Tags.vue";
import MaterialType from "../views/admin/settings/masters/MaterialType.vue";
import Attributes from "../views/admin/settings/masters/Attributes.vue";
import GemstoneRate from "../views/admin/settings/masters/GemstoneRate.vue";
import AdditionalCharges from "../views/admin/settings/masters/AdditionalCharges.vue";
import Tax from "../views/admin/settings/masters/Tax.vue";
import ProductType from "../views/admin/settings/masters/ProductType.vue";
import ViewMetalType from "../views/admin/settings/masters/ViewMetalType.vue"

// SEO
import Seo from "../views/admin/settings/seo/Seo.vue";

// Logs
import ActivityLogs from "../views/admin/settings/logs/ActivityLogs.vue";
import ViewActivity from "../views/admin/settings/logs/ViewActivityLogs.vue";
import OtpLogs from "../views/admin/settings/logs/OtpLogs.vue";
import EmailLogs from "../views/admin/settings/logs/EmailLogs.vue";

// Components
import form from "../views/components/form.vue";
import formWizard from "../views/components/formWizard.vue";
import generalSetting from "../views/components/generalSetting.vue";
import modalFrame from "../views/components/modalFrame.vue";
import noData from "../views/components/noData.vue";
import permissions from "../views/components/permissions.vue";
import table from "../views/components/table.vue";
import tabs from "../views/components/tabs.vue";
import verticalTabs from "../views/components/verticalTabs.vue";
import viewDetailsMaster from "../views/components/viewDetailsMaster.vue";
import breadcrumbs from "../views/components/breadcrumbs.vue";

//products
import ProductList from "../views/admin/products/ProductList.vue";
import AddProduct from "../views/admin/products/AddProduct.vue";
import EditProduct from "../views/admin/products/EditProduct.vue";
import ViewProduct from "../views/admin/products/ViewProduct.vue";

//cart
import Cart from '../views/frontend/Cart.vue'
import DeliveryAddress from '../views/frontend/DeliveryAddress.vue'
import OrderSuccess from '../views/frontend/OrderSuccess.vue'

// Customer Views
import FlashSales from '../views/frontend/FlashSales.vue'
import DynamicPage from '../views/frontend/DynamicPage.vue'
import Promotions from '../views/frontend/Promotions.vue'
import LoyaltyDashboard from '../views/frontend/LoyaltyDashboard.vue'
import ReferralDashboard from '../views/frontend/ReferralDashboard.vue'
import CustomerReturns from '../views/frontend/CustomerReturns.vue'
import CustomerDashboard from '../views/frontend/CustomerDashboard.vue'
import CustomerOrders from '../views/frontend/CustomerOrders.vue'
import CustomerOrderDetails from '../views/frontend/CustomerOrderDetails.vue'
import CustomerProfile from '../views/frontend/CustomerProfile.vue'
import CustomerAddresses from '../views/frontend/CustomerAddresses.vue'
import CustomerWishlist from '../views/frontend/CustomerWishlist.vue'
import CustomerNotifications from '../views/frontend/CustomerNotifications.vue'

// Customer Auth
import CustomerLogin from '../views/frontend/auth/CustomerLogin.vue'
import CustomerRegister from '../views/frontend/auth/CustomerRegister.vue'
import ForgotPassword from '../views/frontend/auth/ForgotPassword.vue'

// Partner Views
import PartnerInquiries from '../views/partner/PartnerInquiries.vue'
import PartnerDashboard from '../views/partner/PartnerDashboard.vue'
import PartnerLogin from '../views/partner/PartnerLogin.vue'
import PartnerProfile from '../views/partner/PartnerProfile.vue'

// New Admin Views
import WarehouseManagement from '../views/admin/warehouse/WarehouseManagement.vue'
import TaxRules from '../views/admin/tax/TaxRules.vue'
import ReportsDashboard from '../views/admin/reports/ReportsDashboard.vue'
import OrderManagement from '../views/admin/orders/OrderManagement.vue'
import ReturnsExchanges from '../views/admin/returns/ReturnsExchanges.vue'
import AdminReturns from '../views/admin/returns/AdminReturns.vue'
import AdminPartnerInquiries from '../views/admin/partner/AdminPartnerInquiries.vue'
import FinanceDashboard from '../views/admin/finance/FinanceDashboard.vue'
import SupportTickets from '../views/admin/support/SupportTickets.vue'
import UserManagement from '../views/admin/users/UserManagement.vue'
import AdvancedOffers from '../views/admin/offers/AdvancedOffers.vue'
import LoyaltyManagement from '../views/admin/loyalty/LoyaltyManagement.vue'
import BulkOperations from '../views/admin/products/BulkOperations.vue'
const routes = [
  // -------------------------
  // FRONTEND ROUTES
  // -------------------------
  { path: '/', name: 'home', component: Home, meta: { requiresAuth: false, layout: 'full', title: "Home" } },
  { path: '/collections', name: 'collections', component: CollectionsIndex, meta: { requiresAuth: false, layout: 'full', title: "Collections" } },
  { path: '/details', name: 'details', component: DetailsIndex, meta: { requiresAuth: false, layout: 'full', title: "Details" } },
  { path: '/about', name: 'about', component: AboutIndex, meta: { requiresAuth: false, layout: 'full', title: "About" } },
  { path: '/contact', name: 'contact', component: ContactIndex, meta: { requiresAuth: false, layout: 'full', title: "Contact" } },

  { path: '/product/:id', name: 'productsDetails', component: ProductsDetails, meta: { requiresAuth: false, layout: 'full', title: "Product Details" } },
  { path: '/events', name: 'events', component: eventsIndex, meta: { requiresAuth: false, layout: 'full', title: "Events" } },

  { path: '/products', name: 'products', component: ProductListing, meta: { requiresAuth: false, layout: 'full', title: "Product Listing" } },

  { path: '/cart', name: 'cart', component: Cart, meta: { requiresAuth: true, authType: 'customer', layout: 'full', title: "My Cart" } },
  { path: '/delivery-address', name: 'deliveryAddress', component: DeliveryAddress, meta: { requiresAuth: false, layout: 'full', title: "Delivery Address" } },
  { path: '/order-success', name: 'OrderSuccess', component: OrderSuccess, meta: { requiresAuth: false, layout: 'full', title: "Order Success" } },

  // Customer Features
  { path: '/flash-sales', name: 'flashSales', component: FlashSales, meta: { requiresAuth: false, layout: 'full', title: "Flash Sales" } },
  { path: '/promotions', name: 'promotions', component: Promotions, meta: { requiresAuth: false, layout: 'full', title: "Promotions & Offers" } },
  { path: '/loyalty', name: 'loyalty', component: LoyaltyDashboard, meta: { requiresAuth: true, authType: 'customer', layout: 'full', title: "Loyalty Rewards" } },
  { path: '/referrals', name: 'referrals', component: ReferralDashboard, meta: { requiresAuth: true, authType: 'customer', layout: 'full', title: "Referral Program" } },
  { path: '/my-returns', name: 'customerReturns', component: CustomerReturns, meta: { requiresAuth: true, authType: 'customer', layout: 'full', title: "My Returns" } },

  // Customer Auth
  { path: '/login', name: 'customerLogin', component: CustomerLogin, meta: { requiresAuth: false, layout: 'full', title: "Login" } },
  { path: '/register', name: 'customerRegister', component: CustomerRegister, meta: { requiresAuth: false, layout: 'full', title: "Register" } },
  { path: '/forgot-password', name: 'forgotPassword', component: ForgotPassword, meta: { requiresAuth: false, layout: 'full', title: "Forgot Password" } },

  // Customer Account
  { path: '/dashboard', name: 'customerDashboard', component: CustomerDashboard, meta: { requiresAuth: true, authType: 'customer', layout: 'full', title: "My Dashboard" } },
  { path: '/orders', name: 'customerOrders', component: CustomerOrders, meta: { requiresAuth: true, authType: 'customer', layout: 'full', title: "My Orders" } },
  { path: '/orders/:id', name: 'customerOrderDetails', component: CustomerOrderDetails, meta: { requiresAuth: true, authType: 'customer', layout: 'full', title: "Order Details" } },
  { path: '/profile', name: 'customerProfile', component: CustomerProfile, meta: { requiresAuth: true, authType: 'customer', layout: 'full', title: "My Profile" } },
  { path: '/addresses', name: 'customerAddresses', component: CustomerAddresses, meta: { requiresAuth: true, authType: 'customer', layout: 'full', title: "My Addresses" } },
  { path: '/wishlist', name: 'customerWishlist', component: CustomerWishlist, meta: { requiresAuth: true, authType: 'customer', layout: 'full', title: "My Wishlist" } },
  { path: '/notifications', name: 'customerNotifications', component: CustomerNotifications, meta: { requiresAuth: true, authType: 'customer', layout: 'full', title: "Notifications" } },

  // -------------------------
  // Partner
  // -------------------------
  { path: '/partner/login', name: 'partnerLogin', component: PartnerLogin, meta: { requiresAuth: false, layout: 'full', title: "Partner Login" } },
  { path: '/partner/register', name: 'RegisterPartner', component: RegisterPartner, meta: { requiresAuth: false, layout: 'full', title: "Register Partner" } },
  { path: '/partner/dashboard', name: 'partnerDashboard', component: PartnerDashboard, meta: { requiresAuth: true, authType: 'partner', layout: 'full', title: "Partner Dashboard" } },
  { path: '/partner/profile', name: 'partnerProfile', component: PartnerProfile, meta: { requiresAuth: true, authType: 'partner', layout: 'full', title: "Business Profile" } },
  { path: '/partner/inquiries', name: 'partnerInquiries', component: PartnerInquiries, meta: { requiresAuth: true, authType: 'partner', layout: 'full', title: "My Inquiries" } },
  { path: '/partner/products', name: 'partnerProducts', component: ProductPartnerListing, meta: { requiresAuth: false, layout: 'full', title: "Partner Product Listing" } },
  { path: '/partner/selected', name: 'productsSelected', component: ProductsSelectedIndex, meta: { requiresAuth: false, layout: 'full', title: "Selected Products" } },
  { path: '/partner/product/:id', name: 'partnerproductsDetails', component: ProductsDetails, meta: { requiresAuth: false, layout: 'full', title: "Product Details" } },

  // -------------------------
  // ADMIN AUTH ROUTES
  // -------------------------
  { path: '/admin/login', name: 'adminLogin', component: AdminLogin, meta: { requiresAuth: false, layout: 'adminFull', title: "Login" } },
  { path: '/admin/forgot-password', name: 'adminForgotPassword', component: AdminForgotPassword, meta: { requiresAuth: false, layout: 'adminFull', title: "Forgot Password" } },
  { path: '/admin/reset-password', name: 'adminResetPassword', component: AdminResetPassword, meta: { requiresAuth: false, layout: 'adminFull', title: "Reset Password" } },

  // -------------------------
  // MAIN ADMIN MENU
  // -------------------------
  { path: '/admin/dashboard', name: 'admin.dashboard', component: AdminDashboard, meta: { requiresAuth: true, title: "Dashboard", parent: "Dashboard" } },
  { path: '/admin/profile', name: 'admin.profile', component: AdminProfile, meta: { requiresAuth: true, title: "Profile" } },
  { path: '/admin/orders', name: 'admin.orders', component: Orders, meta: { requiresAuth: true, title: "Orders" } },
  { path: '/admin/offers', name: 'admin.offers', component: Offers, meta: { requiresAuth: true, title: "Offers" } },
  { path: '/admin/customers', name: 'admin.customers', component: Customers, meta: { requiresAuth: true, title: "Customers" } },
  { path: '/admin/customers/view/:id', name: 'admin.view-customers', component: CustomerView, meta: { requiresAuth: true, title: "Customers" } },
  { path: '/admin/partners', name: 'admin.partners', component: Partners, meta: { requiresAuth: true, title: "Partners" } },
  { path: '/admin/partners/view/:id', name: 'admin.view-partners', component: PartnerView, meta: { requiresAuth: true, title: "Partners" } },
  { path: '/admin/inquiries', name: 'admin.inquiries', component: Inquiries, meta: { requiresAuth: true, title: "Inquiries" } },
  { path: '/admin/access-control', name: 'admin.access-control', component: AccessControl, meta: { requiresAuth: true, title: "Access Control" } },
  { path: '/admin/cms', name: 'admin.cms', component: Cms, meta: { requiresAuth: true, title: "CMS" } },

  // Page Builder
  { path: '/admin/page-builder', name: 'admin.page-builder', component: PagesList, meta: { requiresAuth: true, title: "Page Builder", parent: "CMS" } },
  { path: '/admin/page-builder/global/:type', name: 'admin.page-builder.global', component: GlobalSectionBuilder, meta: { requiresAuth: true, title: "Edit Header/Footer", parent: "CMS", layout: 'adminFull' } },
  { path: '/admin/page-builder/form-submissions', name: 'admin.form-submissions', component: FormSubmissions, meta: { requiresAuth: true, title: "Form Submissions", parent: "CMS" } },
  { path: '/admin/page-builder/redirects', name: 'admin.redirects', component: UrlRedirects, meta: { requiresAuth: true, title: "URL Redirects", parent: "CMS" } },
  { path: '/admin/page-builder/analytics', name: 'admin.analytics', component: Analytics, meta: { requiresAuth: true, title: "Analytics", parent: "CMS" } },
  { path: '/admin/page-builder/:id', name: 'admin.page-builder.edit', component: PageBuilder, meta: { requiresAuth: true, title: "Edit Page", parent: "CMS", layout: 'adminFull' } },
  { path: '/admin/blog', name: 'admin.blog', component: Blog, meta: { requiresAuth: true, title: "Blog" } },
  { path: '/admin/events', name: 'admin.events', component: Events, meta: { requiresAuth: true, title: "Events" } },
  { path: '/admin/exhibition', name: 'admin.exhibitions', component: Exhibition, meta: { requiresAuth: true, title: "Exhibition" } },
  { path: '/admin/brochures', name: 'admin.brochures', component: Brochures, meta: { requiresAuth: true, title: "Brochures" } },
  { path: '/admin/feedback', name: 'admin.feedback', component: Feedback, meta: { requiresAuth: true, title: "Feedback" } },

  // -------------------------
  // NEW ADMIN FEATURES
  // -------------------------
  // Warehouse & Inventory
  { path: '/admin/warehouse', name: 'admin.warehouse', component: WarehouseManagement, meta: { requiresAuth: true, title: "Warehouse Management", parent: "Inventory" } },

  // Tax Management
  { path: '/admin/tax-rules', name: 'admin.tax-rules', component: TaxRules, meta: { requiresAuth: true, title: "Tax Rules Engine", parent: "Settings" } },

  // Reports & Analytics
  { path: '/admin/reports', name: 'admin.reports', component: ReportsDashboard, meta: { requiresAuth: true, title: "Reports & Analytics", parent: "Reports" } },

  // Order Management (Advanced)
  { path: '/admin/order-management', name: 'admin.order-management', component: OrderManagement, meta: { requiresAuth: true, title: "Order Management", parent: "Orders" } },
  { path: '/admin/orders/:id', name: 'admin.order-detail', component: OrderManagement, meta: { requiresAuth: true, title: "Order Detail", parent: "Orders" } },

  // Returns & Exchanges
  { path: '/admin/returns', name: 'admin.returns', component: ReturnsExchanges, meta: { requiresAuth: true, title: "Returns & Exchanges", parent: "Orders" } },
  { path: '/admin/returns-management', name: 'admin.returns-management', component: AdminReturns, meta: { requiresAuth: true, title: "Returns Management", parent: "Orders" } },

  // Partner Inquiries (Admin)
  { path: '/admin/partner-inquiries', name: 'admin.partner-inquiries', component: AdminPartnerInquiries, meta: { requiresAuth: true, title: "Partner Inquiries", parent: "Partners" } },

  // Finance
  { path: '/admin/finance', name: 'admin.finance', component: FinanceDashboard, meta: { requiresAuth: true, title: "Finance Dashboard", parent: "Finance" } },

  // Support Tickets
  { path: '/admin/support', name: 'admin.support', component: SupportTickets, meta: { requiresAuth: true, title: "Support Tickets", parent: "Support" } },

  // User Management
  { path: '/admin/users', name: 'admin.users', component: UserManagement, meta: { requiresAuth: true, title: "User Management", parent: "Settings" } },

  // Advanced Offers
  { path: '/admin/advanced-offers', name: 'admin.advanced-offers', component: AdvancedOffers, meta: { requiresAuth: true, title: "Advanced Offers", parent: "Marketing" } },

  // Loyalty Management
  { path: '/admin/loyalty', name: 'admin.loyalty', component: LoyaltyManagement, meta: { requiresAuth: true, title: "Loyalty Management", parent: "Marketing" } },

  // Bulk Operations
  { path: '/admin/products/bulk', name: 'admin.products.bulk', component: BulkOperations, meta: { requiresAuth: true, title: "Bulk Operations", parent: "Products" } },

  // -------------------------
  // SETTINGS
  // -------------------------
  { path: '/admin/settings', name: 'admin.settings', component: Settings, meta: { requiresAuth: true, title: "Settings", parent: 'Settings' } },

  // Org Structure
  { path: '/admin/settings/org-structure', name: 'admin.orgstructure', component: Settings, meta: { requiresAuth: true, title: "Organization Structure", parent: 'Settings' } },
  { path: '/admin/settings/org-structure/branch', name: 'org.branch', component: Branch, meta: { requiresAuth: true, title: "Branches", parent: 'Settings' } },
  { path: '/admin/settings/org-structure/department', name: 'org.department', component: Department, meta: { requiresAuth: true, title: "Departments", parent: 'Settings' } },
  { path: '/admin/settings/org-structure/role', name: 'org.role', component: Role, meta: { requiresAuth: true, title: "Roles", parent: 'Settings' } },
  { path: '/admin/employees/view/:id', name: 'admin.ViewEmployee', component: ViewEmployee, meta: { requiresAuth: true, title: "View Employee", parent: 'Settings' } },

  // Masters
  { path: '/admin/settings/masters', name: 'admin.masters', component: Masters, meta: { requiresAuth: true, title: "Masters", parent: 'Settings' } },
  { path: '/admin/masters/category', name: 'masters.category', component: Category, meta: { requiresAuth: true, title: "Category", parent: 'Settings' } },
  { path: '/admin/masters/sub-category', name: 'masters.subcategory', component: SubCategory, meta: { requiresAuth: true, title: "Sub Category", parent: 'Settings' } },
  { path: '/admin/masters/tags', name: 'masters.tags', component: Tags, meta: { requiresAuth: true, title: "Tags", parent: 'Settings' } },
  { path: '/admin/masters/material-type', name: 'masters.material', component: MaterialType, meta: { requiresAuth: true, title: "Material Type", parent: 'Settings' } },
  { path: '/admin/masters/attributes', name: 'masters.attributes', component: Attributes, meta: { requiresAuth: true, title: "Attributes", parent: 'Settings' } },
  { path: '/admin/masters/gemstone-rate', name: 'masters.gemstone-rate', component: GemstoneRate, meta: { requiresAuth: true, title: "Gemstone Rate", parent: 'Settings' } },
  { path: '/admin/masters/additional-charges', name: 'masters.additional-charges', component: AdditionalCharges, meta: { requiresAuth: true, title: "Additional Charges", parent: 'Settings' } },
  { path: '/admin/masters/tax', name: 'masters.tax', component: Tax, meta: { requiresAuth: true, title: "Tax", parent: 'Settings' } },
  { path: '/admin/masters/product-type', name: 'masters.product-type', component: ProductType, meta: { requiresAuth: true, title: "Product Type", parent: 'Settings' } },
  { path: '/admin/metal-type/view/:id', name: 'admin.ViewMetalType', component: ViewMetalType, meta: { requiresAuth: true, title: "Metal Type", parent: 'Settings' } },

  //products

  { path: '/admin/products', name: 'admin.products', component: ProductList, meta: { requiresAuth: true, title: "Products", parent: 'Products' } },
  { path: '/admin/products/add', name: 'admin.products.add', component: AddProduct, meta: { requiresAuth: true, title: "Add Products", parent: 'Products' } },
  { path: '/admin/products/edit/:id', name: 'admin.products.edit', component: EditProduct, meta: { requiresAuth: true, title: "Edit Products", parent: 'Products' } },
  { path: '/admin/products/view/:id', name: 'admin.products.view', component: ViewProduct, meta: { requiresAuth: true, title: "View Products", parent: 'Products' } },

  // SEO
  { path: '/admin/seo', name: 'admin.seo', component: Seo, meta: { requiresAuth: true, title: "SEO" } },

  // Logs
  { path: '/admin/settings/logs', name: 'admin.logs', component: ActivityLogs, meta: { requiresAuth: true, title: "Logs" } },
  { path: '/admin/logs/activity', name: 'logs.activity', component: ActivityLogs, meta: { requiresAuth: true, title: "Activity Logs" } },
  { path: '/admin/activity/view/:id', name: 'admin.viewactivity', component: ViewActivity, meta: { requiresAuth: true, title: "View Activity" } },
  { path: '/admin/logs/otp', name: 'logs.otp', component: OtpLogs, meta: { requiresAuth: true, title: "OTP Logs" } },
  { path: '/admin/logs/email', name: 'logs.email', component: EmailLogs, meta: { requiresAuth: true, title: "Email Logs" } },

  // Misc components (no need for titles but added anyway)
  { path: '/form', name: 'form', component: form, meta: { requiresAuth: false, layout: 'full', title: "Form" } },
  { path: '/formWizard', name: 'formWizard', component: formWizard, meta: { requiresAuth: false, layout: 'full', title: "Form Wizard" } },
  { path: '/generalSetting', name: 'generalSetting', component: generalSetting, meta: { requiresAuth: false, layout: 'full', title: "General Setting" } },
  { path: '/modalFrame', name: 'modalFrame', component: modalFrame, meta: { requiresAuth: false, layout: 'full', title: "Modal Frame" } },
  { path: '/noData', name: 'noData', component: noData, meta: { requiresAuth: false, layout: 'full', title: "No Data" } },
  { path: '/permissions', name: 'permissions', component: permissions, meta: { requiresAuth: false, layout: 'full', title: "Permissions" } },
  { path: '/table', name: 'table', component: table, meta: { requiresAuth: false, layout: 'full', title: "Table" } },
  { path: '/tabs', name: 'tabs', component: tabs, meta: { requiresAuth: false, layout: 'full', title: "Tabs" } },
  { path: '/verticalTabs', name: 'verticalTabs', component: verticalTabs, meta: { requiresAuth: false, layout: 'full', title: "Vertical Tabs" } },
  { path: '/viewDetailsMaster', name: 'viewDetailsMaster', component: viewDetailsMaster, meta: { requiresAuth: false, layout: 'full', title: "View Details Master" } },
  { path: '/breadcrumbs', name: 'breadcrumbs', component: breadcrumbs, meta: { requiresAuth: false, layout: 'full', title: "Breadcrumbs" } },

  // CMS Dynamic Pages
  { path: '/page/:slug', name: 'dynamicPage', component: DynamicPage, meta: { requiresAuth: false, layout: 'full', title: "Page" } },

  // Fallback
  { path: '/:pathMatch(.*)*', redirect: '/' },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
  scrollBehavior() {
    return { top: 0 }
  }
})

router.beforeEach((to, from, next) => {
  const adminToken = localStorage.getItem('employee_token')
  const customerToken = localStorage.getItem('customer_token') || localStorage.getItem('token')
  const partnerToken = localStorage.getItem('partner_token')

  // 🔐 Route requires authentication
  if (to.meta.requiresAuth) {

    // CUSTOMER ROUTES (Cart, Checkout, Orders, Dashboard, etc.)
    if (to.meta.authType === 'customer') {
      if (!customerToken) {
        return next({
          name: 'customerLogin',
          query: { redirect: to.fullPath }
        })
      }
    }
    // PARTNER ROUTES (Dashboard, Inquiries, Profile)
    else if (to.meta.authType === 'partner') {
      if (!partnerToken) {
        return next({
          name: 'partnerLogin',
          query: { redirect: to.fullPath }
        })
      }
    }
    // ADMIN ROUTES
    else {
      if (!adminToken) {
        return next({ name: 'adminLogin' })
      }
    }
  }

  // Prevent logged-in admin from visiting admin auth pages
  if (
    ['adminLogin', 'adminForgotPassword', 'adminResetPassword'].includes(to.name) &&
    adminToken
  ) {
    return next({ name: 'admin.dashboard' })
  }

  // Prevent logged-in customer from visiting customer auth pages
  if (
    ['customerLogin', 'customerRegister', 'forgotPassword'].includes(to.name) &&
    customerToken
  ) {
    return next({ name: 'customerDashboard' })
  }

  // Prevent logged-in partner from visiting partner login page
  if (to.name === 'partnerLogin' && partnerToken) {
    return next({ name: 'partnerDashboard' })
  }

  next()
})


export default router
