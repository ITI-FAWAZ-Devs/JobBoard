import { createRouter, createWebHistory } from "vue-router";
import type { RouteLocationNormalized, NavigationGuardNext } from "vue-router";
import { useAuth } from "@/composables/useAuth";

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
      path: "/admin",
      component: () => import("@/layouts/RoleSidebarLayout.vue"),
      meta: { requiresAuth: true, requiredRole: "admin" },
      children: [
        {
          path: "dashboard",
          name: "AdminDashboard",
          component: () => import("@/views/Admin/DashboardView.vue"),
        },
        {
          path: "profile",
          name: "AdminProfile",
          component: () => import("@/views/Admin/ProfileView.vue"),
        },
        {
          path: "notifications",
          name: "AdminNotifications",
          component: () => import("@/views/Admin/NotificationsView.vue"),
        },
        {
          path: "settings",
          name: "AdminSettings",
          component: () => import("@/views/Admin/SettingsView.vue"),
        },
      ],
    },
    {
      path: "/candidate",
      component: () => import("@/layouts/RoleSidebarLayout.vue"),
      meta: { requiresAuth: true, requiredRole: "candidate" },
      children: [
        {
          path: "dashboard",
          name: "CandidateDashboard",
          component: () => import("@/views/Candidate/DashboardView.vue"),
        },
        {
          path: "profile",
          name: "CandidateProfile",
          component: () => import("@/views/Candidate/ProfileView.vue"),
        },
        {
          path: "notifications",
          name: "CandidateNotifications",
          component: () => import("@/views/Candidate/NotificationsView.vue"),
        },
        {
          path: "settings",
          name: "CandidateSettings",
          component: () => import("@/views/Candidate/SettingsView.vue"),
        },
      ],
    },
    {
      path: "/employee",
      component: () => import("@/layouts/RoleSidebarLayout.vue"),
      meta: { requiresAuth: true, requiredRole: "employer" },
      children: [
        {
          path: "dashboard",
          name: "EmployeeDashboard",
          component: () => import("@/views/Employee/DashboardView.vue"),
        },
        {
          path: "profile",
          name: "EmployeeProfile",
          component: () => import("@/views/Employee/ProfileView.vue"),
        },
        {
          path: "notifications",
          name: "EmployeeNotifications",
          component: () => import("@/views/Employee/NotificationsView.vue"),
        },
        {
          path: "settings",
          name: "EmployeeSettings",
          component: () => import("@/views/Employee/SettingsView.vue"),
        },
      ],
    },
    {
      path: "/:pathMatch(.*)*",
      name: "NotFound",
      component: () => import("@/views/NotFoundView.vue"),
    },
  ],
});

// Route guards
router.beforeEach(
  async (
    to: RouteLocationNormalized,
    from: RouteLocationNormalized,
    next: NavigationGuardNext,
  ) => {
    const { user, userRole, fetchUserProfile } = useAuth();

    // Check if route requires authentication
    if (to.meta.requiresAuth) {
      const token = localStorage.getItem("token");

      if (!token) {
        next({ name: "Login", query: { redirect: to.fullPath } });
        return;
      }

      // Fetch user profile if not already fetched
      if (!user.value) {
        try {
          await fetchUserProfile();
        } catch (error) {
          console.error("Failed to fetch profile:", error);
          next({ name: "Login", query: { redirect: to.fullPath } });
          return;
        }
      }

      // Check if user has the required role
      const requiredRole = to.meta.requiredRole as string;
      if (userRole.value !== requiredRole) {
        next({ name: "home" });
        return;
      }
    }

    // Check if route requires guest (not authenticated)
    if (to.meta.requiresGuest) {
      const token = localStorage.getItem("token");
      if (token) {
        next({ name: "home" });
        return;
      }
    }

    next();
  },
);

export default router;
