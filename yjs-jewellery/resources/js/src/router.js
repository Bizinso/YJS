// src/router.js
import { createRouter, createWebHistory } from "vue-router";
import Login from "./views/authentication/Login.vue";


const routes = [
  { path: "/login", component: Login, name: "Login",  meta: { layout: 'full' } },
];

const router = createRouter({
  history: createWebHistory(),
  routes,
  scrollBehavior() {
    return { top: 0 }
  }
});

// Navigation guard
router.beforeEach((to, from, next) => {
  const token = localStorage.getItem('access_token');

  // Check if the route requires authentication
  if (to.matched.some(record => record.meta.requiresAuth)) {
    if (!token) {
      // If no token, redirect to login
      next({ path: '/login' }); // Adjusted to redirect to the login route
    } else {
      next(); // Proceed to route
    }
  } else {
    next(); // Proceed to route if no auth required
  }
});

export default router;
