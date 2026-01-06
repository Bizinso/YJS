<script setup>
import { ref, computed } from 'vue'
import { useRoute } from 'vue-router'

const route = useRoute()
const isCollapsed = ref(false)
const expandedMenus = ref(['dashboard'])

const menuItems = [
    {
        id: 'dashboard',
        label: 'Dashboard',
        icon: 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6',
        route: '/admin/dashboard'
    },
    {
        id: 'orders',
        label: 'Orders',
        icon: 'M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z',
        route: '/admin/orders'
    },
    {
        id: 'products',
        label: 'Products',
        icon: 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4',
        children: [
            { label: 'All Products', route: '/admin/products' },
            { label: 'Add Product', route: '/admin/products/create' },
            { label: 'Categories', route: '/admin/categories' },
            { label: 'Inventory', route: '/admin/inventory' }
        ]
    },
    {
        id: 'customers',
        label: 'Customers',
        icon: 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z',
        route: '/admin/customers'
    },
    {
        id: 'partners',
        label: 'B2B Partners',
        icon: 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4',
        children: [
            { label: 'All Partners', route: '/admin/partners' },
            { label: 'Inquiries', route: '/admin/partner-inquiries' },
            { label: 'Approvals', route: '/admin/partner-approvals' }
        ]
    },
    {
        id: 'offers',
        label: 'Offers & Promotions',
        icon: 'M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7',
        children: [
            { label: 'All Offers', route: '/admin/offers' },
            { label: 'Coupons', route: '/admin/coupons' },
            { label: 'Flash Sales', route: '/admin/flash-sales' }
        ]
    },
    {
        id: 'reports',
        label: 'Reports',
        icon: 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z',
        children: [
            { label: 'Sales Report', route: '/admin/reports/sales' },
            { label: 'Inventory Report', route: '/admin/reports/inventory' },
            { label: 'Customer Analytics', route: '/admin/reports/customers' },
            { label: 'Partner Performance', route: '/admin/reports/partners' }
        ]
    },
    {
        id: 'finance',
        label: 'Finance',
        icon: 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
        route: '/admin/finance'
    },
    {
        id: 'shipping',
        label: 'Shipping',
        icon: 'M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4',
        route: '/admin/shipping'
    },
    {
        id: 'returns',
        label: 'Returns',
        icon: 'M16 15v-1a4 4 0 00-4-4H8m0 0l3 3m-3-3l3-3m9 14V5a2 2 0 00-2-2H6a2 2 0 00-2 2v16l4-2 4 2 4-2 4 2z',
        route: '/admin/returns'
    },
    {
        id: 'content',
        label: 'Content',
        icon: 'M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z',
        children: [
            { label: 'Blog', route: '/admin/blog' },
            { label: 'CMS Pages', route: '/admin/cms' },
            { label: 'Banners', route: '/admin/banners' }
        ]
    },
    {
        id: 'settings',
        label: 'Settings',
        icon: 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z',
        children: [
            { label: 'General', route: '/admin/settings' },
            { label: 'Users & Roles', route: '/admin/users' },
            { label: 'Tax Rules', route: '/admin/tax' },
            { label: 'Warehouses', route: '/admin/warehouses' }
        ]
    }
]

const isActive = (item) => {
    if (item.route) {
        return route.path === item.route || route.path.startsWith(item.route + '/')
    }
    if (item.children) {
        return item.children.some(child => route.path === child.route || route.path.startsWith(child.route + '/'))
    }
    return false
}

const isChildActive = (child) => {
    return route.path === child.route || route.path.startsWith(child.route + '/')
}

const toggleMenu = (id) => {
    const index = expandedMenus.value.indexOf(id)
    if (index > -1) {
        expandedMenus.value.splice(index, 1)
    } else {
        expandedMenus.value.push(id)
    }
}

const isExpanded = (id) => {
    return expandedMenus.value.includes(id)
}
</script>

<template>
    <aside :class="['bg-gray-900 text-white transition-all duration-300 flex flex-col', isCollapsed ? 'w-20' : 'w-64']">
        <!-- Toggle Button -->
        <div class="flex justify-end p-2">
            <button
                @click="isCollapsed = !isCollapsed"
                class="p-2 hover:bg-gray-800 rounded-lg"
            >
                <svg :class="['w-5 h-5 transition-transform', isCollapsed ? 'rotate-180' : '']" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"/>
                </svg>
            </button>
        </div>

        <!-- Menu Items -->
        <nav class="flex-1 overflow-y-auto px-3 pb-4">
            <ul class="space-y-1">
                <li v-for="item in menuItems" :key="item.id">
                    <!-- Menu Item with Children -->
                    <template v-if="item.children">
                        <button
                            @click="toggleMenu(item.id)"
                            :class="[
                                'w-full flex items-center justify-between px-3 py-2.5 rounded-lg transition-colors',
                                isActive(item) ? 'bg-amber-600 text-white' : 'text-gray-300 hover:bg-gray-800'
                            ]"
                        >
                            <div class="flex items-center space-x-3">
                                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="item.icon"/>
                                </svg>
                                <span v-if="!isCollapsed" class="text-sm font-medium">{{ item.label }}</span>
                            </div>
                            <svg v-if="!isCollapsed" :class="['w-4 h-4 transition-transform', isExpanded(item.id) ? 'rotate-180' : '']" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>

                        <!-- Submenu -->
                        <ul v-if="!isCollapsed && isExpanded(item.id)" class="mt-1 ml-8 space-y-1">
                            <li v-for="child in item.children" :key="child.route">
                                <router-link
                                    :to="child.route"
                                    :class="[
                                        'block px-3 py-2 rounded-lg text-sm transition-colors',
                                        isChildActive(child) ? 'bg-amber-600/20 text-amber-400' : 'text-gray-400 hover:text-white hover:bg-gray-800'
                                    ]"
                                >
                                    {{ child.label }}
                                </router-link>
                            </li>
                        </ul>
                    </template>

                    <!-- Simple Menu Item -->
                    <template v-else>
                        <router-link
                            :to="item.route"
                            :class="[
                                'flex items-center space-x-3 px-3 py-2.5 rounded-lg transition-colors',
                                isActive(item) ? 'bg-amber-600 text-white' : 'text-gray-300 hover:bg-gray-800'
                            ]"
                        >
                            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="item.icon"/>
                            </svg>
                            <span v-if="!isCollapsed" class="text-sm font-medium">{{ item.label }}</span>
                        </router-link>
                    </template>
                </li>
            </ul>
        </nav>

        <!-- Footer -->
        <div v-if="!isCollapsed" class="p-4 border-t border-gray-800">
            <div class="text-xs text-gray-500">
                <p>YJS Jewellers Admin</p>
                <p>v1.0.0</p>
            </div>
        </div>
    </aside>
</template>
