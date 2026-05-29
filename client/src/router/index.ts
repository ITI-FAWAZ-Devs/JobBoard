import { createRouter, createWebHistory } from "vue-router";

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: "/",
      name: "home",
      component: () => import("@/views/HomeView.vue"),
    },
    {
      path: "/sign-in",
      name: "Login",
      component: () => import("@/views/LoginView.vue"),
      meta: { requiresGuest: true },
    },
    {
      path: "/role-select",
      name: "RoleSelect",
      component: () => import("@/views/RoleSelectView.vue"),
      meta: { requiresGuest: true },
    },
    {
      path: "/sign-up",
      name: "Register",
      component: () => import("@/views/RegisterView.vue"),
      meta: { requiresGuest: true },
    },
    {
      path: "/:pathMatch(.*)*",
      name: "NotFound",
      component: () => import("@/views/NotFoundView.vue"),
    },
  ],
});

export default router;
