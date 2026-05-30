import { createRouter, createWebHistory } from "vue-router";
import { getProfileApi } from "@/api/profile";
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
      name: "AdminDashboard",
      component: () => import("@/views/Admin/DashboardView.vue"),
      meta: { requiresAuth: true, requiresRole: "admin" },
    },
    {
      path: "/admin/pending-jobs",
      name: "AdminPendingJobs",
      component: () => import("@/views/Admin/PendingJobsView.vue"),
      meta: { requiresAuth: true, requiresRole: "admin" },
    },
    {
      path: "/admin/users",
      name: "AdminUsers",
      component: () => import("@/views/Admin/UserManagementView.vue"),
      meta: { requiresAuth: true, requiresRole: "admin" },
    },
    {
      path: "/employer/analytics",
      name: "EmployerAnalytics",
      component: () => import("@/views/employer/AnalyticsView.vue"),
      meta: { requiresAuth: true, requiresRole: "employer" },
    },
    {
      path: "/employer/checkout",
      name: "EmployerCheckout",
      component: () => import("@/views/Employee/CheckoutView.vue"),
      meta: { requiresAuth: true, requiresRole: "employer" },
    },
    {
      path: "/admin/comments",
      name: "AdminComments",
      component: () => import("@/views/admin/CommentModerationView.vue"),
      meta: { requiresAuth: true, requiresRole: "admin" },
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

const getStoredUser = () => {
  const raw = localStorage.getItem("user");
  if (!raw) return null;
  try {
    return JSON.parse(raw);
  } catch {
    return null;
  }
};

const setStoredUser = (user: unknown) => {
  if (!user) return;
  localStorage.setItem("user", JSON.stringify(user));
};

router.beforeEach(async (to) => {
  const token = localStorage.getItem("token");
  let user = getStoredUser();
  const requiresAuth = Boolean(to.meta?.requiresAuth);
  const requiresGuest = Boolean(to.meta?.requiresGuest);
  const requiredRole = (to.meta?.requiresRole as string | undefined) ?? undefined;

  if (requiresGuest && token) {
    return { path: "/" };
  }

  if (requiresAuth && !token) {
    return { path: "/sign-in", query: { redirect: to.fullPath } };
  }

  if (token && !user) {
    try {
      user = await getProfileApi();
      setStoredUser(user);
    } catch {
      localStorage.removeItem("token");
      localStorage.removeItem("user");
      if (requiresAuth) {
        return { path: "/sign-in", query: { redirect: to.fullPath } };
      }
    }
  }

  if (requiredRole && user?.role !== requiredRole) {
    return { path: "/" };
  }

  return true;
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
